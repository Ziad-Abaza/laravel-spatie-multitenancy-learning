<script setup lang="ts">
import { useI18n } from '../Composables/useI18n';
import BaseButton from './BaseButton.vue';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        submitText?: string;
        cancelText?: string;
        loading?: boolean;
        disabled?: boolean;
    }>(),
    {
        title: '',
        description: '',
        submitText: '',
        cancelText: '',
        loading: false,
        disabled: false,
    }
);

const emit = defineEmits<{
    (e: 'submit'): void;
    (e: 'cancel'): void;
}>();

const { t } = useI18n();
</script>

<template>
    <form class="bg-surface-card border border-border-subtle rounded-2xl p-6 shadow-xs" @submit.prevent="emit('submit')">
        <div v-if="title || description" class="mb-6 pb-4 border-b border-border-subtle">
            <h3 v-if="title" class="text-lg font-semibold text-text-main">
                {{ title }}
            </h3>
            <p v-if="description" class="mt-1 text-sm text-text-muted">
                {{ description }}
            </p>
        </div>

        <!-- Form fields slot -->
        <div class="space-y-5">
            <slot />
        </div>

        <!-- Form actions footer -->
        <div class="mt-8 pt-5 border-t border-border-subtle flex items-center justify-end gap-3">
            <BaseButton
                v-if="cancelText || $slots.cancel"
                variant="secondary"
                :disabled="loading"
                @click="emit('cancel')"
            >
                <slot name="cancel">{{ cancelText || t('cancel', 'Cancel') }}</slot>
            </BaseButton>

            <BaseButton
                type="submit"
                variant="primary"
                :loading="loading"
                :disabled="disabled"
            >
                <slot name="submit">{{ submitText || t('save', 'Save Changes') }}</slot>
            </BaseButton>
        </div>
    </form>
</template>
