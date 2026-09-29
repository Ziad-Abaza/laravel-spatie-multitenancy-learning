import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface LocaleProps {
    current?: string;
    is_rtl?: boolean;
    translations?: Record<string, string>;
}

export function useI18n() {
    const page = usePage<{ locale?: LocaleProps }>();

    const locale = computed(() => page.props.locale?.current || 'en');
    const isRtl = computed(() => Boolean(page.props.locale?.is_rtl));
    const translations = computed(() => page.props.locale?.translations || {});

    function t(key: string, fallback?: string): string {
        if (translations.value && translations.value[key]) {
            return translations.value[key];
        }
        return fallback || key;
    }

    function trans(key: string, replace: Record<string, string | number> = {}): string {
        let line = t(key);
        for (const [placeholder, value] of Object.entries(replace)) {
            line = line.replace(new RegExp(`:${placeholder}`, 'g'), String(value));
        }
        return line;
    }

    return {
        locale,
        isRtl,
        t,
        trans,
    };
}
