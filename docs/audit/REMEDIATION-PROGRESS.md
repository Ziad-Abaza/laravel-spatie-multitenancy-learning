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

- **Current Phase:** Phase 6
- **Current Cluster:** C6 — Auth completeness
- **Current Finding:** FND-041

### Queue

- **Pending:** C6 → C11 (see phase plan below)
- **In Progress:** C6
- **Completed:** C1 (FND-029, FND-030, FND-033), C2 (FND-053, FND-057), C3 (FND-067, FND-006, FND-052; incl. dependency fixes FND-027 + provisioner tenant-migration defect), C4 (FND-058, FND-039, FND-065), C5 (FND-015, FND-018, FND-032, FND-042, FND-021, FND-022)
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
| FND-041 | MEDIUM | Zero `throttle:`/`RateLimiter` anywhere — login/register/register-tenant unthrottled | routes; controllers | PENDING | — | — | — | Add throttle middleware (P0) |
| FND-031 | MEDIUM | `password_reset_tokens` only in tenant DBs; broker `users` lacks `connection` → token repo hits landlord conn where table absent; `landlord_users` no broker | config/auth.php | PENDING | — | — | — | Fix broker wiring or remove surface |
| FND-017 | LOW | No landlord_users password broker — landlord reset unwired | config/auth.php | PENDING | — | — | — | Same fix area as FND-031 |
| FND-037 | MEDIUM | `system.allow_registration` shared to UI but never enforced server-side in register flow | TenantAuthController; TenantUserService | PENDING | — | — | — | Enforce setting server-side |

### Phase 7 — Cluster C7: Billing/subscription integrity (MEDIUM)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-063 | MEDIUM | Yearly = `price*10` hardcoded; `changePlan` leaves billing_interval/currency/ends_at stale | Modules/Subscription/.../SubscriptionService.php | PENDING | — | — | — | Fix recalculation; source multiplier from plan/model |
| FND-061 | MEDIUM | Dashboard.vue reads `metrics.mrr`; service emits `monthly_revenue` → MRR card renders $0 | Dashboard.vue; LandlordMetricsService.php | PENDING | — | — | — | Align prop key (P0 — small diff) |
| FND-056 | LOW | MRR via `->get()->sum()` — unbounded hydration for one scalar | LandlordMetricsService.php | PENDING | — | — | — | SQL `sum('amount')` |
| FND-064 | LOW-MEDIUM | `usage.storage_mb.current = 120` hardcoded fake rendered as real usage | SubscriptionController.php | PENDING | — | — | — | Real metric or remove field |
| FND-066 | LOW | LandlordSubscriptions.vue doesn't wire `pagination`/`page-change` — paginate(15) stuck on page 1 | LandlordSubscriptions.vue | PENDING | — | — | — | Wire grid pagination |

### Phase 8 — Cluster C8: Lifecycle atomicity (MEDIUM)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-055 | LOW-MEDIUM | Provision/delete not transactional: orphan DB+row+subscription on mid-failure; sequential non-atomic deletes, swallowed drop errors | TenantProvisioner.php; TenantLifecycleService.php | PENDING | — | — | — | Wrap in txn + compensation |
| FND-054 | MEDIUM | `extendTrial` unconditionally sets status=Trialing — bypasses status machine; any→any transitions | TenantController.php; TenantLifecycleService.php | PENDING | — | — | — | Transition guards via TenantStatus enum |

