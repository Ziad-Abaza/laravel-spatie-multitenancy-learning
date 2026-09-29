<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Settings, Palette, Globe, Server, Save } from 'lucide-vue-next';
import { THEME_PRESETS } from '@core/Stores/useThemeStore';

const props = defineProps<{
    branding: Record<string, any>;
    themeSettings: Record<string, any>;
    localization: Record<string, any>;
    system: Record<string, any>;
}>();

const { t } = useI18n();

const activeTab = ref<'branding' | 'theme' | 'localization' | 'system'>('branding');

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

function saveBranding() {
    brandingForm.post('/landlord/settings');
}

function saveTheme() {
    themeForm.post('/landlord/settings');
}

function saveLocalization() {
    localizationForm.post('/landlord/settings');
}

function saveSystem() {
    systemForm.post('/landlord/settings');
}

const palettes = THEME_PRESETS;
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6 max-w-4xl mx-auto">
            <div>
                <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('platform_settings', 'Platform Central Settings') }}</h1>
                <p class="text-xs text-text-muted mt-1">{{ t('platform_settings_sub', 'Configure platform-wide branding, system themes, localization, and multi-tenant provisioning.') }}</p>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center gap-2 border-b border-border-subtle pb-2 overflow-x-auto">
                <button
                    type="button"
                    @click="activeTab = 'branding'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all shrink-0"
                    :class="activeTab === 'branding' ? 'bg-primary-600 text-on-primary shadow-xs' : 'text-text-muted hover:text-text-main hover:bg-surface-hover'"
                >
                    <Settings class="w-4 h-4" />
                    <span>{{ t('branding', 'Branding') }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'theme'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all shrink-0"
                    :class="activeTab === 'theme' ? 'bg-primary-600 text-on-primary shadow-xs' : 'text-text-muted hover:text-text-main hover:bg-surface-hover'"
                >
                    <Palette class="w-4 h-4" />
                    <span>{{ t('theme', 'Theming & Tokens') }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'localization'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all shrink-0"
                    :class="activeTab === 'localization' ? 'bg-primary-600 text-on-primary shadow-xs' : 'text-text-muted hover:text-text-main hover:bg-surface-hover'"
                >
                    <Globe class="w-4 h-4" />
                    <span>{{ t('localization', 'Localization') }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'system'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all shrink-0"
                    :class="activeTab === 'system' ? 'bg-primary-600 text-on-primary shadow-xs' : 'text-text-muted hover:text-text-main hover:bg-surface-hover'"
                >
                    <Server class="w-4 h-4" />
                    <span>{{ t('system', 'System & Tenancy') }}</span>
                </button>
            </div>

            <!-- Branding Tab -->
            <div v-if="activeTab === 'branding'" class="bg-surface-card border border-border-subtle rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                <form @submit.prevent="saveBranding" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('platform_name', 'Platform Name') }}</label>
                        <input v-model="brandingForm.settings.app_name" type="text" class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('platform_tagline', 'Platform Tagline') }}</label>
                        <input v-model="brandingForm.settings.tagline" type="text" class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('support_email', 'Support Contact Email') }}</label>
                        <input v-model="brandingForm.settings.support_email" type="email" class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                    </div>

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <button type="submit" :disabled="brandingForm.processing" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save Branding') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Theme Tab -->
            <div v-if="activeTab === 'theme'" class="bg-surface-card border border-border-subtle rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                <form @submit.prevent="saveTheme" class="space-y-6">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-3">{{ t('default_color_palette', 'Default Color Palette') }}</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <label
                                v-for="p in palettes"
                                :key="p.id"
                                class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer capitalize text-xs transition-all"
                                :class="themeForm.settings.palette === p.id ? 'bg-primary-500/10 border-primary-500 text-text-main font-semibold' : 'bg-surface-input border-border-subtle text-text-muted hover:text-text-main'"
                            >
                                <input type="radio" v-model="themeForm.settings.palette" :value="p.id" class="sr-only" />
                                <span class="flex shrink-0">
                                    <span
                                        v-for="(c, i) in p.colors"
                                        :key="c"
                                        class="w-4 h-4 rounded-full shadow-xs border border-black/10"
                                        :class="i > 0 ? '-ms-1.5' : ''"
                                        :style="{ backgroundColor: c }"
                                    />
                                </span>
                                <span>{{ p.id }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-2">{{ t('default_theme_mode', 'Default Theme Mode') }}</label>
                        <select v-model="themeForm.settings.mode" class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                            <option value="dark">{{ t('dark_mode', 'Dark Mode') }}</option>
                            <option value="light">{{ t('light_mode', 'Light Mode') }}</option>
                            <option value="system">{{ t('system_preference', 'System Preference') }}</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <button type="submit" :disabled="themeForm.processing" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save Theme') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Localization Tab -->
            <div v-if="activeTab === 'localization'" class="bg-surface-card border border-border-subtle rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                <form @submit.prevent="saveLocalization" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('default_locale', 'Default Locale') }}</label>
                        <select v-model="localizationForm.settings.default_locale" class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                            <option value="en">English (LTR)</option>
                            <option value="ar">العربية - Arabic (RTL)</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 text-xs text-text-main cursor-pointer">
                            <input type="checkbox" value="ar" v-model="localizationForm.settings.supported_locales" class="rounded bg-surface-input border-border-subtle text-primary-600 focus:ring-primary-500" />
                            <span>{{ t('enable_arabic_desc', 'Enable Arabic (RTL) localization platform-wide') }}</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <button type="submit" :disabled="localizationForm.processing" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save Localization') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Tab -->
            <div v-if="activeTab === 'system'" class="bg-surface-card border border-border-subtle rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
                <form @submit.prevent="saveSystem" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('default_trial_days_label', 'Default Free Trial Duration (Days)') }}</label>
                        <input v-model.number="systemForm.settings.default_trial_days" type="number" min="0" class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('tenant_db_prefix_label', 'Tenant Database Name Prefix') }}</label>
                        <input v-model="systemForm.settings.tenant_db_prefix" type="text" class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono" />
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 text-xs text-text-main cursor-pointer">
                            <input type="checkbox" v-model="systemForm.settings.allow_registration" class="rounded bg-surface-input border-border-subtle text-primary-600 focus:ring-primary-500" />
                            <span>{{ t('allow_registration_desc', 'Allow Public Self-Service Workspace Registration') }}</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-border-subtle flex justify-end">
                        <button type="submit" :disabled="systemForm.processing" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save System Settings') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </LandlordLayout>
</template>
