<?php

use Illuminate\Support\Facades\Route;
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
});

// Tenant Protected Workspace Access
Route::middleware(['tenant', 'auth:web'])->group(function () {
    Route::post('/logout', [TenantAuthController::class, 'logout'])->name('logout');

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
});