### Phase 9 — Cluster C9: SSoT / config drift (MEDIUM/LOW)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-001 | MEDIUM | composer floor `^8.3` but `Pdo\Mysql` (8.4-only) used; AGENTS says PHP 8.4 | composer.json | PENDING | — | — | — | Raise floor to ^8.4 |
| FND-002 | MEDIUM | No JS lockfile; unpinned supply chain | package.json | PENDING | — | — | — | Generate + commit package-lock.json |
| FND-003 | LOW | .styleci.yml conflicts with Pint laravel preset (disables no_unused_imports); no CI evidence | .styleci.yml | PENDING | — | — | — | Remove dead config or align |
| FND-007 | LOW | .env.example `DB_CONNECTION=sqlite` vs README/phpunit `landlord` | .env.example | PENDING | — | — | — | Align env contract |
| FND-008 | LOW | `pestphp/pest-plugin` in allow-plugins; Pest not installed | composer.json | PENDING | — | — | — | Remove stale entry |
| FND-009 | LOW | Redundant dual PSR-4 `Modules\` autoload | composer.json | PENDING | — | — | — | Keep per-module maps; verify broad map need |
| FND-011 | LOW | Weakly typed Inertia shared props (`unknown`, `any`, `status: string` vs enum) | env.d.ts / *.d.ts | PENDING | — | — | — | Tighten types |
| FND-012 | LOW | README documents DomainTenantFinder; code ships SaaSTenantFinder | README.md | PENDING | — | — | — | Doc sync |
| FND-016 | LOW | Config consumes env vars absent from .env.example (DB_LANDLORD_*, DB_TENANT_*, SESSION_CONNECTION, DB_CACHE_CONNECTION, DB_QUEUE_CONNECTION, MEDIA_*) | .env.example | PENDING | — | — | — | Complete env contract |
| FND-019 | LOW | `tenant_artisan_search_fields=['id']` vs README id/slug/domain | config/multitenancy.php; README | PENDING | — | — | — | Align docs or fields |
| FND-020 | LOW | Module composer.json vendor/author = uncustomized nwidart defaults; stubs dead-weight claim | Modules/*/composer.json | PENDING | — | — | — | Fix composer identity only — stub-tree part REJECTED per ADR-STUBS-001 |
| FND-023 | LOW | queue.batching/failed database falls back to `env('DB_CONNECTION','sqlite')` | config/queue.php | PENDING | — | — | — | Point at landlord connection |
| FND-024 | LOW | HandleInertiaRequests fabricates `roles:['Super Admin']`/`['Member']` + empty permissions | HandleInertiaRequests.php | PENDING | — | — | — | Remove hardcoded fabrication |
| FND-025 | MEDIUM | `load($locale,'*','*')` serializes entire translation catalog into every Inertia response | HandleInertiaRequests.php | PENDING | — | — | — | Route-scoped/lazy translations |
| FND-027 | LOW | Dual-model-per-table: App\Models\Tenant/User alias subclasses; parents remain instantiable | app/Models/{Tenant,User}.php | PENDING | — | — | — | Single canonical model; check references |
| FND-034 | LOW | Missing `down()` on landlord tenants+media migrations → irreversible | landlord migrations | PENDING | — | — | — | Add down() |
| FND-040 | LOW | Hardcoded status literals `in:active,inactive,suspended`, `'Member'`/`'active'` fallbacks | Modules/Access/.../UserController.php | PENDING | — | — | — | Route via enums/manifests |
| FND-048 | LOW | LanguageSwitcher hardcodes EN/AR + `=== 'ar'` RTL vs server `locale.supported`/`is_rtl` map | LanguageSwitcher.vue | PENDING | — | — | — | Drive from shared prop |
| FND-050 | MEDIUM | Duplicate of FND-025 (same whole-catalog translations root cause) | HandleInertiaRequests.php | PENDING | — | — | — | Closes with FND-025 |
| FND-059 | LOW | Modules/Index.vue duplicates locked-module list vs ModuleManagementController | Modules/Index.vue | PENDING | — | — | — | Single source via shared prop |
| FND-062 | LOW | `is_public` write-only — stored/cast, no reader | SettingService.php; settings migrations | PENDING | — | — | — | Implement reader or remove flag |

### Phase 10 — Cluster C10: Scaffold debt (LOW)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-004 | LOW | Dead committed content: empty CLAUDE.md, verbatim upstream CHANGELOG.md, `/.github export-ignore` nonexistent | CLAUDE.md; CHANGELOG.md; .gitattributes | PENDING | — | — | — | Remove/rectify |
| FND-005 | MEDIUM | vite-module-loader.js dead AND broken (`__dirname` in ESM, 0 consumers) | vite-module-loader.js | PENDING | — | — | — | Delete dead file |
| FND-013 | LOW | `concurrently` devDependency unused | package.json | PENDING | — | — | — | Remove dep |
| FND-028 | LOW | public/favicon.ico is 0 bytes; robots.txt allows /landlord/* crawl (INFO part) | public/favicon.ico; robots.txt | PENDING | — | — | — | Fix asset; disallow admin surface |
| FND-035 | LOW | app.js dual module-page globs — one unreachable dead fallback | resources/js/app.js | PENDING | — | — | — | Keep working glob only |
| FND-044 | LOW | Scaffold debt: unused config/config.php stubs, empty seeders, stale .gitkeep in populated dirs, dead per-module package.json/vite.config.js | Modules/* | PENDING | — | — | — | Remove dead scaffold (NOT stubs/ — protected) |
| FND-045 | LOW | CoreController + 4 scaffold Core pages unrouted/dead | Modules/Core | PENDING | — | — | — | Remove dead scaffold |
| FND-068 | LOW | Stub tree claimed dead | stubs/nwidart-stubs/* | REJECTED-BY-ADR | — | — | ADR-STUBS-001 | Deletion prohibited; kept as protected asset. Status = COMPLETED via ADR (no code change) |

### Phase 11 — Cluster C11: UX/FE polish (LOW)

| ID | Severity | Root Cause | Files | Status | Started | Completed | Verification | Notes |
|---|---|---|---|---|---|---|---|---|
| FND-026 | LOW | SetLocale writes session every request; accepts POST `locale` input | SetLocale.php | PENDING | — | — | — | Write only on change; query-only input |
| FND-036 | LOW | Hardcoded `#6366f1` Inertia progress color bypasses design tokens | resources/js/app.js | PENDING | — | — | — | Use token |
| FND-043 | LOW | Users index: per-row EffectivePermissionSet → getAllPermissions; roles.permissions not eager-loaded | TenantUserService.php; UserController.php | PENDING | — | — | — | Eager-load roles.permissions |
| FND-046 | LOW-MEDIUM | EnterpriseDataGrid emits search per keystroke, no debounce | EnterpriseDataGrid.vue | PENDING | — | — | — | Debounce |
| FND-047 | LOW | Modal.vue toggles body overflow per instance — scroll lock not reference-counted | Modal.vue | PENDING | — | — | — | Refcounted lock utility |
| FND-049 | LOW | FormEngine/ConfirmDialog hand-roll `<button>` vs BaseButton; icon variant via CSS-class matching | FormEngine.vue; ConfirmDialog.vue | PENDING | — | — | — | Compose BaseButton |
| FND-060 | LOW-MEDIUM | Tenants/Show.vue delete dialog defaults `drop_database: true` | Tenants/Show.vue | PENDING | — | — | — | Default false (P0) |

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

### Migrations Added
- database/migrations/tenant/2026_10_02_000001_add_is_system_to_roles_table.php (FND-032)
- database/migrations/tenant/2026_10_02_000002_drop_shared_infrastructure_tables.php (FND-022)

### Tests Added
- test_sanctum_scaffold_api_routes_are_not_registered (TenancySecurityTest)
- test_plan_with_attached_tenant_cannot_be_deleted, test_plan_with_subscriptions_cannot_be_deleted, test_orphan_plan_can_be_deleted (LandlordTenantProvisioningTest)
- test_tenant_context_scopes_cache_keyspace, test_tenant_schema_is_isolated_without_shared_infrastructure_tables, test_media_paths_are_scoped_to_the_owning_tenant (TenantIsolationTest)
- test_role_and_permission_models_follow_tenancy_context (TenantAccessControlTest)

### Risks
- `docs/audit/` contains this file + MASTER-AUDIT.md only; do not regenerate findings.

### Verification
(per-finding verification evidence is logged in the ledger rows as each finding closes)
