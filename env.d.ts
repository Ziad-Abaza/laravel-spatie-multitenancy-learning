/// <reference types="vite/client" />

export interface SharedTenantData {
    id?: number;
    name?: string;
    slug?: string;
    domain?: string;
    status?: string;
    plan?: {
        id?: number;
        name?: string;
        slug?: string;
        limits?: Record<string, unknown>;
    } | null;
    branding?: Record<string, unknown>;
    [key: string]: unknown;
}

export interface SharedSystemData {
    allow_registration?: boolean;
    [key: string]: unknown;
}

export interface SharedAuthUser {
    id?: number;
    name?: string;
    email?: string;
    is_landlord?: boolean;
    status?: string;
    roles?: string[];
    permissions?: string[];
}

export interface SharedAuthData {
    user?: SharedAuthUser | null;
    isLandlord?: boolean;
}

export interface SharedLocaleData {
    current?: string;
    is_rtl?: boolean;
    supported?: Record<string, string>;
    translations?: Record<string, string>;
}

export interface SharedThemeData {
    theme: string;
    palette: string;
    mode: string;
    palettes: string[];
    font: string;
}

export interface SharedFlashData {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
}

export interface SharedBrandingData {
    app_name?: string;
    tagline?: string;
    support_email?: string;
    workspace_name?: string;
    [key: string]: unknown;
}

export interface SharedBillingData {
    currency: string;
}

export interface AppSharedPageProps {
    auth: SharedAuthData;
    tenant: SharedTenantData | null;
    branding: SharedBrandingData;
    system: SharedSystemData;
    billing: SharedBillingData;
    locale: SharedLocaleData;
    theme: SharedThemeData;
    flash: SharedFlashData;
    [key: string]: unknown;
}

declare module '@inertiajs/core' {
    interface PageProps {
        auth?: SharedAuthData;
        tenant?: SharedTenantData | null;
        branding?: SharedBrandingData;
        system?: SharedSystemData;
        billing?: SharedBillingData;
        locale?: SharedLocaleData;
        theme?: SharedThemeData;
        flash?: SharedFlashData;
        [key: string]: unknown;
    }

    interface InertiaConfig {
        sharedPageProps: AppSharedPageProps;
    }
}

declare module '*.vue' {
    import type { DefineComponent } from 'vue';

    const component: DefineComponent<Record<string, never>, Record<string, never>, any>;

    export default component;
}
