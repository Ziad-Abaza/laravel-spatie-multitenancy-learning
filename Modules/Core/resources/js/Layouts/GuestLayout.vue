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
const branding = computed(() => (page.props as any).branding || {});
const system = computed(() => (page.props as any).system || {});
const allowRegistration = computed(() => system.value.allow_registration ?? true);

const appName = computed(() => {
    if (tenant.value?.name) return tenant.value.name;
    return branding.value.app_name || 'SaaS Platform';
});
</script>

<template>
    <div class="min-h-screen bg-surface-bg text-text-main flex flex-col antialiased selection:bg-primary-500 selection:text-on-primary transition-colors duration-150">
        <!-- Top Navigation -->
        <header class="border-b border-border-subtle bg-surface-header/80 backdrop-blur-md sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-secondary-600 flex items-center justify-center text-on-primary shadow-md shadow-primary-500/25">
                        <Shield class="w-5 h-5" />
                    </div>
                    <span class="font-bold text-lg text-text-main tracking-tight">
                        {{ appName }}
                    </span>
                </Link>

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-text-muted">
                    <template v-if="!tenant">
                        <Link href="/" class="hover:text-text-main transition-colors">{{ t('overview', 'Overview') }}</Link>
                        <Link href="/pricing" class="hover:text-text-main transition-colors">{{ t('pricing', 'Pricing') }}</Link>
                        <Link v-if="allowRegistration" href="/register-tenant" class="text-primary-600 dark:text-primary-400 hover:underline transition-colors font-semibold">
                            {{ t('get_started', 'Get Started') }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="hover:text-text-main transition-colors">{{ t('sign_in', 'Sign In') }}</Link>
                        <Link v-if="allowRegistration" href="/register" class="text-primary-600 dark:text-primary-400 hover:underline transition-colors font-semibold">
                            {{ t('register', 'Register') }}
                        </Link>
                    </template>
                </nav>

                <div class="flex items-center gap-3">
                    <ThemeSwitcher />
                    <LanguageSwitcher />

                    <Link
                        :href="tenant ? '/login' : '/landlord/login'"
                        class="hidden sm:inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-text-muted hover:text-text-main hover:bg-surface-hover transition-colors"
                    >
                        {{ tenant ? t('login', 'Login') : t('admin_login', 'Landlord Login') }}
                    </Link>

                    <Link
                        v-if="allowRegistration"
                        :href="tenant ? '/register' : '/register-tenant'"
                        class="inline-flex items-center px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-xs font-semibold text-on-primary shadow-md shadow-primary-600/20 transition-all"
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
        <footer class="border-t border-border-subtle bg-surface-card py-8 text-center text-xs text-text-muted">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span>&copy; {{ new Date().getFullYear() }} {{ appName }}. {{ t('all_rights_reserved', 'All rights reserved.') }}</span>
                <span class="text-text-subtle">{{ branding.tagline || 'Enterprise Multi-Database Tenancy' }}</span>
            </div>
        </footer>
    </div>
</template>
