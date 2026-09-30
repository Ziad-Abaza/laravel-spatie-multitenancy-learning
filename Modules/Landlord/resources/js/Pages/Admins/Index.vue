<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import EnterpriseDataGrid, { ColumnDefinition } from '@core/Components/EnterpriseDataGrid.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import FormModal from '@core/Components/FormModal.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import IconButton from '@core/Components/IconButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { UserPlus, Edit2, Trash2, Shield, ShieldCheck, Ban, RotateCcw, KeyRound } from 'lucide-vue-next';

interface AdminItem {
    id: number;
    name: string;
    email: string;
    status: string;
    roles: string[];
    can_edit: boolean;
    can_delete: boolean;
    created_at: string;
}

const props = defineProps<{
    admins: AdminItem[];
    assignableRoles: string[];
}>();

const { t, trans } = useI18n();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isRoleModalOpen = ref(false);
const isPasswordModalOpen = ref(false);
const isSuspendModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const target = ref<AdminItem | null>(null);

const createForm = useForm({ name: '', email: '', password: '', password_confirmation: '', role: props.assignableRoles[0] || '' });
const editForm = useForm({ name: '', email: '' });
const roleForm = useForm({ role: '' });
const passwordForm = useForm({ password: '', password_confirmation: '' });

const columns = computed<ColumnDefinition[]>(() => [
    { key: 'name', label: t('administrator', 'Administrator') },
    { key: 'roles', label: t('roles', 'Roles') },
    { key: 'status', label: t('status', 'Status') },
    { key: 'created_at', label: t('joined', 'Joined') },
    { key: 'actions', label: t('actions', 'Actions'), align: 'end' },
]);

function submitCreate() {
    createForm.post('/landlord/admins', {
        onSuccess: () => { isCreateModalOpen.value = false; createForm.reset(); },
    });
}

function openEditModal(admin: AdminItem) {
    target.value = admin;
    editForm.name = admin.name;
    editForm.email = admin.email;
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!target.value) return;
    editForm.put(`/landlord/admins/${target.value.id}`, {
        onSuccess: () => (isEditModalOpen.value = false),
    });
}

function openRoleModal(admin: AdminItem) {
    target.value = admin;
    roleForm.role = admin.roles[0] || '';
    isRoleModalOpen.value = true;
}

function submitRole() {
    if (!target.value) return;
    roleForm.put(`/landlord/admins/${target.value.id}/role`, {
        onSuccess: () => (isRoleModalOpen.value = false),
    });
}

function openPasswordModal(admin: AdminItem) {
    target.value = admin;
    passwordForm.reset();
    isPasswordModalOpen.value = true;
}

function submitPassword() {
    if (!target.value) return;
    passwordForm.put(`/landlord/admins/${target.value.id}/password`, {
        onSuccess: () => { isPasswordModalOpen.value = false; passwordForm.reset(); },
    });
}

function openSuspendModal(admin: AdminItem) {
    target.value = admin;
    isSuspendModalOpen.value = true;
}

function confirmSuspend() {
    if (!target.value) return;
    const action = target.value.status === 'active' ? 'suspend' : 'reactivate';
    router.post(`/landlord/admins/${target.value.id}/${action}`, {}, {
        onSuccess: () => (isSuspendModalOpen.value = false),
    });
}

function openDeleteModal(admin: AdminItem) {
    target.value = admin;
    isDeleteModalOpen.value = true;
}

function confirmDelete() {
    if (!target.value) return;
    router.delete(`/landlord/admins/${target.value.id}`, {
        onSuccess: () => (isDeleteModalOpen.value = false),
    });
}
</script>

