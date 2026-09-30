<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs immediately after auth:landlord on every protected platform request.
 * A suspended administrator is logged out and rejected mid-request — live
 * sessions, remember-me cookies, and stale tokens all re-verify here.
 */
class EnsureLandlordAdminActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('landlord');

        if ($user !== null && ($user->status ?? 'active') !== 'active') {
            Auth::guard('landlord')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, __('account_suspended'));
        }

        return $next($request);
    }
}
