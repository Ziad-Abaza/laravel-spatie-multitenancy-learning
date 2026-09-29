<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        amount: number | string;
        currency?: string;
        period?: string;
    }>(),
    {
        currency: 'USD',
        period: '',
    }
);

const formatted = computed(() => {
    const num = Number(props.amount) || 0;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(num);
});
</script>

<template>
    <span class="inline-flex items-baseline font-semibold tracking-tight">
        <span class="text-white">{{ formatted }}</span>
        <span v-if="period" class="ms-1 text-xs text-slate-400 font-normal">/ {{ period }}</span>
    </span>
</template>
