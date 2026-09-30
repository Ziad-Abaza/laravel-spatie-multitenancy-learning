<script setup lang="ts" generic="V extends string | number | boolean | null = string | number | boolean | null">
import { computed, type Component } from 'vue';
import { useI18n } from '../Composables/useI18n';

/**
 * Unified form control: label + input/select/textarea/checkbox + hint + error.
 * size: 'md' (px-4 py-2.5) for standalone forms, 'sm' (px-3 py-2) inside
 * modals/cards — the two established densities in the design system.
 */
const props = withDefaults(
    defineProps<{
        modelValue: V;
        label?: string;
        type?: 'text' | 'email' | 'password' | 'number' | 'date' | 'url' | 'tel' | 'textarea' | 'select' | 'checkbox';
        options?: Array<{ value: string | number; label: string }> | Record<string, string>;
        placeholder?: string;
        hint?: string;
        error?: string;
        required?: boolean;
        disabled?: boolean;
        size?: 'sm' | 'md';
        icon?: Component;
        dir?: string;
        min?: number | string;
        max?: number | string;
        step?: number | string;
        rows?: number;
    }>(),
    {
        label: '',
        type: 'text',
        options: () => [],
        placeholder: '',
        hint: '',
        error: '',
        required: false,
        disabled: false,
        size: 'md',
        icon: undefined,
        dir: undefined,
        min: undefined,
        max: undefined,
        step: undefined,
        rows: 3,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: V): void;
}>();

const { t } = useI18n();

const fieldId = `ff-${Math.random().toString(36).slice(2, 9)}`;

const controlClass = computed(() =>
    [
        'w-full rounded-xl bg-surface-input border text-text-main text-xs outline-none transition-colors disabled:opacity-50',
        props.size === 'sm' ? 'px-3 py-2' : 'px-4 py-2.5',
        props.error ? 'border-danger/50 focus:border-danger' : 'border-border-subtle focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
    ].join(' ')
);

const normalizedOptions = computed(() => {
    if (Array.isArray(props.options)) {
        return props.options;
    }
    return Object.entries(props.options).map(([value, label]) => ({ value, label }));
});

const controlValue = computed<string | number | null>(() =>
    typeof props.modelValue === 'boolean' ? null : (props.modelValue as string | number | null)
);

function onInput(e: Event) {
    const target = e.target as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
    emit('update:modelValue', (props.type === 'checkbox' ? (target as HTMLInputElement).checked : target.value) as V);
}
</script>

<template>
    <div v-if="type === 'checkbox'" class="flex items-center gap-2">
        <input
            :id="fieldId"
            type="checkbox"
            :checked="Boolean(modelValue)"
            :disabled="disabled"
            class="w-4 h-4 rounded border-border-subtle accent-primary-600 cursor-pointer"
            @change="onInput"
        />
        <label v-if="label" :for="fieldId" class="text-xs font-medium text-text-main cursor-pointer">
            {{ label }}
        </label>
        <p v-if="error" class="text-xs text-danger-fg">{{ error }}</p>
    </div>

    <div v-else>
        <label v-if="label" :for="fieldId" class="block text-xs font-medium text-text-main mb-1">
            {{ label }}
            <span v-if="required" class="text-danger-fg">*</span>
        </label>

        <select
            v-if="type === 'select'"
            :id="fieldId"
            :value="controlValue"
            :disabled="disabled"
            :class="controlClass"
            @change="onInput"
        >
            <slot name="options" />
            <option v-for="opt in normalizedOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
            </option>
        </select>

        <textarea
            v-else-if="type === 'textarea'"
            :id="fieldId"
            :value="controlValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            :dir="dir"
            :class="controlClass"
            @input="onInput"
        />

        <div v-else class="relative">
            <input
                :id="fieldId"
                :type="type"
                :value="controlValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                :min="min"
                :max="max"
                :step="step"
                :dir="dir"
                :class="[controlClass, icon ? 'ps-10' : '', $slots.trailing ? 'pe-0 rounded-e-none border-e-0' : '']"
                @input="onInput"
            />
            <component
                :is="icon"
                v-if="icon"
                class="w-4 h-4 text-text-subtle absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
            />
            <span
                v-if="$slots.trailing"
                class="absolute inset-y-0 end-0 px-4 inline-flex items-center bg-surface-hover text-xs font-mono text-text-muted border border-border-subtle border-s-0 rounded-e-xl"
            >
                <slot name="trailing" />
            </span>
        </div>

        <p v-if="hint && !error" class="mt-1 text-[11px] text-text-muted">{{ hint }}</p>
        <p v-if="error" class="mt-1 text-xs text-danger-fg" role="alert">{{ error }}</p>
    </div>
</template>
