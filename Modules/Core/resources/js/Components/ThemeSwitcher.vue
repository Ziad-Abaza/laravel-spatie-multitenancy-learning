<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Palette, Moon, Sun } from 'lucide-vue-next';
import { useThemeStore, THEME_PRESETS, type ThemePalette, type ThemeMode } from '../Stores/useThemeStore';
import { useI18n } from '../Composables/useI18n';

const themeStore = useThemeStore();
const { t } = useI18n();
const isOpen = ref(false);
const palettes = THEME_PRESETS;

function selectPalette(palette: ThemePalette) {
    themeStore.applyTheme(palette);
    isOpen.value = false;

    router.post('/theme', { theme: palette }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function toggleMode() {
    const nextMode: ThemeMode = themeStore.currentMode === 'dark' ? 'light' : 'dark';
    themeStore.applyMode(nextMode);

    router.post('/theme', { mode: nextMode }, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="relative inline-flex items-center gap-1.5">
        <!-- Palette Dropdown Button -->
        <button
            type="button"
            class="p-2 rounded-xl text-text-muted hover:text-text-main hover:bg-surface-hover transition-colors border border-transparent hover:border-border-subtle"
            :title="t('choose_theme', 'Choose Theme')"
            @click="isOpen = !isOpen"
        >
            <Palette class="w-4 h-4" />
        </button>

        <!-- Mode Toggle Button -->
        <button
            type="button"
            class="p-2 rounded-xl text-text-muted hover:text-text-main hover:bg-surface-hover transition-colors border border-transparent hover:border-border-subtle"
            :title="themeStore.currentMode === 'dark' ? t('light_mode', 'Light Mode') : t('dark_mode', 'Dark Mode')"
            @click="toggleMode"
        >
            <Sun v-if="themeStore.currentMode === 'dark'" class="w-4 h-4 text-accent-500" />
            <Moon v-else class="w-4 h-4 text-primary-600" />
        </button>

        <!-- Palette Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute end-0 top-full mt-2 w-48 rounded-2xl bg-surface-card border border-border-subtle shadow-2xl p-2 z-50"
        >
            <div class="px-2.5 py-1 text-xs font-semibold text-text-muted tracking-wider uppercase">
                {{ t('color_palette', 'Color Palette') }}
            </div>
            <div class="grid grid-cols-3 gap-1.5 p-1">
                <button
                    v-for="p in palettes"
                    :key="p.id"
                    type="button"
                    class="flex flex-col items-center p-2 rounded-xl hover:bg-surface-hover transition-colors border text-xs text-text-muted hover:text-text-main"
                    :class="themeStore.currentTheme === p.id ? 'border-primary-500 bg-surface-hover font-medium text-text-main' : 'border-transparent'"
                    @click="selectPalette(p.id)"
                >
                    <span class="flex mb-1.5">
                        <span
                            v-for="(c, i) in p.colors"
                            :key="c"
                            class="w-4 h-4 rounded-full shadow-sm border border-black/10"
                            :class="i > 0 ? '-ms-1.5' : ''"
                            :style="{ backgroundColor: c }"
                        />
                    </span>
                    <span class="text-[11px]">{{ p.name }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
