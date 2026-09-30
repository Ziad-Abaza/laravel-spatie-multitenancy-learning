<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Shield, Lock, Mail, ArrowRight } from 'lucide-vue-next';

const { t } = useI18n();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/landlord/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout>
        <div class="py-16 sm:py-24 flex items-center justify-center px-4">
            <div class="w-full max-w-md bg-surface-card border border-border-subtle rounded-3xl p-8 sm:p-10 shadow-2xl backdrop-blur-xl">
                <div class="text-center mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-primary-500/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 mx-auto flex items-center justify-center mb-4">
                        <Shield class="w-6 h-6" />
                    </div>
                    <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('landlord_login', 'Landlord Administration') }}</h1>
                    <p class="text-xs text-text-muted mt-1">{{ t('landlord_login_sub', 'Enter platform credentials to access central console.') }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <FormField
                        v-model="form.email"
                        :label="t('email', 'Email Address')"
                        type="email"
                        :icon="Mail"
                        required
                        placeholder="admin@landlord.test"
                        :error="form.errors.email"
                    />

                    <FormField
                        v-model="form.password"
                        :label="t('password', 'Password')"
                        type="password"
                        :icon="Lock"
                        required
                        placeholder="••••••••"
                        :error="form.errors.password"
                    />

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 text-text-muted cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded bg-surface-input border-border-subtle text-primary-600 focus:ring-primary-500"
                            />
                            <span>{{ t('remember_me', 'Remember me') }}</span>
                        </label>
                    </div>

                    <BaseButton type="submit" :loading="form.processing" :icon="ArrowRight" class="w-full !py-3 shadow-lg shadow-primary-600/30">
                        {{ form.processing ? t('authenticating', 'Authenticating...') : t('sign_in', 'Sign In to Console') }}
                    </BaseButton>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
