<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Landlord\Services\TenantProvisioner;
use Modules\Settings\Services\SettingService;
use Modules\Subscription\Models\Plan;

class TenantRegistrationController extends Controller
{
    public function __construct(
        protected TenantProvisioner $provisioner,
        protected SettingService $settingService
    ) {}

    /**
     * Show self-service workspace registration form.
     */
    public function show(): Response|RedirectResponse
    {
        $allowRegistration = (bool) $this->settingService->get('allow_registration', true, 'system');
        if (! $allowRegistration) {
            abort(403, 'Public workspace registration is currently disabled.');
        }

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->getName(),
                'slug' => $plan->slug,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'trial_days' => $plan->trial_days,
                'limits' => $plan->limits,
                'is_free' => $plan->isFree(),
            ]);

        return Inertia::render('Landlord/Landing/RegisterTenant', [
            'plans' => $plans,
        ]);
    }

    /**
     * Process tenant registration, provision database, migrate, and seed owner.
     */
    public function register(Request $request): RedirectResponse
    {
        $allowRegistration = (bool) $this->settingService->get('allow_registration', true, 'system');
        if (! $allowRegistration) {
            abort(403, 'Public workspace registration is currently disabled.');
        }

        $reservedSubdomains = ['localhost', 'admin', 'landlord', 'api', 'www', 'app', 'system', 'root'];

        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:100'],
            'subdomain' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::notIn($reservedSubdomains),
                Rule::unique(Tenant::class, 'slug'),
                // Domains are stored as {slug}.{suffix}; the raw subdomain input
                // never matches the domain column, so uniqueness must be checked
                // against the composed domain.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $domain = $this->provisioner->tenantDomain(strtolower((string) $value));
                    if (Tenant::where('domain', $domain)->exists()) {
                        $fail(__('validation.unique', ['attribute' => 'domain']));
                    }
                },
            ],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:150'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        $tenant = $this->provisioner->provision([
            'name' => $validated['organization_name'],
            'slug' => strtolower($validated['subdomain']),
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
            'plan_id' => $validated['plan_id'] ?? null,
        ]);

        $tenantUrl = $tenant->url('/login');

        return redirect()->away($tenantUrl)->with('success', __('workspace_provisioned'));
    }
}
