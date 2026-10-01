---
noteId: "7e312240bd3d11f1b0d837b196dd4a0c"
tags: []

---

# FEAT-12 Design Gate — Per-Tenant Module Entitlements

**Status:** PROPOSED — requires owner approval before any code is written.
**Date:** 2026-10-01

Module entitlements decide which functional modules a given tenant can reach.
Today `modules_statuses.json` controls *global* enablement (nwidart-modules);
there is no per-tenant filtering at all. This document fixes the design before
implementation.

---

## 1. Where filtering must occur

**Decision:** server-side middleware, shared SSoT with navigation.

- **Not at route registration.** Laravel route registration runs once per
  process/cache; the current tenant is unknown there. Filtering at
  registration would require one route-set per tenant — incompatible with
  `shared_routes_cache` and the existing single route table.
- **Not navigation-only.** Hiding nav items is cosmetic; the routes remain
  reachable and the gap is a real authorization hole.
- **Chosen mechanism:** `EnsureModuleEntitled` middleware applied to
  tenant-context route groups (or per-module route files). It resolves the
  module name from the route's module namespace/tag, checks the entitlement
  set for `Tenant::current()`, and aborts before the controller runs.

## 2. Entitlement storage & SSoT

- New landlord table `tenant_module_entitlements` (`tenant_id`, `module`,
  unique pair). **Rows = grants; absent row = denied** (deny-by-default is
  fail-closed and matches the codebase's existing posture).
- `TenantModuleEntitlements` service is the **single source of truth**:
  - `forTenant(Tenant): array` — cached per request (static memoization).
  - `isEntitled(Tenant, string $module): bool`.
  - Navigation reads from the same service via Inertia shared props — no
    second list, no duplication.
- Core modules (`Core`, `Access`, `Settings`, `Tenant`) are **always
  entitled** — the existing `core_module_locked` invariant extends to
  entitlements; a tenant without them is unprovisioned, not unentitled.

## 3. Runtime revocation semantics

- Entitlements are checked **per request**, so revocation takes effect on the
  next request — mid-request revocation does not retroactively abort the
  in-flight request (consistent with permission revocation elsewhere).
- Cached navigation payload is per-response; no cross-request staleness.

## 4. Route caching

- `route:cache` remains valid — the middleware resolves entitlements at
  request time, not registration time. No per-tenant route files, no
  `SwitchRouteCacheTask` change required.

## 5. `modules_statuses.json` interplay

- Global module disable (nwidart) is an **upper bound**: a disabled module is
  unreachable for every tenant regardless of entitlements. Entitlements can
  only *narrow* the global set, never widen it.
- `isEntitled()` checks `Module::isEnabled($module)` AND the grant row.

## 6. Queued jobs

- Tenant-aware jobs belonging to a disabled-for-tenant module must not run
  tenant side-effects. Minimal approach: the job itself checks entitlement
  before doing work (defensive check in the job's `handle`), since queue
  dispatch happens before tenant context and payload build. This is a
  per-job guard, not a dispatcher layer.
- Phase-1 scope: entitlements gate HTTP routes; the job-side guard is
  implemented on the affected jobs when the first entitlement-protected
  module ships a queued job. Flagged here so the gate is not forgotten.

## 7. 404 vs 403 on denied module URLs

**Decision: 403.**

- 404 would pretend the module does not exist — but tenants of this platform
  legitimately know the module catalog (plans advertise modules), so 404 leaks
  nothing yet creates confusing "page not found" UX for a paying customer.
- 403 states the truth ("not entitled for your workspace") without revealing
  anything about *other* tenants. Module *existence* is not a secret.
- The response renders the existing `ErrorPage` (403) — no new UI.

## 8. UI surface

- Landlord `Tenants/Show` gets an "Entitlements" panel: checkbox list of
  non-core modules, gated by `tenants.update`.
- Tenant navigation filters items by entitlement set (same SSoT service).
- Disabled-by-platform modules show separately with their global-off state.

## 9. Open questions for owner

1. **Grant model:** rows=grants (deny-by-default) vs plan-driven entitlement
   inheritance from `plans.limits`/`features`. Recommend rows=grants with an
   optional plan-sync later — simpler, auditable.
2. **Which modules are sellable** — the initial grantable list should exclude
   modules whose routes have no tenant surface.
3. **Job-side enforcement:** accept the per-job guard approach, or should
   entitlement failure drop the job silently vs. log/report?

---

*Implementation must not start until this document is approved (§FEAT-12
record in FEATURE-IMPLEMENTATION-PLAN.md).*
