<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import { useI18n } from '@core/Composables/useI18n';
import {
    Database,
    Layers,
    Users,
    ArrowLeft,
    ExternalLink,
    Trash2,
} from 'lucide-vue-next';

interface TenantDetail {
    id: number;
    name: string;
    slug: string;
    domain: string;
    database: string;
    status: string;
    url: string;
    user_count: number;
    created_at: string;
    plan: {
        id: number;
        name: string;
        price: number;
        limits: {
            max_users?: number;
            max_storage_mb?: number;
        };
    } | null;
    subscriptions: Array<{
        id: number;
        plan_name: string;
        amount: number;
        currency: string;
        billing_interval: string;
        status: string;
        starts_at: string;
        ends_at: string | null;
    }>;
}

const props = defineProps<{
    tenant: TenantDetail;
}>();

const { t } = useI18n();

const showSuspendModal = ref(false);
const showActivateModal = ref(false);
const showDeleteModal = ref(false);

const actionForm = useForm({});

function suspendTenant() {
    actionForm.post(`/landlord/tenants/${props.tenant.id}/suspend`, {
        onSuccess: () => { showSuspendModal.value = false; }
    });
}

function activateTenant() {
    actionForm.post(`/landlord/tenants/${props.tenant.id}/activate`, {
        onSuccess: () => { showActivateModal.value = false; }
    });
}

function deleteTenant() {
    actionForm.delete(`/landlord/tenants/${props.tenant.id}`, {
        onSuccess: () => { showDeleteModal.value = false; }
    });
}
</script>

