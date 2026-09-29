<script setup lang="ts">
import { computed } from 'vue';
import BadgeCell from './BadgeCell.vue';
import { useI18n } from '../Composables/useI18n';

const props = defineProps<{
    status: string;
    size?: 'sm' | 'md';
}>();

const { t } = useI18n();

const variant = computed<'success' | 'warning' | 'danger' | 'info' | 'neutral'>(() => {
    switch (props.status?.toLowerCase()) {
        case 'active':
            return 'success';
        case 'trialing':
            return 'info';
        case 'past_due':
        case 'suspended':
            return 'warning';
        case 'canceled':
        case 'expired':
            return 'danger';
        default:
            return 'neutral';
    }
});

const label = computed(() => {
    const raw = props.status?.toLowerCase();
    return t(raw, raw ? raw.charAt(0).toUpperCase() + raw.slice(1) : '-');
});
</script>

<template>
    <BadgeCell :variant="variant" :size="size" dot>
        {{ label }}
    </BadgeCell>
</template>
