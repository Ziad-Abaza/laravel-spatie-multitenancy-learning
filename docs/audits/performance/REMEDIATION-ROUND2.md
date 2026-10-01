---
noteId: "5c4d3770bde911f19d227386d2fcdb80"
tags: []

---

# Performance Remediation — Round 2 Evidence Report

Round 1 fixes were committed previously (162 tests green). This round
implements the remaining proven items from the roadmap plus second-pass
findings. All measurements below are from the local dev environment
(PHP built-in server, uncached config/routes, database cache/session
drivers in `.env`) — they bound *what is measurable here*, not production.

## Changes implemented

| # | Change | Bottleneck → root cause | Expected effect | Verification |
|---|--------|------------------------|-----------------|--------------|
| 1 | `TenantUserService::listUsers()` → `paginate(15)`; controller maps page items + emits `pagination` prop; `Users/Index.vue` wires `pagination` + `@page-change` | `/users` loaded and serialized every row; ~N-row payload + per-row EXISTS checks | Bounded payload and query work regardless of member count | `RequestQueryBudgetTest` (new): 21 users → 38 queries first hit, 23 repeat — row-independent |
| 2 | `share()` — `auth`, `tenant`, `branding`, `system`, `billing`, `theme` are now closures | Permission/plan/media/settings work ran even for partial reloads | `only` requests skip unrequested props entirely | Same test (partial reloads exercised by search/page tests) |
| 3 | `only: [...]` on search/filter/page requests: Users, Access audit, Landlord audit, Landlord tenants index pages | Full prop re-evaluation + full payload on every debounced keystroke | Smaller responses, less server work per keystroke | Frontend build green; pages unchanged visually |
| 4 | FK migration `2026_10_07_000002_add_billing_foreign_keys`: `tenants.plan_id`→`nullOnDelete`, `subscriptions.tenant_id`→`cascadeOnDelete`, `subscriptions.plan_id`→`restrictOnDelete` | Integrity enforced only at app level | DB-level enforcement; mirrors existing `delete_blocked` semantics | Orphan check = 0 on all three columns before migrating; `migrate` applied (209ms); suite green |
| 5 | `EnforceTenantLifecycleCommand` `->with('tenant')`; `RecordTenantUsageCommand` bulk `insert`; `purgeExpiredRetentions` filters in SQL (`settings->` JSON) | N+1 tenant lookup per expired subscription; per-metric INSERT; loading all archived tenants to filter in PHP | Bounded, one-insert-per-tenant sweeps | `TenantRetentionTest`, `LandlordTenantProvisioningTest` lifecycle tests green |
| 6 | `QuotaService::getUserCount`/`getStorageUsageMb` skip `execute()` when `$tenant->isCurrent()` | `Tenant::execute()` runs `makeCurrent()` unconditionally → full switch-task round (DB purge+reconnect, cache prefix, permission scope) twice per call, even when already current | Removes 2 redundant tenant switches per `/users` request | `QuotaEnforcementTest` green |
| 7 | `LandlordMetricsService::tenantDiagnostics` → `Cache::remember(60s)` | Full tenant context switch + 4 aggregate queries on every tenant detail page | Support panel tolerates ≤60s staleness | `TenantBackupTest` diagnostics test green |
| 8 | `useI18n.trans` → `replaceAll` (no per-call `RegExp`); `useCurrency` caches `Intl.NumberFormat` per locale+currency | RegExp/Intl constructed per call, per table row | Micro win, also fixes literal-token correctness | Build green |
| 9 | `phpunit.xml` landlord DB → `database/testing.sqlite` (file) | `:memory:` forced re-running ALL migrations on every test | `migrate:fresh` once per run, transaction per test | Suite: ~134s → ~101–111s (~20%) |
| 10 | `TestCase::provisionTenant` clones a pre-migrated/seeded tenant sqlite template; landlord-side steps (tenant row, `subscribeTenant`, owner user, `TenantCreated`/`TenantProvisioned` events) still run for real | Per-test tenant migration + baseline seeding (~0.5–1s × ~47 call sites) | Largest single suite speedup; preserves events/subscription/owner semantics that tests assert | `TenantNotificationTest` (welcome email), `TenantIsolationTest`, `QuotaEnforcementTest`, `schema-check` — all green |
| 11 | `.gitignore` → `/database/*.sqlite` | Test DB artifacts unignored | Hygiene | — |

## Measured results (dev environment)

- **Test suite:** 163 tests, 1,209 assertions — **green**. Duration **~101–111s** vs ~134–136s baseline (≈20% faster; both optimizations are test-only, no production effect).
- **`/users` query count (test env):** 38 first request, 23 repeat — bounded independent of row count (was O(N)).
- **Frontend build:** 3.5s, green.
- **HTTP smoke (dev server):** landlord `/` ≈ 940–998ms warm, tenant `/login` ≈ 925–1025ms — unchanged. The dev wall-clock floor is PHP boot + uncached config/routes + built-in server + DB cache/session drivers, not the code paths fixed here.

## Why the score may still read ~45

The remaining bottleneck is **not in the application code measured here** — it is the runtime environment:

1. `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` in `.env` — every cache/session/queue op is a SQL round-trip (the audit's BE-01).
2. Uncached `config`/`route`/`event` compilation on every request (~1s CLI boot floor observed).
3. Dev server (`php artisan serve`) + debug tooling — no opcache tuning evidence, no HTTP/2, no static asset caching.

**Production-only actions required** (cannot be verified from this environment):

```dotenv
CACHE_STORE=redis
SESSION_DRIVER=redis   # or file/cookie as appropriate
QUEUE_CONNECTION=redis
```

```bash
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
```

## Remaining known items (deferred, low priority)

- Per-row `AccessInvariants` EXISTS checks for baseline principals on admin/user lists — bounded by page size; only matters for privileged-heavy pages.
- `LandlordMetricsService::getMetrics` aggregates on landlord dashboard — bounded landlord queries, no cache (frequency doesn't justify staleness).
- If the score comes from a specific tool (Lighthouse / PageSpeed), re-measure after the env changes — TTFB under `serve` cannot demonstrate it.
