<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import { User, Mail, Lock, ArrowRight, LogIn } from 'lucide-vue-next';

const page = usePage();
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
            <div class="w-full max-w-md bg-slate-900/80 border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-2xl backdrop-blur-xl">
                <div class="text-center mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 mx-auto flex items-center justify-center mb-4 font-bold text-lg">
                        {{ tenant?.name ? tenant.name.charAt(0) : 'W' }}
                    </div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">
                        {{ t('join_workspace', 'Join Workspace') }}
                    </h1>
                    <p class="text-xs text-slate-400 mt-1 font-mono">
                        {{ tenant?.name || 'Workspace' }} ({{ tenant?.domain || 'localhost' }})
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('full_name', 'Full Name') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                placeholder="Sarah Connor"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500 ps-10"
                            />
                            <User class="w-4 h-4 text-slate-500 absolute start-3.5 top-3" />
                        </div>
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('email', 'Email Address') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="sarah@company.com"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500 ps-10"
                            />
                            <Mail class="w-4 h-4 text-slate-500 absolute start-3.5 top-3" />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-400">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('password', 'Password') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500 ps-10"
                            />
                            <Lock class="w-4 h-4 text-slate-500 absolute start-3.5 top-3" />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('confirm_password', 'Confirm Password') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500 ps-10"
                            />
                            <Lock class="w-4 h-4 text-slate-500 absolute start-3.5 top-3" />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 transition-all mt-2"
                    >
                        <span>{{ form.processing ? t('registering', 'Creating account...') : t('create_account', 'Create Account') }}</span>
                        <ArrowRight class="w-4 h-4 rtl:rotate-180" />
                    </button>

                    <div class="pt-4 border-t border-slate-800 text-center">
                        <Link href="/login" class="text-xs text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1.5">
                            <LogIn class="w-3.5 h-3.5" />
                            <span>{{ t('already_have_account', 'Already have an account? Sign In') }}</span>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
