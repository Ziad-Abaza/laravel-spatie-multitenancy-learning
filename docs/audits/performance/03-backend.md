---
noteId: "1921c930bdde11f19d227386d2fcdb80"
tags: []

---

# Phase 3 — Backend Investigation (middleware / providers / controllers / services / listeners)

Method: full read of all middleware (`app/Http/Middleware/*`), service providers, route files (87 routes via `route:list`), controllers, services, listeners, jobs, console commands. Runtime verification: `artisan about`, live `db:table` inspection, `artisan serve` timings (Phase 1). "Cache read" below = SQL SELECT because `CACHE_STORE=database` (proven via `artisan about`; `config/cache.php:18`).

## Findings

### BE-01 — Database-backed cache/session/queue amplifies every request (ROOT CAUSE of the query flood)
- **Severity:** Critical · **Confidence:** Proven (runtime: `artisan about` shows all three on `landlord` MySQL)
- **Evidence:** `.env.example:45,55,59` (`SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`); `config/cache.php:42-48` DB store on `landlord`; `config/session.php:76` session on `landlord`; `config/queue.php:38-45` queue on `landlord`.
- **Why:** every `Cache::rememberForever`/`get`/`forget` is a `SELECT`/`DELETE` against the landlord `cache` table; every request = 1 session SELECT + 1 session write (+2% lottery GC DELETE). Nothing is memoized in-process across calls.
- **Impact:** ~10–15 landlord-DB round-trips per request before application logic. On a per-tenant-DB app this also concentrates all tenant traffic on the landlord DB.
- **Reproduction:** enable `DB::enableQueryLog()`/Telescope on any page; count `cache`/`sessions` table hits.
- **Fixes:** Minimal — `CACHE_STORE=file`/`redis`, `SESSION_DRIVER=file`/`redis`, `QUEUE_CONNECTION=redis` (env only). Balanced — Redis for all three + Horizon. Long-term — keep `PrefixCacheTask` semantics but on Redis so prefixing is free.

### BE-02 — `SettingService` has zero per-request memoization
- **Severity:** High · **Confidence:** Proven
- **Evidence:** `Modules/Settings/app/Services/SettingService.php:215-241` — `domainMap()` calls `scopeMap()` (landlord + tenant) which calls `Cache::rememberForever` — each invocation is a fresh DB SELECT; no instance-level memo.
- **Call fan-out per request:** `SetLocale` (`app/Http/Middleware/SetLocale.php:19-20`) → `supportedLocales()` + `defaultLocale()` (which internally re-calls `supportedLocales()`, lines 200-207) = ~6 map reads for `localization`. `HandleInertiaRequests::share()` (`app/Http/Middleware/HandleInertiaRequests.php:71,93-95,101,111,117`) → branding(2), localization(4+), theme(2), system(2), billing(2) reads. `app.blade.php` calls `getTheme()` on first render (2 more).
- **Impact:** ~14–20 redundant cache-table SELECTs per request for values identical within the request.
- **Reproduction:** query-log a single Inertia page; count `select … from cache where key in ('settings.map.%')` — appears ~14-20×.
- **Fixes:** Minimal — memoize `domainMap`/`scopeMap` results in a per-request array on the singleton (`SettingsServiceProvider.php` binds it). Balanced — memoize + Redis store. Long-term — precompute one merged "settings envelope" per tenant versioned key (single fetch).

### BE-03 — `share()` lazy-loads relations and re-reads translation JSON every request
- **Severity:** Medium · **Confidence:** Proven
- **Evidence:** `app/Http/Middleware/HandleInertiaRequests.php`:
  - `:47-67` evaluates `auth('landlord')->check()` then `auth('web')->check()`; on hit, `getRoleNames()` + `getAllPermissions()->pluck('name')` hydrate roles/permissions (Spatie catalog is 1 cache SELECT; role assignment queries extra unless loaded).
  - `:81-85` `$currentTenant->plan` lazy-loads (1 landlord query) every tenant request.
  - `SettingService.php:164` `$currentTenant->getFirstMediaUrl('logo')` → media-table query every tenant request.
  - `:148-172` `translationsFor()` — `is_file` + `file_get_contents` + `json_decode` on 2–3 JSON catalogs per request, uncached (files are ≤16 KB — moderate but pure overhead).
