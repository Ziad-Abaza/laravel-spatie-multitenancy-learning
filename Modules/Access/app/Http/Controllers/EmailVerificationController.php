<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tenant email verification. Lives on the tenant host — signed links are
 * generated against the current tenant domain, and `email_verified_at` is a
 * column on the tenant-side users table.
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): Response|RedirectResponse
    {
        if ($request->user('web')->hasVerifiedEmail()) {
            return redirect('/dashboard');
        }

        return Inertia::render('Access/Auth/VerifyEmail');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect('/dashboard')->with('success', __('email_verified'));
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user('web')->hasVerifiedEmail()) {
            return redirect('/dashboard');
        }

        $request->user('web')->sendEmailVerificationNotification();

        return back()->with('status', __('verification_link_sent'));
    }
}
