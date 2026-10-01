<?php

namespace Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Access\Services\AuditWriter;

class ProfileController extends Controller
{
    /**
     * Display user profile editor.
     */
    public function edit(): Response
    {
        $user = auth()->user();

        return Inertia::render('Access/Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'job_title' => $user->job_title ?? '',
                'phone' => $user->phone ?? '',
                'avatar_url' => $user->getAvatarUrl(),
            ],
        ]);
    }

    /**
     * Update user profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'job_title' => $validated['job_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        if (! empty($validated['password'])) {
            // Invalidates every other session for this user — the stored
            // password-hash fingerprint in those sessions no longer matches.
            Auth::logoutOtherDevices($validated['current_password']);
        }

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatars');
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatars');
        }

        return back()->with('success', __('profile_updated'));
    }

    /**
     * Revoke every other active session for the current user.
     * Re-authentication via current password is mandatory.
     */
    public function revokeOtherSessions(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($validated['current_password']);

        app(AuditWriter::class)->record(
            $request->user(), 'web', 'auth.sessions_revoked', 'user', $request->user(),
        );

        return back()->with('success', __('other_sessions_revoked'));
    }
}
