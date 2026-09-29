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
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-sm flex flex-col justify-between hover:border-slate-700/60 transition-colors">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-400">{{ title }}</span>
            <div v-if="icon" class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                <component :is="icon" class="w-5 h-5 stroke-[1.75]" />
            </div>
        </div>

        <div class="mt-4">
            <div class="text-3xl font-bold tracking-tight text-white">
                {{ value }}
            </div>
            <div v-if="trend || description" class="mt-2 flex items-center gap-2 text-xs">
                <span
                    v-if="trend"
                    class="font-semibold px-1.5 py-0.5 rounded"
                    :class="trend.isPositive ? 'bg-emerald-500/15 text-emerald-400' : 'bg-rose-500/15 text-rose-400'"
                >
                    {{ trend.isPositive ? '↑' : '↓' }} {{ trend.value }}
                </span>
                <span v-if="description" class="text-slate-400">{{ description }}</span>
            </div>
        </div>
    </div>
</template>
