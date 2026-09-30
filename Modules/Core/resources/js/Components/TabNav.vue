<script setup lang="ts" generic="K extends string | number">
import { type Component } from 'vue';

/** Pill-style tab strip — matches the settings-page tab pattern. */
defineProps<{
    modelValue: K;
    items: Array<{ key: K; label: string; icon?: Component }>;
}>();

const emit = defineEmits<{ (e: 'update:modelValue', key: K): void }>();
</script>

<template>
    <div class="flex flex-wrap items-center gap-1 p-1 rounded-2xl bg-surface-card border border-border-subtle w-fit" role="tablist">
        <button
            v-for="item in items"
            :key="item.key"
            type="button"
            role="tab"
            :aria-selected="modelValue === item.key"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer"
            :class="modelValue === item.key
                ? 'bg-primary-600 text-on-primary shadow-xs'
                : 'text-text-muted hover:text-text-main hover:bg-surface-hover'"
            @click="emit('update:modelValue', item.key)"
        >
            <component :is="item.icon" v-if="item.icon" class="w-4 h-4" />
            <span>{{ item.label }}</span>
        </button>
    </div>
</template>