- **Impact:** ~5 extra queries + 3 file reads per request.
- **Fixes:** Minimal — eager-load `plan` on the resolved tenant; cache `translationsFor` per (locale, module) key. Balanced — cache resolved tenant-by-host + media URL. Long-term — precompiled translation catalogs per module into opcache-friendly PHP or a versioned cache key.

### BE-04 — `UserController::index` / `TenantUserService::listUsers`: unpaginated list + per-row queries
- **Severity:** High · **Confidence:** Proven
- **Evidence:** `Modules/Access/app/Services/TenantUserService.php:25-36` — `->get()` with no pagination, `%like%` search, `latest()`; `with('roles.permissions')` eager-loads roles but **not `media`**. `Modules/Access/app/Http/Controllers/UserController.php:43-55` calls `$user->getAvatarUrl()` per row (N media queries) plus `canManage`/`isDeletableBy` per row; `isDeletableBy` (`ManagementPolicy.php:146-163`) runs an EXISTS with one `whereHas` per baseline permission (`AccessInvariants.php:55-68`) for privileged rows. `quota.can_add` recomputes `User::count()` via `QuotaService.php:83-89` despite the full list already being fetched.
- **Impact:** ~4 + U(media) + K×EXISTS queries; full user table serialized into every Inertia response and every search keystroke (compounds with FE-01).
- **Fixes:** Minimal — eager-load `media`, single `fresh()`, reuse list count for quota. Balanced — paginate + move `canManage`/`isDeletableBy` to a batched computation. Long-term — cursor pagination + server-side projection of needed columns only.

### BE-05 — Synchronous listeners & notifications on the request path
- **Severity:** High · **Confidence:** Proven
- **Evidence:** grep — `ShouldQueue` appears **only** in `Modules/Landlord/app/Jobs/DeliverWebhookJob.php:19`. `SendTenantWelcomeNotification` (Listeners/:20-21) sends mail synchronously inside the `POST /register-tenant` request. `SendTenantStatusNotification` (:18-27) does `tenant->execute()` + tenant-DB role lookup + sync mail inside suspend/activate POSTs. `DispatchDomainEventWebhooks` (`DispatchDomainEventWebhooks.php:32-45`, verified) — `where('active',true)->get()` (no index on `active`, Phase 4), PHP-filters `events` JSON, then per endpoint: `WebhookDelivery::create` INSERT + `DeliverWebhookJob::dispatch` INSERT — inline in every lifecycle mutation and inside `EnforceTenantLifecycleCommand` loops (multiplied).
- **Impact:** SMTP latency + several INSERTs added to mutation requests; webhook fan-out multiplies in console loops.
- **Fixes:** Minimal — `implements ShouldQueue` on listeners (tenant-aware queueing is on). Balanced — queue notifications via `ShouldQueue` notification classes. Long-term — filter endpoints in SQL (`whereJsonContains('events', $key)`) and bulk-insert deliveries.

### BE-06 — `LandlordAdminController::index` mirrors BE-04 (unpaginated + per-row EXISTS)
- **Severity:** Medium · **Confidence:** Proven — `Modules/Landlord/app/Http/Controllers/LandlordAdminController.php:35-47`.

### BE-07 — `UserController::update` calls `fresh()` four times
- **Severity:** Low · **Confidence:** Proven — `UserController.php:166-169` — 4 separate `SELECT *` + roles reload for the audit snapshot.

### BE-08 — Duplicate/heavy aggregates per request in `QuotaService`
- **Severity:** Medium · **Confidence:** Proven
- **Evidence:** `Modules/Subscription/app/Services/QuotaService.php:114-133` — `Media::sum('size')` inside `tenant->execute()` on every tenant dashboard (`TenantDashboardController.php:30`) and subscription overview (`SubscriptionController.php:63`), uncached; `getUserCount()` (`:83-89`) runs `User::count()` — computed twice on `/users` (list + `canAddUser`).
- **Fixes:** Minimal — short TTL cache per tenant. Balanced — maintain `usage_records` rollups via the existing metering command and read those.

