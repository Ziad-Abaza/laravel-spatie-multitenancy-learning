<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Building2, Palette, Save } from 'lucide-vue-next';

const props = defineProps<{
    branding: Record<string, any>;
    theme: Record<string, any>;
}>();

const { t } = useI18n();

const brandingForm = useForm({
    domain: 'branding',
    settings: {
        workspace_name: props.branding?.workspace_name || props.branding?.company_name || '',
        tagline: props.branding?.tagline || '',
    },
});

const themeForm = useForm({
    domain: 'theme',
    settings: {
        palette: props.theme?.palette || props.theme?.theme || 'indigo',
        mode: props.theme?.mode || 'dark',
    },
});

function saveBranding() {
    brandingForm.post('/settings');
}

function saveTheme() {
    themeForm.post('/settings');
}

const palettes = ['indigo', 'emerald', 'violet', 'amber', 'cyan', 'rose'] as const;
</script>

<template>
    <TenantLayout>
        <div class="space-y-8 max-w-4xl mx-auto">
            <div>
                <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('workspace_settings', 'Workspace Settings') }}</h1>
                <p class="text-xs text-text-muted mt-1">{{ t('workspace_settings_sub', 'Customize branding and theme tokens for your workspace.') }}</p>
            </div>

            <!-- Workspace Branding -->
            <div class="bg-surface-card border border-border-subtle rounded-3xl p-6 sm:p-8 space-y-5 shadow-sm">
                <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                    <Building2 class="w-4 h-4" />
                    <span>{{ t('branding', 'Workspace Branding') }}</span>
                </div>

                <form @submit.prevent="saveBranding" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('custom_display_name', 'Custom Display Name') }}</label>
                        <input
                            v-model="brandingForm.settings.workspace_name"
                            type="text"
                            placeholder="My Team Workspace"
                            class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-main mb-1">{{ t('workspace_tagline', 'Workspace Tagline / Slogan') }}</label>
                        <input
                            v-model="brandingForm.settings.tagline"
                            type="text"
                            placeholder="Innovating together"
                            class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        />
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" :disabled="brandingForm.processing" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save Branding') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Workspace Theme -->
            <div class="bg-surface-card border border-border-subtle rounded-3xl p-6 sm:p-8 space-y-5 shadow-sm">
                <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 text-xs font-bold uppercase tracking-wider">
                    <Palette class="w-4 h-4" />
                    <span>{{ t('theme', 'Workspace Theme & Colors') }}</span>
                </div>

                <form @submit.prevent="saveTheme" class="space-y-6">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-3">{{ t('color_palette', 'Color Palette') }}</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <label
                                v-for="p in palettes"
                                :key="p"
                                class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer capitalize text-xs transition-all"
                                :class="themeForm.settings.palette === p ? 'bg-primary-500/10 border-primary-500 text-text-main font-semibold' : 'bg-surface-input border-border-subtle text-text-muted hover:text-text-main'"
                            >
                                <input type="radio" v-model="themeForm.settings.palette" :value="p" class="sr-only" />
                                <span
                                    class="w-4 h-4 rounded-full shadow-xs"
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
                        <label class="block text-xs font-medium text-text-main mb-2">{{ t('default_theme_mode', 'Workspace Theme Mode') }}</label>
                        <select v-model="themeForm.settings.mode" class="w-full px-4 py-2.5 rounded-xl bg-surface-input border border-border-subtle text-text-main text-xs outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                            <option value="dark">{{ t('dark_mode', 'Dark Mode') }}</option>
                            <option value="light">{{ t('light_mode', 'Light Mode') }}</option>
                            <option value="system">{{ t('system_preference', 'System Preference') }}</option>
                        </select>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" :disabled="themeForm.processing" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors">
                            <Save class="w-4 h-4" />
                            <span>{{ t('save_changes', 'Save Theme') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
