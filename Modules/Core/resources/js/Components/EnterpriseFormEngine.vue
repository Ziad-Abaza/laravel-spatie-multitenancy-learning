<script setup lang="ts">
import { useI18n } from '../Composables/useI18n';

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
    <form class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm" @submit.prevent="emit('submit')">
        <div v-if="title || description" class="mb-6 pb-4 border-b border-slate-800/80">
            <h3 v-if="title" class="text-lg font-semibold text-white">
                {{ title }}
            </h3>
            <p v-if="description" class="mt-1 text-sm text-slate-400">
                {{ description }}
            </p>
        </div>

        <!-- Form fields slot -->
        <div class="space-y-5">
            <slot />
        </div>

        <!-- Form actions footer -->
        <div class="mt-8 pt-5 border-t border-slate-800/80 flex items-center justify-end gap-3">
            <button
                v-if="cancelText || $slots.cancel"
                type="button"
                class="px-4 py-2.5 rounded-xl border border-slate-800 bg-slate-950/60 hover:bg-slate-800 text-sm font-medium text-slate-300 transition-colors"
                :disabled="loading"
                @click="emit('cancel')"
            >
                <slot name="cancel">{{ cancelText || t('cancel', 'Cancel') }}</slot>
            </button>

            <button
                type="submit"
                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-sm font-semibold text-white transition-all shadow-md shadow-indigo-600/20 disabled:opacity-50 inline-flex items-center gap-2 cursor-pointer"
                :disabled="loading || disabled"
            >
                <span v-if="loading" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin" />
                <slot name="submit">{{ submitText || t('save', 'Save Changes') }}</slot>
            </button>
        </div>
    </form>
</template>
