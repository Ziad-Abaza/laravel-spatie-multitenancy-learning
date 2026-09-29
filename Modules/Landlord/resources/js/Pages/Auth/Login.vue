<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
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
                    <div class="w-12 h-12 rounded-2xl bg-primary-500/10 border border-primary-500/20 text-primary-400 mx-auto flex items-center justify-center mb-4">
                        <Shield class="w-6 h-6" />
                    </div>
                    <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('landlord_login', 'Landlord Administration') }}</h1>
                    <p class="text-xs text-text-muted mt-1">{{ t('landlord_login_sub', 'Enter platform credentials to access central console.') }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('email', 'Email Address') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                placeholder="admin@landlord.test"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all placeholder:text-text-subtle ps-10"
                            />
                            <Mail class="w-4 h-4 text-text-subtle absolute start-3.5 top-3" />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('password', 'Password') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all placeholder:text-text-subtle ps-10"
                            />
                            <Lock class="w-4 h-4 text-text-subtle absolute start-3.5 top-3" />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-rose-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 text-text-muted cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded bg-surface-bg border-border-subtle text-primary-600 focus:ring-primary-500"
                            />
                            <span>{{ t('remember_me', 'Remember me') }}</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-white font-semibold text-xs shadow-lg shadow-primary-600/30 flex items-center justify-center gap-2 transition-all cursor-pointer"
                    >
                        <span>{{ form.processing ? t('authenticating', 'Authenticating...') : t('sign_in', 'Sign In to Console') }}</span>
                        <ArrowRight class="w-4 h-4 rtl:rotate-180" />
                    </button>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
