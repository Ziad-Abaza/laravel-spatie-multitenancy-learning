<script setup lang="ts">
import { computed, watch, onMounted, onUnmounted } from 'vue';
import { X } from 'lucide-vue-next';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        show?: boolean;
        isOpen?: boolean;
        title?: string;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    }>(),
    {
        show: undefined,
        isOpen: undefined,
        title: '',
        maxWidth: 'md',
    }
);

const isVisible = computed(() => (props.isOpen !== undefined ? props.isOpen : props.show ?? false));

const emit = defineEmits<{
    (e: 'close'): void;
}>();

function close() {
    emit('close');
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && isVisible.value) {
        close();
    }
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

watch(
    isVisible,
    (isOpen) => {
        if (typeof document !== 'undefined') {
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }
    }
);

const maxWidthClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isVisible"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="close"
            >
                <div
                    class="relative w-full rounded-2xl bg-slate-900 border border-slate-800 text-slate-100 shadow-2xl p-6 transition-all"
                    :class="maxWidthClasses[maxWidth]"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-5">
                        <h3 class="text-lg font-semibold text-white">
                            <slot name="title">{{ title }}</slot>
                        </h3>
                        <button
                            type="button"
                            class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                            @click="close"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-4">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-end gap-3">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