### BE-09 — `TenantController::show` is the heaviest landlord page (~15–20 queries)
- **Severity:** Medium · **Confidence:** Proven
- **Evidence:** `Modules/Landlord/app/Http/Controllers/TenantController.php:194-284` — `$tenant->execute()` for 50 users+roles (tenant conn), `tenantDiagnostics` 4 queries (`LandlordMetricsService.php:92-99`), `subscriptions.plan`, 25 backups, 50 usage records, plans list. Plus full connection switch overhead.
- **Fixes:** cache diagnostics 30–60 s; lazy Inertia props (`Inertia::lazy`) for below-fold panels; defer usage records to a paginated sub-request.

### BE-10 — Console commands iterate tenants unbatched
- **Severity:** Medium · **Confidence:** Proven
- **Evidence:** `EnforceTenantLifecycleCommand.php:29-53` — `->get()->each` over expired tenants; each suspend fires the webhook listener + tenant-DB owner lookup + sync mail (N×~4 queries + N mails per run). `RecordTenantUsageCommand.php:33-59` — per tenant: connection switch + 2 aggregates + 2 individual INSERTs (no bulk insert). `BackupTenantsCommand.php:33-52` — sequential dumps, no timeout budgeting.
- **Fixes:** chunk/`cursor()`, bulk `insert()`, queue per-tenant jobs.

### BE-11 — Boot-time overhead: uncached framework artifacts + event discovery ×6
- **Severity:** Medium · **Confidence:** Proven (dev env; production posture unknown)
- **Evidence:** `artisan about` — config/routes/events NOT cached. All 6 module `EventServiceProvider`s set `$shouldDiscoverEvents = true` (e.g. `Modules/Core/app/Providers/EventServiceProvider.php:21`) → directory scans per request when `event:cache` absent. `bootstrap/cache/modules.php` exists (nwidart module cache IS built — mitigated). 12 module route files + 6 empty `api.php` files loaded per request.
- **Fixes:** `php artisan config:cache route:cache event:cache` (deployment step); drop empty `api.php` files or skip their includes.

### BE-12 — `shared_routes_cache` disabled; route cache is per-tenant if enabled
- **Severity:** Low · **Confidence:** Proven — `config/multitenancy.php:118` `shared_routes_cache => false`. Routes are identical across tenants → per-tenant route cache files would be wasteful; enabling `SwitchRouteCacheTask` + `shared_routes_cache => true` is the intended fix.

### BE-13 — `AuthenticateSession` on every web request
- **Severity:** Low · **Confidence:** Proven — `bootstrap/app.php:39` appends it to the web group; adds a password-hash comparison + session re-hash check per request. Deliberate security feature; cost is small but constant. On the landlord surface it runs against the default `web` guard while landlord auth uses `landlord` — verify it is actually scoping the intended guard.

## Per-request query budget (authenticated tenant page, warm caches)

| Source | Queries |
|---|---|
| Session read + write (database driver) | 1 + 1 |
| Tenant find (`SaaSTenantFinder` domain→slug fallback) | 1–2 |
| Auth user (`web` guard) | 1 |
| Settings cache reads (SetLocale + share ~7 domains × 2 scopes) | ~14–20 |
| Spatie permission cache read + role/permission hydration | 1–3 |
| `tenant.plan` + `getFirstMediaUrl('logo')` | 2 |
| Controller logic (varies; dashboard ~4–5) | 4–5 |
| **Total** | **~25–33** (~7 irreducible) |

Landlord dashboard ≈ ~22 (no tenant find, but `LandlordMetricsService` ~8 queries). `landlord/tenants/{id}` ≈ 30+.

## Verified-healthy
- `IdentifyTenant` short-circuits landlord hosts with zero queries; tenants.domain/slug indexed (Phase 4).
- `EnsureLandlordAdminActive`/`EnsureTenantUserActive` reuse the resolved guard user — no extra query.
- `app/Support/ErrorPageRenderer` only works on error responses.
- Module API route files are empty stubs — no API surface.
- `bootstrap/providers.php` contains only `AppServiceProvider`; dev tools (boost/pail) are dev-only via package discovery.
