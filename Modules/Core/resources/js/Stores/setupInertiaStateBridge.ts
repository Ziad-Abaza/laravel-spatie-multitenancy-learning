import { router } from '@inertiajs/vue3';
import { useThemeStore, ThemePalette, ThemeMode } from './useThemeStore';

export function setupInertiaStateBridge(initialProps?: Record<string, any>) {
    const themeStore = useThemeStore();

    if (typeof window !== 'undefined') {
        const themeProps = initialProps?.theme;
        if (themeProps) {
            themeStore.initTheme(themeProps.theme, themeProps.mode);
        } else {
            themeStore.initTheme();
        }

        const localeProps = initialProps?.locale;
        if (localeProps?.current) {
            document.documentElement.setAttribute('lang', localeProps.current);
            document.documentElement.setAttribute('dir', localeProps.is_rtl ? 'rtl' : 'ltr');
        }

        router.on('navigate', (event) => {
            const props = event.detail.page.props as Record<string, any>;
            if (props?.theme?.theme && props.theme.theme !== themeStore.currentTheme) {
                themeStore.applyTheme(props.theme.theme as ThemePalette);
            }
            if (props?.theme?.mode && props.theme.mode !== themeStore.currentMode) {
                themeStore.applyMode(props.theme.mode as ThemeMode);
            }
            if (props?.locale?.current) {
                document.documentElement.setAttribute('lang', props.locale.current);
                document.documentElement.setAttribute('dir', props.locale.is_rtl ? 'rtl' : 'ltr');
            }
        });
    }
}
