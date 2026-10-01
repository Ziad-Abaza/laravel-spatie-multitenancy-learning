<?php

namespace Modules\Landlord\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Events\TenantCreated;
use Modules\Landlord\Notifications\TenantWelcomeNotification;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class SendTenantWelcomeNotification implements NotTenantAware, ShouldQueue
{
    public function handle(TenantCreated $event): void
    {
        try {
            $email = $event->adminData['email'] ?? null;

            if ($email === null) {
                return;
            }

            Notification::route('mail', $email)
                ->notify(new TenantWelcomeNotification($event->tenant));
        } catch (\Throwable $e) {
            // Welcome mail must never break provisioning.
            report($e);
        }
    }
}
