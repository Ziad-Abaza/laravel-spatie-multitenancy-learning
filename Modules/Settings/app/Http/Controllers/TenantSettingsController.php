<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Services\SettingService;

class TenantSettingsController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Show tenant workspace settings.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/TenantSettings', [
            'branding' => $this->settingService->getBranding(),
            'themeSettings' => $this->settingService->getTheme(),
        ]);
    }

    /**
     * Update tenant workspace settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $domain = $request->input('domain', 'branding');
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            $this->settingService->set($key, $value, $domain, true);
        }

        if ($domain === 'theme') {
            if (isset($settings['palette'])) {
                session(['theme' => $settings['palette']]);
            }
            if (isset($settings['mode'])) {
                session(['theme_mode' => $settings['mode']]);
            }
        }

        return back()->with('success', __('workspace_settings_updated'));
    }
}
