<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'success' | 'warning' | 'danger' | 'info' | 'neutral';
        size?: 'sm' | 'md';
        dot?: boolean;
    }>(),
    {
        variant: 'neutral',
        size: 'sm',
        dot: false,
    }
);

const classes = computed(() => {
    const base = 'inline-flex items-center font-medium rounded-full transition-colors';
    const sizeClasses = props.size === 'sm' ? 'px-2.5 py-0.5 text-xs' : 'px-3 py-1 text-sm';

    const variants = {
        success: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25',
        warning: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25',
        danger: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/25',
        info: 'bg-primary-500/15 text-primary-600 dark:text-primary-400 border border-primary-500/25',
        neutral: 'bg-surface-hover text-text-muted border border-border-subtle',
    };

    return `${base} ${sizeClasses} ${variants[props.variant]}`;
});

const dotColor = computed(() => {
    const colors = {
        success: 'bg-emerald-500',
        warning: 'bg-amber-500',
        danger: 'bg-rose-500',
        info: 'bg-primary-500',
        neutral: 'bg-text-subtle',
    };
    return colors[props.variant];
});
</script>

<template>
    <span :class="classes">
        <span v-if="dot" class="w-1.5 h-1.5 rounded-full me-1.5" :class="dotColor" />
        <slot />
    </span>
</template>
