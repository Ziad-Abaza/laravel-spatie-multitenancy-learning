<?php

use Modules\Core\Enums\Locale;
use Modules\Core\Enums\ThemeMode;
use Modules\Core\Enums\ThemePalette;

/*
 | Canonical registry of writable settings. Each definition declares the
 | owning surface (landlord, tenant, or shared) plus the value type that
 | UpdateSettingsRequest and SettingService::set() enforce. Keys not listed
 | here cannot be written — there is no implicit settings surface.
 */
return [
    'definitions' => [
        'branding' => [
            'app_name' => ['owner' => 'landlord', 'type' => 'string', 'rules' => 'string|max:100'],
            'tagline' => ['owner' => 'shared', 'type' => 'string', 'rules' => 'nullable|string|max:150'],
            'support_email' => ['owner' => 'landlord', 'type' => 'string', 'rules' => 'email|max:150'],
        ],
        'theme' => [
            'palette' => ['owner' => 'shared', 'type' => 'string', 'rules' => 'in:'.implode(',', ThemePalette::values())],
            'mode' => ['owner' => 'shared', 'type' => 'string', 'rules' => 'in:'.implode(',', ThemeMode::values())],
        ],
        'localization' => [
            'default_locale' => ['owner' => 'landlord', 'type' => 'string', 'rules' => 'in:'.implode(',', array_column(Locale::cases(), 'value'))],
            'supported_locales' => ['owner' => 'landlord', 'type' => 'array', 'rules' => 'array'],
            'supported_locales.*' => ['owner' => 'landlord', 'type' => 'string', 'rules' => 'in:'.implode(',', array_column(Locale::cases(), 'value'))],
        ],
        'system' => [
            'allow_registration' => ['owner' => 'landlord', 'type' => 'boolean', 'rules' => 'boolean'],
            'tenant_db_prefix' => ['owner' => 'landlord', 'type' => 'string', 'rules' => 'regex:/^[a-z0-9_]{0,20}$/'],
            'default_trial_days' => ['owner' => 'landlord', 'type' => 'integer', 'rules' => 'integer|min:0|max:365'],
        ],
        'billing' => [
            'default_currency' => ['owner' => 'landlord', 'type' => 'string', 'rules' => 'string|size:3|alpha'],
            'default_plan_id' => ['owner' => 'landlord', 'type' => 'integer', 'rules' => 'nullable|integer|exists:plans,id'],
        ],
    ],
];
