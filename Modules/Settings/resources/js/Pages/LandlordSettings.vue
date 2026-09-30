<script setup lang="ts">
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import TabNav from '@core/Components/TabNav.vue';
import Panel from '@core/Components/Panel.vue';
import FormField from '@core/Components/FormField.vue';
import PalettePicker from '@core/Components/PalettePicker.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Settings, Palette, Globe, Server, CreditCard, Save } from 'lucide-vue-next';
import { THEME_PRESETS } from '@core/Stores/useThemeStore';

const props = defineProps<{
    branding: Record<string, any>;
    themeSettings: Record<string, any>;
    localization: Record<string, any>;
    system: Record<string, any>;
    billing: Record<string, any>;
    currencies: Record<string, string>;
    plans: { id: number; name: any; slug: string }[];
}>();

const { t } = useI18n();

type TabKey = 'branding' | 'theme' | 'localization' | 'system' | 'billing';
const activeTab = ref<TabKey>('branding');

const tabs = [
    { key: 'branding' as TabKey, label: t('branding', 'Branding'), icon: Settings },
    { key: 'theme' as TabKey, label: t('theme', 'Theming & Tokens'), icon: Palette },
    { key: 'localization' as TabKey, label: t('localization', 'Localization'), icon: Globe },
    { key: 'system' as TabKey, label: t('system', 'System & Tenancy'), icon: Server },
    { key: 'billing' as TabKey, label: t('billing', 'Billing'), icon: CreditCard },
];

const brandingForm = useForm({
    domain: 'branding',
    settings: {
        app_name: props.branding?.app_name || 'SaaS Cloud',
        support_email: props.branding?.support_email || 'support@saas.test',
        tagline: props.branding?.tagline || 'Multi-Database Enterprise SaaS',
    },
});

const themeForm = useForm({
    domain: 'theme',
    settings: {
        palette: props.themeSettings?.palette || 'indigo',
        mode: props.themeSettings?.mode || 'dark',
    },
});

const localizationForm = useForm({
    domain: 'localization',
    settings: {
        default_locale: props.localization?.default_locale || 'en',
        supported_locales: props.localization?.supported_locales || ['en', 'ar'],
    },
});

const systemForm = useForm({
    domain: 'system',
    settings: {
        allow_registration: props.system?.allow_registration ?? true,
        tenant_db_prefix: props.system?.tenant_db_prefix || 'tenant_',
        default_trial_days: props.system?.default_trial_days || 14,
    },
});

const billingForm = useForm({
    domain: 'billing',
    settings: {
        default_currency: props.billing?.default_currency || 'USD',
        default_plan_id: props.billing?.default_plan_id || '',
    },
});

const page = usePage();
const allowedPalettes = new Set<string>((page.props.theme as any)?.palettes ?? THEME_PRESETS.map((p) => p.id));
const palettes = THEME_PRESETS.filter((p) => allowedPalettes.has(p.id));

