<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class LandlordSettingsController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Show central platform settings editor organized by domains.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/LandlordSettings', [
            'branding' => $this->settingService->allByDomain('branding'),
            'theme' => $this->settingService->allByDomain('theme'),
            'localization' => $this->settingService->allByDomain('localization'),
            'system' => $this->settingService->allByDomain('system'),
        ]);
    }

    /**
     * Update settings by domain.
     */
    public function update(Request $request): RedirectResponse
    {
        $domain = $request->input('domain', 'system');
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            $this->settingService->set($key, $value, $domain, true);
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
