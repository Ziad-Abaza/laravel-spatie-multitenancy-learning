<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import { useI18n } from '@core/Composables/useI18n';
import {
    CreditCard,
    CheckCircle2,
    Users,
    HardDrive,
    ArrowUpCircle,
} from 'lucide-vue-next';

interface PlanDetail {
    id: number;
    name: string;
    slug?: string;
    price: number;
    currency: string;
    billing_interval?: string;
    limits?: {
        max_users?: number;
        max_storage_mb?: number;
        features?: string[];
    };
    is_current?: boolean;
}

interface SubscriptionDetail {
    id: number;
    status: string;
    billing_interval: string;
    amount: number;
    trial_ends_at: string | null;
    ends_at: string | null;
}

interface UsageMetric {
    current: number;
    limit: number | null;
    percentage: number;
}

const props = defineProps<{
    plan: PlanDetail | null;
    subscription: SubscriptionDetail | null;
    usage: {
        users: UsageMetric;
        storage_mb: UsageMetric;
    };
    available_plans: PlanDetail[];
}>();

const { t } = useI18n();

const isUpgradeModalOpen = ref(false);
const selectedPlan = ref<PlanDetail | null>(null);

const upgradeForm = useForm({
    plan_id: null as number | null,
});

function promptChangePlan(targetPlan: PlanDetail) {
    selectedPlan.value = targetPlan;
    upgradeForm.plan_id = targetPlan.id;
    isUpgradeModalOpen.value = true;
}

function confirmChangePlan() {
    if (!upgradeForm.plan_id) return;
    upgradeForm.post('/subscription/change-plan', {
        onSuccess: () => {
            isUpgradeModalOpen.value = false;
        },
    });
}
</script>

