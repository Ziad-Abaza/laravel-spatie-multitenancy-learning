<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import EnterpriseDataGrid, { ColumnDefinition } from '@core/Components/EnterpriseDataGrid.vue';
import FilterSelect from '@core/Components/FilterSelect.vue';
import BadgeCell from '@core/Components/BadgeCell.vue';
import { useI18n } from '@core/Composables/useI18n';

interface AuditLogItem {
    id: number;
    actor_label: string;
    actor_guard: string;
    action: string;
    target_kind: string;
    target_label: string | null;
    ip: string | null;
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
    logs: Paginated<AuditLogItem>;
    actions: string[];
    filters: {
        action?: string;
        search?: string;
    };
}>();

const { t } = useI18n();
const search = ref(props.filters.search || '');
const action = ref(props.filters.action || '');

const actionOptions = computed(() =>
    props.actions.map((a) => ({ value: a, label: t(`audit_action_${a.replace('.', '_')}`, a) }))
);

function applyFilters(page?: number) {
    router.get(
        '/audit-logs',
        {
            search: search.value || undefined,
            action: action.value || undefined,
            page: page ?? undefined,
        },
        { preserveState: true, replace: true }
    );
}

function goToPage(page: number) {
    applyFilters(page);
}

const columns = computed<ColumnDefinition[]>(() => [
    { key: 'created_at', label: t('time', 'Time'), sortable: true },
    { key: 'actor_label', label: t('actor', 'Actor') },
    { key: 'action', label: t('action', 'Action') },
    { key: 'target_label', label: t('target', 'Target') },
    { key: 'ip', label: t('ip_address', 'IP') },
]);

function handleSearch(val: string) {
    search.value = val;
    applyFilters();
}
</script>

<template>
    <TenantLayout>
        <EnterpriseDataGrid
            :title="t('audit_log', 'Audit Log')"
            :description="t('audit_log_sub', 'Append-only record of workspace activity and membership changes.')"
            :columns="columns"
            :rows="logs.data"
            :total-count="logs.total"
            :pagination="{
                current_page: logs.current_page,
                last_page: logs.last_page,
                total: logs.total,
                per_page: logs.per_page,
            }"
            :search-query="search"
            @search="handleSearch"
            @page-change="goToPage"
        >
            <template #toolbar-actions>
                <FilterSelect
                    v-model="action"
                    :options="actionOptions"
                    :placeholder="t('all_actions', 'All Actions')"
                    @change="() => applyFilters()"
                />
            </template>

            <template #cell-action="{ row }">
                <BadgeCell variant="info">{{ t(`audit_action_${row.action.replace('.', '_')}`, row.action) }}</BadgeCell>
            </template>
            <template #cell-target_label="{ row }">
                <span class="text-text-muted">{{ row.target_label || '—' }}</span>
            </template>
            <template #cell-ip="{ row }">
                <span class="font-mono text-xs text-text-muted">{{ row.ip || '—' }}</span>
            </template>
        </EnterpriseDataGrid>
    </TenantLayout>
</template>
