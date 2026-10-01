---
noteId: "c0dc79e0bdde11f19d227386d2fcdb80"
tags: []

---

# QUICK WINS

Everything below is low-risk, ≤ a few lines or env/config only, and independently shippable. Cross-refs give full evidence.

## Env / ops (no code)

- [ ] `CACHE_STORE=redis` (or `file`), `SESSION_DRIVER=redis|file`, `QUEUE_CONNECTION=redis` — kills ~10–15 landlord queries/request. **BE-01** — biggest single win.
- [ ] Run module migrations: `tenant_backups`, `usage_records`, `webhook_deliveries` missing in live DB → those paths currently throw. **DB-01**
- [ ] `php artisan config:cache route:cache event:cache` in deploy (verify prod runs this). **BE-11**
- [ ] `php artisan storage:link` — logo URLs currently 404. **NA-03**
- [ ] Delete stale `public/hot` when Vite dev server is off — otherwise all assets point at a dead `http://[::1]:5173`. **NA-01**
- [ ] Deploy with `composer install --no-dev` — keeps `_boost/*`/`_inertia/devtools/*` routes/providers out of prod. **DEP-05/NA-04**
- [ ] If route caching adopted in tenancy: set `shared_routes_cache => true` and enable `SwitchRouteCacheTask` (otherwise per-tenant route cache files). **BE-12**

## One-line-ish code changes

- [ ] **FE-01** — In `Users/Index.vue:163`, `Audit/Index.vue` (Access:91), `Audit/Index.vue` (Landlord:54), `Tenants/Index.vue:149`: bind `@search="handleSearch"` instead of `@update:search-query` (debounce already exists at `EnterpriseDataGrid.vue:87`). **Do this first — highest UX-per-diff in the audit.**
- [ ] **BE-02** — memoize `SettingService::domainMap`/`scopeMap` results on the service instance (it's already a singleton): per-request array keyed `"{$scope}.{$domain}"`. Saves ~14–20 cache SELECTs even before BE-01 lands.
- [ ] **BE-04** — add `'media'` to `->with('roles.permissions')` in `TenantUserService.php:33` → kills avatar N+1.
- [ ] **BE-07** — `UserController.php:166-169`: one `$user->fresh()` call instead of four.
- [ ] **BE-05** — `implements ShouldQueue` on `SendTenantWelcomeNotification`, `SendTenantStatusNotification`, `AuditTenantLifecycle` (queues are already tenant-aware).
- [ ] **FE-05** — delete `bunny('Instrument Sans', …)` from `vite.config.js:19-23` (font is built but never referenced); add `crossorigin` to the fonts preconnect in `app.blade.php:14`.
- [ ] **FE-03** — hoist the two `import.meta.glob` calls to module scope in `resources/js/app.js:11-12`.
- [ ] **DB-06** — add index on `webhook_endpoints.active` (queried on every domain event).
- [ ] **DEP-02/04** — remove unused `@laravel/multiplex`; move `laravel/tinker` to `require-dev`.

## Expected compound effect

BE-01 + BE-02 + FE-01 alone remove an estimated **~70–80% of SQL statements** on busy index pages (per-keystroke × per-request amplification) and shave most of the fixed landlord-DB overhead on every other request.
