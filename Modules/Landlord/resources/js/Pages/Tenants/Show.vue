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
    Save,
    XCircle,
    CreditCard,
    CalendarClock,
    Pencil,
    AlertTriangle,
} from 'lucide-vue-next';

interface TenantDetail {
    id: number;
    name: string;
    slug: string;
    domain: string;
    database: string;
    status: string;
    trial_ends_at: string | null;
    suspended_at: string | null;
    suspension_reason: string | null;
    url: string;
    user_count: number;
    created_at: string;
    users: Array<{
        id: number;
        name: string;
        email: string;
        roles: string[];
        created_at: string;
    }>;
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
    plans: Array<{ id: number; name: string; price: number }>;
}>();

const { t } = useI18n();

const showSuspendModal = ref(false);
const showActivateModal = ref(false);
const showDeleteModal = ref(false);
const showArchiveModal = ref(false);
const showCancelSubModal = ref(false);

const suspendForm = useForm({ reason: '' });
const deleteForm = useForm({ drop_database: true });
const archiveForm = useForm({});
const activateForm = useForm({});
const cancelSubForm = useForm({});

const identityForm = useForm({
    name: props.tenant.name,
    slug: props.tenant.slug,
    domain: props.tenant.domain,
});

const planForm = useForm({
    plan_id: props.tenant.plan?.id ?? '',
    billing_interval: 'monthly',
});

const trialForm = useForm({
    trial_ends_at: props.tenant.trial_ends_at ?? '',
});

const activeSubscription = () =>
    props.tenant.subscriptions.find((s) => ['active', 'trialing'].includes(s.status)) ?? null;

function suspendTenant() {
    suspendForm.post(`/landlord/tenants/${props.tenant.id}/suspend`, {
        onSuccess: () => { showSuspendModal.value = false; }
    });
}

function activateTenant() {
    activateForm.post(`/landlord/tenants/${props.tenant.id}/activate`, {
        onSuccess: () => { showActivateModal.value = false; }
    });
}

function archiveTenant() {
    archiveForm.post(`/landlord/tenants/${props.tenant.id}/archive`, {
        onSuccess: () => { showArchiveModal.value = false; }
    });
}

function cancelSubscription() {
    cancelSubForm.post(`/landlord/tenants/${props.tenant.id}/cancel-subscription`, {
        onSuccess: () => { showCancelSubModal.value = false; }
    });
}

function saveIdentity() {
    identityForm.put(`/landlord/tenants/${props.tenant.id}`);
}

function applyPlan() {
    planForm.post(`/landlord/tenants/${props.tenant.id}/plan`);
}

function extendTrial() {
    trialForm.post(`/landlord/tenants/${props.tenant.id}/trial`);
}

