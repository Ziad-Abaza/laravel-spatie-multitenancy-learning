<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import Panel from '@core/Components/Panel.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import IconButton from '@core/Components/IconButton.vue';
import DataTable from '@core/Components/DataTable.vue';
import BadgeCell from '@core/Components/BadgeCell.vue';
import StatCard from '@core/Components/StatCard.vue';
import { useI18n } from '@core/Composables/useI18n';
import {
    Database,
    Layers,
    Users,
    ExternalLink,
    Trash2,
    Save,
    XCircle,
    CreditCard,
    CalendarClock,
    Pencil,
    AlertTriangle,
    HardDrive,
    Download,
    Activity,
    Archive,
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
    erasure_requested_at: string | null;
    retention_until: string | null;
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
    backups: Array<{
        id: number;
        size: number;
        driver: string;
        status: string;
        created_at: string;
        download_url: string;
    }>;
    can_export: boolean;
    diagnostics: {
        reachable: boolean;
        user_count: number | null;
        active_users: number | null;
        storage_bytes: number | null;
        last_user_activity: string | null;
    };
    usage: Array<{
        id: number;
        metric: string;
        value: number;
        recorded_at: string;
    }> | null;
}>();

const { t } = useI18n();

const showSuspendModal = ref(false);
const showActivateModal = ref(false);
const showDeleteModal = ref(false);
const showArchiveModal = ref(false);
const showCancelSubModal = ref(false);
const showErasureModal = ref(false);

const suspendForm = useForm({ reason: '' });
const deleteForm = useForm({ drop_database: false });
const archiveForm = useForm({});
const activateForm = useForm({});
const cancelSubForm = useForm({});
const erasureForm = useForm({});

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

const userColumns = [
    { key: 'name', label: t('name', 'Name') },
    { key: 'email', label: t('email', 'Email') },
    { key: 'roles', label: t('roles', 'Roles') },
    { key: 'created_at', label: t('joined', 'Joined') },
];

const subscriptionColumns = [
    { key: 'plan_name', label: t('plan', 'Plan') },
    { key: 'billing_interval', label: t('billing_cycle', 'Interval') },
    { key: 'amount', label: t('amount', 'Amount') },
    { key: 'status', label: t('status', 'Status') },
    { key: 'starts_at', label: t('starts_at', 'Started') },
    { key: 'ends_at', label: t('ends_at', 'Ends') },
];

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

function requestErasure() {
    erasureForm.post(`/landlord/tenants/${props.tenant.id}/request-erasure`, {
        onSuccess: () => { showErasureModal.value = false; }
    });
}

function cancelErasure() {
    erasureForm.post(`/landlord/tenants/${props.tenant.id}/cancel-erasure`);
}

const backupForm = useForm({});
const deleteBackupForm = useForm({});
const pendingBackupDelete = ref<number | null>(null);

const backupColumns = [
    { key: 'created_at', label: t('created_at', 'Created At') },
    { key: 'driver', label: t('driver', 'Driver') },
    { key: 'size', label: t('size', 'Size') },
    { key: 'status', label: t('status', 'Status') },
    { key: 'actions', label: '' },
];

