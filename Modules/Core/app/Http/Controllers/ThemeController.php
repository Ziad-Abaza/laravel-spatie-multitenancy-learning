<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Update active theme palette and mode in session.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', 'in:indigo,emerald,violet,amber,cyan,rose'],
            'palette' => ['nullable', 'string', 'in:indigo,emerald,violet,amber,cyan,rose'],
            'mode' => ['nullable', 'string', 'in:light,dark,system'],
        ]);

        $palette = $validated['palette'] ?? $validated['theme'] ?? null;
        if ($palette) {
            session(['theme' => $palette]);
        }

        if (isset($validated['mode'])) {
            session(['theme_mode' => $validated['mode']]);
        }

        return back();
    }
}
