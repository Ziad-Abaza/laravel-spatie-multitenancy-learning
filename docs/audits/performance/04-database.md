---
noteId: "2e94d050bdde11f19d227386d2fcdb80"
tags: []

---

# Phase 4 — Database Investigation

Method: live inspection of `multivendor@127.0.0.1` (MySQL) via `php artisan db:table` (proven — connection works), review of every migration in `database/migrations/**` and `Modules/*/database/migrations/**`, all models, seeders. Row counts via tinker (tenants=2).

## Runtime-Verified Index Coverage

| Table | Indexes (verified via `db:table`) | Verdict |
|---|---|---|
| `tenants` | PK; UNIQUE `domain`, `database`, `slug`; idx `status`, `plan_id` | ✅ Hot path (`where domain`/`where slug` per request) covered |
| `sessions` | PK; idx `user_id`, `last_activity` | ✅ schema fine — placement is the issue (BE-01) |
| `cache`/`cache_locks` | PK key; idx expiration | ✅ schema fine — usage volume is the issue (BE-01) |
| `jobs` | PK; idx `queue` | ⚠️ missing composite (queue, reserved_at, available_at) for pop scan — low |
| `landlord_users` | PK; UNIQUE email; idx status | ✅ |
| `permissions`/`roles`/`model_has_*`/`role_has_permissions` | standard Spatie indexes | ✅ |
| `media` | morphs(model) composite; UNIQUE uuid; idx order_column | ✅ |
| `settings` | UNIQUE (domain,key); idx domain, key | ✅ |
| `tenant_settings` (tenant DBs) | UNIQUE (domain,key); idx domain, key | ✅ |
| `plans` | PK; UNIQUE slug | ⚠️ `is_active`/`sort_order` unindexed — tiny table, low |
| `subscriptions` | PK; idx tenant_id, plan_id, status | ⚠️ missing (status, trial_ends_at)/(status, ends_at) for lifecycle command; **no FK constraints** (DB-03) |
| `admin_audit_logs` | idx actor_id, action, created_at | ⚠️ missing (action, created_at) composite for filter+sort; LIKE %..% on labels always scans |
| `audit_logs` (tenant DBs) | same pattern | ⚠️ same + missing (target_kind, target_id) |
| `webhook_endpoints` | **PK only** | ⚠️ `active` unindexed — queried on every domain event (BE-05) |
| `users` (tenant DBs) | UNIQUE email; idx status | ⚠️ `created_at` unindexed (`latest()` sort); list unpaginated |

## Findings

### DB-01 — Schema drift: three tables referenced by code do NOT exist in the live DB
- **Severity:** High (correctness → also perf via errors/retries) · **Confidence:** Proven
- **Evidence:** `php artisan db:table` → `usage_records`, `webhook_deliveries`, `tenant_backups` all return "Table doesn't exist", yet migrations exist (`Modules/Landlord/database/migrations/2026_10_01_000010-000012`) and code queries them (`TenantController.php:274` usage records; `DispatchDomainEventWebhooks.php:37` WebhookDelivery::create; `TenantBackupController` full CRUD).
- **Root cause:** module migrations were not run for the module paths (auto-discover registers paths at boot, but `migrate` apparently only ran `database/migrations/landlord`), or ran in a different DB earlier. Also a stray artifact `database/tenant_suspend_8d16e2.sqlite` (127 KB) sits in `database/`.
- **Impact:** webhook dispatch INSERTs will throw on every domain event; backups/usage pages error — exceptions are expensive and the exception path (`ErrorPageRenderer`) rebuilds locale/theme props.
- **Reproduction:** `php artisan db:table tenant_backups` → WARN missing.
- **Fixes:** run module migrations (`php artisan migrate --path=Modules/Landlord/database/migrations --database=landlord` or per nwidart `module:migrate`); add a deploy step.

