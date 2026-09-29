<?php

namespace Modules\Subscription\Services;

use Carbon\Carbon;
use Modules\Core\Enums\SubscriptionStatus;
use Modules\Core\Events\PlanChanged;
use Modules\Core\Events\SubscriptionCreated;
use Modules\Core\Events\SubscriptionUpdated;
use Modules\Landlord\Models\Tenant;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\Subscription;

class SubscriptionService
{
    /**
     * Subscribe a tenant to a plan.
     */
    public function subscribeTenant(
        Tenant $tenant,
        Plan $plan,
        string $billingInterval = 'monthly',
        bool $startTrial = true
    ): Subscription {
        $trialDays = $startTrial ? $plan->trial_days : 0;
        $trialEndsAt = $trialDays > 0 ? Carbon::now()->addDays($trialDays) : null;
        $status = $trialDays > 0 ? SubscriptionStatus::Trialing : SubscriptionStatus::Active;

        $amount = $billingInterval === 'yearly' ? $plan->price * 10 : $plan->price;

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'billing_interval' => $billingInterval,
            'amount' => $amount,
            'currency' => $plan->currency,
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => Carbon::now(),
            'ends_at' => $billingInterval === 'yearly' ? Carbon::now()->addYear() : Carbon::now()->addMonth(),
        ]);

        $tenant->update([
            'plan_id' => $plan->id,
            'trial_ends_at' => $trialEndsAt,
        ]);

        event(new SubscriptionCreated($subscription));

        return $subscription;
    }

    /**
     * Change a tenant's subscription plan.
     */
    public function changePlan(Tenant $tenant, Plan $newPlan): Subscription
    {
        $oldPlan = $tenant->plan;
        $currentSubscription = $tenant->currentSubscription;

        if ($currentSubscription) {
            $oldStatus = $currentSubscription->getStatus();
            $currentSubscription->update([
                'plan_id' => $newPlan->id,
                'amount' => $newPlan->price,
            ]);

            event(new SubscriptionUpdated($currentSubscription, $oldStatus, $currentSubscription->getStatus()));
        } else {
            $this->subscribeTenant($tenant, $newPlan, 'monthly', false);
        }

        $tenant->update(['plan_id' => $newPlan->id]);

        if ($oldPlan) {
            event(new PlanChanged($tenant, $oldPlan, $newPlan));
        }

        return $tenant->currentSubscription;
    }

    /**
     * Cancel an active subscription.
     */
    public function cancelSubscription(Tenant $tenant): ?Subscription
    {
        $subscription = $tenant->currentSubscription;
        if (! $subscription) {
            return null;
        }

        $oldStatus = $subscription->getStatus();
        $subscription->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => Carbon::now(),
        ]);

        event(new SubscriptionUpdated($subscription, $oldStatus, SubscriptionStatus::Canceled->value));

        return $subscription;
    }
}
