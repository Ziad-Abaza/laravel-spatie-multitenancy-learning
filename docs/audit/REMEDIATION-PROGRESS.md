---
noteId: "d3685f50bd1d11f1b0d837b196dd4a0c"
tags: []

---

# REMEDIATION PROGRESS — spatie-multitenant

**Source of truth for remediation state.** Baseline: `docs/audit/MASTER-AUDIT.md` (§9 Findings Register, §11.2 Clusters, §11.3 Roadmap).
**Started:** 2026-10-01
**Rules:** Minimal, root-cause-driven fixes. No new layers/resolvers/DTOs. Preserve modular boundaries, tenant isolation, least privilege, SSoT. Security findings take priority over cosmetic ones.

---

## CURRENT STATE

- **Current Phase:** Complete
- **Current Cluster:** — all 11 clusters closed
- **Current Finding:** — none pending

### Queue

- **Pending:** final verification sweep
- **In Progress:** —
- **Completed:** C1 (FND-029, FND-030, FND-033), C2 (FND-053, FND-057), C3 (FND-067, FND-006, FND-052; incl. dependency fixes FND-027 + provisioner tenant-migration defect), C4 (FND-058, FND-039, FND-065), C5 (FND-015, FND-018, FND-032, FND-042, FND-021, FND-022), C6 (FND-041, FND-031, FND-017, FND-037), C7 (FND-063, FND-061, FND-056, FND-064, FND-066), C8 (FND-055, FND-054), C9 (FND-001, FND-002, FND-003, FND-007, FND-008, FND-009, FND-011, FND-012, FND-016, FND-019, FND-020, FND-023, FND-024, FND-025, FND-027, FND-034, FND-040, FND-048, FND-050, FND-059, FND-062), C10 (FND-004, FND-005, FND-013, FND-028, FND-035, FND-044, FND-045; FND-068 REJECTED-BY-ADR-STUBS-001), C11 (FND-026, FND-036, FND-043, FND-046, FND-047, FND-049, FND-060)
- **Blocked:** —

### Phase plan (order = cluster order per mandate; tiers per §11.2)

| Phase | Cluster | Tier | Members |
|---|---|---|---|
| 1 | C1 Credential/secret hygiene | HIGH | FND-029, FND-030, FND-033 |
| 2 | C2 Domain resolution hardcoding | HIGH | FND-053, FND-057 |
| 3 | C3 Test infrastructure | HIGH | FND-067, FND-006, FND-052 |
| 4 | C4 Dead/missing API surface | MEDIUM | FND-058, FND-039, FND-065 |
| 5 | C5 Tenant isolation soft spots | MEDIUM | FND-015, FND-018, FND-032, FND-042, FND-021, FND-022 |
| 6 | C6 Auth completeness | MEDIUM | FND-041, FND-031, FND-017, FND-037 |
| 7 | C7 Billing/subscription integrity | MEDIUM | FND-063, FND-061, FND-056, FND-064, FND-066 |
| 8 | C8 Lifecycle atomicity | MEDIUM | FND-055, FND-054 |
| 9 | C9 SSoT / config drift | MEDIUM/LOW | FND-001, FND-002, FND-003, FND-007, FND-008, FND-009, FND-011, FND-012, FND-016, FND-019, FND-020, FND-023, FND-024, FND-025, FND-027, FND-034, FND-040, FND-048, FND-050, FND-059, FND-062 |
| 10 | C10 Scaffold debt | LOW | FND-004, FND-005, FND-013, FND-028, FND-035, FND-044, FND-045, FND-068 |
| 11 | C11 UX/FE polish | LOW | FND-026, FND-036, FND-043, FND-046, FND-047, FND-049, FND-060 |

