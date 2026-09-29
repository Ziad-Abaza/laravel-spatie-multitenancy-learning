<script setup lang="ts" generic="T extends Record<string, any>">
import { ref, computed, watch } from 'vue';
import { Search, ChevronDown, ChevronUp, ChevronsUpDown } from 'lucide-vue-next';
import SkeletonLoader from './SkeletonLoader.vue';
import EmptyState from './EmptyState.vue';
import { useI18n } from '../Composables/useI18n';

export interface Column<ItemType = any> {
    key: string;
    label: string;
    sortable?: boolean;
    align?: 'left' | 'center' | 'right' | 'end';
    width?: string;
    formatter?: (item: ItemType) => any;
}

export type ColumnDefinition<ItemType = any> = Column<ItemType>;

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        columns: Column<T>[];
        data?: T[];
        rows?: T[];
        loading?: boolean;
        searchable?: boolean;
        searchPlaceholder?: string;
        searchQuery?: string;
        totalCount?: number;
        emptyTitle?: string;
        emptyDescription?: string;
        pagination?: {
            current_page?: number;
            last_page?: number;
            total?: number;
            per_page?: number;
        };
    }>(),
    {
        title: '',
        description: '',
        data: () => [],
        rows: () => [],
        loading: false,
        searchable: true,
        searchPlaceholder: '',
        searchQuery: '',
        emptyTitle: '',
        emptyDescription: '',
    }
);

const emit = defineEmits<{
    (e: 'search', query: string): void;
    (e: 'update:searchQuery', query: string): void;
    (e: 'page-change', page: number): void;
    (e: 'row-click', item: T): void;
}>();

const { t } = useI18n();

const localSearch = ref(props.searchQuery || '');
const sortKey = ref<string>('');
const sortDirection = ref<'asc' | 'desc'>('asc');

watch(
    () => props.searchQuery,
    (val) => {
        if (val !== undefined && val !== localSearch.value) {
            localSearch.value = val;
        }
    }
);

function onSearchInput(val: string) {
    localSearch.value = val;
    emit('search', val);
    emit('update:searchQuery', val);
}

function toggleSort(col: Column<T>) {
    if (!col.sortable) return;

    if (sortKey.value === col.key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = col.key;
        sortDirection.value = 'asc';
    }
}

const resolvedData = computed<T[]>(() => {
    const raw = (props.rows && props.rows.length > 0) ? props.rows : props.data;
    if (Array.isArray(raw)) {
        return raw;
    }
    if (raw && typeof raw === 'object' && Array.isArray((raw as any).data)) {
        return (raw as any).data;
    }
    return [];
});

const processedData = computed(() => {
    let items = [...resolvedData.value];

    // Local filter if data is present and search is active (and no external server search handler is listening)
    if (localSearch.value && localSearch.value.trim()) {
        const query = localSearch.value.toLowerCase();
        items = items.filter((item) =>
            Object.values(item).some((val) =>
                val !== null && val !== undefined && String(val).toLowerCase().includes(query)
            )
        );
    }

    // Local sort
    if (sortKey.value) {
        items.sort((a, b) => {
            const valA = a[sortKey.value];
            const valB = b[sortKey.value];

            if (valA === valB) return 0;
            if (valA === null || valA === undefined) return 1;
            if (valB === null || valB === undefined) return -1;

            const res = valA > valB ? 1 : -1;
            return sortDirection.value === 'asc' ? res : -res;
        });
    }

    return items;
});
</script>

