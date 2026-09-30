<script setup lang="ts">
import { computed } from 'vue';
import { useCurrency } from '@core/Composables/useCurrency';

const props = withDefaults(
    defineProps<{
        amount: number | string;
        currency?: string;
        period?: string;
    }>(),
    {
        currency: undefined,
        period: '',
    }
);

const { format } = useCurrency();

const formatted = computed(() => format(props.amount, props.currency));
</script>

<template>
    <span class="inline-flex items-baseline font-semibold tracking-tight">
        <span class="text-text-main">{{ formatted }}</span>
        <span v-if="period" class="ms-1 text-xs text-text-muted font-normal">/ {{ period }}</span>
    </span>
</template>
