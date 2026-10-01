<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Mail, Lock, ArrowRight } from 'lucide-vue-next';

const props = defineProps<{
    token: string;
    email: string;
}>();

const page = usePage();
const { t } = useI18n();

const tenant = computed(() => page.props.tenant);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password');
}
</script>

<template>
    <GuestLayout>
        <div class="py-16 sm:py-24 flex items-center justify-center px-4">
            <div class="w-full max-w-md bg-surface-card border border-border-subtle rounded-3xl p-8 sm:p-10 shadow-2xl backdrop-blur-xl">
                <div class="text-center mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-primary-500/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 mx-auto flex items-center justify-center mb-4 font-bold text-lg">
                        {{ tenant?.name ? tenant.name.charAt(0) : 'W' }}
                    </div>
                    <h1 class="text-2xl font-bold text-text-main tracking-tight">
                        {{ t('choose_new_password', 'Choose a New Password') }}
                    </h1>
                    <p class="text-xs text-text-muted mt-1">
                        {{ t('reset_password_sub', 'Choose a new password for your workspace account.') }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <FormField
                        v-model="form.email"
                        :label="t('email', 'Email Address')"
                        type="email"
                        :icon="Mail"
                        required
                        :error="form.errors.email"
                    />

                    <FormField
                        v-model="form.password"
                        :label="t('new_password', 'New Password')"
                        type="password"
                        :icon="Lock"
                        required
                        placeholder="••••••••"
                        :error="form.errors.password"
                    />

                    <FormField
                        v-model="form.password_confirmation"
                        :label="t('confirm_password', 'Confirm New Password')"
                        type="password"
                        :icon="Lock"
                        required
                        placeholder="••••••••"
                        :error="form.errors.password_confirmation"
                    />

                    <BaseButton type="submit" :loading="form.processing" :icon="ArrowRight" class="w-full !py-3 shadow-lg shadow-primary-600/30">
                        {{ form.processing ? t('saving', 'Saving...') : t('choose_new_password', 'Choose a New Password') }}
                    </BaseButton>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
