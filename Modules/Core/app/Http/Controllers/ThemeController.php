<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Services\ThemeResolver;

class ThemeController extends Controller
{
    /**
     * Update active theme palette and mode in session.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', Rule::in(ThemeResolver::PALETTES)],
            'palette' => ['nullable', 'string', Rule::in(ThemeResolver::PALETTES)],
            'mode' => ['nullable', 'string', Rule::in(ThemeResolver::MODES)],
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
