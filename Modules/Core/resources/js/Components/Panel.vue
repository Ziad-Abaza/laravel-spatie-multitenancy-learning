<script setup lang="ts">
import { type Component } from 'vue';

/**
 * Content card — the recurring "rounded-3xl surface-card" block.
 * Optional icon/title/description header; default slot is body;
 * named slots: `actions` (header end), `footer` (separated strip).
 */
withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        icon?: Component;
        padding?: 'sm' | 'md' | 'lg';
        flush?: boolean;
    }>(),
    {
        title: '',
        description: '',
        icon: undefined,
        padding: 'lg',
        flush: false,
    }
);

const paddingClass = { sm: 'p-4', md: 'p-5', lg: 'p-6 sm:p-8' };
</script>

<template>
    <section class="rounded-3xl bg-surface-card border border-border-subtle shadow-sm overflow-hidden">
        <header
            v-if="title || description || icon || $slots.actions"
            class="flex items-start justify-between gap-3 border-b border-border-subtle"
            :class="paddingClass[padding] + ' pb-4'"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                    <component :is="icon" v-if="icon" class="w-4 h-4 shrink-0" />
                    <slot name="title">{{ title }}</slot>
                </div>
                <p v-if="description" class="mt-1 text-xs text-text-muted">{{ description }}</p>
            </div>
            <div v-if="$slots.actions" class="shrink-0">
                <slot name="actions" />
            </div>
        </header>

        <div :class="flush ? '' : paddingClass[padding]">
            <slot />
        </div>

        <footer v-if="$slots.footer" class="border-t border-border-subtle bg-surface-card/40" :class="paddingClass[padding]">
            <slot name="footer" />
        </footer>
    </section>
</template>
