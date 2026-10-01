<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Contracts\SettingManagerContract;
use Modules\Settings\Http\Requests\UpdateSettingsRequest;
use Spatie\Multitenancy\Models\Tenant;

class TenantSettingsController extends Controller
{
    public function __construct(
        protected SettingManagerContract $settings
    ) {}

    /**
     * Show tenant workspace settings.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/TenantSettings', [
            'branding' => $this->settings->getBranding(),
            'themeSettings' => $this->settings->getTheme(),
        ]);
    }

    /**
     * Update tenant workspace settings.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $domain = $request->input('domain', 'branding');

        foreach ($request->input('settings', []) as $key => $value) {
            $this->settings->set($key, $value, $domain, true);
        }

        // Workspace identity lives on the tenant record (single source of
        // truth) — it is not a tenant_settings row.
        if ($request->filled('workspace_name')) {
            Tenant::current()->update(['name' => $request->input('workspace_name')]);
        }

        // Logo is the tenant record's `logo` media collection — stored on the
        // landlord connection alongside the tenant row, served under the
        // tenant-scoped path prefix (TenantAwarePathGenerator).
        if ($request->hasFile('logo')) {
            $tenant = Tenant::current();
            $tenant->clearMediaCollection('logo');
            $tenant->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return back()->with('success', __('workspace_settings_updated'));
    }
}
