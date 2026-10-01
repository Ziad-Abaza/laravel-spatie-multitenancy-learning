---
noteId: "0b0c4440bd2c11f1b0d837b196dd4a0c"
tags: []

---

# Feature Gap Analysis & Product Capability Audit

> Evidence-based audit. Every capability below was verified against actual code,
> routes, migrations, permissions, UI pages, tests, and configuration.
> Every "missing" claim cites the search/proof used. Nothing here is assumed
> from module names or generic SaaS checklists.
>
> Date: 2026-10-01 · Scope: entire repository

---

## 0. Verified System Baseline

| Layer | Verified implementation |
|---|---|
| Framework | Laravel 13.x on PHP 8.4 (`composer.json`) |
| Tenancy | `spatie/laravel-multitenancy` 4.2 — **multi-database** isolation |
| Modularity | `nwidart/laravel-modules` 13 — 6 enabled modules (`modules_statuses.json`) |
| AuthZ | `spatie/laravel-permission` 8.3, two guards: `web` (tenant), `landlord` |
| Media | `spatie/laravel-medialibrary` 11.23 |
| i18n | `spatie/laravel-translatable` 6.14 + JSON catalogs (en/ar) |
| Frontend | Inertia 3.4 + Vue 3 + Pinia + Tailwind 4 (`package.json`) |
| API surface | **None.** Every `Modules/*/routes/api.php` is an empty stub. No Sanctum/Passport in `composer.json`. |
| Queues | `QUEUE_CONNECTION=database`, tenant-aware via `queues_are_tenant_aware_by_default` (`config/multitenancy.php:90`) |
| Mail | `MAIL_MAILER=log` — **no mailable or notification class exists in the codebase** (grep `Mail::|notify(|Notification::` → zero app hits) |
| Tests | 11 feature tests at `tests/Feature/`; module `tests/` dirs exist but are empty |

Connections: `landlord` (central, default) + `tenant` (repointed per request by `SwitchTenantDatabaseTask`).

---

## 1. Product Capability Inventory

Each entry lists the proof trail: files, routes, permissions, UI, dependencies.

### 1.1 Tenancy Core

**Host → Tenant resolution (fail-closed)**
- `app/TenantFinder/SaaSTenantFinder.php` — exact `domain` match, then `{slug}.{TENANT_DOMAIN_SUFFIX}` slug fallback.
- `app/Http/Middleware/IdentifyTenant.php` — prepended to `web` + `api` groups; landlord hosts (allowlist `multitenancy.landlord_domains`) forget tenant; unknown hosts throw `NoCurrentTenant` → 404.
- `app/Http/Middleware/EnsureTenantIsActive.php` — non-active tenant → `TenantSuspendedException` → 423.
- `app/Http/Middleware/EnsureLandlordContext.php` (alias `landlord`) — platform routes 404 on tenant hosts.
- Switch tasks: `PrefixCacheTask`, `SwitchTenantDatabaseTask`, `Modules\Core\Tasks\ScopePermissionCacheTask` (`config/multitenancy.php:70-75`).
- Tenant-aware queues enabled; `queueable_to_job` mapping covers mailables/notifications/closures/listeners/broadcasts.

**Tenant lifecycle state machine**
- `Modules\Core\Enums\TenantStatus` — `trialing → active/trialing/suspended/archived`, `active → suspended/archived/trialing`, `suspended → active/archived`, `archived` terminal.
- `Modules\Landlord\app\Services\TenantLifecycleService.php` — suspend (with `settings.suspension_reason`), activate, archive, extendTrial, delete (row + optional DB drop).
- Columns: `slug, status, plan_id, settings(json), trial_ends_at, suspended_at` — `Modules/Landlord/database/migrations/2026_09_29_120001_add_saas_fields_to_tenants_table.php`.

**Tenant provisioning (self-serve + admin)**
- `Modules\Landlord\app\Services\TenantProvisioner.php` — landlord transaction (create DB + tenant row + subscription), then tenant migrations (`--database=tenant` guard + `Tenant::checkCurrent()`), then baseline seed (permissions, Owner user, theme settings). Compensation path deletes row + drops DB on failure.
- Routes: public `GET/POST /register-tenant` (`landlord` middleware, `throttle:5,1`, reserved-subdomain + unique-domain validation) and admin `GET/POST /landlord/tenants*` (`tenants.create`).
- UI: `Landlord/Landing/RegisterTenant.vue`, `Landlord/Tenants/{Index,Create,Show}.vue`.

