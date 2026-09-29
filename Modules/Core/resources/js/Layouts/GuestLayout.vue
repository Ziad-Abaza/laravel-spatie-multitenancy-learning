<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Shield } from 'lucide-vue-next';
import ThemeSwitcher from '../Components/ThemeSwitcher.vue';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import { useI18n } from '../Composables/useI18n';

const page = usePage();
const { t } = useI18n();

const tenant = computed(() => (page.props as any).tenant);
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col antialiased selection:bg-indigo-500 selection:text-white">
        <!-- Top Navigation -->
        <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white shadow-md shadow-indigo-500/25">
                        <Shield class="w-5 h-5" />
                    </div>
                    <span class="font-bold text-lg text-white tracking-tight">
                        {{ tenant?.name ? `${tenant.name}` : 'SaaS Cloud' }}
                    </span>
                </Link>

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <template v-if="!tenant">
                        <Link href="/" class="hover:text-white transition-colors">{{ t('overview', 'Overview') }}</Link>
                        <Link href="/pricing" class="hover:text-white transition-colors">{{ t('pricing', 'Pricing') }}</Link>
                        <Link href="/register-tenant" class="text-indigo-400 hover:text-indigo-300 transition-colors font-semibold">
                            {{ t('get_started', 'Get Started') }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="hover:text-white transition-colors">{{ t('sign_in', 'Sign In') }}</Link>
                        <Link href="/register" class="text-indigo-400 hover:text-indigo-300 transition-colors font-semibold">
                            {{ t('register', 'Register') }}
                        </Link>
                    </template>
                </nav>

                <div class="flex items-center gap-3">
                    <ThemeSwitcher />
                    <LanguageSwitcher />

                    <Link
                        :href="tenant ? '/login' : '/landlord/login'"
                        class="hidden sm:inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 transition-colors"
                    >
                        {{ tenant ? t('login', 'Login') : t('admin_login', 'Landlord Login') }}
                    </Link>

                    <Link
                        :href="tenant ? '/register' : '/register-tenant'"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white shadow-md shadow-indigo-600/20 transition-all"
                    >
                        {{ tenant ? t('join_workspace', 'Join') : t('get_started', 'Get Started') }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 flex flex-col">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-900 bg-slate-950 py-8 text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span>&copy; {{ new Date().getFullYear() }} SaaS Platform. {{ t('all_rights_reserved', 'All rights reserved.') }}</span>
                <span class="text-slate-600">Enterprise Multi-Database Tenancy</span>
            </div>
        </footer>
    </div>
</template>
