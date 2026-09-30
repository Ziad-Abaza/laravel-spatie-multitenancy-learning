<?php

use Illuminate\Support\Facades\Route;
use Modules\Access\Support\LandlordPermissions as LP;
use Modules\Landlord\Http\Controllers\DashboardController;
use Modules\Landlord\Http\Controllers\LandingController;
use Modules\Landlord\Http\Controllers\LandlordAuthController;
use Modules\Landlord\Http\Controllers\ModuleManagementController;
use Modules\Landlord\Http\Controllers\TenantController;
use Modules\Landlord\Http\Controllers\TenantRegistrationController;

// Public SaaS Landing & Onboarding
Route::get('/', [LandingController::class, 'welcome'])->name('landing.welcome');
Route::get('/pricing', [LandingController::class, 'pricing'])->name('landing.pricing');
Route::get('/register-tenant', [TenantRegistrationController::class, 'show'])->name('tenant.register.show');
Route::post('/register-tenant', [TenantRegistrationController::class, 'register'])->name('tenant.register');

// Landlord Authentication & Administration — landlord hosts only
Route::middleware('landlord')->prefix('landlord')->group(function () {
    Route::get('/login', [LandlordAuthController::class, 'showLoginForm'])->name('landlord.login');
    Route::post('/login', [LandlordAuthController::class, 'login'])->name('landlord.login.post');
    Route::post('/logout', [LandlordAuthController::class, 'logout'])->name('landlord.logout');

    // Landlord Protected Administration
    Route::middleware('auth:landlord')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('landlord.dashboard');

        // Tenant Management
        Route::get('/tenants', [TenantController::class, 'index'])->name('landlord.tenants.index')->middleware('permission:'.LP::TENANTS_VIEW.',landlord');
        Route::get('/tenants/create', [TenantController::class, 'create'])->name('landlord.tenants.create')->middleware('permission:'.LP::TENANTS_CREATE.',landlord');
        Route::post('/tenants', [TenantController::class, 'store'])->name('landlord.tenants.store')->middleware('permission:'.LP::TENANTS_CREATE.',landlord');
        Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('landlord.tenants.show')->middleware('permission:'.LP::TENANTS_VIEW.',landlord');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('landlord.tenants.update')->middleware('permission:'.LP::TENANTS_UPDATE.',landlord');
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('landlord.tenants.suspend')->middleware('permission:'.LP::TENANTS_LIFECYCLE.',landlord');
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('landlord.tenants.activate')->middleware('permission:'.LP::TENANTS_LIFECYCLE.',landlord');
        Route::post('/tenants/{tenant}/archive', [TenantController::class, 'archive'])->name('landlord.tenants.archive')->middleware('permission:'.LP::TENANTS_LIFECYCLE.',landlord');
        Route::post('/tenants/{tenant}/plan', [TenantController::class, 'changePlan'])->name('landlord.tenants.plan')->middleware('permission:'.LP::TENANTS_UPDATE.',landlord');
        Route::post('/tenants/{tenant}/trial', [TenantController::class, 'extendTrial'])->name('landlord.tenants.trial')->middleware('permission:'.LP::TENANTS_UPDATE.',landlord');
        Route::post('/tenants/{tenant}/cancel-subscription', [TenantController::class, 'cancelSubscription'])->name('landlord.tenants.cancel-subscription')->middleware('permission:'.LP::TENANTS_LIFECYCLE.',landlord');
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('landlord.tenants.destroy')->middleware('permission:'.LP::TENANTS_DELETE.',landlord');

        // Module Management
        Route::get('/modules', [ModuleManagementController::class, 'index'])->name('landlord.modules.index')->middleware('permission:'.LP::MODULES_VIEW.',landlord');
        Route::post('/modules/{module}/toggle', [ModuleManagementController::class, 'toggle'])->name('landlord.modules.toggle')->middleware('permission:'.LP::MODULES_MANAGE.',landlord');
    });
});
