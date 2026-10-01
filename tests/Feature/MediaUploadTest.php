<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Enums\UserStatus;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    protected function provisionOwner(Tenant $tenant): User
    {
        return $tenant->execute(function () {
            $owner = User::create([
                'name' => 'Media Owner',
                'email' => 'media-owner-'.uniqid().'@test.local',
                'password' => bcrypt('password'),                'email_verified_at' => now(),
                'status' => UserStatus::Active->value,
            ]);
            $owner->assignRole(TenantPermissions::ROLE_OWNER);

            return $owner;
        });
    }

    public function test_owner_can_upload_workspace_logo_with_tenant_scoped_path(): void
    {
        Storage::fake('public');

        $tenant = $this->provisionTenant([
            'slug' => 'tenant1',
            'admin_email' => 'owner-'.uniqid().'@test.local',
        ]);

        $owner = $this->provisionOwner($tenant);

        try {
            $this->actingAs($owner, 'web')
                ->post('http://tenant1.localhost/settings', [
                    'domain' => 'branding',
                    'workspace_name' => 'Tenant One',
                    'settings' => ['tagline' => 'We ship'],
                    'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
                ])
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $media = $tenant->fresh()->getFirstMedia('logo');

            $this->assertNotNull($media, 'Logo was never attached to the tenant record');
            // Logo rows live on the landlord connection with the tenant row.
            $this->assertDatabaseHas('media', [
                'id' => $media->id,
                'collection_name' => 'logo',
                'model_type' => get_class($tenant),
            ], 'landlord');
            // Path is scoped to the owning tenant — never a bare media id.
            $this->assertStringContainsString("tenants/{$tenant->id}/", $media->getPath());
            Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_member_cannot_upload_workspace_logo(): void
    {
        Storage::fake('public');

        $tenant = $this->provisionTenant([
            'slug' => 'tenant1',
            'admin_email' => 'owner-'.uniqid().'@test.local',
        ]);

        $member = $tenant->execute(function () {
            $member = User::create([
                'name' => 'Plain Member',
                'email' => 'member-'.uniqid().'@test.local',
                'password' => bcrypt('password'),                'email_verified_at' => now(),
                'status' => UserStatus::Active->value,
            ]);
            $member->assignRole(TenantPermissions::ROLE_MEMBER);

            return $member;
        });

        try {
            $this->actingAs($member, 'web')
                ->post('http://tenant1.localhost/settings', [
                    'domain' => 'branding',
                    'settings' => [],
                    'logo' => UploadedFile::fake()->image('logo.png'),
                ])
                ->assertForbidden();
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_user_can_upload_avatar_via_profile(): void
    {
        Storage::fake('public');

        $tenant = $this->provisionTenant([
            'slug' => 'tenant1',
            'admin_email' => 'owner-'.uniqid().'@test.local',
        ]);

        $member = $tenant->execute(function () {
            $member = User::create([
                'name' => 'Avatar Member',
                'email' => 'avatar-'.uniqid().'@test.local',
                'password' => bcrypt('password'),                'email_verified_at' => now(),
                'status' => UserStatus::Active->value,
            ]);
            $member->assignRole(TenantPermissions::ROLE_MEMBER);

            return $member;
        });

        try {
            $this->actingAs($member, 'web')
                ->put('http://tenant1.localhost/profile', [
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => UploadedFile::fake()->image('avatar.jpg', 128, 128),
                ])
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $media = $member->fresh()->getFirstMedia('avatars');

            $this->assertNotNull($media, 'Avatar was never attached to the user');
            // Avatar rows live in the tenant database with the user row.
            $this->assertDatabaseHas('media', [
                'id' => $media->id,
                'collection_name' => 'avatars',
            ], 'tenant');
            $this->assertStringContainsString("tenants/{$tenant->id}/", $media->getPath());
            // getAvatarUrl now resolves to the stored file, not the gravatar fallback.
            $this->assertStringNotContainsString('gravatar.com', $member->fresh()->getAvatarUrl());
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        Storage::fake('public');

        $tenant = $this->provisionTenant([
            'slug' => 'tenant1',
            'admin_email' => 'owner-'.uniqid().'@test.local',
        ]);

        $owner = $this->provisionOwner($tenant);

        try {
            $this->actingAs($owner, 'web')
                ->post('http://tenant1.localhost/settings', [
                    'domain' => 'branding',
                    'settings' => [],
                    'logo' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
                ])
                ->assertSessionHasErrors('logo');

            $this->assertNull($tenant->fresh()->getFirstMedia('logo'));
        } finally {
            Tenant::forgetCurrent();
        }
    }
}
