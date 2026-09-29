<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Services\TenantUserService;

class TenantAuthController extends Controller
{
    public function __construct(
        protected TenantUserService $userService
    ) {}

    /**
     * Show tenant login form.
     */
    public function showLoginForm(): Response|RedirectResponse
    {
        if (! Tenant::checkCurrent()) {
            return redirect()->route('landlord.login');
        }

        return Inertia::render('Access/Auth/Login');
    }

    /**
     * Authenticate tenant user.
     */
    public function login(Request $request): RedirectResponse
    {
        if (! Tenant::checkCurrent()) {
            return redirect()->route('landlord.login');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);
    }

    /**
     * Show tenant registration form.
     */
    public function showRegisterForm(): Response|RedirectResponse
    {
        if (! Tenant::checkCurrent()) {
            return redirect()->route('tenant.register.show');
        }

        return Inertia::render('Access/Auth/Register');
    }

    /**
     * Register a new user in the tenant workspace.
     */
    public function register(Request $request): RedirectResponse
    {
        if (! Tenant::checkCurrent()) {
            return redirect()->route('tenant.register.show');
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->userService->createUser([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'Member',
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect('/dashboard')->with('success', 'Welcome to your workspace!');
    }

    /**
     * Logout of tenant.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
