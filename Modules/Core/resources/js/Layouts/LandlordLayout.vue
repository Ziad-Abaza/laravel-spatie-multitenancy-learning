<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Building2,
    CreditCard,
    Layers,
    Settings,
    Box,
    LogOut,
    Menu,
    X,
    Shield,
    ChevronLeft,
    ChevronRight,
    CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next';
import ThemeSwitcher from '../Components/ThemeSwitcher.vue';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import { useI18n } from '../Composables/useI18n';

const page = usePage();
const { t } = useI18n();

const isSidebarOpen = ref(false);
const isCollapsed = ref(
    typeof window !== 'undefined' ? localStorage.getItem('saas_sidebar_collapsed') === 'true' : false
);

function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('saas_sidebar_collapsed', String(isCollapsed.value));
    }
}

const branding = computed(() => (page.props as any).branding || {});
const appName = computed(() => branding.value.app_name || 'Landlord Central');
const tagline = computed(() => branding.value.tagline || 'Multi-Tenant Core');

const navItems = computed(() => [
    { name: t('dashboard', 'Dashboard'), href: '/landlord', icon: LayoutDashboard },
    { name: t('tenants', 'Tenants'), href: '/landlord/tenants', icon: Building2 },
    { name: t('plans', 'Plans'), href: '/landlord/plans', icon: Layers },
    { name: t('subscriptions', 'Subscriptions'), href: '/landlord/subscriptions', icon: CreditCard },
    { name: t('settings', 'Settings'), href: '/landlord/settings', icon: Settings },
    { name: t('modules', 'Modules'), href: '/landlord/modules', icon: Box },
]);

function isItemActive(href: string): boolean {
    const currentPath = page.url.split('?')[0].split('#')[0].replace(/\/+$/, '') || '/';
    const targetPath = href.replace(/\/+$/, '') || '/';

    if (currentPath === targetPath) {
        return true;
    }

    if (targetPath === '/landlord' || targetPath === '/dashboard' || targetPath === '') {
        return false;
    }

    return currentPath.startsWith(targetPath + '/');
}

function logout() {
    router.post('/landlord/logout');
}
</script>