<template>
    <div class="w-full bg-surface-card border border-border-subtle rounded-2xl overflow-hidden shadow-xs flex flex-col">
        <!-- Header (Title, Description, and Toolbar) -->
        <div
            v-if="title || description || $slots['toolbar-actions'] || $slots.actions || searchable"
            class="p-5 border-b border-border-subtle flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-card"
        >
            <div v-if="title || description" class="min-w-0">
                <h2 v-if="title" class="text-base font-semibold text-text-main tracking-tight">{{ title }}</h2>
                <p v-if="description" class="text-xs text-text-muted mt-0.5">{{ description }}</p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 ms-auto w-full md:w-auto">
                <div v-if="searchable" class="relative w-full sm:w-64">
                    <Search class="w-4 h-4 text-text-subtle absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        :value="localSearch"
                        type="text"
                        :placeholder="searchPlaceholder || t('search', 'Search...')"
                        class="w-full ps-9 pe-3 py-2 bg-surface-input border border-border-subtle rounded-xl text-xs text-text-main placeholder-text-subtle focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
                        @input="onSearchInput(($event.target as HTMLInputElement).value)"
                    />
                </div>

                <div class="flex items-center gap-2 justify-end">
                    <slot name="toolbar-actions" />
                    <slot name="actions" />
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="relative overflow-x-auto w-full">
            <div v-if="loading" class="p-6">
                <SkeletonLoader type="table" :rows="5" />
            </div>

            <div v-else-if="processedData.length === 0" class="p-8">
                <EmptyState :title="emptyTitle || t('no_records_found', 'No records found')" :description="emptyDescription">
                    <template #action>
                        <slot name="empty-action" />
                    </template>
                </EmptyState>
            </div>

            <table v-else class="w-full text-sm text-start">
                <thead class="bg-surface-hover/60 text-xs font-semibold text-text-muted uppercase tracking-wider border-b border-border-subtle">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            scope="col"
                            class="px-6 py-3.5 select-none"
                            :class="[
                                col.align === 'right' || col.align === 'end' ? 'text-end' : col.align === 'center' ? 'text-center' : 'text-start',
                                col.sortable ? 'cursor-pointer hover:text-text-main transition-colors' : '',
                                col.width || ''
                            ]"
                            @click="toggleSort(col)"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                {{ col.label }}
                                <span v-if="col.sortable" class="text-text-subtle">
                                    <ChevronUp v-if="sortKey === col.key && sortDirection === 'asc'" class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" />
                                    <ChevronDown v-else-if="sortKey === col.key && sortDirection === 'desc'" class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" />
                                    <ChevronsUpDown v-else class="w-3.5 h-3.5 opacity-40" />
                                </span>
                            </span>
                        </th>
                        <th v-if="$slots.rowActions" scope="col" class="px-6 py-3.5 text-end">
                            {{ t('actions', 'Actions') }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-border-subtle">
                    <tr
                        v-for="(item, idx) in processedData"
                        :key="item.id || idx"
                        class="hover:bg-surface-hover/50 transition-colors cursor-pointer"
                        @click="emit('row-click', item)"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-6 py-4 whitespace-nowrap text-text-main text-xs"
                            :class="col.align === 'right' || col.align === 'end' ? 'text-end' : col.align === 'center' ? 'text-center' : 'text-start'"
                        >
                            <!-- Supports both #cell-{col.key} and #{col.key} slots with row, item, and value bindings -->
                            <slot :name="`cell-${col.key}`" :row="item" :item="item" :value="item[col.key]">
                                <slot :name="col.key" :row="item" :item="item" :value="item[col.key]">
                                    {{ col.formatter ? col.formatter(item) : item[col.key] }}
                                </slot>
                            </slot>
                        </td>

                        <!-- Row Actions Slot -->
                        <td v-if="$slots.rowActions" class="px-6 py-4 whitespace-nowrap text-end text-xs" @click.stop>
                            <slot name="rowActions" :item="item" :row="item" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / Pagination -->
        <div v-if="processedData.length > 0" class="p-4 border-t border-border-subtle bg-surface-card flex items-center justify-between text-xs text-text-muted">
            <span>
                {{ t('total', 'Total') }}: <strong class="text-text-main">{{ totalCount ?? pagination?.total ?? processedData.length }}</strong>
            </span>
            <div v-if="pagination && pagination.last_page && pagination.last_page > 1" class="flex items-center gap-2">
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-lg border border-border-subtle bg-surface-input hover:bg-surface-hover text-text-main disabled:opacity-40 transition-colors"
                    :disabled="pagination.current_page === 1"
                    @click="emit('page-change', (pagination.current_page || 1) - 1)"
                >
                    Prev
                </button>
                <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-lg border border-border-subtle bg-surface-input hover:bg-surface-hover text-text-main disabled:opacity-40 transition-colors"
                    :disabled="pagination.current_page === pagination.last_page"
                    @click="emit('page-change', (pagination.current_page || 1) + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
