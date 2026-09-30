<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import StatCard from '@core/Components/StatCard.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import DataTable from '@core/Components/DataTable.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import { useI18n } from '@core/Composables/useI18n';
import { useCurrency } from '@core/Composables/useCurrency';
import {
    Building2,
    CreditCard,
    DollarSign,
    Plus,
    ArrowUpRight,
    HardDrive,
} from 'lucide-vue-next';

interface Metrics {
    total_tenants: number;
    active_tenants: number;
    suspended_tenants: number;
    active_subscriptions: number;
    trialing_subscriptions: number;
    mrr: number;
    plans_distribution: Array<{
        id: number;
        name: string | Record<string, string>;
        slug: string;
        count: number;
        price: number;
    }>;
    recent_tenants: Array<{
        id: number;
        name: string;
        domain: string;
        database: string;
        status: string;
        plan_name: string;
        created_at: string;
    }>;
}

const props = defineProps<{
    metrics: Metrics;
}>();

const { t, trans } = useI18n();
const { format: formatCurrency } = useCurrency();
</script>

<template>
    <LandlordLayout>
        <div class="space-y-8">
            <!-- Header Section -->
            <PageHeader
                :title="t('landlord_dashboard', 'Landlord Platform Overview')"
                :subtitle="t('landlord_dashboard_sub', 'Real-time telemetry, tenant database provisioning, and revenue metrics.')"
            >
                <template #actions>
                    <BaseButton href="/landlord/tenants/create" :icon="Plus" class="shadow-lg shadow-primary-600/25">
                        {{ t('provision_tenant', 'Provision Tenant') }}
                    </BaseButton>
                </template>
            </PageHeader>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    :title="t('total_tenants', 'Total Tenants')"
                    :value="metrics.total_tenants"
                    :change="trans('tenants_active', { count: metrics.active_tenants })"
                    change-type="positive"
                    :icon="Building2"
                />

                <StatCard
                    :title="t('monthly_recurring_revenue', 'Estimated MRR')"
                    :value="formatCurrency(metrics.mrr)"
                    :change="trans('paying_subscriptions', { count: metrics.active_subscriptions })"
                    change-type="positive"
                    :icon="DollarSign"
                />

                <StatCard
                    :title="t('active_subscriptions', 'Subscriptions')"
                    :value="metrics.active_subscriptions"
                    :change="trans('in_trial', { count: metrics.trialing_subscriptions })"
                    change-type="neutral"
                    :icon="CreditCard"
                />

                <StatCard
                    :title="t('suspended_tenants', 'Suspended Tenants')"
                    :value="metrics.suspended_tenants"
                    :change="metrics.suspended_tenants > 0 ? t('action_needed', 'Action needed') : t('healthy', 'Healthy')"
                    :change-type="metrics.suspended_tenants > 0 ? 'negative' : 'positive'"
                    :icon="HardDrive"
                />
            </div>

            <!-- Two-Column Layout: Recent Tenants & Plan Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Recent Tenants -->
                <div class="lg:col-span-2 bg-surface-card border border-border-subtle rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-base font-bold text-text-main">{{ t('recently_provisioned_tenants', 'Recently Provisioned Tenants') }}</h2>
                                <p class="text-xs text-text-muted mt-0.5">{{ t('tenant_databases_status', 'Dedicated tenant databases status') }}</p>
                            </div>
                            <Link href="/landlord/tenants" class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 flex items-center gap-1">
                                <span>{{ t('view_all', 'View All') }}</span>
                                <ArrowUpRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>

                        <DataTable
                            :columns="[
                                { key: 'name', label: t('organization', 'Organization') },
                                { key: 'domain', label: t('domain', 'Domain') },
                                { key: 'database', label: t('database', 'Database') },
                                { key: 'status', label: t('status', 'Status') },
                                { key: 'actions', label: t('actions', 'Actions'), align: 'end' },
                            ]"
                            :rows="metrics.recent_tenants"
                            :empty-title="t('no_tenants_found', 'No tenants provisioned yet.')"
                        >
                            <template #cell-name="{ value }">
                                <span class="font-semibold text-text-main">{{ value }}</span>
                            </template>
                            <template #cell-domain="{ value }">
                                <span class="font-mono text-primary-600 dark:text-primary-400">{{ value }}</span>
                            </template>
                            <template #cell-database="{ value }">
                                <span class="font-mono text-text-muted">{{ value }}</span>
                            </template>
                            <template #cell-status="{ value }">
                                <StatusBadge :status="value" />
                            </template>
                            <template #cell-actions="{ row }">
                                <Link :href="`/landlord/tenants/${row.id}`" class="text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 font-medium">
                                    {{ t('manage', 'Manage') }}
                                </Link>
                            </template>
                        </DataTable>
                    </div>
                </div>

                <!-- Plans Distribution -->
                <div class="bg-surface-card border border-border-subtle rounded-3xl p-6 shadow-xl flex flex-col">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-base font-bold text-text-main">{{ t('plan_breakdown', 'Plan Distribution') }}</h2>
                            <p class="text-xs text-text-muted mt-0.5">{{ t('active_tenant_shares', 'Tenant subscription tiers') }}</p>
                        </div>
                        <Link href="/landlord/plans" class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300">
                            {{ t('edit_plans', 'Edit') }}
                        </Link>
                    </div>

                    <div class="space-y-4 my-auto">
                        <div
                            v-for="plan in metrics.plans_distribution"
                            :key="plan.id"
                            class="p-4 rounded-2xl bg-surface-hover border border-border-subtle space-y-2"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-text-main">
                                    {{ typeof plan.name === 'object' ? plan.name.en : plan.name }}
                                </span>
                                <span class="font-mono text-primary-600 dark:text-primary-400 font-semibold">
                                    {{ plan.count }} {{ t('tenants', 'Tenants') }}
                                </span>
                            </div>

                            <div class="w-full bg-surface-hover h-2 rounded-full overflow-hidden">
                                <div
                                    class="bg-primary-500 h-full rounded-full transition-all"
                                    :style="{
                                        width: metrics.total_tenants > 0 ? `${(plan.count / metrics.total_tenants) * 100}%` : '0%'
                                    }"
                                />
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-text-muted pt-1">
                                <span>{{ plan.slug }}</span>
                                <span><CurrencyCell :amount="plan.price" period="mo" /></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
