<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Services\TenantUserService;
use Modules\Access\Support\TenantPermissions;
use Modules\Core\Contracts\SettingManagerContract;

class TenantAuthController extends Controller
{
    public function __construct(
        protected TenantUserService $userService,
        protected SettingManagerContract $settings
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

        // Reject suspended workspace members before any session exists.
        $account = User::where('email', $credentials['email'])->first();
        if ($account !== null && ($account->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'email' => [__('account_suspended')],
            ]);
        }

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => [__('auth_failed')],
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

        abort_unless((bool) $this->settings->get('allow_registration', true, 'system'), 403, 'Registration is currently disabled.');

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

        abort_unless((bool) $this->settings->get('allow_registration', true, 'system'), 403, 'Registration is currently disabled.');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Honeypot — humans never fill a hidden field; a non-empty value
            // marks the submission as a bot and fails validation.
            'website' => ['prohibited'],
        ]);

        $user = $this->userService->createUser([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => TenantPermissions::ROLE_MEMBER,
        ]);

        event(new Registered($user));

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect('/verify-email')->with('success', __('welcome_to_workspace'));
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
