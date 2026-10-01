<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\TenantDashboardController;

Route::middleware(['tenant', 'auth:web', 'verified'])->group(function () {
    Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('tenant.dashboard');
});
