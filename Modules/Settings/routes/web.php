<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\LandlordSettingsController;
use Modules\Settings\Http\Controllers\TenantSettingsController;

// Landlord Settings
Route::prefix('landlord')->middleware('auth:landlord')->group(function () {
    Route::get('/settings', [LandlordSettingsController::class, 'index'])->name('landlord.settings.index');
    Route::post('/settings', [LandlordSettingsController::class, 'update'])->name('landlord.settings.update');
});

// Tenant Settings
Route::middleware(['auth:web', 'tenant'])->group(function () {
    Route::get('/settings', [TenantSettingsController::class, 'index'])->name('tenant.settings.index');
    Route::post('/settings', [TenantSettingsController::class, 'update'])->name('tenant.settings.update');
});
