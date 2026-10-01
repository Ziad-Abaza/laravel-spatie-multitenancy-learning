<?php

namespace Modules\Landlord\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\Events\PlanChanged;
use Modules\Core\Events\SubscriptionCreated;
use Modules\Core\Events\SubscriptionUpdated;
use Modules\Core\Events\TenantCreated;
use Modules\Core\Events\TenantProvisioned;
use Modules\Core\Events\TenantStatusChanged;
use Modules\Landlord\Jobs\DeliverWebhookJob;
use Modules\Landlord\Models\WebhookDelivery;
use Modules\Landlord\Models\WebhookEndpoint;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * Fans out subscribed domain events to registered webhook endpoints.
 * Payloads carry tenant identifiers and event metadata only — credentials,
 * PII, and tenant database content never cross the wire.
 */
class DispatchDomainEventWebhooks implements NotTenantAware, ShouldQueue
{
    public function handle(object $event): void
    {
        $eventKey = $this->eventKey($event);

        $payload = $this->payloadFor($event);

        if ($payload === null) {
            return;
        }

        WebhookEndpoint::query()
            ->where('active', true)
            ->whereJsonContains('events', $eventKey)
            ->get()
            ->each(function (WebhookEndpoint $endpoint) use ($eventKey, $payload) {
                $delivery = WebhookDelivery::create([
                    'webhook_endpoint_id' => $endpoint->id,
                    'event' => $eventKey,
                    'payload' => $payload,
                    'status' => WebhookDelivery::STATUS_PENDING,
                ]);

                DeliverWebhookJob::dispatch($delivery->id);
            });
    }

    /**
     * Kebab-dotted contract key — stable across refactors, never derived
     * from PHP class paths in the payload contract.
     */
    private function eventKey(object $event): string
    {
        return match (true) {
            $event instanceof TenantCreated => 'tenant.created',
            $event instanceof TenantProvisioned => 'tenant.provisioned',
            $event instanceof TenantStatusChanged => 'tenant.status-changed',
            $event instanceof PlanChanged => 'plan.changed',
            $event instanceof SubscriptionCreated => 'subscription.created',
            $event instanceof SubscriptionUpdated => 'subscription.updated',
            default => '',
        };
    }

    /**
     * Whitelist payload per event — adminData (which can carry credentials)
     * and any unlisted field are never serialized.
     *
     * @return array<string, mixed>|null
     */
    private function payloadFor(object $event): ?array
    {
        $tenant = $event->tenant ?? $event->subscription?->tenant ?? null;
        $base = [
            'tenant' => $tenant === null ? null : [
                'id' => $tenant->id,
                'name' => $tenant->name ?? null,
                'slug' => $tenant->slug ?? null,
                'domain' => $tenant->domain ?? null,
            ],
            'occurred_at' => now()->toIso8601String(),
        ];

        return match (true) {
            $event instanceof TenantCreated => $base + [
                'plan_id' => $event->planId,
                'admin_email' => $event->adminData['email'] ?? null,
            ],
            $event instanceof TenantProvisioned => $base,
            $event instanceof TenantStatusChanged => $base + [
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ],
            $event instanceof PlanChanged => $base + [
                'old_plan' => $event->oldPlan?->name ?? null,
                'new_plan' => $event->newPlan?->name ?? null,
            ],
            $event instanceof SubscriptionCreated,
            $event instanceof SubscriptionUpdated => $base + [
                'subscription' => [
                    'id' => $event->subscription->id,
                    'status' => $event->subscription->status,
                    'plan_id' => $event->subscription->plan_id,
                    'billing_interval' => $event->subscription->billing_interval ?? null,
                ],
                'old_status' => $event->oldStatus ?? null,
                'new_status' => $event->newStatus ?? null,
            ],
            default => null,
        };
    }
}
