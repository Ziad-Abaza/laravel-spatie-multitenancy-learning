<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import type { FunctionalComponent } from 'vue';
import {
    ArrowLeft,
    Ban,
    CloudOff,
    Compass,
    FileQuestion,
    Gauge,
    Home,
    Hourglass,
    LockKeyhole,
    LogIn,
    RefreshCw,
    ServerCrash,
    ShieldX,
    Timer,
    TimerReset,
    TriangleAlert,
    Wrench,
} from 'lucide-vue-next';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import { useI18n } from '../Composables/useI18n';

type ErrorTone = 'danger' | 'warning' | 'primary';
type ErrorAction = 'home' | 'back' | 'reload' | 'login';

interface ErrorSpec {
    icon: FunctionalComponent;
    tone: ErrorTone;
    titleKey: string;
    messageKey: string;
    hintKey?: string;
    actions: ErrorAction[];
}

const props = withDefaults(defineProps<{
    status?: number;
    message?: string | null;
    exception?: string | null;
    loginUrl?: string;
    homeUrl?: string;
}>(), {
    status: 500,
    message: null,
    exception: null,
    loginUrl: '/login',
    homeUrl: '/',
});

const { t } = useI18n();
const page = usePage();

const statusMap: Record<number, ErrorSpec> = {
    400: { icon: FileQuestion, tone: 'warning', titleKey: 'error_400_title', messageKey: 'error_400_message', actions: ['home', 'back'] },
    401: { icon: LockKeyhole, tone: 'warning', titleKey: 'error_401_title', messageKey: 'error_401_message', actions: ['login', 'home'] },
    403: { icon: ShieldX, tone: 'danger', titleKey: 'error_403_title', messageKey: 'error_403_message', actions: ['home', 'back'] },
    404: { icon: Compass, tone: 'primary', titleKey: 'error_404_title', messageKey: 'error_404_message', actions: ['home', 'back'] },
    405: { icon: Ban, tone: 'warning', titleKey: 'error_405_title', messageKey: 'error_405_message', actions: ['home', 'back'] },
    408: { icon: Timer, tone: 'warning', titleKey: 'error_408_title', messageKey: 'error_408_message', actions: ['reload', 'home'] },
    419: { icon: TimerReset, tone: 'warning', titleKey: 'error_419_title', messageKey: 'error_419_message', actions: ['reload'] },
    429: { icon: Gauge, tone: 'warning', titleKey: 'error_429_title', messageKey: 'error_429_message', actions: ['reload', 'home'] },
    500: { icon: ServerCrash, tone: 'danger', titleKey: 'error_500_title', messageKey: 'error_500_message', hintKey: 'error_contact_support', actions: ['reload', 'home'] },
    502: { icon: CloudOff, tone: 'danger', titleKey: 'error_502_title', messageKey: 'error_502_message', hintKey: 'error_contact_support', actions: ['reload', 'home'] },
    503: { icon: Wrench, tone: 'primary', titleKey: 'error_503_title', messageKey: 'error_503_message', actions: ['reload', 'home'] },
    504: { icon: Hourglass, tone: 'danger', titleKey: 'error_504_title', messageKey: 'error_504_message', hintKey: 'error_contact_support', actions: ['reload', 'home'] },
};

const defaultSpec: ErrorSpec = {
    icon: TriangleAlert,
    tone: 'danger',
    titleKey: 'error_default_title',
    messageKey: 'error_default_message',
    hintKey: 'error_contact_support',
    actions: ['reload', 'home'],
};

const spec = computed<ErrorSpec>(() => statusMap[props.status] ?? defaultSpec);

const title = computed(() => t(spec.value.titleKey));
const description = computed(() => props.message?.trim() || t(spec.value.messageKey));
const hint = computed(() => (spec.value.hintKey ? t(spec.value.hintKey) : ''));
const headTitle = computed(() => `${props.status} — ${title.value}`);

