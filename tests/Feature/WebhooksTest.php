<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Access\Support\LandlordPermissions as LP;
use Modules\Core\Enums\TenantStatus;
use Modules\Core\Events\TenantStatusChanged;
use Modules\Landlord\Jobs\DeliverWebhookJob;
use Modules\Landlord\Models\WebhookDelivery;
use Modules\Landlord\Models\WebhookEndpoint;
use Tests\TestCase;

class WebhooksTest extends TestCase
{
    public function test_endpoint_index_and_store_are_permission_gated(): void
    {
        $viewer = $this->makeScopedAdmin('whv', [LP::WEBHOOKS_VIEW]);
        $manager = $this->makeScopedAdmin('whm', [LP::WEBHOOKS_VIEW, LP::WEBHOOKS_MANAGE]);
        $none = $this->makeScopedAdmin('whn', [LP::TENANTS_VIEW]);

        try {
            $this->actingAs($none, 'landlord')->get('/landlord/webhooks')->assertForbidden();
            $this->flushSession();
            $this->actingAs($viewer, 'landlord')->get('/landlord/webhooks')->assertOk();
            $this->actingAs($viewer, 'landlord')->post('/landlord/webhooks', [])->assertForbidden();
            $this->flushSession();

            $this->actingAs($manager, 'landlord')
                ->post('/landlord/webhooks', [
                    'name' => 'Ops sink',
                    'url' => 'https://hooks.example.com/ingest',
                    'events' => ['tenant.status-changed'],
                ])
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('webhook_endpoints', [
                'name' => 'Ops sink',
                'url' => 'https://hooks.example.com/ingest',
            ]);
        } finally {
            $this->cleanup($viewer);
            $this->cleanup($manager);
            $this->cleanup($none);
        }
    }

    public function test_http_urls_are_rejected(): void
    {
        $manager = $this->makeScopedAdmin('whm', [LP::WEBHOOKS_MANAGE]);

        try {
            $this->actingAs($manager, 'landlord')
                ->post('/landlord/webhooks', [
                    'name' => 'Insecure',
                    'url' => 'http://insecure.example.com/hook',
                    'events' => ['tenant.created'],
                ])
                ->assertSessionHasErrors('url');
        } finally {
            $this->cleanup($manager);
        }
    }

    public function test_domain_event_creates_delivery_and_dispatches_job(): void
    {
        Queue::fake();

        $tenant = $this->provisionTenant(['slug' => 'tenant1']);
        $endpoint = WebhookEndpoint::create([
            'name' => 'Sink',
            'url' => 'https://hooks.example.com/x',
            'secret' => 'whsec_testsecret',
            'events' => ['tenant.status-changed'],
        ]);
        // An endpoint not subscribed to this event gets nothing.
        $other = WebhookEndpoint::create([
            'name' => 'Other',
            'url' => 'https://hooks.example.com/y',
            'secret' => 'whsec_other',
            'events' => ['tenant.created'],
        ]);

        try {
            event(new TenantStatusChanged($tenant, TenantStatus::Active->value, TenantStatus::Suspended->value));

            $delivery = WebhookDelivery::where('webhook_endpoint_id', $endpoint->id)->firstOrFail();
            $this->assertSame('tenant.status-changed', $delivery->event);
            $this->assertSame('pending', $delivery->status);
            $this->assertSame('suspended', $delivery->payload['new_status']);
            $this->assertSame($tenant->id, $delivery->payload['tenant']['id']);

            $this->assertSame(0, WebhookDelivery::where('webhook_endpoint_id', $other->id)->count());

            Queue::assertPushed(DeliverWebhookJob::class);
        } finally {
            Tenant::forgetCurrent();
        }
    }

    public function test_delivery_sends_hmac_signed_payload(): void
    {
        Http::fake(['https://hooks.example.com/*' => Http::response('ok', 200)]);

        $endpoint = WebhookEndpoint::create([
            'name' => 'Sink',
            'url' => 'https://hooks.example.com/ingest',
            'secret' => 'whsec_secret123',
            'events' => ['tenant.created'],
        ]);
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => 'tenant.created',
            'payload' => ['tenant' => ['id' => 1], 'occurred_at' => now()->toIso8601String()],
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        (new DeliverWebhookJob($delivery->id))->handle();

        $delivery->refresh();
        $this->assertSame(WebhookDelivery::STATUS_DELIVERED, $delivery->status);
        $this->assertNotNull($delivery->delivered_at);

        Http::assertSent(function ($request) use ($endpoint) {
            $body = $request->body();
            $expected = hash_hmac('sha256', $body, $endpoint->secret);

            return $request->url() === $endpoint->url
                && $request->header('X-Webhook-Signature')[0] === $expected
                && $request->header('X-Webhook-Event')[0] === 'tenant.created';
        });
    }

    public function test_failed_delivery_marks_status_and_dead_letter(): void
    {
        Http::fake(['*' => Http::response('server error', 500)]);

        $endpoint = WebhookEndpoint::create([
            'name' => 'Sink',
            'url' => 'https://hooks.example.com/fail',
            'secret' => 'whsec_s',
            'events' => ['tenant.created'],
        ]);
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => 'tenant.created',
            'payload' => ['occurred_at' => now()->toIso8601String()],
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        $job = new DeliverWebhookJob($delivery->id);

        try {
            $job->handle();
        } catch (\RuntimeException) {
            // Expected: failure throws so the queue worker retries.
        }

        $this->assertSame(WebhookDelivery::STATUS_FAILED, $delivery->fresh()->status);
        $this->assertSame(500, $delivery->fresh()->response_status);
        $this->assertSame(1, $delivery->fresh()->attempts);

        $job->failed(new \RuntimeException('exhausted'));
        $this->assertSame(WebhookDelivery::STATUS_DEAD, $delivery->fresh()->status);
    }
}
