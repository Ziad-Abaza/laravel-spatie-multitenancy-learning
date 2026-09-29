import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ThemePalette = 'indigo' | 'emerald' | 'violet' | 'amber' | 'cyan' | 'rose';
export type ThemeMode = 'dark' | 'light' | 'system';

export const useThemeStore = defineStore('theme', () => {
    const currentTheme = ref<ThemePalette>('indigo');
    const currentMode = ref<ThemeMode>('dark');

    function applyTheme(theme: ThemePalette) {
        currentTheme.value = theme;
        if (typeof document !== 'undefined') {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('saas_theme', theme);
        }
    }

    function applyMode(mode: ThemeMode) {
        currentMode.value = mode;
        if (typeof document !== 'undefined') {
            if (mode === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (mode === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', prefersDark);
            }
            localStorage.setItem('saas_mode', mode);
        }
    }

    function initTheme(initialTheme?: string, initialMode?: string) {
        if (typeof window === 'undefined') return;

        const storedTheme = (localStorage.getItem('saas_theme') as ThemePalette) || initialTheme || 'indigo';
        const storedMode = (localStorage.getItem('saas_mode') as ThemeMode) || initialMode || 'dark';

        applyTheme(storedTheme);
        applyMode(storedMode);
    }

    return {
        currentTheme,
        currentMode,
        applyTheme,
        applyMode,
        initTheme,
    };
});
