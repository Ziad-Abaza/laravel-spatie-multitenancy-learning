<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import GuestLayout from '@core/Layouts/GuestLayout.vue';
import BaseButton from '@core/Components/BaseButton.vue';
import { useI18n } from '@core/Composables/useI18n';
import { MailCheck, Send, LogOut } from 'lucide-vue-next';

const page = usePage();
const { t } = useI18n();

const tenant = computed(() => page.props.tenant);
const status = computed(() => page.props.status as string | undefined);

const form = useForm({});

function resend() {
    form.post('/email/verification-notification');
}
</script>

<template>
    <GuestLayout>
        <div class="py-16 sm:py-24 flex items-center justify-center px-4">
            <div class="w-full max-w-md bg-surface-card border border-border-subtle rounded-3xl p-8 sm:p-10 shadow-2xl backdrop-blur-xl">
                <div class="text-center mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-primary-500/10 border border-primary-500/20 text-primary-600 dark:text-primary-400 mx-auto flex items-center justify-center mb-4">
                        <MailCheck class="w-6 h-6" />
                    </div>
                    <h1 class="text-2xl font-bold text-text-main tracking-tight">
                        {{ t('verify_email', 'Verify Your Email') }}
                    </h1>
                    <p class="text-xs text-text-muted mt-2 leading-relaxed">
                        {{ t('verify_email_sub', 'We sent a verification link to your email address. Confirm it to access your workspace.') }}
                    </p>
                </div>

                <div v-if="status" class="mb-4 rounded-xl border border-primary-500/30 bg-primary-500/10 px-4 py-3 text-xs text-primary-600 dark:text-primary-300">
                    {{ status }}
                </div>

                <form @submit.prevent="resend" class="space-y-5">
                    <BaseButton type="submit" :loading="form.processing" :icon="Send" class="w-full !py-3 shadow-lg shadow-primary-600/30">
                        {{ form.processing ? t('sending', 'Sending...') : t('resend_verification', 'Resend Verification Email') }}
                    </BaseButton>
                </form>

                <div class="pt-4 mt-4 border-t border-border-subtle text-center">
                    <Link href="/logout" method="post" as="button" class="text-xs text-text-muted hover:text-text-main inline-flex items-center gap-1.5">
                        <LogOut class="w-3.5 h-3.5" />
                        <span>{{ t('logout', 'Log Out') }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
