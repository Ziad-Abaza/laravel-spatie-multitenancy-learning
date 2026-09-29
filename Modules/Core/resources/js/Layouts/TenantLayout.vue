<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Users,
    ShieldCheck,
    CreditCard,
    Settings,
    User as UserIcon,
    LogOut,
    Menu,
    X,
    Building2,
    ChevronLeft,
    ChevronRight,
    CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next';
import ThemeSwitcher from '../Components/ThemeSwitcher.vue';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import StatusBadge from '../Components/StatusBadge.vue';
import { useI18n } from '../Composables/useI18n';

const page = usePage();
const { t } = useI18n();

const isSidebarOpen = ref(false);
const isCollapsed = ref(
    typeof window !== 'undefined' ? localStorage.getItem('saas_tenant_sidebar_collapsed') === 'true' : false
);

function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('saas_tenant_sidebar_collapsed', String(isCollapsed.value));
    }
}

const tenant = computed(() => (page.props as any).tenant);
const authUser = computed(() => (page.props as any).auth?.user);
const userRoles = computed<string[]>(() => authUser.value?.roles || []);
const userPermissions = computed<string[]>(() => authUser.value?.permissions || []);

const isOwnerOrAdmin = computed(() => {
    return userRoles.value.includes('Owner') ||
        userRoles.value.includes('Admin') ||
        userRoles.value.includes('Super Admin') ||
        Boolean(authUser.value?.is_landlord);
});

function hasPermissionOrRole(requiredPerm: string): boolean {
    if (isOwnerOrAdmin.value) return true;
    return userPermissions.value.includes(requiredPerm);
}

const navItems = computed(() => {
    const items = [
        { name: t('dashboard', 'Dashboard'), href: '/dashboard', icon: LayoutDashboard, visible: true },
        { name: t('team', 'Team'), href: '/users', icon: Users, visible: true },
        { name: t('roles', 'Roles & Permissions'), href: '/roles', icon: ShieldCheck, visible: hasPermissionOrRole('roles.view') },
        { name: t('subscriptions', 'Subscription & Quotas'), href: '/subscription', icon: CreditCard, visible: isOwnerOrAdmin.value },
        { name: t('settings', 'Workspace Settings'), href: '/settings', icon: Settings, visible: isOwnerOrAdmin.value },
        { name: t('profile', 'Profile'), href: '/profile', icon: UserIcon, visible: true },
    ];

    return items.filter(item => item.visible);
});

function isItemActive(href: string): boolean {
    const currentPath = page.url.split('?')[0].split('#')[0].replace(/\/+$/, '') || '/';
    const targetPath = href.replace(/\/+$/, '') || '/';

    if (currentPath === targetPath) {
        return true;
    }

    if (targetPath === '/dashboard' || targetPath === '/landlord' || targetPath === '') {
        return false;
    }

    return currentPath.startsWith(targetPath + '/');
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="h-screen max-h-screen overflow-hidden flex flex-col md:flex-row bg-surface-bg text-text-main antialiased selection:bg-primary-500 selection:text-white">
        <!-- Mobile Header -->
        <header class="md:hidden flex items-center justify-between p-4 bg-surface-header border-b border-border-subtle shrink-0 z-30">
            <div class="flex items-center gap-2.5 font-bold tracking-tight">
                <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white font-black text-sm shadow-xs">
                    {{ tenant?.name ? tenant.name.charAt(0).toUpperCase() : 'W' }}
                </div>
                <span class="truncate max-w-[170px] font-bold text-sm text-text-main">{{ tenant?.name || 'Workspace' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <ThemeSwitcher />
                <LanguageSwitcher />
                <button
                    type="button"
                    class="p-2 text-text-muted hover:text-text-main rounded-lg hover:bg-surface-hover transition-colors"
                    aria-label="Toggle navigation menu"
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
            class="fixed inset-0 bg-black/60 backdrop-blur-xs z-40 md:hidden"
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
            <!-- Tenant Workspace Header -->
            <div class="p-4 border-b border-border-subtle flex items-center justify-between shrink-0 h-16">
                <div class="flex items-center gap-3 overflow-hidden min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white shadow-md shadow-primary-500/20 font-bold text-sm shrink-0">
                        {{ tenant?.name ? tenant.name.charAt(0).toUpperCase() : 'W' }}
                    </div>
                    <div v-if="!isCollapsed" class="overflow-hidden min-w-0 flex-1">
                        <h1 class="text-sm font-bold text-text-main leading-tight truncate">
                            {{ tenant?.name || 'Workspace' }}
                        </h1>
                        <div class="mt-0.5 flex items-center gap-1.5 overflow-hidden">
                            <StatusBadge v-if="tenant?.status" :status="tenant.status" size="sm" />
                            <span v-if="tenant?.plan?.name" class="text-[10px] text-text-muted font-medium truncate">
                                {{ tenant.plan.name }}
                            </span>
                        </div>
                    </div>
                </div>

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
                            ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20 font-semibold'
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

            <!-- Bottom User Profile Section -->
            <div class="p-3.5 border-t border-border-subtle bg-surface-card/40 shrink-0">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5 overflow-hidden min-w-0">
                        <div class="w-8 h-8 rounded-full bg-primary-500/10 border border-primary-500/20 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-xs uppercase shrink-0">
                            {{ authUser?.name ? authUser.name.charAt(0) : 'U' }}
                        </div>
                        <div v-if="!isCollapsed" class="overflow-hidden min-w-0">
                            <p class="text-xs font-semibold text-text-main truncate">
                                {{ authUser?.name || 'User' }}
                            </p>
                            <p class="text-[11px] text-text-muted truncate">
                                {{ authUser?.email || '' }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="p-1.5 rounded-lg text-text-muted hover:text-rose-500 hover:bg-surface-hover transition-colors shrink-0"
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
                <div class="flex items-center gap-2.5">
                    <Building2 class="w-4 h-4 text-text-muted" />
                    <span class="text-xs font-semibold text-text-muted">
                        {{ tenant?.domain || 'localhost' }}
                    </span>
                    <span v-if="tenant?.branding?.tagline" class="text-xs text-text-subtle border-s border-border-subtle ps-2.5">
                        {{ tenant.branding.tagline }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <ThemeSwitcher />
                    <LanguageSwitcher />
                </div>
            </header>

            <!-- Flash Notifications -->
            <div v-if="page.props.flash?.success" class="mx-6 mt-4 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs flex items-center gap-2.5 shrink-0">
                <CheckCircle2 class="w-4 h-4 shrink-0" />
                <span class="font-medium">{{ page.props.flash.success }}</span>
            </div>
            <div v-if="page.props.flash?.error" class="mx-6 mt-4 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2.5 shrink-0">
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
