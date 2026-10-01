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
| B0 | 2026-10-01 | `php artisan test` | **115 passed / 984 assertions / 86.57s — GREEN** | All regression guards verified test-covered (throttle, state machine, plan guards, suspended admin, tenant broker, cache scoping). Only uncovered guard: env-gated seed passwords (seeders not exercised by suite — acceptable, documented). |

## Status Board

| Feature | Gap | Batch | Gates | Status | Files touched | Tests | Mini-audit | Notes |
|---|---|---|---|---|---|---|---|---|
| FEAT-01 Suspended-user enforcement | G3 | B1 | ✅ | **DONE** | `app/Http/Middleware/EnsureTenantUserActive.php` (new), `bootstrap/app.php`, `TenantAuthController`, `TenantAccessControlTest` | +3 tests (login block, mid-session cut-off, inactive block) | clean — mirrors existing middleware, zero new abstractions, no hardcoding | Suite: 115→118 pass, 0 regressions |
| FEAT-03 Logo/avatar uploads | G10 | B1 | ✅ | **DONE** | `FormField.vue` (+file type), `UpdateSettingsRequest`, `TenantSettingsController`, `ProfileController`, `Profile/Edit.vue`, `TenantSettings.vue`, lang en/ar ×2, `MediaUploadTest` | +4 tests (logo scoped path, member 403, avatar, non-image reject) | clean — FND-018 already fixed (`TenantAwarePathGenerator` wired); no new tables/permissions | Suite 118→127, build ✅ |
| FEAT-05 Lifecycle scheduler | G7 | B1 | ✅ | **DONE** | `EnforceTenantLifecycleCommand` (new), `LandlordServiceProvider` (+cmd+schedule), `LandlordTenantProvisioningTest` | +5 tests (trial→suspend, active untouched, sub expiry→expired+suspend, archived terminal, open-ended safe) | clean — transitions stay inside `TenantLifecycleService`; `configureSchedules` hook used as designed | hourly + withoutOverlapping; decision: fail-closed suspend |
| FEAT-10 Dead scaffold cleanup | G20 | B2 | ✅ | **DONE** | Deleted: `Tenant/app/.../TenantController`, `Tenant/Pages/{Index,Create,Edit,Show}.vue`, `Access/.../AccessController`, `Access/Pages/{Index,Create,Edit,Show}.vue` | existing suite (scaffold-absence test stays green) | clean — every deletion grep-proven zero refs; `stubs/` untouched; CoreController already absent | Build ✅, 0 regressions |
| FEAT-04 Notification subsystem | G2 | B3 | ✅ | **DONE** (mail-first MVP) | `Landlord/Notifications/{TenantWelcome,TenantStatusChanged}Notification`, `Landlord/Listeners/SendTenant{Welcome,Status}Notification`, `Landlord/EventServiceProvider` ($listen), Landlord lang ×2, `TenantNotificationTest` | +3 tests (welcome mail, suspend notice, record-only tenant resilience) | clean — events→listeners as designed; sync mail chosen deliberately (queued notifications can't be NotTenantAware per-class → would throw `CurrentTenantCouldNotBeDetermined` in landlord context) | DB channel deferred until concrete need |
| FEAT-02 Password reset flow | G1 | B3 | ✅ | **DONE** | `PasswordResetController` (new), Access routes (4 guest routes), `AccessServiceProvider::boot` (`ResetPassword::createUrlUsing` → tenant-domain URL), `Auth/{ForgotPassword,ResetPassword}.vue`, Login.vue forgot link, Access lang ×2, `PasswordResetTest` | +5 tests (pages, send+tenant-domain URL, valid reset, invalid token, no user-enumeration) | clean — Laravel broker end-to-end, zero new tables, throttle parity with login | Depends: FEAT-04 mail channel (satisfied) |
| FEAT-09 Registration hardening + email verify | G19/G4 | B3 | ✅ | **DONE** | `User` MustVerifyEmail, `EmailVerificationController`, `VerifyEmail::createUrlUsing` (tenant-domain, `signed:relative`), verify routes, `verified` on all tenant-protected groups (Access/Tenant/Settings/Subscription), `Registered` on register, provisioner sends owner verification, honeypot `website` on both registration forms, `VerifyEmail.vue`, `EmailVerificationTest` | +6 tests (redirect, signed verify, resend+domain URL, register→notice, honeypot ×2) | clean — captcha dep rejected per owner decision; owner stays unverified until link click; test users verified explicitly | Suite 141 ✓, build ✓ |
| FEAT-07 Audit expansion + viewer | G5 | B4 | ✅ | **DONE** | Tenant `audit_logs` migration + `AuditLog` model; `AuditWriter` context-resolves landlord/tenant model + redacts password/token keys; `audit.view` in both manifests (read class → Owner/Admin/Support inherit); `AuditTenantLifecycle` listener records tenant.created/status_changed to admin trail; user/role mutations audited inside txns; viewers `Landlord/Audit/Index.vue` + `Access/Audit/Index.vue` + routes + nav | +6 tests (`AuditTrailTest`) | clean — append-only preserved, snapshots redacted, no cross-context reads | Suite 147 ✓, build ✓ |
| FEAT-08 Auth event logging | G16 | B4 | ✅ | **DONE** | `LogAuthActivity` listener on `Login/Failed/Logout` → `AuditWriter` (context-local); fixed latent bug: `configureEmailVerification(): void{}` override in Access ESP had silently disabled `Registered→SendEmailVerificationNotification` | covered by AuditTrailTest (login/failed logged, passwords never persisted) | clean — auth events rate-bounded by existing throttles, credentials never logged | ✓ |
| FEAT-06 Session hygiene | G17 | B4 | ✅ | **DONE (scoped)** | `AuthenticateSession` appended to web group → `Auth::logoutOtherDevices` on password change + explicit `POST /profile/revoke-sessions` (current_password re-auth, audited `auth.sessions_revoked`); UI section on Profile/Edit | AuditTrailTest covers revoke re-auth+audit | **deviation from plan**: raw session-row browser NOT shipped — shared landlord `sessions` table has `user_id` only (numeric PK collides across tenant DBs + landlord) → per-user listing/revocation is a cross-tenant IDOR without a tenant discriminator column. `logoutOtherDevices` achieves the revocation invariant driver-agnostically; session browser deferred pending a tenant-keyed session store decision | Suite 147 ✓ |
| FEAT-12 Per-tenant module entitlements | G15 | B5 | ❌ | BLOCKED | — | — | — | **Design Gate doc written** (`FEAT-12-DESIGN-GATE.md`) — awaits owner approval; no code |
| FEAT-13 Tenant backup/export | G13 | B5 | ✅ | **DONE** | `tenant_backups` + `TenantBackupService` (sqlite copy/mysqldump via configured conn) + `tenants:backup` daily + `TenantBackupController` (export perm; signed download); dir purged on tenant delete | +6 tests (`TenantBackupTest`) | clean — landlord-side artifacts, signed URLs, audited `tenant.backup_*` | Suite 162 ✓, build ✓ |
| FEAT-20 Support diagnostics | G11 | B5 | ✅ | **DONE** | `LandlordMetricsService::tenantDiagnostics` — read-only `execute()` aggregates; degrades `reachable:false`; StatCard row on `Tenants/Show.vue` | covered in TenantBackupTest | clean — aggregates only | ✓ |
| FEAT-11 Outbound webhooks | G9 | B5 | ✅ | **DONE** | `webhook_endpoints`/`webhook_deliveries` + `DispatchDomainEventWebhooks` listener (whitelist payload, `adminData` excluded) + `DeliverWebhookJob` (`NotTenantAware`, HMAC sig, 5 tries/backoff → dead) + `Webhooks/Index.vue` + nav; `webhooks.view`/`manage` | +5 tests (`WebhooksTest`) | clean — HTTPS-only, signed payloads, dead-letter inspectable | ✓ |
| FEAT-15 Usage metering | G12 | B6 | ✅ | **DONE** | `usage_records` + `tenants:record-usage` hourly; scalars exit `execute()` before landlord write; `metrics.view`-gated history panel | covered in `TenantRetentionTest` | clean — aggregates only, no cross-conn writes inside tenant txn | ✓ |
| FEAT-14 Retention & erasure | G18 | B6 | ✅ | **DONE** | `requestErasure`/`cancelErasure`/`purgeExpiredRetentions` on `TenantLifecycleService` (archived-only, `system.retention_grace_days` window); sweep inside `tenants:enforce-lifecycle`; full purge incl. backups; erasure UI on Show.vue | +4 tests (`TenantRetentionTest`) | clean — audited `tenant.erasure_*`/`tenant.deleted`; **deviation**: no auto-export inside purge (artifact would self-delete; manual export is the recovery path) | ✓ |
| FEAT-16 Audited impersonation | G11 | Track X | ❌ | BLOCKED | — | — | — | **Explicit approval + threat model + dedicated security tests; standalone, never batched** |
| FEAT-17 API foundation | G8 | Decision | ❌ | BLOCKED | — | — | — | Decision pending: session-JSON vs Sanctum vs no-API (see plan) |
| FEAT-18 Billing execution | G6 | Decision | ❌ | BLOCKED | — | — | — | Product architecture decision — 9-item decision surface in plan |
| FEAT-19 Runtime feature flags | G14 | — | ☐ | DEFERRED | — | — | — | Registry can host flags; build only on concrete need |

## Change Log

| Date | Feature | Change | Verified by |
|---|---|---|---|
| 2026-10-01 | — | Plan + tracker created; zero code written | — |
| 2026-10-01 | all | Governance hardened: gate chain (Baseline→Design→Code→Test→Mini-audit), STOP rule, FEAT-10 rescoped (stubs protected), FEAT-16 standalone, FEAT-17/18 decision-blocked, batch order baseline→security→cleanup | — |
| 2026-10-01 | B0 | Baseline recorded: 115 tests / 984 assertions green | `php artisan test` |
| 2026-10-01 | FEAT-01 | Suspended/inactive tenant users blocked at login + mid-request (`EnsureTenantUserActive`) | +3 tests, suite 118 ✓ |
| 2026-10-01 | FEAT-03 | Logo upload (tenant settings) + avatar upload (profile); `FormField` gained `type=file`; FND-018 verified already-fixed | +4 tests, build ✓, suite 127 ✓ |
| 2026-10-01 | FEAT-05 | `tenants:enforce-lifecycle` hourly: expired trial→suspend, expired subscription→Expired+suspend; owner decision: fail-closed | +5 tests ✓ |
| 2026-10-01 | FEAT-10 | 10 dead scaffold files deleted after grep zero-ref proof; `stubs/` untouched | build + suite ✓ |
| 2026-10-01 | FEAT-04+02 | Mail-first notifications (welcome/status listeners on existing events) + full password reset flow (guest pages, tenant-scoped broker, tenant-domain reset URLs) | +8 tests, suite 135 ✓ |
| 2026-10-01 | infra | **Finding**: stale `bootstrap/cache/{config,routes-v7,events}.php` from a prior `optimize` poisoned the test env (`app.env=local` → CSRF bypass off, new routes/listeners invisible). Cleared via `config:clear route:clear event:clear`. Rule: never run `optimize`/cache commands on dev; caches must be rebuilt after any route/listener/config change. | manual |
| 2026-10-01 | FEAT-09 | Full email verification (MustVerifyEmail + `verified` on all tenant route groups + signed:relative tenant-domain links + VerifyEmail.vue) + honeypot bot rejection on both registration surfaces; owner decision: no captcha dependency | +6 tests, suite 141 ✓ |
| 2026-10-01 | FEAT-13+20 | Backup registry/command/controller + signed downloads; diagnostics StatCards on Tenants/Show | +6 tests |
| 2026-10-01 | FEAT-11+15+14 | Webhooks (endpoints/deliveries, HMAC job, UI+nav), usage metering (hourly snapshots), retention/erasure (grace window + sweep) | +9 tests, suite 162 ✓, pint ✓, build ✓ |

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
