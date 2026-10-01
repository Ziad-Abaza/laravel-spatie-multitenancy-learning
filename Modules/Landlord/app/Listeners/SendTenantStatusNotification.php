<?php

namespace Modules\Landlord\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Events\TenantStatusChanged;
use Modules\Landlord\Notifications\TenantStatusChangedNotification;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class SendTenantStatusNotification implements NotTenantAware, ShouldQueue
{
    public function handle(TenantStatusChanged $event): void
    {
        try {
            // The owner address lives in the tenant database — resolve it
            // inside the tenant context, then deliver from the landlord side.
            $email = $event->tenant->execute(
                fn () => User::role(TenantPermissions::ROLE_OWNER)->value('email')
            );

            if ($email === null) {
                return;
            }

            Notification::route('mail', $email)
                ->notify(new TenantStatusChangedNotification($event->tenant, $event->newStatus));
        } catch (\Throwable $e) {
            // Notification delivery is best-effort: a missing tenant database
            // (record-only fixtures, dropped schemas) must never break the
            // lifecycle operation that emitted the event.
            report($e);
        }
    }
}
