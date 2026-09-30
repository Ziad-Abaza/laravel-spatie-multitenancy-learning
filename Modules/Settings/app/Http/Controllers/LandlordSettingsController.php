<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Settings\Http\Requests\UpdateSettingsRequest;
use Modules\Subscription\Models\Plan;

class LandlordSettingsController extends Controller
{
    public function __construct(
        protected SettingManagerContract $settings
    ) {}

    /**
     * Show central platform settings editor organized by domains.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/LandlordSettings', [
            'branding' => $this->settings->allByDomain('branding'),
            'themeSettings' => $this->settings->allByDomain('theme'),
            'localization' => $this->settings->allByDomain('localization'),
            'system' => $this->settings->allByDomain('system'),
            'billing' => $this->settings->allByDomain('billing'),
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn (Plan $plan) => ['id' => $plan->id, 'name' => $plan->getName(), 'slug' => $plan->slug]),
        ]);
    }

    /**
     * Update settings by domain.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $domain = $request->input('domain', 'system');

        foreach ($request->input('settings', []) as $key => $value) {
            $this->settings->set($key, $value, $domain, true);
        }

        return back()->with('success', __('settings_updated'));
    }
}
