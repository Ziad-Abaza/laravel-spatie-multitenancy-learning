<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Landlord\Models\Tenant;
use Modules\Landlord\Services\TenantLifecycleService;
use Modules\Landlord\Services\TenantProvisioner;
use Modules\Subscription\Models\Plan;

class TenantController extends Controller
{
    public function __construct(
        protected TenantProvisioner $provisioner,
        protected TenantLifecycleService $lifecycleService
    ) {}

    /**
     * Display a listing of all tenant organizations.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $tenants = Tenant::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('database', 'like', "%{$search}%");
            })
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
            'filters' => [
                'search' => $search,
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

        return redirect()->route('landlord.tenants.index')->with('success', 'Tenant provisioned successfully.');
    }

    /**
     * Show details of a specific tenant.
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->load(['plan', 'subscriptions.plan']);

        $userCount = 0;
        try {
            $userCount = $tenant->execute(fn () => User::count());
        } catch (\Throwable) {
        }

        return Inertia::render('Landlord/Tenants/Show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
                'status' => $tenant->getStatus(),
                'trial_ends_at' => $tenant->trial_ends_at?->format('Y-m-d') ?? null,
                'suspended_at' => $tenant->suspended_at?->format('Y-m-d H:i') ?? null,
                'url' => $tenant->url(),
                'user_count' => $userCount,
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
        ]);
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(Tenant $tenant): RedirectResponse
    {
        $this->lifecycleService->suspend($tenant, request('reason', 'Suspended by platform administrator'));

        return back()->with('success', "Tenant {$tenant->name} has been suspended.");
    }

    /**
     * Activate a suspended tenant.
     */
    public function activate(Tenant $tenant): RedirectResponse
    {
        $this->lifecycleService->activate($tenant);

        return back()->with('success', "Tenant {$tenant->name} has been activated.");
    }

    /**
     * Delete tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $dropDatabase = request()->boolean('drop_database', false);
        $name = $tenant->name;

        $this->lifecycleService->delete($tenant, $dropDatabase);

        return redirect()->route('landlord.tenants.index')->with('success', "Tenant {$name} was deleted successfully.");
    }
}
