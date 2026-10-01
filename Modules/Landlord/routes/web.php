<?php

use Illuminate\Support\Facades\Route;
use Modules\Access\Support\LandlordPermissions as LP;
use Modules\Landlord\Http\Controllers\AuditLogController;
use Modules\Landlord\Http\Controllers\DashboardController;
use Modules\Landlord\Http\Controllers\LandingController;
use Modules\Landlord\Http\Controllers\LandlordAdminController;
use Modules\Landlord\Http\Controllers\LandlordAuthController;
use Modules\Landlord\Http\Controllers\LandlordRoleController;
use Modules\Landlord\Http\Controllers\ModuleManagementController;
use Modules\Landlord\Http\Controllers\TenantBackupController;
use Modules\Landlord\Http\Controllers\TenantController;
use Modules\Landlord\Http\Controllers\TenantRegistrationController;
use Modules\Landlord\Http\Controllers\WebhookEndpointController;

// Public SaaS Landing & Onboarding — landlord hosts only; on tenant hosts
// these routes 404 (EnsureLandlordContext) instead of exposing the
// platform surface inside a tenant context.
Route::middleware('landlord')->group(function () {
    Route::get('/', [LandingController::class, 'welcome'])->name('landing.welcome');
    Route::get('/pricing', [LandingController::class, 'pricing'])->name('landing.pricing');
    Route::get('/register-tenant', [TenantRegistrationController::class, 'show'])->name('tenant.register.show');
    Route::post('/register-tenant', [TenantRegistrationController::class, 'register'])->name('tenant.register')->middleware('throttle:5,1');
});

