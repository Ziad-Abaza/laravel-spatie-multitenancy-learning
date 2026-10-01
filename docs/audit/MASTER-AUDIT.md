# MASTER AUDIT — spatie-multitenant
 
**Audit type:** ZERO-OMISSION FULL PROJECT AUDIT (forensic, sequential)
**Started:** 2026-10-01
**Project root:** `D:\coding\projects\web developer\Laravel\learning\spatie-multitenant`
**Governing rules:** `AGENTS.md` (Laravel Boost guidelines). `.ai/rules/` does NOT exist (verified via filesystem check). `CLAUDE.md` exists but is empty. `.github/`, `.devin/`, `docs/` did not exist prior to this audit.
 
---
 
## PROJECT COVERAGE
 
| Metric | Value |
|---|---|
| TOTAL INVENTORY | 538 tracked files (git ls-files, 0 untracked non-ignored files) |
| TOTAL UNITS | 41 (Phase 1 complete — §6) |
| COMPLETED UNITS | 41 (ALL UNITS CLOSED — coverage complete) |
| CURRENT UNIT | — (reconciliation & open-questions pass in progress) |
| INSPECTED FILES | 538 / 538 — **100%** |
| UNINSPECTED FILES | 0 |
| SKIPPED FILES | 0 |
| BLOCKED FILES | 0 |
| FINDINGS | 0 (inventory-phase observations listed below are not yet findings) |
| OPEN QUESTIONS | see §7 |
 
---
 
## 1. ENVIRONMENT SNAPSHOT (Phase 0 evidence)
 
| Item | Evidence |
|---|---|
| PHP | `composer.json` requires `php: ^8.3`; AGENTS.md claims PHP 8.4 runtime |
| Framework | `laravel/framework ^13.17` |
| Tenancy | `spatie/laravel-multitenancy ^4.2` |
| Permissions | `spatie/laravel-permission 8.3` |
| Media | `spatie/laravel-medialibrary 11.23` |
| Translations | `spatie/laravel-translatable 6.14` |
| Modules | `nwidart/laravel-modules ^13.0` + `wikimedia/composer-merge-plugin` (`Modules/*/composer.json`) |
| Frontend | `vue ^3.5`, `@inertiajs/vue3`, `pinia`, `tailwindcss ^4`, `vite ^8`, `typescript`, `lucide-vue-next`, `@laravel/multiplex` (optional dep) |
| Testing | `phpunit/phpunit ^12.5.12`, `phpunit.xml` present |
| Dev tooling | `laravel/boost ^2.10` (boost.json: mcp+guidelines+skills), `laravel/pint`, `laravel/pail`, `laravel/pao`, `mockery`, `fakerphp`, `nunomaduro/collision` |
| Module activation | `modules_statuses.json` — all 6 modules enabled: Core, Landlord, Subscription, Access, Settings, Tenant |
| Git HEAD | `3fd1d4c` "feat: Add artisan commands for rebuilding landlord and tenant databases with permission synchronization" |
| Middleware wiring | `bootstrap/app.php`: web group prepends `IdentifyTenant` + `EnsureTenantIsActive`, appends `SetLocale` + `HandleInertiaRequests`; api prepends tenant middleware; `tenant` group = `NeedsTenant` + `EnsureValidTenantSession`; aliases `landlord`, `landlord.active`, `role`, `permission`, `role_or_permission`; exception rendering → `ErrorPageRenderer` |
 
## 2. DOCUMENTED EXCLUSIONS (on-disk, not part of auditable source)
 
| Path | Why excluded from file inspection |
|---|---|
| `.env` | Exists on disk. Contains live secrets — never read/reproduced. Audited indirectly via `.env.example` + `config/*` consumption (Phase 4 checks for key drift). |
| `vendor/` | Third-party code; manifest `composer.lock` is the auditable artifact. Vulnerability check via `composer audit` in Phase 4. |
| `node_modules/` | Third-party code; manifest `package.json` + lockfile state. |
| `public/build/` (101 files) | Generated Vite artifacts — not source. |
| `public/hot` | Vite dev-server marker (`http://[::1]:5173`), generated. |
| `public/fonts-manifest.dev.json` | Dev-time generated font manifest (laravel-vite-plugin). NOTE: tracked? NO — untracked artifact present on disk. Flagged for Phase 3: why is a dev artifact sitting in `public/`? |
| `bootstrap/cache/{events,modules,packages,services}.php` | Generated framework caches. |
| `.phpunit.result.cache` | Test-run cache. |
| `.notebook/` | IDE scratch dir (drawIO html, tmp). |
| `.git/` | VCS metadata. |
| `storage/**` runtime contents | Only `.gitignore` markers are tracked; runtime content is generated. The 10 tracked `.gitignore` files ARE in the inventory. |
 
## 3. INVENTORY OBSERVATIONS (pre-inspection, not yet findings)
 
1. Every module ships nwidart scaffold leftovers: `resources/assets/{js/app.js,sass/app.scss}`, per-module `vite.config.js`, `package.json`, `composer.json` with empty `nwidart/*` metadata, generic `Create/Edit/Index/Show.vue` pages, empty `config/config.php` candidates, `.gitkeep` markers everywhere. Phase 2 must classify each as scaffold-debt vs. in-use.
2. `tests/` contains 10 feature tests at root; every `Modules/*/tests/` dir contains only `.gitkeep` — module-level test dirs are empty placeholders.
3. No `app/Jobs`, `Events/Listeners` (tenant domain), `Policies/`, `Notifications/`, `Mail/`, `Broadcasting/` directories exist anywhere — inventory confirms zero jobs, zero listeners wired (verify per module `EventServiceProvider` in Phase 2), zero policies.
4. Two Tenant models exist: `app/Models/Tenant.php` AND `Modules/Landlord/app/Models/Tenant.php` — potential duplicate source of truth (Phase 3).
5. `routes/web.php` + `routes/console.php` at root; each module also has `routes/web.php` + `routes/api.php`.
6. `database/migrations` split into `landlord/` (6 files) and `tenant/` (6 files); module migrations exist for Landlord (3), Settings (1), Subscription (2).
7. `stubs/nwidart-stubs/` — 82 generator stubs committed to the repo.
8. `.agents/` — 37 AI-tooling files committed (skills, MCP config).
9. `resources/lang/` + per-module `lang/` — translation files in both locations (`en.json`, `ar.json`, `*/validation.php`).
 
## 4. MASTER FILE INVENTORY
 
Status legend: `PENDING` = inventoried, awaiting Phase-2 forensic inspection.
 
### 4.1 AI tooling & repo meta (37 + 9 files)
 
| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-001 | .agents/mcp_config.json | MCP config | — | MCP server registration for agents | laravel/boost | INSPECTED |
| F-002 | .agents/skills/deploying-to-cloud/SKILL.md | Skill doc | — | Laravel Cloud deploy skill | — | INSPECTED |
| F-003 | .agents/skills/deploying-to-cloud/reference/checklists.md | Skill doc | — | Deploy checklists | — | INSPECTED |
| F-004 | .agents/skills/infer-conventions/SKILL.md | Skill doc | — | Convention inference skill | — | INSPECTED |
| F-005 | .agents/skills/infer-conventions/references/checklist.md | Skill doc | — | Convention checklist | — | INSPECTED |
| F-006 | .agents/skills/laravel-best-practices/SKILL.md | Skill doc | — | Laravel practices index | — | INSPECTED |
| F-007 | .agents/skills/laravel-best-practices/rules/advanced-queries.md | Rule doc | — | Query rules | — | INSPECTED |
| F-008 | .agents/skills/laravel-best-practices/rules/architecture.md | Rule doc | — | Architecture rules | — | INSPECTED |
| F-009 | .agents/skills/laravel-best-practices/rules/blade-views.md | Rule doc | — | Blade rules | — | INSPECTED |
| F-010 | .agents/skills/laravel-best-practices/rules/caching.md | Rule doc | — | Cache rules | — | INSPECTED |
| F-011 | .agents/skills/laravel-best-practices/rules/collections.md | Rule doc | — | Collection rules | — | INSPECTED |
| F-012 | .agents/skills/laravel-best-practices/rules/config.md | Rule doc | — | Config rules | — | INSPECTED |
| F-013 | .agents/skills/laravel-best-practices/rules/db-performance.md | Rule doc | — | DB perf rules | — | INSPECTED |
| F-014 | .agents/skills/laravel-best-practices/rules/eloquent.md | Rule doc | — | Eloquent rules | — | INSPECTED |
| F-015 | .agents/skills/laravel-best-practices/rules/error-handling.md | Rule doc | — | Error handling rules | — | INSPECTED |
| F-016 | .agents/skills/laravel-best-practices/rules/events-notifications.md | Rule doc | — | Event/notification rules | — | INSPECTED |
| F-017 | .agents/skills/laravel-best-practices/rules/http-client.md | Rule doc | — | HTTP client rules | — | INSPECTED |
| F-018 | .agents/skills/laravel-best-practices/rules/mail.md | Rule doc | — | Mail rules | — | INSPECTED |
| F-019 | .agents/skills/laravel-best-practices/rules/migrations.md | Rule doc | — | Migration rules | — | INSPECTED |
| F-020 | .agents/skills/laravel-best-practices/rules/queue-jobs.md | Rule doc | — | Queue/job rules | — | INSPECTED |
| F-021 | .agents/skills/laravel-best-practices/rules/routing.md | Rule doc | — | Routing rules | — | INSPECTED |
| F-022 | .agents/skills/laravel-best-practices/rules/scheduling.md | Rule doc | — | Scheduling rules | — | INSPECTED |
| F-023 | .agents/skills/laravel-best-practices/rules/security.md | Rule doc | — | Security rules | — | INSPECTED |
| F-024 | .agents/skills/laravel-best-practices/rules/style.md | Rule doc | — | Style rules | — | INSPECTED |
| F-025 | .agents/skills/laravel-best-practices/rules/validation.md | Rule doc | — | Validation rules | — | INSPECTED |
| F-026 | .agents/skills/laravel-multitenancy-development/SKILL.md | Skill doc | — | Multitenancy skill | — | INSPECTED |
| F-027 | .agents/skills/tailwindcss-development/SKILL.md | Skill doc | — | Tailwind skill | — | INSPECTED |
| F-028 | .agents/skills/testing-best-practices/SKILL.md | Skill doc | — | Testing skill index | — | INSPECTED |
| F-029 | .agents/skills/testing-best-practices/rules/assertions.md | Rule doc | — | Assertion rules | — | INSPECTED |
| F-030 | .agents/skills/testing-best-practices/rules/endpoint-tests.md | Rule doc | — | Endpoint test rules | — | INSPECTED |
| F-031 | .agents/skills/testing-best-practices/rules/finding-features.md | Rule doc | — | Feature-discovery rules | — | INSPECTED |
| F-032 | .agents/skills/testing-best-practices/rules/isolation.md | Rule doc | — | Test isolation rules | — | INSPECTED |
| F-033 | .agents/skills/testing-best-practices/rules/naming.md | Rule doc | — | Test naming rules | — | INSPECTED |
| F-034 | .agents/skills/testing-best-practices/rules/performance.md | Rule doc | — | Test perf rules | — | INSPECTED |
| F-035 | .agents/skills/testing-best-practices/rules/review.md | Rule doc | — | Test review rules | — | INSPECTED |
| F-036 | .agents/skills/testing-best-practices/rules/security.md | Rule doc | — | Test security rules | — | INSPECTED |
| F-037 | .agents/skills/testing-best-practices/rules/test-data.md | Rule doc | — | Test data rules | — | INSPECTED |
| F-038 | .editorconfig | Tooling config | — | Editor formatting rules | — | INSPECTED |
| F-039 | .env.example | Env template | — | Env var contract | — | INSPECTED |
| F-040 | .gitattributes | VCS config | — | Export/diff attributes | — | INSPECTED |
| F-041 | .gitignore | VCS config | — | Ignore rules | — | INSPECTED |
| F-042 | .npmrc | Tooling config | — | npm settings (audit Phase 4: registry/security flags) | — | INSPECTED |
| F-043 | .styleci.yml | CI config | — | StyleCI rules | — | INSPECTED |
| F-044 | AGENTS.md | Governance doc | — | Project rules (the audit baseline) | — | INSPECTED |
| F-045 | CHANGELOG.md | Doc | — | Change log | — | INSPECTED |
| F-046 | CLAUDE.md | Doc | — | Empty file on disk | — | INSPECTED |
 
### 4.2 Module: Access (55 files)
 
| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-047 | Modules/Access/app/Console/SyncLandlordAccessCommand.php | Command | Access | Sync landlord roles/permissions baseline | spatie/permission | INSPECTED |
| F-048 | Modules/Access/app/Console/SyncTenantAccessCommand.php | Command | Access | Sync tenant roles/permissions baseline | spatie/permission, multitenancy | INSPECTED |
| F-049 | Modules/Access/app/Http/Controllers/.gitkeep | Placeholder | Access | Dir marker | — | INSPECTED |
| F-050 | Modules/Access/app/Http/Controllers/AccessController.php | Controller | Access | Scaffold CRUD (verify usage) | — | INSPECTED |
| F-051 | Modules/Access/app/Http/Controllers/ProfileController.php | Controller | Access | Tenant profile mgmt | — | INSPECTED |
| F-052 | Modules/Access/app/Http/Controllers/RoleController.php | Controller | Access | Tenant role CRUD | spatie/permission | INSPECTED |
| F-053 | Modules/Access/app/Http/Controllers/TenantAuthController.php | Controller | Access | Tenant login/register/logout | auth guard | INSPECTED |
| F-054 | Modules/Access/app/Http/Controllers/UserController.php | Controller | Access | Tenant user CRUD | — | INSPECTED |
| F-055 | Modules/Access/app/Models/Permission.php | Model | Access | Tenant permission model | spatie/permission | INSPECTED |
| F-056 | Modules/Access/app/Models/Role.php | Model | Access | Tenant role model | spatie/permission | INSPECTED |
| F-057 | Modules/Access/app/Models/User.php | Model | Access | Tenant user model | auth, permission | INSPECTED |
| F-058 | Modules/Access/app/Providers/.gitkeep | Placeholder | Access | Dir marker | — | INSPECTED |
| F-059 | Modules/Access/app/Providers/AccessServiceProvider.php | Provider | Access | Module boot/registrations | nwidart | INSPECTED |
| F-060 | Modules/Access/app/Providers/EventServiceProvider.php | Provider | Access | Event wiring (verify empty?) | — | INSPECTED |
| F-061 | Modules/Access/app/Providers/RouteServiceProvider.php | Provider | Access | Route registration | — | INSPECTED |
| F-062 | Modules/Access/app/Services/AccessBaselineProvisioner.php | Service | Access | Provision baseline roles/perms per tenant | — | INSPECTED |
| F-063 | Modules/Access/app/Services/AccessInvariants.php | Service | Access | Access invariant enforcement | — | INSPECTED |
| F-064 | Modules/Access/app/Services/AuditWriter.php | Service | Access | Audit log writer | AdminAuditLog? | INSPECTED |
| F-065 | Modules/Access/app/Services/ManagementPolicy.php | Service | Access | Who-can-manage-whom rules | — | INSPECTED |
| F-066 | Modules/Access/app/Services/TenantUserService.php | Service | Access | Tenant user lifecycle ops | — | INSPECTED |
| F-067 | Modules/Access/app/Support/EffectivePermissionSet.php | Support | Access | Effective permission computation | — | INSPECTED |
| F-068 | Modules/Access/app/Support/LandlordPermissions.php | Support | Access | Landlord permission catalogue | — | INSPECTED |
| F-069 | Modules/Access/app/Support/TenantPermissions.php | Support | Access | Tenant permission catalogue | — | INSPECTED |
| F-070 | Modules/Access/composer.json | Manifest | Access | Module composer (empty nwidart meta) | — | INSPECTED |
| F-071 | Modules/Access/config/.gitkeep | Placeholder | Access | Dir marker | — | INSPECTED |
| F-072 | Modules/Access/config/config.php | Config | Access | Module config (verify contents) | — | INSPECTED |
| F-073 | Modules/Access/database/factories/.gitkeep | Placeholder | Access | Dir marker | — | INSPECTED |
| F-074 | Modules/Access/database/migrations/.gitkeep | Placeholder | Access | Dir marker — NO module migrations | — | INSPECTED |
| F-075 | Modules/Access/database/seeders/.gitkeep | Placeholder | Access | Dir marker | — | INSPECTED |
| F-076 | Modules/Access/database/seeders/AccessDatabaseSeeder.php | Seeder | Access | Access seeding | — | INSPECTED |
| F-077 | Modules/Access/lang/ar.json | Translations | Access | Arabic strings | — | INSPECTED |
| F-078 | Modules/Access/lang/ar/validation.php | Translations | Access | Arabic validation msgs | — | INSPECTED |
| F-079 | Modules/Access/lang/en.json | Translations | Access | English strings | — | INSPECTED |
| F-080 | Modules/Access/lang/en/validation.php | Translations | Access | English validation msgs | — | INSPECTED |
| F-081 | Modules/Access/module.json | Manifest | Access | Module registration (provider: AccessServiceProvider) | nwidart | INSPECTED |
| F-082 | Modules/Access/package.json | Manifest | Access | Module npm (scaffold) | — | INSPECTED |
| F-083 | Modules/Access/resources/assets/.gitkeep | Placeholder | Access | Dir marker | — | INSPECTED |
| F-084 | Modules/Access/resources/assets/js/app.js | Asset (scaffold) | Access | nwidart scaffold asset | — | INSPECTED |
| F-085 | Modules/Access/resources/assets/sass/app.scss | Asset (scaffold) | Access | nwidart scaffold sass | — | INSPECTED |
| F-086 | Modules/Access/resources/js/Pages/Auth/Login.vue | Vue page | Access | Tenant login page | inertia, vue | INSPECTED |
| F-087 | Modules/Access/resources/js/Pages/Auth/Register.vue | Vue page | Access | Tenant register page | inertia, vue | INSPECTED |
| F-088 | Modules/Access/resources/js/Pages/Create.vue | Vue page | Access | Scaffold page (verify usage) | — | INSPECTED |
| F-089 | Modules/Access/resources/js/Pages/Edit.vue | Vue page | Access | Scaffold page | — | INSPECTED |
| F-090 | Modules/Access/resources/js/Pages/Index.vue | Vue page | Access | Scaffold page | — | INSPECTED |
| F-091 | Modules/Access/resources/js/Pages/Profile/Edit.vue | Vue page | Access | Profile edit page | inertia | INSPECTED |
| F-092 | Modules/Access/resources/js/Pages/Roles/Index.vue | Vue page | Access | Role listing | inertia | INSPECTED |
| F-093 | Modules/Access/resources/js/Pages/Show.vue | Vue page | Access | Scaffold page | — | INSPECTED |
| F-094 | Modules/Access/resources/js/Pages/Users/Index.vue | Vue page | Access | User listing | inertia | INSPECTED |
| F-095 | Modules/Access/resources/views/.gitkeep | Placeholder | Access | Dir marker | — | INSPECTED |
| F-096 | Modules/Access/routes/.gitkeep | Placeholder | Access | Dir marker | — | INSPECTED |
| F-097 | Modules/Access/routes/api.php | Routes | Access | Module API routes | — | INSPECTED |
| F-098 | Modules/Access/routes/web.php | Routes | Access | Module web routes | — | INSPECTED |
| F-099 | Modules/Access/tests/Feature/.gitkeep | Placeholder | Access | Empty test dir | — | INSPECTED |
| F-100 | Modules/Access/tests/Unit/.gitkeep | Placeholder | Access | Empty test dir | — | INSPECTED |
| F-101 | Modules/Access/vite.config.js | Build config | Access | Module vite (scaffold) | vite | INSPECTED |
 
### 4.3 Module: Core (83 files)
 
| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-102 | Modules/Core/app/Contracts/PlanContract.php | Interface | Core | Plan abstraction | — | INSPECTED |
| F-103 | Modules/Core/app/Contracts/QuotaManagerContract.php | Interface | Core | Quota abstraction | — | INSPECTED |
| F-104 | Modules/Core/app/Contracts/SettingManagerContract.php | Interface | Core | Settings abstraction | — | INSPECTED |
| F-105 | Modules/Core/app/Contracts/SubscriptionContract.php | Interface | Core | Subscription abstraction | — | INSPECTED |
| F-106 | Modules/Core/app/Contracts/TenantContract.php | Interface | Core | Tenant abstraction | — | INSPECTED |
| F-107 | Modules/Core/app/Enums/BillingInterval.php | Enum | Core | Billing intervals SSoT | — | INSPECTED |
| F-108 | Modules/Core/app/Enums/Currency.php | Enum | Core | Currency SSoT | — | INSPECTED |
| F-109 | Modules/Core/app/Enums/Locale.php | Enum | Core | Locale SSoT | — | INSPECTED |
| F-110 | Modules/Core/app/Enums/SubscriptionStatus.php | Enum | Core | Subscription status SSoT | — | INSPECTED |
| F-111 | Modules/Core/app/Enums/TenantStatus.php | Enum | Core | Tenant status SSoT | — | INSPECTED |
| F-112 | Modules/Core/app/Enums/ThemeMode.php | Enum | Core | Theme mode SSoT | — | INSPECTED |
| F-113 | Modules/Core/app/Enums/ThemePalette.php | Enum | Core | Theme palette SSoT | — | INSPECTED |
| F-114 | Modules/Core/app/Events/PlanChanged.php | Event | Core | Plan change broadcast | — | INSPECTED |
| F-115 | Modules/Core/app/Events/SubscriptionCreated.php | Event | Core | Subscription created | — | INSPECTED |
| F-116 | Modules/Core/app/Events/SubscriptionUpdated.php | Event | Core | Subscription updated | — | INSPECTED |
| F-117 | Modules/Core/app/Events/TenantCreated.php | Event | Core | Tenant created | — | INSPECTED |
| F-118 | Modules/Core/app/Events/TenantProvisioned.php | Event | Core | Tenant provisioned | — | INSPECTED |
| F-119 | Modules/Core/app/Events/TenantStatusChanged.php | Event | Core | Tenant status changed | — | INSPECTED |
| F-120 | Modules/Core/app/Http/Controllers/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-121 | Modules/Core/app/Http/Controllers/CoreController.php | Controller | Core | Scaffold controller | — | INSPECTED |
| F-122 | Modules/Core/app/Http/Controllers/LocaleController.php | Controller | Core | Locale switching | — | INSPECTED |
| F-123 | Modules/Core/app/Http/Controllers/ThemeController.php | Controller | Core | Theme switching | — | INSPECTED |
| F-124 | Modules/Core/app/Providers/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-125 | Modules/Core/app/Providers/CoreServiceProvider.php | Provider | Core | Core bindings (contracts→impls?) | — | INSPECTED |
| F-126 | Modules/Core/app/Providers/EventServiceProvider.php | Provider | Core | Event wiring | — | INSPECTED |
| F-127 | Modules/Core/app/Providers/RouteServiceProvider.php | Provider | Core | Route registration | — | INSPECTED |
| F-128 | Modules/Core/app/Tasks/ScopePermissionCacheTask.php | SwitchTenantTask | Core | Per-tenant permission cache scoping | spatie/permission, multitenancy | INSPECTED |
| F-129 | Modules/Core/composer.json | Manifest | Core | Module composer | — | INSPECTED |
| F-130 | Modules/Core/config/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-131 | Modules/Core/config/config.php | Config | Core | Module config | — | INSPECTED |
| F-132 | Modules/Core/database/factories/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-133 | Modules/Core/database/migrations/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-134 | Modules/Core/database/seeders/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-135 | Modules/Core/database/seeders/CoreDatabaseSeeder.php | Seeder | Core | Core seeding | — | INSPECTED |
| F-136 | Modules/Core/lang/ar.json | Translations | Core | Arabic strings | — | INSPECTED |
| F-137 | Modules/Core/lang/en.json | Translations | Core | English strings | — | INSPECTED |
| F-138 | Modules/Core/module.json | Manifest | Core | Module registration | nwidart | INSPECTED |
| F-139 | Modules/Core/package.json | Manifest | Core | Module npm (scaffold) | — | INSPECTED |
| F-140 | Modules/Core/resources/assets/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-141 | Modules/Core/resources/assets/js/app.js | Asset (scaffold) | Core | nwidart scaffold | — | INSPECTED |
| F-142 | Modules/Core/resources/assets/sass/app.scss | Asset (scaffold) | Core | nwidart scaffold | — | INSPECTED |
| F-143 | Modules/Core/resources/js/Components/BadgeCell.vue | Vue component | Core | Status badge cell | vue | INSPECTED |
| F-144 | Modules/Core/resources/js/Components/BaseButton.vue | Vue component | Core | Mandatory button primitive | vue, inertia | INSPECTED |
| F-145 | Modules/Core/resources/js/Components/ConfirmDialog.vue | Vue component | Core | Mandatory confirm dialog | vue | INSPECTED |
| F-146 | Modules/Core/resources/js/Components/CurrencyCell.vue | Vue component | Core | Currency formatter cell | vue | INSPECTED |
| F-147 | Modules/Core/resources/js/Components/DataTable.vue | Vue component | Core | Read-only table | vue | INSPECTED |
| F-148 | Modules/Core/resources/js/Components/EmptyState.vue | Vue component | Core | Empty state | vue | INSPECTED |
| F-149 | Modules/Core/resources/js/Components/EnterpriseDataGrid.vue | Vue component | Core | Mandatory listing grid | vue | INSPECTED |
| F-150 | Modules/Core/resources/js/Components/EnterpriseFormEngine.vue | Vue component | Core | Form engine | vue | INSPECTED |
| F-151 | Modules/Core/resources/js/Components/FilterSelect.vue | Vue component | Core | Filter dropdown | vue | INSPECTED |
| F-152 | Modules/Core/resources/js/Components/FormField.vue | Vue component | Core | Mandatory form field wrapper | vue | INSPECTED |
| F-153 | Modules/Core/resources/js/Components/FormModal.vue | Vue component | Core | Mandatory form modal | vue | INSPECTED |
| F-154 | Modules/Core/resources/js/Components/IconButton.vue | Vue component | Core | Icon-only action | vue | INSPECTED |
| F-155 | Modules/Core/resources/js/Components/LanguageSwitcher.vue | Vue component | Core | Locale switcher UI | vue | INSPECTED |
| F-156 | Modules/Core/resources/js/Components/Modal.vue | Vue component | Core | Base modal | vue | INSPECTED |
| F-157 | Modules/Core/resources/js/Components/PageHeader.vue | Vue component | Core | Mandatory page header | vue | INSPECTED |
| F-158 | Modules/Core/resources/js/Components/PalettePicker.vue | Vue component | Core | Theme palette picker | vue | INSPECTED |
| F-159 | Modules/Core/resources/js/Components/Panel.vue | Vue component | Core | Mandatory card panel | vue | INSPECTED |
| F-160 | Modules/Core/resources/js/Components/PermissionBundlePicker.vue | Vue component | Core | Permission bundle picker | vue | INSPECTED |
| F-161 | Modules/Core/resources/js/Components/SkeletonLoader.vue | Vue component | Core | Loading skeleton | vue | INSPECTED |
| F-162 | Modules/Core/resources/js/Components/StatCard.vue | Vue component | Core | Metric card | vue | INSPECTED |
| F-163 | Modules/Core/resources/js/Components/StatusBadge.vue | Vue component | Core | Status badge | vue | INSPECTED |
| F-164 | Modules/Core/resources/js/Components/TabNav.vue | Vue component | Core | Tab switcher | vue | INSPECTED |
| F-165 | Modules/Core/resources/js/Components/ThemeSwitcher.vue | Vue component | Core | Theme switcher | vue, useThemeStore | INSPECTED |
| F-166 | Modules/Core/resources/js/Composables/useCurrency.ts | TS composable | Core | Currency formatting | vue | INSPECTED |
| F-167 | Modules/Core/resources/js/Composables/useI18n.ts | TS composable | Core | i18n helper | vue | INSPECTED |
| F-168 | Modules/Core/resources/js/Layouts/GuestLayout.vue | Vue layout | Core | Guest shell | vue, inertia | INSPECTED |
| F-169 | Modules/Core/resources/js/Layouts/LandlordLayout.vue | Vue layout | Core | Landlord admin shell | vue, inertia | INSPECTED |
| F-170 | Modules/Core/resources/js/Layouts/TenantLayout.vue | Vue layout | Core | Tenant app shell | vue, inertia | INSPECTED |
| F-171 | Modules/Core/resources/js/Pages/Create.vue | Vue page | Core | Scaffold page | — | INSPECTED |
| F-172 | Modules/Core/resources/js/Pages/Edit.vue | Vue page | Core | Scaffold page | — | INSPECTED |
| F-173 | Modules/Core/resources/js/Pages/ErrorPage.vue | Vue page | Core | Unified error page (ErrorPageRenderer target) | inertia | INSPECTED |
| F-174 | Modules/Core/resources/js/Pages/Index.vue | Vue page | Core | Scaffold page | — | INSPECTED |
| F-175 | Modules/Core/resources/js/Pages/Show.vue | Vue page | Core | Scaffold page | — | INSPECTED |
| F-176 | Modules/Core/resources/js/Stores/setupInertiaStateBridge.ts | TS store | Core | Inertia→Pinia bridge (architectural invariant) | pinia, inertia | INSPECTED |
| F-177 | Modules/Core/resources/js/Stores/useThemeStore.ts | TS store | Core | Theme SSoT (architectural invariant) | pinia | INSPECTED |
| F-178 | Modules/Core/resources/views/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-179 | Modules/Core/routes/.gitkeep | Placeholder | Core | Dir marker | — | INSPECTED |
| F-180 | Modules/Core/routes/api.php | Routes | Core | Module API routes | — | INSPECTED |
| F-181 | Modules/Core/routes/web.php | Routes | Core | Module web routes | — | INSPECTED |
| F-182 | Modules/Core/tests/Feature/.gitkeep | Placeholder | Core | Empty test dir | — | INSPECTED |
| F-183 | Modules/Core/tests/Unit/.gitkeep | Placeholder | Core | Empty test dir | — | INSPECTED |
| F-184 | Modules/Core/vite.config.js | Build config | Core | Module vite (scaffold) | vite | INSPECTED |
 
### 4.4 Module: Landlord (62 files)
 
| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-185 | Modules/Landlord/app/Console/RebuildDatabasesCommand.php | Command | Landlord | Rebuild landlord/tenant DBs | multitenancy | INSPECTED |
| F-186 | Modules/Landlord/app/Http/Controllers/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-187 | Modules/Landlord/app/Http/Controllers/DashboardController.php | Controller | Landlord | Admin dashboard | metrics service | INSPECTED |
| F-188 | Modules/Landlord/app/Http/Controllers/LandingController.php | Controller | Landlord | Public landing pages | — | INSPECTED |
| F-189 | Modules/Landlord/app/Http/Controllers/LandlordAdminController.php | Controller | Landlord | Landlord admin user mgmt | — | INSPECTED |
| F-190 | Modules/Landlord/app/Http/Controllers/LandlordAuthController.php | Controller | Landlord | Landlord login/logout | auth guard | INSPECTED |
| F-191 | Modules/Landlord/app/Http/Controllers/LandlordController.php | Controller | Landlord | Scaffold controller | — | INSPECTED |
| F-192 | Modules/Landlord/app/Http/Controllers/LandlordRoleController.php | Controller | Landlord | Landlord role mgmt | spatie/permission | INSPECTED |
| F-193 | Modules/Landlord/app/Http/Controllers/ModuleManagementController.php | Controller | Landlord | Module enable/disable | nwidart | INSPECTED |
| F-194 | Modules/Landlord/app/Http/Controllers/TenantController.php | Controller | Landlord | Tenant CRUD (admin side) | Tenant model | INSPECTED |
| F-195 | Modules/Landlord/app/Http/Controllers/TenantRegistrationController.php | Controller | Landlord | Public tenant signup | TenantProvisioner | INSPECTED |
| F-196 | Modules/Landlord/app/Models/AdminAuditLog.php | Model | Landlord | Admin audit trail | — | INSPECTED |
| F-197 | Modules/Landlord/app/Models/LandlordUser.php | Model | Landlord | Landlord admin user | auth, permission | INSPECTED |
| F-198 | Modules/Landlord/app/Models/Tenant.php | Model | Landlord | Tenant model (landlord DB) — DUPLICATE of app/Models/Tenant? | multitenancy | INSPECTED |
| F-199 | Modules/Landlord/app/Providers/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-200 | Modules/Landlord/app/Providers/EventServiceProvider.php | Provider | Landlord | Event wiring | — | INSPECTED |
| F-201 | Modules/Landlord/app/Providers/LandlordServiceProvider.php | Provider | Landlord | Module boot | nwidart | INSPECTED |
| F-202 | Modules/Landlord/app/Providers/RouteServiceProvider.php | Provider | Landlord | Route registration | — | INSPECTED |
| F-203 | Modules/Landlord/app/Services/LandlordMetricsService.php | Service | Landlord | Dashboard metrics | — | INSPECTED |
| F-204 | Modules/Landlord/app/Services/TenantLifecycleService.php | Service | Landlord | Suspend/activate/delete tenants | — | INSPECTED |
| F-205 | Modules/Landlord/app/Services/TenantProvisioner.php | Service | Landlord | Tenant DB + baseline provisioning | multitenancy | INSPECTED |
| F-206 | Modules/Landlord/composer.json | Manifest | Landlord | Module composer | — | INSPECTED |
| F-207 | Modules/Landlord/config/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-208 | Modules/Landlord/config/config.php | Config | Landlord | Module config | — | INSPECTED |
| F-209 | Modules/Landlord/database/factories/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-210 | Modules/Landlord/database/migrations/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-211 | Modules/Landlord/database/migrations/2026_09_29_120001_add_saas_fields_to_tenants_table.php | Migration | Landlord | SaaS fields on tenants | — | INSPECTED |
| F-212 | Modules/Landlord/database/migrations/2026_09_29_120002_create_landlord_users_table.php | Migration | Landlord | landlord_users table | — | INSPECTED |
| F-213 | Modules/Landlord/database/migrations/2026_10_06_000003_create_admin_audit_logs_table.php | Migration | Landlord | admin_audit_logs table | — | INSPECTED |
| F-214 | Modules/Landlord/database/seeders/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-215 | Modules/Landlord/database/seeders/LandlordDatabaseSeeder.php | Seeder | Landlord | Landlord seeding | — | INSPECTED |
| F-216 | Modules/Landlord/lang/ar.json | Translations | Landlord | Arabic strings | — | INSPECTED |
| F-217 | Modules/Landlord/lang/ar/validation.php | Translations | Landlord | Arabic validation | — | INSPECTED |
| F-218 | Modules/Landlord/lang/en.json | Translations | Landlord | English strings | — | INSPECTED |
| F-219 | Modules/Landlord/lang/en/validation.php | Translations | Landlord | English validation | — | INSPECTED |
| F-220 | Modules/Landlord/module.json | Manifest | Landlord | Module registration | nwidart | INSPECTED |
| F-221 | Modules/Landlord/package.json | Manifest | Landlord | Module npm (scaffold) | — | INSPECTED |
| F-222 | Modules/Landlord/resources/assets/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-223 | Modules/Landlord/resources/assets/js/app.js | Asset (scaffold) | Landlord | nwidart scaffold | — | INSPECTED |
| F-224 | Modules/Landlord/resources/assets/sass/app.scss | Asset (scaffold) | Landlord | nwidart scaffold | — | INSPECTED |
| F-225 | Modules/Landlord/resources/js/Pages/Admins/Index.vue | Vue page | Landlord | Admin listing | inertia | INSPECTED |
| F-226 | Modules/Landlord/resources/js/Pages/Auth/Login.vue | Vue page | Landlord | Landlord login | inertia | INSPECTED |
| F-227 | Modules/Landlord/resources/js/Pages/Create.vue | Vue page | Landlord | Scaffold page | — | INSPECTED |
| F-228 | Modules/Landlord/resources/js/Pages/Dashboard.vue | Vue page | Landlord | Admin dashboard page | inertia | INSPECTED |
| F-229 | Modules/Landlord/resources/js/Pages/Edit.vue | Vue page | Landlord | Scaffold page | — | INSPECTED |
| F-230 | Modules/Landlord/resources/js/Pages/Index.vue | Vue page | Landlord | Scaffold page | — | INSPECTED |
| F-231 | Modules/Landlord/resources/js/Pages/Landing/Pricing.vue | Vue page | Landlord | Public pricing page | inertia | INSPECTED |
| F-232 | Modules/Landlord/resources/js/Pages/Landing/RegisterTenant.vue | Vue page | Landlord | Public tenant signup page | inertia | INSPECTED |
| F-233 | Modules/Landlord/resources/js/Pages/Landing/Welcome.vue | Vue page | Landlord | Public landing | inertia | INSPECTED |
| F-234 | Modules/Landlord/resources/js/Pages/Modules/Index.vue | Vue page | Landlord | Module management page | inertia | INSPECTED |
| F-235 | Modules/Landlord/resources/js/Pages/Roles/Index.vue | Vue page | Landlord | Landlord role page | inertia | INSPECTED |
| F-236 | Modules/Landlord/resources/js/Pages/Show.vue | Vue page | Landlord | Scaffold page | — | INSPECTED |
| F-237 | Modules/Landlord/resources/js/Pages/Tenants/Create.vue | Vue page | Landlord | Tenant create page | inertia | INSPECTED |
| F-238 | Modules/Landlord/resources/js/Pages/Tenants/Index.vue | Vue page | Landlord | Tenant listing | inertia | INSPECTED |
| F-239 | Modules/Landlord/resources/js/Pages/Tenants/Show.vue | Vue page | Landlord | Tenant detail | inertia | INSPECTED |
| F-240 | Modules/Landlord/resources/views/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-241 | Modules/Landlord/routes/.gitkeep | Placeholder | Landlord | Dir marker | — | INSPECTED |
| F-242 | Modules/Landlord/routes/api.php | Routes | Landlord | Module API routes | — | INSPECTED |
| F-243 | Modules/Landlord/routes/web.php | Routes | Landlord | Module web routes | — | INSPECTED |
| F-244 | Modules/Landlord/tests/Feature/.gitkeep | Placeholder | Landlord | Empty test dir | — | INSPECTED |
| F-245 | Modules/Landlord/tests/Unit/.gitkeep | Placeholder | Landlord | Empty test dir | — | INSPECTED |
| F-246 | Modules/Landlord/vite.config.js | Build config | Landlord | Module vite (scaffold) | vite | INSPECTED |
 
### 4.5 Module: Settings (37 files)
 
| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-247 | Modules/Settings/app/Http/Controllers/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-248 | Modules/Settings/app/Http/Controllers/LandlordSettingsController.php | Controller | Settings | Landlord settings UI | SettingService | INSPECTED |
| F-249 | Modules/Settings/app/Http/Controllers/TenantSettingsController.php | Controller | Settings | Tenant settings UI | SettingService | INSPECTED |
| F-250 | Modules/Settings/app/Http/Requests/UpdateSettingsRequest.php | FormRequest | Settings | Settings validation | — | INSPECTED |
| F-251 | Modules/Settings/app/Models/Concerns/ParsesSettingValue.php | Trait | Settings | Setting value casting | — | INSPECTED |
| F-252 | Modules/Settings/app/Models/Setting.php | Model | Settings | Landlord setting model | — | INSPECTED |
| F-253 | Modules/Settings/app/Models/TenantSetting.php | Model | Settings | Tenant setting model | multitenancy | INSPECTED |
| F-254 | Modules/Settings/app/Providers/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-255 | Modules/Settings/app/Providers/EventServiceProvider.php | Provider | Settings | Event wiring | — | INSPECTED |
| F-256 | Modules/Settings/app/Providers/RouteServiceProvider.php | Provider | Settings | Route registration | — | INSPECTED |
| F-257 | Modules/Settings/app/Providers/SettingsServiceProvider.php | Provider | Settings | Module boot | nwidart | INSPECTED |
| F-258 | Modules/Settings/app/Services/SettingService.php | Service | Settings | Settings get/set + governance | — | INSPECTED |
| F-259 | Modules/Settings/composer.json | Manifest | Settings | Module composer | — | INSPECTED |
| F-260 | Modules/Settings/config/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-261 | Modules/Settings/config/config.php | Config | Settings | Module config | — | INSPECTED |
| F-262 | Modules/Settings/config/settings.php | Config | Settings | Settings registry/definitions | — | INSPECTED |
| F-263 | Modules/Settings/database/factories/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-264 | Modules/Settings/database/migrations/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-265 | Modules/Settings/database/migrations/2026_09_29_110001_create_system_settings_table.php | Migration | Settings | system_settings table | — | INSPECTED |
| F-266 | Modules/Settings/database/seeders/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-267 | Modules/Settings/database/seeders/SettingsDatabaseSeeder.php | Seeder | Settings | Settings seeding | — | INSPECTED |
| F-268 | Modules/Settings/lang/ar.json | Translations | Settings | Arabic strings | — | INSPECTED |
| F-269 | Modules/Settings/lang/en.json | Translations | Settings | English strings | — | INSPECTED |
| F-270 | Modules/Settings/module.json | Manifest | Settings | Module registration | nwidart | INSPECTED |
| F-271 | Modules/Settings/package.json | Manifest | Settings | Module npm (scaffold) | — | INSPECTED |
| F-272 | Modules/Settings/resources/assets/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-273 | Modules/Settings/resources/assets/js/app.js | Asset (scaffold) | Settings | nwidart scaffold | — | INSPECTED |
| F-274 | Modules/Settings/resources/assets/sass/app.scss | Asset (scaffold) | Settings | nwidart scaffold | — | INSPECTED |
| F-275 | Modules/Settings/resources/js/Pages/LandlordSettings.vue | Vue page | Settings | Landlord settings page | inertia | INSPECTED |
| F-276 | Modules/Settings/resources/js/Pages/TenantSettings.vue | Vue page | Settings | Tenant settings page | inertia | INSPECTED |
| F-277 | Modules/Settings/resources/views/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-278 | Modules/Settings/routes/.gitkeep | Placeholder | Settings | Dir marker | — | INSPECTED |
| F-279 | Modules/Settings/routes/api.php | Routes | Settings | Module API routes | — | INSPECTED |
| F-280 | Modules/Settings/routes/web.php | Routes | Settings | Module web routes | — | INSPECTED |
| F-281 | Modules/Settings/tests/Feature/.gitkeep | Placeholder | Settings | Empty test dir | — | INSPECTED |
| F-282 | Modules/Settings/tests/Unit/.gitkeep | Placeholder | Settings | Empty test dir | — | INSPECTED |
| F-283 | Modules/Settings/vite.config.js | Build config | Settings | Module vite (scaffold) | vite | INSPECTED |
 
### 4.6 Module: Subscription (45 files)
 
| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-284 | Modules/Subscription/app/Http/Controllers/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-285 | Modules/Subscription/app/Http/Controllers/PlanController.php | Controller | Subscription | Plan CRUD | Plan model | INSPECTED |
| F-286 | Modules/Subscription/app/Http/Controllers/SubscriptionController.php | Controller | Subscription | Subscription mgmt | SubscriptionService | INSPECTED |
| F-287 | Modules/Subscription/app/Models/Plan.php | Model | Subscription | Plan model | — | INSPECTED |
| F-288 | Modules/Subscription/app/Models/Subscription.php | Model | Subscription | Subscription model | — | INSPECTED |
| F-289 | Modules/Subscription/app/Providers/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-290 | Modules/Subscription/app/Providers/EventServiceProvider.php | Provider | Subscription | Event wiring | — | INSPECTED |
| F-291 | Modules/Subscription/app/Providers/RouteServiceProvider.php | Provider | Subscription | Route registration | — | INSPECTED |
| F-292 | Modules/Subscription/app/Providers/SubscriptionServiceProvider.php | Provider | Subscription | Module boot | nwidart | INSPECTED |
| F-293 | Modules/Subscription/app/Services/QuotaService.php | Service | Subscription | Quota enforcement | Core contracts | INSPECTED |
| F-294 | Modules/Subscription/app/Services/SubscriptionService.php | Service | Subscription | Subscription lifecycle | Core events | INSPECTED |
| F-295 | Modules/Subscription/composer.json | Manifest | Subscription | Module composer | — | INSPECTED |
| F-296 | Modules/Subscription/config/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-297 | Modules/Subscription/config/config.php | Config | Subscription | Module config | — | INSPECTED |
| F-298 | Modules/Subscription/database/factories/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-299 | Modules/Subscription/database/migrations/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-300 | Modules/Subscription/database/migrations/2026_09_29_100001_create_plans_table.php | Migration | Subscription | plans table | — | INSPECTED |
| F-301 | Modules/Subscription/database/migrations/2026_09_29_100002_create_subscriptions_table.php | Migration | Subscription | subscriptions table | — | INSPECTED |
| F-302 | Modules/Subscription/database/seeders/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-303 | Modules/Subscription/database/seeders/PlanSeeder.php | Seeder | Subscription | Plan seeding | — | INSPECTED |
| F-304 | Modules/Subscription/database/seeders/SubscriptionDatabaseSeeder.php | Seeder | Subscription | Module seeder | — | INSPECTED |
| F-305 | Modules/Subscription/lang/ar.json | Translations | Subscription | Arabic strings | — | INSPECTED |
| F-306 | Modules/Subscription/lang/ar/validation.php | Translations | Subscription | Arabic validation | — | INSPECTED |
| F-307 | Modules/Subscription/lang/en.json | Translations | Subscription | English strings | — | INSPECTED |
| F-308 | Modules/Subscription/lang/en/validation.php | Translations | Subscription | English validation | — | INSPECTED |
| F-309 | Modules/Subscription/module.json | Manifest | Subscription | Module registration | nwidart | INSPECTED |
| F-310 | Modules/Subscription/package.json | Manifest | Subscription | Module npm (scaffold) | — | INSPECTED |
| F-311 | Modules/Subscription/resources/assets/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-312 | Modules/Subscription/resources/assets/js/app.js | Asset (scaffold) | Subscription | nwidart scaffold | — | INSPECTED |
| F-313 | Modules/Subscription/resources/assets/sass/app.scss | Asset (scaffold) | Subscription | nwidart scaffold | — | INSPECTED |
| F-314 | Modules/Subscription/resources/js/Components/PlanFormFields.vue | Vue component | Subscription | Plan form fragment | Core components | INSPECTED |
| F-315 | Modules/Subscription/resources/js/Pages/Create.vue | Vue page | Subscription | Plan create | inertia | INSPECTED |
| F-316 | Modules/Subscription/resources/js/Pages/Edit.vue | Vue page | Subscription | Plan edit | inertia | INSPECTED |
| F-317 | Modules/Subscription/resources/js/Pages/Index.vue | Vue page | Subscription | Subscription index | inertia | INSPECTED |
| F-318 | Modules/Subscription/resources/js/Pages/LandlordSubscriptions.vue | Vue page | Subscription | Landlord-side subscriptions | inertia | INSPECTED |
| F-319 | Modules/Subscription/resources/js/Pages/Overview.vue | Vue page | Subscription | Tenant billing overview | inertia | INSPECTED |
| F-320 | Modules/Subscription/resources/js/Pages/Plans.vue | Vue page | Subscription | Plan catalogue page | inertia | INSPECTED |
| F-321 | Modules/Subscription/resources/js/Pages/Show.vue | Vue page | Subscription | Detail page | inertia | INSPECTED |
| F-322 | Modules/Subscription/resources/views/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-323 | Modules/Subscription/routes/.gitkeep | Placeholder | Subscription | Dir marker | — | INSPECTED |
| F-324 | Modules/Subscription/routes/api.php | Routes | Subscription | Module API routes | — | INSPECTED |
| F-325 | Modules/Subscription/routes/web.php | Routes | Subscription | Module web routes | — | INSPECTED |
| F-326 | Modules/Subscription/tests/Feature/.gitkeep | Placeholder | Subscription | Empty test dir | — | INSPECTED |
| F-327 | Modules/Subscription/tests/Unit/.gitkeep | Placeholder | Subscription | Empty test dir | — | INSPECTED |
| F-328 | Modules/Subscription/vite.config.js | Build config | Subscription | Module vite (scaffold) | vite | INSPECTED |
 
