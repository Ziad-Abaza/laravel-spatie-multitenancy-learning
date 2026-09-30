<script setup lang="ts">
/**
 * Theme-palette radio picker — the labeled color-dots grid used by
 * landlord and tenant theme settings. Each palette: { id, label?, colors[] }.
 */
withDefaults(
    defineProps<{
        modelValue: string;
        palettes: Array<{ id: string; label?: string; colors: string[] }>;
        columns?: 2 | 3;
    }>(),
    {
        columns: 3,
    }
);

const emit = defineEmits<{ (e: 'update:modelValue', id: string): void }>();
</script>

<template>
    <div class="grid gap-3" :class="columns === 2 ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3'">
        <label
            v-for="p in palettes"
            :key="p.id"
            class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer capitalize text-xs transition-all"
            :class="modelValue === p.id ? 'bg-primary-500/10 border-primary-500 text-text-main font-semibold' : 'bg-surface-input border-border-subtle text-text-muted hover:text-text-main'"
        >
            <input type="radio" :checked="modelValue === p.id" :value="p.id" class="sr-only" @change="emit('update:modelValue', p.id)" />
            <span class="flex shrink-0">
                <span
                    v-for="(c, i) in p.colors"
                    :key="c"
                    class="w-4 h-4 rounded-full shadow-xs border border-black/10"
                    :class="i > 0 ? '-ms-1.5' : ''"
                    :style="{ backgroundColor: c }"
                />
            </span>
            <span>{{ p.label || p.id }}</span>
        </label>
    </div>
</template>
