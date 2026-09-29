<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Modules\Core\Enums\ThemeMode;
use Modules\Core\Enums\ThemePalette;

class ThemeController extends Controller
{
    /**
     * Update active theme palette and mode in session.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'palette' => ['nullable', 'string', new Enum(ThemePalette::class)],
            'mode' => ['nullable', 'string', new Enum(ThemeMode::class)],
        ]);

        if (isset($validated['palette'])) {
            session(['theme' => $validated['palette']]);
        }

        if (isset($validated['mode'])) {
            session(['theme_mode' => $validated['mode']]);
        }

        return back();
    }
}
