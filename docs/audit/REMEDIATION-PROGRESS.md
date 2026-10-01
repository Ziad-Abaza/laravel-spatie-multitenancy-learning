---
noteId: "ae54b140bd2d11f1b0d837b196dd4a0c"
tags: []

---

# Remediation Progress Tracker

> Companion to `FEATURE-IMPLEMENTATION-PLAN.md`. One row per feature; updated at
> every state transition. No code is written for a feature until its Gates pass.
>
> Sources of truth: `MASTER-AUDIT.md`, `FEATURE-GAP-ANALYSIS.md`
> Last updated: 2026-10-01

## Baseline Record (B0)

| Run | Date | Command | Result | Notes |
|---|---|---|---|---|
| — | pending | `php artisan test` | not yet run | Baseline must be recorded before the first feature touches code |

## Status Board

| Feature | Gap | Batch | Gates | Status | Files touched | Tests | Mini-audit | Notes |
|---|---|---|---|---|---|---|---|---|
| FEAT-01 Suspended-user enforcement | G3 | B1 | ☐ | PLANNED | — | — | — | Mirror `EnsureLandlordAdminActive`; login pre-check |
| FEAT-03 Logo/avatar uploads | G10 | B1 | ⚠️ | PLANNED | — | — | — | Blocked on FND-018 (tenant-scoped storage paths) |
| FEAT-05 Lifecycle scheduler | G7 | B1 | ☐ | PLANNED | — | — | — | Trial expiry → suspend decision pending |
| FEAT-10 Dead scaffold cleanup | G20 | B2 | ☐ | PLANNED | — | — | — | `stubs/` PROTECTED — never delete/alter. Prove zero refs per file |
| FEAT-04 Notification subsystem | G2 | B3 | ⚠️ | PLANNED | — | — | — | Mail-first MVP; two-context table decision deferred |
| FEAT-02 Password reset flow | G1 | B3 | ☐ | PLANNED | — | — | — | Broker already `connection=tenant`; consumes FEAT-04 |
| FEAT-09 Registration hardening + email verify | G19/G4 | B3 | ⚠️ | PLANNED | — | — | — | Captcha dep needs approval; honeypot fallback |
| FEAT-07 Audit expansion + viewer | G5 | B4 | ⚠️ | PLANNED | — | — | — | Tenant `audit_logs` table; resolves context-write limit |
| FEAT-08 Auth event logging | G16 | B4 | ☐ | PLANNED | — | — | — | Depends on FEAT-07; listeners on existing auth events |
| FEAT-06 Session hygiene | G17 | B4 | ☐ | PLANNED | — | — | — | Sessions table is landlord-owned — filter by user+context |
| FEAT-12 Per-tenant module entitlements | G15 | B5 | ❌ | BLOCKED | — | — | — | **Design Gate doc required** — route-registration/boot/cache/nav blast radius unproven |
| FEAT-13 Tenant backup/export | G13 | B5 | ☐ | PLANNED | — | — | — | Makes pricing-page backup claim true; FND-030 noted |
| FEAT-20 Support diagnostics | G11 | B5 | ☐ | PLANNED | — | — | — | Read-only aggregates on `Tenants/Show.vue` |
| FEAT-11 Outbound webhooks | G9 | B5 | ☐ | PLANNED | — | — | — | 2 landlord tables; HMAC + retries |
| FEAT-15 Usage metering | G12 | B6 | ☐ | PLANNED | — | — | — | Depends on FEAT-05 scheduler |
| FEAT-14 Retention & erasure | G18 | B6 | ☐ | PLANNED | — | — | — | Depends on FEAT-05 + FEAT-13 |
| FEAT-16 Audited impersonation | G11 | Track X | ❌ | BLOCKED | — | — | — | **Explicit approval + threat model + dedicated security tests; standalone, never batched** |
| FEAT-17 API foundation | G8 | Decision | ❌ | BLOCKED | — | — | — | Decision pending: session-JSON vs Sanctum vs no-API (see plan) |
| FEAT-18 Billing execution | G6 | Decision | ❌ | BLOCKED | — | — | — | Product architecture decision — 9-item decision surface in plan |
| FEAT-19 Runtime feature flags | G14 | — | ☐ | DEFERRED | — | — | — | Registry can host flags; build only on concrete need |

## Change Log

| Date | Feature | Change | Verified by |
|---|---|---|---|
| 2026-10-01 | — | Plan + tracker created; zero code written | — |
| 2026-10-01 | all | Governance hardened: gate chain (Baseline→Design→Code→Test→Mini-audit), STOP rule, FEAT-10 rescoped (stubs protected), FEAT-16 standalone, FEAT-17/18 decision-blocked, batch order baseline→security→cleanup | — |

## Regression Guards (must stay green forever)

These are fixes already landed post-MASTER-AUDIT — any feature touching these
areas must keep their tests passing:

- Throttle on `/login`, `/register`, `/register-tenant`, `/landlord/login`
- `TenantProvisioner::tenantDomain()` as sole domain constructor
- `TenantLifecycleService::assertTransition` state-machine guard
- Provisioning transaction + DB-drop compensation
- `allow_registration` enforced server-side on both register paths
- `password_reset_tokens` broker pinned to `tenant` connection
- `PlanController::destroy` attach-guards + `default_plan_id` cleanup
- `ScopePermissionCacheTask` + `PrefixCacheTask` in `switch_tenant_tasks`
- Isolated sqlite test topology (`phpunit.xml` DB_*_DRIVER=sqlite)
- Env-gated seed passwords (no predictable credentials outside local)
- Suspended landlord admin rejected at login + mid-request (`landlord.active`)

## Protected Assets (never deleted or modified)

- `stubs/` — library-owned generator stubs (nwidart). Explicitly out of scope
  for FEAT-10 and any future cleanup.
- `.env`, `vendor/`, `node_modules/` — obvious exclusions.
- Per-module `composer.json`/`package.json`/`vite.config.js` — leave unless
  proven dead AND safe (nwidart registration may consume them).