<template>
    <div class="h-screen max-h-screen overflow-hidden flex flex-col md:flex-row bg-surface-bg text-text-main antialiased selection:bg-primary-500 selection:text-on-primary">
        <!-- Mobile Header -->
        <header class="md:hidden flex items-center justify-between p-4 bg-surface-header border-b border-border-subtle shrink-0 z-30">
            <div class="flex items-center gap-2.5 font-bold tracking-tight">
                <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-on-primary font-black text-sm shadow-xs">
                    <Shield class="w-4 h-4" />
                </div>
                <span class="truncate max-w-[180px] font-bold text-sm text-text-main">{{ appName }}</span>
            </div>
            <div class="flex items-center gap-2">
                <ThemeSwitcher />
                <LanguageSwitcher />
                <button
                    type="button"
                    class="p-2 text-text-muted hover:text-text-main rounded-lg hover:bg-surface-hover transition-colors"
                    :aria-label="t('toggle_navigation', 'Toggle navigation menu')"
                    @click="isSidebarOpen = !isSidebarOpen"
                >
                    <Menu v-if="!isSidebarOpen" class="w-5 h-5" />
                    <X v-else class="w-5 h-5" />
                </button>
            </div>
        </header>

        <!-- Mobile Drawer Backdrop -->
        <div
            v-if="isSidebarOpen"
            class="fixed inset-0 bg-scrim backdrop-blur-xs z-40 md:hidden"
            @click="isSidebarOpen = false"
        />

        <!-- Fixed Viewport Sidebar -->
        <aside
            class="fixed md:static inset-y-0 start-0 z-50 h-screen max-h-screen flex flex-col shrink-0 bg-surface-sidebar border-e border-border-subtle transition-all duration-200 overflow-hidden"
            :class="[
                isSidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full md:translate-x-0',
                isCollapsed ? 'md:w-20' : 'md:w-64'
            ]"
        >
            <!-- Logo / Brand Header -->
            <div
                class="p-4 border-b border-border-subtle flex items-center justify-between shrink-0 h-16"
                :class="isCollapsed ? 'md:flex-col md:justify-center md:gap-2 md:h-auto md:py-3 md:px-2' : ''"
            >
                <Link href="/landlord" class="flex items-center gap-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-secondary-600 flex items-center justify-center text-on-primary shadow-md shadow-primary-500/20 shrink-0">
                        <Shield class="w-5 h-5" />
                    </div>
                    <div v-if="!isCollapsed" class="overflow-hidden min-w-0">
                        <h1 class="text-sm font-bold text-text-main leading-tight truncate">{{ appName }}</h1>
                        <span class="text-[11px] text-primary-600 dark:text-primary-400 font-medium tracking-wide uppercase truncate block">{{ tagline }}</span>
                    </div>
                </Link>

                <!-- Desktop Collapse Button -->
                <button
                    type="button"
                    class="hidden md:flex p-1.5 rounded-lg text-text-muted hover:text-text-main hover:bg-surface-hover transition-colors shrink-0"
                    :title="isCollapsed ? t('expand_sidebar', 'Expand Sidebar') : t('collapse_sidebar', 'Collapse Sidebar')"
                    @click="toggleCollapse"
                >
                    <ChevronRight v-if="isCollapsed" class="w-4 h-4 rtl:rotate-180" />
                    <ChevronLeft v-else class="w-4 h-4 rtl:rotate-180" />
                </button>
            </div>

            <!-- Internal Scrollable Navigation Links -->
            <nav class="flex-1 min-h-0 p-3 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition-all group"
                    :class="[
                        isItemActive(item.href)
                            ? 'bg-primary-600 text-on-primary shadow-md shadow-primary-600/20 font-semibold'
                            : 'text-text-muted hover:text-text-main hover:bg-surface-hover',
                        isCollapsed ? 'justify-center px-2' : ''
                    ]"
                    :title="isCollapsed ? item.name : undefined"
                    @click="isSidebarOpen = false"
                >
                    <component :is="item.icon" class="w-4 h-4 shrink-0 stroke-[1.8]" />
                    <span v-if="!isCollapsed" class="truncate">{{ item.name }}</span>
                </Link>
            </nav>

            <!-- Bottom User Section -->
            <div class="p-3.5 border-t border-border-subtle bg-surface-card/40 shrink-0" :class="isCollapsed ? 'md:p-2' : ''">
                <div
                    class="flex items-center justify-between gap-2"
                    :class="isCollapsed ? 'md:flex-col md:justify-center' : ''"
                >
                    <div class="flex items-center gap-2.5 overflow-hidden min-w-0">
                        <div class="w-8 h-8 rounded-full bg-secondary-500/10 border border-secondary-500/20 flex items-center justify-center text-secondary-600 dark:text-secondary-400 font-bold text-xs uppercase shrink-0">
                            {{ page.props.auth?.user?.name ? page.props.auth.user.name.charAt(0) : 'A' }}
                        </div>
                        <div v-if="!isCollapsed" class="overflow-hidden min-w-0">
                            <p class="text-xs font-semibold text-text-main truncate">
                                {{ page.props.auth?.user?.name || 'Administrator' }}
                            </p>
                            <p class="text-[11px] text-text-muted truncate">
                                {{ page.props.auth?.user?.email || 'admin@landlord.test' }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="p-1.5 rounded-lg text-text-muted hover:text-danger-fg hover:bg-surface-hover transition-colors shrink-0"
                        :title="t('logout', 'Logout')"
                        @click="logout"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 h-screen max-h-screen min-w-0 flex flex-col overflow-hidden">
            <!-- Topbar (Desktop) -->
            <header class="hidden md:flex items-center justify-between px-8 py-3.5 bg-surface-header border-b border-border-subtle backdrop-blur-md shrink-0 z-30">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">
                        {{ t('landlord_portal', 'Landlord Administration') }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <ThemeSwitcher />
                    <LanguageSwitcher />
                </div>
            </header>

            <!-- Flash Notifications -->
            <div v-if="page.props.flash?.success" class="mx-6 mt-4 p-4 rounded-2xl bg-success/10 border border-success/20 text-success-fg text-xs flex items-center gap-2.5 shrink-0">
                <CheckCircle2 class="w-4 h-4 shrink-0" />
                <span class="font-medium">{{ page.props.flash.success }}</span>
            </div>
            <div v-if="page.props.flash?.error" class="mx-6 mt-4 p-4 rounded-2xl bg-danger/10 border border-danger/20 text-danger-fg text-xs flex items-center gap-2.5 shrink-0">
                <AlertCircle class="w-4 h-4 shrink-0" />
                <span class="font-medium">{{ page.props.flash.error }}</span>
            </div>

            <!-- Page Body: Independent Vertical Scroll -->
            <main class="flex-1 min-h-0 p-6 md:p-8 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
