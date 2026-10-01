<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import TenantLayout from '@core/Layouts/TenantLayout.vue';
import PageHeader from '@core/Components/PageHeader.vue';
import Panel from '@core/Components/Panel.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import PalettePicker from '@core/Components/PalettePicker.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Building2, Palette, Save } from 'lucide-vue-next';
import { THEME_PRESETS } from '@core/Stores/useThemeStore';

const props = defineProps<{
    branding: Record<string, any>;
    themeSettings: Record<string, any>;
}>();

const { t } = useI18n();

const brandingForm = useForm({
    domain: 'branding',
    workspace_name: props.branding?.workspace_name || '',
    logo: null as File | null,
    settings: {
        tagline: props.branding?.tagline || '',
    },
});

const themeForm = useForm({
    domain: 'theme',
    settings: {
        palette: props.themeSettings?.palette || 'indigo',
        mode: props.themeSettings?.mode || 'dark',
    },
});

const page = usePage();
const allowedPalettes = new Set<string>((page.props.theme as any)?.palettes ?? THEME_PRESETS.map((p) => p.id));
const palettes = THEME_PRESETS.filter((p) => allowedPalettes.has(p.id));
</script>

<template>
    <TenantLayout>
        <div class="space-y-8 max-w-4xl mx-auto">
            <PageHeader
                :title="t('workspace_settings', 'Workspace Settings')"
                :subtitle="t('workspace_settings_sub', 'Customize branding and theme tokens for your workspace.')"
            />

            <Panel :title="t('branding', 'Workspace Branding')" :icon="Building2">
                <form class="space-y-4" @submit.prevent="brandingForm.post('/settings')">
                    <FormField
                        v-model="brandingForm.workspace_name"
                        :label="t('custom_display_name', 'Custom Display Name')"
                        :placeholder="t('workspace_name_example', 'e.g. My Team Workspace')"
                        :error="brandingForm.errors.workspace_name"
                    />
                    <FormField
                        v-model="brandingForm.settings.tagline"
                        :label="t('workspace_tagline', 'Workspace Tagline / Slogan')"
                        :placeholder="t('workspace_tagline_example', 'e.g. Innovating together')"
                        :error="brandingForm.errors['settings.tagline']"
                    />
                    <div class="flex items-center gap-4">
                        <img
                            v-if="props.branding?.logo_url"
                            :src="props.branding.logo_url"
                            :alt="t('workspace_logo', 'Workspace Logo')"
                            class="w-12 h-12 rounded-xl object-cover border border-border-subtle"
                        />
                        <FormField
                            v-model="brandingForm.logo"
                            :label="t('workspace_logo', 'Workspace Logo')"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            :hint="t('workspace_logo_hint', 'PNG, JPG or WebP, up to 2MB.')"
                            :error="brandingForm.errors.logo"
                            class="flex-1"
                        />
                    </div>

                    <div class="pt-2 flex justify-end">
                        <BaseButton type="submit" :icon="Save" :loading="brandingForm.processing">
                            {{ t('save_changes', 'Save Branding') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>

            <Panel :title="t('theme', 'Workspace Theme & Colors')" :icon="Palette">
                <form class="space-y-6" @submit.prevent="themeForm.post('/settings')">
                    <div>
                        <label class="block text-xs font-medium text-text-main mb-3">{{ t('color_palette', 'Color Palette') }}</label>
                        <PalettePicker v-model="themeForm.settings.palette" :palettes="palettes" />
                    </div>

                    <FormField
                        v-model="themeForm.settings.mode"
                        :label="t('default_theme_mode', 'Workspace Theme Mode')"
                        type="select"
                        :options="{ dark: t('dark_mode', 'Dark Mode'), light: t('light_mode', 'Light Mode'), system: t('system_preference', 'System Preference') }"
                        :error="themeForm.errors['settings.mode']"
                    />

                    <div class="pt-2 flex justify-end">
                        <BaseButton type="submit" :icon="Save" :loading="themeForm.processing">
                            {{ t('save_changes', 'Save Theme') }}
                        </BaseButton>
                    </div>
                </form>
            </Panel>
        </div>
    </TenantLayout>
</template>
