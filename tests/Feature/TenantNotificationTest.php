<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Modules\Core\Enums\TenantStatus;
use Modules\Landlord\Notifications\TenantStatusChangedNotification;
use Modules\Landlord\Notifications\TenantWelcomeNotification;
use Modules\Landlord\Services\TenantLifecycleService;
use Tests\TestCase;

class TenantNotificationTest extends TestCase
{
    public function test_provisioning_sends_welcome_email_to_owner(): void
    {
        Notification::fake();

        $ownerEmail = 'owner-'.uniqid().'@test.local';

        $this->provisionTenant([
            'slug' => 'welcome-'.substr(uniqid(), -6),
            'admin_email' => $ownerEmail,
        ]);

        Notification::assertSentOnDemand(
            TenantWelcomeNotification::class,
            fn ($notification, $channels, $notifiable) => ($notifiable->routes['mail'] ?? null) === $ownerEmail
        );
    }

    public function test_suspension_notifies_the_workspace_owner(): void
    {
        $ownerEmail = 'owner-'.uniqid().'@test.local';

        $tenant = $this->provisionTenant([
            'slug' => 'suspend-'.substr(uniqid(), -6),
            'admin_email' => $ownerEmail,
        ]);

        // Fake AFTER provisioning so the welcome mail doesn't mask the lookup.
        Notification::fake();

        app(TenantLifecycleService::class)->suspend($tenant, 'Payment overdue');

        Notification::assertSentOnDemand(
            TenantStatusChangedNotification::class,
            fn ($notification, $channels, $notifiable) => ($notifiable->routes['mail'] ?? null) === $ownerEmail
        );
    }

    public function test_status_change_on_record_only_tenant_does_not_explode(): void
    {
        // Record-only tenants have no database — notification delivery must
        // degrade gracefully, never break the lifecycle operation.
        Notification::fake();

        $tenant = $this->createTenantRecord([
            'slug' => 'record-only',
            'status' => TenantStatus::Active,
        ]);

        app(TenantLifecycleService::class)->suspend($tenant, 'No DB');

        $this->assertSame(TenantStatus::Suspended, $tenant->fresh()->status);
        Notification::assertNothingSent();
    }
}
