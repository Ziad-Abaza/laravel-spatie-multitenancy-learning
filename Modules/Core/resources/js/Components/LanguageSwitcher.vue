<script setup lang="ts">
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from '../Composables/useI18n';

const page = usePage();
const { locale } = useI18n();

// locale.supported is the server-side source of truth (settings service).
const supportedLocales = computed<Record<string, string>>(() => {
    return (page.props.locale as any)?.supported || {};
});

function switchLocale(newLocale: string) {
    if (newLocale === locale.value) return;

    // dir/lang are applied by setupInertiaStateBridge from the response props.
    router.post('/locale', { locale: newLocale }, {
        preserveScroll: true,
        preserveState: false,
    });
}
</script>

<template>
    <div class="inline-flex items-center rounded-xl bg-surface-card border border-border-subtle p-0.5 text-xs font-medium text-text-muted shadow-xs">
        <button
            v-for="(label, code) in supportedLocales"
            :key="code"
            type="button"
            class="px-2.5 py-1 rounded-lg transition-all uppercase"
            :class="locale === code ? 'bg-primary-600 text-on-primary shadow-xs font-semibold' : 'text-text-muted hover:text-text-main'"
            :title="label"
            :aria-label="label"
            @click="switchLocale(code)"
        >
            {{ code }}
        </button>
    </div>
</template>
