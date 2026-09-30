<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import Panel from '@core/Components/Panel.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { User, Lock, Save } from 'lucide-vue-next';

interface ProfileData {
    id: number;
    name: string;
    email: string;
    job_title: string;
    phone: string;
}

const props = defineProps<{
    user: ProfileData;
}>();

const { t } = useI18n();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    job_title: props.user.job_title,
    phone: props.user.phone,
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put('/profile', {
        onSuccess: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
}
</script>

<template>
    <TenantLayout>
        <div class="space-y-6 max-w-3xl mx-auto">
            <PageHeader
                :title="t('my_profile', 'My Profile')"
                :subtitle="t('my_profile_sub', 'Manage personal information, role details, and security credentials.')"
            />

            <Panel :title="t('personal_information', 'Personal Information')" :icon="User">
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="space-y-4">
                        <FormField v-model="form.name" :label="t('full_name', 'Full Name')" required :error="form.errors.name" />
                        <FormField v-model="form.email" :label="t('email', 'Email Address')" type="email" required :error="form.errors.email" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormField v-model="form.job_title" :label="t('job_title', 'Job Title')" :error="form.errors.job_title" />
                            <FormField v-model="form.phone" :label="t('phone', 'Phone Number')" :error="form.errors.phone" />
                        </div>
                    </div>

                    <div class="space-y-4 pt-6 border-t border-border-subtle">
                        <h2 class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider flex items-center gap-2">
                            <Lock class="w-4 h-4" />
                            <span>{{ t('update_password', 'Update Password') }}</span>
                        </h2>
                        <FormField
                            v-model="form.current_password"
                            :label="t('current_password', 'Current Password')"
                            type="password"
                            :placeholder="t('leave_blank_password', 'Leave blank to keep current password')"
                            :error="form.errors.current_password"
                        />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormField
                                v-model="form.password"
                                :label="t('new_password', 'New Password')"
                                type="password"
                                :min="8"
                                placeholder="••••••••"
                                :error="form.errors.password"
                            />
                            <FormField
                                v-model="form.password_confirmation"
                                :label="t('confirm_password', 'Confirm New Password')"
                                type="password"
                                placeholder="••••••••"
                                :error="form.errors.password_confirmation"
                            />
                        </div>
                    </div>

                    <div class="pt-6 border-t border-border-subtle flex justify-end">
                        <BaseButton type="submit" :icon="Save" :loading="form.processing">
                            {{ form.processing ? t('saving', 'Saving...') : t('save_changes', 'Save Profile') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>
        </div>
    </TenantLayout>
</template>
