<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Settings\Services\SettingService;

class LocaleController extends Controller
{
    /**
     * Update active session locale and persist setting if supported.
     */
    public function update(Request $request, SettingService $settingService): RedirectResponse
    {
        $supportedLocales = ['en', 'ar'];
        $enableArabic = (bool) $settingService->get('enable_arabic', true, 'localization');

        if (! $enableArabic) {
            $supportedLocales = ['en'];
        }

        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', $supportedLocales)],
        ]);

        $locale = $validated['locale'];

        session(['locale' => $locale]);
        app()->setLocale($locale);

        return back();
    }
}
