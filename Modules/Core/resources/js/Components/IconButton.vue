<script setup lang="ts">
import { type Component } from 'vue';
import { Link } from '@inertiajs/vue3';

/** Icon-only action button for table rows / headers. */
withDefaults(
    defineProps<{
        icon: Component;
        title?: string;
        variant?: 'neutral' | 'primary' | 'success' | 'warning' | 'danger';
        href?: string;
        external?: boolean;
        disabled?: boolean;
    }>(),
    {
        title: undefined,
        variant: 'neutral',
        href: undefined,
        external: false,
        disabled: false,
    }
);

const emit = defineEmits<{ (e: 'click'): void }>();

const variantClasses = {
    neutral: 'text-text-muted hover:text-text-main hover:bg-surface-hover',
    primary: 'text-text-muted hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-500/10',
    success: 'text-success-fg hover:bg-success/10',
    warning: 'text-warning-fg hover:bg-warning/10',
    danger: 'text-danger-fg hover:bg-danger/10',
};

const baseClass = 'p-1.5 rounded-lg transition-colors inline-flex items-center justify-center disabled:opacity-40 cursor-pointer';
</script>

<template>
    <a v-if="href && external" :href="href" target="_blank" rel="noopener" :class="[baseClass, variantClasses[variant]]" :title="title">
        <component :is="icon" class="w-4 h-4" />
    </a>
    <Link v-else-if="href" :href="href" :class="[baseClass, variantClasses[variant]]" :title="title">
        <component :is="icon" class="w-4 h-4" />
    </Link>
    <button v-else type="button" :class="[baseClass, variantClasses[variant]]" :title="title" :disabled="disabled" @click="emit('click')">
        <component :is="icon" class="w-4 h-4" />
    </button>
</template>
