<?php

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Settings\Services\SettingService;
use Spatie\Multitenancy\Models\Tenant;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Settings writes are limited to keys declared in the settings registry
     * and owned by the current surface (landlord or tenant).
     */
    public function rules(SettingService $settings): array
    {
        $domain = (string) $this->input('domain', 'system');

        $rules = [
            'domain' => ['required', 'string'],
            'settings' => ['required', 'array'],
            // Workspace identity is a Tenant attribute (tenants.name), not a
            // settings key — the controller applies it to the tenant record.
            'workspace_name' => ['sometimes', 'string', 'max:100'],
        ];

        // Workspace logo is tenant media (landlord `logo` collection on the
        // tenants row), not a settings key — only meaningful in tenant context.
        if (Tenant::checkCurrent()) {
            $rules['logo'] = ['sometimes', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'];
        }

        foreach ((array) $this->input('settings', []) as $key => $value) {
            $definition = $settings->definition($domain, (string) $key);
            $writable = $definition !== null && $settings->canWrite($domain, (string) $key);

            $rules["settings.{$key}"] = $writable
                ? [$definition['type'] === 'array' ? 'array' : 'present', ...explode('|', $definition['rules'])]
                : ['prohibited'];

            if ($writable && ($itemDefinition = $settings->definition($domain, "{$key}.*")) !== null) {
                $rules["settings.{$key}.*"] = explode('|', $itemDefinition['rules']);
            }
        }

        return $rules;
    }
}
