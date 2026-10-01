<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import LandlordLayout from '@core/Layouts/LandlordLayout.vue';
import EnterpriseDataGrid, { ColumnDefinition } from '@core/Components/EnterpriseDataGrid.vue';
import StatusBadge from '@core/Components/StatusBadge.vue';
import BadgeCell from '@core/Components/BadgeCell.vue';
import ConfirmDialog from '@core/Components/ConfirmDialog.vue';
import FormModal from '@core/Components/FormModal.vue';
import FormField from '@core/Components/FormField.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import IconButton from '@core/Components/IconButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { Webhook, Trash2, RotateCcw, Power } from 'lucide-vue-next';

interface EndpointItem {
    id: number;
    name: string;
    url: string;
    events: string[];
    active: boolean;
    deliveries_count: number;
    dead_deliveries_count: number;
    created_at: string;
}

const props = defineProps<{
    endpoints: EndpointItem[];
    supported_events: string[];
    can_manage: boolean;
}>();

const { t } = useI18n();

const isCreateModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const target = ref<EndpointItem | null>(null);

const createForm = useForm<{ name: string; url: string; events: string[] }>({
    name: '',
    url: '',
    events: [],
});

const columns = computed<ColumnDefinition[]>(() => [
    { key: 'name', label: t('endpoint', 'Endpoint') },
    { key: 'events', label: t('events', 'Events') },
    { key: 'deliveries_count', label: t('deliveries', 'Deliveries') },
    { key: 'status', label: t('status', 'Status') },
    { key: 'created_at', label: t('created_at', 'Created') },
    { key: 'actions', label: t('actions', 'Actions'), align: 'end' },
]);

function submitCreate() {
    createForm.post('/landlord/webhooks', {
        onSuccess: () => { isCreateModalOpen.value = false; createForm.reset(); },
    });
}

function toggleEndpoint(endpoint: EndpointItem) {
    router.post(`/landlord/webhooks/${endpoint.id}/toggle`);
}

function rotateSecret(endpoint: EndpointItem) {
    router.post(`/landlord/webhooks/${endpoint.id}/rotate-secret`);
}

function confirmDelete() {
    if (!target.value) return;
    router.delete(`/landlord/webhooks/${target.value.id}`, {
        onSuccess: () => (isDeleteModalOpen.value = false),
    });
}
</script>

<template>
    <LandlordLayout>
        <EnterpriseDataGrid
            :title="t('webhooks', 'Webhooks')"
            :description="t('webhooks_sub', 'Outbound event integrations — signed payloads to external systems.')"
            :columns="columns"
            :rows="endpoints"
            :empty-title="t('no_webhooks', 'No webhook endpoints registered.')"
        >
            <template v-if="can_manage" #actions>
                <BaseButton :icon="Webhook" size="sm" @click="isCreateModalOpen = true">
                    {{ t('add_endpoint', 'Add Endpoint') }}
                </BaseButton>
            </template>

            <template #cell-name="{ row }">
                <div>
                    <div class="font-semibold text-text-main">{{ row.name }}</div>
                    <div class="text-xs text-text-muted font-mono">{{ row.url }}</div>
                </div>
            </template>
            <template #cell-events="{ row }">
                <div class="flex flex-wrap gap-1">
                    <BadgeCell v-for="e in row.events" :key="e" variant="info">{{ e }}</BadgeCell>
                </div>
            </template>
            <template #cell-deliveries_count="{ row }">
                <span class="text-text-main">{{ row.deliveries_count }}</span>
                <span v-if="row.dead_deliveries_count" class="text-xs text-danger-fg ms-1">
                    ({{ row.dead_deliveries_count }} {{ t('dead', 'dead') }})
                </span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.active ? 'active' : 'inactive'" />
            </template>
            <template #cell-actions="{ row }">
                <div v-if="can_manage" class="flex items-center justify-end gap-1">
                    <IconButton
                        :icon="Power"
                        :variant="row.active ? 'warning' : 'success'"
                        :title="row.active ? t('deactivate', 'Deactivate') : t('activate', 'Activate')"
                        @click="toggleEndpoint(row)"
                    />
                    <IconButton
                        :icon="RotateCcw"
                        variant="secondary"
                        :title="t('rotate_secret', 'Rotate Secret')"
                        @click="rotateSecret(row)"
                    />
                    <IconButton
                        :icon="Trash2"
                        variant="danger"
                        :title="t('delete', 'Delete')"
                        @click="target = row; isDeleteModalOpen = true"
                    />
                </div>
            </template>
        </EnterpriseDataGrid>

        <FormModal
            :is-open="isCreateModalOpen"
            :title="t('add_endpoint', 'Add Endpoint')"
            :loading="createForm.processing"
            @close="isCreateModalOpen = false"
            @submit="submitCreate"
        >
            <FormField
                v-model="createForm.name"
                :label="t('name', 'Name')"
                :error="createForm.errors.name"
                size="sm"
                required
            />
            <FormField
                v-model="createForm.url"
                type="url"
                :label="t('endpoint_url', 'Endpoint URL (HTTPS only)')"
                :error="createForm.errors.url"
                :hint="t('endpoint_url_hint', 'Deliveries are signed with the endpoint secret via X-Webhook-Signature.')"
                size="sm"
                required
            />
            <div>
                <span class="block text-xs font-semibold text-text-muted mb-2">{{ t('events', 'Events') }}</span>
                <div class="grid grid-cols-2 gap-2">
                    <label
                        v-for="e in supported_events"
                        :key="e"
                        class="flex items-center gap-2 text-sm text-text-main"
                    >
                        <input
                            type="checkbox"
                            :value="e"
                            v-model="createForm.events"
                            class="rounded border-border-strong text-primary-600"
                        />
                        <span class="font-mono text-xs">{{ e }}</span>
                    </label>
                </div>
                <p v-if="createForm.errors.events" class="mt-1 text-xs text-danger-fg">{{ createForm.errors.events }}</p>
            </div>
        </FormModal>

        <ConfirmDialog
            :is-open="isDeleteModalOpen"
            :title="t('confirm_delete_webhook_title', 'Delete Webhook Endpoint?')"
            :message="t('confirm_delete_webhook_message', 'Deliveries to this endpoint stop immediately; pending retries are discarded with the registry row.')"
            :confirm-text="t('delete_forever', 'Delete Forever')"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="confirmDelete"
        />
    </LandlordLayout>
</template>
