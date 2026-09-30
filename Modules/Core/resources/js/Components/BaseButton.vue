<script setup lang="ts">
import { computed } from 'vue';
import { type Component } from 'vue';
import { Link } from '@inertiajs/vue3';

/**
 * Unified button. variant maps to the design-system semantic tokens.
 * `href` renders an Inertia Link (internal); `external` renders <a target=_blank>.
 */
const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'ghost';
        size?: 'sm' | 'md';
        icon?: Component;
        type?: 'button' | 'submit';
        href?: string;
        external?: boolean;
        disabled?: boolean;
        loading?: boolean;
        title?: string;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        icon: undefined,
        type: 'button',
        href: undefined,
        external: false,
        disabled: false,
        loading: false,
        title: undefined,
    }
);

const classes = computed(() => {
    const base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer border';
    const size = props.size === 'sm' ? 'px-3 py-1.5 text-[11px]' : 'px-4 py-2.5 text-xs';
    const variants = {
        primary: 'bg-primary-600 hover:bg-primary-500 text-on-primary border-transparent shadow-md shadow-primary-600/20',
        secondary: 'bg-surface-input hover:bg-surface-hover text-text-main border-border-subtle',
        success: 'bg-success/10 text-success-fg border-success/25 hover:bg-success/20',
        warning: 'bg-warning/10 text-warning-fg border-warning/25 hover:bg-warning/20',
        danger: 'bg-danger/10 text-danger-fg border-danger/25 hover:bg-danger/20',
        ghost: 'text-text-muted hover:text-text-main hover:bg-surface-hover border-transparent',
    };
    return `${base} ${size} ${variants[props.variant]}`;
});
</script>

<template>
    <a v-if="href && external" :href="href" target="_blank" rel="noopener" :class="classes" :title="title">
        <component :is="icon" v-if="icon" class="w-4 h-4 shrink-0" />
        <slot />
    </a>
    <Link v-else-if="href" :href="href" :class="classes" :title="title">
        <component :is="icon" v-if="icon" class="w-4 h-4 shrink-0" />
        <slot />
    </Link>
    <button v-else :type="type" :class="classes" :disabled="disabled || loading" :title="title">
        <span v-if="loading" class="w-3.5 h-3.5 border-2 border-current/30 border-t-current rounded-full animate-spin shrink-0" />
        <component :is="icon" v-else-if="icon" class="w-4 h-4 shrink-0" />
        <slot />
    </button>
</template>
