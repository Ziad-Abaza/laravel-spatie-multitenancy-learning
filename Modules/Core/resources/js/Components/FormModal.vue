<script setup lang="ts">
import Modal from './Modal.vue';
import BaseButton from './BaseButton.vue';
import { useI18n } from '../Composables/useI18n';

/**
 * Modal + form shell: teleported dialog, submit on @submit, footer actions.
 * Replaces the repeated "Modal > form > footer buttons" block in CRUD pages.
 */
withDefaults(
    defineProps<{
        isOpen: boolean;
        title: string;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
        submitText?: string;
        cancelText?: string;
        loading?: boolean;
        submitVariant?: 'primary' | 'success' | 'warning' | 'danger';
    }>(),
    {
        maxWidth: 'md',
        submitText: '',
        cancelText: '',
        loading: false,
        submitVariant: 'primary',
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
}>();

const { t } = useI18n();
</script>

<template>
    <Modal :is-open="isOpen" :title="title" :max-width="maxWidth" @close="emit('close')">
        <form class="space-y-4" @submit.prevent="emit('submit')">
            <slot />

            <div class="pt-4 border-t border-border-subtle flex justify-end gap-2">
                <BaseButton variant="ghost" size="sm" :disabled="loading" @click="emit('close')">
                    {{ cancelText || t('cancel', 'Cancel') }}
                </BaseButton>
                <BaseButton type="submit" size="sm" :variant="submitVariant" :loading="loading">
                    <slot name="submit">{{ submitText || t('save', 'Save') }}</slot>
                </BaseButton>
            </div>
        </form>
    </Modal>
</template>
