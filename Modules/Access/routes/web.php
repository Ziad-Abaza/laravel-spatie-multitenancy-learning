<?php

use Illuminate\Support\Facades\Route;
use Modules\Access\Http\Controllers\AuditLogController;
use Modules\Access\Http\Controllers\EmailVerificationController;
use Modules\Access\Http\Controllers\PasswordResetController;
use Modules\Access\Http\Controllers\ProfileController;
use Modules\Access\Http\Controllers\RoleController;
use Modules\Access\Http\Controllers\TenantAuthController;
use Modules\Access\Http\Controllers\UserController;
use Modules\Access\Support\TenantPermissions as P;

// Guest Authentication & Registration (Context-aware: Landlord redirects to landlord auth, Tenant renders workspace auth)
Route::middleware('guest:web')->group(function () {
    Route::get('/login', [TenantAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [TenantAuthController::class, 'login'])->name('login.post')->middleware('throttle:6,1');
    Route::get('/register', [TenantAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [TenantAuthController::class, 'register'])->name('register.post')->middleware('throttle:6,1');

    // Password recovery — tokens live in the tenant database (broker `users`
    // is bound to the tenant connection), so these routes are tenant-local.
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:6,1');
});

// Tenant Protected Workspace Access
Route::middleware(['tenant', 'auth:web'])->group(function () {
    Route::post('/logout', [TenantAuthController::class, 'logout'])->name('logout');

    // Email verification — authenticated members who have NOT verified yet
    // must still reach these; the signed link is tenant-domain scoped.
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed:relative')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');

    Route::middleware('verified')->group(function () {
        // Team Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission:'.P::USERS_VIEW);
        Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('permission:'.P::USERS_CREATE);
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('permission:'.P::USERS_UPDATE);
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('permission:'.P::USERS_DELETE);

        // Roles & Permissions
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index')->middleware('permission:'.P::ROLES_VIEW);
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store')->middleware('permission:'.P::ROLES_CREATE);

        // User Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/revoke-sessions', [ProfileController::class, 'revokeOtherSessions'])->name('profile.revoke-sessions');

        // Audit Trail — append-only viewer (Owner/Admin via audit.view)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index')->middleware('permission:'.P::AUDIT_VIEW);
    });
});
