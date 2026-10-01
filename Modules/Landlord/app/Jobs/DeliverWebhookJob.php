<?php

namespace Modules\Landlord\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Modules\Landlord\Models\WebhookDelivery;
use Spatie\Multitenancy\Jobs\NotTenantAware;

/**
 * Outbound delivery — always landlord-context (`NotTenantAware`): the payload
 * is prebuilt at dispatch time and only touches landlord tables. Retries with
 * backoff; after `tries` the delivery is marked dead and stays inspectable.
 */
class DeliverWebhookJob implements NotTenantAware, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public int $deliveryId
    ) {}

    public function backoff(): array
    {
        return [10, 60, 300, 900, 3600];
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::with('endpoint')->find($this->deliveryId);

        if ($delivery === null || $delivery->endpoint === null || ! $delivery->endpoint->active) {
            return;
        }

        $endpoint = $delivery->endpoint;
        $body = json_encode($delivery->payload);

        $delivery->increment('attempts');

        $response = Http::timeout(15)
            ->withHeaders([
                'X-Webhook-Signature' => $endpoint->signatureFor($body),
                'X-Webhook-Event' => $delivery->event,
            ])
            ->withBody($body, 'application/json')
            ->post($endpoint->url);

        $delivery->update([
            'response_status' => $response->status(),
            'response_body' => substr($response->body(), 0, 2000),
        ]);

        if ($response->successful()) {
            $delivery->update([
                'status' => WebhookDelivery::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            return;
        }

        $delivery->update(['status' => WebhookDelivery::STATUS_FAILED]);

        // The thrown exception lets the worker retry with `backoff()`; after
        // `tries` is exhausted, failed() marks the delivery dead — the row
        // stays inspectable in the registry between attempts either way.
        throw new \RuntimeException("Webhook delivery {$delivery->id} got HTTP {$response->status()}.");
    }

    public function failed(\Throwable $e): void
    {
        WebhookDelivery::where('id', $this->deliveryId)
            ->update(['status' => WebhookDelivery::STATUS_DEAD]);
    }
}
