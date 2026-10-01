<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Support\LandlordPermissions;
use Modules\Landlord\Models\WebhookEndpoint;

class WebhookEndpointController extends Controller
{
    public function index(Request $request): Response
    {
        $endpoints = WebhookEndpoint::query()
            ->withCount([
                'deliveries as deliveries_count',
                'deliveries as dead_deliveries_count' => fn ($q) => $q->where('status', 'dead'),
            ])
            ->latest()
            ->get()
            ->map(fn (WebhookEndpoint $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'url' => $e->url,
                'events' => $e->events ?? [],
                'active' => $e->active,
                'deliveries_count' => $e->deliveries_count,
                'dead_deliveries_count' => $e->dead_deliveries_count,
                'created_at' => $e->created_at?->format('Y-m-d H:i') ?? '-',
            ]);

        return Inertia::render('Landlord/Webhooks/Index', [
            'endpoints' => $endpoints,
            'supported_events' => WebhookEndpoint::supportedEvents(),
            'can_manage' => $request->user('landlord')?->can(LandlordPermissions::WEBHOOKS_MANAGE) ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            // HTTPS-only in the URL rule — HTTP targets would leak signed
            // payloads (and the HMAC secret is useless over plaintext).
            'url' => ['required', 'url', 'starts_with:https://', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => [Rule::in(WebhookEndpoint::supportedEvents())],
        ]);

        WebhookEndpoint::create($validated + [
            'secret' => 'whsec_'.Str::random(40),
        ]);

        return back()->with('success', __('webhook_created'));
    }

    public function toggle(WebhookEndpoint $endpoint): RedirectResponse
    {
        $endpoint->update(['active' => ! $endpoint->active]);

        return back()->with('success', __('webhook_updated'));
    }

    /**
     * Rotate the signing secret — old signatures stop validating immediately.
     * Deliveries already queued keep the endpoint reference and sign with
     * the new secret on retry.
     */
    public function rotateSecret(WebhookEndpoint $endpoint): RedirectResponse
    {
        $endpoint->update(['secret' => 'whsec_'.Str::random(40)]);

        return back()->with('success', __('webhook_secret_rotated'));
    }

    public function destroy(WebhookEndpoint $endpoint): RedirectResponse
    {
        $endpoint->delete();

        return back()->with('success', __('webhook_deleted'));
    }
}