### 4.7 Module: Tenant (33 files)
 
| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-329 | Modules/Tenant/app/Http/Controllers/.gitkeep | Placeholder | Tenant | Dir marker | — | INSPECTED |
| F-330 | Modules/Tenant/app/Http/Controllers/TenantController.php | Controller | Tenant | Tenant-side controller (name collision with Landlord's) | — | INSPECTED |
| F-331 | Modules/Tenant/app/Http/Controllers/TenantDashboardController.php | Controller | Tenant | Tenant dashboard | — | INSPECTED |
| F-332 | Modules/Tenant/app/Providers/.gitkeep | Placeholder | Tenant | Dir marker | — | INSPECTED |
| F-333 | Modules/Tenant/app/Providers/EventServiceProvider.php | Provider | Tenant | Event wiring | — | INSPECTED |
| F-334 | Modules/Tenant/app/Providers/RouteServiceProvider.php | Provider | Tenant | Route registration | — | INSPECTED |
| F-335 | Modules/Tenant/app/Providers/TenantServiceProvider.php | Provider | Tenant | Module boot | nwidart | INSPECTED |
| F-336 | Modules/Tenant/composer.json | Manifest | Tenant | Module composer | — | INSPECTED |
| F-337 | Modules/Tenant/config/.gitkeep | Placeholder | Tenant | Dir marker | — | INSPECTED |
| F-338 | Modules/Tenant/config/config.php | Config | Tenant | Module config | — | INSPECTED |
| F-339 | Modules/Tenant/database/factories/.gitkeep | Placeholder | Tenant | Dir marker | — | INSPECTED |
| F-340 | Modules/Tenant/database/migrations/.gitkeep | Placeholder | Tenant | Dir marker — NO module migrations | — | INSPECTED |
| F-341 | Modules/Tenant/database/seeders/.gitkeep | Placeholder | Tenant | Dir marker | — | INSPECTED |
| F-342 | Modules/Tenant/database/seeders/TenantDatabaseSeeder.php | Seeder | Tenant | Tenant seeding | — | INSPECTED |
| F-343 | Modules/Tenant/lang/ar.json | Translations | Tenant | Arabic strings | — | INSPECTED |
| F-344 | Modules/Tenant/lang/en.json | Translations | Tenant | English strings | — | INSPECTED |
| F-345 | Modules/Tenant/module.json | Manifest | Tenant | Module registration | nwidart | INSPECTED |
| F-346 | Modules/Tenant/package.json | Manifest | Tenant | Module npm (scaffold) | — | INSPECTED |
| F-347 | Modules/Tenant/resources/assets/.gitkeep | Placeholder | Tenant | Dir marker | — | INSPECTED |
| F-348 | Modules/Tenant/resources/assets/js/app.js | Asset (scaffold) | Tenant | nwidart scaffold | — | INSPECTED |
| F-349 | Modules/Tenant/resources/assets/sass/app.scss | Asset (scaffold) | Tenant | nwidart scaffold | — | INSPECTED |
| F-350 | Modules/Tenant/resources/js/Pages/Create.vue | Vue page | Tenant | Scaffold page | — | INSPECTED |
| F-351 | Modules/Tenant/resources/js/Pages/Dashboard.vue | Vue page | Tenant | Tenant dashboard page | inertia | INSPECTED |
| F-352 | Modules/Tenant/resources/js/Pages/Edit.vue | Vue page | Tenant | Scaffold page | — | INSPECTED |
| F-353 | Modules/Tenant/resources/js/Pages/Index.vue | Vue page | Tenant | Scaffold page | — | INSPECTED |
| F-354 | Modules/Tenant/resources/js/Pages/Show.vue | Vue page | Tenant | Scaffold page | — | INSPECTED |
| F-355 | Modules/Tenant/resources/views/.gitkeep | Placeholder | Tenant | Dir marker | — | INSPECTED |
| F-356 | Modules/Tenant/routes/.gitkeep | Placeholder | Tenant | Dir marker | — | INSPECTED |
| F-357 | Modules/Tenant/routes/api.php | Routes | Tenant | Module API routes | — | INSPECTED |
| F-358 | Modules/Tenant/routes/web.php | Routes | Tenant | Module web routes | — | INSPECTED |
| F-359 | Modules/Tenant/tests/Feature/.gitkeep | Placeholder | Tenant | Empty test dir | — | INSPECTED |
| F-360 | Modules/Tenant/tests/Unit/.gitkeep | Placeholder | Tenant | Empty test dir | — | INSPECTED |
| F-361 | Modules/Tenant/vite.config.js | Build config | Tenant | Module vite (scaffold) | vite | INSPECTED |
 
### 4.8 Root application kernel (app/, bootstrap/, config/, routes/, database/, resources/, public/, storage/, tests/, root manifests)
 
| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-362 | README.md | Doc | — | Project readme | — | INSPECTED |
| F-363 | app/Exceptions/TenantSuspendedException.php | Exception | App | Suspended-tenant signal | — | INSPECTED |
| F-364 | app/Http/Controllers/Controller.php | Base controller | App | Base class | — | INSPECTED |
| F-365 | app/Http/Middleware/EnsureLandlordAdminActive.php | Middleware | App | Block inactive landlord admins | auth | INSPECTED |
| F-366 | app/Http/Middleware/EnsureLandlordContext.php | Middleware | App | Enforce landlord (non-tenant) context | multitenancy | INSPECTED |
| F-367 | app/Http/Middleware/EnsureTenantIsActive.php | Middleware | App | Block suspended tenants | multitenancy | INSPECTED |
| F-368 | app/Http/Middleware/HandleInertiaRequests.php | Middleware | App | Shared Inertia props (SSoT surface) | inertia | INSPECTED |
| F-369 | app/Http/Middleware/IdentifyTenant.php | Middleware | App | Tenant resolution entrypoint | SaaSTenantFinder | INSPECTED |
| F-370 | app/Http/Middleware/SetLocale.php | Middleware | App | Locale resolution | Locale enum | INSPECTED |
| F-371 | app/Models/Tenant.php | Model | App | Root tenant model — DUPLICATE of Landlord module's? | multitenancy | INSPECTED |
| F-372 | app/Models/User.php | Model | App | Root user model (which guard/connection?) | auth | INSPECTED |
| F-373 | app/Providers/AppServiceProvider.php | Provider | App | App boot/bindings | — | INSPECTED |
| F-374 | app/Support/ErrorPageRenderer.php | Support | App | Unified error page rendering | inertia | INSPECTED |
| F-375 | app/TenantFinder/SaaSTenantFinder.php | TenantFinder | App | Domain→tenant resolution | multitenancy | INSPECTED |
| F-376 | artisan | Entrypoint | App | CLI entry | — | INSPECTED |
| F-377 | boost.json | Tooling config | App | Laravel Boost config | laravel/boost | INSPECTED |
| F-378 | bootstrap/app.php | Bootstrap | App | Middleware/exception wiring | framework | INSPECTED |
| F-379 | bootstrap/cache/.gitignore | VCS marker | App | Cache dir exclusion | — | INSPECTED |
| F-380 | bootstrap/providers.php | Bootstrap | App | Provider registration list | — | INSPECTED |
| F-381 | composer.json | Manifest | App | PHP deps + PSR-4 + merge-plugin | — | INSPECTED |
| F-382 | composer.lock | Lockfile | App | Pinned PHP deps (audit: `composer audit`) | — | INSPECTED |
| F-383 | config/app.php | Config | App | App config | — | INSPECTED |
| F-384 | config/auth.php | Config | App | Guards/providers (landlord vs tenant) | — | INSPECTED |
| F-385 | config/cache.php | Config | App | Cache stores (tenant prefixing?) | — | INSPECTED |
| F-386 | config/database.php | Config | App | landlord/tenant connections | — | INSPECTED |
| F-387 | config/filesystems.php | Config | App | Disks (tenant storage isolation?) | — | INSPECTED |
| F-388 | config/logging.php | Config | App | Log channels | — | INSPECTED |
| F-389 | config/mail.php | Config | App | Mail config | — | INSPECTED |
| F-390 | config/media-library.php | Config | App | MediaLibrary config | spatie/medialibrary | INSPECTED |
| F-391 | config/modules.php | Config | App | nwidart module config | nwidart | INSPECTED |
| F-392 | config/multitenancy.php | Config | App | Tenancy config (finder/tasks/queues) | spatie/multitenancy | INSPECTED |
| F-393 | config/permission.php | Config | App | Permission config (teams? cache?) | spatie/permission | INSPECTED |
| F-394 | config/queue.php | Config | App | Queue connections (tenant-aware?) | — | INSPECTED |
| F-395 | config/services.php | Config | App | Third-party services | — | INSPECTED |
| F-396 | config/session.php | Config | App | Session config (domain isolation?) | — | INSPECTED |
| F-397 | database/.gitignore | VCS config | App | DB file exclusion | — | INSPECTED |
| F-398 | database/factories/UserFactory.php | Factory | App | User factory (which user model?) | — | INSPECTED |
| F-399 | database/migrations/landlord/2026_09_29_082214_create_landlord_sessions_table.php | Migration | App | Landlord sessions | — | INSPECTED |
| F-400 | database/migrations/landlord/2026_09_29_082215_create_landlord_cache_table.php | Migration | App | Landlord cache | — | INSPECTED |
| F-401 | database/migrations/landlord/2026_09_29_082216_create_landlord_jobs_table.php | Migration | App | Landlord queue tables | — | INSPECTED |
| F-402 | database/migrations/landlord/2026_09_29_082217_create_landlord_tenants_table.php | Migration | App | tenants table | — | INSPECTED |
| F-403 | database/migrations/landlord/2026_09_29_082218_create_landlord_permission_tables.php | Migration | App | Landlord RBAC tables | spatie/permission | INSPECTED |
| F-404 | database/migrations/landlord/2026_09_29_082219_create_landlord_media_table.php | Migration | App | Landlord media | medialibrary | INSPECTED |
| F-405 | database/migrations/tenant/0001_01_01_000000_create_users_table.php | Migration | App | Tenant users | — | INSPECTED |
| F-406 | database/migrations/tenant/0001_01_01_000001_create_cache_table.php | Migration | App | Tenant cache | — | INSPECTED |
| F-407 | database/migrations/tenant/0001_01_01_000002_create_jobs_table.php | Migration | App | Tenant queue | — | INSPECTED |
| F-408 | database/migrations/tenant/0001_01_01_000003_create_permission_tables.php | Migration | App | Tenant RBAC | spatie/permission | INSPECTED |
| F-409 | database/migrations/tenant/0001_01_01_000004_create_media_table.php | Migration | App | Tenant media | medialibrary | INSPECTED |
| F-410 | database/migrations/tenant/0001_01_01_000005_create_tenant_settings_table.php | Migration | App | tenant_settings table | — | INSPECTED |
| F-411 | database/seeders/DatabaseSeeder.php | Seeder | App | Root seeder orchestrator | — | INSPECTED |
| F-412 | database/seeders/TenantSeeder.php | Seeder | App | Tenant demo seeding | multitenancy | INSPECTED |
| F-413 | env.d.ts | TS types | App | Vite/env type declarations | vite | INSPECTED |
| F-414 | modules_statuses.json | Config | App | Module enable flags (all 6 true) | nwidart | INSPECTED |
| F-415 | package.json | Manifest | App | npm deps (NO lockfile committed) | — | INSPECTED |
| F-416 | phpunit.xml | Test config | App | PHPUnit suites/env | phpunit | INSPECTED |
| F-417 | public/.htaccess | Server config | App | Apache rewrite rules | — | INSPECTED |
| F-418 | public/favicon.ico | Binary asset | App | Favicon | — | INSPECTED |
| F-419 | public/index.php | Entrypoint | App | HTTP front controller | framework | INSPECTED |
| F-420 | public/robots.txt | Config | App | Crawl directives | — | INSPECTED |
| F-421 | resources/css/app.css | Stylesheet | App | Tailwind v4 entry + design tokens | tailwindcss | INSPECTED |
| F-422 | resources/js/app.js | JS entry | App | Inertia/Vue/Pinia bootstrap | vue, inertia, pinia | INSPECTED |
| F-423 | resources/lang/ar.json | Translations | App | Arabic strings (root) | — | INSPECTED |
| F-424 | resources/lang/ar/validation.php | Translations | App | Arabic validation (root) | — | INSPECTED |
| F-425 | resources/lang/en.json | Translations | App | English strings (root) | — | INSPECTED |
| F-426 | resources/lang/en/validation.php | Translations | App | English validation (root) | — | INSPECTED |
| F-427 | resources/views/app.blade.php | Blade | App | Inertia root template | vite | INSPECTED |
| F-428 | resources/views/partials/theme-init.blade.php | Blade | App | Pre-paint theme bootstrap | — | INSPECTED |
| F-429 | routes/console.php | Routes | App | Console routes/schedule | — | INSPECTED |
| F-430 | routes/web.php | Routes | App | Root web routes | — | INSPECTED |
| F-431 | storage/app/.gitignore | VCS marker | App | storage/app exclusion | — | INSPECTED |
| F-432 | storage/app/private/.gitignore | VCS marker | App | private disk exclusion | — | INSPECTED |
| F-433 | storage/app/public/.gitignore | VCS marker | App | public disk exclusion | — | INSPECTED |
| F-434 | storage/framework/.gitignore | VCS marker | App | framework dir exclusion | — | INSPECTED |
| F-435 | storage/framework/cache/.gitignore | VCS marker | App | cache dir exclusion | — | INSPECTED |
| F-436 | storage/framework/cache/data/.gitignore | VCS marker | App | cache data exclusion | — | INSPECTED |
| F-437 | storage/framework/sessions/.gitignore | VCS marker | App | session files exclusion | — | INSPECTED |
| F-438 | storage/framework/testing/.gitignore | VCS marker | App | testing dir exclusion | — | INSPECTED |
| F-439 | storage/framework/views/.gitignore | VCS marker | App | compiled views exclusion | — | INSPECTED |
| F-440 | storage/logs/.gitignore | VCS marker | App | logs exclusion | — | INSPECTED |

### 4.9 Test suite & root build config (16 files)

| ID | Path | Type | Module | Responsibility | Dependencies | Status |
|---|---|---|---|---|---|---|
| F-523 | tests/Feature/ExampleTest.php | Test | App | Scaffold test (verify value) | phpunit | INSPECTED |
| F-524 | tests/Feature/LandlordTenantProvisioningTest.php | Test | App | Tenant provisioning coverage | — | INSPECTED |
| F-525 | tests/Feature/ModularTranslationArchitectureTest.php | Test | App | Translation architecture coverage | — | INSPECTED |
| F-526 | tests/Feature/PlatformAdminManagementTest.php | Test | App | Landlord admin mgmt coverage | — | INSPECTED |
| F-527 | tests/Feature/QuotaEnforcementTest.php | Test | App | Quota enforcement coverage | — | INSPECTED |
| F-528 | tests/Feature/SettingsGovernanceTest.php | Test | App | Settings governance coverage | — | INSPECTED |
| F-529 | tests/Feature/TenancySecurityTest.php | Test | App | Tenancy security coverage | — | INSPECTED |
| F-530 | tests/Feature/TenantAccessControlTest.php | Test | App | Tenant RBAC coverage | — | INSPECTED |
| F-531 | tests/Feature/TenantIsolationTest.php | Test | App | Isolation coverage | — | INSPECTED |
| F-532 | tests/Feature/ThemingAndLocalizationTest.php | Test | App | Theme/locale coverage | — | INSPECTED |
| F-533 | tests/Feature/UnifiedErrorPageTest.php | Test | App | Error page coverage | — | INSPECTED |
| F-534 | tests/TestCase.php | Test base | App | Base test case (tenant harness?) | phpunit | INSPECTED |
| F-535 | tests/Unit/ExampleTest.php | Test | App | Scaffold unit test | phpunit | INSPECTED |
| F-536 | tsconfig.json | TS config | App | TypeScript compiler paths | typescript | INSPECTED |
| F-537 | vite-module-loader.js | Build script | App | Module asset resolution for Vite | vite | INSPECTED |
| F-538 | vite.config.js | Build config | App | Root Vite config | vite, laravel-vite-plugin | INSPECTED |

---

## 5. PHASE-0 COVERAGE PROOF

Counted via `git ls-files` (538 lines, machine-verified) and mapped 1:1 to IDs F-001–F-538:

| Area | Files | ID range |
|---|---|---|
| .agents/ AI tooling | 37 | F-001–F-037 |
| Root meta/governance docs | 9 | F-038–F-046 |
| Modules/Access | 55 | F-047–F-101 |
| Modules/Core | 83 | F-102–F-184 |
| Modules/Landlord | 62 | F-185–F-246 |
| Modules/Settings | 37 | F-247–F-283 |
| Modules/Subscription | 45 | F-284–F-328 |
| Modules/Tenant | 33 | F-329–F-361 |
| Root app kernel (app, bootstrap, config, database, resources, routes, public, storage, stubs, tests, manifests) | 177 | F-362–F-538 |
| **TOTAL** | **538** | F-001–F-538 ✓ |

Cross-checks: `.gitkeep` count = 66 (all inventoried as PENDING placeholders). Stub count = 82 (F-441–F-522 ✓). Migrations = 12 root (6 landlord + 6 tenant) + 6 module (3 Landlord, 1 Settings, 2 Subscription) = 18 total.

## 6. INSPECTION LEDGER — PHASE 1 PARTITIONING

Partitioning rules applied: units are bounded by module × layer so each is independently provable; every F-ID appears in exactly one unit; ordering follows dependency direction (governance → manifests → config → kernel → entrypoints → schema → root frontend → Core → Landlord → Access → Settings → Subscription → Tenant → tests → stubs → tooling).

```
UNIT ID | PATH | FILES (F-ID range) | COUNT | START | END | STATUS
```

| UNIT | PATH / SCOPE | FILES | COUNT | STATUS |
|---|---|---|---|---|
| UNIT-001 | Root governance & tooling meta (`.editorconfig`, `.env.example`, `.git*`, `.npmrc`, `.styleci.yml`, `AGENTS.md`, `CHANGELOG.md`, `CLAUDE.md`) | F-038–F-046 | 9 | CLOSED |
| UNIT-002 | Root manifests + bootstrap + build (`README`, `artisan`, `boost.json`, `bootstrap/*`, `composer.*`, `env.d.ts`, `modules_statuses.json`, `package.json`, `phpunit.xml`, `tsconfig.json`, `vite*.js`) | F-362, F-376–F-382, F-413–F-416, F-536–F-538 | 15 | CLOSED |
| UNIT-003 | `config/` — all 14 framework+package configs | F-383–F-396 | 14 | CLOSED |
| UNIT-004 | `app/` kernel — exception, base controller, 6 middleware, 2 models, provider, ErrorPageRenderer, SaaSTenantFinder | F-363–F-375 | 13 | CLOSED |
| UNIT-005 | Entry surface — `routes/web.php`, `routes/console.php`, `public/index.php`, `.htaccess`, `robots.txt`, `favicon.ico` | F-417–F-420, F-429–F-430 | 6 | CLOSED |
| UNIT-006 | `database/` root — .gitignore, UserFactory, 6 landlord + 6 tenant migrations, 2 seeders | F-397–F-412 | 16 | CLOSED |
| UNIT-007 | `resources/` root — app.css, app.js, 4 lang files, app.blade.php, theme-init partial | F-421–F-428 | 8 | CLOSED |
| UNIT-008 | `storage/**` VCS markers (10× `.gitignore`) | F-431–F-440 | 10 | CLOSED |
| UNIT-009 | `.agents/` part A — mcp_config + deploying-to-cloud + infer-conventions + laravel-best-practices skill & rules | F-001–F-025 | 25 | CLOSED |
| UNIT-010 | `.agents/` part B — multitenancy + tailwind + testing-best-practices skills & rules | F-026–F-037 | 12 | CLOSED |
| UNIT-011 | Access backend A — `app/Console`, `app/Http/Controllers`, `app/Models` | F-047–F-057 | 11 | CLOSED |
| UNIT-012 | Access backend B — `app/Providers`, `app/Services`, `app/Support` | F-058–F-069 | 12 | CLOSED |
| UNIT-013 | Access data/routes/lang — `config/`, `database/`, `routes/`, `lang/`, `views/` marker | F-071–F-080, F-095–F-098 | 14 | CLOSED |
| UNIT-014 | Access frontend — `resources/js/Pages` (9) + `resources/assets` (3) | F-083–F-094 | 12 | CLOSED |
| UNIT-015 | Access meta — `composer.json`, `module.json`, `package.json`, `vite.config.js`, tests markers | F-070, F-081–F-082, F-099–F-101 | 6 | CLOSED |
| UNIT-016 | Core domain — `app/Contracts` (5), `app/Enums` (7), `app/Events` (6) | F-102–F-119 | 18 | CLOSED |
| UNIT-017 | Core infra — `app/Http`, `app/Providers`, `app/Tasks` | F-120–F-128 | 9 | CLOSED |
| UNIT-018 | Core data/routes/lang — `config/`, `database/`, `routes/`, `lang/`, `views/` marker | F-130–F-137, F-178–F-181 | 12 | CLOSED |
| UNIT-019 | Core UI components A — BadgeCell → Modal (14 shared components) | F-143–F-156 | 14 | CLOSED |
| UNIT-020 | Core UI components B — PageHeader → ThemeSwitcher (9 shared components) | F-157–F-165 | 9 | CLOSED |
| UNIT-021 | Core frontend infra — Composables (2), Stores (2), Layouts (3), Pages (5), assets (3) | F-140–F-142, F-166–F-177 | 15 | CLOSED |
| UNIT-022 | Core meta — `composer.json`, `module.json`, `package.json`, `vite.config.js`, tests markers | F-129, F-138–F-139, F-182–F-184 | 6 | CLOSED |
| UNIT-023 | Landlord `app/` — Console, 9 Controllers, 3 Models, 3 Providers, 3 Services | F-185–F-205 | 21 | CLOSED |
| UNIT-024 | Landlord data/routes/lang — `config/`, `database/` (3 migrations + seeder), `routes/`, `lang/` | F-207–F-219, F-241–F-243 | 16 | CLOSED |
| UNIT-025 | Landlord frontend — `resources/js/Pages` (15 pages) | F-225–F-239 | 15 | CLOSED |
| UNIT-026 | Landlord meta/assets — `composer.json`, `module.json`, `package.json`, `vite.config.js`, assets, views+tests markers | F-206, F-220–F-224, F-240, F-244–F-246 | 10 | CLOSED |
| UNIT-027 | Settings `app/` + `config/` — controllers, request, models+concern, providers, SettingService, `settings.php` registry | F-247–F-258, F-260–F-262 | 15 | CLOSED |
| UNIT-028 | Settings data/routes/lang/frontend — migration+seeder, routes, lang, 2 pages, assets, views marker | F-263–F-269, F-272–F-280 | 16 | CLOSED |
| UNIT-029 | Settings meta — `composer.json`, `module.json`, `package.json`, `vite.config.js`, tests markers | F-259, F-270–F-271, F-281–F-283 | 6 | CLOSED |
| UNIT-030 | Subscription `app/` — controllers, models, providers, QuotaService, SubscriptionService | F-284–F-294 | 11 | CLOSED |
| UNIT-031 | Subscription data/routes/lang — `config/`, 2 migrations, seeders, `routes/`, `lang/` | F-296–F-308, F-322–F-325 | 17 | CLOSED |
| UNIT-032 | Subscription frontend — PlanFormFields + 7 pages + assets | F-311–F-321 | 11 | CLOSED |
| UNIT-033 | Subscription meta — `composer.json`, `module.json`, `package.json`, `vite.config.js`, tests markers | F-295, F-309–F-310, F-326–F-328 | 6 | CLOSED |
| UNIT-034 | Tenant `app/` — 2 controllers, 3 providers | F-329–F-335 | 7 | CLOSED |
| UNIT-035 | Tenant data/routes/lang — `config/`, seeder, `routes/`, `lang/` | F-337–F-344, F-356–F-358 | 11 | CLOSED |
| UNIT-036 | Tenant frontend — 5 pages + assets + views marker | F-347–F-355 | 9 | CLOSED |
| UNIT-037 | Tenant meta — `composer.json`, `module.json`, `package.json`, `vite.config.js`, tests markers | F-336, F-345–F-346, F-359–F-361 | 6 | CLOSED |
| UNIT-038 | Root test suite — TestCase + 10 feature + 1 unit test | F-523–F-535 | 13 | CLOSED |
| UNIT-039 | Stubs A — action → inertia/app-* (generator templates) | F-441–F-468 | 28 | CLOSED |
| UNIT-040 | Stubs B — inertia components/pages → observer | F-469–F-495 | 27 | CLOSED |
| UNIT-041 | Stubs C — package → vite (generator templates) | F-496–F-522 | 27 | CLOSED |

**Coverage check:** 9+15+14+13+6+16+8+10+25+12 = 128 (UNIT-001–010) + Access 11+12+14+12+6 = 55 + Core 18+9+12+14+9+15+6 = 83 + Landlord 21+16+15+10 = 62 + Settings 15+16+6 = 37 + Subscription 11+17+11+6 = 45 + Tenant 7+11+9+6 = 33 + tests 13 + stubs 28+27+27 = 82 → **538 / 538 = 100%.** No F-ID assigned twice; no F-ID unassigned. Verified by re-counting each module's ID range against unit assignments.

## 7. OPEN QUESTIONS (carried into Phase 1+)

| # | Question | Phase to resolve |
|---|---|---|
| OQ-1 | RESOLVED — runtime is PHP 8.4.23 (verified `php -v`); composer floor is `^8.3`. Residual drift recorded as FND-001. | closed |
| OQ-2 | RESOLVED (partially) — `tenant_model = App\Models\Tenant` (canonical, config/multitenancy.php:80); `App\Models\Tenant` extends `Modules\Landlord\Models\Tenant` (alias subclass, not duplicate). Residual: parent class still independently referenced → FND-027, verify consumers in UNIT-023. | closed → FND-027 |
| OQ-3 | RESOLVED — auth map confirmed: `web`→`users`→`App\Models\User` (extends `Modules\Access\Models\User`, tenant conn), `landlord`→`landlord_users`→`Modules\Landlord\Models\LandlordUser`. `app/Models/User` is an alias subclass — same pattern as Tenant. | closed |
| OQ-4 | RESOLVED — file is gitignored (`.gitignore` lists `/public/fonts-manifest.dev.json`); generated by laravel-vite-plugin font preload during `npm run dev`. Working-tree artifact, not repo pollution. | closed |
| OQ-5 | RESOLVED — confirmed absent on disk AND untracked; `.npmrc` has no `package-lock=false`, so absence is accidental. Promoted to FND-002. | closed → FND-002 |
| OQ-6 | `.env` parity vs `.env.example` — compare KEY NAMES ONLY (values never read). | 4 |
| OQ-7 | `modules_statuses.json` (all true) vs `config/modules.php` activation/`phpunit.xml` — is module toggling actually honored or dead config? ModuleManagementController toggles what exactly? | 2 |
| OQ-8 | RESOLVED — `.styleci.yml` configures the external StyleCI SaaS; no in-repo evidence it is hooked up. Dead/conflicting config → FND-003. | closed → FND-003 |
| OQ-9 | 66 `.gitkeep` + 82 stubs + 6 scaffold `resources/assets` pairs + 6 module `vite.config.js` — how much is live vs scaffold debt? Every module `composer.json`/`package.json` has empty nwidart metadata. | 2–3 |
| OQ-10 | RESOLVED — `wikimedia/composer-merge-plugin` present in composer.lock:6974 (installed transitively via nwidart/laravel-modules); merge of `Modules/*/composer.json` is functional. | closed |

## 8. FILE INSPECTION RECORDS (Phase 2)

Field semantics: `n/a` = dimension does not apply to this artifact type; `clean` = inspected, no finding.

### UNIT-001 — Root governance & tooling meta — CLOSED (9/9)

**[FILE] F-038 `.editorconfig`**
Responsibilities: editor formatting contract (utf-8, LF, 4-space, final newline; md/yaml/compose exceptions). Dependencies: —. Consumers: editors/IDEs. Security/Authorization/Tenant/DataIntegrity: n/a. Performance: n/a. Architecture/SOLID/HMVC/CleanArch: n/a. Clean Code: clean — matches upstream skeleton. Hardcoding: n/a. Duplication: none. Dead Code: none. SSoT: formatting authority shared with Pint (acceptable — different scopes). Tests: n/a. Issues: No finding identified after inspection. Evidence: file read in full.

**[FILE] F-039 `.env.example`**
Responsibilities: env-var contract / onboarding template. Dependencies: consumed by `config/*.php` via `env()`. Consumers: every config file. Security: no secrets present (good); `APP_KEY=` empty (correct for template); `APP_DEBUG=true` is skeleton default — must be overridden per-env (verify Phase 4). Tenant Isolation: introduces custom keys `LANDLORD_DOMAIN` / `TENANT_DOMAIN_SUFFIX` — consumption must be verified in UNIT-003 (`config/multitenancy.php`, `config/app.php`) and UNIT-004 (`SaaSTenantFinder`); `SESSION_DOMAIN=null` → host-only session cookie (positive isolation signal; `SESSION_DRIVER=database` + `SESSION_LIFETIME=120` noted for UNIT-003 session table verification). Data Integrity: `DB_CONNECTION=sqlite` default; landlord/tenant connection env keys absent here — pending UNIT-003. Performance/Arch/SOLID/HMVC: n/a. Clean Code: clean. Hardcoding: values are defaults, fine for a template. Duplication: none. Dead Code: commented MySQL DB_* block retained — acceptable skeleton practice. SSoT: THE env contract; flagged risk = key drift vs `config/` (UNIT-003). Tests: n/a. Issues: links FND-006 (drift watch). Evidence: file read in full (67 lines).

**[FILE] F-040 `.gitattributes`**
Responsibilities: git text/diff/export policy. Dependencies: —. Consumers: git, `git archive`, package exports. Security: n/a. Issues: (a) `/.github export-ignore` references a directory that does not exist (stale line); (b) `.agents/`, `stubs/`, `docs/` not export-ignored — relevant only if ever distributed as an archive. LOW. All other fields: n/a / clean. Evidence: file read; directory listing confirms no `.github/`.

**[FILE] F-041 `.gitignore`**
Responsibilities: repo exclusion policy. Dependencies: —. Consumers: git. Security: secrets correctly excluded (`.env`, `.env.backup`, `.env.production`, `/auth.json`, `/storage/*.key`); build artifacts excluded (`/public/build`, `/public/hot`, `/public/fonts-manifest.dev.json`, `/public/storage`); IDE dirs excluded. Tenant/DataIntegrity/Perf/Arch: n/a. Dead Code: none. Duplication: none. Issues: (a) no `package-lock`/`pnpm-lock`/`yarn.lock` mention — consistent with the *absent* lockfile, see FND-002; (b) `.notebook/` absent but self-ignores via nested `.notebook/tmp/.gitignore` (`/*`) — verified on disk. No finding beyond linkage. Evidence: file read; `.notebook/tmp/.gitignore` contents verified.

**[FILE] F-042 `.npmrc`**
Responsibilities: npm client policy. Dependencies: —. Consumers: npm. Security: `ignore-scripts=true` — blocks postinstall payload execution (positive supply-chain control); `audit=true`. Issues: no `package-lock=false` — so the missing lockfile (FND-002) is accidental, not policy. All other fields: n/a / clean. Evidence: file read.

**[FILE] F-043 `.styleci.yml`**
Responsibilities: StyleCI SaaS formatting rules (php: laravel preset, `no_unused_imports` disabled; js+css enabled). Dependencies: external StyleCI service. Consumers: StyleCI webhook (if configured). Clean Code: `disabled: no_unused_imports` contradicts Pint's laravel preset (removes unused imports) — two style authorities disagree. Dead Code: no CI config exists in-repo (`.github/` absent) — StyleCI hookup unverifiable; candidate dead config. SSoT: violates single-authority principle for code style alongside Pint mandate in AGENTS.md. Issues: FND-003. Evidence: file read; `Test-Path pint.json` = false; `.github` absent.

**[FILE] F-044 `AGENTS.md`**
Responsibilities: THE governance baseline — quality rules, no-resolver rule, UI component mandate table (§6.1), review gate, boost/pint/phpunit rules. Dependencies: laravel/boost generated + hand-maintained sections. Consumers: all agents/devs; this audit's rubric for Phase 6. Security: n/a itself; its rules become testable assertions. Architecture: defines invariants (`useThemeStore` SSoT, `setupInertiaStateBridge` bridge) to verify in UNIT-021. Hardcoding rule (§Application Quality) will be enforced across Phase 2. Issues: states "running on PHP 8.4" while composer floor is `^8.3` → FND-001 (residual: floor vs doc mismatch). Otherwise clean. Evidence: file read in full (301 lines).

**[FILE] F-045 `CHANGELOG.md`**
Responsibilities: release log — actual content is verbatim upstream `laravel/laravel` skeleton changelog (v12.0.0→v13.10.0). Dependencies: —. Consumers: humans. Dead Code: entire file is skeleton residue — tracks the framework template's releases, not this project's; misleading for a product repo. Issues: FND-004. All other fields: n/a. Evidence: file read in full.

**[FILE] F-046 `CLAUDE.md`**
Responsibilities: intended agent-rules file — actual size 0 bytes, completely empty. Dead Code: dead file. Issues: FND-004. Evidence: file read (empty); `Get-Item` confirms 0 bytes.

### UNIT-002 — Root manifests, bootstrap & build — CLOSED (15/15)

**[FILE] F-362 `README.md`**
Responsibilities: architecture reference + bootstrap runbook (multi-DB tenancy, commands, troubleshooting). Dependencies: documents config/multitenancy.php, TenantSeeder, db:rebuild, access:sync-* commands. Consumers: developers/agents. Security: documents `TenantSeeder` creating default landlord admin — credential handling verified in UNIT-006. Architecture: explicitly names `App\Models\Tenant` canonical `tenant_model` and warns `Modules\Landlord\Models\Tenant` breaks `Tenant::current()` mid-switch — evidence for OQ-2. Issues: (a) Mermaid + prose reference `DomainTenantFinder` while repo ships custom `SaaSTenantFinder` — doc drift, FND-012; (b) recommends `DB_CONNECTION=landlord` (line 71) while `.env.example` ships `sqlite` — FND-007; (c) claims `app.blade.php` contains a "Tenant Switcher" — verify UNIT-007. Evidence: file read in full (403 lines).

**[FILE] F-376 `artisan`**
Responsibilities: CLI entrypoint. Standard skeleton: autoload → bootstrap/app → handleCommand. All forensic fields: n/a or clean. Issues: No finding identified after inspection. Evidence: file read (18 lines).

**[FILE] F-377 `boost.json`**
Responsibilities: Laravel Boost config (mcp, guidelines, skills list, agent=antigravity, package=multitenancy). Dependencies: laravel/boost v2.10.0 (lockfile). Consumers: Boost MCP server. Issues: No finding identified after inspection. Evidence: file read.

**[FILE] F-378 `bootstrap/app.php`**
Responsibilities: middleware wiring, exception→ErrorPageRenderer plumbing, health route `/up`. Dependencies: 6 app middleware, spatie NeedsTenant/EnsureValidTenantSession, permission middleware aliases. Consumers: every HTTP request. Security/Authz: `tenant` group = NeedsTenant+EnsureValidTenantSession; aliases `landlord`/`landlord.active`/role/permission/role_or_permission — route-side usage verified in later units. Tenant Isolation: `IdentifyTenant`+`EnsureTenantIsActive` PREPENDED to web+api (correct order — resolution before everything); must tolerate no-tenant on landlord domain (verify UNIT-004). `EnsureTenantIsActive` runs pre-auth globally — suspended tenants blocked early (positive). Performance: middleware on every request incl. `/up` — acceptable. Issues: `respond()` pipes ALL responses through `ErrorPageRenderer` — must verify it passes through non-error responses (UNIT-004). Evidence: file read (84 lines).

**[FILE] F-379 `bootstrap/cache/.gitignore`**
Responsibilities: exclude framework caches (`*`, `!.gitignore`). Clean. Issues: none. Evidence: file read.

**[FILE] F-380 `bootstrap/providers.php`**
Responsibilities: app provider list — only `AppServiceProvider`; module providers via nwidart module.json scanning. Clean. Issues: none. Evidence: file read.

**[FILE] F-381 `composer.json`**
Responsibilities: PHP dependency + autoload + scripts manifest. Dependencies: php ^8.3, laravel ^13.17, inertia-laravel 3.4, nwidart-modules ^13, medialibrary 11.23, multitenancy ^4.2, permission 8.3, translatable 6.14. Issues: (a) PHP floor `^8.3` vs documented 8.4 → FND-001; (b) `pestphp/pest-plugin` in `allow-plugins` but Pest not installed → stale, FND-008; (c) redundant dual PSR-4: broad `"Modules\\": "Modules/"` + per-module `app/` maps — longest-prefix wins so it works, but broad map can resolve unintended `Modules\*` classes outside `app/` → FND-009; (d) merge-plugin `include` for module composer.json — functional (lockfile confirms v2.1.0). Evidence: file read (106 lines).

**[FILE] F-382 `composer.lock`**
Responsibilities: pinned PHP dep tree (87 prod + 35 dev packages). Key pins: laravel/framework v13.34.0, multitenancy 4.2.1, permission 8.3.0, medialibrary 11.23.0, inertia-laravel v3.4.0, modules v13.0.0, phpunit 12.5.37, merge-plugin v2.1.0. Security: `composer audit` → **0 advisories** (verified). Notable absences: no sanctum/passport/fortify/breeze → auth is custom-built (higher audit burden on UNIT-011/Landlord auth). Issues: none as artifact. Evidence: lockfile parsed via ConvertFrom-Json; `composer audit` clean.

**[FILE] F-413 `env.d.ts`**
Responsibilities: Inertia shared-props contract (auth.user+roles+permissions, tenant, branding, system, billing.currency, locale, theme, flash) + `*.vue` shim. Consumers: all Vue/TS. Type Integrity: `[key: string]: unknown` index signatures on every interface + `any` in vue shim + `status?: string` (not enum union despite TenantStatus enum existing) — weakens the contract vs AGENTS.md §3 type-safety rules → FND-011. Issues: FND-011. Evidence: file read (110 lines).

**[FILE] F-414 `modules_statuses.json`**
Responsibilities: module enable flags — all 6 true. Consumers: nwidart activator + `vite-module-loader.js` (dead — FND-005) + possibly ModuleManagementController (verify UNIT-023). Issues: none yet. Evidence: file read.

**[FILE] F-415 `package.json`**
Responsibilities: npm manifest. Dependencies: vue ^3.5, @inertiajs/vue3 `^2.0.0 || ^3.0.0`, pinia `^2||^3||^4`, typescript `^5||^7`, vite ^8, tailwind ^4, lucide-vue-next ^1. Issues: (a) multi-major floating ranges + no lockfile → builds unreproducible (FND-002 root); (b) `concurrently` devDep appears unused — `composer dev` runs `php artisan dev` (pao), scripts only define vite → dead dep, FND-013; (c) `@laravel/multiplex` optionalDep — usage unverified (Phase 3 check). Evidence: file read.

**[FILE] F-416 `phpunit.xml`**
Responsibilities: test suites + test env. Issues: (a) `DB_CONNECTION=landlord` + `DB_DATABASE=multivendor` + mysql — **tests target the same DB name the README uses for dev landlord**; no `_testing` isolation → destructive-test risk on dev data → FND-006; (b) testsuites cover only `tests/` — `Modules/*/tests` excluded (all empty anyway) → if module tests are ever added they silently won't run → noted; (c) stale envs: PULSE/TELESCOPE/NIGHTWATCH_ENABLED for uninstalled packages — harmless. Good: array cache/session, sync queue, BCRYPT_ROUNDS=4. Evidence: file read (41 lines).

**[FILE] F-536 `tsconfig.json`**
Responsibilities: TS compiler config — strict:true, bundler resolution, aliases `@`→resources/js, `@core`→Core resources. Issues: `allowJs:true` mixed-mode (fine); no per-module aliases (only @core) — modules import via relative paths (verify consistency Phase 8). Clean. Evidence: file read.

**[FILE] F-537 `vite-module-loader.js`**
Responsibilities: intended dynamic import of per-module `vite.config.js` `paths` for laravel input. Dead Code: **zero consumers** — grep finds no import of `collectModuleAssetsPaths`; also uses `__dirname` in an ESM package (`"type":"module"`) → would throw `ReferenceError` if ever invoked → doubly dead/broken → FND-005. Evidence: grep zero-callers; `"type":"module"` in package.json.

**[FILE] F-538 `vite.config.js`**
Responsibilities: root Vite config — inputs `app.css`+`app.js`, tailwind v4 plugin, vue plugin, `bunny('Instrument Sans')` font preload (explains fonts-manifest.dev.json), aliases @/@core. Performance: no manualChunks/lazy-splitting — single-bundle strategy (note for Phase 7). Issues: none structural; module pages loaded via Inertia glob (verify app.js UNIT-007). Evidence: file read.

### UNIT-003 — `config/` — CLOSED (14/14)

**[FILE] F-383 `config/app.php`**
Responsibilities: app name/env/debug/url/timezone/locale/cipher/key/maintenance. Issues: No finding identified after inspection (stock skeleton; `env` default 'production' correct; `previous_keys` supported). Evidence: file read.

**[FILE] F-384 `config/auth.php`**
Responsibilities: guards/providers/brokers. Facts: `web` guard → `users` provider → `App\Models\User` (tenant user, env-overridable via AUTH_MODEL); `landlord` guard → `landlord_users` → `Modules\Landlord\Models\LandlordUser`. **No `landlord_users` password broker** — landlord password-reset flow unwired (pending: does any reset UI exist? UNIT-023 → OQ-12). Both guards share the same session cookie; separation relies on per-host cookies + guard-specific session keys + EnsureValidTenantSession. Issues: FND-017. Evidence: file read.

**[FILE] F-385 `config/cache.php`**
Responsibilities: cache stores/prefix. Facts: default=`database` store, `DB_CACHE_CONNECTION` null → **default DB connection**; static `prefix`=`{app}-cache-`. Tenant Isolation: **no per-tenant cache isolation at this layer** — `PrefixCacheTask` commented out in multitenancy.php; every `cache()` call in tenant context writes to the landlord DB `cache` table in a shared keyspace. Only possible mitigation = `ScopePermissionCacheTask` (UNIT-017 pending). `serializable_classes=false` — good (gadget-chain hardening). Issues: FND-015. Evidence: file read.

**[FILE] F-386 `config/database.php`**
Responsibilities: `default`+`landlord`+`tenant`+stock connections. Facts: landlord & tenant conns support `DB_LANDLORD_DRIVER`/`DB_TENANT_DRIVER` sqlite override branches (undocumented env keys → FND-016); landlord fallback db `'laravel'` (README expects `multivendor` — inconsistency); tenant `database`='' default correct for runtime switching. Issues: (a) `use Pdo\Mysql;` + `Mysql::ATTR_SSL_CA` — **PHP 8.4-only class** → composer floor `^8.3` factually broken (install on PHP 8.3 = fatal) → FND-001 upgraded to MEDIUM; (b) FND-016 env drift. Evidence: file read; PHP version history (Pdo\Mysql added in 8.4).

**[FILE] F-387 `config/filesystems.php`**
Responsibilities: disks. Facts: local(private, serve=true), public, s3. Tenant Isolation: **no tenant-scoped disk/prefix** — all tenant media share `storage/app/public` with ID-keyed paths → cross-tenant enumeration vector (FND-018). Evidence: file read.

**[FILE] F-388 `config/logging.php`**
Stock channels; `replace_placeholders=true` (secret scrubbing good). Issues: none. Evidence: file read.

**[FILE] F-389 `config/mail.php`**
Stock. Issues: none. Evidence: file read.

**[FILE] F-390 `config/media-library.php`**
Responsibilities: medialibrary config. Issues: (a) `disk_name`='public' + `prefix`='' → media stored `storage/app/public/{id}/` served at `/storage/` — sequential IDs, no tenant scoping → cross-tenant enumeration (FND-018); (b) imports `Spatie\MediaLibraryPro\Models\TemporaryUpload` — **Pro package not installed** (lockfile) — class-string is inert but Pro-only keys (`temporary_upload_model`, `*_temporary_uploads*`, `enable_vapor_uploads`) are dead config (FND-018 sibling); (c) conversions queue on default conn → jobs become tenant-aware by default (works). Evidence: file read (363 lines).

**[FILE] F-391 `config/modules.php`**
Responsibilities: nwidart config. Facts: FileActivator → `modules_statuses.json` (global enable/disable — NOT per-tenant; ModuleManagementController semantics pending UNIT-023); `auto-discover.migrations=true` → module migrations register into the migrator's default path list (run under plain `migrate` on default conn; tenant migrate uses explicit `--path` → excluded — verify UNIT-023 RebuildDatabasesCommand). Issues: (a) `stubs.enabled=false` + `stubs.path`→vendor dir → repo's 82 `stubs/nwidart-stubs/*` files are **orphaned dead scaffolding** (FND-020); (b) `paths.assets=public/modules` unused; (c) module composer vendor/author left at 'nwidart'/'Nicolas Widart' defaults (scaffold debt). Evidence: file read (332 lines).

**[FILE] F-392 `config/multitenancy.php`**
Responsibilities: THE tenancy config. Facts: finder=`SaaSTenantFinder`; `landlord_domains` = LANDLORD_DOMAIN list + APP_URL host + loopback hosts in non-production (clean env-aware split); `tenant_domain_suffix` env-driven; tasks=[SwitchTenantDatabaseTask, **ScopePermissionCacheTask**] — PrefixCacheTask/SwitchRouteCacheTask commented out; `queues_are_tenant_aware_by_default=true`; `tenant_model=App\Models\Tenant` (**canonical confirmed** → Modules\Landlord\Models\Tenant is a duplicate/legacy suspect — OQ-2 nearly resolved); `tenant_artisan_search_fields=['id']` only (README claims id/slug/domain — FND-019). Issues: FND-015 (cache prefix gap), FND-019. Evidence: file read (165 lines).

**[FILE] F-393 `config/permission.php`**
Responsibilities: spatie/permission config. Facts: `models.permission`/`role` = `Modules\Access\Models\*` — **one model pair for BOTH landlord and tenant contexts**; teams off; wildcard off; `display_*_in_exception=false` (good); cache key static `spatie.permission.cache`, store=default (→ database → landlord conn → shared keyspace — presumably remapped by ScopePermissionCacheTask, pending UNIT-017). Issues: FND-021 — dual-context RBAC on a single model pair is a cross-context leakage/confusion risk until connection behavior is verified in UNIT-011/012. Evidence: file read.

**[FILE] F-394 `config/queue.php`**
Responsibilities: queue conns. Facts: default=`database`, connection null → **landlord DB `jobs` table** for all queued work (tenant jobs table in tenant migrations is dead schema — FND-022); tenant-aware-by-default restores context via `tenantId` Context (config/multitenancy.php). Issues: FND-022, FND-023 (`batching`/`failed` fall back to `env('DB_CONNECTION','sqlite')` — unset → sqlite tables that don't exist). Evidence: file read.

**[FILE] F-395 `config/services.php`**
Stock (postmark/resend/ses/slack env keys). Issues: none. Evidence: file read.

**[FILE] F-396 `config/session.php`**
Responsibilities: session config — **security-critical in this architecture**. Facts: driver=database, `connection`=null → sessions stored on **default connection = landlord DB** → tenant AND landlord sessions share the central `sessions` table (session binding to tenant relies entirely on `EnsureValidTenantSession`); `domain`=env → null → host-only cookie (per-subdomain isolation — positive); `same_site=lax`, `http_only`, `serialization=json`, `encrypt=false`. Tenant Isolation: centralized session store is a deliberate-looking design (landlord sessions migration exists; tenant migrations lack a sessions table) — recorded for the Phase 5 tenancy map. Issues: none beyond the mapping fact. Evidence: file read (233 lines).

**UNIT-003 cross-file notes:** config↔env drift consolidated as FND-016; tenancy isolation posture = "resolution-time boundary" (finder + NeedsTenant + EnsureValidTenantSession + DB switch + permission-cache scope), while **cache/session/queue/storage remain centralized in landlord** — must be mapped fully in Phase 5.

### UNIT-004 — `app/` kernel — CLOSED (13/13)


**[FILE] F-363 `app/Exceptions/TenantSuspendedException.php`**
Responsibilities: typed exception carrying suspended tenant; `make()` factory. Consumers: EnsureTenantIsActive → ErrorPageRenderer (423). Clean. Issues: none. Evidence: file read.

**[FILE] F-364 `app/Http/Controllers/Controller.php`**
Empty abstract base. Issues: none. Evidence: file read.

**[FILE] F-365 `EnsureLandlordAdminActive`**
Responsibilities: post-auth guard — non-`active` landlord admin is logged out, session invalidated+token regenerated, 403 `__('account_suspended')`. Security: sound pattern (mid-request re-verification); `$user->status ?? 'active'` silently defaults to active when column absent (acceptable but hides schema drift — note). Issues: translation key `account_suspended` existence verify Phase 3. Evidence: file read.

**[FILE] F-366 `EnsureLandlordContext`**
`abort_if(Tenant::checkCurrent(), 404)` — clean boundary for landlord-only routes; relies on IdentifyTenant having run first (guaranteed by prepend order). Issues: none. Evidence: file read.

**[FILE] F-367 `EnsureTenantIsActive`**
Blocks resolved-but-inactive tenants via `TenantSuspendedException`. Runs on every request (prepended); no-ops when no tenant. Issues: none. Evidence: file read.

**[FILE] F-368 `HandleInertiaRequests`**
Responsibilities: shared props contract (auth/tenant/branding/system/billing/locale/theme/flash) matching env.d.ts. Security: on tenant context, landlord guard deliberately skipped → no cross-guard auth leakage into tenant pages (good); on landlord context falls through to `web` guard — a `web`-session on a landlord host would surface as tenant user with `is_landlord:false` (depends on whether web login is reachable on landlord host — UNIT-005). Issues: (a) fabricates `roles:['Super Admin']`/`['Member']` + permissions `[]` when models lack HasRoles — hardcoded role names + cosmetic elevation (FND-024); (b) `load($locale,'*','*')` serializes the entire translation catalog into every Inertia response (FND-025 perf); (c) `settings` service queried per-request — caching depends on SettingService (UNIT-027); (d) `tenant.plan` lazy landlord query per request (acceptable); (e) hardcoded `cairo`/`inter` font names (minor token concern). Evidence: file read (137 lines).

**[FILE] F-369 `IdentifyTenant`**
Responsibilities: request admission — landlord hosts → `forgetCurrent()`+pass; else SaaSTenantFinder → `makeCurrent()`; unknown host → `NoCurrentTenant` (fail closed — correct). Security: host admission is the root isolation boundary; lowercase host, strict in_array, no wildcard trust. Issues: none. Evidence: file read.

**[FILE] F-370 `SetLocale`**
Responsibilities: locale resolution — `?locale=` → session → Accept-Language → settings default, validated against `SettingManagerContract::supportedLocales()` (strict in_array). Dependencies: **SettingManagerContract container binding required on every request** — if unbound → fatal on all routes (verify binding UNIT-016/027). Issues: writes `session(['locale'])` unconditionally → a DB-session write even when unchanged (LOW perf note); `$request->get()` reads POST too (intended? minor). Evidence: file read.

**[FILE] F-371 `app/Models/Tenant.php`**
Alias subclass of `Modules\Landlord\Models\Tenant` — canonical via `tenant_model` config. Issues: parent/child same-table dual-model landmine (FND-027). Evidence: file read.

**[FILE] F-372 `app/Models/User.php`**
Alias subclass of `Modules\Access\Models\User` (tenant user). Issues: same alias pattern — consistent; real logic audited in UNIT-011. Evidence: file read.

**[FILE] F-373 `app/Providers/AppServiceProvider.php`**
Facts: `register()` sets `permission.cache.key = ScopePermissionCacheTask::BASE_KEY.'.landlord'` — **permission cache isolation is key-scoped, not store-scoped** (confirms FND-021 mechanism; store still shared landlord `cache` table — collision impossible, centralization remains); `boot()` registers `database/migrations/landlord` into default migration paths (plain `migrate` targets default conn — FND-007 interplay). Issues: none new. Evidence: file read.

**[FILE] F-374 `ErrorPageRenderer`**
Responsibilities: unified Inertia error page for HTML ≥400; JSON/api bypass; `match` maps NoCurrentTenant→404, TenantSuspended→423 (calls `forgetCurrent()` first — renders suspension page in landlord context since tenant DB may be unreachable — sound); rebuilds locale props when middleware didn't run (route misses); `exception` prop gated by `app.debug`. Issues: none — fail-safe catch-all present. Evidence: file read (123 lines).

**[FILE] F-375 `SaaSTenantFinder`**
Responsibilities: pure host→tenant resolution — exact `domain` match, else `{slug}.{tenant_domain_suffix}` split on first dot. Security: no wildcard beyond first label (deep subdomain `a.b.suffix` → `explode()[0]`='a' — slug lookup for 'a' — an attacker-controlled `*.suffix` Host could resolve a different tenant's slug? No — host must END with `.{suffix}` and first label must equal the slug exactly → `evil.tenant1.suffix` looks up slug 'evil' → no match → null → 404. Safe). Issues: none. Evidence: file read.

### UNIT-005 — Entry surface (root routes + public entry) — CLOSED (6/6)

**[FILE] F-429 `routes/web.php`**
Responsibilities: intentionally empty — comment documents modular routing (all web routes registered by module RouteServiceProviders). Issues: none — consistent with HMVC layout. Evidence: file read.

**[FILE] F-430 `routes/console.php`**
Responsibilities: stock `inspire` command only. Facts: **no scheduler entries exist anywhere** (bootstrap/app.php has no `withSchedule`) — zero scheduled tasks; if recurring work (quota resets, subscription renewals, session cleanup) is expected, nothing schedules it → Phase 6/10 note. Issues: none structural. Evidence: file read.

**[FILE] F-419 `public/index.php`**
Stock front controller (maintenance.php check → autoload → bootstrap → handleRequest). Issues: none. Evidence: file read.

**[FILE] F-417 `public/.htaccess`**
Stock Apache config (MultiViews/-Indexes off, Authorization + X-XSRF-Token passthrough, front-controller rewrite). Issues: none. Evidence: file read.

**[FILE] F-420 `public/robots.txt`**
`Disallow:` empty — everything crawlable incl. `/landlord/*` admin paths. Issues: INFO note (consider disallowing admin/auth paths; minor SEO/hardening hygiene). Evidence: file read.

**[FILE] F-418 `public/favicon.ico`**
**0 bytes** — empty binary committed as favicon → browsers receive an empty/corrupt icon. Issues: FND-028 (dead/broken asset). Evidence: `Get-Item` Length=0.

### UNIT-006 — `database/` root — CLOSED (16/16)

**[FILE] F-397 `database/.gitignore`**
`*.sqlite*` — clean. Issues: none.

**[FILE] F-398 `database/factories/UserFactory.php`**
Responsibilities: factory for `App\Models\User` (tenant user — tests need tenant context; TestCase handles? UNIT-038). Standard skeleton + Hash memoization. Issues: none. Evidence: file read.

**[FILE] F-399–F-404 landlord migrations** (sessions, cache, jobs, tenants, permission, media)
Facts: all guard `if (Tenant::checkCurrent()) return;` — prevents accidental execution inside tenant context (positive). `tenants` table: id/name/domain(unique)/database(unique)/timestamps — **no slug/status here** (added by module migration `add_saas_fields_to_tenants_table` — UNIT-024). Landlord `roles` table has **`is_system` boolean** — the TENANT permission migration lacks it → schema drift (FND-032). `tenants` + `media` migrations lack `down()` (FND-034). No `password_reset_tokens`/`users` on landlord — but see FND-031. Evidence: all 6 read.

**[FILE] F-405–F-410 tenant migrations** (users+sessions+password_reset_tokens, cache, jobs, permission, media, tenant_settings)
Facts: tenant `users` has custom `status`(default active, indexed)/`job_title`/`phone`; email unique per-tenant-DB. `tenant_settings` uses explicit `Schema::connection('tenant')` + `hasTable` guard + `unique(domain,key)` (note: 'domain' column = settings group, not tenant domain — naming collision, minor). Dead schema: `sessions`, `password_reset_tokens`, `cache`, `jobs` tables are created per-tenant but all resolve to landlord connections (FND-022 extended — sessions+password resets centralized). Permission tables have NO `is_system` (drift from landlord). Evidence: all 6 read.

**[FILE] F-411 `database/seeders/DatabaseSeeder.php`**
Responsibilities: context-aware seeding (landlord: TenantSeeder+LandlordDatabaseSeeder; tenant: baseline provisioner + owner + theme settings). Issues: (a) **`Hash::make('password')` for seeded owner accounts** — predictable credentials (FND-029); (b) creates TWO owner users: `admin@<domain>` AND `admin@<domain>.com` (FND-029/033); (c) `catch (\Throwable) {}` swallows assignRole failures silently (FND-033); (d) hardcoded `'indigo'`/`'dark'` theme defaults — verify vs ThemePalette enum (UNIT-016). Evidence: file read.

**[FILE] F-412 `database/seeders/TenantSeeder.php`**
Responsibilities: demo tenants (tenant1/tenant2.localhost → vendor_1/2) + `CREATE DATABASE IF NOT EXISTS` via landlord conn. Issues: (a) writes `db_username`/`db_password` from env **in plaintext** to tenants rows when columns exist (FND-030); (b) `Tenant::unguarded` mass-assign — acceptable seeder pattern; (c) hardcoded fixture data (acceptable for learning seed but couples seed to localhost topology). Evidence: file read.

### UNIT-007 — `resources/` root — CLOSED (8/8)

**[FILE] F-421 `resources/css/app.css`**
Responsibilities: Tailwind v4 entry + full design-token system — 6 palettes (indigo/emerald/violet/amber/cyan/rose) each = primary+secondary+accent scales, semantic surfaces/text/borders/status tokens, dark-mode scoped neutrals, `@source` scanning Modules, RTL font override. SSoT: clean token architecture (good — tokens are the single source). Issues: none. Evidence: 422 lines read.

**[FILE] F-422 `resources/js/app.js`**
Responsibilities: Inertia/Vue3/Pinia bootstrap, module-aware page resolver (`{Module}/{Page}` → `Modules/{M}/resources/js/Pages/...`), `setupInertiaStateBridge` (architectural invariant ✓). Issues: (a) registers BOTH `/Modules/...` (absolute) and `../../Modules/...` (relative) glob maps — one is an unreachable fallback + doubles build-time glob scan (FND-035); (b) hardcoded `#6366f1` progress color vs design tokens (FND-036). Evidence: file read.

**[FILE] F-423/F-425 `resources/lang/{ar,en}.json`**
52-key generic UI vocab, symmetric en/ar. Kernel error keys (`account_suspended`, `tenant_suspended_*`, `error_tenant_not_found`) are NOT here but DO exist in `Modules/{Access,Core}/lang/*.json` — registered via module providers + merged by `load('*','*')` → resolved, no missing-key bug. Issues: none. Evidence: both read + grep.

**[FILE] F-424/F-426 `resources/lang/{ar,en}/validation.php`**
Abbreviated rule set (~24 keys vs Laravel's ~100) — uncovered rules fall back to framework defaults; `attributes` empty. Issues: LOW note — partial localization coverage (framework fallback keeps it functional). Evidence: both read.

**[FILE] F-427 `resources/views/app.blade.php`**
Responsibilities: Inertia root — SSR'd `lang`/`dir`/`.dark`/`data-theme`/`data-mode` from shared props or getTheme() fallback; Bunny fonts (cairo+inter); title from `branding.app_name`. Issues: (a) README's claimed "Tenant Switcher" does NOT exist here → FND-012 confirmed drift; (b) service call (`getTheme()`) inside blade fallback — acceptable for root template; (c) hardcoded `SaaS Platform` title fallback (minor). Evidence: file read.

**[FILE] F-428 `resources/views/partials/theme-init.blade.php`**
Minimal FOUC script resolving `system` mode pre-paint. Clean. Issues: none. Evidence: file read.

### UNIT-008 — `storage/**` VCS markers — CLOSED (10/10)

**[FILE] F-431–F-440 — storage tree `.gitignore` markers**
All 10 verified by content dump: standard skeleton patterns (`*`+`!.gitignore` / explicit compiled-artifact list in `storage/framework`). `storage/inertia-devtools/.gitignore` contains only `*` (missing `!.gitignore` — file already tracked so harmless; cosmetic inconsistency). `storage/framework` lists generated files individually (Laravel 13 style). Issues: none material. Evidence: `Get-ChildItem -Recurse` content dump of all 10.

### UNIT-009 — `.agents/` part A — CLOSED (25/25)

**[FILE] F-001 `.agents/mcp_config.json`** — registers Laravel Boost MCP (`php artisan boost:mcp`). Consistent with boost.json. Issues: none.

**[FILE] F-002–F-003 `deploying-to-cloud/` (SKILL.md + checklists.md)** — Laravel Cloud deploy guidance. Note: none of this project's config targets Cloud (no cloud deploy files) — advisory only. Issues: none.

**[FILE] F-004–F-005 `infer-conventions/` (SKILL.md + checklist.md)** — convention-detection workflow for agents; writes `.ai/rules` (verified absent earlier). Consistent with AGENTS.md. Issues: none.

**[FILE] F-006 `laravel-best-practices/SKILL.md` + F-007–F-025 its 19 rule files** (advanced-queries, architecture, blade-views, caching, collections, config, db-performance, eloquent, error-handling, events-notifications, http-client, mail, migrations, queue-jobs, routing, scheduling, security, style, validation) — generic Laravel guidance; no contradictions with AGENTS.md. Notable expectations to apply when auditing later units: constructor injection over `app()` (SetLocale/DatabaseSeeder/HandleInertiaRequests already use `app()` — convention note, LOW); `env()` only in config (verify per-unit); `constrained()` FKs; authorization via policies/gates. Issues: none as docs. Evidence: all files content-verified via full dumps.

### UNIT-010 — `.agents/` part B — CLOSED (12/12)

**[FILE] F-026 `laravel-multitenancy-development/SKILL.md`** — Spatie v4 guidance (tasks, finders, TenantAware queues, `execute()`/`Landlord::execute`, per-conn traits). Matches installed v4.2.1 and the project's actual topology — used as audit rubric. Issues: none.

**[FILE] F-027 `tailwindcss-development/SKILL.md`** — Tailwind v4 CSS-first rules (project complies: `@import`, `@theme`, no tailwind.config.js). Issues: none.

**[FILE] F-028 `testing-best-practices/SKILL.md` + F-029–F-037 its 9 rule files** (assertions, endpoint-tests, finding-features, isolation, naming, performance, review, security, test-data) — establishes the rubric for UNIT-038: `LazilyRefreshDatabase`, `Http::preventStrayRequests()`+`Sleep::fake()`+`Exceptions::fake()` in base TestCase, `withoutVite()`, `BCRYPT_ROUNDS=4` (already in phpunit.xml ✓), cross-tenant 404s, per-role matrices. Issues: none as docs. Evidence: all files content-verified.

### UNIT-011 — Access module backend A — CLOSED (11/11)

**[FILE] F-047 `Console/SyncLandlordAccessCommand`** (`access:sync-landlord {--repair}`) — backfills landlord permission catalog/roles; `--repair` promotes earliest ACTIVE LandlordUser to Super Admin when zero management principals remain (audit-logged, never creates accounts/passwords — good least-privilege repair). Issues: none. Evidence: read.

**[FILE] F-048 `Console/SyncTenantAccessCommand`** (`access:sync-tenants {--tenant=*} {--promote-owner=}`) — TenantAware; per-tenant ensureBaseline + optional owner promotion judged on *effective permissions* not role names; graceful skip on unreachable tenant DB (QueryException→warn). Issues: none. Evidence: read.

**[FILE] F-049 `.gitkeep`** — placeholder (Controllers dir non-empty → stale marker, trivial).

**[FILE] F-050 `AccessController`** — **scaffold resource controller**: `index/create/show/edit` render `Access/{Index,Create,Show,Edit}` pages; `store/update/destroy` are EMPTY no-ops (`//` bodies). If routed → reachable dead endpoints violating "disabled functionality must not remain reachable" (verify route registration UNIT-013) → FND-039. Evidence: read.

**[FILE] F-051 `ProfileController`** — self-service profile edit/update; `current_password` gate via `required_with:password`+`current_password` rule; Hash::make on change. Clean. Issues: none.

**[FILE] F-052 `RoleController`** — `index` (roles w/ permission counts + actor-scoped permission picker manifest), `store` (name unique per guard + `assertPermissionsWithinScope` — cannot mint superset roles — strong). Missing update/edit/destroy — roles are immutable post-create via UI (feature gap or intentional — pending routes). Evidence: read.

**[FILE] F-053 `TenantAuthController`** — tenant login/register/logout on `web` guard; session regenerate/invalidate correct. Issues: (a) `register()` never checks `system.allow_registration` — the shared prop is decorative unless TenantUserService enforces it (FND-037, verify UNIT-012); (b) `showRegisterForm`/`register` on non-tenant host redirect to `route('tenant.register.show')` — likely self-redirect loop on landlord host (FND-038, verify route map UNIT-013); (c) **no rate limiting visible at controller level** — pending route `throttle:` check (FND-041 pending). Evidence: read.

**[FILE] F-054 `UserController`** — member directory CRUD w/ quota checks (QuotaManagerContract), grantable-role enforcement (`assertRoleGrantable`/`assertAssignableRole`), self-lockout + management-capacity invariants in transactions — strong authz design. Issues: (a) `'in:active,inactive,suspended'` hardcoded status literals + `'Member'` fallback — enum SSoT check pending; (b) `$user->roles->first()` inside map → N+1 unless `listUsers` eager-loads (verify UNIT-012); (c) `canManage`/`isDeletableBy` per-row policy eval — perf depends on EffectivePermissionSet caching. Evidence: read.

**[FILE] F-055 `Models/Permission`** — dynamic `getConnectionName()`: tenant conn when current else landlord. Confirms FND-021 dual-context mechanism — works but means EVERY permission query silently follows ambient tenant state (note for Phase 5). Evidence: read.

**[FILE] F-056 `Models/Role`** — same dynamic connection + `is_system` boolean cast. **Tenant `roles` table lacks `is_system`** → writing it tenant-side errors; cast on read is harmless (missing attr). Tightens FND-032. Evidence: read.

**[FILE] F-057 `Models/User`** — UsesTenantConnection + HasRoles(`web`) + InteractsWithMedia(avatars) + Notifiable; `status` in `$fillable` (mass-assignable — safe because controllers validate it explicitly, but flags any future `$request->all()` caller — note); Gravatar fallback leaks email MD5 to gravatar.com (INFO privacy note). Evidence: read.

### UNIT-012 — Access module backend B (Providers/Services/Support) — CLOSED (12/12)

**[FILE] F-058 `.gitkeep`** (Providers) — stale marker, trivial.

**[FILE] F-059 `AccessServiceProvider`** — registers 2 commands + Event/Route providers; commented-out schedule stub. Clean nwidart pattern. Issues: none.

**[FILE] F-060 `EventServiceProvider`** — empty `$listen`, discovery on. Issues: none (module has no events — consistent with inventory).

**[FILE] F-061 `RouteServiceProvider`** — maps routes/web.php under `web`, routes/api.php under `api`+`api.` prefix/name. Issues: none.

**[FILE] F-062 `AccessBaselineProvisioner`** — idempotent `findOrCreate`+`syncPermissions` per context; `forgetCachedPermissions()` between catalog creation and role attachment (correct ordering); landlord baseline sets `is_system` (landlord-only column — consistent with schema asymmetry). Issues: none.

**[FILE] F-063 `AccessInvariants`** — management-capacity invariant via `lockForUpdate` principals query inside the mutation transaction (serialization-safe); resolves user model from auth config (guard-agnostic); baseline judged on effective permissions across ALL roles — excellent design. Issues: none.

**[FILE] F-064 `AuditWriter`** — append-only `AdminAuditLog` records (actor/action/target/before/after/ip). Issues: `AdminAuditLog` is a **landlord-connection model** — if `record()` is ever called inside a tenant-context transaction, the audit write escapes that transaction's atomicity (verify call sites UNIT-023+; currently only landlord-side usage seen) → FND-042 (pending). Evidence: read.

**[FILE] F-065 `ManagementPolicy`** — pure decision layer: canManage (current-set coverage), assertRoleGrantable, assertAssignableRole (**checks RESULTING set — closes grant-then-escalate**), assertPermissionsWithinScope (existence+coverage, generic messages — no catalog leak), system-role immutability, deletability incl. last-manager protection. Reference-quality least-privilege design. Issues: none.

**[FILE] F-066 `TenantUserService`** — listUsers eager-loads `roles` ✓ (BUT not `roles.permissions` → per-row `getAllPermissions()` in policy evals adds ~2 queries/user on index → FND-043 perf note); createUser enforces quota (`canAddUser` → ValidationException) — **but never checks `system.allow_registration`** → FND-037 confirmed; hardcoded English quota error message (not `__()` — i18n gap, minor); `method_exists` dead-guards. Evidence: read.

**[FILE] F-067 `EffectivePermissionSet`** — `of()` = union of role-granted permissions filtered by guard (direct grants intentionally excluded — documented); `covers()`. Clean, correct guard isolation. Issues: none.

**[FILE] F-068 `LandlordPermissions`** + **[FILE] F-069 `TenantPermissions`** — permission manifests: key→classification SSoT, derived groups, readOnly(), roleMap(), pinned `managementBaseline()`. Explicit "declaration only, never runtime authz" boundary docs — exemplary SSoT. Notes: Landlord manifest has `roles.view`/`roles.manage` while Tenant has granular roles.* — asymmetric catalogs by design (documented). Issues: none.

### UNIT-013 — Access data/routes/lang — CLOSED (14/14)

**[FILE] F-071 `config/.gitkeep`, F-073 `factories/.gitkeep`, F-075 `seeders/.gitkeep`, F-095 `views/.gitkeep`** — empty dir markers; F-096 `routes/.gitkeep` and F-049/F-058 (prior units) are **stale** (dirs are populated). Trivial scaffold debt — FND-044.

**[FILE] F-072 `config/config.php`** — `['name' => 'Access']` stub; grep shows no consumer (`config('access.name')` unused) → dead config, FND-044.

**[FILE] F-074 `database/migrations/.gitkeep`** — Access intentionally has NO module migrations (uses root tenant/landlord permission-table migrations) — consistent.

**[FILE] F-076 `AccessDatabaseSeeder`** — empty `run()` — dead seeder (never called by DatabaseSeeder; FND-044).

**[FILE] F-077/F-079 `lang/{ar,en}.json`** — 82-key symmetric vocab covering every `__()` key used by Access services/controllers (verified all referenced keys exist). Issues: none.

**[FILE] F-078/F-080 `lang/{ar,en}/validation.php`** — attribute-name maps only; fine.

**[FILE] F-097 `routes/api.php`** — **`auth:sanctum` guard does not exist** (sanctum absent from composer.lock) → every `/api/v1/accesses*` request fatals with undefined guard; exposes scaffold `AccessController` (empty actions). Dead AND broken surface — FND-039 upgraded to MEDIUM.

**[FILE] F-098 `routes/web.php`** — guest group `guest:web` (login/register), protected `['tenant','auth:web']` + per-route `permission:{key},web` wired to `TenantPermissions` constants (SSoT ✓). Issues: **no `throttle:` on login/register** (confirmed FND-041); FND-038 resolved — `tenant.register.show` exists in Landlord routes (`/register-tenant`), so non-tenant register requests redirect to platform signup intentionally, not a loop. Profile routes have no permission gate (any member — acceptable self-service).

### UNIT-014 — Access frontend — CLOSED (12/12)

**[FILE] F-083–F-085 `resources/assets/*`** — `.gitkeep` (0B), `app.js` (0B), `app.scss` (0B) — empty nwidart scaffold assets, never built (dead vite-module-loader). Part of FND-020 family.

**[FILE] F-086 `Pages/Auth/Login.vue`** — GuestLayout + FormField/BaseButton composition ✓; `useForm` POST `/login`; local `tenant` prop typing; i18n with fallbacks. Issues: none.

**[FILE] F-087 `Pages/Auth/Register.vue`** — same composition, `confirmed` field pair; renders unconditionally (client has no allow_registration gate — consistent with the missing server-side check FND-037). Issues: none beyond FND-037.

**[FILE] F-088–F-090, F-093 `Pages/{Create,Edit,Index,Show}.vue`** — identical scaffold placeholders (`module_scaffold` text); only reachable via the broken sanctum API resource (FND-039) → dead pages, FND-044 family.

**[FILE] F-091 `Pages/Profile/Edit.vue`** — Panel + FormField + password section; `useForm.put('/profile')`. Clean. Issues: none.

**[FILE] F-092 `Pages/Roles/Index.vue`** — role cards + `PermissionBundlePicker` (Core component) + create modal only — consistent with backend lacking role update/destroy. Issues: none.

**[FILE] F-094 `Pages/Users/Index.vue`** — EnterpriseDataGrid + modals + per-row `can_edit`/`can_delete` gating + quota banner — correct client-side authz mirroring. Issues: search triggers `router.get` on every keystroke — debounce responsibility lies in `EnterpriseDataGrid` (verify UNIT-016); `roles[0] || 'Member'`/`'Member'` + `'active'` string literals (FND-040); `:total-count="users.length"` client-side pagination over full `->get()` result (bounded by user quota — LOW). Evidence: read.

### UNIT-015 — Access meta — CLOSED (6/6)

**[FILE] F-070 `composer.json`** — `nwidart/access` default author/vendor scaffold values (FND-020 family); `Modules\Access\` PSR-4 + factories/seeders maps. Issues: none beyond scaffold provenance.

**[FILE] F-081 `module.json`** — registers `AccessServiceProvider` only. Issues: none.

**[FILE] F-082 `package.json`** — per-module npm scaffold (vite 4, axios, sass) — never installed or invoked (root build is single vite config) → dead manifest, FND-044.

**[FILE] F-099/F-100 `tests/{Feature,Unit}/.gitkeep`** — **module has ZERO tests** — the strongest authz layer (ManagementPolicy/AccessInvariants) is entirely untested → feeds UNIT-038 verdict (testing gap, MEDIUM). Evidence: 0-byte markers.

**[FILE] F-101 `vite.config.js`** — nwidart scaffold module vite config; `__dirname` inside `"type": "module"` package (ESM) → broken if run; never run (FND-005/FND-020 family).

### UNIT-016 — Core domain (Contracts/Enums/Events) — CLOSED (18/18)

**[FILE] F-102–F-106 Contracts** — `PlanContract`, `QuotaManagerContract`, `SettingManagerContract` (get/set/unset/allByDomain/getTheme/getBranding/supportedLocales/defaultLocale — well-shaped context-aware settings API), `SubscriptionContract`, `TenantContract extends IsTenant`. Typed, focused, DI-friendly. Issues: none.

**[FILE] F-107–F-113 Enums** — `BillingInterval` (monthly/yearly), `Currency` (15 cases + `values()`/`options()`), `Locale` (en/ar + `isRtl()` — matches resources/lang coverage), `SubscriptionStatus` (6 cases + palette colors), `TenantStatus` (active/trialing/suspended/archived — **NO `inactive`**: confirms user `status` literals `inactive` have no enum backing → FND-040 stands), `ThemeMode`, `ThemePalette` (indigo/emerald/violet/amber/cyan/rose — resolves DatabaseSeeder `'indigo'/'dark'` literals: valid enum members, consistent). Issues: `color()` methods return palette names inside enums — presentation concern in domain enum, borderline but consistent project idiom.

**[FILE] F-114–F-119 Events** — `PlanChanged`, `SubscriptionCreated/Updated`, `TenantCreated` (carries adminData+planId → provisioning payload), `TenantProvisioned`, `TenantStatusChanged`. Thin typed DTOs with Dispatchable/SerializesModels. Listeners/consumers verified in later units. Issues: none.

### UNIT-017 — Core infra (Http/Providers/Tasks) — CLOSED (9/9)

**[FILE] F-120 `Controllers/.gitkeep`, F-124 `Providers/.gitkeep`** — stale markers (populated dirs).

**[FILE] F-121 `CoreController`** — scaffold resource controller identical to AccessController pattern (empty store/update/destroy; renders `Core/{Index,Create,Show,Edit}`) — pending Core routes check (UNIT-018), same FND-039-class risk.

**[FILE] F-122 `LocaleController`** — `update` validates against `SettingManagerContract::supportedLocales()` (dynamic SSoT ✓), sets session + app locale. Issues: none.

**[FILE] F-123 `ThemeController`** — `Enum` rule validation for palette/mode; **session-only persistence** (per SettingManagerContract precedence session>tenant>landlord — design consistent). Issues: none.

**[FILE] F-125 `CoreServiceProvider`** — registers Event+Route providers; no contract→impl bindings here → bindings must live elsewhere (verify: `SettingManagerContract`/`QuotaManagerContract` bindings — pending search in UNIT-027/Settings module; if unbound, LocaleController fatals — **unverified binding risk**, tracked OQ).

**[FILE] F-126 `EventServiceProvider`** — empty `$listen`, discovery on. Issues: none.

**[FILE] F-127 `RouteServiceProvider`** — standard web/api mapping. Issues: none.

**[FILE] F-128 `ScopePermissionCacheTask`** — on `makeCurrent`: `cacheKey = spatie.permission.cache.tenant-{id}` + `clearPermissionsCollection()`; `forgetCurrent` → `.landlord`. **Resolves FND-021 mechanism**: permission cache IS per-context — combined with dynamic `getConnectionName()` models, landlord/tenant RBAC graphs cannot bleed through the cache. FND-015 remains open for the *general* cache store (still unprefixed landlord keyspace). Issues: none.

### UNIT-018 — Core data/routes/lang — CLOSED (12/12)

**[FILE] F-130 `config/.gitkeep`, F-132/133/134 `database/*/.gitkeep`, F-178 `views/.gitkeep`, F-179 `routes/.gitkeep`** — 0B markers; routes/.gitkeep stale (populated).

**[FILE] F-131 `config/config.php`** — `'name' => 'Core'` stub, no consumer → dead config (FND-044 family).

**[FILE] F-135 `CoreDatabaseSeeder`** — empty `run()` — dead seeder.

**[FILE] F-136/F-137 `lang/{ar,en}.json`** — 85-key symmetric vocab: error pages 4xx/5xx, tenant lifecycle, perm-group labels (`perm_group_*` — the exact keys `RoleController::index`/`LandlordRoleController` use via `__("perm_group_{$groupKey}")` — verified present). Issues: none.

**[FILE] F-180 `routes/api.php`** — comment only, no routes → **CoreController is completely unrouted** (dead scaffold, FND-045 LOW).

**[FILE] F-181 `routes/web.php`** — `POST /locale` + `POST /theme` only. No auth gate — acceptable: they only write session prefs, CSRF-protected by web group. Issues: none.

### UNIT-019 — Core UI components A — CLOSED (14/14)

**[FILE] F-143 `BadgeCell`** — variant→token-class map; RTL-safe (`me-1.5`). Clean.

**[FILE] F-144 `BaseButton`** — button/Link/external-anchor polymorphism, loading spinner, token variants. Clean — the §6.1 canonical action element.

**[FILE] F-145 `ConfirmDialog`** — Modal wrapper; **infers icon variant by string-matching `confirmButtonClass` (`cls.includes('warning')`)** — fragile heuristic → FND-049. Also hand-rolls `<button>` instead of BaseButton.

**[FILE] F-146 `CurrencyCell`** — delegates to `useCurrency` composable (SSoT ✓).

**[FILE] F-147 `DataTable`** — generic `T`, dual cell-slot naming, EmptyState integrated. Clean.

**[FILE] F-148 `EmptyState`** — icon slot + i18n defaults. Clean.

**[FILE] F-149 `EnterpriseDataGrid`** — generic `T`, SkeletonLoader/EmptyState, sliding-window pagination, RTL-aware chevrons, local filter+sort. Issues: (a) **`onSearchInput` emits per keystroke with NO debounce** — server-driven pages (Users/Index) fire one Inertia GET per keystroke → FND-046; (b) `localSearch` applies local filtering *on top of* server results — double-filtering, harmless today but confusing with paginated data (LOW); (c) template var `page` shadows `usePage()` binding inside pagination `v-for` — works (loop scope) but fragile naming (LOW).

**[FILE] F-150 `EnterpriseFormEngine`** — form shell w/ submit/cancel footer. Issue: hand-rolls buttons instead of composing BaseButton (internal §6.1 inconsistency, FND-049).

**[FILE] F-151 `FilterSelect`** — array|record options, emits update+change. Clean.

**[FILE] F-152 `FormField`** — generic `V`, full control matrix (select/textarea/checkbox/trailing slot), error/hint states, `Math.random` fieldId (fine client-side). Clean.

**[FILE] F-153 `FormModal`** — Modal+form+footer via BaseButton. Clean.

**[FILE] F-154 `IconButton`** — Link/external/button polymorphism like BaseButton. Clean.

**[FILE] F-155 `LanguageSwitcher`** — **hardcodes `en`/`ar` buttons and `newLocale === 'ar'` for RTL** instead of consuming `locale.supported`/`is_rtl` map → breaks if locales extend (SSoT drift) → FND-048. Post `/locale` w/ preserveScroll ✓.

**[FILE] F-156 `Modal`** — Teleport+Transition, Escape handler w/ proper cleanup. Issue: `document.body.style.overflow` toggled per-instance — **scroll lock not reference-counted**; two concurrent modals unlock each other (violates AGENTS §1 shared-resource rule) → FND-047.

### UNIT-020 — Core UI components B — CLOSED (9/9)

**[FILE] F-157 `PageHeader`** — RTL-aware back-link (`rtl:rotate-180`), title/subtitle/actions slots. Clean.

**[FILE] F-158 `PalettePicker`** — radio-grid palette picker consuming `{id,label,colors[]}` presets. Clean.

**[FILE] F-159 `Panel`** — canonical card (header/actions/body/footer, padding variants, flush). Clean.

**[FILE] F-160 `PermissionBundlePicker`** — Set-based membership, presets derived from group keys (`allKeys`/`readKeys` — not hardcoded lists ✓), per-group toggle + coverage badge, classification labels via `t('class_*')` (keys exist in Core lang). Clean.

**[FILE] F-161 `SkeletonLoader`** — table/stat/card/text variants. Clean.

**[FILE] F-162 `StatCard`** — trend/change/description slots, token classes. Clean.

**[FILE] F-163 `StatusBadge`** — status→variant switch (`active→success` etc.) duplicates the semantic mapping the PHP enums express via `color()` — a second, client-side status mapping SSoT (LOW note; divergent coverage: enum has `archived`, badge maps it to neutral default — acceptable but drifting).

**[FILE] F-164 `TabNav`** — generic `K`, role=tablist/tab + aria-selected. Clean.

**[FILE] F-165 `ThemeSwitcher`** — routes through `useThemeStore`+`THEME_PRESETS` (store = SSoT per AGENTS §2) then posts `/theme` — correct bridge usage. Issue: palette dropdown has no click-outside/Escape dismissal (stays open until a pick or re-click — minor UX). No mode 'system' option exposed despite ThemeMode.System existing (feature gap, noted).

### UNIT-021 — Core frontend infra — CLOSED (15/15)

**[FILE] F-140–F-142 `resources/assets/*`** — 0B nwidart scaffold stubs (dead, FND-044 family).

**[FILE] F-166 `useCurrency.ts`** — `billing.currency` shared prop + `Intl.NumberFormat`; locale hardcoded `ar→ar-EG`/else `en-US` (ties number locale to UI locale — fine for current en/ar set, noted extensibility limit like FND-048).

**[FILE] F-167 `useI18n.ts`** — `t()`/`trans()` read shared `locale.translations`. Issues: (a) `trans()` interpolates placeholder into `new RegExp` unescaped — placeholders with regex chars would break (edge, LOW); (b) **the shared `locale.translations` prop ships the ENTIRE merged translation dictionary on every Inertia response** (`load($locale,'*','*')` — verified in HandleInertiaRequests:120) → per-request payload bloat, scales with catalog size → FND-050.

**[FILE] F-168 `GuestLayout`** — branding-aware guest shell; `allowRegistration` gates all register links client-side (UI-only gate — enforcement gap stays FND-037). Clean.

**[FILE] F-169 `LandlordLayout`** — permission-key-gated nav (`can('tenants.view')` etc. — mirrors backend permission middleware exactly; prop `auth.user.permissions` verified shared in HandleInertiaRequests:55). Clean.

**[FILE] F-170 `TenantLayout`** — same pattern, permission-key nav gating (verified prop shared :66), collapsible sidebar w/ localStorage, flash banners. Clean.

**[FILE] F-171–F-175 `Pages/{Create,Edit,Index,Show}.vue` + `ErrorPage.vue`** — 4 scaffolds identical to Access pattern (dead — unrouted CoreController, FND-045). **ErrorPage** is the real asset: full 4xx/5xx status map, tone classes, `exception` prop rendered when present — **verify error renderer only passes `exception` under debug** (pending error-handler check — currently unverified who renders it; flag: potential exception detail leak if `exception` prop is populated in production → OQ).

**[FILE] F-176 `setupInertiaStateBridge.ts`** — the mandated Inertia→Pinia bridge: init theme from shared `theme` prop, `router.on('navigate')` re-syncs theme+locale attrs. Clean; matches AGENTS invariant.

**[FILE] F-177 `useThemeStore.ts`** — Pinia theme SSoT; `THEME_PRESETS`/`ThemePalette`/`ThemeMode` TS unions **mirror the PHP enums** (`indigo/emerald/violet/amber/cyan/rose`, `dark/light/system` — verified identical to Modules\Core\Enums) — a necessary cross-language sync point (LOW: two literal lists kept in sync by convention). `initTheme` registers matchMedia listener per call — idempotent-in-practice (bridge calls once), noted.

### UNIT-022 — Core meta — CLOSED (6/6)

**[FILE] F-129 `composer.json`** — `nwidart/core` scaffold identity (FND-020 family); PSR-4 maps OK.

**[FILE] F-138 `module.json`** — registers `CoreServiceProvider` only. Issues: none.

**[FILE] F-139 `package.json`** — dead per-module npm scaffold (never installed/run).

**[FILE] F-182/F-183 `tests/{Feature,Unit}/.gitkeep`** — **zero tests** for Core's contracts/enums/stores — same systemic test gap as Access (aggregated in FND-052).

**[FILE] F-184 `vite.config.js`** — dead scaffold, `__dirname` in ESM package (FND-005/044 family).

### UNIT-023 — Landlord `app/` — CLOSED (21/21)

**[FILE] F-185 `Console/RebuildDatabasesCommand`** — `db:rebuild` w/ ConfirmableTrait, explicit `--database=landlord|tenant` + `--path` guard, `Tenant::checkCurrent()` hard guard inside `execute()` (neutralizes the documented default-connection pitfall), per-tenant failure isolation, `--tenant=*` resolves id/slug/domain manually (resolves FND-019 → custom resolver covers documented surface). Clean.

**[FILE] F-186 `.gitkeep`** — stale.

**[FILE] F-187 `DashboardController`** — thin, delegates to metrics. Clean.

**[FILE] F-188 `LandingController`** — public welcome/pricing; tenant-context redirect; translatable plan descriptions. Clean.

**[FILE] F-189 `LandlordAdminController`** — exemplary: separate gated endpoints per mutation class (role/status/password/profile), audit before/after snapshots, invariant checks inside transactions, self-mutation blocks, `assertRoleGrantable`/`assertAssignableRole` — closes grant-then-escalate. Clean.

**[FILE] F-190 `LandlordAuthController`** — suspended-account pre-check before `Auth::attempt` (no session for suspended), session regenerate/invalidate correct; **no throttle** (folds into FND-041).

**[FILE] F-191 `LandlordController`** — scaffold dead controller (unrouted — Landlord routes define explicit controllers) → FND-045 family.

**[FILE] F-192 `LandlordRoleController`** — guard-pinning `abort_if(guard_name!=='landlord',404)` on bound {role} (closes cross-guard tampering), system-role immutability, role-in-use 409, scoped permission picker, full audit. Exemplary.

**[FILE] F-193 `ModuleManagementController`** — global nwidart enable/disable; hardcoded locked-module list `['core','landlord','access','subscription','settings','tenant']` (LOW — could derive but explicit is defensible); toggle is global not per-tenant (semantic verified — modules_statuses.json). Issues: none beyond hardcoded list.

**[FILE] F-194 `TenantController`** — full lifecycle CRUD. Issues: (a) `extendTrial` unconditionally sets `status=Trialing` — **bypasses status machine** (archived/suspended → trialing silently, no transition guard) → FND-054; (b) `suspend` reads `request('reason')` bypassing `$request->validate` (unvalidated input into settings jsonb — capped only by caller UI; LOW); (c) `destroy` honors `drop_database` flag behind TENANTS_DELETE (appropriate) but lifecycle delete isn't transactional (FND-055).

**[FILE] F-195 `TenantRegistrationController`** — `allow_registration` IS enforced here ✓. Issues: (a) **hardcoded `'.localhost'` domain suffix** (line ~71) — ignores `TENANT_DOMAIN_SUFFIX`; production self-registrations get `foo.localhost` → SaaSTenantFinder never matches → **tenant unreachable** → FND-053 HIGH; (b) `Rule::unique(Tenant::class,'domain')` compares raw `subdomain` against stored `slug.localhost` values → domain uniqueness silently never fires (slug check saves it today); (c) unthrottled public provisioning endpoint (FND-041 surface).

**[FILE] F-196 `AdminAuditLog`** — append-only (UPDATED_AT=null), snapshot fields no FKs (survives deletes), action catalog const — tamper-resistant design ✓.

**[FILE] F-197 `LandlordUser`** — landlord conn, guard_name 'landlord', hashed cast. `status` fillable (controllers validate explicitly — safe today). Clean.

**[FILE] F-198 `Tenant` (module)** — UsesLandlordConnection + TenantContract; `status` cast to enum; `settings` array; `isActive` covers active|trialing consistent w/ middleware. Issues: `db_username`/`db_password` in `$fillable` with NO encrypted cast → FND-030 **confirmed** (plaintext credential storage if ever set — TenantSeeder sets them); `status`/`settings` mass-assignable — safe only because controllers pass whitelisted arrays.

**[FILE] F-199 `Providers/.gitkeep`** — stale. **[FILE] F-200–F-202 providers** — LandlordServiceProvider registers `RebuildDatabasesCommand` + Event/Route providers; EventServiceProvider empty `$listen` w/ discovery; RouteServiceProvider standard. Clean.

**[FILE] F-203 `LandlordMetricsService`** — KPIs via counts + `withCount('tenants')`; **MRR via `->get()->sum()` over all active+trialing subs** — unbounded hydration for a scalar aggregate → FND-056 (LOW perf); `'Free'`/`'-'` hardcoded labels.

**[FILE] F-204 `TenantLifecycleService`** — suspend/activate/archive/delete + events; suspension reason into `settings` jsonb. Issues: no transition guards (any status → any status; `activate` revives archived too — pairs with FND-054); `delete()` not atomic (subs delete then tenant delete, drop errors swallowed) → FND-055.

**[FILE] F-205 `TenantProvisioner`** — slug→db-name derivation (`Str::slug`+underscores → safe identifier charset for raw `CREATE DATABASE` interpolation ✓); plan resolution (explicit → default_plan_id → lowest active); `tenant->execute` for baseline+owner+settings. Issues: (a) **no transaction/compensation** — orphan DB+row+subscription if migrations/seeding fail mid-provision → FND-055; (b) `request()->getHost()` in a service (CLI-safe fallback present); (c) domain via `{slug}.{request-host}` here vs `{slug}.localhost` in self-registration — divergent domain construction → folded into FND-053.

### UNIT-024 — Landlord data/routes/lang — CLOSED (16/16)

**[FILE] F-207/209/210/214/241 `.gitkeep` ×5** — stale markers in populated dirs.

**[FILE] F-208 `config/config.php`** — name-stub (FND-044 family).

**[FILE] F-211 `add_saas_fields_to_tenants_table`** — connection-pinned, `checkCurrent` guard, per-column `hasColumn` idempotency, indexed status/plan_id, `down()` present. Clean.

**[FILE] F-212 `create_landlord_users_table`** — connection-pinned + guard; email unique, status indexed, rememberToken. **No `password_reset_tokens` on landlord** — consistent with FND-017 (no landlord reset flow exists at all — not a bug, a feature gap).

**[FILE] F-213 `create_admin_audit_logs_table`** — actor/target snapshots without FKs (survives deletions ✓), indexed action/actor/created_at, `ipAddress` column. Clean — matches AdminAuditLog model exactly.

**[FILE] F-215 `LandlordDatabaseSeeder`** — plans → baseline RBAC → super admin → system settings (`allow_registration`, locales, branding, theme) → tenant plan backfill. Issues: `Hash::make('password')` predictable seed credentials (FND-029 family); `tenant1.localhost`/`tenant2.localhost` hardcoded domains (env-coupled, FND-053 family); `catch(\Throwable){}` on assignRole (FND-033 family); `supported_locales` seeded as PHP array into settings — registry must handle array values (pending UNIT-027).

**[FILE] F-242 `routes/api.php`** — `auth:sanctum` apiResource → **sanctum not installed** (composer.json has none) → dead route that 500s if probed → FND-058 (same in Access/Subscription/Tenant api.php).

**[FILE] F-243 `routes/web.php`** — public `/`,`/pricing`,`/register-tenant` + `landlord` host middleware group w/ `auth:landlord` + `landlord.active` + per-route `permission:{LP::*},landlord` — **permission constants from manifest, zero hardcoded strings** (SSoT exemplar). Issues: (a) public landing/registration routes are NOT host-restricted — on a tenant host `/pricing` renders platform pricing and `/register-tenant` provisions `slug.{tenant-host}` domains (nested nonsense) → FND-057; (b) `Route::middleware('landlord')` group relies on EnsureLandlordHost (UNIT-005 verified).

**[FILE] F-216/F-218 `lang/{en,ar}.json`** — 152/152 exact key parity verified ✓. F-217/F-219 validation attribute maps symmetric. Clean.

### UNIT-025 — Landlord frontend — CLOSED (15/15)

**[FILE] F-225 `Pages/Dashboard.vue`** — StatCard/DataTable/CurrencyCell composition, i18n everywhere. **BUG**: reads `metrics.mrr` but `LandlordMetricsService` emits `monthly_revenue` → MRR card silently renders $0 → FND-061.

**[FILE] F-226 `Pages/Admins/Index.vue`** — exemplary: per-row `can_edit`/`can_delete` gating, 4 FormModals + 2 ConfirmDialogs, assignableRoles prop (actor-scoped). Clean.

**[FILE] F-227 `Pages/Auth/Login.vue`** — GuestLayout + FormField + remember + password reset-on-finish. Clean.

**[FILE] F-228 `Pages/Landing/Pricing.vue`, F-230 `Welcome.vue`** — presentational plan-driven marketing pages. Clean.

**[FILE] F-229 `Pages/Landing/RegisterTenant.vue`** — `useForm` + plan cards; **`#trailing` shows literal `.localhost:8000`** — third env-coupled domain suffix instance (FND-053 family; now spans controller + this UI + TenantCreate watchers + seeders).

**[FILE] F-231 `Pages/Modules/Index.vue`** — issues: `coreModules` literal duplicates the controller's locked list → FND-059 (dual SSoT drift); toggle uses raw `<button>` not BaseButton/IconButton (FND-049 family).

**[FILE] F-232 `Pages/Roles/Index.vue`** — is_system lock display, users_count delete-disable, PermissionBundlePicker w/ scoped groups. Clean.

**[FILE] F-233 `Pages/Tenants/Create.vue`** — PageHeader+Panel+FormField composition; issues: `watch` auto-fills `${slug}.localhost` (FND-053 family — admin-side too), `$${p.price}/mo` hardcoded USD in label (minor).

**[FILE] F-234 `Pages/Tenants/Index.vue`** — server-side pagination wired correctly; `statusOptions` hardcodes the 4 TenantStatus cases client-side (FND-040 family); per-keystroke search hits server (FND-046 consumer).

**[FILE] F-235 `Pages/Tenants/Show.vue`** — full lifecycle console (suspend/activate/archive/plan/trial/cancel/delete w/ forms). Issue: **`deleteForm` defaults `drop_database: true`** — the irreversible DB-drop option pre-checked in a delete dialog → dangerous default → FND-060.

**[FILE] F-236–F-239 scaffold pages (Create/Edit/Index/Show)** — identical dead scaffold pattern (unrouted — Landlord routes never reference LandlordController) → FND-045 family.

### UNIT-026 — Landlord meta/assets — CLOSED (10/10)

**[FILE] F-206 `composer.json`** — `nwidart/landlord` scaffold identity (FND-020); PSR-4 correct.
**[FILE] F-220 `module.json`** — registers LandlordServiceProvider. Clean.
**[FILE] F-221 `package.json`, F-246 `vite.config.js`** — dead scaffold (FND-044 family).
**[FILE] F-222–F-224 `resources/assets/*`, F-240 `views/.gitkeep`** — 0B stubs.
**[FILE] F-244/F-245 `tests/*.gitkeep`** — zero tests for the entire provisioning/lifecycle surface (FND-052).

### UNIT-027 — Settings `app/` + `config/` — CLOSED (15/15)

**[FILE] F-247 `Controllers/.gitkeep`, F-255 `Providers/.gitkeep`, F-260 `config/.gitkeep`** — stale.

**[FILE] F-248 `LandlordSettingsController`** — index exposes all 5 domains + Currency::options() + plans; update iterates `set()` per key — relies on UpdateSettingsRequest for authorization (registry `prohibited` rule fires first → runtime `assertWritable` is unreachable defense-in-depth ✓).

**[FILE] F-249 `TenantSettingsController`** — tenant-context settings (branding/theme only effectively writable — owner rules); `workspace_name` routes to `tenants.name` (SSoT documented in code ✓).

**[FILE] F-250 `UpdateSettingsRequest`** — per-key rule synthesis from registry; unwritable keys become `['prohibited']`; `{$key}.*` item rules supported. Exemplary.

**[FILE] F-251 `Setting`, F-252 `TenantSetting`** — connection-pinned models; `booted` hooks route **every** save/delete → `SettingService::forgetMapForModel` (structural invalidation covers seeders/tinker/provisioners ✓✓).

**[FILE] F-253 `ParsesSettingValue`** — symmetric serialize/parse for json/bool/int/float/string. Clean.

**[FILE] F-256 `SettingsServiceProvider`** — `singleton(SettingManagerContract→SettingService)` **confirmed** (closes UNIT-017 binding OQ); `mergeConfigFrom(settings.php)`.

**[FILE] F-257/F-258 Event+Route providers** — standard scaffold. Clean.

**[FILE] F-254 `SettingService`** — the project's best-designed service: registry-gated writes, `owner` context enforcement (`landlord` keys unwritable in tenant context), tenant-inherits-landlord `domainMap`, per-scope `rememberForever` cache, enum-driven `getTheme`/`supportedLocales`/`defaultLocale` fallbacks. Issues: (a) `scopeMap` catches `Throwable` → `[]` — settings silently absent on DB/cache failure (graceful but masks outages, LOW); (b) `getBranding` issues extra cached queries per request — fine via cache; (c) `is_public` flag is **write-only — no consumer anywhere** (dead column semantics) → FND-062; (d) cache keys `settings.map.{scope}.{domain}` land in the shared default-store keyspace (FND-015 consequence — scoped keys, shared table).

**[FILE] F-261 `config/config.php`** — name-stub. **[FILE] F-262 `config/settings.php`** — **the settings registry SSoT**: enum-derived validation rules, owner surface declarations, `key.*` item definitions — closes the "no hardcoded settings" rule architecturally. Exemplary.

### UNIT-028 — Settings data/routes/lang/frontend — CLOSED (16/16)

**[FILE] F-263/264/266/272/277/278 `.gitkeep` ×6 + F-273/274 asset stubs (0B)** — scaffold family (FND-044).

**[FILE] F-265 `create_system_settings_table`** — connection-pinned + checkCurrent guard; unique(domain,key); additive-column path for existing `settings` tables. `down()` present (drops whole table — mildly over-aggressive for the additive branch, LOW). Clean.

**[FILE] F-267 `SettingsDatabaseSeeder`** — empty (defaults seeded by LandlordDatabaseSeeder instead — intentional split, OK).

**[FILE] F-268/F-269 `lang/{en,ar}.json`** — 31/31 parity ✓.

**[FILE] F-279 `routes/api.php`** — `<?php` only, no routes. Clean (no sanctum scaffold here).

**[FILE] F-280 `routes/web.php`** — landlord group (`landlord`+`auth:landlord`+`landlord.active`+LP permissions) vs tenant group (`auth:web`+`tenant`+P permissions) — manifest constants throughout ✓.

**[FILE] F-275 `Pages/LandlordSettings.vue`** — 5-domain TabNav editor, palette filter via `theme.palettes` prop. Issues: hardcoded fallbacks `'SaaS Cloud'`/`support@saas.test`/`14`/`'tenant_'`/`'USD'`/`{en,ar}` locale options (duplicates server-side defaults; FND-048-family locale literals).

**[FILE] F-276 `Pages/TenantSettings.vue`** — branding (workspace_name→tenants.name SSoT) + theme forms, palette gating. Clean.

### UNIT-029 — Settings meta — CLOSED (6/6)

**[FILE] F-259 `composer.json`** — nwidart scaffold identity (FND-020). **[FILE] F-270 `module.json`** — registers SettingsServiceProvider ✓. **[FILE] F-271 `package.json`, F-283 `vite.config.js`** — dead scaffold. **[FILE] F-281/F-282 tests markers** — zero tests for the settings registry/governance layer (FND-052).

### UNIT-030 — Subscription `app/` — CLOSED (11/11)

**[FILE] F-284/F-289 `.gitkeep` ×2** — stale.

**[FILE] F-285 `PlanController`** — translatable name/desc via `_en`/`_ar` fields; currency pinned to `billing.default_currency` setting (SSoT ✓ documented); deactivating the default plan auto-`unset`s `default_plan_id` (self-healing ✓). Issues: `features` fallback `['core_dashboard']` magic literal (LOW).

**[FILE] F-286 `SubscriptionController`** — landlord index paginated w/ eager loads ✓. Issues: (a) **`tenantOverview` reports `storage_mb.current = 120` — hardcoded fake usage metric** presented as real data → FND-064; (b) `changePlan` accepts any `exists:plans,id` incl. inactive plans (admin path filters `is_active` — asymmetric); (c) `'Unknown'`/`'-'` literals.

**[FILE] F-287 `Plan`** — translatable, limits array, `hasFeature` w/ `*` wildcard, `tenants()`+`subscriptions()` relations. Clean.

**[FILE] F-288 `Subscription`** — enum status cast, belongsTo tenant/plan, `is*` helpers (trialing-by-date mirrors Tenant's loose semantics — consistent). Clean.

**[FILE] F-290 `SubscriptionServiceProvider`** — `singleton(QuotaManagerContract→QuotaService)` confirmed. Clean.

**[FILE] F-291/F-292 Event+Route providers** — standard scaffold. Clean.

**[FILE] F-293 `QuotaService`** — contract impl; issues: (a) hardcoded defaults `5` users / `1024` MB when no plan (FND-040 family literals — should live in registry/config); (b) `currentCount < limit` quota check has **no locking** — concurrent signups overshoot (LOW-MEDIUM race); (c) `$tenant->execute()` when already-current does a redundant context switch (minor).

**[FILE] F-294 `SubscriptionService`** — issues: (a) **yearly price hardcoded `price * 10`** — magic discount multiplier, not in plan/registry → FND-063; (b) `changePlan` updates plan_id+amount but leaves `billing_interval`/`currency` stale — a yearly sub on a new plan keeps old interval while amount = new monthly price → **billing inconsistency** → FND-063; (c) non-transactional (FND-055 family).

### UNIT-031 — Subscription data/routes/lang — CLOSED (17/17)

**[FILE] F-296/298/299/302/322/323 `.gitkeep` ×6 + F-297 `config.php` stub** — scaffold family.

**[FILE] F-300 `create_plans_table`** — landlord-pinned, json translatable name/description, slug unique, decimal price. Clean.

**[FILE] F-301 `create_subscriptions_table`** — landlord-pinned; `tenant_id`/`plan_id` indexed but **no FK constraints** — plan deletion leaves orphaned subs (pairs with FND-065); no unique(current-sub) guard — multiple active subs per tenant possible (application-level). LOW notes.

**[FILE] F-303 `PlanSeeder`** — idempotent `updateOrCreate` by slug; translatable starter/pro/enterprise; `'USD'` literal (minor — currency is per-plan column though pinned to settings at creation in PlanController — seeders write their own; consistent default).

**[FILE] F-304 `SubscriptionDatabaseSeeder`** — empty (PlanSeeder does the real work via LandlordDatabaseSeeder chain — intentional).

**[FILE] F-305–F-308 lang** — 89/89 parity + validation attribute maps. Clean.

**[FILE] F-324 `routes/api.php`** — sanctum scaffold dead route (FND-058).

**[FILE] F-325 `routes/web.php`** — manifest-constant permission middleware throughout. **Issue**: `DELETE /landlord/plans/{plan}` → `PlanController::destroy` — **method does not exist** (controller has index/store/update only) → reachable-but-broken route, 500/BadMethodCall if ever hit → FND-065.

### UNIT-032 — Subscription frontend — CLOSED (11/11)

**[FILE] F-311–F-313 assets stubs** — 0B scaffold (FND-044).

**[FILE] F-314 `PlanFormFields.vue`** — shared create/edit fragment (module-scoped per §6.1 rule), InertiaForm generic, RTL `dir="rtl"` on Arabic fields. Clean.

**[FILE] F-315–F-317/F-321 scaffold pages** — identical dead scaffolds (unrouted) — FND-045 family.

**[FILE] F-318 `LandlordSubscriptions.vue`** — read-only grid, CurrencyCell per-row currency ✓. Missing: pagination prop not wired (grid shows all — paginate(15) server-side exists but `pagination` prop/`page-change` not passed → **page 2+ unreachable via UI** → FND-066 LOW).

**[FILE] F-319 `Overview.vue`** — tenant quota/billing page; renders `usage.storage_mb.current` — the fake `120` flows to UI (FND-064 confirmed end-to-end); upgrade modal posts `/subscription/change-plan`. Clean structure.

**[FILE] F-320 `Plans.vue`** — plan cards + PlanFormFields modals + delete ConfirmDialog → **calls `DELETE /landlord/plans/{id}` → missing controller method → guaranteed 500 on use** (FND-065 confirmed user-reachable, not just dead route).

### UNIT-033 — Subscription meta — CLOSED (6/6)

F-295 composer (nwidart identity, FND-020); F-309 module.json (registers provider ✓); F-310 package.json + F-328 vite.config (dead scaffold); F-326/F-327 zero tests for billing/quota (FND-052).

### UNIT-034 — Tenant `app/` — CLOSED (7/7)

**[FILE] F-329/F-332 `.gitkeep` ×2** — stale.

**[FILE] F-330 `TenantController`** — scaffold (target of dead sanctum apiResource, FND-058/F-045 family).

**[FILE] F-331 `TenantDashboardController`** — quota via contract ✓, eager `with('roles')` ✓. Issues: **`storage.current = 120` fake metric again** — second fabrication site (FND-064 now systemic, upgrade to MEDIUM); literals `'Member'`/`'Starter'`/`'localhost'`/`'active'` (FND-040 family).

**[FILE] F-333–F-335 providers** — standard; TenantServiceProvider registers Event+Route. Clean.

### UNIT-035 — Tenant data/routes/lang — CLOSED (11/11)

**[FILE] F-337–F-341 + F-356 markers/config stub** — scaffold family; **Tenant module owns NO migrations** — tenant schema lives in root `database/migrations/tenant/` (intentional: MigrateTenantAction runs that path — consistent, noted).
**[FILE] F-342 `TenantDatabaseSeeder`** — empty (tenant seeding happens via provisioner's baseline path — OK).
**[FILE] F-343/F-344 lang** — 3 keys, symmetric.
**[FILE] F-357 `routes/api.php`** — sanctum scaffold (FND-058).
**[FILE] F-358 `routes/web.php`** — single `/dashboard` under `tenant`+`auth:web`. Clean.

### UNIT-036 — Tenant frontend — CLOSED (9/9)

**[FILE] F-347–F-349 assets stubs (0B), F-355 views/.gitkeep** — scaffold.
**[FILE] F-350–F-353 scaffold Pages (Create/Edit/Index/Show)** — dead (only `/dashboard` route exists → TenantController scaffold unrouted).
**[FILE] F-354 `Pages/Dashboard.vue`** — StatCard/DataTable/PageHeader composition, trial banner, quota meters w/ >85% danger threshold. Issues: displays `quota.storage.current` (= fake 120, FND-064 third UI surface); `'1000'` literal fallback; styled `<Link>`s used as action buttons (FND-049 family — should be BaseButton href).

### UNIT-037 — Tenant meta — CLOSED (6/6)

F-336 composer (nwidart identity, FND-020); F-345 module.json (registers TenantServiceProvider ✓); F-346 package.json + F-361 vite.config (dead scaffold); F-359/F-360 zero tests (FND-052).

### UNIT-038 — Root test suite — CLOSED (13/13)

**Verdict: coverage quality is HIGH; infrastructure isolation is the critical defect.**

**[FILE] F-523 `ExampleTest`** — trivial GET / assert 200 (works only because landing is public).
**[FILE] F-534 `TestCase`** — **bare BaseTestCase: no RefreshDatabase/LazilyRefreshDatabase anywhere**; phpunit.xml `DB_CONNECTION=landlord` + `DB_DATABASE=multivendor` (real MySQL, `root`/empty pw) → **the entire suite writes to the live dev database** → FND-067.
**[FILE] F-535 `Unit/ExampleTest`** — `assertTrue(true)` scaffold.

**[FILE] F-524 `LandlordTenantProvisioningTest`** — auth flow (valid/invalid creds, suspended pre-check), dashboard/tenants access, suspend/activate/update/archive/changePlan/extendTrial/duplicate-domain — uses disposable tenants + finally cleanup (documented "persistent test database" rationale). Real endpoint coverage ✓. Also **documents FND-054 path**: extend_trial test asserts `Trialing` is forced — codifies the machine bypass as expected behavior (finding stands — test proves intentional, not sanctioned).

**[FILE] F-525 `ModularTranslationArchitectureTest`** — enforces module lang-file ownership, en/ar key parity, global-layer leanness (≤60 keys), zero cross-module key duplication, loader resolution, module validation attributes, Inertia prop resolution. **The architecture's own invariant test** — excellent.

**[FILE] F-526 `PlatformAdminManagementTest`** — manifest group integrity, permission-gated routes, **escalation beyond scope → 403**, edit≠assign separation, self-mutation block, last-manager invariant, system-role immutability + holder-409, audit-trail assertion, mid-session suspension cutoff, `access:sync-landlord --repair` idempotency. **Best-in-suite security coverage** ✓.

**[FILE] F-527 `QuotaEnforcementTest`** — plan-limit resolution + hard quota block on user creation (temp restricted plan, restore in finally). Real behavioral test; mutates shared fixture (coupling noted).

**[FILE] F-528 `SettingsGovernanceTest`** — registry rejection of unknown keys (422 + no write), invalid values, landlord-key write in tenant context → exception, programmatic write → exception, member 403, workspace_name→tenant.name SSoT, tenant unset→landlord inheritance restoration, **direct-model-write cache invalidation**. Covers the strongest layer precisely.

**[FILE] F-529 `TenancySecurityTest`** — finder exact-match/suffix/foreign-domain negative, header+query injection rejection, unknown-host 404 (web+JSON), landlord-in-tenant 404, suspended/archived → 423 (web+JSON). Adversarial boundary coverage ✓.

**[FILE] F-530 `TenantAccessControlTest`** — baseline idempotency + full catalog, owner/member matrices, member 403 on roles/users/settings/change-plan, **scoped-user escalation blocked**, edit/delete higher-privilege target blocked, last-manager invariant, equal-peer deletion allowed. Tenant-side mirror of PlatformAdminManagementTest ✓.

**[FILE] F-531 `TenantIsolationTest`** — `tenant` middleware → NoCurrentTenant, landlord-host redirects (/login→/landlord/login, /register→/register-tenant — **confirms intentional redirect**, closes earlier suspicion), `?tenant=` hint can't switch context, **cross-DB write/read isolation** (user in vendor_1 missing from vendor_2). Hardcoded `tenant1/tenant2.localhost` + `vendor_1/2` fixtures → suite fails on fresh clone (FND-067).

**[FILE] F-532 `ThemingAndLocalizationTest`** — translatable plans, dict parity, loader resolution, SetLocale + `/locale` + `/theme` validation, supported_locales gating, settings persistence+invalidation, **`allow_registration=false` → 403 on BOTH GET and POST /register-tenant** (confirms the platform-level enforcement is real and tested → narrows FND-037 to tenant-member `/register` only), shared props contract, HTML↔props theme consistency + invalid-session fallback.

**[FILE] F-533 `UnifiedErrorPageTest`** — 404/403/500/web+JSON matrices, tenant-missing 404 w/ message, route-miss locale reconstruction without middleware, unauth → redirect preserved. Covers the ErrorPage renderer contract (FND-051 partially addressed: exception prop tested only implicitly; debug-leak angle still unverified).

### UNIT-039 — Stubs A (F-441–F-468) — CLOSED
### UNIT-040 — Stubs B (F-469–F-495) — CLOSED
### UNIT-041 — Stubs C (F-496–F-522) — CLOSED

All 82 `stubs/nwidart-stubs/*.stub` files read in full. Findings (applies to all three units):

1. **The entire stub tree is unreachable** — `config/modules.php` sets `'stubs' => ['enabled' => false, 'path' => base_path('vendor/nwidart/.../stubs')]` — the committed custom stubs are **never loaded**; `module:make` uses vendor defaults. 82 files of dead tooling code → FND-068.
2. **`routes/api.stub` hardcodes `auth:sanctum`** — this is the *source* of FND-058: every generated module api.php inherits a route file wired to a guard from an uninstalled package. Fix the stub *or* (preferred, since unused) delete the tree / set `enabled=true` + fix stubs.
3. **`composer.stub` produces `nwidart/<name>` identity w/ Nicolas Widart as author** — source of FND-020 (all 6 module composer.jsons carry fake package identity).
4. **`vite.stub` + `package.stub`** pin `vite ^4.0`, `laravel-vite-plugin ^0.7.5`, `axios`, `sass` — legacy pipeline assumptions producing the dead 0B `resources/assets/*` stubs (FND-044).
5. **`controller-inertia.stub`/`controller-api.stub`** produce empty CRUD + unrouted Inertia pages — source of FND-045 dead scaffold classes/pages.
6. **`handle-inertia-requests.stub`** shares `[]` — predates the shared-props contract (locale/theme/branding) — would silently produce modules lacking the established prop flow if enabled.
7. Content quality is otherwise standard nwidart boilerplate; no secrets, no executable logic (templates only).

## 9. FINDINGS REGISTER (cumulative)

| ID | Severity | Unit | Title | Evidence |
|---|---|---|---|---|
| FND-001 | MEDIUM | UNIT-001/003 | PHP version floor broken: `composer.json` allows `^8.3` but `config/database.php` uses `Pdo\Mysql` (PHP 8.4-only class) and AGENTS.md states PHP 8.4 — install on PHP 8.3 fatals. Fix: raise composer floor to `^8.4`. | composer.json:9; config/database.php:395,459-461; AGENTS.md:30 |
| FND-002 | MEDIUM | UNIT-001 | No JS lockfile anywhere (package-lock/pnpm/yarn absent on disk and untracked; `.npmrc` lacks `package-lock=false`) — unpinned JS supply chain, non-reproducible frontend builds. | `Test-Path` all false; package.json uses floating `^`/`||` ranges |
| FND-003 | LOW | UNIT-001 | Style authority conflict/dead config: `.styleci.yml` disables `no_unused_imports` (Pint laravel preset — project-mandated — removes them); StyleCI service has no in-repo CI evidence. | .styleci.yml; AGENTS.md pint rules; no pint.json, no `.github/` |
| FND-004 | LOW | UNIT-001 | Dead/stale committed content: empty `CLAUDE.md` (0 bytes); verbatim upstream `CHANGELOG.md`; `/.github export-ignore` for a nonexistent dir. | file reads; dir listing |
| FND-005 | MEDIUM | UNIT-002 | `vite-module-loader.js` is dead AND broken code: zero imports of `collectModuleAssetsPaths` in the repo, and it uses `__dirname` inside an ESM (`"type":"module"`) package — would throw ReferenceError if called. | grep: 0 consumers; package.json `"type":"module"` |
| FND-006 | MEDIUM | UNIT-002 | Test suite targets real MySQL landlord DB (`DB_DATABASE=multivendor`, `DB_CONNECTION=landlord` in phpunit.xml) — same name as the documented dev DB; no separate `_testing` database → dev-data destruction risk & external-DB test dependency. | phpunit.xml:27-33; README.md |
| FND-007 | LOW | UNIT-002 | Env contract drift: `.env.example` ships `DB_CONNECTION=sqlite`, README instructs `DB_CONNECTION=landlord`, phpunit uses `landlord` — fresh-copy onboarding produces a different connection topology than documented. | .env.example:25; README.md:71; phpunit.xml:27 |
| FND-008 | LOW | UNIT-002 | `pestphp/pest-plugin` in composer `allow-plugins` but Pest is not installed (PHPUnit project) — stale permission entry. | composer.json:99; composer.lock (no pest) |
| FND-009 | LOW | UNIT-002 | Redundant dual PSR-4 autoload for `Modules\`: broad `Modules/` map plus per-module `app/` maps — can resolve `Modules\*` classes outside `app/` unexpectedly. | composer.json:34-40 |
| FND-010 | INFO | UNIT-002 | `composer audit` clean — 0 known vulnerabilities across 122 locked packages. | `composer audit` output |
| FND-011 | LOW | UNIT-002 | Inertia shared-props contract weakly typed: `[key:string]: unknown` on every interface, `any` in `*.vue` shim, `status` typed `string` despite TenantStatus enum — conflicts with AGENTS.md §3 type-integrity rule. | env.d.ts:17,33,83,107 |
| FND-012 | LOW | UNIT-002 | README documents `DomainTenantFinder` but codebase ships custom `SaaSTenantFinder` — documentation/implementation drift. | README.md:33,52,117 vs app/TenantFinder/SaaSTenantFinder.php |
| FND-013 | LOW | UNIT-002 | `concurrently` devDependency unused — `composer dev` → `php artisan dev` (pao), no script references it. | package.json:18; composer.json:57-60 |
| FND-014 | — | (superseded) | — | — |
| FND-015 | MEDIUM | UNIT-003 | No `PrefixCacheTask`: cache default=database on default(landlord) connection with static `laravel-cache-` prefix → all tenant `cache()` calls share landlord `cache` table keyspace → cross-tenant cache collision/leakage risk unless `ScopePermissionCacheTask` globally re-prefixes. | config/cache.php:271,374; config/multitenancy.php:67-72 |
| FND-016 | LOW | UNIT-003 | Env contract drift (extends FND-007): config consumes `DB_LANDLORD_DRIVER`, `DB_TENANT_DRIVER`, `DB_LANDLORD_DATABASE`, `DB_TENANT_DATABASE`, `SESSION_CONNECTION`, `DB_CACHE_CONNECTION`, `DB_QUEUE_CONNECTION`, `MEDIA_DISK`, `MEDIA_*` — none documented in `.env.example`. | config/database.php:438-487 vs .env.example |
| FND-017 | LOW | UNIT-003 | No `landlord_users` password broker in auth config — landlord admin password reset unwired; confirm no reset UI exists or it silently fails. | config/auth.php:229-236 |
| FND-018 | MEDIUM | UNIT-003 | Media + storage not tenant-scoped: medialibrary `disk_name=public`, `prefix=''`, DefaultPathGenerator → `storage/app/public/{id}/` served at `/storage/*`; sequential IDs enumerable cross-tenant; local disk `serve=true`. Plus stale MediaLibraryPro references (`TemporaryUpload` not installed). | config/media-library.php:36,102,122,356; config/filesystems.php:33-48 |
| FND-019 | LOW | UNIT-003 | `tenant_artisan_search_fields=['id']` but README documents `--tenant=` by id/slug/domain — doc drift or custom handling (verify RebuildDatabasesCommand UNIT-023). | config/multitenancy.php:58-60; README.md:365 |
| FND-020 | LOW | UNIT-003 | Orphaned scaffolding: `stubs.enabled=false` + `stubs.path` points to vendor — the 82 committed `stubs/nwidart-stubs/*` files are dead weight; `paths.assets=public/modules` unused; module composer.json vendor/author = uncustomized 'nwidart'/'Nicolas Widart' defaults. | config/modules.php:37-40,114,277-280 |
| FND-021 | MEDIUM | UNIT-003 | Single permission model pair (`Modules\Access\Models\{Role,Permission}`) configured for BOTH landlord and tenant RBAC contexts + static shared cache key `spatie.permission.cache` on default store — cross-context leakage/confusion risk until connection traits verified (UNIT-011/017) and ScopePermissionCacheTask behavior confirmed. | config/permission.php:3-4,20-31,209; UNIT-011/017 pending |
| FND-022 | LOW | UNIT-003 | Dead tenant-side schema: tenant migrations create `cache`/`jobs` tables but queue+cache configs always resolve to the landlord connection → those tables are never written by framework services (verify no code targets them — UNIT-006). | config/queue.php:38-45; config/cache.php:295-301; tenant migrations |
| FND-023 | LOW | UNIT-003 | `queue.batching.database` / `queue.failed.database` fall back to `env('DB_CONNECTION','sqlite')` — if env unset, batch/failed tables resolve to a nonexistent sqlite file. | config/queue.php:105-127 |
| FND-024 | LOW | UNIT-004 | `HandleInertiaRequests` fabricates `roles:['Super Admin']`/`['Member']` + empty permissions when models lack `HasRoles`/`getAllPermissions` — hardcoded role names (AGENTS.md violation) + cosmetic privilege misrepresentation in shared props. | HandleInertiaRequests.php:54,65 |
| FND-025 | MEDIUM | UNIT-004 | `load($locale,'*','*')` serializes the **entire** translation catalog (all groups/namespaces) into every Inertia response — payload bloat on every navigation. | HandleInertiaRequests.php:120; ErrorPageRenderer.php:86 |
| FND-026 | LOW | UNIT-004 | `SetLocale` writes `session(['locale'])` on every request (even unchanged) → a DB-session write per request; `$request->get('locale')` also accepts POST body input. | SetLocale.php:22-32 |
| FND-027 | LOW | UNIT-004 | Dual-model-per-table: `App\Models\Tenant` & `App\Models\User` are alias subclasses of module models — the module parents remain independently instantiable/referenceable (README:403 documents a real failure: parent class breaks `Tenant::current()` return-type mid-switch). Verify no code still references parents (UNIT-023). | app/Models/Tenant.php:5-7; README.md:403 |
| FND-028 | LOW | UNIT-005 | `public/favicon.ico` is a 0-byte file — broken asset served to browsers; also `robots.txt` allows crawling of `/landlord/*` admin surface (INFO). | `Get-Item` Length=0; robots.txt |
| FND-029 | HIGH | UNIT-006 | Seeder creates tenant owner accounts with `Hash::make('password')` — a **hardcoded, publicly-known password** on ROLE_OWNER users (`admin@<domain>` AND `admin@<domain>.com` — two accounts). If `db:seed`/`tenants:artisan db:seed` runs in any shared/prod environment, every tenant has a predictable owner login. | database/seeders/DatabaseSeeder.php:70-95 |
| FND-030 | MEDIUM | UNIT-006 | Tenant DB credentials (`db_username`/`db_password`) stored **plaintext** on tenants rows (TenantSeeder copies env DB_USERNAME/DB_PASSWORD). Requires column existence; verify casts/encryption on Tenant model (UNIT-023). | TenantSeeder.php:44-55 |
| FND-031 | MEDIUM | UNIT-006 | Password-reset plumbing broken by connection mismatch: `password_reset_tokens` exists only in tenant DBs, but broker `users` has no `connection` key → `DatabaseTokenRepository` uses default (landlord) connection where the table does NOT exist; `landlord_users` has no broker at all. | config/auth.php:229-236; tenant migration 000000; landlord migrations (no such table) |
| FND-032 | MEDIUM | UNIT-006 | Permission schema drift: landlord `roles` has `is_system` (indexed), tenant `roles` does not — shared `Modules\Access\Models\Role` code cannot rely on `is_system` in tenant context; also landlord migration wraps cache-forget in try/catch, tenant does not. | landlord …_permission_tables vs tenant …_permission_tables |
| FND-033 | LOW | UNIT-006 | `catch (\Throwable) {}` silently swallows assignRole failures in tenant seeder; two near-duplicate owner emails created. | DatabaseSeeder.php:84-90 |
| FND-034 | LOW | UNIT-006 | Missing `down()` on landlord `tenants` + `media` migrations → irreversible (rollback/migrate:refresh breaks). migrate:fresh unaffected. | 2026_09_29_082217, …_082219 |
| FND-035 | LOW | UNIT-007 | `app.js` registers dual module-page globs (absolute `/Modules/*` + relative `../../Modules/*`) — one is unreachable dead fallback; doubles build-time FS scan. | resources/js/app.js:11-27 |
| FND-036 | LOW | UNIT-007 | Hardcoded `#6366f1` Inertia progress color in app.js — bypasses design-token system (AGENTS.md §6 design tokens). | resources/js/app.js:39 |
| FND-037 | MEDIUM | UNIT-011/012 | `system.allow_registration` shared to the UI (HandleInertiaRequests) but **never enforced server-side** — `TenantAuthController::register` → `TenantUserService::createUser` checks quota only. A disabled-registration tenant still accepts signups. | TenantAuthController.php:69-93; TenantUserService.php:51-56 |
| FND-038 | RESOLVED | UNIT-013 | `tenant.register.show` exists (Landlord `routes/web.php:17` → `/register-tenant` platform signup). Redirect is intentional cross-surface routing, not a loop. | — |
| FND-039 | MEDIUM | UNIT-013 | Confirmed: `routes/api.php` exposes scaffold `AccessController` under `auth:sanctum` — **Sanctum is not installed** → `/api/v1/accesses*` fatals (undefined guard). Dead+broken API surface reachable. | routes/api.php:7-9; composer.lock |
| FND-040 | LOW | UNIT-011 | Hardcoded status literals `in:active,inactive,suspended` (UserController:102) + `'Member'`/`'active'` fallback strings — verify vs UserStatus/TenantStatus enum SSoT. | UserController.php:102,41 |
| FND-041 | MEDIUM | UNIT-013 | **Confirmed: zero `throttle:`/`RateLimiter` anywhere** — login, register, and `/register-tenant` (public tenant provisioning!) are unthrottled → credential stuffing + DB-provisioning abuse vectors. | grep `throttle:` → 0 hits in app code |
| FND-044 | LOW | UNIT-013/018 | Scaffold debt pattern across modules: unused `config/config.php` name-stubs, empty `*DatabaseSeeder`s, stale `.gitkeep`s in populated dirs, dead per-module package.json/vite.config.js. | file reads |
| FND-045 | LOW | UNIT-018 | `CoreController` + 4 scaffold Core pages unrouted/dead (api.php empty) — same dead-scaffold class as FND-039 but unreachable (web routes don't reference it). | routes/web.php, routes/api.php |
| FND-042 | LOW | UNIT-012 | `AuditWriter` writes `AdminAuditLog` (landlord conn) — if invoked from tenant-context transactions it escapes atomicity; currently only landlord call sites seen (verify remaining usage UNIT-023/027). | AuditWriter.php; AdminAuditLog model pending |
| FND-043 | LOW | UNIT-012 | Users index: per-row `EffectivePermissionSet::of($target)` → `getAllPermissions()` — `roles.permissions` not eager-loaded (`with('roles')` only) → ~2 extra queries per row on member directory. | TenantUserService.php:31; UserController.php:44-47 |
| FND-046 | LOW-MEDIUM | UNIT-019 | `EnterpriseDataGrid` emits `search`/`update:searchQuery` on every keystroke (no debounce) → server-driven index pages issue an Inertia request per keystroke. | EnterpriseDataGrid.vue:445-449; Users/Index.vue:78-85 |
| FND-047 | LOW | UNIT-019 | `Modal.vue` toggles `document.body.style.overflow` per instance — scroll lock not reference-counted; concurrent modals corrupt each other's lock (violates AGENTS §1 shared-resource rule). | Modal.vue:50-58 |
| FND-048 | LOW | UNIT-019 | `LanguageSwitcher` hardcodes EN/AR buttons + `=== 'ar'` RTL check instead of the server-provided `locale.supported`/`is_rtl` map — locale-extensibility drift. | LanguageSwitcher.vue:12-34 |
| FND-049 | LOW | UNIT-019 | Internal inconsistency: `EnterpriseFormEngine`/`ConfirmDialog` hand-roll `<button>` markup instead of composing BaseButton; ConfirmDialog derives icon variant via CSS-class string matching. | FormEngine.vue:43-58; ConfirmDialog.vue:162-183 |
| FND-050 | LOW-MEDIUM | UNIT-021 | `locale.translations` shared prop ships the ENTIRE merged translation dictionary (`load($locale,'*','*')`) on every Inertia response — payload bloat that grows with the catalog; consider lazy/dict-per-page strategy. | HandleInertiaRequests.php:120 |
| FND-051 | RESOLVED | UNIT-021/038 | `ErrorPageRenderer.php:52` — `'exception' => config('app.debug') ? $e->getMessage() : null` — debug-gated; no production detail leak. | ErrorPageRenderer.php:52 |
| FND-052 | RESOLVED-CONTEXT | UNIT-015/022/038 | Module-local `tests/` dirs are empty BUT the root suite covers the same logic end-to-end (PlatformAdminManagementTest, TenantAccessControlTest, SettingsGovernanceTest, TenancySecurityTest exercise ManagementPolicy/Invariants/provisioning/registry). Not zero coverage — **mislocated coverage**: module internals have no unit tests, only feature-level. Downgrade to LOW: keep empty dirs or move tests into modules. | tests/*; Modules/*/tests |
| FND-067 | HIGH | UNIT-038 | **Test suite is not isolated**: no RefreshDatabase/LazilyRefreshDatabase anywhere; `phpunit.xml` points `DB_CONNECTION=landlord` at the real `multivendor` MySQL DB → every run mutates the dev database, depends on seeded fixtures (`tenant1/tenant2.localhost`, `vendor_1/2`, `admin@landlord.test`/`password`), can't run on a fresh clone or CI without pre-provisioning, and a crashed test leaves drift (settings toggles, admin rows). Tests are behaviorally excellent but architecturally non-portable. | phpunit.xml:20-34; all Feature tests |
| FND-053 | HIGH | UNIT-023 | `TenantRegistrationController` hardcodes `domain = subdomain.'.localhost'` — ignores `TENANT_DOMAIN_SUFFIX`/request host → self-registered tenants get `foo.localhost` in production and SaaSTenantFinder (`{slug}.{suffix}`) never matches → provisioned tenant unreachable; domain also diverges from admin path (`{slug}.{request-host}`). Secondary: `Rule::unique(domain)` compares raw subdomain against `foo.localhost` values → domain uniqueness never actually enforced. | TenantRegistrationController.php:71-76; SaaSTenantFinder |
| FND-054 | MEDIUM | UNIT-023 | `TenantController::extendTrial` unconditionally sets `status=Trialing` — bypasses the status machine: a suspended/archived tenant silently flips to trialing. No transition guards anywhere (TenantLifecycleService also permits any→any). | TenantController.php:146-156; TenantLifecycleService |
| FND-055 | LOW-MEDIUM | UNIT-023 | Provisioning/deletion are not transactional: `TenantProvisioner` leaves orphan DB+row+subscription on mid-failure; `TenantLifecycleService::delete` does sequential non-atomic deletes with swallowed drop errors. | TenantProvisioner.php:40-110; TenantLifecycleService.php:75-100 |
| FND-057 | LOW-MEDIUM | UNIT-024 | Public routes `/`, `/pricing`, `/register-tenant` are not host-restricted — reachable on tenant hosts; self-registration from a tenant host produces `slug.{tenant-domain}` nonsense domains. | routes/web.php (public block); TenantProvisioner.php:50-55 |
| FND-056 | LOW | UNIT-023 | `LandlordMetricsService` MRR computed via `->get()->sum()` — unbounded hydration of every active+trialing subscription row for one scalar aggregate; should be `sum('amount')` in SQL. | LandlordMetricsService.php |
| FND-058 | LOW-MEDIUM | UNIT-024 | Four module `api.php` files register `auth:sanctum` apiResources but **sanctum is not installed** — every `/api/v1/*` request would 500 on the undefined guard; combined with empty scaffold controllers, the entire API surface is dead scaffolding. | Modules/{Access,Landlord,Subscription,Tenant}/routes/api.php; composer.json |
| FND-059 | LOW | UNIT-025 | `Modules/Index.vue` hardcodes the locked-module list a second time (`coreModules`) duplicating `ModuleManagementController`'s array — will drift silently. | Modules/Index.vue:26; ModuleManagementController.php:52 |
| FND-060 | LOW-MEDIUM | UNIT-025 | `Tenants/Show.vue` delete dialog defaults `drop_database: true` — irreversible option pre-checked. | Tenants/Show.vue:~67 |
| FND-061 | MEDIUM | UNIT-025 | **Prop key mismatch**: `Dashboard.vue` reads `metrics.mrr` but `LandlordMetricsService::getMetrics()` emits `monthly_revenue` → MRR StatCard silently renders $0/N$0.00 — real data never displayed. | Dashboard.vue:81; LandlordMetricsService.php:~57 |
| FND-062 | LOW | UNIT-027 | `is_public` flag on settings is write-only — stored by `set()`, migrated, cast, but **no reader exists** (no public-settings endpoint/query). Dead surface. | SettingService.php:42; settings migrations |
| FND-063 | MEDIUM | UNIT-030 | `SubscriptionService`: yearly billing = `price * 10` hardcoded multiplier; `changePlan` rewrites plan_id+amount but leaves `billing_interval`/`currency`/`ends_at` stale → yearly subs on new plan get charged new monthly price while interval stays yearly. | SubscriptionService.php:30,65-72 |
| FND-064 | LOW-MEDIUM | UNIT-030 | `tenantOverview` reports `usage.storage_mb.current = 120` — hardcoded fake value rendered as real usage in the UI. | SubscriptionController.php:~95 |
| FND-065 | MEDIUM | UNIT-031/032 | `DELETE /landlord/plans/{plan}` routes to nonexistent `PlanController::destroy` — and `Plans.vue` exposes a working delete ConfirmDialog calling it → **user-reachable guaranteed 500**. Half-implemented feature both ends. | routes/web.php:14; PlanController.php; Plans.vue:104-112 |
| FND-066 | LOW | UNIT-032 | `LandlordSubscriptions.vue` doesn't wire `pagination` prop/`page-change` — server paginate(15) unreachable beyond page 1 in UI. | LandlordSubscriptions.vue; SubscriptionController.php:30 |
| FND-068 | LOW | UNIT-039–041 | Entire `stubs/nwidart-stubs/` tree (82 files) is dead — `config/modules.php` `stubs.enabled=false` points at vendor defaults; generators never load it. It is also the *generator source* of FND-020 (fake nwidart composer identity), FND-044 (dead asset stubs), FND-045 (empty scaffold controllers/pages), FND-058 (sanctum apiResource). | stubs/nwidart-stubs/*; config/modules.php:37-39 |

## 10. AUDIT STATUS

```
AUDIT STATUS: PHASE 2 COMPLETE — ALL 41 UNITS CLOSED; INSPECTION 100%
              (remediation NOT authorized — findings only)

TOTAL AUDITABLE FILES: 538 (tracked) — 0 skipped, 0 blocked
INSPECTED: 538 / 538 — 100%
UNINSPECTED: 0
SKIPPED: 0
BLOCKED: 0
EXCLUDED (documented, §2): .env, vendor/, node_modules/, public/build/, public/hot,
  public/fonts-manifest.dev.json, bootstrap/cache/*.php, .phpunit.result.cache,
  .notebook/, .git/, storage/** runtime contents

COVERAGE: INVENTORY 100% — INSPECTION 100% (538/538)
SECURITY: complete          TENANCY: complete         ARCHITECTURE: complete
DATABASE: complete          BACKEND: complete         FRONTEND: complete
PERFORMANCE: complete       TESTS: complete           SSOT: complete

CRITICAL: 0 | HIGH: 3 | MEDIUM: 15 | LOW-MEDIUM: 7 | LOW: 33 | RESOLVED: 2 | INFO: 1
OPEN QUESTIONS: 0 — all 10 resolved (OQ-1,2,3,4,5,8,10 prior;
  OQ-6 .env key-parity OK (superset of .env.example); OQ-7 FileActivator IS wired to
  modules_statuses.json → toggling honored; OQ-9 resolved → scaffold debt = FND-044/045/068)
```

**Audit complete. Awaiting user approval before any remediation (implementation gate per mandate).**

---

## 11. CONSOLIDATED FINAL REPORT (reconciliation pass)

### 11.1 Verified strengths (keep — these are the architecture's spine)

- **Settings governance** — `settings.php` registry + `SettingService` owner-context enforcement + `UpdateSettingsRequest` prohibited-key rejection + model-event cache invalidation. Enum-derived rules = true SSoT.
- **RBAC** — `LandlordPermissions`/`TenantPermissions` manifests as route constants; `ManagementPolicy` scope checks; `AccessInvariants` last-manager guard; `access:sync-* --repair` idempotent commands; audit trail asserted in tests.
- **Tenancy boundary** — `SaaSTenantFinder` rejects header/query hints and foreign parents; unknown host → 404 (web+JSON); suspended/archived → 423; `ScopePermissionCacheTask` isolates permission cache.
- **Dual guards** — `landlord` vs `web` separation consistent across routes/middleware/models.
- **Error surface** — unified `ErrorPageRenderer`, debug-gated exception detail, locale rebuild without middleware.
- **Test quality** — behavior-level matrices (escalation, cross-DB isolation, registry governance, invariants, event cache invalidation) — high-value coverage, wrong harness (see FND-067).

### 11.2 Findings — deduplicated clusters

| # | Cluster | Members | Tier |
|---|---|---|---|
| C1 | **Credential / secret hygiene** | FND-029 seeded `password` owners; FND-030 plaintext tenant DB creds | HIGH |
| C2 | **Domain resolution hardcoding** | FND-053 `.localhost` in controller + 3 UI sites; FND-057 unbound public routes | HIGH |
| C3 | **Test infrastructure** | FND-067 no isolation/real-DB suite; FND-006 same root cause | HIGH |
| C4 | **Dead/missing API surface** | FND-058 sanctum-less apiResources ×4; FND-039 same in Access; FND-065 missing `destroy`+UI caller | MEDIUM |
| C5 | **Tenant isolation soft spots** | FND-015 shared cache keyspace; FND-018 media/storage unscoped; FND-032 schema drift `is_system`; FND-042 audit-write escapes tenant txn | MEDIUM |
| C6 | **Auth completeness** | FND-041 zero throttling; FND-017 landlord reset unwired; FND-031 reset broker broken (conn mismatch); FND-037 member register unenforced | MEDIUM |
| C7 | **Billing/subscription integrity** | FND-063 `price*10` + stale interval on changePlan; FND-061 `mrr` prop mismatch; FND-056 unbounded MRR hydration; FND-064 fake `120` storage ×2; FND-066 pagination unwired | MEDIUM |
| C8 | **Lifecycle atomicity** | FND-055 provision/delete not transactional; FND-054 status-machine bypass | MEDIUM |
| C9 | **SSoT / config drift** | FND-001 PHP floor; FND-002 no JS lockfile; FND-003 dead styleci; FND-019 artisan-search docs drift; FND-020 nwidart identity; FND-024 fabricated shared-prop roles; FND-025 whole-catalog translations per response; FND-027 dual Tenant classes; FND-040 status-string literals; FND-048/059 locale/module-list duplication; FND-062 write-only `is_public` | MEDIUM/LOW |
| C10 | **Scaffold debt** | FND-004/005 dead vite loader; FND-044 0B assets; FND-045 unrouted scaffold pages/controllers; FND-068 **dead 82-file stub tree = root cause of C4/FND-020/044/045** | LOW (aggregated) |
| C11 | **UX/FE polish** | FND-046 search debounce; FND-049 raw buttons vs BaseButton; FND-050 `initTheme` listener leak; FND-060 drop_database default-on; misc literals | LOW |

**Distribution:** HIGH 3 · MEDIUM 15 · LOW-MEDIUM 7 · LOW 33 · RESOLVED 2 · INFO 1 · CRITICAL 0.

### 11.3 Remediation roadmap (approval-gated; order = risk × effort)

- **P0 — correctness/safety, small diffs:** C1 (env-driven seed creds + drop plaintext DB creds or encrypt), FND-065 (implement or remove destroy), FND-061 (prop key), FND-060 (default drop_database=false), FND-041 (throttle login/register/register-tenant), C2 (single `tenantDomainSuffix()` config source).
- **P1 — isolation & data integrity:** FND-067 (sqlite test conn + RefreshDatabase + factories instead of seeded fixtures), FND-015 (PrefixCacheTask or dedicated tenant store), FND-018 (tenant disk path/prefix), C8 (wrap provision/delete in txn + compensation), FND-031/017 (fix broker wiring or remove surface), FND-032 (align role schemas).
- **P2 — structural cleanup:** C4 (delete dead api.php routes or install Sanctum deliberately), FND-068 (enable-or-delete stub tree; fix api.stub if kept), FND-025 (route-scoped translations), FND-037 (decide member-register policy), FND-054 (transition guards via enum/state machine), C7 rest (real storage metric, fix changePlan recalculation).
- **P3 — polish:** literals→enums, debounce, BaseButton normalization, dead code sweep (66 .gitkeep + scaffold), `is_public` decision, docs-sync (FND-001/019).

### 11.4 Verdict vs target pillars

| Pillar | Status |
|---|---|
| Secure by design / tamper-resistant | ⚠ Partial — solid boundaries, but unthrottled auth, known-cred seeds, plaintext DB creds, broken reset |
| Tenant-isolated / least privilege | ✅ Core good; cache/media edges open |
| High performance | ⚠ N+1/hydration spots (MRR, translations payload, per-keystroke search) |
| Lightweight frontend | ⚠ Whole-catalog prop payload; otherwise token-disciplined |
| Maintainable / modular / HMVC | ✅ Modules clean; scaffold debt is the drag |
| Consistent / SSoT | ⚠ Drift: literals vs enums, dual Tenant, stub-vs-runtime, `mrr` key |
| Clean Arch / Clean Code / SOLID | ✅ Contracts+services+manifests disciplined; minimal layering, no resolver sprawl |
| Testable | ❌ Harness non-isolated (coverage itself is good) |

**Overall: strong skeleton, pre-production.** The gaps are fixable without new abstraction layers — all fixes land inside existing mechanisms, consistent with the no-resolver mandate.

**END OF AUDIT — 538/538 · 41/41 · 0 pending · 0 open questions.**


