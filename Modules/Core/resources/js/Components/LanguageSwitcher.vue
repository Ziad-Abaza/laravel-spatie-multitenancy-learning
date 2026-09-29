<script setup lang="ts">
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from '../Composables/useI18n';

const page = usePage();
const { locale } = useI18n();

const supportedLocales = computed(() => {
    return (page.props.locale as any)?.supported || { en: 'English', ar: 'العربية' };
});

const isArabicSupported = computed(() => Boolean(supportedLocales.value['ar']));

function switchLocale(newLocale: string) {
    if (newLocale === locale.value) return;

    router.post('/locale', { locale: newLocale }, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            const isRtl = newLocale === 'ar';
            document.documentElement.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
            document.documentElement.setAttribute('lang', newLocale);
        },
    });
}
</script>

<template>
    <div class="inline-flex items-center rounded-xl bg-surface-card border border-border-subtle p-0.5 text-xs font-medium text-text-muted shadow-xs">
        <button
            type="button"
            class="px-2.5 py-1 rounded-lg transition-all"
            :class="locale === 'en' ? 'bg-primary-600 text-on-primary shadow-xs font-semibold' : 'text-text-muted hover:text-text-main'"
            @click="switchLocale('en')"
        >
            EN
        </button>
        <button
            v-if="isArabicSupported"
            type="button"
            class="px-2.5 py-1 rounded-lg transition-all"
            :class="locale === 'ar' ? 'bg-primary-600 text-on-primary shadow-xs font-semibold' : 'text-text-muted hover:text-text-main'"
            @click="switchLocale('ar')"
        >
            عربي
        </button>
    </div>
</template>
