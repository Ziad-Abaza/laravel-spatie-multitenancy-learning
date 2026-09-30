<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { User, Mail, Lock, ArrowRight, LogIn } from 'lucide-vue-next';

const page = usePage<{
    tenant?: {
        name?: string;
        domain?: string;
    } | null;
}>();
const { t } = useI18n();

const tenant = computed(() => page.props.tenant);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
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
                        {{ t('join_workspace', 'Join Workspace') }}
                    </h1>
                    <p class="text-xs text-text-muted mt-1 font-mono">
                        {{ tenant?.name || 'Workspace' }} ({{ tenant?.domain || 'localhost' }})
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <FormField
                        v-model="form.name"
                        :label="t('full_name', 'Full Name')"
                        :icon="User"
                        required
                        placeholder="Sarah Connor"
                        :error="form.errors.name"
                    />
                    <FormField
                        v-model="form.email"
                        :label="t('email', 'Email Address')"
                        type="email"
                        :icon="Mail"
                        required
                        placeholder="sarah@company.com"
                        :error="form.errors.email"
                    />
                    <FormField
                        v-model="form.password"
                        :label="t('password', 'Password')"
                        type="password"
                        :icon="Lock"
                        required
                        :min="8"
                        placeholder="••••••••"
                        :error="form.errors.password"
                    />
                    <FormField
                        v-model="form.password_confirmation"
                        :label="t('confirm_password', 'Confirm Password')"
                        type="password"
                        :icon="Lock"
                        required
                        placeholder="••••••••"
                        :error="form.errors.password_confirmation"
                    />

                    <BaseButton type="submit" :loading="form.processing" :icon="ArrowRight" class="w-full !py-3 shadow-lg shadow-primary-600/30 mt-2">
                        {{ form.processing ? t('registering', 'Creating account...') : t('create_account', 'Create Account') }}
                    </BaseButton>

                    <div class="pt-4 border-t border-border-subtle text-center">
                        <Link href="/login" class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 inline-flex items-center gap-1.5">
                            <LogIn class="w-3.5 h-3.5" />
                            <span>{{ t('already_have_account', 'Already have an account? Sign In') }}</span>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
