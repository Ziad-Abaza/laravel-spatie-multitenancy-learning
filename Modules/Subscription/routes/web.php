<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\PlanController;
use Modules\Subscription\Http\Controllers\SubscriptionController;

// Landlord Subscription & Plan Management
Route::prefix('landlord')->middleware('auth:landlord')->group(function () {
    Route::get('/plans', [PlanController::class, 'index'])->name('landlord.plans.index');
    Route::post('/plans', [PlanController::class, 'store'])->name('landlord.plans.store');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('landlord.plans.update');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('landlord.plans.destroy');

    Route::get('/subscriptions', [SubscriptionController::class, 'landlordIndex'])->name('landlord.subscriptions.index');
});

// Tenant Subscription & Plan Upgrade Management
Route::middleware(['auth:web', 'tenant'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'tenantOverview'])->name('tenant.subscription.overview');
    Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan'])->name('tenant.subscription.change-plan');
});
