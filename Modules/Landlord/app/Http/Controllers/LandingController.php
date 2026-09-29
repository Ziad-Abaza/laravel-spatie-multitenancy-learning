<?php

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscription\Models\Plan;

class LandingController extends Controller
{
    /**
     * Display the public SaaS marketing homepage.
     */
    public function welcome(): Response|RedirectResponse
    {
        if (Tenant::checkCurrent()) {
            return redirect()->route('tenant.dashboard');
        }

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->getName(),
                'slug' => $plan->slug,
                'description' => $plan->getTranslation('description', app()->getLocale()),
                'price' => $plan->price,
                'currency' => $plan->currency,
                'billing_interval' => $plan->billing_interval,
                'trial_days' => $plan->trial_days,
                'limits' => $plan->limits,
                'is_free' => $plan->isFree(),
            ]);

        return Inertia::render('Landlord/Landing/Welcome', [
            'plans' => $plans,
        ]);
    }

    /**
     * Display public pricing and feature matrix.
     */
    public function pricing(): Response
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->getName(),
                'slug' => $plan->slug,
                'description' => $plan->getTranslation('description', app()->getLocale()),
                'price' => $plan->price,
                'currency' => $plan->currency,
                'billing_interval' => $plan->billing_interval,
                'trial_days' => $plan->trial_days,
                'limits' => $plan->limits,
                'is_free' => $plan->isFree(),
            ]);

        return Inertia::render('Landlord/Landing/Pricing', [
            'plans' => $plans,
        ]);
    }
}
