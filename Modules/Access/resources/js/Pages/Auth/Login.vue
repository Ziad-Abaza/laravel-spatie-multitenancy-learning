<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Building2, Lock, Mail, ArrowRight, UserPlus } from 'lucide-vue-next';

const page = usePage();
const { t } = useI18n();

const tenant = computed(() => page.props.tenant);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
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
                        {{ tenant?.name ? `${tenant.name}` : t('workspace_login', 'Workspace Login') }}
                    </h1>
                    <p class="text-xs text-slate-400 mt-1 font-mono">
                        {{ tenant?.domain || 'Sign in to your team workspace' }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('email', 'Email Address') }}</label>
                        <div class="relative">
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                placeholder="name@company.com"
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
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500 ps-10"
                            />
                            <Lock class="w-4 h-4 text-slate-500 absolute start-3.5 top-3" />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>{{ t('remember_me', 'Remember me') }}</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 transition-all"
                    >
                        <span>{{ form.processing ? t('authenticating', 'Signing in...') : t('sign_in', 'Sign In') }}</span>
                        <ArrowRight class="w-4 h-4 rtl:rotate-180" />
                    </button>

                    <div class="pt-4 border-t border-slate-800 text-center">
                        <Link href="/register" class="text-xs text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1.5">
                            <UserPlus class="w-3.5 h-3.5" />
                            <span>{{ t('dont_have_account', "Don't have an account? Register") }}</span>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