// Landlord Authentication & Administration — landlord hosts only
Route::middleware('landlord')->prefix('landlord')->group(function () {
    Route::get('/login', [LandlordAuthController::class, 'showLoginForm'])->name('landlord.login');
    Route::post('/login', [LandlordAuthController::class, 'login'])->name('landlord.login.post')->middleware('throttle:6,1');
    Route::post('/logout', [LandlordAuthController::class, 'logout'])->name('landlord.logout');

    // Landlord Protected Administration — auth first, then live status check
    Route::middleware(['auth:landlord', 'landlord.active'])->group(function () {
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
        Route::post('/tenants/{tenant}/request-erasure', [TenantController::class, 'requestErasure'])->name('landlord.tenants.request-erasure')->middleware('permission:'.LP::TENANTS_LIFECYCLE.',landlord');
        Route::post('/tenants/{tenant}/cancel-erasure', [TenantController::class, 'cancelErasure'])->name('landlord.tenants.cancel-erasure')->middleware('permission:'.LP::TENANTS_LIFECYCLE.',landlord');
        Route::post('/tenants/{tenant}/plan', [TenantController::class, 'changePlan'])->name('landlord.tenants.plan')->middleware('permission:'.LP::TENANTS_UPDATE.',landlord');
        Route::post('/tenants/{tenant}/trial', [TenantController::class, 'extendTrial'])->name('landlord.tenants.trial')->middleware('permission:'.LP::TENANTS_UPDATE.',landlord');
        Route::post('/tenants/{tenant}/cancel-subscription', [TenantController::class, 'cancelSubscription'])->name('landlord.tenants.cancel-subscription')->middleware('permission:'.LP::TENANTS_LIFECYCLE.',landlord');
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('landlord.tenants.destroy')->middleware('permission:'.LP::TENANTS_DELETE.',landlord');

        // Tenant Backups — dumps are landlord-side artifacts; download is signed
        Route::post('/tenants/{tenant}/backups', [TenantBackupController::class, 'store'])->name('landlord.tenants.backups.store')->middleware('permission:'.LP::TENANTS_EXPORT.',landlord');
        Route::get('/tenants/{tenant}/backups/{backup}/download', [TenantBackupController::class, 'download'])->name('landlord.tenants.backups.download')->middleware(['permission:'.LP::TENANTS_VIEW.',landlord', 'signed']);
        Route::delete('/tenants/{tenant}/backups/{backup}', [TenantBackupController::class, 'destroy'])->name('landlord.tenants.backups.destroy')->middleware('permission:'.LP::TENANTS_EXPORT.',landlord');

        // Module Management
        Route::get('/modules', [ModuleManagementController::class, 'index'])->name('landlord.modules.index')->middleware('permission:'.LP::MODULES_VIEW.',landlord');
        Route::post('/modules/{module}/toggle', [ModuleManagementController::class, 'toggle'])->name('landlord.modules.toggle')->middleware('permission:'.LP::MODULES_MANAGE.',landlord');

        // Platform Administrator Management
        Route::get('/admins', [LandlordAdminController::class, 'index'])->name('landlord.admins.index')->middleware('permission:'.LP::ADMINS_VIEW.',landlord');
        Route::post('/admins', [LandlordAdminController::class, 'store'])->name('landlord.admins.store')->middleware('permission:'.LP::ADMINS_CREATE.',landlord');
        Route::put('/admins/{admin}', [LandlordAdminController::class, 'update'])->name('landlord.admins.update')->middleware('permission:'.LP::ADMINS_UPDATE.',landlord');
        Route::put('/admins/{admin}/role', [LandlordAdminController::class, 'assignRole'])->name('landlord.admins.assign-role')->middleware('permission:'.LP::ADMINS_ASSIGN_ROLE.',landlord');
        Route::put('/admins/{admin}/password', [LandlordAdminController::class, 'resetPassword'])->name('landlord.admins.reset-password')->middleware('permission:'.LP::ADMINS_RESET_PASSWORD.',landlord');
        Route::post('/admins/{admin}/suspend', [LandlordAdminController::class, 'suspend'])->name('landlord.admins.suspend')->middleware('permission:'.LP::ADMINS_SUSPEND.',landlord');
        Route::post('/admins/{admin}/reactivate', [LandlordAdminController::class, 'reactivate'])->name('landlord.admins.reactivate')->middleware('permission:'.LP::ADMINS_SUSPEND.',landlord');
        Route::delete('/admins/{admin}', [LandlordAdminController::class, 'destroy'])->name('landlord.admins.destroy')->middleware('permission:'.LP::ADMINS_DELETE.',landlord');

        // Audit Trail — append-only viewer
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('landlord.audit.index')->middleware('permission:'.LP::AUDIT_VIEW.',landlord');

        // Outbound Webhooks — endpoint registry for domain-event integrations
        Route::get('/webhooks', [WebhookEndpointController::class, 'index'])->name('landlord.webhooks.index')->middleware('permission:'.LP::WEBHOOKS_VIEW.',landlord');
        Route::post('/webhooks', [WebhookEndpointController::class, 'store'])->name('landlord.webhooks.store')->middleware('permission:'.LP::WEBHOOKS_MANAGE.',landlord');
        Route::post('/webhooks/{endpoint}/toggle', [WebhookEndpointController::class, 'toggle'])->name('landlord.webhooks.toggle')->middleware('permission:'.LP::WEBHOOKS_MANAGE.',landlord');
        Route::post('/webhooks/{endpoint}/rotate-secret', [WebhookEndpointController::class, 'rotateSecret'])->name('landlord.webhooks.rotate-secret')->middleware('permission:'.LP::WEBHOOKS_MANAGE.',landlord');
        Route::delete('/webhooks/{endpoint}', [WebhookEndpointController::class, 'destroy'])->name('landlord.webhooks.destroy')->middleware('permission:'.LP::WEBHOOKS_MANAGE.',landlord');

        // Platform Roles & Permissions
        Route::get('/roles', [LandlordRoleController::class, 'index'])->name('landlord.roles.index')->middleware('permission:'.LP::ROLES_VIEW.',landlord');
        Route::post('/roles', [LandlordRoleController::class, 'store'])->name('landlord.roles.store')->middleware('permission:'.LP::ROLES_MANAGE.',landlord');
        Route::put('/roles/{role}', [LandlordRoleController::class, 'update'])->name('landlord.roles.update')->middleware('permission:'.LP::ROLES_MANAGE.',landlord');
        Route::delete('/roles/{role}', [LandlordRoleController::class, 'destroy'])->name('landlord.roles.destroy')->middleware('permission:'.LP::ROLES_MANAGE.',landlord');
    });
});
