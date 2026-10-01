<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import EnterpriseDataGrid, { ColumnDefinition } from '@core/Components/EnterpriseDataGrid.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import FormModal from '@core/Components/FormModal.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import IconButton from '@core/Components/IconButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { UserPlus, Edit2, Trash2, Shield, AlertCircle } from 'lucide-vue-next';

interface UserItem {
    id: number;
    name: string;
    email: string;
    job_title: string;
    phone: string;
    status: string;
    role: string;
    avatar_url: string | null;
    can_edit: boolean;
    can_delete: boolean;
    created_at: string;
}

const props = defineProps<{
    users: UserItem[];
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    roles: string[];
    quota: {
        current: number;
        limit: number | null;
        can_add: boolean;
    };
    filters: {
        search?: string;
    };
}>();

const { t, trans } = useI18n();

const search = ref(props.filters.search || '');
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const editingUser = ref<UserItem | null>(null);
const deletingUser = ref<UserItem | null>(null);

const addForm = useForm({
    name: '',
    email: '',
    password: '',
    job_title: '',
    phone: '',
    role: props.roles[0] || 'Member',
});

const editForm = useForm({
    name: '',
    email: '',
    job_title: '',
    phone: '',
    status: 'active',
    role: 'Member',
});

const columns = computed<ColumnDefinition[]>(() => [
    { key: 'name', label: t('member', 'Member') },
    { key: 'role', label: t('role', 'Role') },
    { key: 'status', label: t('status', 'Status') },
    { key: 'job_title', label: t('job_title', 'Title') },
    { key: 'created_at', label: t('joined', 'Joined') },
    { key: 'actions', label: t('actions', 'Actions'), align: 'end' },
]);

// Partial reloads: only the table-related props are re-evaluated server-side.
const TABLE_PROPS = ['users', 'pagination', 'filters', 'quota'];

function handleSearch(val: string) {
    search.value = val;
    router.get(
        '/users',
        { search: val },
        { preserveState: true, replace: true, only: TABLE_PROPS }
    );
}

function handlePageChange(page: number) {
    router.get(
        '/users',
        { search: search.value || undefined, page },
        { preserveState: true, replace: true, only: TABLE_PROPS }
    );
}

function openAddModal() {
    addForm.reset();
    isAddModalOpen.value = true;
}

function submitAdd() {
    addForm.post('/users', {
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        },
    });
}

function openEditModal(user: UserItem) {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.job_title = user.job_title !== '-' ? user.job_title : '';
    editForm.phone = user.phone !== '-' ? user.phone : '';
    editForm.status = user.status;
    editForm.role = user.role;
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!editingUser.value) return;
    editForm.put(`/users/${editingUser.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
}

function openDeleteModal(user: UserItem) {
    deletingUser.value = user;
    isDeleteModalOpen.value = true;
}

function confirmDelete() {
    if (!deletingUser.value) return;
    router.delete(`/users/${deletingUser.value.id}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
        },
    });
}
</script>

