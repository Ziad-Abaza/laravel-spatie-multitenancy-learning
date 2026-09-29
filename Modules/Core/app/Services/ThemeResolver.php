<?php

namespace Modules\Core\Services;

use Modules\Core\Contracts\SettingManagerContract;

/**
 * Single source of truth for the active theme.
 *
 * Resolution order (applied identically everywhere):
 *   1. Per-user session override (set via POST /theme or settings save)
 *   2. Persisted configuration (tenant settings merged over landlord settings)
 *   3. Built-in defaults
 *
 * Every consumer — the shared Inertia props, the root Blade view, and error
 * views — resolves through this class so the rendered HTML and the `theme`
 * prop can never diverge.
 */
class ThemeResolver
{
    /** @var list<string> */
    public const PALETTES = ['indigo', 'emerald', 'violet', 'amber', 'cyan', 'rose'];

    /** @var list<string> */
    public const MODES = ['light', 'dark', 'system'];

    public const DEFAULT_PALETTE = 'indigo';

    public const DEFAULT_MODE = 'dark';

    public function __construct(
        protected SettingManagerContract $settings
    ) {}

    /**
     * Resolve the active theme for the current request.
     *
     * @return array{theme: string, palette: string, mode: string}
     */
    public function resolve(): array
    {
        $configured = $this->settings->getTheme();

        $theme = $this->asPalette(session('theme'))
            ?? $this->asPalette($configured['theme'] ?? null)
            ?? self::DEFAULT_PALETTE;

        $mode = $this->asMode(session('theme_mode'))
            ?? $this->asMode($configured['mode'] ?? null)
            ?? self::DEFAULT_MODE;

        return [
            'theme' => $theme,
            'palette' => $theme,
            'mode' => $mode,
        ];
    }

    protected function asPalette(mixed $value): ?string
    {
        return is_string($value) && in_array($value, self::PALETTES, true) ? $value : null;
    }

    protected function asMode(mixed $value): ?string
    {
        return is_string($value) && in_array($value, self::MODES, true) ? $value : null;
    }
}
