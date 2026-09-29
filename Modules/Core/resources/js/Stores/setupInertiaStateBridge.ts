import { router } from '@inertiajs/vue3';
import { useThemeStore, ThemePalette, ThemeMode } from './useThemeStore';

export function setupInertiaStateBridge() {
    const themeStore = useThemeStore();

    // Check if initial page data exists
    if (typeof window !== 'undefined') {
        const initialPage = (window as any).__INITIAL_PAGE__;
        const initialTheme = initialPage?.props?.theme;
        if (initialTheme) {
            themeStore.initTheme(initialTheme.theme, initialTheme.mode);
        } else {
            themeStore.initTheme();
        }

        router.on('navigate', (event) => {
            const props = event.detail.page.props as Record<string, any>;
            if (props?.theme?.theme && props.theme.theme !== themeStore.currentTheme) {
                themeStore.applyTheme(props.theme.theme as ThemePalette);
            }
            if (props?.theme?.mode && props.theme.mode !== themeStore.currentMode) {
                themeStore.applyMode(props.theme.mode as ThemeMode);
            }
        });
    }
}