**Unclustered-finding mapping decisions (root-cause based, documented here since the report left them unassigned):**
- FND-033 (seeder swallow + duplicate owner emails) → C1: same file/root cause area as FND-029.
- FND-007, FND-008, FND-009, FND-011, FND-012, FND-016, FND-023, FND-034 → C9: config/SSoT/docs drift.
- FND-021, FND-022 → C5: tenancy-context isolation/schema questions.
- FND-050 → C9: register text is the same whole-catalog-translations root cause as FND-025 (cluster table's "initTheme listener" label has no matching register entry; the scroll-lock item it likely meant is FND-047).
- FND-026, FND-036, FND-043, FND-047 → C11: UX/FE/perf polish.
- FND-013, FND-028, FND-035 → C10: dead assets/dependencies.
- FND-052 → C3: test-harness cluster (its own resolution path).
- Non-actionable: FND-010 (INFO — audit clean), FND-014 (superseded), FND-038 (RESOLVED), FND-051 (RESOLVED).

---

## ARCHITECTURAL DECISION RECORDS

### ADR-STUBS-001 — nwidart stub trees are protected operational assets (NON-NEGOTIABLE)

**Decision:** The directories `stubs/` (incl. `stubs/nwidart-stubs/`) and `Modules/*/stubs/` — and any files/content linked to them — are part of the operational structure of `nwidart/laravel-modules`. They are NOT dead code, NOT scaffold residue, and NOT deletion candidates during cleanup or refactoring.

**Rules:**
- Deleting, moving, or modifying these files is prohibited.
- They may not be treated as an architectural problem unless official `nwidart/laravel-modules` documentation proves they are unneeded.
- Any finding/refactor/cleanup proposing stub deletion violates this ADR and must be rejected.
- FND-068 is therefore resolved by decision: the stub tree stays. Its only actionable sub-part (stub *content* quality if generators are ever enabled) is out of scope because stubs are disabled (`stubs.enabled=false`) and protected regardless.

**Status:** Active — recorded 2026-10-01.

## PROTECTED PROJECT ASSETS

| Asset | Reason | Decision |
|---|---|---|
| `stubs/`, `stubs/nwidart-stubs/` (82 files) | nwidart/laravel-modules operational structure | ADR-STUBS-001 — never delete/move/modify |
| `Modules/*/stubs/` | Same | ADR-STUBS-001 |

---

## FINDINGS LEDGER

Status values: PENDING / IN PROGRESS / COMPLETED / BLOCKED / NO-ACTION (resolved or non-finding in audit).

### Phase 1 — Cluster C1: Credential / secret hygiene (HIGH)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-029 | HIGH | Hardcoded `Hash::make('password')` seeds ROLE_OWNER accounts on every tenant | database/seeders/DatabaseSeeder.php | COMPLETED | 2026-10-01 | 2026-10-01 | php -l pass | Password now `env('SEED_TENANT_OWNER_PASSWORD')`, falling back to `password` ONLY in `local` env; owner seeding skipped otherwise. Var documented in .env.example |
| FND-030 | MEDIUM | `db_username`/`db_password` stored plaintext on tenants rows; no encrypted cast | TenantSeeder.php; Modules/Landlord/app/Models/Tenant.php | COMPLETED | 2026-10-01 | 2026-10-01 | php -l pass; grep: no consumers | Root cause: columns are created by NO migration and read by NO code — dead legacy surface. Removed fillable entries + seeder writes + README example rows. Nothing to encrypt |
| FND-033 | LOW | `catch (\Throwable) {}` swallows assignRole failure; two near-duplicate owner emails | database/seeders/DatabaseSeeder.php | COMPLETED | 2026-10-01 | 2026-10-01 | php -l pass | Single `admin@{domain}` owner; empty catch removed — failures now surface |

### Phase 2 — Cluster C2: Domain resolution hardcoding (HIGH)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-053 | HIGH | `domain = subdomain.'.localhost'` hardcoded; ignores TENANT_DOMAIN_SUFFIX; unique-rule compares raw subdomain vs full domain | TenantProvisioner.php; TenantRegistrationController.php; HandleInertiaRequests.php; RegisterTenant.vue; Tenants/Create.vue; TenantSeeder.php; LandlordDatabaseSeeder.php | COMPLETED | 2026-10-01 | 2026-10-01 | php -l pass | Single construction point `TenantProvisioner::tenantDomain()` → `{slug}.{multitenancy.tenant_domain_suffix}`, request-host fallback; controller no longer passes domain; unique rule now checks composed domain; suffix shared to UI via `tenancy.domain_suffix` prop; seeders derive fixture domains from suffix. Landlord seeder admin password also env-gated (FND-029 family) + catch removed (FND-033 family) |
| FND-057 | LOW-MEDIUM | Public routes `/`, `/pricing`, `/register-tenant` not host-restricted → reachable on tenant hosts | Modules/Landlord/routes/web.php | COMPLETED | 2026-10-01 | 2026-10-01 | php -l pass | Wrapped in `Route::middleware('landlord')` (existing EnsureLandlordContext → 404 on tenant hosts) |

### Phase 3 — Cluster C3: Test infrastructure (HIGH)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-067 | HIGH | No RefreshDatabase; phpunit points at real `multivendor` MySQL DB; tests depend on seeded fixtures | phpunit.xml; tests/TestCase.php; tests/Feature/*; TenantProvisioner.php; TenantLifecycleService.php; TenantSeeder.php | COMPLETED | 2026-10-01 | 2026-10-01 | `php artisan test` — 96 passed / 919 assertions on isolated sqlite | In-memory sqlite landlord (RefreshDatabase: migrate:fresh once, txn-rollback per test); per-tenant sqlite files provisioned via the real TenantProvisioner and deleted in tearDown; deterministic env pinned in phpunit.xml (no MySQL); fixtures self-provisioned per test — suite runs on a fresh clone |
| FND-006 | MEDIUM | Same root cause: test suite targets dev DB name | phpunit.xml | COMPLETED | 2026-10-01 | 2026-10-01 | same run | `DB_LANDLORD_DRIVER=sqlite` + `DB_LANDLORD_DATABASE=:memory:`; no mysql host/db/user envs remain |
| FND-052 | RESOLVED-CONTEXT→LOW | Module `tests/` dirs empty; coverage exists only at root feature level | phpunit.xml | COMPLETED | 2026-10-01 | 2026-10-01 | full suite green incl. new suite registration | Added `Modules/*/tests` glob as a `Modules` testsuite so module tests can no longer be silently skipped; coverage remains at root feature level per audit downgrade |

**C3 dependency fixes (root causes that blocked an isolated suite):**
- `TenantProvisioner::runTenantMigrations` used spatie `MigrateTenantAction`, which runs a bare `migrate` inside `execute()` — that targets `database.default` (landlord), so provisioned tenant DBs were **never actually migrated**. Replaced with explicit `Artisan::call('migrate', ['--database'=>'tenant','--path'=>'database/migrations/tenant'])` inside `execute()` + `checkCurrent()` guard — the same pattern `RebuildDatabasesCommand` already uses.
- FND-027 (scheduled C9) fixed early as a C3 blocker: `Modules\Landlord\Models\Tenant` (parent of canonical `App\Models\Tenant`) was instantiated directly in 11 files → `Tenant::current()` `?static` TypeError on any provisioned tenant. All code now references the canonical `App\Models\Tenant` / `App\Models\User`; module parents remain as base classes only.
- sqlite handling normalized: under a sqlite landlord driver `tenants.database` now stores the DB file path (what the `tenant` connection actually consumes) in provisioner, lifecycle delete, and TenantSeeder.

### Phase 4 — Cluster C4: Dead/missing API surface (MEDIUM)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-058 | LOW-MEDIUM | 4 module api.php register `auth:sanctum` apiResources; sanctum not installed → guaranteed 500 | Modules/{Access,Landlord,Subscription,Tenant}/routes/api.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_sanctum_scaffold_api_routes_are_not_registered` — all /api/v1/* → 404; suite green | Files emptied to comment-only stubs (same convention as Core/Settings api.php); RouteServiceProviders still require the files so the api.php shell is kept |
| FND-039 | MEDIUM | Same root cause in Access module + scaffold AccessController reachable | Modules/Access/routes/api.php | COMPLETED | 2026-10-01 | 2026-10-01 | same test | Route registration removed → scaffold controller unreachable. Controller file deletion deferred to C10 scaffold sweep |
| FND-065 | MEDIUM | `DELETE /landlord/plans/{plan}` → nonexistent `PlanController::destroy`; Plans.vue ConfirmDialog calls it → guaranteed 500 | PlanController.php; en/ar.json | COMPLETED | 2026-10-01 | 2026-10-01 | 3 new tests: attached-tenant/refused, attached-subscription/refused, orphan/deleted | Implemented destroy: refuses plans with tenants or subscriptions (matches UI warning text), unsets default_plan_id reference, deletes orphan plans. New plan_delete_blocked/plan_deleted keys added to both locales (parity test requires identical key sets) |

### Phase 5 — Cluster C5: Tenant isolation soft spots (MEDIUM)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-015 | MEDIUM | No PrefixCacheTask: tenant `cache()` calls share landlord cache table keyspace | config/multitenancy.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_tenant_context_scopes_cache_keyspace` | Enabled `PrefixCacheTask` (prefix `tenant_id_{id}` while a tenant is current, restored on forget) — keyspace isolation on the shared store, composes with the already key-scoped permission cache |
| FND-018 | MEDIUM | Media/storage not tenant-scoped: `disk_name=public`, `prefix=''`, enumerable IDs at /storage/* | app/Support/TenantAwarePathGenerator.php; config/media-library.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_media_paths_are_scoped_to_the_owning_tenant`; full suite green | Media files now stored under `tenants/{id}/` — scoped by the OWNER record for Tenant-owned media (stable in every context) and by ambient tenant for tenant-side models. Landlord media keeps default path. Row storage unchanged (media table follows the owning model's connection) |
| FND-032 | MEDIUM | Landlord `roles.is_system` exists; tenant `roles` lacks it → shared Role model can't rely on column | database/migrations/tenant/2026_10_02_000001_add_is_system_to_roles_table.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_tenant_schema_is_isolated…` asserts column; suite green | New tenant migration aligns roles schema |
| FND-042 | LOW | AuditWriter writes AdminAuditLog (landlord conn) — escapes tenant txn atomicity if called in tenant context | Modules/Access/app/Services/AuditWriter.php | COMPLETED | 2026-10-01 | 2026-10-01 | grep: call sites are landlord-only (SyncLandlordAccessCommand, LandlordAdminController, LandlordRoleController); suite green | Constrained at source: `record()` now throws LogicException if invoked with a current tenant — the audit trail is landlord-centralized by design and can no longer silently escape a tenant transaction |
| FND-021 | MEDIUM | Single Role/Permission model pair configured for both landlord+tenant contexts | Modules/Access/app/Models/{Role,Permission}.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_role_and_permission_models_follow_tenancy_context` + existing landlord/tenant behavior suite | Verified mechanism: `getConnectionName()` resolves tenant/landlord by `Tenant::checkCurrent()` and ScopePermissionCacheTask scopes the registrar key per context — the design is sound; verified with an explicit context test |
| FND-022 | LOW | Tenant migrations create cache/jobs tables never used (queue+cache always resolve to landlord conn) | tenant migrations | COMPLETED | 2026-10-01 | 2026-10-01 | `test_tenant_schema_is_isolated…` asserts tables absent; suite green | Removed dead `cache`, `jobs`, `sessions` creation (kept `password_reset_tokens` — wired by FND-031; kept `media` — InteractsWithMedia inherits the model connection so tenant uploads ARE tenant-local). Added drop migration for previously migrated tenant DBs |

### Phase 6 — Cluster C6: Auth completeness (MEDIUM)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-041 | MEDIUM | Zero `throttle:`/`RateLimiter` anywhere — login/register/register-tenant unthrottled | Modules/Access/routes/web.php; Modules/Landlord/routes/web.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_tenant_login_attempts_are_rate_limited`, `test_tenant_provisioning_endpoint_is_rate_limited` → 429 | `throttle:6,1` on tenant login/register POSTs + landlord login POST; `throttle:5,1` on public `POST /register-tenant` provisioning |
| FND-031 | MEDIUM | `password_reset_tokens` only in tenant DBs; broker `users` lacks `connection` → token repo hits landlord conn where table absent; `landlord_users` no broker | config/auth.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_password_reset_tokens_are_stored_in_the_tenant_database` — real broker createToken lands in tenant DB | Added `'connection' => 'tenant'` to the users broker — DatabaseTokenRepository now resolves the tenant connection |
| FND-017 | LOW | No landlord_users password broker — landlord reset unwired | config/auth.php | COMPLETED | 2026-10-01 | 2026-10-01 | grep: zero forgot/reset routes, controllers, or pages exist anywhere | Verified feature gap, not a defect: no landlord self-service reset surface exists (admin password resets are privileged management actions via LandlordAdminController). Broker deliberately left absent — wiring it now would point at a flow that does not exist |
| FND-037 | MEDIUM | `system.allow_registration` shared to UI but never enforced server-side in register flow | Modules/Access/app/Http/Controllers/TenantAuthController.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_tenant_member_registration_respects_platform_allow_registration` — 403 GET+POST | Controller enforces the landlord-owned flag on showRegisterForm + register, same abort(403) contract as /register-tenant |

### Phase 7 — Cluster C7: Billing/subscription integrity (MEDIUM)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-063 | MEDIUM | Yearly = `price*10` hardcoded; `changePlan` leaves billing_interval/currency/ends_at stale | Modules/Subscription/app/Services/SubscriptionService.php; TenantProvisioner; LandlordDatabaseSeeder | COMPLETED | 2026-10-01 | 2026-10-01 | `test_subscription_amount_and_interval_come_from_the_plan`, `test_change_plan_refreshes_interval_currency_and_ends_at` | Root cause: plans already carry `billing_interval` + `price` — the subscription now inherits all of them (`subscribeTenant` no longer takes an interval arg; `changePlan` rewrites interval/amount/currency/ends_at from the new plan). `*10` hack deleted |
| FND-061 | MEDIUM | Dashboard.vue reads `metrics.mrr`; service emits `monthly_revenue` → MRR card renders $0 | LandlordMetricsService.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_metrics_emit_real_mrr_key_with_sql_aggregation` | Emitted key renamed to `mrr` (matches the sole consumer) |
| FND-056 | LOW | MRR via `->get()->sum()` — unbounded hydration for one scalar | LandlordMetricsService.php | COMPLETED | 2026-10-01 | 2026-10-01 | same test asserts exact 129.0 (29 + 1200/12) | Single SQL aggregate: `SUM(CASE WHEN billing_interval='yearly' THEN amount/12 ELSE amount END)` — no row hydration, sqlite+mysql compatible |
| FND-064 | LOW-MEDIUM | `usage.storage_mb.current = 120` hardcoded fake rendered as real usage | QuotaManagerContract; QuotaService; SubscriptionController; TenantDashboardController | COMPLETED | 2026-10-01 | 2026-10-01 | `test_storage_usage_reflects_real_tenant_media_bytes` (2MB media → 2) | New `getStorageUsageMb` on the quota contract sums `media.size` on the tenant connection; both fabrication sites wired to it |
| FND-066 | LOW | LandlordSubscriptions.vue doesn't wire `pagination`/`page-change` — paginate(15) stuck on page 1 | LandlordSubscriptions.vue | COMPLETED | 2026-10-01 | 2026-10-01 | Convention diff vs Tenants/Index.vue pattern | Wired `:pagination` + `@page-change` → router.get('/landlord/subscriptions', {page}) — server pagination now reachable |

### Phase 8 — Cluster C8: Lifecycle atomicity (MEDIUM)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-055 | LOW-MEDIUM | Provision/delete not transactional: orphan DB+row+subscription on mid-failure; sequential non-atomic deletes, swallowed drop errors | TenantProvisioner.php; TenantLifecycleService.php | COMPLETED | 2026-10-01 | 2026-10-01 | suite green incl. all lifecycle tests | Landlord writes (tenant row + subscription + DB create) now run inside a landlord transaction; on any later failure (migrate/seed) the catch block deletes the rows via atomic `delete()` and drops the tenant DB via shared `dropTenantDatabase()` — compensation errors are reported, never mask the original. `delete()` wraps subs+tenant delete in a landlord txn; drop failures now throw instead of being swallowed |
| FND-054 | MEDIUM | `extendTrial` unconditionally sets status=Trialing — bypasses status machine; any→any transitions | Modules/Core/app/Enums/TenantStatus.php; TenantLifecycleService.php; TenantController.php | COMPLETED | 2026-10-01 | 2026-10-01 | `test_extend_trial_rejects_non_trialing_tenants` (Suspended→Trialing refused), `test_archived_tenant_is_terminal` (Archived→Active refused) | `TenantStatus::canTransitionTo()` is now the state machine; suspend/activate/archive/extendTrial all pass through `assertTransition` → ValidationException; extendTrial moved into the lifecycle service (controller was bypassing it). New `tenant_invalid_transition` key (en+ar) |

### Phase 9 — Cluster C9: SSoT / config drift (MEDIUM/LOW)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-001 | MEDIUM | composer floor `^8.3` but `Pdo\Mysql` (8.4-only) used; AGENTS says PHP 8.4 | composer.json | COMPLETED | 2026-10-01 | 2026-10-01 | autoload+tests | Floor ^8.4; Pdo\Mysql is 8.4-only as audited |
| FND-002 | MEDIUM | No JS lockfile; unpinned supply chain | package-lock.json | COMPLETED | 2026-10-01 | 2026-10-01 | npm install --package-lock-only; 0 vulnerabilities | Lockfile generated+committed |
| FND-003 | LOW | .styleci.yml conflicts with Pint laravel preset (disables no_unused_imports); no CI evidence | .styleci.yml | COMPLETED | 2026-10-01 | 2026-10-01 | deleted | Pint is the formatter; no StyleCI CI exists |
| FND-007 | LOW | .env.example `DB_CONNECTION=sqlite` vs README/phpunit `landlord` | .env.example | COMPLETED | 2026-10-01 | 2026-10-01 | docs match config | DB_CONNECTION=landlord documented; DB_*_DRIVER sqlite|mysql switch documented |
| FND-008 | LOW | `pestphp/pest-plugin` in allow-plugins; Pest not installed | composer.json | COMPLETED | 2026-10-01 | 2026-10-01 | composer dump-autoload ok | pest-plugin allow-plugins entry removed |
| FND-009 | LOW | Redundant dual PSR-4 `Modules\` autoload | composer.json | COMPLETED | 2026-10-01 | 2026-10-01 | class_exists(seeder+controller) verified | Broad Modules\ map removed — merge-plugin registers per-module PSR-4 incl. Database\Seeders |
| FND-011 | LOW | Weakly typed Inertia shared props (`unknown`, `any`, `status: string` vs enum) | env.d.ts | COMPLETED | 2026-10-01 | 2026-10-01 | compile-time contract | Shared props now fully typed (required keys match share()); missing tenancy prop added |
| FND-012 | LOW | README documents DomainTenantFinder; code ships SaaSTenantFinder | README.md | COMPLETED | 2026-10-01 | 2026-10-01 | diff vs config/multitenancy.php | SaaSTenantFinder + current switch task list documented |
| FND-016 | LOW | Config consumes env vars absent from .env.example (DB_LANDLORD_*, DB_TENANT_*, SESSION_CONNECTION, DB_CACHE_CONNECTION, DB_QUEUE_CONNECTION, MEDIA_*) | .env.example | COMPLETED | 2026-10-01 | 2026-10-01 | audit sweep vs config env() calls | DB_LANDLORD_*, DB_TENANT_*, SESSION_CONNECTION, DB_CACHE_CONNECTION, DB_QUEUE_CONNECTION, MEDIA_* documented |
| FND-019 | LOW | `tenant_artisan_search_fields=['id']` vs README id/slug/domain | config/multitenancy.php; README.md | COMPLETED | 2026-10-01 | 2026-10-01 | docs now match config | search_fields=[id,slug,domain]; README aligned |
| FND-020 | LOW | Module composer.json vendor/author = uncustomized nwidart defaults; stubs dead-weight claim | Modules/*/composer.json | COMPLETED | 2026-10-01 | 2026-10-01 | JSON valid; autoload ok | Vendor name=spatie-multitenant/*; nwidart authors removed |
| FND-023 | LOW | queue.batching/failed database falls back to `env('DB_CONNECTION','sqlite')` | config/queue.php | COMPLETED | 2026-10-01 | 2026-10-01 | tests green | batching+failed use DB_QUEUE_CONNECTION default landlord |
| FND-024 | LOW | HandleInertiaRequests fabricates `roles:['Super Admin']`/`['Member']` + empty permissions | app/Http/Middleware/HandleInertiaRequests.php | COMPLETED | 2026-10-01 | 2026-10-01 | suite green | Fake 'Super Admin'/'Member' fallbacks removed; real roles/permissions only |
| FND-025 | MEDIUM | `load($locale,'*','*')` serializes entire translation catalog into every Inertia response | app/Http/Middleware/HandleInertiaRequests.php | COMPLETED | 2026-10-01 | 2026-10-01 | test_translations_prop_is_scoped_to_the_route_module + updated bilingual test | Scoped dict: global+Core+route module; moved cross-module keys to global (max_users,storage) and landlord dict (landing copy) |
| FND-027 | LOW | Dual-model-per-table: App\Models\Tenant/User alias subclasses; parents remain instantiable | app/Models/{Tenant,User}.php | COMPLETED | 2026-10-01 | C3 | recorded in C3 | Parents now base-classes-only; all instantiations via canonical models |
| FND-034 | LOW | Missing `down()` on landlord tenants+media migrations → irreversible | landlord tenants+media migrations | COMPLETED | 2026-10-01 | 2026-10-01 | migrations parse; tests green | down() added to both |
| FND-040 | LOW | Hardcoded status literals `in:active,inactive,suspended`, `'Member'`/`'active'` fallbacks | UserController; TenantDashboardController; new Core\Enums\UserStatus | COMPLETED | 2026-10-01 | 2026-10-01 | suite green | UserStatus enum created; Rule::enum replaces in: literal; Member/active fallbacks via TenantPermissions/UserStatus/TenantStatus |
| FND-048 | LOW | LanguageSwitcher hardcodes EN/AR + `=== 'ar'` RTL vs server `locale.supported`/`is_rtl` map | LanguageSwitcher.vue | COMPLETED | 2026-10-01 | 2026-10-01 | prop-driven render | Buttons iterate locale.supported; dir/lang via setupInertiaStateBridge (is_rtl from server) |
| FND-050 | MEDIUM | Duplicate of FND-025 (same whole-catalog translations root cause) | HandleInertiaRequests.php | COMPLETED | 2026-10-01 | 2026-10-01 | same as FND-025 | Closed with FND-025. C11 note: useThemeStore.initTheme matchMedia listener now guarded against double-registration (mediaListenerAttached) |
| FND-059 | LOW | Modules/Index.vue duplicates locked-module list vs ModuleManagementController | Modules/Index.vue; ModuleManagementController.php | COMPLETED | 2026-10-01 | 2026-10-01 | suite green | LOCKED_MODULES const on controller; page uses mod.is_locked prop |
| FND-062 | LOW | `is_public` write-only — stored/cast, no reader | SettingService; contracts; models; migrations | COMPLETED | 2026-10-01 | 2026-10-01 | suite green | Flag removed entirely: set() signature, fillable/cast, columns dropped via new landlord+tenant migrations (idempotent hasColumn guards) |

### Phase 10 — Cluster C10: Scaffold debt (LOW)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-004 | LOW | Dead committed content: empty CLAUDE.md, verbatim upstream CHANGELOG.md, `/.github export-ignore` nonexistent | CLAUDE.md; CHANGELOG.md; .gitattributes | COMPLETED | 2026-10-01 | 2026-10-01 | deleted; .gitattributes cleaned | Empty CLAUDE.md + upstream CHANGELOG.md removed; stale export-ignore lines dropped |
| FND-005 | MEDIUM | vite-module-loader.js dead AND broken (`__dirname` in ESM, 0 consumers) | vite-module-loader.js | COMPLETED | 2026-10-01 | 2026-10-01 | deleted; 0 consumers confirmed | Dead+broken ESM file removed |
| FND-013 | LOW | `concurrently` devDependency unused | package.json + lockfile | COMPLETED | 2026-10-01 | 2026-10-01 | npm remove; lockfile updated | concurrently devDep removed |
| FND-028 | LOW | public/favicon.ico is 0 bytes; robots.txt allows /landlord/* crawl (INFO part) | public/favicon.ico; robots.txt | COMPLETED | 2026-10-01 | 2026-10-01 | hex-verified ICO; robots.txt updated | Valid 16x16 ICO (brand #6366f1); Disallow: /landlord |
| FND-035 | LOW | app.js dual module-page globs — one unreachable dead fallback | resources/js/app.js | COMPLETED | 2026-10-01 | 2026-10-01 | tests green | Single absolute /Modules glob retained |
| FND-044 | LOW | Scaffold debt: unused config/config.php stubs, empty seeders, stale .gitkeep in populated dirs, dead per-module package.json/vite.config.js | Modules/* | COMPLETED | 2026-10-01 | 2026-10-01 | 115 tests pass | Deleted 6 config stubs, 6 package.json, 6 vite.config.js, 5 empty seeders, 5 stale .gitkeep (populated dirs only) |
| FND-045 | LOW | CoreController + 4 scaffold Core pages unrouted/dead | Modules/Core | COMPLETED | 2026-10-01 | 2026-10-01 | tests green; no render refs | CoreController + Index/Create/Edit/Show.vue removed; locale/theme controllers + ErrorPage kept |
| FND-068 | LOW | Stub tree claimed dead | stubs/nwidart-stubs/* | REJECTED-BY-ADR | — | — | ADR-STUBS-001 | Deletion prohibited; kept as protected asset. Status = COMPLETED via ADR (no code change) |

### Phase 11 — Cluster C11: UX/FE polish (LOW)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-026 | LOW | SetLocale writes session every request; accepts POST `locale` input | app/Http/Middleware/SetLocale.php | COMPLETED | 2026-10-01 | 2026-10-01 | suite green | $request->query() instead of get(); session written only when locale differs |
| FND-036 | LOW | Hardcoded `#6366f1` Inertia progress color bypasses design tokens | resources/js/app.js | COMPLETED | 2026-10-01 | 2026-10-01 | npm build pass | progress color read from --color-primary-500 CSS var at runtime |
| FND-043 | LOW | Users index: per-row EffectivePermissionSet → getAllPermissions; roles.permissions not eager-loaded | TenantUserService.php | COMPLETED | 2026-10-01 | 2026-10-01 | suite green | with(roles.permissions); also fixed leftover 'active' literal -> UserStatus::Active |
| FND-046 | LOW-MEDIUM | EnterpriseDataGrid emits search per keystroke, no debounce | EnterpriseDataGrid.vue | COMPLETED | 2026-10-01 | 2026-10-01 | build pass | 300ms debounce on search emit; update:searchQuery stays immediate |
| FND-047 | LOW | Modal.vue toggles body overflow per instance — scroll lock not reference-counted | Modal.vue + new Composables/useScrollLock.ts | COMPLETED | 2026-10-01 | 2026-10-01 | build pass | Shared refcounted lock; stale lock released on unmount |
| FND-049 | LOW | FormEngine/ConfirmDialog hand-roll `<button>` vs BaseButton; icon variant via CSS-class matching | ConfirmDialog.vue; EnterpriseFormEngine.vue; BaseButton.vue | COMPLETED | 2026-10-01 | 2026-10-01 | build pass | Buttons via BaseButton; icon keyed on variant prop (class-sniffing + confirmButtonClass removed); BaseButton click emit added for multi-root attr fallthrough |
| FND-060 | LOW-MEDIUM | Tenants/Show.vue delete dialog defaults `drop_database: true` | Tenants/Show.vue | COMPLETED | 2026-10-01 | 2026-10-01 | build pass | drop_database defaults false |

### Non-actionable findings (audit-closed)

| ID | Reason |
|---|---|
| FND-010 | INFO — `composer audit` clean; no remediation |
| FND-014 | Superseded in register |
| FND-038 | RESOLVED — intentional cross-surface redirect |
| FND-051 | RESOLVED — debug-gated exception detail |

---

## LOG

### Files Modified
- database/seeders/DatabaseSeeder.php (FND-029, FND-033)
- database/seeders/TenantSeeder.php (FND-030, FND-053)
- Modules/Landlord/app/Models/Tenant.php (FND-030)
- Modules/Landlord/app/Services/TenantProvisioner.php (FND-053 — canonical tenantDomain())
- Modules/Landlord/app/Http/Controllers/TenantRegistrationController.php (FND-053)
- Modules/Landlord/routes/web.php (FND-057)
- Modules/Landlord/database/seeders/LandlordDatabaseSeeder.php (FND-029/033/053 family)
- Modules/Landlord/resources/js/Pages/Landing/RegisterTenant.vue (FND-053)
- Modules/Landlord/resources/js/Pages/Tenants/Create.vue (FND-053)
- app/Http/Middleware/HandleInertiaRequests.php (FND-053 — tenancy.domain_suffix prop)
- .env.example (FND-029 — SEED_TENANT_OWNER_PASSWORD + SEED_LANDLORD_ADMIN_PASSWORD documented)
- README.md (FND-030 — stale fillable example)
- docs/audit/REMEDIATION-PROGRESS.md (this file)
- phpunit.xml (FND-067/006: sqlite topology + pinned env; FND-052: Modules testsuite)
- tests/TestCase.php (FND-067: RefreshDatabase + landlord baseline + provisionTenant/createTenantRecord helpers + sqlite cleanup)
- tests/Feature/TenantIsolationTest.php, TenantAccessControlTest.php, SettingsGovernanceTest.php, QuotaEnforcementTest.php, LandlordTenantProvisioningTest.php, TenancySecurityTest.php (FND-067: self-provisioned fixtures replace dev-DB fixtures)
- Modules/Landlord/app/Services/TenantProvisioner.php (C3 deps: sqlite DB path; tenant migrations actually run on tenant connection)
- Modules/Landlord/app/Services/TenantLifecycleService.php (sqlite delete uses stored path)
- database/seeders/TenantSeeder.php (sqlite-aware fixture DBs)
- FND-027 canonical-model swaps (App\Models\Tenant / App\Models\User): TenantController, TenantRegistrationController, LandlordMetricsService, LandlordDatabaseSeeder, TenantUserService, SyncTenantAccessCommand, SubscriptionService, QuotaService, Plan, Subscription
- Modules/{Access,Landlord,Subscription,Tenant}/routes/api.php (FND-058/039 — emptied)
- Modules/Subscription/app/Http/Controllers/PlanController.php (FND-065 — destroy)
- Modules/Subscription/lang/{en,ar}.json (FND-065 — plan_deleted, plan_delete_blocked)
- tests/Feature/TenancySecurityTest.php (C4 regression test + retargeted suspended/ghost-api URLs)
- tests/Feature/LandlordTenantProvisioningTest.php (FND-065 destroy coverage)

- config/multitenancy.php (FND-015 — PrefixCacheTask enabled)
- config/media-library.php (FND-018 — TenantAwarePathGenerator)
- app/Support/TenantAwarePathGenerator.php (FND-018)
- Modules/Access/app/Services/AuditWriter.php (FND-042 — landlord-context guard)
- database/migrations/tenant/0001_01_01_000000_create_users_table.php (FND-022 — sessions block removed)
- deleted: database/migrations/tenant/0001_01_01_000001_create_cache_table.php, 0001_01_01_000002_create_jobs_table.php (FND-022)
- Modules/Access/routes/web.php (FND-041 — throttle on login/register POSTs)
- Modules/Landlord/routes/web.php (FND-041 — throttle on landlord login + register-tenant)
- Modules/Access/app/Http/Controllers/TenantAuthController.php (FND-037 — allow_registration enforcement)
- config/auth.php (FND-031 — users broker → tenant connection)
- Modules/Subscription/app/Services/SubscriptionService.php (FND-063)
- Modules/Landlord/app/Services/TenantProvisioner.php (FND-063 — caller update)
- Modules/Landlord/database/seeders/LandlordDatabaseSeeder.php (FND-063 — caller update)
- Modules/Landlord/app/Services/LandlordMetricsService.php (FND-056, FND-061)
- Modules/Core/app/Contracts/QuotaManagerContract.php + Modules/Subscription/app/Services/QuotaService.php (FND-064 — getStorageUsageMb)
- Modules/Subscription/app/Http/Controllers/SubscriptionController.php + Modules/Tenant/app/Http/Controllers/TenantDashboardController.php (FND-064)
- Modules/Subscription/resources/js/Pages/LandlordSubscriptions.vue (FND-066)
- Modules/Core/app/Enums/TenantStatus.php (FND-054 — canTransitionTo)
- Modules/Landlord/app/Services/TenantLifecycleService.php (FND-054/055 — transition guards, extendTrial, atomic delete, dropTenantDatabase)
- Modules/Landlord/app/Services/TenantProvisioner.php (FND-055 — landlord txn + compensation)
- Modules/Landlord/app/Http/Controllers/TenantController.php (FND-054 — delegates to service)
- Modules/Landlord/lang/{en,ar}.json (FND-054 — tenant_invalid_transition; FND-025 — absorbed landing-page keys)
- composer.json (FND-001 ^8.4, FND-008 pest-plugin, FND-009 broad Modules\ map removed)
- Modules/*/composer.json (FND-020 — vendor identity)
- package-lock.json (FND-002 — generated)
- deleted: .styleci.yml (FND-003)
- .env.example (FND-007, FND-016 — complete env contract)
- config/queue.php (FND-023 — DB_QUEUE_CONNECTION→landlord)
- config/multitenancy.php (FND-019 — id/slug/domain search fields)
- README.md (FND-012 — SaaSTenantFinder docs; FND-019)
- app/Http/Middleware/HandleInertiaRequests.php (FND-024, FND-025/050)
- env.d.ts (FND-011 — strict shared props + tenancy)
- landlord tenants+media migrations (FND-034 — down())
- Modules/Core/app/Enums/UserStatus.php (FND-040 — new SSoT enum)
- Modules/Access/app/Http/Controllers/UserController.php + Modules/Tenant/app/Http/Controllers/TenantDashboardController.php (FND-040)
- Modules/Core/resources/js/Components/LanguageSwitcher.vue (FND-048)
- Modules/Landlord/app/Http/Controllers/ModuleManagementController.php + Modules/Landlord/resources/js/Pages/Modules/Index.vue (FND-059)
- SettingManagerContract + SettingService + Setting/TenantSetting models + settings migrations (FND-062 — is_public removed)
- new migrations: Modules/Settings/.../drop_is_public_from_settings, database/migrations/tenant/.../drop_is_public_from_tenant_settings (FND-062)
- resources/lang/{en,ar}.json (FND-025 — max_users/storage → global), Modules/Subscription/lang/{en,ar}.json (moved keys out)
- deleted: CLAUDE.md, CHANGELOG.md, vite-module-loader.js (FND-004/005)
- .gitattributes (FND-004), public/robots.txt + public/favicon.ico (FND-028)
- resources/js/app.js (FND-035 — single glob)
- package.json + package-lock.json (FND-013 — concurrently removed)
- deleted: 6× Modules/*/config/config.php, 6× package.json, 6× vite.config.js, 5× empty DatabaseSeeders, 5× stale .gitkeep (FND-044)
- deleted: Modules/Core CoreController.php + Index/Create/Edit/Show.vue (FND-045)
- FND-068 REJECTED-BY-ADR-STUBS-001 — stubs/ tree protected, no code change
- app/Http/Middleware/SetLocale.php (FND-026)
- resources/js/app.js (FND-036 — token-driven progress color)
- Modules/Core/resources/js/Components/EnterpriseDataGrid.vue (FND-046 — debounced search)
- Modules/Core/resources/js/Composables/useScrollLock.ts (FND-047 — new refcounted lock)
- Modules/Core/resources/js/Components/Modal.vue (FND-047)
- Modules/Core/resources/js/Components/{ConfirmDialog,EnterpriseFormEngine,BaseButton}.vue (FND-049)
- Modules/Landlord/resources/js/Pages/Tenants/Show.vue (FND-060 — drop_database defaults false)
- Modules/Core/resources/js/Stores/useThemeStore.ts (C11 initTheme listener idempotence)

### Migrations Added
- database/migrations/tenant/2026_10_02_000001_add_is_system_to_roles_table.php (FND-032)
- database/migrations/tenant/2026_10_02_000002_drop_shared_infrastructure_tables.php (FND-022)

### Tests Added
- test_sanctum_scaffold_api_routes_are_not_registered (TenancySecurityTest)
- test_plan_with_attached_tenant_cannot_be_deleted, test_plan_with_subscriptions_cannot_be_deleted, test_orphan_plan_can_be_deleted (LandlordTenantProvisioningTest)
- test_tenant_context_scopes_cache_keyspace, test_tenant_schema_is_isolated_without_shared_infrastructure_tables, test_media_paths_are_scoped_to_the_owning_tenant (TenantIsolationTest)
- test_role_and_permission_models_follow_tenancy_context (TenantAccessControlTest)
- test_tenant_login_attempts_are_rate_limited, test_tenant_provisioning_endpoint_is_rate_limited (TenancySecurityTest)
- test_tenant_member_registration_respects_platform_allow_registration, test_password_reset_tokens_are_stored_in_the_tenant_database (TenantAccessControlTest)
- test_subscription_amount_and_interval_come_from_the_plan, test_change_plan_refreshes_interval_currency_and_ends_at, test_metrics_emit_real_mrr_key_with_sql_aggregation, test_storage_usage_reflects_real_tenant_media_bytes (QuotaEnforcementTest)
- test_extend_trial_rejects_non_trialing_tenants, test_archived_tenant_is_terminal (LandlordTenantProvisioningTest)
- test_translations_prop_is_scoped_to_the_route_module (ThemingAndLocalizationTest); updated test_inertia_receives_resolved_translations_in_both_locales to assert scoped payload

### Risks
- `docs/audit/` contains this file + MASTER-AUDIT.md only; do not regenerate findings.

### Verification
(per-finding verification evidence is logged in the ledger rows as each finding closes)
