---
noteId: "c0a71200bdde11f19d227386d2fcdb80"
tags: []

---

# PERFORMANCE ROADMAP

Each row: finding → minimal fix / balanced fix / long-term fix. Ordered by ROI. Effort assumes the repo's existing conventions.

## Sprint 1 — Free wins (hours)

1. **BE-01** — *Minimal:* set `CACHE_STORE=redis`, `SESSION_DRIVER=redis` (or `file`), `QUEUE_CONNECTION=redis`. *Balanced:* Redis everywhere + Horizon. *Long-term:* dedicated infra store, landlord DB serves domain only.
2. **DB-01** — *Minimal:* `php artisan migrate --path=Modules/Landlord/database/migrations --database=landlord`. *Balanced:* `module:migrate` in deploy. *Long-term:* assert schema parity in CI.
3. **NA-01** — *Minimal:* delete `public/hot` when vite is stopped; confirm it's gitignored.
4. **NA-03** — `php artisan storage:link` (elevated on Windows).
5. **BE-11** — `php artisan config:cache route:cache event:cache` in deploy.
6. **FE-05/NA-02** — remove `bunny('Instrument Sans')` from `vite.config.js`; `crossorigin` on fonts preconnect; rebuild.
7. **FE-01** — point the four index pages at the debounced `@search` emit (one-line each) — *biggest UX win per diff*.

## Sprint 2 — Request-path cuts (days)

8. **BE-02** — *Minimal:* instance-level array memo on `domainMap`/`scopeMap`. *Balanced:* memo + Redis. *Long-term:* single versioned settings envelope key per scope.
9. **BE-03** — eager-load `tenant.plan` after tenant resolution; `Cache::remember("i18n.{locale}.{module}.{filemtime}")` in `translationsFor`.
10. **BE-04** — add `media` to `with()`; reuse `count($users)` for quota; single `fresh()` (BE-07); then paginate `listUsers` (BE-04/FE-02 balanced fix: length-aware paginator + grid `pagination` prop).
11. **BE-05/AR-04** — `implements ShouldQueue` on the three listeners; `ShouldQueue` on notifications; `whereJsonContains('events', $key)` + bulk `insert()` deliveries.
12. **BE-08** — 30–60 s per-tenant cache for `getUserCount`/`getStorageUsageMb`; later, read `usage_records` rollups.

## Sprint 3 — Structural (weeks)

13. **AR-02** — wrap non-critical `share()` props in `Inertia::lazy`; adoption rule: new heavy props default to lazy/deferred.
14. **BE-09** — cache `LandlordMetricsService::tenantDiagnostics` (30–60 s); lazy-defer backups/usage panels.
15. **AR-03/DB-07** — replace ambient connection with explicit landlord/tenant model pairs.
16. **BE-10** — `chunkById`/`cursor`, bulk inserts, per-tenant queued jobs in lifecycle/usage commands.
17. **DB-03/04/05/06** — corrective migration: FKs on `tenants.plan_id`, `subscriptions.tenant_id/plan_id`; composites `audit_logs(action,created_at)`, `subscriptions(status,trial_ends_at)`, `webhook_endpoints(active)`, `users(created_at)`.
18. **DB-09** — freeze one migrated tenant sqlite template; copy per test; `LazilyRefreshDatabase`.

## Sprint 4 — Long-term / optional

- Octane (spatie multitenancy already hooks its lifecycle) after sync listeners are gone.
- `manualChunks` vendor split if vendor chunk grows past ~250 KB (currently 184 KB — fine).
- Edge caching: `Cache-Control: immutable` on fingerprinted `build/assets`; gzip/brotli at web server.
- Dep hygiene: pin `@inertiajs/vue3`/`pinia`/`typescript` to single majors; drop `@laravel/multiplex`; `tinker` → require-dev; loosen exact pins to `^` for spatie packages if policy allows.

## Done criteria

- `/users` page ≤10 queries (from ~26–33); search = ≤1 request per 300 ms pause.
- `POST /register-tenant` returns without SMTP wait (queued mail).
- `db:table tenant_backups|usage_records|webhook_deliveries` all exist.
- `artisan about` shows Config/Routes/Events **CACHED** and redis drivers.
- CI test suite time reduced materially after DB-09.
