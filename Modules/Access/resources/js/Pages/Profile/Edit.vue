<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import { User, Lock, Mail, Phone, Briefcase, Save } from 'lucide-vue-next';

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
            <div>
                <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('my_profile', 'My Profile') }}</h1>
                <p class="text-xs text-text-muted mt-1">{{ t('my_profile_sub', 'Manage personal information, role details, and security credentials.') }}</p>
            </div>

            <form @submit.prevent="submit" class="bg-surface-card border border-border-subtle rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
                <!-- Profile Information -->
                <div class="space-y-4">
                    <h2 class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider flex items-center gap-2">
                        <User class="w-4 h-4" />
                        <span>{{ t('personal_information', 'Personal Information') }}</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('full_name', 'Full Name') }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-danger-fg">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('email', 'Email Address') }}</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-danger-fg">{{ form.errors.email }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('job_title', 'Job Title') }}</label>
                            <input
                                v-model="form.job_title"
                                type="text"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('phone', 'Phone Number') }}</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Password Update -->
                <div class="space-y-4 pt-6 border-t border-border-subtle">
                    <h2 class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider flex items-center gap-2">
                        <Lock class="w-4 h-4" />
                        <span>{{ t('update_password', 'Update Password') }}</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('current_password', 'Current Password') }}</label>
                        <input
                            v-model="form.current_password"
                            type="password"
                            :placeholder="t('leave_blank_password', 'Leave blank to keep current password')"
                            class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.current_password" class="mt-1 text-xs text-danger-fg">{{ form.errors.current_password }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('new_password', 'New Password') }}</label>
                            <input
                                v-model="form.password"
                                type="password"
                                minlength="8"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-danger-fg">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('confirm_password', 'Confirm New Password') }}</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                            />
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-border-subtle flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-on-primary font-semibold text-xs shadow-md shadow-primary-600/25 flex items-center gap-2 transition-all"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ form.processing ? t('saving', 'Saving...') : t('save_changes', 'Save Profile') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
