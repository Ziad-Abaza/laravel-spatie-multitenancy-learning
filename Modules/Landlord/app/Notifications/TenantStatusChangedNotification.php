<?php

namespace Modules\Landlord\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * Sent to the workspace owner when the lifecycle service transitions the
 * tenant (suspend / activate / archive). Delivers over mail only, and
 * synchronously — landlord-side dispatch must not be tenant-queue-bound.
 */
class TenantStatusChangedNotification extends Notification
{
    public function __construct(
        protected IsTenant $tenant,
        protected string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('mail_tenant_status_subject', ['tenant' => $this->tenant->name]))
            ->line(__('mail_tenant_status_body', [
                'tenant' => $this->tenant->name,
                'status' => $this->newStatus,
            ]))
            ->when(
                $reason = $this->tenant->settings['suspension_reason'] ?? null,
                fn (MailMessage $mail) => $mail->line(__('mail_tenant_status_reason', ['reason' => $reason]))
            );
    }
}
