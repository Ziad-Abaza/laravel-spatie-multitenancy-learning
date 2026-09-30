import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ThemePalette = 'indigo' | 'emerald' | 'violet' | 'amber' | 'cyan' | 'rose';
export type ThemeMode = 'dark' | 'light' | 'system';

/**
 * Theme registry — each theme is exactly 3 harmonious colors:
 * primary / secondary / accent (swatch hexes mirror resources/css/app.css).
 */
export const THEME_PRESETS: readonly { id: ThemePalette; name: string; colors: [string, string, string] }[] = [
    { id: 'indigo', name: 'Indigo', colors: ['#6366f1', '#0ea5e9', '#f59e0b'] },
    { id: 'emerald', name: 'Emerald', colors: ['#10b981', '#14b8a6', '#f97316'] },
    { id: 'violet', name: 'Violet', colors: ['#8b5cf6', '#d946ef', '#06b6d4'] },
    { id: 'amber', name: 'Amber', colors: ['#f59e0b', '#f97316', '#14b8a6'] },
    { id: 'cyan', name: 'Cyan', colors: ['#06b6d4', '#0ea5e9', '#f43f5e'] },
    { id: 'rose', name: 'Rose', colors: ['#f43f5e', '#d946ef', '#f59e0b'] },
];

const THEME_PALETTES: readonly ThemePalette[] = THEME_PRESETS.map((p) => p.id);
const THEME_MODES: readonly ThemeMode[] = ['dark', 'light', 'system'];

function asPalette(value: string | null | undefined): ThemePalette | undefined {
    return THEME_PALETTES.includes(value as ThemePalette) ? (value as ThemePalette) : undefined;
}

function asMode(value: string | null | undefined): ThemeMode | undefined {
    return THEME_MODES.includes(value as ThemeMode) ? (value as ThemeMode) : undefined;
}

function systemPrefersDark(): boolean {
    return typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;
}

/**
 * The server-resolved `theme` prop (shared on every Inertia response) is the
 * single source of truth. The store only mirrors it onto <html> as
 * `data-theme` / `data-mode` / `.dark`, which drive the CSS design tokens.
 */
export const useThemeStore = defineStore('theme', () => {
    const currentTheme = ref<ThemePalette>('indigo');
    const currentMode = ref<ThemeMode>('dark');
    const isDark = ref(true);

    function applyTheme(theme: ThemePalette) {
        currentTheme.value = theme;
        if (typeof document !== 'undefined') {
            document.documentElement.setAttribute('data-theme', theme);
        }
    }

    function applyMode(mode: ThemeMode) {
        currentMode.value = mode;
        isDark.value = mode === 'dark' || (mode === 'system' && systemPrefersDark());
        if (typeof document !== 'undefined') {
            document.documentElement.setAttribute('data-mode', mode);
            document.documentElement.classList.toggle('dark', isDark.value);
        }
    }

    let mediaListenerAttached = false;

    function initTheme(initialTheme?: string, initialMode?: string) {
        applyTheme(asPalette(initialTheme) ?? 'indigo');
        applyMode(asMode(initialMode) ?? 'dark');

        if (typeof window !== 'undefined' && ! mediaListenerAttached) {
            mediaListenerAttached = true;
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (currentMode.value === 'system') {
                    applyMode('system');
                }
            });
        }
    }

    return {
        currentTheme,
        currentMode,
        isDark,
        applyTheme,
        applyMode,
        initTheme,
    };
});
