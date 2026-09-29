<script setup lang="ts">
withDefaults(
    defineProps<{
        type?: 'table' | 'card' | 'text' | 'stat';
        rows?: number;
    }>(),
    {
        type: 'text',
        rows: 3,
    }
);
</script>

<template>
    <div class="animate-pulse w-full">
        <!-- Table Skeleton -->
        <div v-if="type === 'table'" class="space-y-3">
            <div class="h-10 bg-surface-hover rounded-lg w-full" />
            <div v-for="i in rows" :key="i" class="h-12 bg-surface-hover/60 rounded-lg w-full" />
        </div>

        <!-- Stat Card Skeleton -->
        <div v-else-if="type === 'stat'" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="p-6 bg-surface-card border border-border-subtle rounded-xl space-y-3">
                <div class="h-4 bg-surface-hover rounded w-1/2" />
                <div class="h-8 bg-surface-hover rounded w-3/4" />
                <div class="h-3 bg-surface-hover/60 rounded w-1/3" />
            </div>
        </div>

        <!-- Card Skeleton -->
        <div v-else-if="type === 'card'" class="p-6 bg-surface-card border border-border-subtle rounded-xl space-y-4">
            <div class="h-6 bg-surface-hover rounded w-1/3" />
            <div class="space-y-2">
                <div class="h-4 bg-surface-hover/60 rounded w-full" />
                <div class="h-4 bg-surface-hover/60 rounded w-5/6" />
            </div>
            <div class="h-10 bg-surface-hover rounded-lg w-1/4" />
        </div>

        <!-- Text Skeleton -->
        <div v-else class="space-y-2.5">
            <div v-for="i in rows" :key="i" class="h-4 bg-surface-hover rounded" :style="{ width: `${100 - (i * 12)}%` }" />
        </div>
    </div>
</template>