**Admin lifecycle actions** — `Modules\Landlord\app\Http\Controllers\TenantController.php`:
`suspend`, `activate`, `archive`, `plan` (changePlan), `trial` (extendTrial), `cancel-subscription`, `destroy` (optional `drop_database`). All gated by `tenants.lifecycle` / `tenants.update` / `tenants.delete` on guard `landlord`.

### 1.2 Authentication

**Tenant guard `web`**
- `Modules\Access\app\Http\Controllers\TenantAuthController.php` — login (`throttle:6,1`, remember-me), register (gated by `system.allow_registration`, auto Member role, quota via `TenantUserService`), logout with session invalidate + token regenerate.
- UI: `Access/Auth/{Login,Register}.vue`.

**Landlord guard `landlord`**
- `Modules\Landlord\app\Http\Controllers\LandlordAuthController.php` — login `throttle:6,1`, logout.
- `app/Http/Middleware/EnsureLandlordAdminActive.php` (alias `landlord.active`) — suspended admin is logged out mid-request (kills remember-me/stale sessions).
- UI: `Landlord/Auth/Login.vue`.

**Profile self-service**
- `Modules\Access\app\Http\Controllers\ProfileController.php` — name/email/job_title/phone + password change requiring `current_password`. UI: `Access/Profile/Edit.vue`.

### 1.3 Authorization (two-guard RBAC)

- Permission manifests (declaration only, never runtime): `Modules\Access\app\Support\TenantPermissions.php` (11 keys, roles Owner/Admin/Member) and `LandlordPermissions.php` (19 keys, roles Super Admin/Support, read/write/sensitive classification).
- `Modules\Access\app\Services\AccessBaselineProvisioner.php` — idempotent catalog+role materialization per database; `is_system` flag on Super Admin.
- `Modules\Access\app\Services\ManagementPolicy.php` — delegation ceiling by **effective permission coverage** (not role names): `assertCanManage`, `assertRoleGrantable`, `assertAssignableRole` (resulting-set check closes grant-then-escalate), `assertPermissionsWithinScope`, `assertRoleMutable` (system roles immutable), `assertRoleDeletable` (409 role-in-use), `assertNotSelfMutation`.
- `Modules\Access\app\Services\AccessInvariants.php` — "≥1 active management principal" invariant, `lockForUpdate` inside mutation transactions.
- `Modules\Access\app\Services\AuditWriter.php` + `Modules\Landlord\app\Models\AdminAuditLog.php` — append-only landlord-context audit (actor/action/target/before/after/ip); throws `LogicException` inside tenant context.
- Enforcement: `permission:` middleware on every protected route; scoped permission cache via `ScopePermissionCacheTask`.
- UI: `Access/Users/Index.vue`, `Access/Roles/Index.vue`, `Landlord/Admins/Index.vue`, `Landlord/Roles/Index.vue`.
- Console: `access:sync-tenants`, `access:sync-landlord [--repair]` (`Modules/Access/app/Console/`).
- Landlord admin management: `LandlordAdminController` — create/update/assign-role/reset-password/suspend/reactivate/delete, each audited (`admin.*` actions).

### 1.4 Subscription & Entitlements (internal billing records)

- `Modules\Subscription\app\Models\Plan.php` — translatable name/description, `price`, `currency`, `billing_interval`, `trial_days`, `limits` JSON (`max_users`, `max_storage_mb`, `features[]` incl. `*`), `sort_order`, `is_active`.
- `Modules\Subscription\app\Models\Subscription.php` — status enum (`trialing/active/past_due/expired/canceled`), interval, amount, trial/starts/ends/canceled_at.
- `Modules\Subscription\app\Services\SubscriptionService.php` — subscribeTenant, changePlan, cancelSubscription; emits `SubscriptionCreated/Updated`, `PlanChanged` events.
- `Modules\Subscription\app\Services\QuotaService.php` (implements `QuotaManagerContract`) — `canAddUser`, `canUseFeature` (→ `Plan::hasFeature`), user/storage limits; storage usage summed from tenant `media` table.
- Quota actually enforced at user creation (`TenantUserService::createUser` throws on limit).
- Routes: landlord `plans.*` CRUD + `subscriptions.index`; tenant `subscription.overview`, `subscription.change-plan` (`subscription.view/manage`).
- UI: `Subscription/{Index,Create,Edit,Show,Plans,Overview,LandlordSubscriptions}.vue`, `PlanFormFields.vue`, public `Landlord/Landing/Pricing.vue`.

