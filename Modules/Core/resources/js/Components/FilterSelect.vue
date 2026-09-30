<script setup lang="ts">
/**
 * Compact select used in index-page filter toolbars.
 * Emits `change` after updating — wire it to a shared applyFilters() that
 * reloads via router.get with preserveState.
 */
withDefaults(
    defineProps<{
        modelValue: string | number;
        options: Array<{ value: string | number; label: string }> | Record<string, string>;
        placeholder?: string;
        ariaLabel?: string;
    }>(),
    {
        placeholder: '',
        ariaLabel: undefined,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
    (e: 'change', value: string | number): void;
}>();

function onChange(e: Event) {
    const value = (e.target as HTMLSelectElement).value;
    emit('update:modelValue', value);
    emit('change', value);
}
</script>

<template>
    <select
        :value="modelValue"
        :aria-label="ariaLabel || placeholder"
        class="px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
        @change="onChange"
    >
        <option v-if="placeholder" value="">{{ placeholder }}</option>
        <template v-if="Array.isArray(options)">
            <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </template>
        <template v-else>
            <option v-for="(label, value) in options" :key="value" :value="value">{{ label }}</option>
        </template>
    </select>
</template>
