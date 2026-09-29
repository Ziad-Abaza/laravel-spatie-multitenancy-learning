<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Palette, Moon, Sun } from 'lucide-vue-next';
import { useThemeStore, type ThemePalette, type ThemeMode } from '../Stores/useThemeStore';

const themeStore = useThemeStore();
const isOpen = ref(false);

onMounted(() => {
    themeStore.initTheme();
});

const palettes: { id: ThemePalette; name: string; bg: string }[] = [
    { id: 'indigo', name: 'Indigo', bg: 'bg-indigo-500' },
    { id: 'emerald', name: 'Emerald', bg: 'bg-emerald-500' },
    { id: 'violet', name: 'Violet', bg: 'bg-violet-500' },
    { id: 'amber', name: 'Amber', bg: 'bg-amber-500' },
    { id: 'cyan', name: 'Cyan', bg: 'bg-cyan-500' },
    { id: 'rose', name: 'Rose', bg: 'bg-rose-500' },
];

function selectPalette(palette: ThemePalette) {
    themeStore.applyTheme(palette);
    isOpen.value = false;
}

function toggleMode() {
    const nextMode: ThemeMode = themeStore.currentMode === 'dark' ? 'light' : 'dark';
    themeStore.applyMode(nextMode);
}
</script>

<template>
    <div class="relative inline-flex items-center gap-1.5">
        <!-- Palette Dropdown Button -->
        <button
            type="button"
            class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors border border-transparent hover:border-slate-700/60"
            title="Choose Theme"
            @click="isOpen = !isOpen"
        >
            <Palette class="w-4 h-4" />
        </button>

        <!-- Mode Toggle Button -->
        <button
            type="button"
            class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors border border-transparent hover:border-slate-700/60"
            :title="themeStore.currentMode === 'dark' ? 'Light Mode' : 'Dark Mode'"
            @click="toggleMode"
        >
            <Sun v-if="themeStore.currentMode === 'dark'" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4 text-indigo-400" />
        </button>

        <!-- Palette Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute end-0 top-full mt-2 w-48 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-2 z-50 animate-in fade-in zoom-in-95 duration-100"
        >
            <div class="px-2.5 py-1 text-xs font-semibold text-slate-400 tracking-wider uppercase">
                Color Palette
            </div>
            <div class="grid grid-cols-3 gap-1.5 p-1">
                <button
                    v-for="p in palettes"
                    :key="p.id"
                    type="button"
                    class="flex flex-col items-center p-2 rounded-xl hover:bg-slate-800 transition-colors border text-xs text-slate-300"
                    :class="themeStore.currentTheme === p.id ? 'border-indigo-500 bg-slate-800/80 font-medium' : 'border-transparent'"
                    @click="selectPalette(p.id)"
                >
                    <span class="w-5 h-5 rounded-full mb-1 shadow-sm" :class="p.bg" />
                    <span class="text-[11px]">{{ p.name }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