<template>
    <TenantLayout>
        <div class="space-y-6">
            <!-- Quota Warning if limit reached -->
            <div
                v-if="!quota.can_add"
                class="p-4 rounded-2xl bg-warning/10 border border-warning/25 text-warning-fg text-xs flex items-center justify-between"
            >
                <div class="flex items-center gap-2.5">
                    <AlertCircle class="w-4 h-4 shrink-0 text-warning-fg" />
                    <span>
                        {{ t('quota_limit_reached_msg', 'Your workspace has reached its limit of') }}
                        <strong>{{ quota.limit }} {{ t('users', 'team members') }}</strong>.
                    </span>
                </div>
                <Link href="/subscription" class="font-bold underline hover:text-text-main">
                    {{ t('upgrade_plan', 'Upgrade Plan') }} &rarr;
                </Link>
            </div>

            <EnterpriseDataGrid
                :title="t('team_members', 'Team Directory')"
                :description="t('team_members_sub', 'Manage team members, roles, and isolated workspace access.')"
                :columns="columns"
                :rows="users"
                :pagination="pagination"
                :search-query="search"
                @search="handleSearch"
                @page-change="handlePageChange"
            >
                <template #toolbar-actions>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-text-muted">
                            {{ t('quota', 'Seats') }}: <strong class="text-text-main">{{ quota.current }} / {{ quota.limit ?? '∞' }}</strong>
                        </span>
                        <BaseButton :icon="UserPlus" :disabled="!quota.can_add" @click="openAddModal">
                            {{ t('add_member', 'Add Member') }}
                        </BaseButton>
                    </div>
                </template>

                <!-- Member Name & Email -->
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

                <!-- Role Badge -->
                <template #cell-role="{ row }">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20">
                        <Shield class="w-3 h-3" />
                        <span>{{ row.role }}</span>
                    </span>
                </template>

                <!-- Status Badge -->
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <!-- Actions -->
                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1" @click.stop>
                        <IconButton v-if="row.can_edit" :icon="Edit2" :title="t('edit', 'Edit')" @click="openEditModal(row)" />
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
        </div>

        <!-- Add Member Modal -->
        <FormModal
            :is-open="isAddModalOpen"
            :title="t('add_team_member', 'Add New Team Member')"
            :submit-text="t('add_member', 'Add Member')"
            :loading="addForm.processing"
            @close="isAddModalOpen = false"
            @submit="submitAdd"
        >
            <FormField v-model="addForm.name" :label="t('full_name', 'Full Name')" size="sm" required :error="addForm.errors.name" />
            <FormField v-model="addForm.email" :label="t('email', 'Email Address')" type="email" size="sm" required :error="addForm.errors.email" />
            <FormField v-model="addForm.password" :label="t('password', 'Initial Password')" type="password" size="sm" required :min="8" :error="addForm.errors.password" />
            <div class="grid grid-cols-2 gap-3">
                <FormField
                    v-model="addForm.role"
                    :label="t('role', 'Role')"
                    type="select"
                    size="sm"
                    :options="roles.map((r) => ({ value: r, label: r }))"
                    :error="addForm.errors.role"
                />
                <FormField v-model="addForm.job_title" :label="t('job_title', 'Job Title')" size="sm" :placeholder="t('job_title_example', 'e.g. Engineer')" :error="addForm.errors.job_title" />
            </div>
        </FormModal>

        <!-- Edit Member Modal -->
        <FormModal
            :is-open="isEditModalOpen"
            :title="t('edit_team_member', 'Edit Team Member')"
            :submit-text="t('save_changes', 'Save Changes')"
            :loading="editForm.processing"
            @close="isEditModalOpen = false"
            @submit="submitEdit"
        >
            <FormField v-model="editForm.name" :label="t('full_name', 'Full Name')" size="sm" required :error="editForm.errors.name" />
            <FormField v-model="editForm.email" :label="t('email', 'Email Address')" type="email" size="sm" required :error="editForm.errors.email" />
            <div class="grid grid-cols-2 gap-3">
                <FormField
                    v-model="editForm.role"
                    :label="t('role', 'Role')"
                    type="select"
                    size="sm"
                    :options="roles.map((r) => ({ value: r, label: r }))"
                    :error="editForm.errors.role"
                />
                <FormField
                    v-model="editForm.status"
                    :label="t('status', 'Status')"
                    type="select"
                    size="sm"
                    :options="{ active: t('active', 'Active'), suspended: t('suspended', 'Suspended') }"
                    :error="editForm.errors.status"
                />
            </div>
            <FormField v-model="editForm.job_title" :label="t('job_title', 'Job Title')" size="sm" :error="editForm.errors.job_title" />
        </FormModal>

        <!-- Delete Member Confirm -->
        <ConfirmDialog
            :is-open="isDeleteModalOpen"
            :title="t('confirm_remove_member', 'Remove Team Member?')"
            :message="trans('confirm_remove_member_msg', { name: deletingUser?.name || '' })"
            :confirm-text="t('remove', 'Remove Member')"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="confirmDelete"
        />
    </TenantLayout>
</template>
