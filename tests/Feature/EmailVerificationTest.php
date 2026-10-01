<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Core\Enums\UserStatus;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    protected function unverifiedMember(Tenant $tenant): User
    {
        return $tenant->execute(fn () => User::create([
            'name' => 'Unverified Member',
            'email' => 'unverified-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
            'status' => UserStatus::Active->value,
            'email_verified_at' => null,
        ]));
    }

    public function test_unverified_user_is_redirected_from_protected_routes(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $user = $this->unverifiedMember($tenant);

        try {
            $this->actingAs($user, 'web')
                ->get("http://{$tenant->domain}/dashboard")
                ->assertRedirect("http://{$tenant->domain}/verify-email");

            $this->actingAs($user, 'web')
                ->get("http://{$tenant->domain}/verify-email")
                ->assertOk();
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_signed_link_verifies_the_user_on_tenant_host(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $user = $this->unverifiedMember($tenant);

        try {
            $relative = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)],
                absolute: false
            );

            $this->actingAs($user, 'web')
                ->get("http://{$tenant->domain}{$relative}")
                ->assertRedirect("http://{$tenant->domain}/dashboard");

            $tenant->execute(fn () => $this->assertNotNull($user->fresh()->email_verified_at));
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_resend_sends_verification_notification_with_tenant_domain_url(): void
    {
        Notification::fake();

        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $user = $this->unverifiedMember($tenant);

        try {
            $this->actingAs($user, 'web')
                ->post("http://{$tenant->domain}/email/verification-notification")
                ->assertSessionHas('status');

            Notification::assertSentTo(
                $user,
                VerifyEmail::class,
                fn ($notification) => str_contains($notification->toMail($user)->actionUrl ?? '', $tenant->domain)
            );
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_register_fires_verification_and_lands_on_notice(): void
    {
        Notification::fake();

        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            $email = 'fresh-'.uniqid().'@test.local';

            $this->post("http://{$tenant->domain}/register", [
                'name' => 'Fresh Member',
                'email' => $email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertRedirect("http://{$tenant->domain}/verify-email");

            $tenant->execute(function () use ($email) {
                $user = User::where('email', $email)->firstOrFail();
                $this->assertNull($user->email_verified_at);
            });
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_honeypot_rejects_bot_registration(): void
    {
        $tenant = $this->provisionTenant(['slug' => 'tenant1']);

        try {
            $this->post("http://{$tenant->domain}/register", [
                'name' => 'Bot',
                'email' => 'bot-'.uniqid().'@test.local',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'website' => 'https://spam.example',
            ])->assertSessionHasErrors('website');
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_landlord_registration_honeypot_rejects_bots(): void
    {
        $this->post('/register-tenant', [
            'organization_name' => 'Bot Org',
            'subdomain' => 'botorg'.substr(uniqid(), -4),
            'admin_name' => 'Bot Admin',
            'admin_email' => 'bot-'.uniqid().'@test.local',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'website' => 'https://spam.example',
        ])->assertSessionHasErrors('website');
    }
}
