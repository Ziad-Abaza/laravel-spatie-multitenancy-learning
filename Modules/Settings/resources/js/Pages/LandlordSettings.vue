<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Settings, Palette, Globe, Server, Save, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps<{
    branding: Record<string, any>;
    theme: Record<string, any>;
    localization: Record<string, any>;
    system: Record<string, any>;
}>();

const { t } = useI18n();

const activeTab = ref<'branding' | 'theme' | 'localization' | 'system'>('branding');

const brandingForm = useForm({
    domain: 'branding',
    settings: {
        app_name: props.branding.app_name || 'SaaS Cloud',
        support_email: props.branding.support_email || 'support@saas.test',
        tagline: props.branding.tagline || 'Multi-Database Enterprise SaaS',
    },
});

const themeForm = useForm({
    domain: 'theme',
    settings: {
        default_palette: props.theme.default_palette || 'indigo',
        default_mode: props.theme.default_mode || 'dark',
    },
});

const localizationForm = useForm({
    domain: 'localization',
    settings: {
        default_locale: props.localization.default_locale || 'en',
        enable_arabic: props.localization.enable_arabic ?? true,
    },
});

const systemForm = useForm({
    domain: 'system',
    settings: {
        allow_registration: props.system.allow_registration ?? true,
        tenant_db_prefix: props.system.tenant_db_prefix || 'tenant_',
        default_trial_days: props.system.default_trial_days || 14,
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

const palettes = ['indigo', 'emerald', 'violet', 'amber', 'cyan', 'rose'] as const;
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6 max-w-4xl mx-auto">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">{{ t('platform_settings', 'Platform Central Settings') }}</h1>
                <p class="text-xs text-slate-400 mt-1">{{ t('platform_settings_sub', 'Configure platform-wide branding, system themes, localization, and multi-tenant provisioning.') }}</p>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center gap-2 border-b border-slate-800 pb-2">
                <button
                    type="button"
                    @click="activeTab = 'branding'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all"
                    :class="activeTab === 'branding' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                >
                    <Settings class="w-4 h-4" />
                    <span>{{ t('branding', 'Branding') }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'theme'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all"
                    :class="activeTab === 'theme' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                >
                    <Palette class="w-4 h-4" />
                    <span>{{ t('theme', 'Theming & Tokens') }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'localization'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all"
                    :class="activeTab === 'localization' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                >
                    <Globe class="w-4 h-4" />
                    <span>{{ t('localization', 'Localization') }}</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'system'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all"
                    :class="activeTab === 'system' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                >
                    <Server class="w-4 h-4" />
                    <span>{{ t('system', 'System & Tenancy') }}</span>
                </button>
            </div>

            <!-- Branding Tab -->
            <div v-if="activeTab === 'branding'" class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6">
                <form @submit.prevent="saveBranding" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Platform Name</label>
                        <input v-model="brandingForm.settings.app_name" type="text" class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Platform Tagline</label>
                        <input v-model="brandingForm.settings.tagline" type="text" class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Support Contact Email</label>
                        <input v-model="brandingForm.settings.support_email" type="email" class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end">
                        <button type="submit" :disabled="brandingForm.processing" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-2">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save Branding') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Theme Tab -->
            <div v-if="activeTab === 'theme'" class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6">
                <form @submit.prevent="saveTheme" class="space-y-6">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-3">Default Color Palette</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <label
                                v-for="p in palettes"
                                :key="p"
                                class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer capitalize text-xs"
                                :class="themeForm.settings.default_palette === p ? 'bg-indigo-500/20 border-indigo-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-300'"
                            >
                                <input type="radio" v-model="themeForm.settings.default_palette" :value="p" class="sr-only" />
                                <span
                                    class="w-4 h-4 rounded-full"
                                    :class="{
                                        'bg-indigo-500': p === 'indigo',
                                        'bg-emerald-500': p === 'emerald',
                                        'bg-violet-500': p === 'violet',
                                        'bg-amber-500': p === 'amber',
                                        'bg-cyan-500': p === 'cyan',
                                        'bg-rose-500': p === 'rose',
                                    }"
                                />
                                <span>{{ p }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">Default Theme Mode</label>
                        <select v-model="themeForm.settings.default_mode" class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500">
                            <option value="dark">Dark Mode</option>
                            <option value="light">Light Mode</option>
                            <option value="system">System Preference</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end">
                        <button type="submit" :disabled="themeForm.processing" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-2">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save Theme') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Localization Tab -->
            <div v-if="activeTab === 'localization'" class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6">
                <form @submit.prevent="saveLocalization" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Default Locale</label>
                        <select v-model="localizationForm.settings.default_locale" class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500">
                            <option value="en">English (LTR)</option>
                            <option value="ar">العربية - Arabic (RTL)</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" v-model="localizationForm.settings.enable_arabic" class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500" />
                            <span>Enable Arabic (RTL) localization platform-wide</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end">
                        <button type="submit" :disabled="localizationForm.processing" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-2">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save Localization') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Tab -->
            <div v-if="activeTab === 'system'" class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6">
                <form @submit.prevent="saveSystem" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Default Free Trial Duration (Days)</label>
                        <input v-model.number="systemForm.settings.default_trial_days" type="number" min="0" class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Tenant Database Name Prefix</label>
                        <input v-model="systemForm.settings.tenant_db_prefix" type="text" class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs outline-none focus:border-indigo-500 font-mono" />
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" v-model="systemForm.settings.allow_registration" class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500" />
                            <span>Allow Public Self-Service Workspace Registration</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end">
                        <button type="submit" :disabled="systemForm.processing" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-2">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save System Settings') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </LandlordLayout>
</template>