<template>
    <LandlordLayout>
        <div class="space-y-8 max-w-6xl mx-auto">
            <!-- Back & Header -->
            <div>
                <Link
                    href="/landlord/tenants"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-text-muted hover:text-text-main mb-4 transition-colors"
                >
                    <ArrowLeft class="w-4 h-4 rtl:rotate-180" />
                    <span>{{ t('back_to_tenants', 'Back to Tenants') }}</span>
                </Link>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-primary-500/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-xl">
                            {{ tenant.name.charAt(0) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ tenant.name }}</h1>
                                <StatusBadge :status="tenant.status" />
                            </div>
                            <div class="flex items-center gap-4 mt-1 text-xs text-text-muted">
                                <a :href="tenant.url" target="_blank" class="flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:underline">
                                    <span>{{ tenant.domain }}</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                                <span>•</span>
                                <span class="font-mono text-text-muted">{{ tenant.database }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-2">
                        <button
                            v-if="tenant.status !== 'suspended'"
                            type="button"
                            @click="showSuspendModal = true"
                            class="px-3.5 py-2 rounded-xl bg-warning/10 text-warning-fg border border-warning/25 hover:bg-warning/20 text-xs font-semibold transition-all cursor-pointer"
                        >
                            {{ t('suspend_tenant', 'Suspend Workspace') }}
                        </button>

                        <button
                            v-else
                            type="button"
                            @click="showActivateModal = true"
                            class="px-3.5 py-2 rounded-xl bg-success/10 text-success-fg border border-success/25 hover:bg-success/20 text-xs font-semibold transition-all cursor-pointer"
                        >
                            {{ t('activate_tenant', 'Activate Workspace') }}
                        </button>

                        <button
                            type="button"
                            @click="showDeleteModal = true"
                            class="p-2 rounded-xl bg-danger/10 text-danger-fg border border-danger/25 hover:bg-danger/20 text-xs transition-all cursor-pointer"
                            :title="t('delete_tenant', 'Delete Workspace')"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Grid of Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Database Isolation Card -->
                <div class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-3 shadow-sm">
                    <div class="flex items-center gap-2.5 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                        <Database class="w-4 h-4" />
                        <span>{{ t('database_isolation', 'Dedicated Database') }}</span>
                    </div>
                    <div class="text-lg font-bold font-mono text-text-main">{{ tenant.database }}</div>
                    <p class="text-xs text-text-muted">
                        {{ t('database_isolation_note', 'Isolated schema with independent migrations, permissions, and tenant users.') }}
                    </p>
                </div>

                <!-- Plan & Quota Card -->
                <div class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-3 shadow-sm">
                    <div class="flex items-center gap-2.5 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                        <Layers class="w-4 h-4" />
                        <span>{{ t('subscription_plan', 'Subscription Tier') }}</span>
                    </div>
                    <div class="text-lg font-bold text-text-main flex items-center justify-between">
                        <span>{{ tenant.plan?.name ?? t('free_tier', 'Free Tier') }}</span>
                        <span v-if="tenant.plan" class="text-sm font-normal text-text-muted">${{ tenant.plan.price }}/mo</span>
                    </div>
                    <div class="text-xs text-text-muted space-y-1">
                        <div>{{ t('max_users', 'Max Users') }}: {{ tenant.plan?.limits?.max_users ?? 'Unlimited' }}</div>
                        <div>{{ t('storage', 'Storage Quota') }}: {{ tenant.plan?.limits?.max_storage_mb ?? 1000 }} MB</div>
                    </div>
                </div>

                <!-- Team & Usage Card -->
                <div class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-3 shadow-sm">
                    <div class="flex items-center gap-2.5 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                        <Users class="w-4 h-4" />
                        <span>{{ t('workspace_users', 'Current Users') }}</span>
                    </div>
                    <div class="text-lg font-bold text-text-main">{{ tenant.user_count }} {{ t('active_users', 'members') }}</div>
                    <p class="text-xs text-text-muted">
                        {{ t('isolated_user_records', 'User accounts live in this tenant database only.') }}
                    </p>
                </div>
            </div>

            <!-- Subscriptions History -->
            <div class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-4 shadow-sm">
                <h2 class="text-base font-bold text-text-main">{{ t('subscription_history', 'Subscription History') }}</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead>
                            <tr class="border-b border-border-subtle text-text-muted uppercase tracking-wider">
                                <th class="py-2.5 px-3 text-start">{{ t('plan', 'Plan') }}</th>
                                <th class="py-2.5 px-3 text-start">{{ t('billing_cycle', 'Interval') }}</th>
                                <th class="py-2.5 px-3 text-start">{{ t('amount', 'Amount') }}</th>
                                <th class="py-2.5 px-3 text-start">{{ t('status', 'Status') }}</th>
                                <th class="py-2.5 px-3 text-start">{{ t('starts_at', 'Started') }}</th>
                                <th class="py-2.5 px-3 text-start">{{ t('ends_at', 'Ends') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            <tr v-for="sub in tenant.subscriptions" :key="sub.id" class="hover:bg-surface-hover">
                                <td class="py-3 px-3 font-semibold text-text-main">{{ sub.plan_name }}</td>
                                <td class="py-3 px-3 capitalize text-text-muted">{{ sub.billing_interval }}</td>
                                <td class="py-3 px-3 font-semibold text-text-main">
                                    <CurrencyCell :amount="sub.amount" :currency="sub.currency" />
                                </td>
                                <td class="py-3 px-3">
                                    <StatusBadge :status="sub.status" />
                                </td>
                                <td class="py-3 px-3 text-text-muted">{{ sub.starts_at }}</td>
                                <td class="py-3 px-3 text-text-muted">{{ sub.ends_at }}</td>
                            </tr>
                            <tr v-if="tenant.subscriptions.length === 0">
                                <td colspan="6" class="py-6 text-center text-text-subtle">
                                    {{ t('no_subscriptions_found', 'No subscription records found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Suspend Modal -->
        <ConfirmDialog
            :is-open="showSuspendModal"
            :title="t('confirm_suspend_title', 'Suspend Organization Workspace?')"
            :message="t('confirm_suspend_message', 'This will immediately lock all users from logging in or using the workspace until reactivated.')"
            :confirm-text="t('suspend', 'Suspend Workspace')"
            variant="warning"
            @close="showSuspendModal = false"
            @confirm="suspendTenant"
        />

        <!-- Activate Modal -->
        <ConfirmDialog
            :is-open="showActivateModal"
            :title="t('confirm_activate_title', 'Activate Organization Workspace?')"
            :message="t('confirm_activate_message', 'This will restore access for all tenant members and reactivate subscriptions.')"
            :confirm-text="t('activate', 'Activate Workspace')"
            variant="success"
            @close="showActivateModal = false"
            @confirm="activateTenant"
        />

        <!-- Delete Modal -->
        <ConfirmDialog
            :is-open="showDeleteModal"
            :title="t('confirm_delete_tenant_title', 'Permanently Delete Workspace?')"
            :message="t('confirm_delete_tenant_message', 'CAUTION: This will drop the dedicated database and delete all tenant records permanently. This action cannot be undone.')"
            :confirm-text="t('delete_forever', 'Delete Forever')"
            variant="danger"
            @close="showDeleteModal = false"
            @confirm="deleteTenant"
        />
    </LandlordLayout>
</template>
