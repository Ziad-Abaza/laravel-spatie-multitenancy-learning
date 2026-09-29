<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import StatCard from '@core/Components/StatCard.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import { useI18n } from '@core/Composables/useI18n';
import {
    Users,
    HardDrive,
    Sparkles,
    UserPlus,
    Settings,
    ArrowUpRight,
    CreditCard,
} from 'lucide-vue-next';

interface TenantData {
    name: string;
    domain: string;
    status: string;
    plan_name: string;
    trial_ends_at: string | null;
    is_trialing: boolean;
}

interface QuotaMetric {
    current: number;
    limit: number | null;
    percentage: number;
}

interface RecentUser {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

const props = defineProps<{
    tenant: TenantData;
    quota: {
        users: QuotaMetric;
        storage: QuotaMetric;
    };
    recent_users: RecentUser[];
}>();

const { t, trans } = useI18n();
</script>

<template>
    <TenantLayout>
        <div class="space-y-8">
            <!-- Free Trial Alert Banner -->
            <div
                v-if="tenant.is_trialing"
                class="p-4 sm:p-5 rounded-3xl bg-primary-500/10 border border-primary-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xl"
            >
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-500/20 text-primary-600 dark:text-primary-400 flex items-center justify-center shrink-0">
                        <Sparkles class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-text-main">{{ t('free_trial_active', 'Workspace Trial Active') }}</h2>
                        <p class="text-xs text-text-muted mt-0.5">
                            {{ t('trial_ends_note', 'Your free trial expires on') }} <strong class="text-text-main">{{ tenant.trial_ends_at }}</strong>.
                        </p>
                    </div>
                </div>

                <Link
                    href="/subscription"
                    class="px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary font-semibold text-xs shadow-md shadow-primary-600/30 transition-all text-center shrink-0"
                >
                    {{ t('upgrade_now', 'Upgrade Workspace') }}
                </Link>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ tenant.name }}</h1>
                        <StatusBadge :status="tenant.status" size="sm" />
                    </div>
                    <p class="text-xs text-text-muted mt-1 font-mono">{{ tenant.domain }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        href="/users"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary font-semibold text-xs shadow-md shadow-primary-600/20 transition-all"
                    >
                        <UserPlus class="w-4 h-4" />
                        <span>{{ t('invite_team', 'Add Member') }}</span>
                    </Link>

                    <Link
                        href="/settings"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-card hover:bg-surface-hover text-text-main text-xs font-semibold border border-border-subtle transition-all"
                    >
                        <Settings class="w-4 h-4" />
                        <span>{{ t('settings', 'Settings') }}</span>
                    </Link>
                </div>
            </div>

            <!-- Telemetry KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <StatCard
                    :title="t('current_plan', 'Subscription Plan')"
                    :value="tenant.plan_name"
                    :change="tenant.is_trialing ? t('trialing', 'Trialing') : t('active', 'Active')"
                    change-type="positive"
                    :icon="CreditCard"
                />

                <StatCard
                    :title="t('team_members', 'Team Members')"
                    :value="quota.users.current"
                    :change="quota.users.limit ? trans('seats_remaining', { count: quota.users.limit - quota.users.current }) : t('unlimited', 'Unlimited')"
                    :change-type="quota.users.percentage > 85 ? 'negative' : 'positive'"
                    :icon="Users"
                />

                <StatCard
                    :title="t('storage_usage', 'Workspace Storage')"
                    :value="`${quota.storage.current} MB`"
                    :change="trans('percent_utilized', { percent: quota.storage.percentage })"
                    change-type="neutral"
                    :icon="HardDrive"
                />
            </div>

            <!-- Two-Column Grid: Quota Meter & Recent Team Members -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Recent Members -->
                <div class="lg:col-span-2 bg-surface-card border border-border-subtle rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-base font-bold text-text-main">{{ t('team_members', 'Recent Team Members') }}</h2>
                                <p class="text-xs text-text-muted mt-0.5">{{ t('workspace_members_sub', 'Users with isolated workspace credentials') }}</p>
                            </div>
                            <Link href="/users" class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 flex items-center gap-1">
                                <span>{{ t('view_all', 'View All') }}</span>
                                <ArrowUpRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-start text-xs">
                                <thead>
                                    <tr class="border-b border-border-subtle text-text-muted uppercase tracking-wider font-semibold">
                                        <th class="py-2.5 px-3 text-start">{{ t('name', 'Name') }}</th>
                                        <th class="py-2.5 px-3 text-start">{{ t('role', 'Role') }}</th>
                                        <th class="py-2.5 px-3 text-start">{{ t('joined', 'Joined') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-subtle">
                                    <tr v-for="user in recent_users" :key="user.id" class="hover:bg-surface-hover transition-colors">
                                        <td class="py-3 px-3">
                                            <div class="font-semibold text-text-main">{{ user.name }}</div>
                                            <div class="text-[11px] text-text-muted">{{ user.email }}</div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20">
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-text-muted font-mono text-[11px]">{{ user.created_at }}</td>
                                    </tr>
                                    <tr v-if="recent_users.length === 0">
                                        <td colspan="3" class="py-8 text-center text-text-subtle">
                                            {{ t('no_users_found', 'No users found.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Resource Quotas Meter -->
                <div class="bg-surface-card border border-border-subtle rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-base font-bold text-text-main">{{ t('resource_limits', 'Resource Limits') }}</h2>
                            <Link href="/subscription" class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300">
                                {{ t('upgrade', 'Upgrade') }}
                            </Link>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-text-muted">{{ t('user_seats', 'Team Seats') }}</span>
                                    <span class="font-mono text-primary-600 dark:text-primary-400 font-semibold">{{ quota.users.current }} / {{ quota.users.limit ?? '∞' }}</span>
                                </div>
                                <div class="w-full bg-surface-hover h-2 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="quota.users.percentage > 85 ? 'bg-danger' : 'bg-primary-500'"
                                        :style="{ width: `${quota.users.percentage}%` }"
                                    />
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-text-muted">{{ t('storage', 'Disk Storage') }}</span>
                                    <span class="font-mono text-primary-600 dark:text-primary-400 font-semibold">{{ quota.storage.current }}MB / {{ quota.storage.limit ?? '1000' }}MB</span>
                                </div>
                                <div class="w-full bg-surface-hover h-2 rounded-full overflow-hidden">
                                    <div
                                        class="bg-primary-500 h-full rounded-full transition-all"
                                        :style="{ width: `${quota.storage.percentage}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-border-subtle mt-6">
                        <Link
                            href="/subscription"
                            class="w-full py-2 px-3 rounded-xl bg-surface-input hover:bg-surface-hover text-text-main text-xs font-semibold text-center block transition-all border border-border-subtle"
                        >
                            {{ t('view_quota_details', 'View Quota & Billing Details') }} &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
