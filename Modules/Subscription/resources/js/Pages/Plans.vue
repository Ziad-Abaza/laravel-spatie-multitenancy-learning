<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import Modal from '@core/Components/Modal.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Layers, Plus, Edit2, Trash2, Check, Users, HardDrive } from 'lucide-vue-next';

interface PlanItem {
    id: number;
    name: Record<string, string> | string | null;
    name_localized: string;
    slug: string;
    description?: Record<string, string> | string | null;
    description_localized?: string | null;
    price: number;
    currency: string;
    billing_interval: string;
    trial_days: number;
    is_active: boolean;
    limits: {
        max_users?: number;
        max_storage_mb?: number;
        features?: string[];
    };
    tenants_count: number;
}

const props = defineProps<{
    plans: PlanItem[];
}>();

const { t, locale } = useI18n();

function getPlanDescription(plan: PlanItem): string {
    if (plan.description_localized) {
        return plan.description_localized;
    }
    if (!plan.description) {
        return '';
    }
    if (typeof plan.description === 'object' && plan.description !== null) {
        const lang = locale.value || 'en';
        return plan.description[lang] || plan.description.en || plan.description.ar || Object.values(plan.description)[0] || '';
    }
    return String(plan.description);
}

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const editingPlan = ref<PlanItem | null>(null);
const deletingPlan = ref<PlanItem | null>(null);

const createForm = useForm({
    name_en: '',
    name_ar: '',
    slug: '',
    description_en: '',
    description_ar: '',
    price: 29,
    currency: 'USD',
    billing_interval: 'monthly',
    trial_days: 14,
    max_users: 10,
    max_storage_mb: 2048,
});

const editForm = useForm({
    name_en: '',
    name_ar: '',
    description_en: '',
    description_ar: '',
    price: 0,
    billing_interval: 'monthly',
    trial_days: 0,
    max_users: 5,
    max_storage_mb: 1024,
});

function openCreateModal() {
    createForm.reset();
    isCreateModalOpen.value = true;
}

