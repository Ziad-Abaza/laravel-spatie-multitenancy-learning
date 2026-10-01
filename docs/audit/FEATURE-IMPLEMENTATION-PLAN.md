---
noteId: "a098ea30bd2d11f1b0d837b196dd4a0c"
tags: []

---

# Feature Implementation Plan — Gap Remediation Registry

> Governance registry for closing the gaps proven in `FEATURE-GAP-ANALYSIS.md`.
> **No feature may be implemented before its record below passes every Gate.**
>
> Sources of truth: `docs/audit/MASTER-AUDIT.md` + `docs/audit/FEATURE-GAP-ANALYSIS.md`
> Companion tracker: `docs/audit/REMEDIATION-PROGRESS.md`
> Created: 2026-10-01 · Status: **PLANNING — implementation NOT started**

---

## 0. Reconciliation Note (audit drift)

`MASTER-AUDIT.md` reflects the code at audit time; a remediation pass has since
landed. Verified in current code (read today): throttle on auth/registration
(FND-041 ✅), `tenantDomain()` SSoT (FND-053 ✅), `assertTransition` state machine
(FND-054 ✅), provisioning transaction + compensation (FND-055 ✅), `allow_registration`
enforced server-side (FND-037 ✅), real storage usage (FND-064 ✅), `PlanController::destroy`
with attach-guards (FND-065 ✅), SQL MRR + `mrr` key (FND-056/061 ✅), env-gated seed
passwords (FND-029 ✅), broker `connection=tenant` (FND-031 ✅), dead sanctum API routes
removed (FND-039/058 ✅), `is_public` dropped (FND-062 ✅), `PrefixCacheTask` present
(FND-015 ✅), isolated sqlite test topology + `RefreshDatabase` (FND-067 ✅),
`package-lock.json` present (FND-002 ✅).

**Zero-Regression implication:** every one of these fixes is a *regression guard*
requirement — the test suite must cover them, and no new feature may reintroduce
the failure mode.

---

## 0.5 Mandatory Gate Chain (per feature, non-negotiable)

Every feature executes this chain in order. A gate failure stops the feature —
no partial "carry on and fix later".

```text
BASELINE GATE → DESIGN GATE → CODE GATE → TEST GATE → MINI-AUDIT
```

### Baseline Gate (before ANY code)

1. Run the full existing test suite (`php artisan test`) and record the result
   (pass count, failures, duration) in `REMEDIATION-PROGRESS.md` before touching
   a single file.
2. Inspect **every file the feature will touch in full**, and trace its
   consumers/dependencies — not just the lines being changed.
3. Confirm no open `MASTER-AUDIT.md` finding intersects the touched surface.

A baseline that is already red must be explained in writing before proceeding —
new work on a broken baseline hides regressions.

### Design Gate (before code for non-trivial features)

For any feature touching boot lifecycle, route registration, guards, schema, or
cross-context behavior (FEAT-04, 07, 09, 11, 12, 13, 14, 15, 16, 17, 18), the
record below is **not sufficient authorization to write code**. A short design
note must first answer: exact insertion points, failure modes, blast radius on
cache/session/Inertia/permissions, and rollback path. Then it is reviewed.

### Code Gate

- Reuse > Extend > Create — proven per record.
- No hardcoded business rules, statuses, permissions, locales, module names.
- No new abstraction unless existing architecture is proven insufficient
  (evidence required in the record).

### Test Gate

- Feature tests covering the new behavior AND the boundary it touches
  (tenant↔landlord, suspended↔active, permission-denied paths).
- **Re-run the baseline suite.** Compare against the recorded baseline: any new
  failure = regression → stop, do not proceed to the next feature.
