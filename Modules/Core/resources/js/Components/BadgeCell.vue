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
        success: 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/20',
        warning: 'bg-amber-500/15 text-amber-400 border border-amber-500/20',
        danger: 'bg-rose-500/15 text-rose-400 border border-rose-500/20',
        info: 'bg-indigo-500/15 text-indigo-400 border border-indigo-500/20',
        neutral: 'bg-slate-800 text-slate-300 border border-slate-700/60',
    };

    return `${base} ${sizeClasses} ${variants[props.variant]}`;
});

const dotColor = computed(() => {
    const colors = {
        success: 'bg-emerald-400',
        warning: 'bg-amber-400',
        danger: 'bg-rose-400',
        info: 'bg-indigo-400',
        neutral: 'bg-slate-400',
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
