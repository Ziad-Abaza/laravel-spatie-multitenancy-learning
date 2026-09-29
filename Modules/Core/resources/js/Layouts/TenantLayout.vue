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

const tenant = computed(() => page.props.tenant);
const authUser = computed(() => page.props.auth?.user);

const navItems = [
    { name: t('dashboard', 'Dashboard'), href: '/dashboard', icon: LayoutDashboard },
    { name: t('team', 'Team'), href: '/users', icon: Users },
    { name: t('roles', 'Roles'), href: '/roles', icon: ShieldCheck },
    { name: t('subscriptions', 'Subscription & Quotas'), href: '/subscription', icon: CreditCard },
    { name: t('settings', 'Settings'), href: '/settings', icon: Settings },
    { name: t('profile', 'Profile'), href: '/profile', icon: UserIcon },
];

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row antialiased">
        <!-- Mobile Header -->
        <header class="md:hidden flex items-center justify-between p-4 bg-slate-900 border-b border-slate-800">
            <div class="flex items-center gap-2 font-bold text-white tracking-tight">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-black text-sm">
                    {{ tenant?.name ? tenant.name.charAt(0) : 'T' }}
                </div>
                <span class="truncate max-w-[150px]">{{ tenant?.name || 'Workspace' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <ThemeSwitcher />
                <LanguageSwitcher />
                <button
                    type="button"
                    class="p-2 text-slate-400 hover:text-white"
                    @click="isSidebarOpen = !isSidebarOpen"
                >
                    <Menu v-if="!isSidebarOpen" class="w-6 h-6" />
                    <X v-else class="w-6 h-6" />
                </button>
            </div>
        </header>

        <!-- Sidebar -->
        <aside
            class="fixed md:static inset-y-0 start-0 z-40 w-64 bg-slate-900 border-e border-slate-800 flex flex-col transition-transform duration-200 md:translate-x-0"
            :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            <!-- Tenant Workspace Header -->
            <div class="p-6 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 font-bold text-base">
                        {{ tenant?.name ? tenant.name.charAt(0).toUpperCase() : 'W' }}
                    </div>
                    <div class="overflow-hidden flex-1">
                        <h1 class="text-sm font-bold text-white leading-tight truncate">
                            {{ tenant?.name || 'Workspace' }}
                        </h1>
                        <div class="mt-1 flex items-center gap-1.5">
                            <StatusBadge v-if="tenant?.status" :status="tenant.status" size="sm" />
                            <span v-if="tenant?.plan?.name" class="text-[10px] text-slate-400 font-medium truncate">
                                {{ tenant.plan.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
                    :class="
                        $page.url === item.href || $page.url.startsWith(item.href + '/')
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-semibold'
                            : 'text-slate-400 hover:text-white hover:bg-slate-800/70'
                    "
                    @click="isSidebarOpen = false"
                >
                    <component :is="item.icon" class="w-4 h-4 stroke-[1.8]" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <!-- Bottom User Profile Card -->
            <div class="p-4 border-t border-slate-800/80 bg-slate-950/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-indigo-400 font-bold text-xs uppercase">
                            {{ authUser?.name ? authUser.name.charAt(0) : 'U' }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs font-semibold text-white truncate">
                                {{ authUser?.name || 'User' }}
                            </p>
                            <p class="text-[11px] text-slate-400 truncate">
                                {{ authUser?.email || '' }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition-colors"
                        title="Logout"
                        @click="logout"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar (Desktop) -->
            <header class="hidden md:flex items-center justify-between px-8 py-4 bg-slate-900/60 border-b border-slate-800/80 backdrop-blur-sm sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <Building2 class="w-4 h-4 text-slate-400" />
                    <span class="text-xs font-semibold text-slate-300">
                        {{ tenant?.domain || 'localhost' }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <ThemeSwitcher />
                    <LanguageSwitcher />
                </div>
            </header>

            <!-- Flash Banners -->
            <div v-if="page.props.flash?.success" class="mx-6 mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-2.5">
                <CheckCircle2 class="w-4 h-4 shrink-0" />
                <span>{{ page.props.flash.success }}</span>
            </div>
            <div v-if="page.props.flash?.error" class="mx-6 mt-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-center gap-2.5">
                <AlertCircle class="w-4 h-4 shrink-0" />
                <span>{{ page.props.flash.error }}</span>
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-6 md:p-8 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
