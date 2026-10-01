---
noteId: "58d48c70bdde11f19d227386d2fcdb80"
tags: []

---

# Phase 6 — Architecture Investigation

Scope: tenancy architecture, module system, shared-state flow (Inertia↔Pinia), middleware pipeline design, cross-cutting infra choices. Distinguishes *architecture problems* (design-level) from *implementation problems* (fixable locally).

## Architectural root causes

### AR-01 — The landlord database is also the infrastructure store for every tenant request
- **Severity:** High (architectural) · **Confidence:** Proven
- **Design:** `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` — all resolve to the `landlord` connection (`.env.example:45,55,59`; `config/session.php:76`, `config/cache.php:44`, `config/queue.php:40`).
- **Why it's architectural:** in a database-per-tenant design, placing sessions/cache/queue on the landlord DB means *every tenant page view* is a landlord-DB workload: session read+write, ~14–20 settings-cache SELECTs, permission-cache SELECT, plus tenant/user queries on the tenant DB. The landlord DB becomes the throughput ceiling for the whole fleet, and tenant-context code must maintain two live connections per request.
- **Contrast with fixable bugs:** this isn't a missing index — it's a topology choice that guarantees N extra round-trips structurally.
- **Fixes:** Minimal — Redis for cache/session/queue (env-only change; `PrefixCacheTask` already exists for tenant scoping). Balanced — Redis + per-tenant cache prefixing (already built) + landlord-only session table. Long-term — dedicated infra store (Redis/Valkey) and consider separating "infra" concerns from the landlord *domain* DB entirely.

### AR-02 — `HandleInertiaRequests::share()` is a mandatory per-request bundle of unrelated concerns
- **Severity:** Medium (architectural) · **Confidence:** Proven
- **Evidence:** `app/Http/Middleware/HandleInertiaRequests.php:41-139` — every Inertia response carries auth (dual-guard), permissions, tenant+plan+branding, settings (5 domains), locale+full translation catalog, theme, flash. Every prop is evaluated eagerly — Inertia `only()`/`lazy()`/`defer` are **never used** anywhere (verified: no `Inertia::lazy`/`optional` in controllers).
- **Why architectural:** the share() contract assumes every page needs every prop; partial reloads (`preserveState` navigations, per-keystroke searches — FE-01) recompute the entire prop set. The system has no mechanism to mark props cheap/expensive.
- **Fixes:** Minimal — wrap non-critical props in closures/`Inertia::lazy` so they're excluded from `only()` partial reloads. Balanced — move static-ish props (translations, supported locales, palettes) into a versioned cache key. Long-term — split shared props into "request-volatile" vs "deployment-volatile" and serve the latter from the client once (bridge already exists via `setupInertiaStateBridge`).

### AR-03 — Ambient connection selection on Role/Permission models
- **Severity:** Medium · **Confidence:** Proven — `Modules/Access/app/Models/Role.php:19-24`, `Permission.php:14-19` choose landlord vs tenant connection via `Tenant::checkCurrent()` at call time. Every other model uses a fixed connection trait. Ambient state + connection resolution = queries can silently target the wrong DB inside queued jobs or nested `execute()` blocks. Perf angle: permission cache warm-up under the wrong context re-reads the catalog on the wrong connection.
- **Fixes:** explicit per-context models or connection-forcing at call sites.

### AR-04 — Synchronous domain-event fan-out on the request path
- **Severity:** High (architectural) · **Confidence:** Proven — only `DeliverWebhookJob` implements `ShouldQueue` (verified by grep). Tenant lifecycle events (`TenantCreated`, `TenantStatusChanged`, subscription events) synchronously trigger mail sends, webhook-endpoint scans, and delivery inserts during HTTP requests and console loops.
- **Fixes:** `ShouldQueue` listeners (tenant-aware queueing already on); bulk-insert deliveries; filter endpoints in SQL.

### AR-05 — Tenancy pipeline is well-layered (no finding — for the record)
- `IdentifyTenant` (prepend, fail-closed) → `EnsureTenantIsActive` → session/auth → `tenant` group (`NeedsTenant`, `EnsureValidTenantSession`, `EnsureTenantUserActive`). Landlord hosts short-circuit with zero queries. `SaaSTenantFinder` uses indexed columns. Switch tasks are minimal (cache prefix, DB switch, permission-cache scope). This is sound; the cost is *what the resolved context is used for downstream*, not resolution itself.

### AR-06 — Duplicated model surfaces (App\* vs Modules\*)
- **Severity:** Low · **Confidence:** Proven — `App\Models\Tenant` extends `Modules\Landlord\Models\Tenant`; `App\Models\User` vs `Modules\Access\Models\User`; `AuditLog` vs `AdminAuditLog`; two `AuditLogController`s with near-identical query logic. Two surfaces for the same concepts → duplicated queries, duplicated fixes, and the AR-03 connection ambiguity above.
- **Fixes:** consolidate on module models; keep `app/Models` aliases only where config references require them.

## Dev-vs-production deltas (deployment posture)
- `route:cache`, `config:cache`, `event:cache`, `view:cache` all absent in this environment (views only). If the deploy doesn't run `php artisan optimize`, prod pays per-request event discovery (6 ESPs) + route compilation + config loading: roughly the same class of overhead measured at ~1 s CLI boot.
- `shared_routes_cache => false` (`config/multitenancy.php:118`) — correct to leave off only because `SwitchRouteCacheTask` is off; if route caching is adopted, enable both together.
- Octane is supported by the multitenancy provider out of the box — not configured here; irrelevant to current findings.
