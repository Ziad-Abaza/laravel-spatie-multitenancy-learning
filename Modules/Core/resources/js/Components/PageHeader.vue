<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { useI18n } from '../Composables/useI18n';

withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        backHref?: string;
        backLabel?: string;
    }>(),
    {
        subtitle: '',
        backHref: '',
        backLabel: '',
    }
);

const { t } = useI18n();
</script>

<template>
    <div>
        <Link
            v-if="backHref"
            :href="backHref"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-text-muted hover:text-text-main mb-4 transition-colors"
        >
            <ArrowLeft class="w-4 h-4 rtl:rotate-180" />
            <span>{{ backLabel || t('back', 'Back') }}</span>
        </Link>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="min-w-0 flex items-center gap-4">
                <slot name="leading" />
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-text-main tracking-tight truncate">
                        <slot name="title">{{ title }}</slot>
                    </h1>
                    <p v-if="subtitle || $slots.subtitle" class="text-xs text-text-muted mt-1">
                        <slot name="subtitle">{{ subtitle }}</slot>
                    </p>
                </div>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
