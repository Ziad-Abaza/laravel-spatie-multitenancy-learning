---
noteId: "64e2f1a0bdde11f19d227386d2fcdb80"
tags: []

---

# Phase 7 — Dependency & Package Investigation

Method: `composer.json`/`composer.lock`, `package.json`/`package-lock.json`, provider registration (`bootstrap/providers.php`, `bootstrap/cache/{packages,services,modules}.php`), vite config.

## PHP dependencies (locked)

| Package | Constraint | Locked | Assessment |
|---|---|---|---|
| laravel/framework | ^13.17 | 13.34.0 | current |
| inertiajs/inertia-laravel | 3.4 (pinned) | 3.4.0 | pinned minor — blocks patches |
| nwidart/laravel-modules | ^13.0 | 13.0.0 | module cache artifact present (`bootstrap/cache/modules.php`) |
| spatie/laravel-multitenancy | ^4.2 | 4.2.1 | fits |
| spatie/laravel-permission | 8.3 (pinned) | 8.3.0 | pinned — blocks patches |
| spatie/laravel-medialibrary | 11.23 (pinned) | 11.23.0 | pinned — blocks patches |
| spatie/laravel-translatable | 6.14 | 6.14.0 | pinned |
| laravel/tinker | ^3.0 | 3.0.2 | production dep (usually dev) |
| wikimedia/composer-merge-plugin | (transitive) | 2.1.0 | merges `Modules/*/composer.json` per `composer dump` — small fixed cost; module composer files duplicate the root PSR-4 map |

Dev-only correctly scoped: boost 2.10, pail 1.2.7, pao 1.1.5, pint, phpunit 12.

## JS dependencies (locked)

| Package | Constraint | Installed | Assessment |
|---|---|---|---|
| vue | ^3.5.0 | 3.5.43 | ok |
| @inertiajs/vue3 | `^2 \|\| ^3` floating | 3.7.1 | multi-major range — fresh installs may pick a different major |
| pinia | `^2 \|\| ^3 \|\| ^4` floating | 4.0.3 | same |
| typescript | `^5 \|\| ^7` floating | — | same |
| lucide-vue-next | ^1.0.0 | 1.0.0 | tree-shaken correctly in build (per-icon chunks) |
| @laravel/multiplex (optional) | ^0.4.1 | 0.4.5 | **installed but never imported** — dead dep |
| vite / @vitejs/plugin-vue / @tailwindcss/vite | ^8/^6/^4 | 8.3.1 / — / 4.3.3 | ok |

## Findings

### DEP-01 — Floating multi-major ranges
- Severity: Medium · Confidence: Proven — `package.json:10,12,20`. `npm install` on a fresh lock can silently jump majors (e.g. pinia 3→4). Not a runtime perf issue; reproducibility risk.

### DEP-02 — Dead dependency `@laravel/multiplex`
- Severity: Low · Confidence: Proven — in `optionalDependencies`; zero imports in the repo. Dead surface.

### DEP-03 — Exact pins block patch-level security/perf fixes
- Severity: Low-Medium · Confidence: Proven — `inertia-laravel: 3.4`, `medialibrary: 11.23`, `permission: 8.3`, `translatable: 6.14` (no `^`/`~`). Deliberate or accidental, the effect is identical: patch releases never arrive without manual bumps.

### DEP-04 — laravel/tinker in production requires
- Severity: Low · Confidence: Proven — `composer.json:12` — tinker is a dev tool; in prod it adds a provider + psysh dependency surface.

### DEP-05 — Dev providers do not leak into production
- Severity: Info · Confidence: Proven — `bootstrap/providers.php` has only `AppServiceProvider`; boost/pail/pao are `require-dev` and register via package discovery only when installed. `_boost/*` and `_inertia/devtools/*` routes exist in dev (route:list) — ensure `--no-dev` deploys.

### DEP-06 — nwidart module metadata is cached (mitigated)
- Severity: Info · Confidence: Proven — `bootstrap/cache/modules.php` (893 B) exists; `FileActivator` still reads `modules_statuses.json` per request (small file read) and `auto-discover.migrations` scans module migration dirs at boot. With `config:cache`+`route:cache` this is noise; without them it adds to the uncached-boot cost (BE-11).

### DEP-07 — Build is healthy; no dependency-bloat finding
- ~0.60 MB total assets, entry 18 KB, vendor 184 KB (Phase 5). No dependency-driven bundle problem.

## Unproven
- Whether deployed environments run `composer install --no-dev` and `php artisan optimize` — deploy scripts are not in the repo.
- Package versions vs. known CVEs — not checked against an advisory DB.
