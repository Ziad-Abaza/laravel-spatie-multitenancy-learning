<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\LandlordSettingsController;
use Modules\Settings\Http\Controllers\TenantSettingsController;

// Landlord Settings — landlord hosts only
Route::prefix('landlord')->middleware(['landlord', 'auth:landlord'])->group(function () {
    Route::get('/settings', [LandlordSettingsController::class, 'index'])->name('landlord.settings.index');
    Route::post('/settings', [LandlordSettingsController::class, 'update'])->name('landlord.settings.update');
});

// Tenant Settings — workspace owners and admins only
Route::middleware(['auth:web', 'tenant', 'role:Owner|Admin'])->group(function () {
    Route::get('/settings', [TenantSettingsController::class, 'index'])->name('tenant.settings.index');
    Route::post('/settings', [TenantSettingsController::class, 'update'])->name('tenant.settings.update');
});
