<script setup>
import { Head } from '@inertiajs/vue3'
import { useI18n } from '@core/Composables/useI18n'

const { t, trans } = useI18n();

defineProps({
    // Add your props here
})
</script>

<template>
    <div>
        <Head :title="trans('module_page_title', { module: 'Settings', page: t('index', 'Index') })" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-surface-card text-text-main overflow-hidden shadow-sm sm:rounded-lg border border-border-subtle">
                    <div class="p-6">
                        <h1 class="text-2xl font-bold">{{ t('index', 'Index') }}</h1>
                        <p class="mt-4">{{ trans('module_scaffold', { module: 'Settings', page: t('index', 'Index') }) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
