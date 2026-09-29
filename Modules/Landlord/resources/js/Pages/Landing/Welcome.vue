<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import {
    ShieldCheck,
    Database,
    Zap,
    Users,
    Layers,
    Check,
    ArrowRight,
    Sparkles,
    Lock,
} from 'lucide-vue-next';

interface Plan {
    id: number;
    name: string;
    slug: string;
    description: string;
    price: number;
    currency: string;
    billing_interval: string;
    trial_days: number;
    limits: {
        max_users?: number;
        max_storage_mb?: number;
        features?: string[];
    };
    is_free: boolean;
}

const props = defineProps<{
    plans: Plan[];
}>();

const { t } = useI18n();

const features = computed(() => [
    {
        icon: Database,
        title: t('multi_database_isolation', 'Multi-Database Isolation'),
        description: t('multi_database_isolation_desc', 'Every organization gets a dedicated, isolated database for maximum security, compliance, and zero data leakage.'),
    },
    {
        icon: Zap,
        title: t('instant_provisioning', 'Instant Provisioning'),
        description: t('instant_provisioning_desc', 'Automated tenant database creation, schema migrations, and administrator seeding in seconds.'),
    },
    {
        icon: Layers,
        title: t('flexible_plans', 'Flexible Plans & Billing'),
        description: t('flexible_plans_desc', 'Dynamic plans, feature flags, user seats, and storage quotas managed centrally.'),
    },
    {
        icon: Lock,
        title: t('rbac_security', 'Role-Based Access Control'),
        description: t('rbac_security_desc', 'Fine-grained permissions and roles within each tenant workspace using Spatie Permission.'),
    },
    {
        icon: Users,
        title: t('team_collaboration', 'Team Collaboration'),
        description: t('team_collaboration_desc', 'Invite team members, assign workspace roles, and control access permissions easily.'),
    },
    {
        icon: ShieldCheck,
        title: t('enterprise_grade', 'Enterprise Performance'),
        description: t('enterprise_grade_desc', 'High cohesion modular architecture powered by Laravel Modules and Vue 3 + Inertia.'),
    },
]);
</script>

<template>
    <GuestLayout>
        <!-- Hero Section -->
        <section class="relative overflow-hidden py-24 sm:py-32">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-900/30 via-slate-950 to-slate-950 -z-10" />
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-indigo-500/30 bg-indigo-500/10 text-indigo-300 text-xs font-semibold mb-8 animate-pulse">
                    <Sparkles class="w-3.5 h-3.5" />
                    <span>{{ t('saas_tagline', 'Next-Gen Multi-Database SaaS Platform') }}</span>
                </div>

                <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight max-w-4xl mx-auto leading-tight sm:leading-none">
                    {{ t('hero_title_1', 'Enterprise Cloud SaaS') }}
                    <span class="block bg-gradient-to-r from-indigo-400 via-purple-300 to-indigo-200 bg-clip-text text-transparent mt-2">
                        {{ t('hero_title_2', 'With Strict Data Isolation') }}
                    </span>
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                    {{ t('hero_subtitle', 'Launch and scale your workspace with dedicated databases, modular micro-architecture, automated onboarding, and enterprise role-based security.') }}
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        href="/register-tenant"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-xl shadow-indigo-600/30 transition-all hover:scale-[1.02]"
                    >
                        <span>{{ t('start_free_trial', 'Start Free Trial') }}</span>
                        <ArrowRight class="w-4 h-4 rtl:rotate-180" />
                    </Link>

                    <Link
                        href="/pricing"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-200 border border-slate-700 font-semibold text-sm transition-all"
                    >
                        {{ t('view_pricing', 'View Pricing & Plans') }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section class="py-20 border-t border-slate-900 bg-slate-950/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-xs font-bold text-indigo-400 uppercase tracking-widest">{{ t('platform_features', 'Architecture & Features') }}</h2>
                    <p class="mt-2 text-3xl font-extrabold text-white tracking-tight">{{ t('engineered_for_scale', 'Engineered For Enterprise Scale') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="feat in features"
                        :key="feat.title"
                        class="p-7 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-indigo-500/40 transition-all group"
                    >
                        <div class="w-12 h-12 rounded-xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mb-5 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <component :is="feat.icon" class="w-6 h-6 stroke-[1.8]" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">{{ feat.title }}</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">{{ feat.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Preview Section -->
        <section class="py-20 border-t border-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-xs font-bold text-indigo-400 uppercase tracking-widest">{{ t('transparent_pricing', 'Simple Pricing') }}</h2>
                    <p class="mt-2 text-3xl font-extrabold text-white tracking-tight">{{ t('choose_plan_header', 'Choose the Right Plan for Your Team') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="relative rounded-2xl bg-slate-900/60 border border-slate-800/80 p-8 flex flex-col justify-between hover:border-indigo-500/50 transition-all shadow-xl"
                        :class="plan.slug === 'pro' ? 'ring-2 ring-indigo-500 bg-slate-900/90' : ''"
                    >
                        <div v-if="plan.slug === 'pro'" class="absolute -top-3.5 start-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-indigo-600 text-white text-[11px] font-bold uppercase tracking-wider shadow-md">
                            {{ t('most_popular', 'Most Popular') }}
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                            <p class="text-xs text-slate-400 mt-2 min-h-[32px]">{{ plan.description }}</p>

                            <div class="mt-6 flex items-baseline gap-1 text-white">
                                <span class="text-4xl font-extrabold tracking-tight">
                                    <CurrencyCell :amount="plan.price" :currency="plan.currency" />
                                </span>
                                <span class="text-xs text-slate-400 font-medium">/ {{ plan.billing_interval }}</span>
                            </div>

                            <ul class="mt-8 space-y-3.5 text-xs text-slate-300">
                                <li class="flex items-center gap-2.5">
                                    <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>{{ t('max_users', 'Max Users') }}: <strong class="text-white">{{ plan.limits?.max_users ?? 'Unlimited' }}</strong></span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>{{ t('storage', 'Storage') }}: <strong class="text-white">{{ plan.limits?.max_storage_mb ?? 1000 }} MB</strong></span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>{{ plan.trial_days > 0 ? `${plan.trial_days} days trial` : t('instant_access', 'Instant Access') }}</span>
                                </li>
                                <li
                                    v-for="(feature, idx) in plan.limits?.features ?? []"
                                    :key="idx"
                                    class="flex items-center gap-2.5 text-slate-300"
                                >
                                    <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-800">
                            <Link
                                :href="`/register-tenant?plan_id=${plan.id}`"
                                class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl font-semibold text-xs transition-all shadow-md"
                                :class="plan.slug === 'pro' ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/30' : 'bg-slate-800 hover:bg-slate-700 text-slate-200'"
                            >
                                {{ t('get_started', 'Get Started') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>
