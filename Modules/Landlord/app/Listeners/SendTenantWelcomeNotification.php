<?php

namespace Modules\Landlord\Listeners;

use Illuminate\Support\Facades\Notification;
use Modules\Core\Events\TenantCreated;
use Modules\Landlord\Notifications\TenantWelcomeNotification;

class SendTenantWelcomeNotification
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