### 1.5 Settings Governance

- Registry SSoT `Modules\Settings\config\settings.php` — domains `branding/theme/localization/system/billing`, per-key `owner` (landlord/tenant/shared) + type + rules; **unlisted keys cannot be written** (`InvalidArgumentException`).
- `Modules\Settings\app\Services\SettingService.php` — two-tier storage (`settings` landlord / `tenant_settings` tenant), tenant overrides landlord, `Cache::rememberForever` per scope+domain with structural invalidation on model save/delete.
- `getTheme()` (session > tenant > landlord > enum default), `getBranding()`, `supportedLocales()`, `defaultLocale()`.
- UI: `Settings/{LandlordSettings,TenantSettings}.vue`; routes gated by `platform.settings.*` / `settings.*`.

### 1.6 i18n / RTL / Theming

- `app/Http/Middleware/SetLocale.php` — query > session > Accept-Language > default, restricted to `supported_locales`.
- `HandleInertiaRequests::translationsFor()` — ships global + Core + current-module JSON only.
- RTL via `Locale::isRtl()`, font switch cairo/inter; `ThemeMode`, `ThemePalette` enums; session-level theme override; Pinia `useThemeStore` + `setupInertiaStateBridge`; `LanguageSwitcher`, `ThemeSwitcher`, `PalettePicker`.
- Routes: `POST /locale`, `POST /theme`.

### 1.7 Dashboards & Metrics

- `LandlordMetricsService` → `Landlord/Pages/Dashboard.vue`: total/active/suspended tenants, active+trialing subs, MRR (yearly normalized), plan distribution, recent tenants.
- `TenantDashboardController` → `Tenant/Dashboard.vue`: user/storage quota %, plan, trial end, recent users.
- Tenant detail page embeds a cross-DB user listing via `$tenant->execute()` (`TenantController::show`, limit 50).

### 1.8 Module Administration

- `ModuleManagementController` — list nwidart modules (name/enabled/priority), toggle non-locked modules; `LOCKED_MODULES` SSoT covers all six current modules (so toggling is effectively dead code today but works for future modules). Gated by `modules.view/manage`.

### 1.9 Files & Media

- `spatie/laravel-medialibrary` configured; `app/Support/TenantAwarePathGenerator.php`.
- `Modules\Landlord\Models\Tenant` — `HasMedia`, `logo` collection consumed by `SettingService::getBranding()`.
- `Modules\Access\Models\User` — `avatars` collection + gravatar fallback (`getAvatarUrl`).

### 1.10 Error & Edge Handling

- `app/Support/ErrorPageRenderer.php` — unified Inertia `Core/ErrorPage` for all HTML 4xx/5xx; JSON for API/expectsJson. `TenantSuspendedException` → 423, `NoCurrentTenant` → 404.
- Health endpoint `/up` (`bootstrap/app.php:27`).

### 1.11 Operations Tooling

- `db:rebuild [--all|--tenant=*]` (`RebuildDatabasesCommand`) — guarded `migrate:fresh --seed` across both contexts.
- `access:sync-tenants`, `access:sync-landlord --repair`.
- Context-aware `DatabaseSeeder` / `TenantSeeder` / `LandlordDatabaseSeeder` / `PlanSeeder` (Starter 0$/5u/1GB, Pro 29$/25u/10GB, Enterprise 99$/9999u/100GB+`*`).
- Seed credentials env-gated (`SEED_*_PASSWORD`, no predictable password outside local).

### 1.12 Shared UI Component Library

23 components in `Modules/Core/resources/js/Components/` (mandated by AGENTS.md §6.1): PageHeader, Panel, FormField, BaseButton, IconButton, FormModal, ConfirmDialog, DataTable, EnterpriseDataGrid, EnterpriseFormEngine, FilterSelect, TabNav, PalettePicker, Modal, EmptyState, SkeletonLoader, StatusBadge, BadgeCell, CurrencyCell, StatCard, PermissionBundlePicker, LanguageSwitcher, ThemeSwitcher.

---

## 2. Product Mapping (requested domain map vs evidence)

