<?php

use Illuminate\Support\Facades\Route;
use Modules\Access\Support\LandlordPermissions as LP;
use Modules\Access\Support\TenantPermissions as P;
use Modules\Settings\Http\Controllers\LandlordSettingsController;
use Modules\Settings\Http\Controllers\TenantSettingsController;

// Landlord Settings — landlord hosts only
Route::prefix('landlord')->middleware(['landlord', 'auth:landlord', 'landlord.active'])->group(function () {
    Route::get('/settings', [LandlordSettingsController::class, 'index'])->name('landlord.settings.index')->middleware('permission:'.LP::PLATFORM_SETTINGS_VIEW.',landlord');
    Route::post('/settings', [LandlordSettingsController::class, 'update'])->name('landlord.settings.update')->middleware('permission:'.LP::PLATFORM_SETTINGS_MANAGE.',landlord');
});

// Tenant Settings — permission-gated (Owner/Admin hold settings.manage via baseline roles)
Route::middleware(['auth:web', 'tenant', 'verified'])->group(function () {
    Route::get('/settings', [TenantSettingsController::class, 'index'])->name('tenant.settings.index')->middleware('permission:'.P::SETTINGS_VIEW);
    Route::post('/settings', [TenantSettingsController::class, 'update'])->name('tenant.settings.update')->middleware('permission:'.P::SETTINGS_MANAGE);
});