function deleteTenant() {
    deleteForm.delete(`/landlord/tenants/${props.tenant.id}`, {
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
                            v-if="tenant.status !== 'archived'"
                            type="button"
                            @click="showArchiveModal = true"
                            class="px-3.5 py-2 rounded-xl bg-surface-input text-text-muted border border-border-subtle hover:bg-surface-hover text-xs font-semibold transition-all cursor-pointer"
                        >
                            {{ t('archive_tenant', 'Archive') }}
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

            <!-- Suspension Reason Banner -->
            <div v-if="tenant.status === 'suspended'" class="p-4 rounded-2xl bg-warning/10 border border-warning/25 text-warning-fg text-xs flex items-start gap-2.5">
                <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" />
                <div>
                    <div class="font-semibold">{{ t('tenant_suspended_at', 'Suspended') }}: {{ tenant.suspended_at }}</div>
                    <div v-if="tenant.suspension_reason" class="mt-0.5 text-text-muted">{{ t('reason', 'Reason') }}: {{ tenant.suspension_reason }}</div>
                </div>
            </div>

            <!-- Workspace Controls -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Identity -->
                <form @submit.prevent="saveIdentity" class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-4 shadow-sm">
                    <div class="flex items-center gap-2.5 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                        <Pencil class="w-4 h-4" />
                        <span>{{ t('tenant_identity', 'Identity') }}</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1">{{ t('name', 'Name') }}</label>
                            <input v-model="identityForm.name" type="text" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1">{{ t('slug', 'Slug') }}</label>
                            <input v-model="identityForm.slug" type="text" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs font-mono outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1">{{ t('domain', 'Domain') }}</label>
                            <input v-model="identityForm.domain" type="text" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs font-mono outline-none focus:border-primary-500" />
                        </div>
                    </div>
                    <button type="submit" :disabled="identityForm.processing" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold transition-colors disabled:opacity-50">
                        <Save class="w-4 h-4" />
                        <span>{{ t('save_changes', 'Save') }}</span>
                    </button>
                </form>

                <!-- Plan & Billing -->
                <form @submit.prevent="applyPlan" class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-4 shadow-sm">
                    <div class="flex items-center gap-2.5 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                        <CreditCard class="w-4 h-4" />
                        <span>{{ t('plan_billing', 'Plan & Billing') }}</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1">{{ t('plan', 'Plan') }}</label>
                            <select v-model="planForm.plan_id" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500">
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }} — <CurrencyCell :amount="plan.price" /></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1">{{ t('billing_cycle', 'Interval') }}</label>
                            <select v-model="planForm.billing_interval" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500">
                                <option value="monthly">{{ t('monthly', 'Monthly') }}</option>
                                <option value="yearly">{{ t('yearly', 'Yearly') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" :disabled="planForm.processing" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold transition-colors disabled:opacity-50">
                            <Layers class="w-4 h-4" />
                            <span>{{ t('apply_plan', 'Apply Plan') }}</span>
                        </button>
                        <button
                            v-if="activeSubscription()"
                            type="button"
                            @click="showCancelSubModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-danger-fg hover:bg-danger/10 text-xs font-semibold transition-colors"
                        >
                            <XCircle class="w-4 h-4" />
                            <span>{{ t('cancel_subscription', 'Cancel') }}</span>
                        </button>
                    </div>
                </form>

                <!-- Trial & Lifecycle -->
                <form @submit.prevent="extendTrial" class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-4 shadow-sm">
                    <div class="flex items-center gap-2.5 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                        <CalendarClock class="w-4 h-4" />
                        <span>{{ t('trial_lifecycle', 'Trial & Lifecycle') }}</span>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('trial_ends_at', 'Trial Ends At') }}</label>
                        <input v-model="trialForm.trial_ends_at" type="date" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                        <p v-if="tenant.trial_ends_at" class="mt-1.5 text-[11px] text-text-muted">{{ t('current_trial', 'Current trial end') }}: {{ tenant.trial_ends_at }}</p>
                    </div>
                    <button type="submit" :disabled="trialForm.processing" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold transition-colors disabled:opacity-50">
                        <CalendarClock class="w-4 h-4" />
                        <span>{{ t('extend_trial', 'Extend Trial') }}</span>
                    </button>
                </form>
            </div>

            <!-- Workspace Users -->
            <div class="p-6 rounded-3xl bg-surface-card border border-border-subtle space-y-4 shadow-sm">
                <h2 class="text-base font-bold text-text-main flex items-center gap-2">
                    <Users class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                    {{ t('workspace_users_list', 'Workspace Users') }}
                    <span class="text-xs font-normal text-text-muted">({{ tenant.user_count }})</span>
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead>
                            <tr class="border-b border-border-subtle text-text-muted uppercase tracking-wider">
                                <th class="py-2.5 px-3 text-start">{{ t('name', 'Name') }}</th>
                                <th class="py-2.5 px-3 text-start">{{ t('email', 'Email') }}</th>
                                <th class="py-2.5 px-3 text-start">{{ t('roles', 'Roles') }}</th>
                                <th class="py-2.5 px-3 text-start">{{ t('joined', 'Joined') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            <tr v-for="user in tenant.users" :key="user.id" class="hover:bg-surface-hover">
                                <td class="py-3 px-3 font-semibold text-text-main">{{ user.name }}</td>
                                <td class="py-3 px-3 text-text-muted">{{ user.email }}</td>
                                <td class="py-3 px-3">
                                    <span v-for="role in user.roles" :key="role" class="inline-block me-1 px-2 py-0.5 rounded-md bg-primary-500/10 text-primary-600 dark:text-primary-400 text-[10px] font-semibold">{{ role }}</span>
                                </td>
                                <td class="py-3 px-3 text-text-muted">{{ user.created_at }}</td>
                            </tr>
                            <tr v-if="!tenant.users || tenant.users.length === 0">
                                <td colspan="4" class="py-6 text-center text-text-subtle">{{ t('no_users_found', 'No users in this workspace.') }}</td>
                            </tr>
                        </tbody>
                    </table>
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
        >
            <div class="mt-3">
                <label class="block text-xs font-medium text-text-main mb-1">{{ t('suspension_reason', 'Suspension Reason (optional)') }}</label>
                <input v-model="suspendForm.reason" type="text" :placeholder="t('suspension_reason_placeholder', 'e.g. Payment overdue, policy violation')" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
            </div>
        </ConfirmDialog>

        <!-- Archive Modal -->
        <ConfirmDialog
            :is-open="showArchiveModal"
            :title="t('confirm_archive_title', 'Archive This Workspace?')"
            :message="t('confirm_archive_message', 'Archiving closes access and removes the tenant from active operations. Data is retained and can be reactivated later.')"
            :confirm-text="t('archive', 'Archive')"
            variant="warning"
            @close="showArchiveModal = false"
            @confirm="archiveTenant"
        />

        <!-- Cancel Subscription Modal -->
        <ConfirmDialog
            :is-open="showCancelSubModal"
            :title="t('confirm_cancel_sub_title', 'Cancel Current Subscription?')"
            :message="t('confirm_cancel_sub_message', 'The active subscription will be marked canceled. The workspace keeps its data; assign a new plan to restore billing.')"
            :confirm-text="t('cancel_subscription', 'Cancel Subscription')"
            variant="danger"
            @close="showCancelSubModal = false"
            @confirm="cancelSubscription"
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
        >
            <label class="mt-3 flex items-center gap-2 text-xs text-text-main cursor-pointer">
                <input v-model="deleteForm.drop_database" type="checkbox" class="w-4 h-4 rounded border-border-subtle accent-danger" />
                <span>{{ t('drop_database', 'Also drop the tenant database (irreversible)') }}</span>
            </label>
        </ConfirmDialog>
    </LandlordLayout>
</template>