| Domain | Status | Evidence |
|---|---|---|
| Authentication | ✅ Present | Dual guards, throttled login, register, logout, profile+password change. **No password-reset flow, no email verification, no 2FA.** |
| Authorization | ✅ Present (strong) | Two-guard RBAC, coverage-based delegation, system roles, last-manager invariant, sync commands. |
| Tenancy | ✅ Present (strong) | Multi-DB, fail-closed finder, lifecycle state machine, provisioning with compensation. |
| CRM | ❌ Absent | No leads/contacts/deals anywhere (no module, table, or page). |
| Finance | ❌ Absent | Only plan price + MRR metric; **no invoices, payments, transactions, payment gateway** (no Cashier/Stripe/Paddle in composer). |
| HR | ❌ Absent | User directory only (job_title/phone fields). No attendance/leave/payroll. |
| Laboratory | ❌ Absent | No module/routes/tables. |
| Licensing | ⚠️ Partial | Plan `limits.features` + `hasFeature` act as coarse entitlements; no license keys/activations. |
| Quotations | ❌ Absent | Nothing. |
| Settings | ✅ Present (strong) | Governed registry, ownership rules, cache invalidation, landlord+tenant UIs. |
| Platform Management | ✅ Present | Tenants CRUD+lifecycle, admins, roles, modules, platform settings, metrics dashboard. |
| Billing | ⚠️ Partial | Subscription records + plan changes exist; **no charging, invoicing, proration, dunning, or billing portal.** |
| Notifications | ❌ Absent | `MAIL_MAILER=log`; zero Mailable/Notification classes; no in-app bell. |
| Files | ⚠️ Partial | Media library wired (tenant logo, avatars, quota accounting) but **no upload endpoint and no file-manager UI**. |
| Reports | ❌ Absent | Dashboard KPIs only; no report builder/exports. |
| Integrations | ❌ Absent | No API, webhooks, OAuth apps, or third-party connectors. |
| API | ❌ Absent | All `api.php` stubs empty; no tokens (no Sanctum/Passport). |
| Audit | ⚠️ Partial | `admin_audit_logs` covers landlord admin/role mutations only. **No viewer UI, no tenant-side trail, lifecycle actions not audited.** |
| Analytics | ⚠️ Partial | MRR + counts KPIs only; no per-tenant usage analytics, funnels, or dashboards. |

---

## 3. Gap Discovery (absence proven, not assumed)

Proof method per item is cited inline (`grep`/`read` over the repo).

### G1 — No password-reset flow (Critical/Security)
`password_reset_tokens` table exists (tenant migration) and a test proves tokens land in the tenant DB (`TenantAccessControlTest:72`), **but no route, controller, or page requests or consumes a token** — the broker is never invoked outside tests. Landlord admins have only an admin-initiated reset. Locked-out users have no recovery path.

### G2 — No notifications/mail subsystem (Critical/Operational)
`MAIL_MAILER=log`; grep for `Mail::|Notification::|->notify(` finds zero app usages. Consequences: no welcome/provisioned email, no trial-ending alerts, no suspension notices, no admin alerts. `TenantCreated/TenantProvisioned/TenantStatusChanged/Subscription*` events have **zero listeners** (all `EventServiceProvider::$listen = []`).

### G3 — Suspended tenant users can still authenticate (Critical/Security)
`User.status` + `UserStatus` enum exist and admins can set `suspended` (`UserController@update`), and the landlord side has `EnsureLandlordAdminActive` — **but no tenant-side equivalent**: `TenantAuthController::login` calls `Auth::attempt` with no status check, and no middleware blocks suspended tenant users mid-session. A "suspended" workspace member keeps full access. Asymmetric enforcement.

### G4 — No email verification (Security)
`email_verified_at` columns exist on both user tables; no `MustVerifyEmail`, no `VerificationController`, no routes.

### G5 — Tenant-side audit trail absent; landlord audit unreadable (High Value/Compliance)
`AuditWriter` throws inside tenant context by design; `admin_audit_logs` records only `admin.*`/`role.*`/`system.repair` actions. Tenant lifecycle ops (suspend/archive/delete/plan change), tenant user/role mutations, logins — **unaudited**. No audit viewer page exists on either surface.

### G6 — No billing execution (High Value)
Subscriptions are bookkeeping only: no payment provider, invoices, payment methods, proration, `past_due` transition (enum case exists, nothing sets it), or dunning. `Pricing.vue` is a static page; registration creates a subscription without payment. Any real revenue path is missing.

