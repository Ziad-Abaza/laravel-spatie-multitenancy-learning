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
                <h1 class="text-2xl font-bold text-white tracking-tight">{{ t('system_modules', 'Modular Monolith Modules') }}</h1>
                <p class="text-xs text-slate-400 mt-1">{{ t('system_modules_sub', 'Self-contained domain modules powered by nwidart/laravel-modules.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="mod in modules"
                    :key="mod.name"
                    class="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between hover:border-indigo-500/40 transition-all shadow-xl"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                                <Box class="w-5 h-5" />
                            </div>
                            <span
                                v-if="mod.is_enabled"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" />
                                <span>{{ t('active', 'Active') }}</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-800 text-slate-400 border border-slate-700"
                            >
                                <span>{{ t('disabled', 'Disabled') }}</span>
                            </span>
                        </div>

                        <h2 class="text-lg font-bold text-white">{{ mod.name }}</h2>
                        <p class="text-xs text-slate-400 mt-2 min-h-[36px]">{{ mod.description }}</p>

                        <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center gap-2 text-[11px] font-mono text-slate-500 truncate">
                            <Folder class="w-3.5 h-3.5 shrink-0" />
                            <span class="truncate">{{ mod.path }}</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                        <span v-if="coreModules.includes(mod.lower_name)" class="text-[11px] font-semibold text-indigo-400">
                            {{ t('platform_core', 'Core Foundation') }}
                        </span>
                        <button
                            v-else
                            type="button"
                            @click="toggleModule(mod.name)"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all"
                            :class="
                                mod.is_enabled
                                    ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20'
                                    : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20'
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
