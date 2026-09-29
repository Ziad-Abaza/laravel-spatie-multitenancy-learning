<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import { Check, HelpCircle } from 'lucide-vue-next';

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
const isYearly = ref(false);
</script>

<template>
    <GuestLayout>
        <section class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest">{{ t('pricing_plans', 'Plans & Pricing') }}</span>
                <h1 class="mt-2 text-4xl sm:text-5xl font-black text-white tracking-tight">{{ t('pricing_headline', 'Transparent plans that grow with you') }}</h1>
                <p class="mt-4 text-base text-slate-400">{{ t('pricing_subline', 'All plans include multi-database isolation, SSL, custom domains, and automated backups.') }}</p>
            </div>

            <!-- Plan Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative rounded-3xl bg-slate-900/60 border border-slate-800 p-8 flex flex-col justify-between hover:border-indigo-500/50 transition-all shadow-2xl"
                    :class="plan.slug === 'pro' ? 'ring-2 ring-indigo-500 bg-slate-900/90' : ''"
                >
                    <div v-if="plan.slug === 'pro'" class="absolute -top-3.5 start-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-indigo-600 text-white text-[11px] font-bold uppercase tracking-wider shadow-lg">
                        {{ t('recommended', 'Recommended') }}
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-white">{{ plan.name }}</h2>
                            <span v-if="plan.trial_days > 0" class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                {{ plan.trial_days }} {{ t('days_trial', 'Days Free') }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-400 mt-3 min-h-[40px]">{{ plan.description }}</p>

                        <div class="mt-8 flex items-baseline gap-1 text-white">
                            <span class="text-4xl sm:text-5xl font-extrabold tracking-tight">
                                <CurrencyCell :amount="plan.price" :currency="plan.currency" />
                            </span>
                            <span class="text-xs text-slate-400 font-medium">/ {{ plan.billing_interval }}</span>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-800">
                            <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-4">{{ t('what_is_included', 'What is included') }}:</p>
                            <ul class="space-y-3.5 text-xs text-slate-300">
                                <li class="flex items-center gap-3">
                                    <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>{{ t('max_users', 'Max Users') }}: <strong class="text-white">{{ plan.limits?.max_users ?? 'Unlimited' }}</strong></span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>{{ t('dedicated_database', 'Dedicated Database Isolation') }}</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>{{ t('storage', 'Storage Quota') }}: <strong class="text-white">{{ plan.limits?.max_storage_mb ?? 1000 }} MB</strong></span>
                                </li>
                                <li
                                    v-for="(feature, idx) in plan.limits?.features ?? []"
                                    :key="idx"
                                    class="flex items-center gap-3"
                                >
                                    <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-800">
                        <Link
                            :href="`/register-tenant?plan_id=${plan.id}`"
                            class="w-full inline-flex items-center justify-center py-3 px-4 rounded-xl font-semibold text-xs transition-all shadow-md"
                            :class="plan.slug === 'pro' ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/30' : 'bg-slate-800 hover:bg-slate-700 text-slate-200'"
                        >
                            {{ t('start_with_plan', 'Get Started with') }} {{ plan.name }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>
