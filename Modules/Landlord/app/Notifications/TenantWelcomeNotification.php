<?php

namespace Modules\Landlord\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\Multitenancy\Contracts\IsTenant;

/**
 * Sent to the workspace owner right after provisioning. Delivers over mail
 * only — the in-app channel is deferred until a concrete need exists.
 *
 * Deliberately synchronous: `queues_are_tenant_aware_by_default` would bind
 * (or reject) a queued job dispatched outside tenant context, and lifecycle
 * events fire on the landlord side.
 */
class TenantWelcomeNotification extends Notification
{
    public function __construct(
        protected IsTenant $tenant
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('mail_tenant_welcome_subject', ['tenant' => $this->tenant->name]))
            ->line(__('mail_tenant_welcome_body', ['tenant' => $this->tenant->name]))
            ->action(__('mail_tenant_welcome_action'), "http://{$this->tenant->domain}");
    }
}