### G7 — No lifecycle automation / scheduler (Critical/Operational)
`routes/console.php` contains only `inspire`. Nothing scheduled: expired trials never transition (`isTrialing()` reads a date but `status` stays `trialing` and access is never revoked), `ends_at` is never enforced, suspended tenants never auto-archive, no cleanup jobs. **Trial expiry currently has zero effect on access.**

### G8 — No API surface at all (Enterprise/Scalability)
All module `api.php` files are empty comments; no Sanctum/Passport; no token management; no versioning. The platform cannot be integrated, automated, or headless-consumed. Tenant provisioning is HTTP-only.

### G9 — No webhooks (in or out) (High Value)
No webhook tables, routes, signatures, or dispatchers. `TenantProvisioned`/`PlanChanged` events already exist — the natural emission points — but nothing leaves the process.

### G10 — Tenant logo upload is a dead capability (Bug-adjacent)
`getBranding()` reads `$tenant->getFirstMediaUrl('logo')` and the model is `HasMedia`, but `TenantSettingsController::update` handles only settings + `workspace_name`, and landlord `update` handles only name/slug/domain. **No endpoint ever writes the `logo` collection.** Same gap pattern for user avatars (collection read by `getAvatarUrl`, no upload route).

### G11 — No support tooling (Operational)
Landlord `Tenants/Show` renders a read-only user list — no impersonation, no support notes, no "login as" (and deliberately no cross-guard bridge). Support staff can view but cannot diagnose inside a workspace.

### G12 — No usage metering beyond two quotas (Enterprise)
Only user-count and storage-MB snapshots. No metering table/events, no API-call metrics, no per-tenant analytics, no usage history (time series).

### G13 — No import/export or backup/restore (Operational/Enterprise)
No export routes (CSV/Excel), no data-portability endpoint, no backup commands. `Pricing.vue` **claims "automated backups"** — false advertising relative to code (nothing backs up tenant DBs).

### G14 — No feature flags beyond plan entitlements (Enterprise)
`Plan::hasFeature` is static entitlement by plan; no per-tenant overrides, no runtime flags, no rollout/kill-switch mechanism. `ModuleManagement` toggles modules **globally**, not per tenant.

### G15 — No per-tenant module/feature entitlements (High Value, architectural)
Module enablement is platform-wide (`modules_statuses.json`); there is no `tenant_modules`/`tenant_features` mapping. Plans declare feature strings that nothing maps to module access — the entitlement and the module systems are disconnected.