<template>
    <LandlordLayout>
        <EnterpriseDataGrid
            :title="t('platform_admins', 'Platform Administrators')"
            :description="t('platform_admins_sub', 'Manage accounts that can operate the landlord control plane.')"
            :columns="columns"
            :rows="admins"
            :total-count="admins.length"
        >
            <template #toolbar-actions>
                <BaseButton :icon="UserPlus" @click="isCreateModalOpen = true">
                    {{ t('add_admin', 'Add Administrator') }}
                </BaseButton>
            </template>

            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-surface-input border border-border-subtle flex items-center justify-center font-bold text-xs text-primary-600 dark:text-primary-400">
                        {{ row.name.charAt(0) }}
                    </div>
                    <div>
                        <div class="font-semibold text-text-main">{{ row.name }}</div>
                        <div class="text-[11px] text-text-muted">{{ row.email }}</div>
                    </div>
                </div>
            </template>

            <template #cell-roles="{ row }">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20">
                    <Shield class="w-3 h-3" />
                    <span>{{ row.roles.join(', ') || '—' }}</span>
                </span>
            </template>

            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>

            <template #cell-actions="{ row }">
                <div class="flex items-center justify-end gap-1" @click.stop>
                    <template v-if="row.can_edit">
                        <IconButton :icon="Edit2" :title="t('edit', 'Edit')" @click="openEditModal(row)" />
                        <IconButton :icon="ShieldCheck" :title="t('change_role', 'Change Role')" @click="openRoleModal(row)" />
                        <IconButton :icon="KeyRound" :title="t('reset_password', 'Reset Password')" @click="openPasswordModal(row)" />
                        <IconButton
                            :icon="row.status === 'active' ? Ban : RotateCcw"
                            :title="row.status === 'active' ? t('suspend', 'Suspend') : t('reactivate', 'Reactivate')"
                            @click="openSuspendModal(row)"
                        />
                    </template>
                    <IconButton
                        v-if="row.can_delete"
                        :icon="Trash2"
                        variant="danger"
                        :title="t('delete', 'Delete')"
                        @click="openDeleteModal(row)"
                    />
                </div>
            </template>
        </EnterpriseDataGrid>

        <!-- Create Admin -->
        <FormModal
            :is-open="isCreateModalOpen"
            :title="t('add_platform_admin', 'Add Platform Administrator')"
            :submit-text="t('add_admin', 'Add Administrator')"
            :loading="createForm.processing"
            @close="isCreateModalOpen = false"
            @submit="submitCreate"
        >
            <FormField v-model="createForm.name" :label="t('full_name', 'Full Name')" size="sm" required :error="createForm.errors.name" />
            <FormField v-model="createForm.email" :label="t('email', 'Email Address')" type="email" size="sm" required :error="createForm.errors.email" />
            <div class="grid grid-cols-2 gap-3">
                <FormField v-model="createForm.password" :label="t('password', 'Password')" type="password" size="sm" required :error="createForm.errors.password" />
                <FormField v-model="createForm.password_confirmation" :label="t('confirm_password', 'Confirm Password')" type="password" size="sm" required :error="createForm.errors.password_confirmation" />
            </div>
            <FormField
                v-model="createForm.role"
                :label="t('role', 'Role')"
                type="select"
                size="sm"
                required
                :options="assignableRoles.map((r) => ({ value: r, label: r }))"
                :error="createForm.errors.role"
            />
        </FormModal>

        <!-- Edit Admin -->
        <FormModal
            :is-open="isEditModalOpen"
            :title="t('edit_admin', 'Edit Administrator')"
            :submit-text="t('save_changes', 'Save Changes')"
            :loading="editForm.processing"
            @close="isEditModalOpen = false"
            @submit="submitEdit"
        >
            <FormField v-model="editForm.name" :label="t('full_name', 'Full Name')" size="sm" required :error="editForm.errors.name" />
            <FormField v-model="editForm.email" :label="t('email', 'Email Address')" type="email" size="sm" required :error="editForm.errors.email" />
        </FormModal>

        <!-- Assign Role -->
        <FormModal
            :is-open="isRoleModalOpen"
            :title="t('change_role', 'Change Role')"
            :submit-text="t('save_changes', 'Save Changes')"
            :loading="roleForm.processing"
            @close="isRoleModalOpen = false"
            @submit="submitRole"
        >
            <FormField
                v-model="roleForm.role"
                :label="t('role', 'Role')"
                type="select"
                size="sm"
                required
                :options="assignableRoles.map((r) => ({ value: r, label: r }))"
                :error="roleForm.errors.role"
            />
        </FormModal>

        <!-- Reset Password -->
        <FormModal
            :is-open="isPasswordModalOpen"
            :title="t('reset_password', 'Reset Password')"
            :submit-text="t('reset_password', 'Reset Password')"
            :loading="passwordForm.processing"
            @close="isPasswordModalOpen = false"
            @submit="submitPassword"
        >
            <FormField v-model="passwordForm.password" :label="t('new_password', 'New Password')" type="password" size="sm" required :error="passwordForm.errors.password" />
            <FormField v-model="passwordForm.password_confirmation" :label="t('confirm_password', 'Confirm Password')" type="password" size="sm" required :error="passwordForm.errors.password_confirmation" />
        </FormModal>

        <!-- Suspend / Reactivate Confirm -->
        <ConfirmDialog
            :is-open="isSuspendModalOpen"
            :title="target?.status === 'active' ? t('confirm_suspend_admin', 'Suspend Administrator?') : t('confirm_reactivate_admin', 'Reactivate Administrator?')"
            :message="target?.status === 'active'
                ? trans('confirm_suspend_admin_msg', { name: target?.name || '' })
                : trans('confirm_reactivate_admin_msg', { name: target?.name || '' })"
            :confirm-text="target?.status === 'active' ? t('suspend', 'Suspend') : t('reactivate', 'Reactivate')"
            :variant="target?.status === 'active' ? 'warning' : 'primary'"
            @close="isSuspendModalOpen = false"
            @confirm="confirmSuspend"
        />

        <!-- Delete Confirm -->
        <ConfirmDialog
            :is-open="isDeleteModalOpen"
            :title="t('confirm_delete_admin', 'Delete Administrator?')"
            :message="trans('confirm_delete_admin_msg', { name: target?.name || '' })"
            :confirm-text="t('delete', 'Delete')"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="confirmDelete"
        />
    </LandlordLayout>
</template>
