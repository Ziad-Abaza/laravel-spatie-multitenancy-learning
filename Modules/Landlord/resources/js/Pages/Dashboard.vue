<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import StatCard from '@core/Components/StatCard.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import { useI18n } from '@core/Composables/useI18n';
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

const { t } = useI18n();
</script>

<template>
    <LandlordLayout>
        <div class="space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('landlord_dashboard', 'Landlord Platform Overview') }}</h1>
                    <p class="text-xs text-text-muted mt-1">{{ t('landlord_dashboard_sub', 'Real-time telemetry, tenant database provisioning, and revenue metrics.') }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/landlord/tenants/create"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white font-semibold text-xs shadow-lg shadow-primary-600/25 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ t('provision_tenant', 'Provision Tenant') }}</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    :title="t('total_tenants', 'Total Tenants')"
                    :value="metrics.total_tenants"
                    :change="`${metrics.active_tenants} active`"
                    change-type="positive"
                    :icon="Building2"
                />

                <StatCard
                    :title="t('monthly_recurring_revenue', 'Estimated MRR')"
                    :value="`$${Math.round(metrics.mrr).toLocaleString()}`"
                    :change="`${metrics.active_subscriptions} paying`"
                    change-type="positive"
                    :icon="DollarSign"
                />

                <StatCard
                    :title="t('active_subscriptions', 'Subscriptions')"
                    :value="metrics.active_subscriptions"
                    :change="`${metrics.trialing_subscriptions} in trial`"
                    change-type="neutral"
                    :icon="CreditCard"
                />

                <StatCard
                    :title="t('suspended_tenants', 'Suspended Tenants')"
                    :value="metrics.suspended_tenants"
                    :change="metrics.suspended_tenants > 0 ? 'Action needed' : 'Healthy'"
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
                            <Link href="/landlord/tenants" class="text-xs font-semibold text-primary-400 hover:text-primary-300 flex items-center gap-1">
                                <span>{{ t('view_all', 'View All') }}</span>
                                <ArrowUpRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-start text-xs">
                                <thead>
                                    <tr class="border-b border-border-subtle text-text-muted text-start uppercase tracking-wider font-semibold">
                                        <th class="py-3 px-3 text-start">{{ t('organization', 'Organization') }}</th>
                                        <th class="py-3 px-3 text-start">{{ t('domain', 'Domain') }}</th>
                                        <th class="py-3 px-3 text-start">{{ t('database', 'Database') }}</th>
                                        <th class="py-3 px-3 text-start">{{ t('status', 'Status') }}</th>
                                        <th class="py-3 px-3 text-end">{{ t('actions', 'Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-subtle">
                                    <tr v-for="tenant in metrics.recent_tenants" :key="tenant.id" class="hover:bg-surface-hover transition-colors">
                                        <td class="py-3 px-3 font-semibold text-text-main">{{ tenant.name }}</td>
                                        <td class="py-3 px-3 font-mono text-primary-400">{{ tenant.domain }}</td>
                                        <td class="py-3 px-3 font-mono text-text-muted">{{ tenant.database }}</td>
                                        <td class="py-3 px-3">
                                            <StatusBadge :status="tenant.status" />
                                        </td>
                                        <td class="py-3 px-3 text-end">
                                            <Link :href="`/landlord/tenants/${tenant.id}`" class="text-primary-400 hover:text-primary-300 font-medium">
                                                {{ t('manage', 'Manage') }}
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="metrics.recent_tenants.length === 0">
                                        <td colspan="5" class="py-8 text-center text-text-subtle">
                                            {{ t('no_tenants_found', 'No tenants provisioned yet.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Plans Distribution -->
                <div class="bg-surface-card border border-border-subtle rounded-3xl p-6 shadow-xl flex flex-col">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-base font-bold text-text-main">{{ t('plan_breakdown', 'Plan Distribution') }}</h2>
                            <p class="text-xs text-text-muted mt-0.5">{{ t('active_tenant_shares', 'Tenant subscription tiers') }}</p>
                        </div>
                        <Link href="/landlord/plans" class="text-xs font-semibold text-primary-400 hover:text-primary-300">
                            {{ t('edit_plans', 'Edit') }}
                        </Link>
                    </div>

                    <div class="space-y-4 my-auto">
                        <div
                            v-for="plan in metrics.plans_distribution"
                            :key="plan.id"
                            class="p-4 rounded-2xl bg-surface-bg border border-border-subtle space-y-2"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-text-main">
                                    {{ typeof plan.name === 'object' ? plan.name.en : plan.name }}
                                </span>
                                <span class="font-mono text-primary-400 font-semibold">
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
                                <span>${{ plan.price }}/mo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
