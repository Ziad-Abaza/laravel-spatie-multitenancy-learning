<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscription\Models\Plan;

class PlanController extends Controller
{
    /**
     * Display listing of all subscription plans for Landlord admin.
     */
    public function index(): Response
    {
        $plans = Plan::withCount('tenants')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name, // Full translatable array
                'name_localized' => $plan->getName(),
                'slug' => $plan->slug,
                'description' => $plan->description,
                'description_localized' => $plan->getDescription(),
                'price' => $plan->price,
                'currency' => $plan->currency,
                'billing_interval' => $plan->billing_interval,
                'trial_days' => $plan->trial_days,
                'is_active' => $plan->is_active,
                'limits' => $plan->limits ?? [],
                'tenants_count' => $plan->tenants_count,
            ]);

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'unique:plans,slug'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_storage_mb' => ['required', 'integer', 'min:100'],
            'features' => ['nullable', 'array'],
        ]);

        Plan::create([
            'name' => [
                'en' => $validated['name_en'],
                'ar' => $validated['name_ar'],
            ],
            'slug' => $validated['slug'],
            'description' => [
                'en' => $validated['description_en'] ?? '',
                'ar' => $validated['description_ar'] ?? '',
            ],
            'price' => $validated['price'],
            'currency' => strtoupper($validated['currency']),
            'billing_interval' => $validated['billing_interval'],
            'trial_days' => $validated['trial_days'],
            'is_active' => true,
            'limits' => [
                'max_users' => (int) $validated['max_users'],
                'max_storage_mb' => (int) $validated['max_storage_mb'],
                'features' => $validated['features'] ?? ['core_dashboard'],
            ],
        ]);

        return back()->with('success', __('plan_created'));
    }

    /**
     * Update an existing plan.
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_storage_mb' => ['required', 'integer', 'min:100'],
            'is_active' => ['boolean'],
        ]);

        $limits = $plan->limits ?? [];
        $limits['max_users'] = (int) $validated['max_users'];
        $limits['max_storage_mb'] = (int) $validated['max_storage_mb'];

        $plan->update([
            'name' => [
                'en' => $validated['name_en'],
                'ar' => $validated['name_ar'],
            ],
            'price' => $validated['price'],
            'trial_days' => $validated['trial_days'],
            'is_active' => $request->boolean('is_active', true),
            'limits' => $limits,
        ]);

        return back()->with('success', __('plan_updated'));
    }
}
