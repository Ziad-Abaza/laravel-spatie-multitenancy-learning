<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import EnterpriseDataGrid, { ColumnDefinition } from '@core/Components/EnterpriseDataGrid.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Plus, Eye, ExternalLink, Ban, Play, Trash2 } from 'lucide-vue-next';

interface TenantItem {
    id: number;
    name: string;
    slug: string;
    domain: string;
    database: string;
    status: string;
    plan_name: string;
    url: string;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

const props = defineProps<{
    tenants: Paginated<TenantItem>;
    plans: Array<{ id: number; name: string }>;
    filters: {
        search?: string;
        status?: string;
        plan_id?: string | number;
    };
}>();

const { t } = useI18n();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const planId = ref(props.filters.plan_id || '');

const statusOptions = ['active', 'trialing', 'suspended', 'archived'];

function applyFilters(page?: number) {
    router.get(
        '/landlord/tenants',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            plan_id: planId.value || undefined,
            page: page ?? undefined,
        },
        { preserveState: true, replace: true }
    );
}

function goToPage(page: number) {
    applyFilters(page);
}

type PendingAction = { type: 'suspend' | 'activate' | 'delete'; row: TenantItem } | null;
const pendingAction = ref<PendingAction>(null);
const actionProcessing = ref(false);

const pendingMeta = computed(() => {
    const a = pendingAction.value;
    if (!a) return null;
    if (a.type === 'suspend') {
        return {
            title: t('confirm_suspend_title', 'Suspend Organization Workspace?'),
            message: t('confirm_suspend_message', 'This will immediately lock all users from logging in or using the workspace until reactivated.'),
            confirm: t('suspend', 'Suspend'),
            variant: 'warning' as const,
        };
    }
    if (a.type === 'activate') {
        return {
            title: t('confirm_activate_title', 'Activate Organization Workspace?'),
            message: t('confirm_activate_message', 'This will restore access for all tenant members and reactivate subscriptions.'),
            confirm: t('activate', 'Activate'),
            variant: 'success' as const,
        };
    }
    return {
        title: t('confirm_delete_tenant_title', 'Permanently Delete Workspace?'),
        message: t('confirm_delete_tenant_message', 'CAUTION: This will drop the dedicated database and delete all tenant records permanently. This action cannot be undone.'),
        confirm: t('delete_forever', 'Delete Forever'),
        variant: 'danger' as const,
    };
});

function confirmPending() {
    const a = pendingAction.value;
    if (!a) return;
    actionProcessing.value = true;
    const done = { onFinish: () => { actionProcessing.value = false; pendingAction.value = null; } };
    if (a.type === 'delete') {
        router.delete(`/landlord/tenants/${a.row.id}`, { ...done, preserveState: true });
    } else {
        router.post(`/landlord/tenants/${a.row.id}/${a.type}`, {}, { ...done, preserveState: true });
    }
}

const columns = computed<ColumnDefinition[]>(() => [
    { key: 'name', label: t('organization', 'Organization'), sortable: true },
    { key: 'domain', label: t('domain', 'Domain') },
    { key: 'database', label: t('database', 'Database') },
    { key: 'plan_name', label: t('plan', 'Plan') },
    { key: 'status', label: t('status', 'Status') },
    { key: 'created_at', label: t('created_at', 'Created At') },
    { key: 'actions', label: t('actions', 'Actions'), align: 'end' },
]);

function handleSearch(val: string) {
    search.value = val;
    applyFilters();
}

function rowClick(row: TenantItem) {
    router.visit(`/landlord/tenants/${row.id}`);
}
</script>

