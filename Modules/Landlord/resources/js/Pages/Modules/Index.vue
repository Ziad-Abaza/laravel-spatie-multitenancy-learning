<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Box, Layers, ShieldCheck, Power, Folder } from 'lucide-vue-next';

interface SystemModule {
    name: string;
    lower_name: string;
    description: string;
    is_enabled: boolean;
    priority: number;
    path: string;
}

const props = defineProps<{
    modules: SystemModule[];
}>();

const { t } = useI18n();

const coreModules = ['core', 'landlord', 'access', 'subscription', 'settings', 'tenant'];

function toggleModule(name: string) {
    router.post(`/landlord/modules/${name}/toggle`);
}
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-text-main tracking-tight">{{ t('system_modules', 'Modular Monolith Modules') }}</h1>
                <p class="text-xs text-text-muted mt-1">{{ t('system_modules_sub', 'Self-contained domain modules powered by nwidart/laravel-modules.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="mod in modules"
                    :key="mod.name"
                    class="p-6 rounded-3xl bg-surface-card border border-border-subtle flex flex-col justify-between hover:border-primary-500/40 transition-all shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-xl bg-primary-600/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                                <Box class="w-5 h-5" />
                            </div>
                            <span
                                v-if="mod.is_enabled"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-success/10 text-success-fg border border-success/25"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-success" />
                                <span>{{ t('active', 'Active') }}</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-surface-input text-text-muted border border-border-subtle"
                            >
                                <span>{{ t('disabled', 'Disabled') }}</span>
                            </span>
                        </div>

                        <h2 class="text-lg font-bold text-text-main">{{ mod.name }}</h2>
                        <p class="text-xs text-text-muted mt-2 min-h-[36px]">{{ mod.description }}</p>

                        <div class="mt-4 pt-4 border-t border-border-subtle flex items-center gap-2 text-[11px] font-mono text-text-subtle truncate">
                            <Folder class="w-3.5 h-3.5 shrink-0" />
                            <span class="truncate">{{ mod.path }}</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-border-subtle flex items-center justify-between">
                        <span v-if="coreModules.includes(mod.lower_name)" class="text-[11px] font-semibold text-primary-600 dark:text-primary-400">
                            {{ t('platform_core', 'Core Foundation') }}
                        </span>
                        <button
                            v-else
                            type="button"
                            @click="toggleModule(mod.name)"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all"
                            :class="
                                mod.is_enabled
                                    ? 'bg-danger/10 text-danger-fg border border-danger/25 hover:bg-danger/20'
                                    : 'bg-success/10 text-success-fg border border-success/25 hover:bg-success/20'
                            "
                        >
                            <Power class="w-3.5 h-3.5" />
                            <span>{{ mod.is_enabled ? t('disable', 'Disable') : t('enable', 'Enable') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