<template>
    <TenantLayout>
        <div class="space-y-8 max-w-6xl mx-auto">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('subscription_and_quotas', 'Subscription & Quota Management') }}</h1>
                <p class="text-xs text-text-muted mt-1">{{ t('subscription_quotas_sub', 'Manage your workspace plan, view live resource quotas, and upgrade features.') }}</p>
            </div>

            <!-- Current Plan & Quota Telemetry -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Current Subscription Card -->
                <div class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-4 shadow-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                            <CreditCard class="w-4 h-4" />
                            <span>{{ t('current_plan', 'Current Plan') }}</span>
                        </div>
                        <StatusBadge v-if="subscription" :status="subscription.status" size="sm" />
                    </div>

                    <div>
                        <h2 class="text-2xl font-black text-text-main">{{ plan?.name || 'Free Workspace' }}</h2>
                        <div class="mt-2 text-xl font-bold text-text-main flex items-baseline gap-1">
                            <span v-if="plan && plan.price > 0">
                                <CurrencyCell :amount="plan.price" :currency="plan.currency" />
                                <span class="text-xs text-text-muted font-normal">/ {{ t(subscription?.billing_interval || 'monthly') }}</span>
                            </span>
                            <span v-else class="text-success-fg text-sm font-semibold">{{ t('free_forever', 'Free Forever') }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-border-subtle space-y-2 text-xs text-text-muted">
                        <div v-if="subscription?.trial_ends_at" class="flex items-center justify-between text-primary-600 dark:text-primary-400">
                            <span>{{ t('trial_period', 'Trial Ends') }}</span>
                            <span class="font-semibold">{{ subscription.trial_ends_at }}</span>
                        </div>
                        <div v-if="subscription?.ends_at" class="flex items-center justify-between">
                            <span>{{ t('next_renewal', 'Next Billing Date') }}</span>
                            <span class="text-text-main">{{ subscription.ends_at }}</span>
                        </div>
                    </div>
                </div>

                <!-- Users Quota Card -->
                <div class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-4 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                                <Users class="w-4 h-4" />
                                <span>{{ t('user_seats_quota', 'Team User Quota') }}</span>
                            </div>
                            <span class="text-xs font-mono font-semibold text-text-main">
                                {{ usage.users.current }} / {{ usage.users.limit ?? '∞' }}
                            </span>
                        </div>

                        <div class="w-full bg-surface-hover h-2.5 rounded-full overflow-hidden mb-2">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="usage.users.percentage >= 90 ? 'bg-danger' : 'bg-primary-500'"
                                :style="{ width: `${usage.users.percentage}%` }"
                            />
                        </div>

                        <p class="text-xs text-text-muted">
                            {{ usage.users.percentage }}% {{ t('quota_used', 'of team seats occupied.') }}
                            <span v-if="usage.users.limit && usage.users.current >= usage.users.limit" class="text-danger-fg font-semibold block mt-1">
                                {{ t('quota_reached', 'Quota reached. Upgrade your plan to add more members.') }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Storage Quota Card -->
                <div class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-4 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                                <HardDrive class="w-4 h-4" />
                                <span>{{ t('storage_quota', 'Storage Quota') }}</span>
                            </div>
                            <span class="text-xs font-mono font-semibold text-text-main">
                                {{ usage.storage_mb.current }}MB / {{ usage.storage_mb.limit ?? '1000' }}MB
                            </span>
                        </div>

                        <div class="w-full bg-surface-hover h-2.5 rounded-full overflow-hidden mb-2">
                            <div
                                class="bg-primary-500 h-full rounded-full transition-all"
                                :style="{ width: `${usage.storage_mb.percentage}%` }"
                            />
                        </div>

                        <p class="text-xs text-text-muted">
                            {{ usage.storage_mb.percentage }}% {{ t('storage_used', 'of isolated workspace storage utilized.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Available Plans Upgrade Grid -->
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-text-main tracking-tight">{{ t('available_plans', 'Available Subscription Tiers') }}</h2>
                    <p class="text-xs text-text-muted mt-0.5">{{ t('upgrade_plans_sub', 'Upgrade or modify your plan. Quota increases take effect immediately.') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="targetPlan in available_plans"
                        :key="targetPlan.id"
                        class="p-6 rounded-3xl bg-surface-card border flex flex-col justify-between transition-all"
                        :class="targetPlan.is_current ? 'border-primary-500 ring-1 ring-primary-500/50' : 'border-border-subtle hover:border-border-strong'"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-bold text-text-main">{{ targetPlan.name }}</h3>
                                <span v-if="targetPlan.is_current" class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20">
                                    {{ t('current_active_plan', 'Active Plan') }}
                                </span>
                            </div>

                            <div class="flex items-baseline gap-1 my-4">
                                <span class="text-3xl font-black text-text-main">
                                    <CurrencyCell :amount="targetPlan.price" :currency="targetPlan.currency" />
                                </span>
                                <span class="text-xs text-text-muted">/ {{ t(targetPlan.billing_interval || 'monthly') }}</span>
                            </div>

                            <ul class="space-y-2.5 text-xs text-text-muted pt-4 border-t border-border-subtle">
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-3.5 h-3.5 text-success-fg shrink-0" />
                                    <span>{{ t('max_users', 'Max Users') }}: <strong class="text-text-main">{{ targetPlan.limits?.max_users ?? t('unlimited', 'Unlimited') }}</strong></span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <CheckCircle2 class="w-3.5 h-3.5 text-success-fg shrink-0" />
                                    <span>{{ t('storage', 'Storage') }}: <strong class="text-text-main">{{ targetPlan.limits?.max_storage_mb ?? 1000 }} MB</strong></span>
                                </li>
                                <li
                                    v-for="(feature, idx) in targetPlan.limits?.features ?? []"
                                    :key="idx"
                                    class="flex items-center gap-2"
                                >
                                    <CheckCircle2 class="w-3.5 h-3.5 text-success-fg shrink-0" />
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="pt-6 mt-6 border-t border-border-subtle">
                            <button
                                v-if="!targetPlan.is_current"
                                type="button"
                                @click="promptChangePlan(targetPlan)"
                                class="w-full py-2.5 px-4 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary font-semibold text-xs shadow-md shadow-primary-600/25 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <ArrowUpCircle class="w-4 h-4" />
                                <span>{{ t('switch_to_plan', 'Switch to this Plan') }}</span>
                            </button>
                            <div
                                v-else
                                class="w-full py-2.5 text-center text-xs font-semibold text-text-subtle"
                            >
                                {{ t('currently_active', 'Currently Active') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Plan Change Modal -->
        <ConfirmDialog
            :is-open="isUpgradeModalOpen"
            :title="t('confirm_plan_change_title', 'Confirm Subscription Change?')"
            :message="t('confirm_plan_change_msg', `Do you want to switch your workspace subscription to ${selectedPlan?.name}? New limits will apply immediately.`)"
            :confirm-text="t('confirm_change', 'Update Subscription')"
            variant="primary"
            @close="isUpgradeModalOpen = false"
            @confirm="confirmChangePlan"
        />
    </TenantLayout>
</template>
