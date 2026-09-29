<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import { Building2, Globe, User, Mail, Lock, Sparkles, CheckCircle2, ArrowRight } from 'lucide-vue-next';

interface Plan {
    id: number;
    name: string;
    slug: string;
    price: number;
    currency: string;
    trial_days: number;
    limits: {
        max_users?: number;
        max_storage_mb?: number;
    };
    is_free: boolean;
}

const props = defineProps<{
    plans: Plan[];
}>();

const { t } = useI18n();

// Read query param if passed e.g. /register-tenant?plan_id=2
const urlParams = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
const preselectedPlanId = Number(urlParams.get('plan_id')) || (props.plans[0]?.id ?? null);

const form = useForm({
    organization_name: '',
    subdomain: '',
    plan_id: preselectedPlanId,
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
});

function slugify(text: string) {
    return text
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
}

function onOrgNameInput() {
    if (!form.subdomain || form.subdomain === slugify(form.organization_name.slice(0, -1))) {
        form.subdomain = slugify(form.organization_name);
    }
}

function submit() {
    form.post('/register-tenant', {
        preserveScroll: true,
    });
}
</script>

<template>
    <GuestLayout>
        <div class="py-12 sm:py-16 max-w-3xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-3">
                    <Sparkles class="w-3.5 h-3.5" />
                    <span>{{ t('instant_onboarding', 'Self-Service Onboarding') }}</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight">{{ t('create_your_workspace', 'Create your workspace') }}</h1>
                <p class="mt-2 text-sm text-slate-400">{{ t('create_workspace_sub', 'Your dedicated tenant database will be provisioned instantly with zero configuration.') }}</p>
            </div>

            <form @submit.prevent="submit" class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl backdrop-blur-xl space-y-8">
                <!-- Section 1: Workspace Info -->
                <div class="space-y-5">
                    <h2 class="text-sm font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-2">
                        <Building2 class="w-4 h-4" />
                        <span>{{ t('organization_details', 'Workspace Details') }}</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('organization_name', 'Organization / Company Name') }}</label>
                        <input
                            v-model="form.organization_name"
                            type="text"
                            required
                            @input="onOrgNameInput"
                            placeholder="Acme Corporation"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500"
                        />
                        <p v-if="form.errors.organization_name" class="mt-1 text-xs text-rose-400">{{ form.errors.organization_name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('subdomain_identifier', 'Workspace Subdomain / URL') }}</label>
                        <div class="flex items-center rounded-xl bg-slate-800/80 border border-slate-700 overflow-hidden focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                            <input
                                v-model="form.subdomain"
                                type="text"
                                required
                                placeholder="acme"
                                class="flex-1 px-4 py-2.5 bg-transparent text-white text-sm outline-none placeholder:text-slate-500"
                            />
                            <span class="px-4 py-2.5 bg-slate-800 text-xs font-mono text-slate-400 border-s border-slate-700">
                                .localhost:8000
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">{{ t('subdomain_help', 'Only lowercase letters, numbers, and hyphens.') }}</p>
                        <p v-if="form.errors.subdomain" class="mt-1 text-xs text-rose-400">{{ form.errors.subdomain }}</p>
                    </div>
                </div>

                <!-- Section 2: Choose Plan -->
                <div class="space-y-4 pt-6 border-t border-slate-800">
                    <h2 class="text-sm font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-2">
                        <Sparkles class="w-4 h-4" />
                        <span>{{ t('select_subscription_plan', 'Select Subscription Plan') }}</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label
                            v-for="plan in plans"
                            :key="plan.id"
                            class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition-all text-start"
                            :class="
                                form.plan_id === plan.id
                                    ? 'bg-indigo-600/15 border-indigo-500 ring-1 ring-indigo-500'
                                    : 'bg-slate-800/40 border-slate-700/80 hover:border-slate-600'
                            "
                        >
                            <input
                                type="radio"
                                name="plan_id"
                                :value="plan.id"
                                v-model="form.plan_id"
                                class="sr-only"
                            />
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-sm text-white">{{ plan.name }}</span>
                                <CheckCircle2 v-if="form.plan_id === plan.id" class="w-4 h-4 text-indigo-400" />
                            </div>
                            <div class="text-base font-extrabold text-white mt-auto">
                                <CurrencyCell :amount="plan.price" :currency="plan.currency" />
                                <span class="text-[11px] text-slate-400 font-normal">/ mo</span>
                            </div>
                            <span v-if="plan.trial_days > 0" class="text-[10px] text-indigo-300 font-semibold mt-1">
                                {{ plan.trial_days }} {{ t('days_trial', 'Days Free') }}
                            </span>
                        </label>
                    </div>
                    <p v-if="form.errors.plan_id" class="mt-1 text-xs text-rose-400">{{ form.errors.plan_id }}</p>
                </div>

                <!-- Section 3: Owner Admin Account -->
                <div class="space-y-5 pt-6 border-t border-slate-800">
                    <h2 class="text-sm font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-2">
                        <User class="w-4 h-4" />
                        <span>{{ t('administrator_account', 'Workspace Administrator Account') }}</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('full_name', 'Full Name') }}</label>
                        <input
                            v-model="form.admin_name"
                            type="text"
                            required
                            placeholder="John Doe"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500"
                        />
                        <p v-if="form.errors.admin_name" class="mt-1 text-xs text-rose-400">{{ form.errors.admin_name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('work_email', 'Work Email') }}</label>
                        <input
                            v-model="form.admin_email"
                            type="email"
                            required
                            placeholder="john@example.com"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500"
                        />
                        <p v-if="form.errors.admin_email" class="mt-1 text-xs text-rose-400">{{ form.errors.admin_email }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('password', 'Password') }}</label>
                            <input
                                v-model="form.admin_password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500"
                            />
                            <p v-if="form.errors.admin_password" class="mt-1 text-xs text-rose-400">{{ form.errors.admin_password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">{{ t('confirm_password', 'Confirm Password') }}</label>
                            <input
                                v-model="form.admin_password_confirmation"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-500"
                            />
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-800">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3.5 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 flex items-center justify-center gap-2 transition-all"
                    >
                        <span v-if="form.processing">{{ t('provisioning_workspace', 'Provisioning Dedicated Database...') }}</span>
                        <template v-else>
                            <span>{{ t('launch_workspace', 'Launch Workspace Now') }}</span>
                            <ArrowRight class="w-4 h-4 rtl:rotate-180" />
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
