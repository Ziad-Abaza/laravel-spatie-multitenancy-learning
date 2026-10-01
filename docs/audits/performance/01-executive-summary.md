# Phase 1 — Executive Summary & Investigation Plan

Audit date: 2026-10-02 · Scope: full-system performance root-cause analysis (read-only) · Repo: `spatie-multitenant` (Laravel 13.34 / PHP 8.4.23 / Vue 3.5 + Inertia 3.7 + Pinia 4 + Vite 8 + Tailwind 4, nwidart/laravel-modules v13, spatie/laravel-multitenancy 4.2)

## Measured Baseline (development environment)

All timings measured on this machine via `php artisan serve` (PHP built-in server, Windows). These are **development-environment numbers**, not production SLIs.

| Metric | Value | Method |
|---|---|---|
| Framework boot (CLI) | ~1.05–1.09 s | `Measure-Command { php artisan inspire / route:list }` |
| Landlord landing page TTFB | ~920–950 ms warm, 3.1 s cold | `Invoke-WebRequest http://localhost:8788/` ×5 |
| Tenant login page TTFB | ~930–990 ms warm | Host-header spoof `tenant1.localhost` ×4 |
| Route count | 87 routes | `php artisan route:list` |
| Tenant rows | 2 | `Tenant::count()` |

Environment state (proven via `php artisan about`): `APP_DEBUG=true`, **config / routes / events NOT cached**, views cached, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` — all three on the `landlord` MySQL connection (`multivendor@127.0.0.1`), `storage` symlink **not linked**.

## Headline Findings (full evidence in phases 2–7)

1. **~20+ SQL statements are executed before the controller on every authenticated tenant request** — because cache, session, and queue all use the *database* driver on the landlord connection, and `SettingService` performs zero per-request memoization (each "cache hit" is a real `SELECT`). [Phase 3/4 — **Proven**]
2. **The data-grid search debounce is dead code**: `EnterpriseDataGrid` emits `update:searchQuery` synchronously per keystroke; all four index pages bind to the un-debounced emit → one full Inertia request per keystroke. [Phase 2 — **Proven**]
3. **`HandleInertiaRequests::share()` re-reads up to 3 JSON translation files and performs uncached lazy loads (`tenant.plan`, `getFirstMediaUrl('logo')`, role/permission hydration) on every Inertia response.** [Phase 3 — **Proven**]
4. **Synchronous listeners on the request path**: tenant welcome/status emails and webhook fan-out execute inline during POSTs (no `ShouldQueue`). [Phase 3 — **Proven**]
5. **Unbounded `->get()` + per-row EXISTS queries** in `TenantUserService::listUsers` / `UserController::index` (avatar N+1, per-owner invariant checks, unpaginated payload). [Phase 3/4 — **Proven**]
6. **Schema drift**: migrations exist for `tenant_backups`, `usage_records`, `webhook_deliveries` but the tables **do not exist** in the live landlord DB — those code paths will error, not just be slow. [Phase 4 — **Proven**]
7. Schema is otherwise well-indexed on the hot paths (tenants.domain/slug unique, settings(domain,key) unique). No missing-index crisis; problems are *volume of queries*, not *missing indexes*.
8. Frontend architecture is largely healthy (lazy page globs, token CSS, `<Link>` navigation, no prop mutation) — findings are concentrated in the grid search path, per-request backend amplification, and absence of production caching (`route:cache`/`config:cache`/`event:cache` unrun in this env).

## Dev vs Production Caveat

`APP_DEBUG=true`, no config/route/event cache, PHP built-in server, Windows. The ~930 ms warm TTFB is a **dev floor**, not a production number. Structural findings (query amplification, sync listeners, unbounded lists) are environment-independent; absolute timings are not.

## Investigation Plan

| Phase | Scope | Method | Status |
|---|---|---|---|
| 1 | Baseline & plan | artisan about, serve+curl timing, route:list | ✅ done |
| 2 | Frontend (Vue/Inertia/Pinia/Vite/Tailwind) | full-file read of 55 Vue files + app.js/css/blade | see `02-frontend.md` |
| 3 | Backend (middleware, providers, controllers, services, listeners) | static analysis + live schema | see `03-backend.md` |
| 4 | Database (indexes, N+1, connections, drift) | `db:table` inspection + migration review | see `04-database.md` |
| 5 | Network & assets (fonts, build, public/) | vite config + blade + package analysis | see `05-network-assets.md` |
| 6 | Architecture (tenancy, modules, state flow) | config + provider review | see `06-architecture.md` |
| 7 | Dependencies | composer.lock + package-lock.json | see `07-dependencies.md` |
| 8 | Root-cause matrix | consolidation | `08-root-cause-matrix.md` |
| 9 | Roadmap | consolidation | `09-roadmap.md` + final deliverables |

## Evidence Limitations

- `.env` is gitignored/unreadable — driver values proven via `artisan about` (which reflects real `.env`), not the file itself.
- No production build exists (`public/build/` absent) — bundle-size claims are config-level, marked accordingly.
- Authenticated-route timings not measured (no session); query counts for those paths are static-analysis-derived and marked Highly Likely where applicable.
