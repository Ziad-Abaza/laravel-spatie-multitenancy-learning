<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';
import { useI18n } from '../Composables/useI18n';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: '',
        description: '',
    }
);

const { t } = useI18n();
</script>

<template>
    <div class="flex flex-col items-center justify-center p-8 md:p-12 text-center rounded-2xl border border-dashed border-border-subtle bg-surface-card/50">
        <div class="w-12 h-12 rounded-xl bg-surface-hover border border-border-subtle flex items-center justify-center text-text-muted mb-4 shadow-inner">
            <slot name="icon">
                <Inbox class="w-6 h-6 stroke-[1.75]" />
            </slot>
        </div>

        <h3 class="text-base font-semibold text-text-main">
            {{ title || t('no_data', 'No records found') }}
        </h3>

        <p class="mt-1.5 text-sm text-text-muted max-w-sm">
            {{ description || t('no_data_desc', 'Get started by creating your first entry.') }}
        </p>

        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
