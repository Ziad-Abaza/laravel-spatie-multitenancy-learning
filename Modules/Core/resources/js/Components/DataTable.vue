<script setup lang="ts" generic="T extends Record<string, any>">
import EmptyState from './EmptyState.vue';
import { useI18n } from '../Composables/useI18n';

/**
 * Lightweight read table for detail/panel contexts (subscriptions, users,
 * activity). For index/listing pages with toolbar, sorting, and pagination
 * use EnterpriseDataGrid instead.
 *
 * Cell overrides: #cell-{key} or #{key} with { row, value } bindings.
 */
withDefaults(
    defineProps<{
        columns: Array<{ key: string; label: string; align?: 'start' | 'center' | 'end' }>;
        rows: T[];
        emptyTitle?: string;
        emptyDescription?: string;
        compact?: boolean;
    }>(),
    {
        emptyTitle: '',
        emptyDescription: '',
        compact: false,
    }
);

const { t } = useI18n();
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-start text-xs">
            <thead>
                <tr class="border-b border-border-subtle text-text-muted uppercase tracking-wider">
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        scope="col"
                        class="py-2.5 px-3 text-start font-medium"
                        :class="{ 'text-end': col.align === 'end', 'text-center': col.align === 'center' }"
                    >
                        {{ col.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle">
                <tr v-for="(row, i) in rows" :key="(row.id as string | number) ?? i" class="hover:bg-surface-hover transition-colors">
                    <td
                        v-for="col in columns"
                        :key="col.key"
                        class="px-3 text-text-muted"
                        :class="[compact ? 'py-2' : 'py-3', { 'text-end': col.align === 'end', 'text-center': col.align === 'center' }]"
                    >
                        <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                            <slot :name="col.key" :row="row" :value="row[col.key]">
                                {{ row[col.key] ?? '—' }}
                            </slot>
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
        <EmptyState
            v-if="rows.length === 0"
            :title="emptyTitle || t('no_data', 'No records found')"
            :description="emptyDescription"
            class="!p-6 border-0 bg-transparent"
        />
    </div>
</template>