- Green tests alone don't count — the suite must actually cover the previous
  behavior (e.g. a fix isn't proven by deleting the assertion that failed).

### Post-implementation Mini-Audit

Recorded in the feature record + `REMEDIATION-PROGRESS.md`: clean code, SOLID,
HMVC boundaries, SSoT, security, tenant isolation, performance — touched files only.

### Completion Contract — a feature is DONE only when ALL hold

1. Existing tests pass — recorded **before** implementation (baseline).
2. Every touched file was inspected in full.
3. Every dependency/consumer of touched code was traced.
4. No existing SSoT was duplicated.
5. No new hardcoded business rule/config/status/permission/locale/module name.
6. No new abstraction unless existing architecture was proven insufficient.
7. Tenant/landlord boundaries were explicitly tested.
8. Existing security invariants were regression-tested.
9. Existing UI components and patterns were reused where applicable.
10. Feature tests pass.
11. Full relevant test suite passes.
12. Build/lint/static checks pass (`npm run build`, `pint`).
13. Post-implementation mini-audit recorded.
14. `MASTER-AUDIT.md` findings are not reopened or regressed.
15. **Protected assets — including `stubs/` — are never removed or altered.**
16. Any unexpected finding stops implementation and is documented first.

### STOP Rule (overrides everything)

> If implementation reveals an architectural contradiction, undocumented
> dependency, SSoT conflict, security concern, or previously unknown behavior:
> **STOP. Do not patch around it.** Document it in this registry, update the
> record, resolve the decision, then continue. The "found problem → workaround →
> continue" pattern is exactly what produces the next cleanup cycle.

Known still-open FNDs relevant to feature work: FND-018 (media storage not
tenant-pathed — **blocks G10**), FND-030 (tenant DB credentials plaintext —
**blocks G13/G18**), FND-032 (`is_system` schema drift tenant vs landlord —
**touches G5**), FND-026/FND-050 (session write / translation payload — perf,
non-blocking), FND-043 (N+1 in users index — fix opportunistically, not a feature).

---

## 1. Feature Records

Legend: Gates = ✅ pass · ⚠️ conditional · ❌ blocked · Status = PLANNED/IN-PROGRESS/DONE/BLOCKED.

---

### FEAT-01 — Tenant Suspended-User Enforcement (G3)

| Field | Value |
|---|---|
| Business Purpose | A suspended workspace member must not authenticate or retain a live session. Today `User.status=suspended` is settable but unenforced — privilege that was revoked still works. |
| Modules | Access |
| Tables | `users.status` (exists), `sessions` (landlord-owned, shared) |
| Permissions | none new — enforcement, not a capability |
| Components | none (login error uses existing validation-flash pattern) |
| APIs | n/a |
| Services | `ManagementPolicy` (suspend path), `TenantUserService` |

**Reuse answers** — Reuse existing architecture? YES — mirror `EnsureLandlordAdminActive` as a tenant-side middleware (`EnsureTenantUserActive`); do NOT create a new abstraction. Reuse components? n/a. Reuse permissions? n/a. Reuse workflows? YES — same post-auth pattern as `landlord.active`. Reuse UI? YES — login error via `ValidationException` like `LandlordAuthController`.

**Architecture Gate:** ✅ single middleware appended to the `tenant` group / `auth:web` routes + login pre-check in `TenantAuthController::login` (mirroring `LandlordAuthController:30-36`). No new layers. Extends the *existing* guard boundary.
**Tenant Gate:** Tenant→Tenant ✅ (status read from tenant DB, already in context); Landlord→Tenant n/a; Queue n/a; Cache n/a (permissions cache untouched); Storage n/a; API ✅ (api group gets same middleware via `tenant` group reuse); Notification n/a. **No leakage.**
**Security Gate:** AuthN ✅ (status checked at `attempt` + mid-request); AuthZ ✅; Permissions n/a; IDOR n/a; PrivEsc ✅ closes the gap; MassAssign ✅ (`status` already guarded in `UserController`); Validation ✅; Audit — record `auth.suspended_blocked` only if FEAT-07 lands first, else skip (no new audit infra for this).
**Database Gate:** ✅ no migration — `users.status` + `UserStatus` enum exist.
**UI Gate:** ✅ reuse flash/error translation keys; add `account_suspended` key to Access lang files (en/ar).
**Risks:** suspended owner self-lockout is already impossible (ManagementPolicy blocks self-status-change) — verify test.
**Status:** PLANNED · Priority rank 1

---

### FEAT-02 — Password Reset Flow (G1)

| Field | Value |
|---|---|
| Business Purpose | Users who lose credentials get a self-service recovery path; today locked-out users have none. |
| Modules | Access |
| Tables | `password_reset_tokens` (exists in tenant DB; broker already `connection=tenant`) |
| Permissions | none new — public guest flow |
| Components | `FormField`, `BaseButton`, `GuestLayout` (all exist) |
| APIs | n/a (web flow) |
| Services | native `Password` broker — no service class needed |

**Reuse answers** — Architecture? YES — Laravel broker, configured correctly already. Components? YES — GuestLayout/FormField/BaseButton. Permissions? n/a. Workflows? YES — `throttle:` middleware pattern as on login. UI? YES — mirror `Access/Auth/Login.vue` shape.
**Architecture Gate:** ✅ routes `forgot-password`/`reset-password` + controller methods inside existing `TenantAuthController` (or thin `PasswordResetController` in Access) — guest:web group.
**Tenant Gate:** all vectors ✅ — broker pinned to `tenant` connection (FND-031 fix must be regression-tested); token + reset happen inside current tenant only; email is the cross-context boundary → tenant-local addresses only. **Requires notification delivery → see dependency.**
**Security Gate:** AuthN guest-only; AuthZ n/a; IDOR ✅ token-guarded; PrivEsc ✅ cannot reset other users (token bound to email); MassAssign ✅ broker handles; Validation ✅ `Password::defaults()`; Audit — `auth.password_reset` action (defer to FEAT-07 infra); throttle `6,1` request + broker `throttle:60`.
**Database Gate:** ✅ table exists, zero migrations.
**UI Gate:** ✅ two pages (`Auth/ForgotPassword.vue`, `Auth/ResetPassword.vue`) composed from shared components only.
**Dependency:** notification/mail channel (FEAT-04 minimal baseline ships together — the `ResetPassword` notification IS the first consumer; `MAIL_MAILER=log` already suffices for dev).
**Status:** PLANNED · Priority rank 2

---

### FEAT-03 — Media Upload Endpoints: Tenant Logo + User Avatar (G10)

| Field | Value |
|---|---|
| Business Purpose | Finish half-built capability: branding reads `logo` media and `getAvatarUrl()` reads `avatars`, but nothing writes them. |
| Modules | Settings (logo), Access (avatar) |
| Tables | `media` (exists, both contexts) |
| Permissions | `settings.manage` (logo), self-service profile (avatar) |
| Components | `FormField` file input / `FormModal` |
| APIs | n/a |
| Services | medialibrary `InteractsWithMedia` (already on both models) |

**Reuse answers** — all YES: existing collections, existing settings/profile endpoints, existing permission keys. Extend `TenantSettingsController::update` + `ProfileController::update` — no new controllers.
**Architecture Gate:** ⚠️ **FND-018 blocker**: `config/media-library.php` `disk_name=public`, no tenant prefix — uploaded files are enumerable cross-tenant at `/storage/{id}`. Must first set `TenantAwarePathGenerator` (exists! `app/Support/TenantAwarePathGenerator.php` — verify it's wired in config) or per-tenant disk prefixing. Fix at the source, not via a wrapper.
**Tenant Gate:** Storage → Tenant = THE gate item: path must include tenant key; Cache n/a; Queue n/a (unless conversions queued — keep sync); API n/a.
**Security Gate:** Validation ✅ (`image`, `max:2048`, mime whitelist); MassAssign ✅ (`addMediaFromRequest('logo')`); IDOR ✅ (own tenant/self only); PrivEsc ✅ permission-gated; Audit optional (`settings.updated`); storage-quota accounting ✅ already counted by `QuotaService::getStorageUsageMb` — free enforcement.
**Database Gate:** ✅ none.
**UI Gate:** ✅ file input inside existing settings/profile forms; no new patterns.
**Status:** PLANNED (conditional on FND-018 fix inside this feature) · Priority rank 3

---

### FEAT-04 — Notification Subsystem Baseline (G2)

| Field | Value |
|---|---|
| Business Purpose | Zero outbound communication today — no welcome, trial-ending, suspension, or reset notices despite 6 domain events with zero listeners. |
| Modules | Core (channels/contracts), consumers in Landlord/Subscription/Access |
| Tables | `notifications` (database channel) — **two-context decision required**: landlord `notifications` table for platform mail, tenant table for workspace notifications |
| Permissions | `notifications.view` (tenant read) — new key, extend `TenantPermissions` manifest |
| Components | `StatusBadge`/`EmptyState` reuse; new minimal bell widget only if in-app feed chosen |
| APIs | n/a |
| Services | listeners on existing events: `TenantCreated`→welcome, `TenantStatusChanged`→suspend/activate notice, trial-ending → new scheduled check (FEAT-05) |

**Reuse answers** — Architecture? YES — Laravel Notifications + tenant-aware queue config already done (`queueable_to_job` maps `SendQueuedNotifications`). Components? partial (list UI is new but composed from shared parts). Permissions? extend existing manifests. Workflows? YES — existing events. UI patterns? YES.
**Architecture Gate:** ⚠️ Notification table placement is the design crux — keep it honest: `Mail`-only MVP first (no DB channel), add in-app feed later only if needed. No "NotificationService" wrapper — use `->notify()` directly per Laravel conventions (AGENTS: no unnecessary abstraction).
**Tenant Gate:** THE critical feature for this gate — mailables/notifications dispatched inside tenant context must be `TenantAware` (default ✅ per config); landlord mail must be `NotTenantAware`; a tenant's notifications table lives in its DB. Cache n/a; Storage n/a; API n/a.
**Security Gate:** AuthZ ✅ (notifications to own tenant users); MassAssign n/a; Validation n/a; Audit — delivery failures logged; PrivEsc n/a.
**Database Gate:** ⚠️ deferred — mail-only MVP needs zero tables; DB-channel feed adds `notifications` migration to BOTH migration dirs.
**UI Gate:** ✅ toast/flash surface already exists; bell deferred.
**Status:** PLANNED · Priority rank 6 · **Unblocks FEAT-02 delivery, FEAT-05 alerts**

---

### FEAT-05 — Lifecycle Automation Scheduler (G7)

| Field | Value |
|---|---|
| Business Purpose | Trial expiry currently does nothing; `past_due` enum never set; no periodic maintenance. |
| Modules | Landlord (jobs), Subscription (transitions), Core (schedule registration) |
| Tables | none new |
| Permissions | none new |
| Components | none (backend) |
| APIs | n/a |
| Services | `TenantLifecycleService` (extend with `expireTrial`), `SubscriptionService` |

**Reuse answers** — all YES: state machine + `TenantStatusChanged`/`SubscriptionUpdated` events + scheduler in `routes/console.php`. New code = scheduled commands only, transitions stay inside existing service.
**Architecture Gate:** ✅ `Schedule` entries in `routes/console.php`; transitions via `TenantLifecycleService` (single mutation SSoT preserved).
**Tenant Gate:** commands iterate `Tenant::all()` on landlord connection; per-tenant work via `$tenant->execute()`; Queue ✅ TenantAware jobs; Cache ✅ prefixed; no cross-context writes.
**Security Gate:** console-only, no web surface; Audit ✅ emit events → audit records when FEAT-07 lands; no validation surface.
**Database Gate:** ✅ none — statuses/datetime columns exist.
**UI Gate:** n/a (surfaced via existing dashboard metrics).
**Decision needed:** expiry semantics — `trialing` → `suspended` (fail-safe) vs grace period; recommend suspend + notice via FEAT-04.
**Status:** PLANNED · Priority rank 4

---

### FEAT-06 — Session & Credential Hygiene (G17)

| Field | Value |
|---|---|
| Business Purpose | Password changes don't revoke other sessions; users can't see/revoke their own sessions; no login history. |
| Modules | Access |
| Tables | `sessions` (exists, landlord connection) — **decision: shared session table means tenant sessions live centrally; listing must filter by `user_id`+guard column** |
| Permissions | none new (self-service) |
| Components | `DataTable`, `ConfirmDialog`, `Panel` |
| APIs | n/a |
| Services | `Auth::logoutOtherDevices()` on password change |

**Reuse answers** — all YES.
**Architecture Gate:** ✅ extend `ProfileController` + profile page; `logoutOtherDevices` is one line on the existing update path.
**Tenant Gate:** ⚠️ **storage vector** — sessions table is landlord-owned (documented in .env.example); session rows carry no tenant key → the session-browser must filter `user_id` AND verify current tenant to avoid cross-tenant session enumeration. Mitigation: only revoke/show rows matching current user id — IDOR-safe by construction.
**Security Gate:** all ✅ — self-scope only, `current_password` re-auth for revocation, audit `auth.session_revoked`.
**Database Gate:** ✅ none.
**UI Gate:** ✅ `DataTable` + `ConfirmDialog` on existing `Profile/Edit.vue`.
**Status:** PLANNED · Priority rank 5

---

### FEAT-07 — Audit Trail Expansion + Viewer (G5)

| Field | Value |
|---|---|
| Business Purpose | Lifecycle ops, tenant user/role mutations, and auth events are unaudited; existing `admin_audit_logs` has no viewer. |
| Modules | Access (writer), Landlord (viewer + lifecycle audit) |
| Tables | `admin_audit_logs` (exists); **new** `audit_logs` in tenant migration set (tenant-context writes can't reach landlord table — `AuditWriter` throws by design) |
| Permissions | landlord `audit.view` (new manifest key); tenant `audit.view` (new key, Owner/Admin) |
| Components | `EnterpriseDataGrid`, `FilterSelect`, `StatusBadge`, `DataTable` |
| APIs | none |
| Services | `AuditWriter` — extend with context-aware model resolution (tenant `AuditLog` model vs `AdminAuditLog`), NOT a new parallel service |

**Reuse answers** — Architecture? YES — extend `AuditWriter` + add sibling `AuditLog` tenant model. Components? YES — grid+filters. Permissions? extend manifests. Workflows? YES — record calls inside existing mutation transactions. UI? YES — grid pattern.
**Architecture Gate:** ⚠️ requires one design decision: single `AuditWriter` resolving model by context (landlord `AdminAuditLog` / tenant `AuditLog`) — keeps one API, honors the existing landlord-only guard by making context explicit, no intermediate layer.
**Tenant Gate:** tenant writes go to tenant table inside tenant txn ✅; landlord writes unchanged ✅; viewer queries are context-local ✅; no cross-context reads.
**Security Gate:** append-only preserved (`UPDATED_AT=null`, no update/delete routes); viewer permission-gated; sensitive `before/after` must redact `password` keys; Audit self-referential ✅.
**Database Gate:** ⚠️ new `audit_logs` table in `database/migrations/tenant/` — justified (different scope, same shape); **also** resolve FND-032 `is_system` drift while touching schema (separate decision: add column to tenant roles or scope flag to landlord-only).
**UI Gate:** ✅ `Landlord/Audit/Index.vue` + `Access/Audit/Index.vue` composed from shared grid.
**Status:** PLANNED · Priority rank 7

---

### FEAT-08 — Observability Basics: Auth Logging + Failure Surface (G16)

| Field | Value |
|---|---|
| Business Purpose | Failed logins are throttled but invisible — no security event trail, no queue/health visibility beyond `/up`. |
| Modules | Access (listeners on `Login`/`Failed`/`Logout` events), Landlord (surface) |
| Tables | reuse `admin_audit_logs` / FEAT-07 `audit_logs` (`auth.login_failed`, `auth.login`, `auth.logout` actions) — **do NOT create `auth_logs` table** |
| Permissions | reuse `audit.view` from FEAT-07 |
| Components | viewer = FEAT-07 grid |
| APIs | n/a |
| Services | `AuditWriter` (extended by FEAT-07) |

**Reuse answers** — all YES; this feature is "wire existing auth events into the audit writer," nothing else.
**Architecture Gate:** ✅ listeners in existing (currently empty) `EventServiceProvider::$listen` — the designed hook point.
**Tenant Gate:** tenant auth events → tenant `audit_logs`; landlord auth events → `admin_audit_logs`. ✅
**Security Gate:** never log passwords/tokens; IP + user-agent only; rate of logging bounded by existing throttle.
**Database Gate:** ✅ piggybacks FEAT-07 migration.
**UI Gate:** ✅ reuse.
**Status:** PLANNED · Priority rank 8 · **Depends on FEAT-07**

---

### FEAT-09 — Registration Hardening + Email Verification (G19 + G4)

| Field | Value |
|---|---|
| Business Purpose | Public registration provisions real databases with only throttle protection; no proof-of-email or bot defense. |
| Modules | Access (user verify), Landlord (tenant-register verify) |
| Tables | `users.email_verified_at` exists; `password_reset_tokens` pattern reusable for verification via signed URLs — **no new table** |
| Permissions | none |
| Components | `EmptyState` (verify notice), existing auth pages |
| APIs | n/a |
| Services | `MustVerifyEmail` on `Modules\Access\Models\User` + signed verification routes; captcha middleware (Turnstile/hCaptcha — **new dependency → approval required** per AGENTS "do not change dependencies without approval") |

**Reuse answers** — all YES (Laravel native verification, existing mail channel via FEAT-04).
**Architecture Gate:** ⚠️ captcha adds an external dependency — record decision; fallback = honeypot + throttle hardening (zero deps).
**Tenant Gate:** verification routes are tenant-scoped (signed URL per tenant DB user) ✅; tenant-register flow stays landlord-side; email send is TenantAware ✅.
**Security Gate:** signed URLs, `signed` middleware, resend throttled, no enumeration (uniform response).
**Database Gate:** ✅ none.
**UI Gate:** ✅ notice/verify pages from shared components.
**Status:** PLANNED · Priority rank 9/10 · **Depends on FEAT-04**

---

### FEAT-10 — Dead Scaffold Cleanup (G20) — **scope corrected**

| Field | Value |
|---|---|
| Business Purpose | `Modules\Tenant` ships an unrouted CRUD controller + 4 pages — unreachable dead code that misleads contributors. Narrow cleanup of proven-unreachable scaffold only. |
| Modules | Tenant, Core, Access |
| Tables/Permissions/APIs | none |
| Components | deleting unused only |

**PROTECTED — out of scope, never touched:**
- `stubs/nwidart-stubs/` — library-owned generator stubs. **Do not delete or modify**, regardless of FND-020/068 "dead weight" observations. Protected asset per Completion Contract §0.5-15.
- Per-module `package.json`/`vite.config.js`/`composer.json` — may be load-bearing for nwidart registration; leave unless proven dead *and* safe.

**In scope (must prove zero references before each deletion):** `Modules\Tenant\app\Http\Controllers\TenantController.php` + `Pages/{Index,Create,Edit,Show}.vue` (unrouted — proven: only `/dashboard` is routed); `CoreController` + unrouted Core scaffold pages; `AccessController` + unrouted scaffold pages.

**Reuse answers** — n/a (deletion task).
**Architecture Gate:** ✅ each deletion is gated on a fresh `grep` proof of zero references at execution time, not on this plan's claim.
**Tenant Gate:** n/a · **Security Gate:** removing unreachable code only. **DB Gate:** n/a · **UI Gate:** no shared component is touched.
**Status:** PLANNED · moved to its own cleanup batch AFTER security/correctness work — scaffold removal never outranks a live security gap.

---

### FEAT-11 — Outbound Webhooks (G9)

| Field | Value |
|---|---|
| Business Purpose | The 6 domain events already exist; external systems (billing, CRM, ops) need push integration for a real SaaS platform. |
| Modules | Landlord (endpoint registry + dispatcher), Core (event payload contract) |
| Tables | `webhook_endpoints` (url, secret, events[], active), `webhook_deliveries` (status, attempts, response) — **landlord-only** |
| Permissions | `webhooks.view`, `webhooks.manage` — extend `LandlordPermissions` manifest (sensitive class) |
| Components | `EnterpriseDataGrid`, `FormModal`, `StatusBadge` |
| APIs | n/a (outbound) |
| Services | queued dispatcher job (tenant-aware queues already configured) listening on existing events |

**Reuse answers** — Architecture? YES — listeners on existing events + queued `Http` dispatch. Components YES. Permissions — extend manifest. Workflows YES.
**Architecture Gate:** ✅ no parallel event bus; job = standard Laravel queued job.
**Tenant Gate:** webhooks are a landlord/platform surface — payload must contain tenant *identifiers* (id/slug/domain), never tenant DB content; dispatcher job `NotTenantAware`, queries landlord tables only. Queue ✅ explicitly `NotTenantAware`.
**Security Gate:** HMAC signature header, HTTPS-only URLs, secret rotation, retry backoff + dead-letter after N attempts, no payload PII beyond contract, permission `sensitive`.
**Database Gate:** ⚠️ two landlord tables — justified (new domain, no existing fit).
**UI Gate:** ✅ `Landlord/Webhooks/Index.vue` from shared grid+modal.
**Status:** PLANNED · Priority rank 11

---

### FEAT-12 — Per-Tenant Module Entitlements (G15)

| Field | Value |
|---|---|
| Business Purpose | Modules toggle globally today; plans declare `features` strings that map to nothing. Connecting plan entitlements → module access is the monetization backbone. |
| Modules | Subscription (entitlement SSoT), Landlord (tenant_module overrides), Core (route/menu gating) |
| Tables | `tenant_modules` pivot or `settings`-style overrides on `tenants.settings` — **prefer extending `tenants.settings` json** (existing SSoT) over new table |
| Permissions | `modules.manage` reuse + `plans.manage` (entitlement authoring) |
| Components | `PermissionBundlePicker`-style selector inside plan form; module visibility in `TenantLayout` nav |
| APIs | n/a |
| Services | `QuotaService::canUseFeature` (already the check point — extend to module keys) |

**Reuse answers** — Architecture? YES — `Plan::hasFeature` + `canUseFeature` already the entitlement seam; module registry already exists (nwidart). Components YES. Permissions YES. Workflows YES.
**Architecture Gate:** ❌→⚠️ **MANDATORY DESIGN GATE — this record is NOT authorization to code.**
Filtering module routes at registration time intersects the nwidart boot lifecycle, route caching, `modules_statuses.json`, the Inertia nav tree, and permission surfacing. Before any code, a design note must prove:
- exactly where module-route filtering sits (route registration vs middleware vs nav-only),
- whether disabling a module mid-runtime affects boot, cached routes, or queued jobs referencing it,
- how nav/menu entries derive visibility (single map consumed by both gate and UI — no second source),
- what happens to a tenant mid-request when an entitlement is revoked,
- that disabled-module URLs return 404/403 without leaking existence.
**Tenant Gate:** map is landlord-side data read during tenant requests → cache per tenant (`settings.map` pattern reusable); Queue ⚠️ tenant-aware jobs of a disabled module must not run — decided in design gate; no cross-tenant reads.
**Security Gate:** entitlement check server-side only; can't be bypassed by URL guessing; Audit on override change.
**Database Gate:** ⚠️ prefer `tenants.settings.entitlements[]` + plan `limits.features` — zero new tables; document the key in `settings` registry pattern.
**UI Gate:** ✅ extend `PlanFormFields` + Modules grid with per-tenant column.
**Status:** PLANNED · Priority rank 12 · **BLOCKED on Design Gate document**

---

### FEAT-13 — Tenant Backup & Export (G13)

| Field | Value |
|---|---|
| Business Purpose | Per-tenant DB backup/export — operational recovery + data portability; also fixes the false "automated backups" marketing claim by making it real. |
| Modules | Landlord |
| Tables | `tenant_backups` registry (tenant_id, path, size, created_at, driver) — landlord |
| Permissions | `tenants.export` (new sensitive key) |
| Components | `DataTable`, `BaseButton`, `ConfirmDialog` |
| APIs | download route (signed, admin-only) |
| Services | command `tenant:backup` (mysqldump / sqlite copy) + controller action in existing `TenantController` |

**Reuse answers** — all YES.
**Architecture Gate:** ✅ console command + admin trigger on existing tenant detail page.
**Tenant Gate:** operates on `tenant.database` explicitly with `Tenant::checkCurrent()` guard pattern; dumps land in `storage/app/backups/{tenant_slug}/` — landlord-side artifact, never served to tenant hosts.
**Security Gate:** ⚠️ FND-030 interaction — tenant rows may hold plaintext DB creds; backup command uses configured connection, not stored creds. Signed download, expiry, `sensitive` permission, audit record.
**Database Gate:** ✅ one registry table justified.
**UI Gate:** ✅ backup history panel on `Tenants/Show.vue` via `DataTable`.
**Status:** PLANNED · Priority rank 13

---

### FEAT-14 — Data Retention & Erasure (G18)

| Field | Value |
|---|---|
| Business Purpose | Archived tenants persist forever; no retention policy or erasure workflow — compliance exposure. |
| Modules | Landlord (policy), Tenant (export-before-delete) |
| Tables | `tenants.settings.retention_until` / `erasure_requested_at` — **extend existing columns**, no new table |
| Permissions | `tenants.lifecycle` reuse + `tenants.export` (FEAT-13) |
| Components | reuse ConfirmDialog + badge |
| APIs | n/a |
| Services | `TenantLifecycleService` (extend: `scheduleErasure`, `exportArchive`) + scheduled sweep via FEAT-05 scheduler |

**Reuse answers** — all YES; depends on FEAT-05 (scheduler) + FEAT-13 (export).
**Architecture Gate:** ✅ lifecycle service owns transitions — retention is a lifecycle concern, stays there.
**Tenant Gate:** purge = `dropTenantDatabase` (exists) + row delete; export precedes erasure; queued job `NotTenantAware`.
**Security Gate:** erasure is irreversible → ConfirmDialog + audit + grace window; export artifact ACL'd like FEAT-13.
**Database Gate:** ✅ settings-json reuse.
**Status:** PLANNED · Priority rank 14 · **Depends on FEAT-05 + FEAT-13**

---

### FEAT-15 — Usage Metering (G12)

| Field | Value |
|---|---|
| Business Purpose | Quota reads are point-in-time; metering gives per-tenant usage history for billing/fair-use/analytics. |
| Modules | Subscription (recording), Landlord (surface) |
| Tables | `usage_records` (tenant_id, metric, value, recorded_at) — landlord |
| Permissions | `metrics.view` (new read key) |
| Components | `StatCard` + grid reuse |
| APIs | n/a |
| Services | extend `QuotaService` reads to also record snapshots (scheduled, FEAT-05) |

**Architecture Gate:** ✅ snapshots written by scheduled command calling existing quota methods — no parallel metrics pipeline.
**Tenant Gate:** recording runs inside `$tenant->execute()` writing to landlord table — must run *after* `execute()` returns or pass scalar values out (never write cross-connection inside tenant txn).
**Security Gate:** read-only surface; aggregate-only, no user-level data.
**Database Gate:** ⚠️ one new table justified (time-series, no existing fit).
**Status:** PLANNED · Priority rank 15 · **Depends on FEAT-05**

---

### FEAT-16 — Audited Support Impersonation (G11)

| Field | Value |
|---|---|
| Business Purpose | Platform support cannot diagnose inside a workspace without credentials sharing — needs a time-boxed, audited "login as" bridge. |
| Modules | Landlord (initiate), Access (consume session) |
| Tables | `impersonation_tokens` (landlord: token, admin_id, tenant_id, user_id, expires_at) — or signed URL with expiry, **prefer signed URL (zero table)** |
| Permissions | `tenants.impersonate` — new sensitive key |
| Components | `BaseButton` action on `Tenants/Show.vue` user list |
| APIs | n/a |
| Services | none new — signed route → session login on `web` guard + audit writes both sides |

**Architecture Gate:** ⚠️ **highest-risk feature — exempt from all batches.** Crosses the guard boundary by design. Constraints: read-only impersonation (impersonated session flagged `impersonator_id`, ManagementPolicy blocks writes when flagged — extend the *existing* policy, no parallel check); hard expiry ≤30min; landlord-side start audit + tenant-side entry audit.
**Tenant Gate:** the bridge writes only a session on the tenant host; no DB crossing.
**Security Gate:** every vector in play — one-time signed URL, `sensitive` permission, audit mandatory, banner in UI (`ImpersonationBanner` inside `TenantLayout` — new component justified, single instance not repeated).
**Gate requirements (all mandatory before ANY code):**
1. Explicit owner approval — not implied by this plan.
2. Separate written threat model (abuse cases, token theft, session fixation, audit-tampering).
3. Dedicated security test suite (penetration-style): token replay, expired/forged URLs, cross-tenant token use, write attempts during impersonation, session persistence after expiry.
4. Standalone delivery — never bundled with another feature or batch.
**Status:** BLOCKED · Track X (standalone) · **Requires explicit sign-off + threat model before build**

---

### FEAT-17 — API Foundation (G8) — **reclassified: Decision-Blocked**

| Field | Value |
|---|---|
| Business Purpose | No programmatic surface exists — blocks integrations, mobile, automation, webhooks-in. |
| Modules | all (per-module `api.php` stubs exist — the designed extension point) |
| Tables | depends on auth decision |
| Permissions | reuse existing catalog — **never a second permission system** |
| Components | token management UI only if token auth is chosen |
| APIs | `api/v1/*` per module |
| Services | auth mechanism TBD |

**⚠️ Decision required before ANY design — unresolved contradiction:**
This plan previously assumed Sanctum. But MASTER-AUDIT FND-039/058 record that dead Sanctum apiResource routes were *removed as scaffolding debt* — re-adding Sanctum is not automatically justified. The real question is **who consumes the API**:

| Option | When correct | Cost |
|---|---|---|
| A. Session-authenticated JSON endpoints (no new dep) | API serves only the existing Inertia frontend / first-party automation | lowest — no package, no tokens |
| B. Sanctum tokens | third-party/headless/machine consumers need bearer auth | dependency + token model ×2 contexts + ability mapping |
| C. No API yet | nothing external consumes it today | zero — close the gap record instead |

Precedent (module `api.php` stubs + `api` middleware group already tenant-aware) supports *any* option. **Decision must come from actual consumer requirement, not "APIs are standard."** If B is chosen: abilities map to existing permission keys, tokens are guard-scoped and never interchangeable, `sensitive` permissions excluded initially.

**Architecture Gate:** pending decision · **Tenant Gate:** `IdentifyTenant` already on api group ✅ · **Security/DB/UI Gates:** evaluated after decision.
**Status:** BLOCKED — pending product decision (A/B/C) + dependency approval if B.

---

### FEAT-18 — Billing Execution (G6) — **reclassified: Product Architecture Decision, not a feature task**

| Field | Value |
|---|---|
| Business Purpose | Subscriptions are bookkeeping only — no charging, invoicing, proration, or dunning. The revenue path is the business reason the platform exists. |
| Modules | Subscription (epicenter), Landlord, Core events |

**This is not implementable as a feature record.** It is a product/architecture decision track whose outcome reshapes the data model. The decision surface — each item needs an explicit answer before any code:

| Decision | Options |
|---|---|
| Provider | Stripe via Cashier / Paddle / manual invoicing first |
| Money authority | provider-of-record vs local ledger reconciled to provider |
| Invoicing | provider-generated vs local `invoices` table |
| Proration | provider-native vs local policy |
| Dunning/`past_due` | provider webhooks vs FEAT-05 scheduler transitions |
| Tax/currency | single currency (`billing.default_currency` SSoT) vs multi |
| Failed payments | provider retry vs local grace → suspend via `TenantLifecycleService` |
| Reconciliation/idempotency | webhook event store + idempotent handlers |
| Refunds | out of scope v1 vs admin-initiated |

**Non-negotiables regardless of choice:** PCI boundary (hosted checkout — never store card data), webhook signature verification, full audit trail, billing records stay landlord-side, tenant sees own subscription only.
**Status:** BLOCKED — pending product decision. Removed from feature batches.

---

### FEAT-19 — Runtime Feature Flags (G14)

| Field | Value |
|---|---|
| Business Purpose | Plan entitlements are static; flags give kill-switches/rollout control without deploys. |
| Modules | Core (flag read), Landlord (flag admin) |
| Tables | reuse `settings`/`tenant_settings` registry — **flags ARE settings**; new domain `features` in the registry, not a new system |
| Permissions | `platform.settings.manage` / `settings.manage` reuse |
| Components | reuse settings panels |
| Services | `SettingService` reuse |

**Architecture Gate:** ✅ this is the registry doing its job — zero new infrastructure; only a new settings domain + a `feature()` helper reading it. Cheapest possible honest implementation.
**Tenant Gate:** ✅ settings inheritance already correct (tenant overrides landlord).
**Security Gate:** owner-scoped writes already enforced.
**Database Gate:** ✅ **zero migrations** — the strongest argument for this design.
**Status:** PLANNED · Priority rank 19 · **Defer until a concrete flag need exists (avoid speculative infrastructure)**

---

### FEAT-20 — Support Diagnostics Panel (G11 remainder)

| Field | Value |
|---|---|
| Business Purpose | Beyond impersonation: support needs tenant health view — DB reachability, user count, storage, last activity — without entering the workspace. |
| Modules | Landlord |
| Tables | none (live queries via `$tenant->execute()`) |
| Permissions | `tenants.view` reuse |
| Components | extend `Tenants/Show.vue` panels (`StatCard`, `DataTable`) |
| Services | extend `LandlordMetricsService` with per-tenant diagnostics method |

**Reuse answers** — all YES.
**Tenant Gate:** read-only `execute()` calls — existing proven pattern (`TenantController::show` already lists users); failures return degraded state (existing try/catch pattern).
**Security Gate:** read-only, `tenants.view`, no tenant data exfiltration beyond aggregates.
**Status:** PLANNED · Priority: bundled with FEAT-13/15 work on the same page

---

## 2. Execution Order (baseline-first, security before hygiene)

| Batch | Features | Rationale |
|---|---|---|
| **B0 — Regression/Architecture Baseline** | (no feature) | Run full suite, record baseline in `REMEDIATION-PROGRESS.md`, verify every regression guard listed there is test-covered. Nothing proceeds until the baseline is green and recorded. |
| **B1 — Security/Correctness** | FEAT-01, FEAT-03 (+FND-018), FEAT-05 | Live enforcement gaps first: revoked access that still works, unscoped storage, dead lifecycle automation. Scheduler lands early so later features ride it. |
| **B2 — Cleanup** | FEAT-10 | Scaffold removal *after* the security gaps are closed — hygiene never outranks a live vulnerability. `stubs/` protected. |
| **B3 — Communication spine** | FEAT-04, FEAT-02, FEAT-09 | Notifications baseline, then reset/verify flows consume it |
| **B4 — Audit & session depth** | FEAT-07, FEAT-08, FEAT-06 | Audit expansion enables auth-event logging; sessions harden auth |
| **B5 — Platform surface** | FEAT-12*, FEAT-13, FEAT-20, FEAT-11 | *FEAT-12 enters only after its Design Gate document is approved |
| **B6 — Enterprise ops** | FEAT-15, FEAT-14 | Metering + retention on top of the scheduler |
| **Track X — Standalone** | FEAT-16 | Explicit approval + threat model + dedicated security tests; never batched |
| **Decision-Blocked** | FEAT-17, FEAT-18 | No code until the recorded product/architecture decisions are made |
| **Deferred** | FEAT-19 | Only when a concrete flag need arises |

## 3. Standing Rules for Implementation

1. The §0.5 gate chain is executed per feature, in order — no skips, no batching of gates.
2. Every feature updates this file's record (Status → IN-PROGRESS → DONE) **and** `REMEDIATION-PROGRESS.md` (files, reason, impact, tests, risks, verification).
3. Any Gate marked ⚠️/❌ must be resolved to ✅ or escalated **before** code is written.
4. New dependencies require explicit approval (FEAT-09 captcha — honeypot fallback exists; FEAT-17 auth mechanism — decision pending; FEAT-18 PSP — decision pending).
5. Tests required per feature — extend the existing root `tests/Feature` suite conventions (`TestCase::provisionTenant`, isolated sqlite topology).
6. `stubs/` and any other protected asset are never deleted or modified.
7. **STOP rule is absolute** — found a contradiction? Document, decide, then continue.