const planOptions = [
    { value: '', label: t('first_active_plan', 'First active plan (by sort order)') },
    ...props.plans.map((p) => ({ value: p.id, label: String(p.name) })),
];
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6 max-w-4xl mx-auto">
            <PageHeader
                :title="t('platform_settings', 'Platform Central Settings')"
                :subtitle="t('platform_settings_sub', 'Configure platform-wide branding, system themes, localization, and multi-tenant provisioning.')"
            />

            <TabNav v-model="activeTab" :items="tabs" />

            <!-- Branding Tab -->
            <Panel v-if="activeTab === 'branding'">
                <form class="space-y-4" @submit.prevent="brandingForm.post('/landlord/settings')">
                    <FormField v-model="brandingForm.settings.app_name" :label="t('platform_name', 'Platform Name')" :error="brandingForm.errors['settings.app_name']" />
                    <FormField v-model="brandingForm.settings.tagline" :label="t('platform_tagline', 'Platform Tagline')" :error="brandingForm.errors['settings.tagline']" />
                    <FormField v-model="brandingForm.settings.support_email" :label="t('support_email', 'Support Contact Email')" type="email" :error="brandingForm.errors['settings.support_email']" />

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <BaseButton type="submit" :icon="Save" :loading="brandingForm.processing">
                            {{ t('save_changes', 'Save Branding') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>

            <!-- Theme Tab -->
            <Panel v-if="activeTab === 'theme'">
                <form class="space-y-6" @submit.prevent="themeForm.post('/landlord/settings')">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-3">{{ t('default_color_palette', 'Default Color Palette') }}</label>
                        <PalettePicker v-model="themeForm.settings.palette" :palettes="palettes" />
                    </div>

                    <FormField
                        v-model="themeForm.settings.mode"
                        :label="t('default_theme_mode', 'Default Theme Mode')"
                        type="select"
                        :options="{ dark: t('dark_mode', 'Dark Mode'), light: t('light_mode', 'Light Mode'), system: t('system_preference', 'System Preference') }"
                        :error="themeForm.errors['settings.mode']"
                    />

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <BaseButton type="submit" :icon="Save" :loading="themeForm.processing">
                            {{ t('save_changes', 'Save Theme') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>

            <!-- Localization Tab -->
            <Panel v-if="activeTab === 'localization'">
                <form class="space-y-4" @submit.prevent="localizationForm.post('/landlord/settings')">
                    <FormField
                        v-model="localizationForm.settings.default_locale"
                        :label="t('default_locale', 'Default Locale')"
                        type="select"
                        :options="{ en: 'English (LTR)', ar: 'العربية - Arabic (RTL)' }"
                        :error="localizationForm.errors['settings.default_locale']"
                    />

                    <label class="flex items-center gap-2 text-xs text-text-main cursor-pointer">
                        <input type="checkbox" value="ar" v-model="localizationForm.settings.supported_locales" class="rounded bg-surface-input border-border-subtle text-primary-600 focus:ring-primary-500" />
                        <span>{{ t('enable_arabic_desc', 'Enable Arabic (RTL) localization platform-wide') }}</span>
                    </label>

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <BaseButton type="submit" :icon="Save" :loading="localizationForm.processing">
                            {{ t('save_changes', 'Save Localization') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>

            <!-- System Tab -->
            <Panel v-if="activeTab === 'system'">
                <form class="space-y-4" @submit.prevent="systemForm.post('/landlord/settings')">
                    <FormField v-model.number="systemForm.settings.default_trial_days" :label="t('default_trial_days_label', 'Default Free Trial Duration (Days)')" type="number" min="0" :error="systemForm.errors['settings.default_trial_days']" />
                    <FormField v-model="systemForm.settings.tenant_db_prefix" :label="t('tenant_db_prefix_label', 'Tenant Database Name Prefix')" :error="systemForm.errors['settings.tenant_db_prefix']" class="font-mono" />

                    <FormField
                        v-model="systemForm.settings.allow_registration"
                        type="checkbox"
                        :label="t('allow_registration_desc', 'Allow Public Self-Service Workspace Registration')"
                    />

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <BaseButton type="submit" :icon="Save" :loading="systemForm.processing">
                            {{ t('save_changes', 'Save System Settings') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>

            <!-- Billing Tab -->
            <Panel v-if="activeTab === 'billing'">
                <form class="space-y-4" @submit.prevent="billingForm.post('/landlord/settings')">
                    <FormField
                        v-model="billingForm.settings.default_currency"
                        :label="t('default_currency_label', 'Default Currency')"
                        type="select"
                        :options="currencies"
                        :error="billingForm.errors['settings.default_currency']"
                    />
                    <FormField
                        v-model="billingForm.settings.default_plan_id"
                        :label="t('default_plan_label', 'Default Plan for New Registrations')"
                        type="select"
                        :options="planOptions"
                        :error="billingForm.errors['settings.default_plan_id']"
                    />

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <BaseButton type="submit" :icon="Save" :loading="billingForm.processing">
                            {{ t('save_changes', 'Save Billing') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>
        </div>
    </LandlordLayout>
</template>
