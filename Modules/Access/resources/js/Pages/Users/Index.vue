<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import EnterpriseDataGrid, { ColumnDefinition } from '@core/Components/EnterpriseDataGrid.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import Modal from '@core/Components/Modal.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Users, UserPlus, Edit2, Trash2, Shield, AlertCircle } from 'lucide-vue-next';

interface UserItem {
    id: number;
    name: string;
    email: string;
    job_title: string;
    phone: string;
    status: string;
    role: string;
    avatar_url: string | null;
    created_at: string;
}

const props = defineProps<{
    users: UserItem[];
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

function handleSearch(val: string) {
    search.value = val;
    router.get(
        '/users',
        { search: val },
        { preserveState: true, replace: true }
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
                :total-count="users.length"
                :search-query="search"
                @update:search-query="handleSearch"
            >
                <template #toolbar-actions>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-text-muted">
                            {{ t('quota', 'Seats') }}: <strong class="text-text-main">{{ quota.current }} / {{ quota.limit ?? '∞' }}</strong>
                        </span>

                        <button
                            type="button"
                            :disabled="!quota.can_add"
                            @click="openAddModal"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 disabled:opacity-40 disabled:cursor-not-allowed text-on-primary font-semibold text-xs shadow-md shadow-primary-600/20 transition-all"
                        >
                            <UserPlus class="w-4 h-4" />
                            <span>{{ t('add_member', 'Add Member') }}</span>
                        </button>
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
                        <button
                            type="button"
                            @click="openEditModal(row)"
                            class="p-1.5 rounded-lg text-text-muted hover:text-text-main hover:bg-surface-hover transition-colors"
                            :title="t('edit', 'Edit')"
                        >
                            <Edit2 class="w-3.5 h-3.5" />
                        </button>

                        <button
                            v-if="row.role !== 'Owner'"
                            type="button"
                            @click="openDeleteModal(row)"
                            class="p-1.5 rounded-lg text-danger-fg hover:bg-danger/10 transition-colors"
                            :title="t('delete', 'Delete')"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </template>
            </EnterpriseDataGrid>
        </div>

        <!-- Add Member Modal -->
        <Modal :is-open="isAddModalOpen" :title="t('add_team_member', 'Add New Team Member')" @close="isAddModalOpen = false">
            <form @submit.prevent="submitAdd" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-text-main mb-1">{{ t('full_name', 'Full Name') }}</label>
                    <input v-model="addForm.name" type="text" required class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-text-main mb-1">{{ t('email', 'Email Address') }}</label>
                    <input v-model="addForm.email" type="email" required class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-text-main mb-1">{{ t('password', 'Initial Password') }}</label>
                    <input v-model="addForm.password" type="password" required minlength="8" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('role', 'Role') }}</label>
                        <select v-model="addForm.role" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500">
                            <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('job_title', 'Job Title') }}</label>
                        <input v-model="addForm.job_title" type="text" :placeholder="t('job_title_example', 'e.g. Engineer')" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                    </div>
                </div>

                <div class="pt-4 border-t border-border-subtle flex justify-end gap-2">
                    <button type="button" @click="isAddModalOpen = false" class="px-4 py-2 rounded-xl text-xs text-text-muted hover:text-text-main">{{ t('cancel', 'Cancel') }}</button>
                    <button type="submit" :disabled="addForm.processing" class="px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold">{{ t('add_member', 'Add Member') }}</button>
                </div>
            </form>
        </Modal>

        <!-- Edit Member Modal -->
        <Modal :is-open="isEditModalOpen" :title="t('edit_team_member', 'Edit Team Member')" @close="isEditModalOpen = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-text-main mb-1">{{ t('full_name', 'Full Name') }}</label>
                    <input v-model="editForm.name" type="text" required class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-text-main mb-1">{{ t('email', 'Email Address') }}</label>
                    <input v-model="editForm.email" type="email" required class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('role', 'Role') }}</label>
                        <select v-model="editForm.role" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500">
                            <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('status', 'Status') }}</label>
                        <select v-model="editForm.status" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500">
                            <option value="active">{{ t('active', 'Active') }}</option>
                            <option value="suspended">{{ t('suspended', 'Suspended') }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-text-main mb-1">{{ t('job_title', 'Job Title') }}</label>
                    <input v-model="editForm.job_title" type="text" class="w-full px-3 py-2 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500" />
                </div>

                <div class="pt-4 border-t border-border-subtle flex justify-end gap-2">
                    <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 rounded-xl text-xs text-text-muted hover:text-text-main">{{ t('cancel', 'Cancel') }}</button>
                    <button type="submit" :disabled="editForm.processing" class="px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold">{{ t('save_changes', 'Save Changes') }}</button>
                </div>
            </form>
        </Modal>

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
