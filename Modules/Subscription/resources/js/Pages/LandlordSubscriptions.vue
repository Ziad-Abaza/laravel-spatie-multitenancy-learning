<script setup lang="ts">
import { computed } from 'vue';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import EnterpriseDataGrid, { ColumnDefinition } from '@core/Components/EnterpriseDataGrid.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import { useI18n } from '@core/Composables/useI18n';

interface SubscriptionItem {
    id: number;
    tenant_name: string;
    tenant_domain: string;
    plan_name: string;
    status: string;
    amount: number;
    currency: string;
    billing_interval: string;
    starts_at: string;
    ends_at: string;
    trial_ends_at: string | null;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    subscriptions: Paginated<SubscriptionItem>;
}>();

const { t } = useI18n();

const columns = computed<ColumnDefinition[]>(() => [
    { key: 'tenant_name', label: t('tenant', 'Tenant') },
    { key: 'plan_name', label: t('plan', 'Plan') },
    { key: 'amount', label: t('amount', 'Amount') },
    { key: 'billing_interval', label: t('billing_interval', 'Interval') },
    { key: 'status', label: t('status', 'Status') },
    { key: 'starts_at', label: t('starts_at', 'Started') },
    { key: 'ends_at', label: t('ends_at', 'Next Renewal') },
]);
</script>

<template>
    <LandlordLayout>
        <EnterpriseDataGrid
            :title="t('subscriptions_directory', 'Tenant Subscriptions')"
            :description="t('subscriptions_directory_sub', 'Live overview of active, trialing, past-due, and cancelled tenant subscriptions.')"
            :columns="columns"
            :rows="subscriptions.data"
            :total-count="subscriptions.total"
        >
            <template #cell-tenant_name="{ row }">
                <div>
                    <div class="font-semibold text-text-main">{{ row.tenant_name }}</div>
                    <div class="font-mono text-[11px] text-text-muted">{{ row.tenant_domain }}</div>
                </div>
            </template>

            <template #cell-amount="{ row }">
                <CurrencyCell :amount="row.amount" :currency="row.currency" />
            </template>

            <template #cell-billing_interval="{ row }">
                <span class="capitalize text-text-main">{{ row.billing_interval }}</span>
            </template>

            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
        </EnterpriseDataGrid>
    </LandlordLayout>
</template>
