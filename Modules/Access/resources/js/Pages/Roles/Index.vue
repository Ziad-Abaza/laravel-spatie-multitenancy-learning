<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import FormModal from '@core/Components/FormModal.vue';
import FormField from '@core/Components/FormField.vue';
import { useI18n } from '@core/Composables/useI18n';
import { ShieldCheck, Plus, Check } from 'lucide-vue-next';

interface RoleItem {
    id: number;
    name: string;
    permissions_count: number;
    permissions: string[];
}

const props = defineProps<{
    roles: RoleItem[];
    permissions: string[];
}>();

const { t } = useI18n();

const isCreateModalOpen = ref(false);

const form = useForm({
    name: '',
    permissions: [] as string[],
});

function openCreateModal() {
    form.reset();
    isCreateModalOpen.value = true;
}

function submit() {
    form.post('/roles', {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <TenantLayout>
        <div class="space-y-6">
            <PageHeader
                :title="t('roles_permissions', 'Roles & Permissions')"
                :subtitle="t('roles_permissions_sub', 'Role-based access control inside current tenant database using Spatie Permission.')"
            >
                <template #actions>
                    <BaseButton :icon="Plus" @click="openCreateModal">
                        {{ t('create_role', 'Create New Role') }}
                    </BaseButton>
                </template>
            </PageHeader>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="p-6 rounded-3xl bg-surface-card border border-border-subtle flex flex-col justify-between hover:border-primary-500/40 transition-all shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-primary-600/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold">
                                <ShieldCheck class="w-5 h-5" />
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-surface-input text-text-main border border-border-subtle">
                                {{ role.permissions_count }} {{ t('permissions', 'permissions') }}
                            </span>
                        </div>

                        <h2 class="text-lg font-bold text-text-main">{{ role.name }}</h2>

                        <div class="mt-4 pt-4 border-t border-border-subtle">
                            <span class="text-xs font-semibold text-text-muted block mb-2">{{ t('assigned_permissions', 'Assigned Capabilities') }}:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="perm in role.permissions"
                                    :key="perm"
                                    class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-md bg-surface-input text-text-main font-mono"
                                >
                                    <Check class="w-3 h-3 text-success-fg" />
                                    <span>{{ perm }}</span>
                                </span>
                                <span v-if="role.permissions.length === 0" class="text-xs text-text-subtle italic">
                                    {{ t('full_access', 'Full administrative authority') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Role Modal -->
        <FormModal
            :is-open="isCreateModalOpen"
            :title="t('create_new_role', 'Create Custom Role')"
            max-width="lg"
            :submit-text="t('create_role', 'Create Role')"
            :loading="form.processing"
            @close="isCreateModalOpen = false"
            @submit="submit"
        >
            <FormField
                v-model="form.name"
                :label="t('role_name', 'Role Name')"
                size="sm"
                required
                :placeholder="t('role_name_example', 'e.g. Editor, Financial Officer')"
                :error="form.errors.name"
            />

            <div>
                <label class="block text-xs font-medium text-text-main mb-2">{{ t('assign_permissions', 'Assign Permissions') }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-1">
                    <label
                        v-for="perm in permissions"
                        :key="perm"
                        class="flex items-center gap-2 p-2 rounded-lg bg-surface-input border border-border-subtle cursor-pointer text-xs text-text-muted hover:text-text-main"
                    >
                        <input
                            type="checkbox"
                            :value="perm"
                            v-model="form.permissions"
                            class="rounded bg-surface-hover border-border-subtle text-primary-600 focus:ring-primary-500"
                        />
                        <span class="font-mono text-[11px]">{{ perm }}</span>
                    </label>
                </div>
            </div>
        </FormModal>
    </TenantLayout>
</template>
