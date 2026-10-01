---
noteId: "9308e8a0bdde11f19d227386d2fcdb80"
tags: []

---

# PERFORMANCE AUDIT — spatie-multitenant

**Date:** 2026-10-02 · **Nature:** read-only root-cause investigation; zero source modifications.
**Stack (verified at runtime):** Laravel 13.34.0 · PHP 8.4.23 · MySQL `multivendor` · nwidart/laravel-modules 13 · spatie/laravel-multitenancy 4.2 · spatie/laravel-permission 8.3 · spatie/laravel-medialibrary 11.23 · Inertia 3.7 · Vue 3.5 · Pinia 4 · Vite 8 · Tailwind 4.

## Verdict

The system is **architecturally sound but operationally wasteful**: the frontend is well-disciplined (lazy pages, tokens, no leaks, healthy 0.6 MB build), schemas are well-indexed on hot paths, and the tenancy pipeline is correctly layered. The performance problem is **request amplification**: a database-backed infrastructure trio (cache/session/queue on the landlord connection) combined with a non-memoized settings service and eager `share()` props produces **~25–33 SQL statements before application logic** on every tenant page — and a dead debounce multiplies that by one request per keystroke on index pages.

## Measured baseline (this dev environment)

| Metric | Value |
|---|---|
| CLI boot (`artisan inspire`/`route:list`) | ~1.05–1.09 s |
| Landing page TTFB | ~920–950 ms warm / 3.1 s cold |
| Tenant login TTFB | ~930–990 ms warm |
| Total built assets | ~0.60 MB (entry 18 KB, vendor 184 KB) |

`APP_DEBUG=true`; config/routes/events uncached; PHP built-in server — **dev numbers, not production SLIs**.

## Top root causes (evidence in phase files)

1. **BE-01 (Critical):** `CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION` = `database` on `landlord` → every cache hit, session read/write, and job push is a landlord SQL round-trip. `03-backend.md`
2. **BE-02 (High):** `SettingService` re-reads cache store per call — no memoization → ~14–20 identical SELECTs/request. `03-backend.md`
3. **FE-01 (High):** `EnterpriseDataGrid` debounce is dead code — pages bind the immediate `update:searchQuery` emit → Inertia request per keystroke. `02-frontend.md`
4. **BE-05/AR-04 (High):** All listeners/notifications are synchronous — SMTP + webhook fan-out inside requests and console loops. `03-backend.md`
5. **BE-04 (High):** `listUsers` unpaginated `->get()` + per-user `getAvatarUrl()` N+1 + per-row baseline EXISTS. `03-backend.md`
6. **DB-01 (High):** live DB is missing `tenant_backups`, `usage_records`, `webhook_deliveries` — those paths throw. `04-database.md`
7. **BE-03/BE-11 (Medium):** uncached translation file reads + lazy plan/media in `share()`; no `config:cache`/`route:cache`/`event:cache` in this env. `03-backend.md`

## Report index

| File | Contents |
|---|---|
| `01-executive-summary.md` | baseline + plan |
| `02-frontend.md` | FE-01…FE-08 |
| `03-backend.md` | BE-01…BE-13 + per-request query budget |
| `04-database.md` | DB-01…DB-09 + verified index inventory |
| `05-network-assets.md` | NA-01…NA-05 + measured bundle |
| `06-architecture.md` | AR-01…AR-06 |
| `07-dependencies.md` | DEP-01…DEP-07 |
| `08-root-cause-matrix.md` | prioritized matrix + causal chain |
| `09-roadmap.md` | phased fix roadmap |
| `ROOT-CAUSE-MATRIX.md` / `PERFORMANCE-ROADMAP.md` / `QUICK-WINS.md` / `ARCHITECTURAL-RISKS.md` | canonical deliverables |

## How to reproduce the headline issue

1. `php artisan serve`, request `http://localhost:8788/` — ~930 ms TTFB warm.
2. `DB::enableQueryLog()` (or Telescope) on `/users` in tenant context → count ~25–33 queries; ~14–20 of them are `cache`/`sessions`/`settings.map.*` reads.
3. Type 5 characters in the grid search → 5 full Inertia requests (each paying the budget above).

## Confidence notes

- Proven = read in code and/or observed at runtime on this machine.
- Production posture (env values, `optimize`, `--no-dev` install, Redis presence) is **not in the repo** — marked accordingly in each phase.
- `.env` is gitignored; driver facts were verified via `artisan about` which reflects the real env.
