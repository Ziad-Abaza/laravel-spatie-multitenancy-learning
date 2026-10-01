<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every tenant-context request. A suspended workspace member is
 * logged out and rejected mid-request — live sessions, remember-me cookies,
 * and stale tokens all re-verify here. Mirror of EnsureLandlordAdminActive
 * for the `web` guard.
 */
class EnsureTenantUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if ($user !== null && ($user->status ?? 'active') !== 'active') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, __('account_suspended'));
        }

        return $next($request);
    }
}
