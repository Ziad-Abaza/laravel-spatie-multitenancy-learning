<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import Panel from '@core/Components/Panel.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Building2, User } from 'lucide-vue-next';

interface Plan {
    id: number;
    name: string;
    price: number;
}

const props = defineProps<{
    plans: Plan[];
}>();

const { t } = useI18n();

const page = usePage<{
    tenancy?: {
        domain_suffix?: string | null;
    };
}>();

// Mirrors TenantProvisioner::tenantDomain(): configured suffix, else the
// request host.
const domainSuffix = computed(
    () => page.props.tenancy?.domain_suffix || window.location.hostname
);

const form = useForm({
    name: '',
    slug: '',
    domain: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    plan_id: props.plans[0]?.id || '',
});

watch(() => form.name, (name) => {
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    if (!form.slug) form.slug = slug;
    if (!form.domain) form.domain = slug ? `${slug}.${domainSuffix.value}` : '';
});

watch(() => form.slug, (slug) => {
    form.domain = slug ? `${slug}.${domainSuffix.value}` : '';
});

const planOptions = [
    { value: '', label: t('free_plan', 'Free Plan') },
    ...props.plans.map((p) => ({ value: p.id, label: `${p.name} ($${p.price}/mo)` })),
];

function submit() {
    form.post('/landlord/tenants');
}
</script>

<template>
    <LandlordLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <PageHeader
                :title="t('provision_tenant', 'Provision Tenant Organization')"
                :subtitle="t('provision_tenant_sub', 'Creates an isolated MySQL database, runs all module tenant migrations, and seeds the owner account.')"
                back-href="/landlord/tenants"
                :back-label="t('back_to_tenants', 'Back to Tenants')"
            />

            <Panel :title="t('organization_details', 'Workspace Details')" :icon="Building2">
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="space-y-4">
                        <FormField
                            v-model="form.name"
                            :label="t('organization_name', 'Company / Tenant Name')"
                            required
                            placeholder="Stark Industries"
                            :error="form.errors.name"
                        />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormField
                                v-model="form.slug"
                                :label="t('slug', 'Workspace Slug')"
                                required
                                placeholder="stark"
                                :error="form.errors.slug"
                            />
                            <FormField
                                v-model="form.domain"
                                :label="t('domain', 'Domain FQDN')"
                                required
                                :placeholder="`stark.${domainSuffix}`"
                                :error="form.errors.domain"
                                class="font-mono"
                            />
                        </div>
                        <FormField
                            v-model="form.plan_id"
                            :label="t('subscription_plan', 'Subscription Plan')"
                            type="select"
                            :options="planOptions"
                            :error="form.errors.plan_id"
                        />
                    </div>

                    <div class="space-y-4 pt-6 border-t border-border-subtle">
                        <h2 class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider flex items-center gap-2">
                            <User class="w-4 h-4" />
                            <span>{{ t('tenant_owner_account', 'Initial Owner Account') }}</span>
                        </h2>

                        <FormField
                            v-model="form.admin_name"
                            :label="t('admin_name', 'Owner Name')"
                            required
                            placeholder="Tony Stark"
                            :error="form.errors.admin_name"
                        />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormField
                                v-model="form.admin_email"
                                :label="t('admin_email', 'Owner Email')"
                                type="email"
                                required
                                placeholder="tony@stark.test"
                                :error="form.errors.admin_email"
                            />
                            <FormField
                                v-model="form.admin_password"
                                :label="t('admin_password', 'Temporary Password')"
                                type="password"
                                required
                                :min="8"
                                placeholder="••••••••"
                                :error="form.errors.admin_password"
                            />
                        </div>
                    </div>

                    <div class="pt-6 border-t border-border-subtle flex items-center justify-end gap-3">
                        <BaseButton href="/landlord/tenants" variant="ghost">
                            {{ t('cancel', 'Cancel') }}
                        </BaseButton>
                        <BaseButton type="submit" :loading="form.processing" class="shadow-lg shadow-primary-600/30">
                            {{ form.processing ? t('provisioning', 'Provisioning...') : t('provision_tenant', 'Provision Tenant') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>
        </div>
    </LandlordLayout>
</template>