function formatBytes(bytes: number | null): string {
    if (bytes === null) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function createBackup() {
    backupForm.post(`/landlord/tenants/${props.tenant.id}/backups`);
}

function deleteBackup() {
    if (pendingBackupDelete.value === null) return;
    deleteBackupForm.delete(`/landlord/tenants/${props.tenant.id}/backups/${pendingBackupDelete.value}`, {
        onSuccess: () => { pendingBackupDelete.value = null; },
    });
}

const usageColumns = [
    { key: 'recorded_at', label: t('recorded_at', 'Recorded At') },
    { key: 'metric', label: t('metric', 'Metric') },
    { key: 'value', label: t('value', 'Value') },
];
</script>

<template>
    <LandlordLayout>
        <div class="space-y-8 max-w-6xl mx-auto">
            <PageHeader
                :title="tenant.name"
                :subtitle="tenant.database"
                back-href="/landlord/tenants"
                :back-label="t('back_to_tenants', 'Back to Tenants')"
            >
                <template #leading>
                    <div class="w-14 h-14 rounded-2xl bg-primary-500/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-xl shrink-0">
                        {{ tenant.name.charAt(0) }}
                    </div>
                </template>
                <template #title>
                    <span class="flex items-center gap-3">
                        {{ tenant.name }}
                        <StatusBadge :status="tenant.status" />
                    </span>
                </template>
                <template #subtitle>
                    <span class="inline-flex items-center gap-3">
                        <a :href="tenant.url" target="_blank" class="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:underline">
                            <span>{{ tenant.domain }}</span>
                            <ExternalLink class="w-3 h-3" />
                        </a>
                        <span>•</span>
                        <span class="font-mono">{{ tenant.database }}</span>
                    </span>
                </template>
                <template #actions>
                    <BaseButton
                        v-if="tenant.status !== 'suspended'"
                        variant="warning"
                        size="sm"
                        @click="showSuspendModal = true"
                    >
                        {{ t('suspend_tenant', 'Suspend Workspace') }}
                    </BaseButton>
                    <BaseButton
                        v-else
                        variant="success"
                        size="sm"
                        @click="showActivateModal = true"
                    >
                        {{ t('activate_tenant', 'Activate Workspace') }}
                    </BaseButton>
                    <BaseButton
                        v-if="tenant.status !== 'archived'"
                        variant="secondary"
                        size="sm"
                        @click="showArchiveModal = true"
                    >
                        {{ t('archive_tenant', 'Archive') }}
                    </BaseButton>
                    <BaseButton
                        v-if="tenant.status === 'archived' && !tenant.erasure_requested_at"
                        variant="danger"
                        size="sm"
                        @click="showErasureModal = true"
                    >
                        {{ t('request_erasure', 'Request Erasure') }}
                    </BaseButton>
                    <BaseButton
                        v-if="tenant.erasure_requested_at"
                        variant="secondary"
                        size="sm"
                        :loading="erasureForm.processing"
                        @click="cancelErasure"
                    >
                        {{ t('cancel_erasure', 'Cancel Erasure') }}
                    </BaseButton>
                    <IconButton
                        :icon="Trash2"
                        variant="danger"
                        :title="t('delete_tenant', 'Delete Workspace')"
                        @click="showDeleteModal = true"
                    />
                </template>
            </PageHeader>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Panel padding="md" :title="t('database_isolation', 'Dedicated Database')" :icon="Database">
                    <div class="text-lg font-bold font-mono text-text-main">{{ tenant.database }}</div>
                    <p class="mt-2 text-xs text-text-muted">
                        {{ t('database_isolation_note', 'Isolated schema with independent migrations, permissions, and tenant users.') }}
                    </p>
                </Panel>

                <Panel padding="md" :title="t('subscription_plan', 'Subscription Tier')" :icon="Layers">
                    <div class="text-lg font-bold text-text-main flex items-center justify-between">
                        <span>{{ tenant.plan?.name ?? t('free_tier', 'Free Tier') }}</span>
                        <CurrencyCell v-if="tenant.plan" :amount="tenant.plan.price" class="text-sm font-normal text-text-muted" />
                    </div>
                    <div class="mt-2 text-xs text-text-muted space-y-1">
                        <div>{{ t('max_users', 'Max Users') }}: {{ tenant.plan?.limits?.max_users ?? 'Unlimited' }}</div>
                        <div>{{ t('storage', 'Storage Quota') }}: {{ tenant.plan?.limits?.max_storage_mb ?? 1000 }} MB</div>
                    </div>
                </Panel>

                <Panel padding="md" :title="t('workspace_users', 'Current Users')" :icon="Users">
                    <div class="text-lg font-bold text-text-main">{{ tenant.user_count }} {{ t('active_users', 'members') }}</div>
                    <p class="mt-2 text-xs text-text-muted">
                        {{ t('isolated_user_records', 'User accounts live in this tenant database only.') }}
                    </p>
                </Panel>
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
                <Panel padding="md" :title="t('tenant_identity', 'Identity')" :icon="Pencil">
                    <form class="space-y-4" @submit.prevent="saveIdentity">
                        <FormField v-model="identityForm.name" :label="t('name', 'Name')" size="sm" :error="identityForm.errors.name" />
                        <FormField v-model="identityForm.slug" :label="t('slug', 'Slug')" size="sm" class="font-mono" :error="identityForm.errors.slug" />
                        <FormField v-model="identityForm.domain" :label="t('domain', 'Domain')" size="sm" :error="identityForm.errors.domain" />
                        <BaseButton type="submit" :icon="Save" :loading="identityForm.processing">
                            {{ t('save_changes', 'Save') }}
                        </BaseButton>
                    </form>
                </Panel>

                <Panel padding="md" :title="t('plan_billing', 'Plan & Billing')" :icon="CreditCard">
                    <form class="space-y-4" @submit.prevent="applyPlan">
                        <FormField v-model="planForm.plan_id" :label="t('plan', 'Plan')" type="select" size="sm" :error="planForm.errors.plan_id">
                            <template #options>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }} — ${{ plan.price }}/mo</option>
                            </template>
                        </FormField>
                        <FormField
                            v-model="planForm.billing_interval"
                            :label="t('billing_cycle', 'Interval')"
                            type="select"
                            size="sm"
                            :options="{ monthly: t('monthly', 'Monthly'), yearly: t('yearly', 'Yearly') }"
                            :error="planForm.errors.billing_interval"
                        />
                        <div class="flex items-center gap-2">
                            <BaseButton type="submit" :icon="Layers" :loading="planForm.processing">
                                {{ t('apply_plan', 'Apply Plan') }}
                            </BaseButton>
                            <BaseButton
                                v-if="activeSubscription()"
                                variant="ghost"
                                :icon="XCircle"
                                class="!text-danger-fg hover:!bg-danger/10"
                                @click="showCancelSubModal = true"
                            >
                                {{ t('cancel_subscription', 'Cancel') }}
                            </BaseButton>
                        </div>
                    </form>
                </Panel>

                <Panel padding="md" :title="t('trial_lifecycle', 'Trial & Lifecycle')" :icon="CalendarClock">
                    <form class="space-y-4" @submit.prevent="extendTrial">
                        <FormField
                            v-model="trialForm.trial_ends_at"
                            :label="t('trial_ends_at', 'Trial Ends At')"
                            type="date"
                            size="sm"
                            :hint="tenant.trial_ends_at ? `${t('current_trial', 'Current trial end')}: ${tenant.trial_ends_at}` : ''"
                            :error="trialForm.errors.trial_ends_at"
                        />
                        <BaseButton type="submit" :icon="CalendarClock" :loading="trialForm.processing">
                            {{ t('extend_trial', 'Extend Trial') }}
                        </BaseButton>
                    </form>
                    <div
                        v-if="tenant.erasure_requested_at"
                        class="mt-4 rounded-xl border border-danger/25 bg-danger/10 px-3 py-2 text-xs text-danger-fg"
                    >
                        {{ t('erasure_scheduled_note', 'Erasure scheduled — this workspace will be permanently purged at') }}
                        <span class="font-mono font-semibold">{{ tenant.retention_until }}</span>
                    </div>
                </Panel>
            </div>

            <!-- Workspace Users -->
            <Panel padding="md" :icon="Users" :title="`${t('workspace_users_list', 'Workspace Users')} (${tenant.user_count})`">
                <DataTable
                    :columns="userColumns"
                    :rows="tenant.users"
                    :empty-title="t('no_users_found', 'No users in this workspace.')"
                    class="-mx-3"
                >
                    <template #cell-name="{ value }">
                        <span class="font-semibold text-text-main">{{ value }}</span>
                    </template>
                    <template #cell-roles="{ value }">
                        <span v-for="role in value" :key="role" class="inline-block me-1 px-2 py-0.5 rounded-md bg-primary-500/10 text-primary-600 dark:text-primary-400 text-[10px] font-semibold">{{ role }}</span>
                    </template>
                </DataTable>
            </Panel>

            <!-- Diagnostics (FEAT-20) — read-only support aggregates -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    :label="t('db_reachability', 'Database')"
                    :value="diagnostics.reachable ? t('reachable', 'Reachable') : t('unreachable', 'Unreachable')"
                    :icon="Activity"
                />
                <StatCard
                    :label="t('users_total', 'Total Users')"
                    :value="diagnostics.user_count ?? '—'"
                    :icon="Users"
                />
                <StatCard
                    :label="t('users_active', 'Active Users')"
                    :value="diagnostics.active_users ?? '—'"
                    :icon="Users"
                />
                <StatCard
                    :label="t('storage_used', 'Storage Used')"
                    :value="formatBytes(diagnostics.storage_bytes)"
                    :icon="HardDrive"
                />
            </div>

            <!-- Backups (FEAT-13) -->
            <Panel padding="md" :title="t('backups', 'Backups')" :icon="Archive">
                <template #actions>
                    <BaseButton
                        v-if="can_export"
                        size="sm"
                        :icon="Download"
                        :loading="backupForm.processing"
                        @click="createBackup"
                    >
                        {{ t('create_backup', 'Create Backup') }}
                    </BaseButton>
                </template>
                <DataTable
                    :columns="backupColumns"
                    :rows="backups"
                    :empty-title="t('no_backups_found', 'No backups yet.')"
                    class="-mx-3"
                >
                    <template #cell-size="{ value }">
                        <span class="font-mono text-xs">{{ formatBytes(value) }}</span>
                    </template>
                    <template #cell-status="{ value }">
                        <StatusBadge :status="value" />
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-2">
                            <a :href="row.download_url" class="text-primary-600 dark:text-primary-400 hover:underline text-xs">
                                {{ t('download', 'Download') }}
                            </a>
                            <IconButton
                                v-if="can_export"
                                :icon="Trash2"
                                variant="danger"
                                :title="t('delete', 'Delete')"
                                @click="pendingBackupDelete = row.id"
                            />
                        </div>
                    </template>
                </DataTable>
            </Panel>

            <!-- Usage metering history (FEAT-15) — metrics.view-gated server-side -->
            <Panel v-if="usage !== null" padding="md" :title="t('usage_history', 'Usage History')" :icon="Activity">
                <DataTable
                    :columns="usageColumns"
                    :rows="usage"
                    :empty-title="t('no_usage_found', 'No usage snapshots recorded yet.')"
                    class="-mx-3"
                >
                    <template #cell-metric="{ value }">
                        <BadgeCell variant="neutral">{{ value }}</BadgeCell>
                    </template>
                    <template #cell-value="{ row }">
                        <span class="font-mono text-xs">{{ row.metric === 'storage.bytes' ? formatBytes(row.value) : row.value }}</span>
                    </template>
                </DataTable>
            </Panel>

            <!-- Subscription History -->
            <Panel padding="md" :title="t('subscription_history', 'Subscription History')">
                <DataTable
                    :columns="subscriptionColumns"
                    :rows="tenant.subscriptions"
                    :empty-title="t('no_subscriptions_found', 'No subscription records found.')"
                    class="-mx-3"
                >
                    <template #cell-plan_name="{ value }">
                        <span class="font-semibold text-text-main">{{ value }}</span>
                    </template>
                    <template #cell-billing_interval="{ value }">
                        <span class="capitalize">{{ value }}</span>
                    </template>
                    <template #cell-amount="{ row }">
                        <CurrencyCell :amount="row.amount" :currency="row.currency" class="font-semibold text-text-main" />
                    </template>
                    <template #cell-status="{ value }">
                        <StatusBadge :status="value" />
                    </template>
                </DataTable>
            </Panel>
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
            <FormField
                v-model="suspendForm.reason"
                :label="t('suspension_reason', 'Suspension Reason (optional)')"
                :placeholder="t('suspension_reason_placeholder', 'e.g. Payment overdue, policy violation')"
                size="sm"
                class="mt-3"
            />
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
            <FormField
                v-model="deleteForm.drop_database"
                type="checkbox"
                :label="t('drop_database', 'Also drop the tenant database (irreversible)')"
                size="sm"
                class="mt-3"
            />
        </ConfirmDialog>

        <!-- Erasure Modal -->
        <ConfirmDialog
            :is-open="showErasureModal"
            :title="t('confirm_erasure_title', 'Schedule Data Erasure?')"
            :message="t('confirm_erasure_message', 'After the retention grace window elapses, this workspace\'s database and all artifacts will be permanently purged. Create a backup first if you need a recovery copy.')"
            :confirm-text="t('request_erasure', 'Request Erasure')"
            variant="danger"
            @close="showErasureModal = false"
            @confirm="requestErasure"
        />

        <!-- Delete Backup Modal -->
        <ConfirmDialog
            :is-open="pendingBackupDelete !== null"
            :title="t('confirm_delete_backup_title', 'Delete This Backup?')"
            :message="t('confirm_delete_backup_message', 'The dump file will be removed permanently. This action cannot be undone.')"
            :confirm-text="t('delete_forever', 'Delete Forever')"
            variant="danger"
            @close="pendingBackupDelete = null"
            @confirm="deleteBackup"
        />
    </LandlordLayout>
</template>