<template>
    <LandlordLayout>
        <EnterpriseDataGrid
            :title="t('tenants_directory', 'Tenant Organizations')"
            :description="t('tenants_directory_sub', 'Manage multi-tenant organizations, isolated tenant databases, and subscriptions.')"
            :columns="columns"
            :rows="tenants.data"
            :total-count="tenants.total"
            :pagination="{
                current_page: tenants.current_page,
                last_page: tenants.last_page,
                total: tenants.total,
                per_page: tenants.per_page,
            }"
            :search-query="search"
            @update:search-query="handleSearch"
            @page-change="goToPage"
            @row-click="rowClick"
        >
            <template #toolbar-actions>
                <select
                    v-model="status"
                    @change="applyFilters"
                    class="px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500"
                >
                    <option value="">{{ t('all_statuses', 'All Statuses') }}</option>
                    <option v-for="s in statusOptions" :key="s" :value="s" class="capitalize">{{ t(s, s) }}</option>
                </select>
                <select
                    v-model="planId"
                    @change="applyFilters"
                    class="px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500"
                >
                    <option value="">{{ t('all_plans', 'All Plans') }}</option>
                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                </select>
                <Link
                    href="/landlord/tenants/create"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary font-semibold text-xs shadow-md shadow-primary-600/20 transition-all cursor-pointer"
                >
                    <Plus class="w-4 h-4" />
                    <span>{{ t('provision_tenant', 'Provision Tenant') }}</span>
                </Link>
            </template>

            <!-- Custom Domain Cell -->
            <template #cell-domain="{ row }">
                <a
                    :href="row.url"
                    target="_blank"
                    @click.stop
                    class="inline-flex items-center gap-1 font-mono text-xs text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300"
                >
                    <span>{{ row.domain }}</span>
                    <ExternalLink class="w-3 h-3" />
                </a>
            </template>

            <!-- Custom Database Cell -->
            <template #cell-database="{ row }">
                <span class="font-mono text-xs text-text-muted">{{ row.database }}</span>
            </template>

            <!-- Custom Status Cell -->
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>

            <!-- Custom Actions Cell -->
            <template #cell-actions="{ row }">
                <div class="flex items-center justify-end gap-1" @click.stop>
                    <Link
                        :href="`/landlord/tenants/${row.id}`"
                        class="p-1.5 rounded-lg text-text-muted hover:text-text-main hover:bg-surface-hover transition-colors"
                        :title="t('view_tenant', 'View Tenant')"
                    >
                        <Eye class="w-4 h-4" />
                    </Link>
                    <a
                        :href="row.url"
                        target="_blank"
                        class="p-1.5 rounded-lg text-text-muted hover:text-primary-600 dark:hover:text-primary-400 hover:bg-surface-hover transition-colors"
                        :title="t('open_workspace', 'Open Workspace')"
                    >
                        <ExternalLink class="w-4 h-4" />
                    </a>
                    <button
                        v-if="row.status === 'suspended'"
                        type="button"
                        class="p-1.5 rounded-lg text-success-fg hover:bg-success/10 transition-colors"
                        :title="t('activate_tenant', 'Activate Workspace')"
                        @click="pendingAction = { type: 'activate', row }"
                    >
                        <Play class="w-4 h-4" />
                    </button>
                    <button
                        v-else-if="row.status !== 'archived'"
                        type="button"
                        class="p-1.5 rounded-lg text-warning-fg hover:bg-warning/10 transition-colors"
                        :title="t('suspend_tenant', 'Suspend Workspace')"
                        @click="pendingAction = { type: 'suspend', row }"
                    >
                        <Ban class="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        class="p-1.5 rounded-lg text-danger-fg hover:bg-danger/10 transition-colors"
                        :title="t('delete_tenant', 'Delete Workspace')"
                        @click="pendingAction = { type: 'delete', row }"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </template>
        </EnterpriseDataGrid>

        <ConfirmDialog
            :is-open="pendingAction !== null"
            :title="pendingMeta?.title"
            :message="pendingMeta?.message"
            :confirm-text="pendingMeta?.confirm"
            :variant="pendingMeta?.variant ?? 'danger'"
            :loading="actionProcessing"
            @close="pendingAction = null"
            @confirm="confirmPending"
        />
    </LandlordLayout>
</template>
