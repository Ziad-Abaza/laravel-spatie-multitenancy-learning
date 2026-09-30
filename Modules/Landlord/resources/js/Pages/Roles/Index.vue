<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import IconButton from '@core/Components/IconButton.vue';
import FormModal from '@core/Components/FormModal.vue';
import FormField from '@core/Components/FormField.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import PermissionBundlePicker, { PermissionGroup } from '@core/Components/PermissionBundlePicker.vue';
import { useI18n } from '@core/Composables/useI18n';
import { ShieldCheck, Plus, Check, Edit2, Trash2, Lock } from 'lucide-vue-next';

interface RoleItem {
    id: number;
    name: string;
    is_system: boolean;
    users_count: number;
    permissions: string[];
}

const props = defineProps<{
    roles: RoleItem[];
    permissionGroups: PermissionGroup[];
}>();

const { t, trans } = useI18n();

const isFormModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const editingRole = ref<RoleItem | null>(null);
const deletingRole = ref<RoleItem | null>(null);

const form = useForm({
    name: '',
    permissions: [] as string[],
});

function openCreateModal() {
    editingRole.value = null;
    form.reset();
    isFormModalOpen.value = true;
}

function openEditModal(role: RoleItem) {
    editingRole.value = role;
    form.name = role.name;
    form.permissions = [...role.permissions];
    isFormModalOpen.value = true;
}

function submit() {
    if (editingRole.value) {
        form.put(`/landlord/roles/${editingRole.value.id}`, {
            onSuccess: () => (isFormModalOpen.value = false),
        });
    } else {
        form.post('/landlord/roles', {
            onSuccess: () => {
                isFormModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function openDeleteModal(role: RoleItem) {
    deletingRole.value = role;
    isDeleteModalOpen.value = true;
}

function confirmDelete() {
    if (!deletingRole.value) return;
    router.delete(`/landlord/roles/${deletingRole.value.id}`, {
        onSuccess: () => (isDeleteModalOpen.value = false),
    });
}
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <PageHeader
                :title="t('platform_roles', 'Platform Roles')"
                :subtitle="t('platform_roles_sub', 'Permission bundles controlling landlord administration capabilities.')"
            >
                <template #actions>
                    <BaseButton :icon="Plus" @click="openCreateModal">
                        {{ t('create_role', 'Create Role') }}
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
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="role.is_system"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-warning/10 text-warning-fg border border-warning/25 uppercase tracking-wide"
                                >
                                    <Lock class="w-3 h-3" />
                                    {{ t('system_role', 'System') }}
                                </span>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-surface-input text-text-main border border-border-subtle">
                                    {{ role.permissions.length }} {{ t('permissions', 'permissions') }}
                                </span>
                            </div>
                        </div>

                        <h2 class="text-lg font-bold text-text-main">{{ role.name }}</h2>
                        <p class="text-[11px] text-text-subtle mt-0.5">
                            {{ role.users_count }} {{ t('members', 'members') }}
                        </p>

                        <div class="mt-4 pt-4 border-t border-border-subtle">
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="perm in role.permissions"
                                    :key="perm"
                                    class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-md bg-surface-input text-text-main font-mono"
                                >
                                    <Check class="w-3 h-3 text-success-fg" />
                                    <span>{{ perm }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!role.is_system" class="flex items-center justify-end gap-1 mt-4 pt-4 border-t border-border-subtle">
                        <IconButton :icon="Edit2" :title="t('edit', 'Edit')" @click="openEditModal(role)" />
                        <IconButton
                            :icon="Trash2"
                            variant="danger"
                            :title="t('delete', 'Delete')"
                            :disabled="role.users_count > 0"
                            @click="openDeleteModal(role)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Role Modal -->
        <FormModal
            :is-open="isFormModalOpen"
            :title="editingRole ? t('edit_role', 'Edit Role') : t('create_new_role', 'Create Custom Role')"
            max-width="lg"
            :submit-text="editingRole ? t('save_changes', 'Save Changes') : t('create_role', 'Create Role')"
            :loading="form.processing"
            @close="isFormModalOpen = false"
            @submit="submit"
        >
            <FormField
                v-model="form.name"
                :label="t('role_name', 'Role Name')"
                size="sm"
                required
                :placeholder="t('role_name_example', 'e.g. Support Lead, Finance Reviewer')"
                :error="form.errors.name"
            />

            <div>
                <label class="block text-xs font-medium text-text-main mb-2">{{ t('assign_permissions', 'Assign Permissions') }}</label>
                <PermissionBundlePicker v-model="form.permissions" :groups="permissionGroups" />
                <p v-if="form.errors.permissions" class="mt-1.5 text-[11px] text-danger-fg">{{ form.errors.permissions }}</p>
            </div>
        </FormModal>

        <!-- Delete Role Confirm -->
        <ConfirmDialog
            :is-open="isDeleteModalOpen"
            :title="t('confirm_delete_role', 'Delete Role?')"
            :message="trans('confirm_delete_role_msg', { name: deletingRole?.name || '' })"
            :confirm-text="t('delete', 'Delete')"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="confirmDelete"
        />
    </LandlordLayout>
</template>
