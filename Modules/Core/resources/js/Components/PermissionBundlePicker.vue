<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@core/Composables/useI18n';
import { ShieldCheck, Eye, SlidersHorizontal } from 'lucide-vue-next';

export interface PermissionOption {
    key: string;
    classification: 'read' | 'write' | 'sensitive';
}

export interface PermissionGroup {
    key: string;
    label: string;
    permissions: PermissionOption[];
}

const props = defineProps<{
    groups: PermissionGroup[];
    modelValue: string[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const { t } = useI18n();

const selected = computed(() => new Set(props.modelValue));
const allKeys = computed(() => props.groups.flatMap((g) => g.permissions.map((p) => p.key)));
const readKeys = computed(() =>
    props.groups.flatMap((g) => g.permissions.filter((p) => p.classification === 'read').map((p) => p.key))
);

function setSelection(keys: string[]) {
    emit('update:modelValue', [...new Set(keys)]);
}

function isGroupFullySelected(group: PermissionGroup): boolean {
    return group.permissions.every((p) => selected.value.has(p.key));
}

function toggleGroup(group: PermissionGroup) {
    const next = new Set(selected.value);
    if (isGroupFullySelected(group)) {
        group.permissions.forEach((p) => next.delete(p.key));
    } else {
        group.permissions.forEach((p) => next.add(p.key));
    }
    emit('update:modelValue', [...next]);
}

function togglePermission(key: string) {
    const next = new Set(selected.value);
    next.has(key) ? next.delete(key) : next.add(key);
    emit('update:modelValue', [...next]);
}

const classBadge: Record<string, string> = {
    read: 'bg-success/10 text-success-fg border-success/20',
    write: 'bg-warning/10 text-warning-fg border-warning/20',
    sensitive: 'bg-danger/10 text-danger-fg border-danger/20',
};
</script>

<template>
    <div class="space-y-3">
        <!-- Presets -->
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-surface-input border border-border-subtle text-text-muted hover:text-text-main hover:border-primary-500/40 transition-colors"
                @click="setSelection(allKeys)"
            >
                <ShieldCheck class="w-3.5 h-3.5" />
                {{ t('preset_full_scope', 'Full Scope') }}
            </button>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-surface-input border border-border-subtle text-text-muted hover:text-text-main hover:border-primary-500/40 transition-colors"
                @click="setSelection(readKeys)"
            >
                <Eye class="w-3.5 h-3.5" />
                {{ t('preset_read_only', 'Read Only') }}
            </button>
            <span class="ms-auto inline-flex items-center gap-1.5 text-[11px] text-text-subtle">
                <SlidersHorizontal class="w-3.5 h-3.5" />
                {{ modelValue.length }} / {{ allKeys.length }}
            </span>
        </div>

        <!-- Bundle cards -->
        <div class="space-y-2 max-h-72 overflow-y-auto pe-1">
            <div
                v-for="group in groups"
                :key="group.key"
                class="rounded-xl border border-border-subtle bg-surface-input/50 overflow-hidden"
            >
                <button
                    type="button"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-text-main hover:bg-surface-hover transition-colors"
                    @click="toggleGroup(group)"
                >
                    <span>{{ group.label }}</span>
                    <span
                        class="text-[10px] px-2 py-0.5 rounded-full border"
                        :class="isGroupFullySelected(group)
                            ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400 border-primary-500/30'
                            : 'bg-surface-input text-text-subtle border-border-subtle'"
                    >
                        {{ group.permissions.filter((p) => selected.has(p.key)).length }}/{{ group.permissions.length }}
                    </span>
                </button>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 px-3 pb-3">
                    <label
                        v-for="perm in group.permissions"
                        :key="perm.key"
                        class="flex items-center gap-2 px-2 py-1.5 rounded-md cursor-pointer text-[11px] hover:bg-surface-hover transition-colors"
                        :class="selected.has(perm.key) ? 'text-text-main' : 'text-text-muted'"
                    >
                        <input
                            type="checkbox"
                            :checked="selected.has(perm.key)"
                            class="rounded bg-surface-hover border-border-subtle text-primary-600 focus:ring-primary-500"
                            @change="togglePermission(perm.key)"
                        />
                        <span class="font-mono flex-1">{{ perm.key }}</span>
                        <span class="text-[9px] px-1.5 py-px rounded border uppercase tracking-wide" :class="classBadge[perm.classification]">
                            {{ t(`class_${perm.classification}`, perm.classification) }}
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>
