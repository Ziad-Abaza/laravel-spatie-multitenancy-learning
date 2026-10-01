---
noteId: "84190e60bdde11f19d227386d2fcdb80"
tags: []

---

# Phase 9 — Recommended Roadmap

Ordered by ROI. Each item references its finding ID; evidence and reproduction live in the phase files. **Nothing here was implemented** — audit was strictly read-only.

## Wave 0 — Restore correctness (do first; these are errors, not slowness)
| Item | Fixes | Effort |
|---|---|---|
| Run missing module migrations | DB-01 (`tenant_backups`, `usage_records`, `webhook_deliveries`) | minutes |
| `php artisan storage:link` | NA-03 | minutes |
| Remove stale `public/hot` when vite isn't running | NA-01 | minutes |

## Wave 1 — Config/env only (biggest impact per effort)
| Item | Fixes | Expected effect |
|---|---|---|
| `CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION` → redis (or file for cache/session) | BE-01, AR-01 | −10–15 landlord queries/request |
| `php artisan config:cache route:cache event:cache` (+ `shared_routes_cache`+`SwitchRouteCacheTask` if route caching adopted) | BE-11, BE-12 | removes per-request discovery/compile |
| Remove `bunny('Instrument Sans')`; add `crossorigin` preconnect | FE-05, NA-02 | −118 KB dead assets, faster first paint |
| Deploy with `composer install --no-dev` | DEP-05, NA-04 | removes dev routes/providers |

## Wave 2 — Small code changes (high frequency paths)
| Item | Fixes | Expected effect |
|---|---|---|
| Bind grid consumers to debounced `search` emit (or debounce `update:searchQuery`) | FE-01 | −80–95% requests on index pages |
| Memoize `SettingService::domainMap`/`scopeMap` per request on the singleton | BE-02 | −14–20 queries/request even if cache stays on DB |
| Eager-load `media` in `listUsers`; reuse fetched count for quota; collapse 4×`fresh()` | BE-04, BE-07, BE-08 | removes avatar N+1 + redundant count |
| `ShouldQueue` on `SendTenantWelcomeNotification`, `SendTenantStatusNotification`, `AuditTenantLifecycle`; `whereJsonContains` on webhook `events` | BE-05, AR-04 | mutation POSTs drop mail+fan-out latency |
| Eager-load `tenant.plan` in `share()`; cache `translationsFor` per (locale,module) | BE-03 | −2 queries + 3 file reads/req |

## Wave 3 — Structural
| Item | Fixes |
|---|---|
| Paginate `listUsers` (cursor/length-aware) + grid pagination contract | BE-04, FE-02 |
| `Inertia::lazy`/`defer` for heavy share() props and `TenantController::show` panels | AR-02, BE-09 |
| Usage rollups: read `QuotaService` numbers from `usage_records` instead of live aggregates | BE-08, BE-10 |
| Composite indexes: `audit_logs(action,created_at)`, `subscriptions(status,trial_ends_at)`, `webhook_endpoints(active)`; FK constraints on `tenants.plan_id`, `subscriptions.*` | DB-03/04/05/06 |
| Queue all listeners; chunk/bulk-insert console loops; queue per-tenant jobs for lifecycle enforcement | BE-05, BE-10 |
| Resolve Role/Permission connection per-callsite (remove ambient resolution) | AR-03, DB-07 |
| Test suite: frozen migrated sqlite template per tenant; `LazilyRefreshDatabase` | DB-09 |

## Wave 4 — Long-term
- Settings envelope: single versioned merged key per scope (replaces N domain maps) — BE-02 long-term.
- Consolidate duplicate model surfaces (`App\Models` vs module models) — AR-06.
- Infra topology: dedicated Redis/Valkey for cache+session+queue; landlord DB serves only landlord domain — AR-01.
- Optional: Octane (multitenancy provider already hooks its events) once blocking sync listeners are removed.

## Verification plan for the fixer
1. `php artisan about` — expect cached config/routes/events, redis drivers.
2. Enable `DB::listen` counter or Telescope on `/users` — target <10 queries/page (from ~26-33).
3. Re-run `Measure-Command`/curl baseline (Phase 1 method) — same-env comparisons only.
4. `npm run build` — confirm asset totals stay <1 MB and no chunk >250 KB.
5. Test suite timing before/after DB-09 changes.
