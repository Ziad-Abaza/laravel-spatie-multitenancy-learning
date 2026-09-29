<script setup lang="ts">
import { type Component } from 'vue';

defineProps<{
    title: string;
    value: string | number;
    description?: string;
    icon?: Component;
    trend?: {
        value: string;
        isPositive: boolean;
    };
}>();
</script>

<template>
    <div class="p-6 rounded-2xl bg-surface-card border border-border-subtle shadow-xs flex flex-col justify-between hover:border-border-strong transition-colors">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-text-muted">{{ title }}</span>
            <div v-if="icon" class="w-10 h-10 rounded-xl bg-primary-500/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                <component :is="icon" class="w-5 h-5 stroke-[1.75]" />
            </div>
        </div>

        <div class="mt-4">
            <div class="text-3xl font-bold tracking-tight text-text-main">
                {{ value }}
            </div>
            <div v-if="trend || description" class="mt-2 flex items-center gap-2 text-xs">
                <span
                    v-if="trend"
                    class="font-semibold px-1.5 py-0.5 rounded"
                    :class="trend.isPositive ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/15 text-rose-600 dark:text-rose-400'"
                >
                    {{ trend.isPositive ? '↑' : '↓' }} {{ trend.value }}
                </span>
                <span v-if="description" class="text-text-muted">{{ description }}</span>
            </div>
        </div>
    </div>
</template>
