<script setup lang="ts">
import { computed } from 'vue';
import { AlertTriangle } from 'lucide-vue-next';
import Modal from './Modal.vue';
import BaseButton from './BaseButton.vue';
import { useI18n } from '../Composables/useI18n';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    show?: boolean;
    isOpen?: boolean;
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'success' | 'primary';
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    show: undefined,
    isOpen: undefined,
    title: '',
    message: '',
    confirmText: '',
    cancelText: '',
    variant: 'danger',
    loading: false,
});

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'close'): void;
}>();

const { t } = useI18n();

const isVisible = computed(() => (props.isOpen !== undefined ? props.isOpen : props.show ?? false));

const variantIconClasses: Record<NonNullable<Props['variant']>, string> = {
    danger: 'text-danger-fg',
    warning: 'text-warning-fg',
    success: 'text-success-fg',
    primary: 'text-primary-600 dark:text-primary-400',
};

const iconColorClass = computed(() => variantIconClasses[props.variant]);
</script>

<template>
    <Modal :show="isVisible" max-width="md" @close="emit('close')">
        <template #title>
            <span class="flex items-center gap-2">
                <AlertTriangle class="w-5 h-5 shrink-0" :class="iconColorClass" />
                <span class="text-text-main font-semibold">{{ title || t('confirm_delete_title', 'Confirm Action') }}</span>
            </span>
        </template>

        <p class="text-sm text-text-muted">
            {{ message || t('confirm_delete_text', 'Are you sure you want to proceed? This action cannot be undone.') }}
        </p>

        <slot />

        <template #footer>
            <BaseButton variant="secondary" :disabled="loading" @click="emit('close')">
                {{ cancelText || t('cancel', 'Cancel') }}
            </BaseButton>
            <BaseButton :variant="variant" :loading="loading" @click="emit('confirm')">
                {{ confirmText || t('delete', 'Confirm') }}
            </BaseButton>
        </template>
    </Modal>
</template>
