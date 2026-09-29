<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Building2, User, ArrowLeft } from 'lucide-vue-next';

interface Plan {
    id: number;
    name: string;
    price: number;
}

const props = defineProps<{
    plans: Plan[];
}>();

const { t } = useI18n();

const form = useForm({
    name: '',
    slug: '',
    domain: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    plan_id: props.plans[0]?.id || '',
});

function onNameChange() {
    const slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    if (!form.slug) form.slug = slug;
    if (!form.domain) form.domain = `${slug}.localhost`;
}

function onSlugChange() {
    form.domain = `${form.slug}.localhost`;
}

function submit() {
    form.post('/landlord/tenants');
}
</script>

<template>
    <LandlordLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <div>
                <Link
                    href="/landlord/tenants"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-text-muted hover:text-text-main mb-4 transition-colors"
                >
                    <ArrowLeft class="w-4 h-4 rtl:rotate-180" />
                    <span>{{ t('back_to_tenants', 'Back to Tenants') }}</span>
                </Link>

                <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('provision_tenant', 'Provision Tenant Organization') }}</h1>
                <p class="text-xs text-text-muted mt-1">{{ t('provision_tenant_sub', 'Creates an isolated MySQL database, runs all module tenant migrations, and seeds the owner account.') }}</p>
            </div>

            <form @submit.prevent="submit" class="bg-surface-card border border-border-subtle rounded-3xl p-8 space-y-6 shadow-xl">
                <!-- Org Details -->
                <div class="space-y-4">
                    <h2 class="text-xs font-bold text-primary-400 uppercase tracking-wider flex items-center gap-2">
                        <Building2 class="w-4 h-4" />
                        <span>{{ t('organization_details', 'Workspace Details') }}</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('organization_name', 'Company / Tenant Name') }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            @input="onNameChange"
                            placeholder="Stark Industries"
                            class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all placeholder:text-text-subtle"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('slug', 'Workspace Slug') }}</label>
                            <input
                                v-model="form.slug"
                                type="text"
                                required
                                @input="onSlugChange"
                                placeholder="stark"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all placeholder:text-text-subtle"
                            />
                            <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-500">{{ form.errors.slug }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('domain', 'Domain FQDN') }}</label>
                            <input
                                v-model="form.domain"
                                type="text"
                                required
                                placeholder="stark.localhost"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all placeholder:text-text-subtle font-mono text-xs"
                            />
                            <p v-if="form.errors.domain" class="mt-1 text-xs text-rose-500">{{ form.errors.domain }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('subscription_plan', 'Subscription Plan') }}</label>
                        <select
                            v-model="form.plan_id"
                            class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all"
                        >
                            <option value="">{{ t('free_plan', 'Free Plan') }}</option>
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }} (${{ plan.price }}/mo)
                            </option>
                        </select>
                        <p v-if="form.errors.plan_id" class="mt-1 text-xs text-rose-500">{{ form.errors.plan_id }}</p>
                    </div>
                </div>

                <!-- Admin Details -->
                <div class="space-y-4 pt-6 border-t border-border-subtle">
                    <h2 class="text-xs font-bold text-primary-400 uppercase tracking-wider flex items-center gap-2">
                        <User class="w-4 h-4" />
                        <span>{{ t('tenant_owner_account', 'Initial Owner Account') }}</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('admin_name', 'Owner Name') }}</label>
                        <input
                            v-model="form.admin_name"
                            type="text"
                            required
                            placeholder="Tony Stark"
                            class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all placeholder:text-text-subtle"
                        />
                        <p v-if="form.errors.admin_name" class="mt-1 text-xs text-rose-500">{{ form.errors.admin_name }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('admin_email', 'Owner Email') }}</label>
                            <input
                                v-model="form.admin_email"
                                type="email"
                                required
                                placeholder="tony@stark.test"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all placeholder:text-text-subtle"
                            />
                            <p v-if="form.errors.admin_email" class="mt-1 text-xs text-rose-500">{{ form.errors.admin_email }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-text-main mb-1.5">{{ t('admin_password', 'Temporary Password') }}</label>
                            <input
                                v-model="form.admin_password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="••••••••"
                                class="w-full px-4 py-2.5 rounded-xl bg-surface-bg border border-border-subtle text-text-main text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all placeholder:text-text-subtle"
                            />
                            <p v-if="form.errors.admin_password" class="mt-1 text-xs text-rose-500">{{ form.errors.admin_password }}</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-border-subtle flex items-center justify-end gap-3">
                    <Link
                        href="/landlord/tenants"
                        class="px-4 py-2.5 rounded-xl text-xs font-semibold text-text-muted hover:text-text-main transition-colors"
                    >
                        {{ t('cancel', 'Cancel') }}
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-white font-semibold text-xs shadow-lg shadow-primary-600/30 flex items-center gap-2 transition-all cursor-pointer"
                    >
                        <span>{{ form.processing ? t('provisioning', 'Provisioning...') : t('provision_tenant', 'Provision Tenant') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </LandlordLayout>
</template>