### DB-02 — Query volume, not missing indexes, is the database problem
- **Severity:** High · **Confidence:** Proven
- The per-request budget in `03-backend.md` (~25–33 queries/page) is the dominant DB cost. Hot lookups are indexed; the wins are eliminating *calls*, not adding indexes. Cross-reference BE-01/BE-02/BE-03.

### DB-03 — Missing FK constraints on billing columns
- **Severity:** Medium (integrity) · **Confidence:** Proven
- **Evidence:** `tenants.plan_id` bare `unsignedBigInteger` (`Modules/Landlord/database/migrations/2026_09_29_120001_add_saas_fields_to_tenants_table.php:29`); `subscriptions.tenant_id`/`plan_id` same (`2026_09_29_100002:23-24`). Indexed but not constrained → orphaned subscriptions possible; `plan.delete_blocked` is application-level only.
- **Fixes:** add FKs in a corrective migration (with orphan cleanup first).

### DB-04 — `users` table: unindexed `latest()` sort + unpaginated reads
- **Severity:** Medium · **Confidence:** Proven — `TenantUserService.php:34-35` (`latest()->get()`); no `created_at` index in tenant migrations. Small today; grows with `max_users` plan limit (9999, `PlanSeeder.php:76`).

### DB-05 — Audit tables: LIKE search + filter/sort without composite index
- **Severity:** Low · **Confidence:** Proven — `Modules/Access/.../AuditLogController.php:20-25`, `Modules/Landlord/.../AuditLogController.php:19-25`: `%like%` on `actor_label`/`target_label` (unindexable scan) + `action` filter + `latest('created_at')`. Composite `(action, created_at)` would serve filtered pagination; append-only tables grow monotonically.
- **Fixes:** composite indexes; prefix-only search or dedicated search columns for labels.

### DB-06 — `webhook_endpoints.active` unindexed
- **Severity:** Low · **Confidence:** Proven — migration `:27` + `DispatchDomainEventWebhooks.php:33` (`where active → get()` on every event). Tiny table today; flag for growth.

### DB-07 — Role/Permission connection resolves per-call at runtime
- **Severity:** Low (correctness risk more than perf) · **Confidence:** Highly Likely
- **Evidence:** `Modules/Access/app/Models/Role.php:19-24`, `Permission.php:14-19` — `getConnectionName()` picks landlord vs tenant via `Tenant::checkCurrent()` at call time. Any query issued under the wrong ambient context (e.g. inside a queued job after `makeCurrent`, or during permission cache warm-up on the wrong scope) silently hits the wrong DB. All other models use fixed `UsesLandlordConnection`/`UsesTenantConnection`.
- **Fixes:** split into explicit LandlordRole/TenantRole models or bind connection by callsite, not ambient state.

### DB-08 — N+1 inventory (all proven statically)
- `UserController::index` → `getAvatarUrl()` per user (media not eager-loaded) — BE-04.
- `HandleInertiaRequests::share` → `tenant.plan` lazy + `getFirstMediaUrl('logo')` — BE-03.
- `LandlordAdminController::index` — same per-row EXISTS pattern — BE-06.
- `DispatchDomainEventWebhooks` — per-endpoint `WebhookDelivery::create` in a loop — BE-05.
- `RecordTenantUsageCommand` — 2 INSERTs per tenant — BE-10.
- Seeders are clean (constant-size `updateOrCreate`).

### DB-09 — Test suite cost: full per-tenant provisioning per test
- **Severity:** Medium (CI speed) · **Confidence:** Proven
- **Evidence:** `tests/TestCase.php:20` `use RefreshDatabase`; `:33` `seedLandlordBaseline()` runs PlanSeeder + AccessBaselineProvisioner + 7 settings writes on **every** test; `provisionTenant()` (`:86`) runs real `TenantProvisioner` → sqlite file + all tenant migrations + seeders per call (~40 call sites).
- **Fixes:** frozen migrated sqlite template copied per test; `LazilyRefreshDatabase`; skip baseline where irrelevant.

## Unproven
- EXPLAIN plans / actual index selectivity — tables are near-empty in dev; index gaps marked ⚠️ become material only at scale.
