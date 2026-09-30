<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import IconButton from '@core/Components/IconButton.vue';
import FormModal from '@core/Components/FormModal.vue';
import PlanFormFields from '../Components/PlanFormFields.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Plus, Users, HardDrive, Edit2, Trash2 } from 'lucide-vue-next';

interface Plan {
    id: number;
    name: string | Record<string, string>;
    name_localized: string;
    slug: string;
    description: string | Record<string, string> | null;
    price: number;
    currency: string;
    billing_interval: string;
    trial_days: number;
    tenants_count: number;
    limits: {
        max_users?: number;
        max_storage_mb?: number;
        features?: string[];
    };
}

defineProps<{
    plans: Plan[];
}>();

const { t, locale } = useI18n();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedPlan = ref<Plan | null>(null);

const createForm = useForm({
    name_en: '',
    name_ar: '',
    slug: '',
    description_en: '',
    description_ar: '',
    price: 0,
    billing_interval: 'monthly',
    trial_days: 14,
    max_users: 5,
    max_storage_mb: 1000,
});

const editForm = useForm({
    name_en: '',
    name_ar: '',
    description_en: '',
    description_ar: '',
    price: 0,
    billing_interval: 'monthly',
    trial_days: 14,
    max_users: 5,
    max_storage_mb: 1000,
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

function openEditModal(plan: Plan) {
    selectedPlan.value = plan;

    const names = typeof plan.name === 'object' ? plan.name : { en: plan.name, ar: '' };
    const descs = typeof plan.description === 'object' && plan.description ? plan.description : { en: plan.description || '', ar: '' };

    editForm.name_en = names.en || '';
    editForm.name_ar = names.ar || '';
    editForm.description_en = descs.en || '';
    editForm.description_ar = descs.ar || '';
    editForm.price = plan.price;
    editForm.billing_interval = plan.billing_interval;
    editForm.trial_days = plan.trial_days;
    editForm.max_users = plan.limits?.max_users ?? 5;
    editForm.max_storage_mb = plan.limits?.max_storage_mb ?? 1000;

    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!selectedPlan.value) return;
    editForm.put(`/landlord/plans/${selectedPlan.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
}

function openDeleteModal(plan: Plan) {
    selectedPlan.value = plan;
    isDeleteModalOpen.value = true;
}

function confirmDelete() {
    if (!selectedPlan.value) return;
    useForm({}).delete(`/landlord/plans/${selectedPlan.value.id}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
        },
    });
}

function getPlanDescription(plan: Plan): string {
    if (!plan.description) return '';
    if (typeof plan.description === 'object') {
        return (plan.description as Record<string, string>)[locale.value] || (plan.description as Record<string, string>)['en'] || '';
    }
    return plan.description;
}
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <PageHeader
                :title="t('subscription_plans', 'Subscription Plans')"
                :subtitle="t('subscription_plans_sub', 'Manage pricing tiers, quotas, translatable names (EN/AR), and limits.')"
            >
                <template #actions>
                    <BaseButton :icon="Plus" @click="openCreateModal">
                        {{ t('create_plan', 'Create New Plan') }}
                    </BaseButton>
                </template>
            </PageHeader>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="p-6 rounded-3xl bg-surface-card border border-border-subtle flex flex-col justify-between hover:border-primary-500/40 transition-all shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded-md bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20">
                                {{ plan.slug }}
                            </span>
                            <span class="text-xs text-text-muted">
                                {{ plan.tenants_count }} {{ t('active_tenants', 'Tenants') }}
                            </span>
                        </div>

                        <h2 class="text-xl font-bold text-text-main mt-3">{{ plan.name_localized }}</h2>
                        <p class="text-xs text-text-muted mt-1 min-h-[32px]">
                            {{ getPlanDescription(plan) }}
                        </p>

                        <div class="mt-6 flex items-baseline gap-1">
                            <span class="text-3xl font-black text-text-main">
                                <CurrencyCell :amount="plan.price" />
                            </span>
                            <span class="text-xs text-text-muted">{{ t(plan.billing_interval) }}</span>
                        </div>

                        <div class="mt-6 pt-5 border-t border-border-subtle space-y-2.5 text-xs text-text-muted">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-text-muted">
                                    <Users class="w-3.5 h-3.5" />
                                    <span>{{ t('max_users', 'Max Users') }}</span>
                                </span>
                                <strong class="text-text-main">{{ plan.limits?.max_users ?? 'Unlimited' }}</strong>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-text-muted">
                                    <HardDrive class="w-3.5 h-3.5" />
                                    <span>{{ t('storage', 'Storage Quota') }}</span>
                                </span>
                                <strong class="text-text-main">{{ plan.limits?.max_storage_mb ?? 1000 }} MB</strong>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-text-muted">{{ t('trial_period', 'Trial Period') }}</span>
                                <strong class="text-text-main">{{ plan.trial_days }} {{ t('days', 'Days') }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-border-subtle flex items-center justify-end gap-2">
                        <IconButton :icon="Edit2" :title="t('edit_plan', 'Edit Plan')" @click="openEditModal(plan)" />
                        <IconButton :icon="Trash2" variant="danger" :title="t('delete_plan', 'Delete Plan')" @click="openDeleteModal(plan)" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Plan Modal -->
        <FormModal
            :is-open="isCreateModalOpen"
            :title="t('create_new_plan', 'Create Subscription Plan')"
            max-width="lg"
            :submit-text="t('save', 'Save Plan')"
            :loading="createForm.processing"
            @close="isCreateModalOpen = false"
            @submit="submitCreate"
        >
            <PlanFormFields :form="createForm" with-slug />
        </FormModal>

        <!-- Edit Plan Modal -->
        <FormModal
            :is-open="isEditModalOpen"
            :title="t('edit_plan', 'Edit Subscription Plan')"
            max-width="lg"
            :submit-text="t('save', 'Update Plan')"
            :loading="editForm.processing"
            @close="isEditModalOpen = false"
            @submit="submitEdit"
        >
            <PlanFormFields :form="editForm" />
        </FormModal>

        <!-- Delete Modal -->
        <ConfirmDialog
            :is-open="isDeleteModalOpen"
            :title="t('confirm_delete_plan_title', 'Delete Subscription Plan?')"
            :message="t('confirm_delete_plan_msg', 'Are you sure? Plans with existing tenants cannot be deleted.')"
            :confirm-text="t('delete', 'Delete Plan')"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="confirmDelete"
        />
    </LandlordLayout>
</template>
