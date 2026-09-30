<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import CurrencyCell from '@core/Components/CurrencyCell.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { Building2, User, Sparkles, CheckCircle2, ArrowRight, AlertTriangle } from 'lucide-vue-next';

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

const page = usePage();
const { t } = useI18n();

const allowRegistration = computed(() => page.props.system?.allow_registration !== false);

// Mirrors TenantProvisioner::tenantDomain(): configured suffix, else the
// request host (with port for display fidelity in dev).
const domainSuffix = computed(
    () => page.props.tenancy?.domain_suffix || window.location.host
);

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

watch(() => form.organization_name, (name, prev) => {
    if (!form.subdomain || form.subdomain === slugify(prev || '')) {
        form.subdomain = slugify(name);
    }
});

function submit() {
    if (!allowRegistration.value) return;
    form.post('/register-tenant', {
        preserveScroll: true,
    });
}
</script>

<template>
    <GuestLayout>
        <div class="py-12 sm:py-16 max-w-3xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-500/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 text-xs font-semibold mb-3">
                    <Sparkles class="w-3.5 h-3.5" />
                    <span>{{ t('instant_onboarding', 'Self-Service Onboarding') }}</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-black text-text-main tracking-tight">{{ t('create_your_workspace', 'Create your workspace') }}</h1>
                <p class="mt-2 text-sm text-text-muted">{{ t('create_workspace_sub', 'Your dedicated tenant database will be provisioned instantly with zero configuration.') }}</p>
            </div>

            <div v-if="!allowRegistration" class="bg-surface-card border border-border-subtle rounded-3xl p-8 text-center space-y-4 shadow-xl">
                <div class="w-12 h-12 rounded-full bg-warning/10 text-warning-fg flex items-center justify-center mx-auto">
                    <AlertTriangle class="w-6 h-6" />
                </div>
                <h2 class="text-lg font-bold text-text-main">{{ t('registration_closed', 'Registration Currently Closed') }}</h2>
                <p class="text-sm text-text-muted max-w-md mx-auto">
                    {{ t('registration_disabled_message', 'Self-service organization registration is currently disabled by system administrators.') }}
                </p>
                <div class="pt-4">
                    <BaseButton href="/" class="!px-6 shadow-md">
                        {{ t('return_home', 'Return Home') }}
                    </BaseButton>
                </div>
            </div>

            <form v-else @submit.prevent="submit" class="bg-surface-card border border-border-subtle rounded-3xl p-6 sm:p-10 shadow-2xl space-y-8">
                <!-- Section 1: Workspace Info -->
                <div class="space-y-5">
                    <h2 class="text-sm font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider flex items-center gap-2">
                        <Building2 class="w-4 h-4" />
                        <span>{{ t('organization_details', 'Workspace Details') }}</span>
                    </h2>

                    <FormField
                        v-model="form.organization_name"
                        :label="t('organization_name', 'Organization / Company Name')"
                        required
                        placeholder="Acme Corporation"
                        :error="form.errors.organization_name"
                    />

                    <FormField
                        v-model="form.subdomain"
                        :label="t('subdomain_identifier', 'Workspace Subdomain / URL')"
                        required
                        placeholder="acme"
                        :hint="t('subdomain_help', 'Only lowercase letters, numbers, and hyphens.')"
                        :error="form.errors.subdomain"
                    >
                        <template #trailing>.{{ domainSuffix }}</template>
                    </FormField>
                </div>

                <!-- Section 2: Choose Plan -->
                <div class="space-y-4 pt-6 border-t border-border-subtle">
                    <h2 class="text-sm font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider flex items-center gap-2">
                        <Sparkles class="w-4 h-4" />
                        <span>{{ t('selected_subscription_plan', 'Select Subscription Plan') }}</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label
                            v-for="plan in plans"
                            :key="plan.id"
                            class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition-all text-start"
                            :class="
                                form.plan_id === plan.id
                                    ? 'bg-primary-500/10 border-primary-500 ring-1 ring-primary-500'
                                    : 'bg-surface-input border-border-subtle hover:border-border-strong'
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
                                <span class="font-bold text-sm text-text-main">{{ plan.name }}</span>
                                <CheckCircle2 v-if="form.plan_id === plan.id" class="w-4 h-4 text-primary-500" />
                            </div>
                            <div class="text-base font-extrabold text-text-main mt-auto">
                                <CurrencyCell :amount="plan.price" />
                                <span class="text-[11px] text-text-muted font-normal">{{ t('per_month', '/ mo') }}</span>
                            </div>
                            <span v-if="plan.trial_days > 0" class="text-[10px] text-primary-600 dark:text-primary-400 font-semibold mt-1">
                                {{ plan.trial_days }} {{ t('days_trial', 'Days Free') }}
                            </span>
                        </label>
                    </div>
                    <p v-if="form.errors.plan_id" class="mt-1 text-xs text-danger-fg">{{ form.errors.plan_id }}</p>
                </div>

                <!-- Section 3: Owner Admin Account -->
                <div class="space-y-5 pt-6 border-t border-border-subtle">
                    <h2 class="text-sm font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider flex items-center gap-2">
                        <User class="w-4 h-4" />
                        <span>{{ t('administrator_credentials', 'Workspace Administrator Account') }}</span>
                    </h2>

                    <FormField
                        v-model="form.admin_name"
                        :label="t('admin_name_label', 'Full Name')"
                        required
                        placeholder="John Doe"
                        :error="form.errors.admin_name"
                    />

                    <FormField
                        v-model="form.admin_email"
                        :label="t('admin_email_label', 'Work Email')"
                        type="email"
                        required
                        placeholder="john@example.com"
                        :error="form.errors.admin_email"
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <FormField
                            v-model="form.admin_password"
                            :label="t('admin_password_label', 'Password')"
                            type="password"
                            required
                            :min="8"
                            placeholder="••••••••"
                            :error="form.errors.admin_password"
                        />
                        <FormField
                            v-model="form.admin_password_confirmation"
                            :label="t('confirm_password_label', 'Confirm Password')"
                            type="password"
                            required
                            placeholder="••••••••"
                            :error="form.errors.admin_password_confirmation"
                        />
                    </div>
                </div>

                <div class="pt-6 border-t border-border-subtle">
                    <BaseButton
                        type="submit"
                        :loading="form.processing"
                        :icon="form.processing ? undefined : ArrowRight"
                        class="w-full !py-3.5 !px-6 !text-sm font-bold shadow-xl shadow-primary-600/30"
                    >
                        {{ form.processing ? t('provisioning_tenant', 'Provisioning Dedicated Database...') : t('provision_workspace_button', 'Deploy Isolated Workspace') }}
                    </BaseButton>
                    <p class="text-[11px] text-text-subtle text-center mt-3">{{ t('workspace_provisioning_hint', 'Zero-downtime deployment: Migrates dedicated schema & seeds administrative roles instantly.') }}</p>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
