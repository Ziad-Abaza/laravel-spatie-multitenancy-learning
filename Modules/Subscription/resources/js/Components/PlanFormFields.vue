<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import FormField from '@core/Components/FormField.vue';
import { useI18n } from '@core/Composables/useI18n';

/** Shared create/edit plan fields — keeps the two form modals in Plans.vue in sync. */
defineProps<{
    form: InertiaForm<{
        name_en: string;
        name_ar: string;
        description_en: string;
        description_ar: string;
        price: number;
        billing_interval: string;
        trial_days: number;
        max_users: number;
        max_storage_mb: number;
    }>;
    withSlug?: boolean;
}>();

const { t } = useI18n();
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormField v-model="form.name_en" :label="t('name_english', 'Name (English)')" size="sm" required :error="form.errors.name_en" />
        <FormField v-model="form.name_ar" :label="t('name_arabic', 'Name (Arabic)')" size="sm" required dir="rtl" :error="form.errors.name_ar" />
    </div>

    <FormField
        v-if="withSlug"
        v-model="(form as any).slug"
        :label="t('slug', 'Slug')"
        size="sm"
        required
        :placeholder="t('slug_example', 'e.g. enterprise-plus')"
        :error="(form as any).errors.slug"
    />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <FormField v-model="form.price" :label="t('price', 'Price')" type="number" size="sm" min="0" step="0.01" required :error="form.errors.price" />
        <FormField
            v-model="form.billing_interval"
            :label="t('billing_interval', 'Interval')"
            type="select"
            size="sm"
            :options="{ monthly: t('monthly', 'Monthly'), yearly: t('yearly', 'Yearly') }"
            :error="form.errors.billing_interval"
        />
        <FormField v-model="form.trial_days" :label="t('trial_days', 'Trial Days')" type="number" size="sm" min="0" required :error="form.errors.trial_days" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormField v-model="form.max_users" :label="t('max_users', 'Max Users')" type="number" size="sm" min="1" required :error="form.errors.max_users" />
        <FormField v-model="form.max_storage_mb" :label="t('max_storage_mb', 'Max Storage (MB)')" type="number" size="sm" min="100" required :error="form.errors.max_storage_mb" />
    </div>
</template>
