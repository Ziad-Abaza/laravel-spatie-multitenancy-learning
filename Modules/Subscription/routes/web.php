<?php

use Illuminate\Support\Facades\Route;
use Modules\Access\Support\LandlordPermissions as LP;
use Modules\Access\Support\TenantPermissions as P;
use Modules\Subscription\Http\Controllers\PlanController;
use Modules\Subscription\Http\Controllers\SubscriptionController;

// Landlord Subscription & Plan Management — landlord hosts only
Route::prefix('landlord')->middleware(['landlord', 'auth:landlord', 'landlord.active'])->group(function () {
    Route::get('/plans', [PlanController::class, 'index'])->name('landlord.plans.index')->middleware('permission:'.LP::PLANS_VIEW.',landlord');
    Route::post('/plans', [PlanController::class, 'store'])->name('landlord.plans.store')->middleware('permission:'.LP::PLANS_MANAGE.',landlord');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('landlord.plans.update')->middleware('permission:'.LP::PLANS_MANAGE.',landlord');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('landlord.plans.destroy')->middleware('permission:'.LP::PLANS_MANAGE.',landlord');

    Route::get('/subscriptions', [SubscriptionController::class, 'landlordIndex'])->name('landlord.subscriptions.index')->middleware('permission:'.LP::SUBSCRIPTIONS_VIEW.',landlord');
});

// Tenant Subscription & Plan Upgrade Management
Route::middleware(['auth:web', 'tenant', 'verified'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'tenantOverview'])->name('tenant.subscription.overview')->middleware('permission:'.P::SUBSCRIPTION_VIEW);
    Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan'])->name('tenant.subscription.change-plan')->middleware('permission:'.P::SUBSCRIPTION_MANAGE);
});
