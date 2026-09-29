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
        success: 'bg-success/10 text-success-fg border border-success/25',
        warning: 'bg-warning/10 text-warning-fg border border-warning/25',
        danger: 'bg-danger/10 text-danger-fg border border-danger/25',
        info: 'bg-info/10 text-info-fg border border-info/25',
        neutral: 'bg-surface-hover text-text-muted border border-border-subtle',
    };

    return `${base} ${sizeClasses} ${variants[props.variant]}`;
});

const dotColor = computed(() => {
    const colors = {
        success: 'bg-success',
        warning: 'bg-warning',
        danger: 'bg-danger',
        info: 'bg-info',
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
