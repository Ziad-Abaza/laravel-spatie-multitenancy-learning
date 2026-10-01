<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Mail, ArrowRight, ArrowLeft } from 'lucide-vue-next';

const page = usePage();
const { t } = useI18n();

const tenant = computed(() => page.props.tenant);
const status = computed(() => page.props.status as string | undefined);

const form = useForm({
    email: '',
});

function submit() {
    form.post('/forgot-password');
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
                        {{ t('forgot_password', 'Forgot Password') }}
                    </h1>
                    <p class="text-xs text-text-muted mt-1">
                        {{ t('forgot_password_sub', 'Enter your email and we will send you a reset link.') }}
                    </p>
                </div>

                <div v-if="status" class="mb-4 rounded-xl border border-primary-500/30 bg-primary-500/10 px-4 py-3 text-xs text-primary-600 dark:text-primary-300">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <FormField
                        v-model="form.email"
                        :label="t('email', 'Email Address')"
                        type="email"
                        :icon="Mail"
                        required
                        placeholder="name@company.com"
                        :error="form.errors.email"
                    />

                    <BaseButton type="submit" :loading="form.processing" :icon="ArrowRight" class="w-full !py-3 shadow-lg shadow-primary-600/30">
                        {{ form.processing ? t('sending', 'Sending...') : t('send_reset_link', 'Send Reset Link') }}
                    </BaseButton>

                    <div class="pt-4 border-t border-border-subtle text-center">
                        <Link href="/login" class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 inline-flex items-center gap-1.5">
                            <ArrowLeft class="w-3.5 h-3.5" />
                            <span>{{ t('back_to_login', 'Back to Sign In') }}</span>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