const toneClasses: Record<ErrorTone, { iconWrap: string; code: string }> = {
    danger: {
        iconWrap: 'bg-danger/10 text-danger-fg',
        code: 'from-danger to-warning',
    },
    warning: {
        iconWrap: 'bg-warning/10 text-warning-fg',
        code: 'from-warning to-accent-500',
    },
    primary: {
        iconWrap: 'bg-primary-500/10 text-primary-600 dark:text-primary-400',
        code: 'from-primary-600 via-secondary-500 to-accent-500',
    },
};

const actionIcons: Record<ErrorAction, FunctionalComponent> = {
    home: Home,
    back: ArrowLeft,
    reload: RefreshCw,
    login: LogIn,
};

const actionLabelKeys: Record<ErrorAction, string> = {
    home: 'error_back_home',
    back: 'error_go_back',
    reload: 'error_reload',
    login: 'error_sign_in',
};

const appName = computed(() => {
    const branding = (page.props as any).branding || {};
    return branding.app_name || 'SaaS Platform';
});

function buttonClass(index: number): string {
    return index === 0
        ? 'inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary font-semibold text-sm shadow-lg shadow-primary-600/30 transition-colors'
        : 'inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-surface-input hover:bg-surface-hover border border-border-subtle text-text-main font-semibold text-sm transition-colors';
}

function goBack(): void {
    window.history.back();
}

function reload(): void {
    window.location.reload();
}
</script>

<template>
    <div class="min-h-screen bg-surface-bg text-text-main flex flex-col antialiased">
        <Head :title="headTitle" />

        <div class="flex items-center justify-between px-6 py-5">
            <span class="font-bold text-sm text-text-muted tracking-tight">{{ appName }}</span>
            <LanguageSwitcher />
        </div>

        <main class="flex-1 flex items-center justify-center p-6">
            <div class="text-center max-w-lg w-full bg-surface-card border border-border-subtle rounded-3xl px-8 py-12 shadow-2xl">
                <div
                    class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6"
                    :class="toneClasses[spec.tone].iconWrap"
                >
                    <component :is="spec.icon" class="w-8 h-8" />
                </div>

                <div
                    class="text-6xl sm:text-7xl font-extrabold leading-none bg-gradient-to-r bg-clip-text text-transparent mb-4 tracking-tight"
                    :class="toneClasses[spec.tone].code"
                >
                    {{ status }}
                </div>

                <h1 class="text-xl sm:text-2xl font-bold text-text-main mb-3">{{ title }}</h1>

                <p class="text-sm text-text-muted leading-relaxed mb-2">{{ description }}</p>

                <p v-if="exception" class="text-xs text-text-subtle font-mono bg-surface-input border border-border-subtle rounded-lg px-3 py-2 mt-4 mb-2 break-words" dir="ltr">
                    {{ exception }}
                </p>

                <p v-if="hint" class="text-xs text-text-subtle mb-8 mt-4">{{ hint }}</p>
                <div v-else class="mb-8"></div>

                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <template v-for="(action, index) in spec.actions" :key="action">
                        <a v-if="action === 'home'" :href="homeUrl" :class="buttonClass(index)">
                            <component :is="actionIcons[action]" class="w-4 h-4" />
                            {{ t(actionLabelKeys[action]) }}
                        </a>
                        <a v-else-if="action === 'login'" :href="loginUrl" :class="buttonClass(index)">
                            <component :is="actionIcons[action]" class="w-4 h-4" />
                            {{ t(actionLabelKeys[action]) }}
                        </a>
                        <button v-else type="button" :class="buttonClass(index)" @click="action === 'back' ? goBack() : reload()">
                            <component :is="actionIcons[action]" class="w-4 h-4" :class="{ 'rtl:-scale-x-100': action === 'back' }" />
                            {{ t(actionLabelKeys[action]) }}
                        </button>
                    </template>
                </div>
            </div>
        </main>
    </div>
</template>
