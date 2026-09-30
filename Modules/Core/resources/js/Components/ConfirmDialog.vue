<script setup lang="ts">
import { computed } from 'vue';
import { AlertTriangle } from 'lucide-vue-next';
import Modal from './Modal.vue';
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
    confirmButtonClass?: string;
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
    confirmButtonClass: '',
    loading: false,
});

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'close'): void;
}>();

const { t } = useI18n();

const isVisible = computed(() => (props.isOpen !== undefined ? props.isOpen : props.show ?? false));

const variantButtonClasses: Record<NonNullable<Props['variant']>, string> = {
    danger: 'bg-danger hover:bg-danger/90 text-on-primary',
    warning: 'bg-warning hover:bg-warning/90 text-on-primary',
    success: 'bg-success hover:bg-success/90 text-on-primary',
    primary: 'bg-primary-600 hover:bg-primary-500 text-on-primary',
};

const variantIconClasses: Record<NonNullable<Props['variant']>, string> = {
    danger: 'text-danger-fg',
    warning: 'text-warning-fg',
    success: 'text-success-fg',
    primary: 'text-primary-600 dark:text-primary-400',
};

const resolvedButtonClass = computed(() => props.confirmButtonClass || variantButtonClasses[props.variant]);
const iconColorClass = computed(() => {
    const cls = props.confirmButtonClass || '';
    if (cls.includes('warning') || cls.includes('amber') || cls.includes('yellow')) return variantIconClasses.warning;
    if (cls.includes('success') || cls.includes('emerald') || cls.includes('green')) return variantIconClasses.success;
    if (cls.includes('primary') || cls.includes('indigo') || cls.includes('blue')) return variantIconClasses.primary;
    return variantIconClasses[props.variant];
});
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
            <button
                type="button"
                class="px-4 py-2 text-sm font-medium text-text-main bg-surface-input hover:bg-surface-hover border border-border-subtle rounded-xl transition-colors"
                :disabled="loading"
                @click="emit('close')"
            >
                {{ cancelText || t('cancel', 'Cancel') }}
            </button>
            <button
                type="button"
                class="px-4 py-2 text-sm font-medium rounded-xl transition-colors disabled:opacity-50 inline-flex items-center gap-2 shadow-xs"
                :class="resolvedButtonClass"
                :disabled="loading"
                @click="emit('confirm')"
            >
                <span v-if="loading" class="w-4 h-4 border-2 border-on-primary/30 border-t-on-primary rounded-full animate-spin" />
                {{ confirmText || t('delete', 'Confirm') }}
            </button>
        </template>
    </Modal>
</template>
