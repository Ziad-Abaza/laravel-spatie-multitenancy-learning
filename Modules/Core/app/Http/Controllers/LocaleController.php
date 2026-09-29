<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Contracts\SettingManagerContract;

class LocaleController extends Controller
{
    /**
     * Update active session locale and persist setting if supported.
     */
    public function update(Request $request, SettingManagerContract $settings): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys($settings->supportedLocales()))],
        ]);

        session(['locale' => $validated['locale']]);
        app()->setLocale($validated['locale']);

        return back();
    }
}
