---
noteId: "c1069720bdde11f19d227386d2fcdb80"
tags: []

---

# ARCHITECTURAL RISKS

Risks that are structural (design-level) rather than bugs — they don't hurt at 2 tenants, but they bound the system's ceiling. Ordered by risk magnitude.

## R-1. The landlord DB is the fleet's single point of contention
`SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION` all resolve to the landlord MySQL connection (proven, `artisan about`). Every tenant request does session R/W + ~14–20 settings cache reads + permission cache read against it.
- **Failure mode:** tenant traffic growth linearly loads the landlord DB; a landlord-DB outage stalls *all* tenants' sessions — not just admin functions.
- **Also:** `PrefixCacheTask` keys tenant cache entries inside that same landlord `cache` table — tenant cache volume lands on landlord too.
- **Direction:** Redis/Valkey for infra stores (prefixing already designed for it); landlord DB reserved for landlord-domain data.

## R-2. `share()` is an unbounded eager contract
Every Inertia response eagerly computes auth+permissions+tenant+plan+branding+5 settings domains+locale+full translation catalog+theme+flash (`HandleInertiaRequests.php:41-139`); zero `lazy`/`defer`/`only` usage anywhere in controllers. Any new shared prop is paid by every request and every partial reload (compounds with FE-01's per-keystroke visits).
- **Direction:** adopt `Inertia::lazy`/`defer` as the default for non-critical props; cache deployment-volatile props (translations, palettes) under versioned keys.

## R-3. Synchronous domain-event fan-out
Only `DeliverWebhookJob` is `ShouldQueue`. Lifecycle events run mail (SMTP), endpoint scans, and delivery inserts inline — inside HTTP POSTs *and* inside `EnforceTenantLifecycleCommand`'s per-tenant loop. As endpoint count and tenant count grow, mutations degrade quadratically.
- **Direction:** queue listeners; bulk-insert deliveries; SQL-level event filtering.

## R-4. Ambient-state connection resolution on Role/Permission
`Role`/`Permission` pick their DB connection from `Tenant::checkCurrent()` at call time (`Modules/Access/app/Models/{Role,Permission}.php`). Under queue workers, nested `execute()`, or mis-ordered middleware, queries can silently hit the wrong database — a correctness risk that masquerades as random slowness/empty results.
- **Direction:** explicit context-specific models or connection binding at callsites.

## R-5. Unbounded collection contracts
`listUsers` (`->get()`, no pagination), `LandlordAdminController::index`, per-endpoint `->get()` in webhook dispatch, `Tenant::all()->eachCurrent` console loops — the system has no pagination contract between server and grid (FE-02). Plan limits allow ~9999 users; tables that are fine today become full-table payloads tomorrow.
- **Direction:** enforce pagination (or explicit unbounded-justification) at service boundaries; composite indexes where filter+sort patterns are fixed (audit, subscriptions).

## R-6. Integrity enforced in code, not schema
No FK constraints on `tenants.plan_id`, `subscriptions.tenant_id/plan_id`; webhook endpoint rows count on `active` unindexed; plan-delete protection is application-level (`PlanController`). Orphans/deletions are prevented only by code paths staying correct.
- **Direction:** corrective migration with orphan sweep + FKs.

## R-7. Ops/deploy posture unknown — biggest unproven risk
No deploy scripts in repo; prod may or may not run `optimize`, `--no-dev`, storage links, module migrations (DB-01 proves module migrations were already missed once). Everything marked "Proven-env" in the matrix needs a one-time production verification: `artisan about`, `ls public/build`, `db:table` on the three missing tables.
- **Direction:** codify a deploy checklist (migrate landlord+modules, optimize, storage:link, horizon/workers) — the repo already drifts without it.

## Non-risks (checked, fine)
Tenancy resolution layering (fail-closed finder → status middleware → tenant group); module cache artifact exists; frontend component conventions match the mandated Core library; index coverage on per-request hot paths.
