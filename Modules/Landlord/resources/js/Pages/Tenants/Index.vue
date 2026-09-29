<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import EnterpriseDataGrid, { ColumnDefinition } from '@core/Components/EnterpriseDataGrid.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Plus, Eye, ExternalLink } from 'lucide-vue-next';

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
    filters: {
        search?: string;
    };
}>();

const { t } = useI18n();
const search = ref(props.filters.search || '');

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
    router.get(
        '/landlord/tenants',
        { search: val },
        { preserveState: true, replace: true }
    );
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
            :search-query="search"
            @update:search-query="handleSearch"
            @row-click="rowClick"
        >
            <template #toolbar-actions>
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
                <div class="flex items-center justify-end gap-2" @click.stop>
                    <Link
                        :href="`/landlord/tenants/${row.id}`"
                        class="p-1.5 rounded-lg text-text-muted hover:text-text-main hover:bg-surface-hover transition-colors"
                        :title="t('view_tenant', 'View Tenant')"
                    >
                        <Eye class="w-4 h-4" />
                    </Link>
                </div>
            </template>
        </EnterpriseDataGrid>
    </LandlordLayout>
</template>
