<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Core\Enums\UserStatus;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function tenantWithMember(): array
    {
        $tenant = $this->provisionTenant([
            'slug' => 'tenant1',
            'admin_email' => 'owner-'.uniqid().'@test.local',
        ]);

        $member = $tenant->execute(fn () => User::create([
            'name' => 'Reset Member',
            'email' => 'reset-'.uniqid().'@test.local',
            'password' => bcrypt('old-password'),            'email_verified_at' => now(),
            'status' => UserStatus::Active->value,
        ]));

        return [$tenant, $member];
    }

    public function test_forgot_password_page_renders_on_tenant_host(): void
    {
        [$tenant] = $this->tenantWithMember();

        $this->get("http://{$tenant->domain}/forgot-password")->assertOk();
        $this->get("http://{$tenant->domain}/reset-password/some-token?email=a@b.c")->assertOk();
    }

    public function test_reset_link_is_sent_to_existing_tenant_user(): void
    {
        Notification::fake();

        [$tenant, $member] = $this->tenantWithMember();

        try {
            $this->post("http://{$tenant->domain}/forgot-password", [
                'email' => $member->email,
            ])->assertSessionHas('status');

            Notification::assertSentTo($member, ResetPassword::class);

            // The link must target the tenant host — never the landlord host.
            Notification::assertSentTo(
                $member,
                ResetPassword::class,
                fn ($notification, $channels, $notifiable) => str_contains($notification->toMail($notifiable)->actionUrl ?? '', $tenant->domain)
            );
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        [$tenant, $member] = $this->tenantWithMember();

        try {
            $token = $tenant->execute(fn () => Password::broker('users')->createToken($member));

            $this->post("http://{$tenant->domain}/reset-password", [
                'token' => $token,
                'email' => $member->email,
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])->assertRedirect("http://{$tenant->domain}/login");

            $tenant->execute(function () use ($member) {
                $this->assertTrue(Hash::check('brand-new-password', $member->fresh()->password));
            });
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_invalid_token_is_rejected(): void
    {
        [$tenant, $member] = $this->tenantWithMember();

        try {
            $this->post("http://{$tenant->domain}/reset-password", [
                'token' => 'bogus-token',
                'email' => $member->email,
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])->assertSessionHasErrors('email');

            $tenant->execute(function () use ($member) {
                $this->assertTrue(Hash::check('old-password', $member->fresh()->password));
            });
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_unknown_email_does_not_leak_user_existence(): void
    {
        [$tenant] = $this->tenantWithMember();

        try {
            $this->post("http://{$tenant->domain}/forgot-password", [
                'email' => 'nobody-'.uniqid().'@test.local',
            ])->assertSessionHasErrors('email');
        } finally {
            Tenant::forgetCurrent();
        }
    }
}
