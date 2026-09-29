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
        <Head :title="trans('module_page_title', { module: 'Subscription', page: t('create', 'Create') })" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-surface-card text-text-main overflow-hidden shadow-sm sm:rounded-lg border border-border-subtle">
                    <div class="p-6">
                        <h1 class="text-2xl font-bold">{{ t('create', 'Create') }}</h1>
                        <p class="mt-4">{{ trans('module_scaffold', { module: 'Subscription', page: t('create', 'Create') }) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