### G16 — Observability thin (Operational)
`/up` health + log files only. No structured audit of auth failures (login throttling exists but failed attempts aren't logged/audited), no metrics endpoint, no queue-failure visibility, no Telescope/Horizon.

### G17 — No session/device management for end users (Security)
Users cannot view or revoke their own sessions; no login history; no forced-logout on password change (`ProfileController@update` changes password without `Auth::logoutOtherDevices`).

### G18 — No data retention / GDPR mechanics (Compliance)
Archived tenants keep data forever; no retention policy, export-my-data, or erasure workflow. Deletion is manual (`tenants.destroy` + optional DB drop).

### G19 — Registration has no bot protection or email confirmation (Security)
Public `/register-tenant` + `/register` rely on `throttle` only; no captcha, no disposable-email check, no verification — combined with G4, anyone can provision databases anonymously at `throttle:5,1`.

### G20 — Dead CRUD scaffolding in Tenant module (Code health)
`Modules\Tenant\app\Http\Controllers\TenantController.php` + `Pages/{Index,Create,Edit,Show}.vue` are generated stubs with empty bodies and **no routes** — unreachable dead code that will mislead future contributors.

---

## 4. Classification

| # | Capability | Class |
|---|---|---|
| G1 | Password reset flow | **Critical / Security** |
| G3 | Suspended-user auth block (tenant guard) | **Critical / Security** |
| G7 | Scheduler + lifecycle automation (trial expiry, `past_due`, cleanup) | **Critical / Operational** |
| G2 | Notification subsystem (mail + in-app) | **Critical / Operational** |
| G6 | Billing execution (provider, invoices, dunning) | **High Value** (gate on revenue) |
| G10 | Logo/avatar upload endpoints (finish media wiring) | **High Value** (half-built today) |
| G5 | Tenant-side + lifecycle audit trail & viewer | **High Value / Enterprise** |
| G15 | Per-tenant module/feature entitlements | **High Value / Enterprise** |
| G4 | Email verification | **Security** |
| G17 | Session/device management + logout-other-devices | **Security** |
| G19 | Registration hardening (verification, captcha hook) | **Security** |
| G8 | API foundation (Sanctum tokens, versioned `/api/v1`) | **Enterprise / Scalability** |
| G9 | Outbound webhooks on existing domain events | **Enterprise** |
| G12 | Usage metering (time-series) | **Enterprise** |
| G11 | Support tooling (read-only tenant impersonation w/ audit) | **Operational** |
| G13 | Export + tenant DB backup tooling | **Operational / Compliance** |
| G16 | Observability (auth-failure logging, metrics, queue health) | **Operational** |
| G14 | Runtime feature flags | **Enterprise** (defer — plan entitlements cover most cases) |
| G18 | Data retention & erasure workflows | **Enterprise / Compliance** |
| G20 | Remove dead Tenant-module CRUD stubs | **Developer Experience** |

Not-applicable domains (CRM, HR, Laboratory, Quotations) are business-verticals — excluded from this platform-foundation scope by design, not gaps.

---

## 5. Architecture Compatibility

| Gap | Fits current arch? | Affected modules | Needs migration | Needs permissions | Tenant-aware | Audit | API | Risks |
|---|---|---|---|---|---|---|---|---|
| G1 Password reset | ✅ native `Password` broker already tenant-correct (proven by test) | Access | no (table exists) | no | yes | add `auth.password_reset` | optional | low |
| G3 Suspended-user block | ✅ mirror `EnsureLandlordAdminActive` for `web` guard | Access (+middleware alias) | no | no | yes | — | — | low; must not break `EnsureValidTenantSession` order |
| G7 Scheduler | ✅ `Schedule` in `routes/console.php`; state machine already exists | Landlord/Subscription | no | no | per-tenant loop | yes (`system.*`) | no | medium: transition semantics need care (grace periods) |
| G2 Notifications | ✅ tenant-aware queue config already done; events exist | Core (channels), all modules emit | `notifications` tables ×2 contexts | no | **yes — critical** (notifications DB is per-tenant) | — | no | medium: mail per-tenant config, landlord vs tenant `notifications` table placement |
| G10 Media uploads | ✅ collections already read; quota already counts media bytes | Settings/Landlord | no | `settings.manage` / `platform.settings.manage` reuse | yes | no | no | low: file validation + MEDIA_DISK |
| G6 Billing | ⚠️ needs provider choice (Cashier/Stripe vs internal ledger); AGENTS forbids dependency changes without approval | Subscription | invoices/payments tables | `plans.manage`, new `billing.*` | landlord-side records | yes | future | **high**: money correctness, webhooks, taxes |
| G5 Audit expansion | ✅ pattern exists (`AuditWriter`); needs tenant-context writer (landlord table can't be written inside tenant txn) + viewer page | Access/Landlord | tenant `audit_logs` table + viewer | `audit.view` new keys both guards | yes | self | no | medium: dual-write strategy decision |
| G15 Per-tenant modules | ⚠️ must extend existing module registry, not add a "Resolver" (forbidden); map plan `features` → module gates | Landlord/Core/Subscription | `tenant_modules` pivot | `modules.manage` reuse | yes | yes | no | medium-high: route/menu gating must live in existing module pipeline |
| G8 API | ✅ add Sanctum + `api/v1` per module (stubs already exist) | all | token tables | new `api.*` scope mapping | yes (IdentifyTenant already on api group) | yes | **is** the API | high: surface area, rate limits, token scopes |
| G9 Webhooks | ✅ events exist; add `webhook_endpoints` + queued dispatch (tenant-aware queues ready) | Landlord/Core | webhook tables | `webhooks.manage` | mostly landlord | yes | yes | medium |
| G12 Metering | ✅ add `usage_events` landlord table, record in QuotaService/enforcement points | Subscription/Landlord | usage tables | `metrics.view` | yes | no | future | medium: write volume |
| G11 Impersonation | ⚠️ cross-guard login must be explicit, audited, time-boxed; tenant guard session impersonation needs a dedicated signed flow, **not** a new "resolver" layer | Landlord/Access | `impersonation_tokens` or signed route | `tenants.impersonate` (sensitive) | yes | **mandatory** | no | **high**: privilege-boundary crossing — biggest risk on this list |
| G13 Backup/export | ✅ `mysqldump`/sqlite copy per `tenant.database` + storage archive; admin-triggered | Landlord | `backups` registry | `tenants.export` | yes | yes | no | medium: storage of dumps |
| G16 Observability | ✅ failed-login listener on `Failed` event; Laravel health checks; optional Horizon later | Access/Core | `auth_logs` | `platform.settings.view` reuse | both | — | no | low |
| G17 Sessions | ✅ database session driver already; add session-browser/revoke + `logoutOtherDevices` on password change | Access | maybe `sessions` indexes | self-service | yes | `auth.*` | no | low |
| G19 Reg hardening | ✅ add captcha middleware (e.g. Turnstile) + require verification post-register | Access/Landlord | no | no | both | — | no | low |
| G18 Retention | ✅ policy fields on tenant (retention_until), scheduled purge job, export-before-delete | Landlord | tenant columns | `tenants.lifecycle` reuse | yes | yes | no | medium |
| G20 Dead stubs | ✅ delete files | Tenant | no | no | — | — | — | none |

None of the above requires a Resolver/intermediate layer — each lands inside its owning module consistent with AGENTS.md ("No Resolver or Intermediate Layers").

---

## 6. Priority Matrix

Scored 1–5. Root Value = avg(Business, Architectural, Security). Ranked by **Value ÷ Complexity**, ties broken by risk.

| Gap | Biz | Arch | Sec | Value | Cmplx | Risk | V/C | Rank |
|---|---|---|---|---|---|---|---|---|
| G3 Suspended-user auth block | 4 | 4 | 5 | 4.3 | 1 | 1 | **4.3** | 1 |
| G1 Password reset flow | 5 | 3 | 5 | 4.3 | 2 | 1 | **2.2** | 2 |
| G10 Media upload endpoints | 4 | 4 | 2 | 3.3 | 1 | 1 | **3.3** | 3 |
| G7 Scheduler/lifecycle automation | 5 | 5 | 3 | 4.3 | 2 | 3 | **2.2** | 4 |
| G17 Session management | 3 | 3 | 5 | 3.7 | 2 | 1 | **1.8** | 5 |
| G2 Notification subsystem | 5 | 4 | 3 | 4.0 | 3 | 3 | **1.3** | 6 |
| G5 Audit expansion + viewer | 4 | 4 | 4 | 4.0 | 3 | 2 | **1.3** | 7 |
| G16 Observability basics | 3 | 4 | 4 | 3.7 | 2 | 1 | **1.8** | 8* |
| G19 Registration hardening | 4 | 2 | 4 | 3.3 | 2 | 1 | **1.7** | 9 |
| G4 Email verification | 4 | 2 | 4 | 3.3 | 2 | 2 | **1.7** | 10 |
| G20 Dead-stub cleanup | 2 | 4 | 1 | 2.3 | 1 | 0 | **2.3** | — (hygiene) |
| G9 Webhooks | 4 | 4 | 2 | 3.3 | 3 | 2 | **1.1** | 11 |
| G15 Per-tenant module entitlements | 4 | 5 | 2 | 3.7 | 4 | 3 | **0.9** | 12 |
| G13 Backup/export | 3 | 3 | 3 | 3.0 | 3 | 2 | **1.0** | 13 |
| G18 Retention/erasure | 3 | 3 | 4 | 3.3 | 4 | 3 | **0.8** | 14 |
| G12 Usage metering | 4 | 3 | 2 | 3.0 | 4 | 2 | **0.75** | 15 |
| G11 Impersonation | 3 | 3 | 3 | 3.0 | 4 | 5 | **0.75** | 16 |
| G8 API surface | 4 | 4 | 3 | 3.7 | 5 | 3 | **0.74** | 17 |
| G6 Billing execution | 5 | 3 | 4 | 4.0 | 5 | 5 | **0.8** | 18 (but biz-blocking — schedule by strategy, not ratio) |
| G14 Runtime feature flags | 3 | 3 | 2 | 2.7 | 4 | 3 | **0.67** | 19 |

\* G16 ranks by ratio below G17 but is operationally bundled with G2/G5 work.

**Recommended execution order (ratio-driven):**
1. G3 → G1 → G10 → G7 (cheap, critical, unblocks correctness)
2. G17 → G16 → G2 → G5 (security + operational spine)
3. G19/G4 → G9 → G15 (hardening + enterprise surface)
4. G13/G18/G12 → G11 → G8 (ops + platform API)
5. G6 last — highest value but needs a payment-provider decision and is independent of all others.

---

## 7. Enterprise Capability Checklist — verified verdicts

| Capability | Verdict | Evidence |
|---|---|---|
| Audit Logging | ⚠️ **Partial** | `admin_audit_logs` + `AuditWriter` exist (landlord admin/role ops only). No tenant trail, no lifecycle audit, no viewer UI. |
| Feature Flags | ⚠️ **Partial** | `Plan::hasFeature` / `limits.features` entitlements only; no runtime flags. |
| Entitlements | ⚠️ **Partial** | Plan features + `QuotaService::canUseFeature` exist; not wired to routes/modules (G15). |
| Usage Metering | ⚠️ **Partial** | Live quota reads (users, storage MB); no event metering/history. |
| Tenant Lifecycle | ✅ **Present** | Full state machine + suspend/activate/archive/delete + 423 enforcement. |
| Tenant Provisioning | ✅ **Present** | `TenantProvisioner` — atomic + compensated, self-serve & admin. |
| Tenant Suspension | ✅ **Present** | `tenants.lifecycle`, reason stored, request-time block. |
| Tenant Archival | ✅ **Present** | Terminal `archived` state. |
| SSO | ❌ Absent | No Socialite/SAML packages or routes. |
| SAML | ❌ Absent | Nothing. |
| SCIM | ❌ Absent | Nothing. |
| API Tokens | ❌ Absent | No Sanctum/Passport; no token tables. |
| Webhooks | ❌ Absent | No endpoints or dispatchers. |
| API Versioning | ❌ Absent | No API at all. |
| Import/Export | ❌ Absent | No exporters/importers anywhere. |
| Backup/Restore | ❌ Absent | Nothing — despite marketing claim on `Pricing.vue` ("automated backups"). |
| Tenant Analytics | ⚠️ Partial | Landlord MRR/counts; per-tenant quota % only. No usage trends. |
| Operational Dashboards | ✅ **Present** (basic) | `LandlordMetricsService` dashboard. |
| Support Tools | ❌ Absent | Read-only tenant user list; no impersonation/notes. |
| Security Monitoring | ⚠️ Partial | Login throttling + suspended-admin enforcement; no auth-failure logging/alerting. |
| Compliance Readiness | ❌ Absent | No retention, export, erasure, or consent mechanics. |
| Incident Readiness | ⚠️ Partial | `/up` health endpoint; no status page, alerting, or runbooks. |
| Observability | ❌ Absent (basics) | Log files only; no metrics, tracing, queue dashboard. |
| Integration Framework | ❌ Absent | No API/webhooks to build on. |
| Plugin Architecture | ✅ **Present** | nwidart modules + admin toggle UI (`modules.view/manage`). |
| Data Retention Policies | ❌ Absent | Archived data kept indefinitely. |

**Legend:** ✅ implemented & reachable · ⚠️ partially built or disconnected · ❌ proven absent.

---

## Appendix — Evidence shortcuts used

- Routes: all `Modules/*/routes/{web,api}.php` + `routes/web.php` read in full.
- Auth flows: `TenantAuthController`, `LandlordAuthController`, `ProfileController`, `LandlordAdminController`.
- Tenancy: `SaaSTenantFinder`, `IdentifyTenant`, `EnsureTenantIsActive`, `EnsureLandlordContext`, `EnsureLandlordAdminActive`, `config/multitenancy.php`, `bootstrap/app.php`.
- Services: `TenantProvisioner`, `TenantLifecycleService`, `SubscriptionService`, `QuotaService`, `SettingService`, `LandlordMetricsService`, `AccessBaselineProvisioner`, `ManagementPolicy`, `AccessInvariants`, `AuditWriter`, `TenantUserService`.
- Absence proofs: grep `Mail::|Notification::|->notify(|MustVerifyEmail|two_factor|Sanctum|api_token|webhook|impersonat|backup|Password::` → only marketing copy, config defaults, skill docs, and a broker-token test. All `api.php` files contain comment-only stubs. `routes/console.php` has no scheduled tasks. All five `EventServiceProvider::$listen` arrays are empty.