function submitCreate() {
    createForm.post('/landlord/plans', {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
}

function openEditModal(plan: PlanItem) {
    editingPlan.value = plan;
    const nameObj = typeof plan.name === 'object' && plan.name !== null ? plan.name : { en: plan.name || '', ar: '' };
    const descObj = typeof plan.description === 'object' && plan.description !== null ? plan.description : { en: plan.description || '', ar: '' };

    editForm.name_en = nameObj.en || '';
    editForm.name_ar = nameObj.ar || '';
    editForm.description_en = descObj.en || '';
    editForm.description_ar = descObj.ar || '';
    editForm.price = plan.price;
    editForm.billing_interval = plan.billing_interval;
    editForm.trial_days = plan.trial_days;
    editForm.max_users = plan.limits?.max_users ?? 5;
    editForm.max_storage_mb = plan.limits?.max_storage_mb ?? 1024;

    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!editingPlan.value) return;
    editForm.put(`/landlord/plans/${editingPlan.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
}

function openDeleteModal(plan: PlanItem) {
    deletingPlan.value = plan;
    isDeleteModalOpen.value = true;
}

function confirmDelete() {
    if (!deletingPlan.value) return;
    router.delete(`/landlord/plans/${deletingPlan.value.id}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
        },
    });
}
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">{{ t('subscription_plans', 'Subscription Plans') }}</h1>
                    <p class="text-xs text-slate-400 mt-1">{{ t('subscription_plans_sub', 'Manage pricing tiers, quotas, translatable names (EN/AR), and limits.') }}</p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-md shadow-indigo-600/20 transition-all"
                >
                    <Plus class="w-4 h-4" />
                    <span>{{ t('create_plan', 'Create New Plan') }}</span>
                </button>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between hover:border-indigo-500/40 transition-all shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                {{ plan.slug }}
                            </span>
                            <span class="text-xs text-slate-400">
                                {{ plan.tenants_count }} {{ t('active_tenants', 'Tenants') }}
                            </span>
                        </div>

                        <h2 class="text-xl font-bold text-white mt-3">{{ plan.name_localized }}</h2>
                        <p class="text-xs text-slate-400 mt-1 min-h-[32px]">
                            {{ getPlanDescription(plan) }}
                        </p>

                        <div class="mt-6 flex items-baseline gap-1">
                            <span class="text-3xl font-black text-white">
                                <CurrencyCell :amount="plan.price" :currency="plan.currency" />
                            </span>
                            <span class="text-xs text-slate-400">/ {{ plan.billing_interval }}</span>
                        </div>

                        <div class="mt-6 pt-5 border-t border-slate-800 space-y-2.5 text-xs text-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-slate-400">
                                    <Users class="w-3.5 h-3.5" />
                                    <span>{{ t('max_users', 'Max Users') }}</span>
                                </span>
                                <strong class="text-white">{{ plan.limits?.max_users ?? 'Unlimited' }}</strong>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-slate-400">
                                    <HardDrive class="w-3.5 h-3.5" />
                                    <span>{{ t('storage', 'Storage Quota') }}</span>
                                </span>
                                <strong class="text-white">{{ plan.limits?.max_storage_mb ?? 1000 }} MB</strong>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">{{ t('trial_period', 'Trial Period') }}</span>
                                <strong class="text-white">{{ plan.trial_days }} {{ t('days', 'Days') }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="openEditModal(plan)"
                            class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                            :title="t('edit_plan', 'Edit Plan')"
                        >
                            <Edit2 class="w-4 h-4" />
                        </button>

                        <button
                            type="button"
                            @click="openDeleteModal(plan)"
                            class="p-2 rounded-lg text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-colors"
                            :title="t('delete_plan', 'Delete Plan')"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Plan Modal -->
        <Modal :is-open="isCreateModalOpen" :title="t('create_new_plan', 'Create Subscription Plan')" max-width="lg" @close="isCreateModalOpen = false">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Name (English)</label>
                        <input v-model="createForm.name_en" type="text" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Name (Arabic)</label>
                        <input v-model="createForm.name_ar" type="text" required dir="rtl" class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Slug</label>
                    <input v-model="createForm.slug" type="text" required placeholder="e.g. enterprise-plus" class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Price (USD)</label>
                        <input v-model.number="createForm.price" type="number" min="0" step="0.01" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Interval</label>
                        <select v-model="createForm.billing_interval" class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Trial Days</label>
                        <input v-model.number="createForm.trial_days" type="number" min="0" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Max Users</label>
                        <input v-model.number="createForm.max_users" type="number" min="1" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Max Storage (MB)</label>
                        <input v-model.number="createForm.max_storage_mb" type="number" min="100" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex justify-end gap-2">
                    <button type="button" @click="isCreateModalOpen = false" class="px-4 py-2 rounded-xl text-xs text-slate-400 hover:text-white">Cancel</button>
                    <button type="submit" :disabled="createForm.processing" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold">Save Plan</button>
                </div>
            </form>
        </Modal>

        <!-- Edit Plan Modal -->
        <Modal :is-open="isEditModalOpen" :title="t('edit_plan', 'Edit Subscription Plan')" max-width="lg" @close="isEditModalOpen = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Name (English)</label>
                        <input v-model="editForm.name_en" type="text" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Name (Arabic)</label>
                        <input v-model="editForm.name_ar" type="text" required dir="rtl" class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Price (USD)</label>
                        <input v-model.number="editForm.price" type="number" min="0" step="0.01" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Interval</label>
                        <select v-model="editForm.billing_interval" class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Trial Days</label>
                        <input v-model.number="editForm.trial_days" type="number" min="0" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Max Users</label>
                        <input v-model.number="editForm.max_users" type="number" min="1" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Max Storage (MB)</label>
                        <input v-model.number="editForm.max_storage_mb" type="number" min="100" required class="w-full px-3 py-2 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex justify-end gap-2">
                    <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 rounded-xl text-xs text-slate-400 hover:text-white">Cancel</button>
                    <button type="submit" :disabled="editForm.processing" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold">Update Plan</button>
                </div>
            </form>
        </Modal>

        <!-- Delete Modal -->
        <ConfirmDialog
            :is-open="isDeleteModalOpen"
            :title="t('confirm_delete_plan_title', 'Delete Subscription Plan?')"
            :message="t('confirm_delete_plan_msg', 'Are you sure? Plans with existing tenants cannot be deleted.')"
            :confirm-text="t('delete', 'Delete Plan')"
            confirm-button-class="bg-rose-600 hover:bg-rose-500 text-white"
            @close="isDeleteModalOpen = false"
            @confirm="confirmDelete"
        />
    </LandlordLayout>
</template>
