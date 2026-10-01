<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Support\LandlordPermissions as LP;
use Modules\Landlord\Models\TenantBackup;
use Modules\Landlord\Models\UsageRecord;
use Modules\Landlord\Services\LandlordMetricsService;
use Modules\Landlord\Services\TenantLifecycleService;
use Modules\Landlord\Services\TenantProvisioner;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Services\SubscriptionService;

class TenantController extends Controller
{
    public function __construct(
        protected TenantProvisioner $provisioner,
        protected TenantLifecycleService $lifecycleService,
        protected SubscriptionService $subscriptionService,
        protected LandlordMetricsService $metrics
    ) {}

    /**
     * Display a listing of all tenant organizations.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $planId = $request->query('plan_id');

        $tenants = Tenant::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('database', 'like', "%{$search}%");
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($planId, fn ($query) => $query->where('plan_id', $planId))
            ->with(['plan', 'currentSubscription'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug ?? $tenant->domain,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
                'status' => $tenant->getStatus(),
                'plan_name' => $tenant->plan?->getName() ?? 'Free',
                'url' => $tenant->url(),
                'created_at' => $tenant->created_at?->format('Y-m-d H:i') ?? '-',
            ]);

        return Inertia::render('Landlord/Tenants/Index', [
            'tenants' => $tenants,
            'plans' => Plan::orderBy('sort_order')->get()
                ->map(fn (Plan $plan) => ['id' => $plan->id, 'name' => $plan->getName()]),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'plan_id' => $planId,
            ],
        ]);
    }

    /**
     * Show form to manually provision a new tenant.
     */
    public function create(): Response
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->getName(),
                'price' => $plan->price,
            ]);

        return Inertia::render('Landlord/Tenants/Create', [
            'plans' => $plans,
        ]);
    }

    /**
     * Provision new tenant manually from Landlord admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'min:3', 'max:30', 'unique:tenants,slug'],
            'domain' => ['required', 'string', 'unique:tenants,domain'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:150'],
            'admin_password' => ['required', 'string', 'min:8'],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        $this->provisioner->provision([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'domain' => $validated['domain'],
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
            'plan_id' => $validated['plan_id'] ?? null,
        ]);

        return redirect()->route('landlord.tenants.index')->with('success', __('tenant_provisioned'));
    }

    /**
     * Show details of a specific tenant.
     */
    /**
     * Update tenant identity fields (name / slug / domain).
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash', "unique:tenants,slug,{$tenant->id}"],
            'domain' => ['required', 'string', 'max:190', "unique:tenants,domain,{$tenant->id}"],
        ]);

        $tenant->update($validated);

        return back()->with('success', __('tenant_updated'));
    }

    /**
     * Change the tenant's subscription plan and billing interval.
     */
    public function changePlan(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = Plan::where('is_active', true)->findOrFail($validated['plan_id']);
        $this->subscriptionService->changePlan($tenant, $plan);

        return back()->with('success', __('tenant_plan_changed', ['plan' => $plan->getName()]));
    }

    /**
     * Extend or set the tenant's trial end date.
     */
    public function extendTrial(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'trial_ends_at' => ['required', 'date', 'after:today'],
        ]);

        $this->lifecycleService->extendTrial($tenant, Carbon::parse($validated['trial_ends_at']));

        return back()->with('success', __('tenant_trial_extended'));
    }

    /**
     * Cancel the tenant's current subscription.
     */
    public function cancelSubscription(Tenant $tenant): RedirectResponse
    {
        $this->subscriptionService->cancelSubscription($tenant);

        return back()->with('success', __('tenant_subscription_canceled'));
    }

    /**
     * Archive an inactive tenant (data retained, access closed).
     */
    public function archive(Tenant $tenant): RedirectResponse
    {
        $this->lifecycleService->archive($tenant);

        return back()->with('success', __('tenant_archived_notice', ['name' => $tenant->name]));
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load(['plan', 'subscriptions.plan']);

        $users = [];
        try {
            $users = $tenant->execute(fn () => User::query()
                ->with('roles:name')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'created_at' => $user->created_at?->format('Y-m-d') ?? '-',
                ])
                ->all());
        } catch (\Throwable) {
        }

        return Inertia::render('Landlord/Tenants/Show', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn (Plan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->getName(),
                    'price' => $plan->price,
                ]),
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
                'status' => $tenant->getStatus(),
                'trial_ends_at' => $tenant->trial_ends_at?->format('Y-m-d') ?? null,
                'suspended_at' => $tenant->suspended_at?->format('Y-m-d H:i') ?? null,
                'suspension_reason' => $tenant->settings['suspension_reason'] ?? null,
                'erasure_requested_at' => $tenant->settings['erasure_requested_at'] ?? null,
                'retention_until' => $tenant->settings['retention_until'] ?? null,
                'url' => $tenant->url(),
                'user_count' => count($users),
                'users' => $users,
                'plan' => $tenant->plan ? [
                    'id' => $tenant->plan->id,
                    'name' => $tenant->plan->getName(),
                    'slug' => $tenant->plan->slug,
                    'price' => $tenant->plan->price,
                    'limits' => $tenant->plan->limits,
                ] : null,
                'subscriptions' => $tenant->subscriptions->map(fn ($sub) => [
                    'id' => $sub->id,
                    'plan_name' => $sub->plan?->getName() ?? '-',
                    'status' => $sub->getStatus(),
                    'amount' => $sub->amount,
                    'billing_interval' => $sub->billing_interval,
                    'starts_at' => $sub->starts_at?->format('Y-m-d') ?? '-',
                    'ends_at' => $sub->ends_at?->format('Y-m-d') ?? '-',
                ]),
                'created_at' => $tenant->created_at?->format('Y-m-d H:i') ?? '-',
            ],
            'backups' => $tenant->backups()->latest('created_at')->limit(25)->get()
                ->map(fn (TenantBackup $backup) => [
                    'id' => $backup->id,
                    'size' => $backup->size,
                    'driver' => $backup->driver,
                    'status' => $backup->status,
                    'created_at' => $backup->created_at?->format('Y-m-d H:i') ?? '-',
                    'download_url' => URL::temporarySignedRoute(
                        'landlord.tenants.backups.download',
                        now()->addHour(),
                        ['tenant' => $tenant->id, 'backup' => $backup->id]
                    ),
                ]),
            'can_export' => request()->user('landlord')?->can(LP::TENANTS_EXPORT) ?? false,
            'diagnostics' => $this->metrics->tenantDiagnostics($tenant),
            // Metering surface is a `metrics.view` read gate — no key at all
            // for principals without it, so the UI has nothing to expose.
            'usage' => request()->user('landlord')?->can(LP::METRICS_VIEW)
                ? UsageRecord::where('tenant_id', $tenant->id)
                    ->latest('recorded_at')->limit(50)->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'metric' => $r->metric,
                        'value' => (float) $r->value,
                        'recorded_at' => $r->recorded_at?->format('Y-m-d H:i') ?? '-',
                    ])
                : null,
        ]);
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(Tenant $tenant): RedirectResponse
    {
        $this->lifecycleService->suspend($tenant, request('reason', 'Suspended by platform administrator'));

        return back()->with('success', __('tenant_suspended_notice', ['name' => $tenant->name]));
    }

    /**
     * Schedule erasure of an archived tenant (governed grace window).
     */
    public function requestErasure(Tenant $tenant): RedirectResponse
    {
        $this->lifecycleService->requestErasure($tenant);

        return back()->with('success', __('tenant_erasure_scheduled', ['name' => $tenant->name]));
    }

    /**
     * Cancel a pending erasure before the grace window lapses.
     */
    public function cancelErasure(Tenant $tenant): RedirectResponse
    {
        $this->lifecycleService->cancelErasure($tenant);

        return back()->with('success', __('tenant_erasure_canceled', ['name' => $tenant->name]));
    }

    /**
     * Activate a suspended tenant.
     */
    public function activate(Tenant $tenant): RedirectResponse
    {
        $this->lifecycleService->activate($tenant);

        return back()->with('success', __('tenant_activated_notice', ['name' => $tenant->name]));
    }

    /**
     * Delete tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $dropDatabase = request()->boolean('drop_database', false);
        $name = $tenant->name;

        $this->lifecycleService->delete($tenant, $dropDatabase);

        return redirect()->route('landlord.tenants.index')->with('success', __('tenant_deleted_notice', ['name' => $name]));
    }
}
