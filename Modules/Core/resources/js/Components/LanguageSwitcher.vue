<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Languages } from 'lucide-vue-next';
import { useI18n } from '../Composables/useI18n';

const { locale } = useI18n();

function switchLocale(newLocale: string) {
    if (newLocale === locale.value) return;

    router.visit(window.location.href, {
        method: 'get',
        data: { locale: newLocale },
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            const isRtl = newLocale === 'ar';
            document.documentElement.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
            document.documentElement.setAttribute('lang', newLocale);
        },
    });
}
</script>

<template>
    <div class="inline-flex items-center rounded-xl bg-slate-900/80 border border-slate-800 p-0.5 text-xs font-medium text-slate-300">
        <button
            type="button"
            class="px-2.5 py-1 rounded-lg transition-all"
            :class="locale === 'en' ? 'bg-indigo-600 text-white shadow-sm font-semibold' : 'text-slate-400 hover:text-slate-200'"
            @click="switchLocale('en')"
        >
            EN
        </button>
        <button
            type="button"
            class="px-2.5 py-1 rounded-lg transition-all"
            :class="locale === 'ar' ? 'bg-indigo-600 text-white shadow-sm font-semibold' : 'text-slate-400 hover:text-slate-200'"
            @click="switchLocale('ar')"
        >
            عربي
        </button>
    </div>
</template>
