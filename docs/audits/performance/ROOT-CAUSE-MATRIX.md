---
noteId: "a05d8970bdde11f19d227386d2fcdb80"
tags: []

---

# ROOT-CAUSE MATRIX

Canonical prioritized list of all findings. Evidence + reproduction in the phase files (`01`–`07`); fix detail in `PERFORMANCE-ROADMAP.md` / `QUICK-WINS.md`.

| Rank | ID | Root cause | Kind | Sev | Conf | Affected paths | Impact |
|---|---|---|---|---|---|---|---|
| 1 | BE-01 | DB driver for cache+session+queue on landlord conn | Architecture | Critical | Proven | every request | +10–15 SQL/req baseline |
| 2 | BE-02 | `SettingService` no per-request memoization | Implementation | High | Proven | every Inertia request | ~14–20 redundant SELECTs |
| 3 | FE-01 | Un-debounced grid search emit bound by all index pages | Implementation | High | Proven | `/users`, `/roles`, `/audit-logs`, `/landlord/tenants` | 1 request/keystroke |
| 4 | BE-05 | Sync listeners + notifications + webhook fan-out | Architecture | High | Proven | register-tenant, suspend/activate, all lifecycle events | SMTP + N inserts inline |
| 5 | BE-04 | `listUsers` unbounded + avatar N+1 + per-row EXISTS | Implementation | High | Proven | `/users` | ~4+U+K queries, full-table payload |
| 6 | DB-01 | 3 tables missing in live DB | Ops/drift | High | Proven | backups, usage, webhook deliveries | hard errors on those paths |
| 7 | BE-03 | `share()` lazy plan/media + per-request JSON reads | Implementation | Medium | Proven | every Inertia request | +2 queries + 3 file reads |
| 8 | BE-11 | Uncached config/routes/events + 6× event discovery | Ops | Medium | Proven-env | every request | ~hundreds ms boot overhead |
| 9 | BE-09 | `TenantController::show` ~15–20 queries + tenant switch | Implementation | Medium | Proven | `/landlord/tenants/{id}` | heaviest page |
| 10 | BE-08 | Live aggregates (`Media::sum`, `User::count`) per request | Implementation | Medium | Proven | tenant dashboard, subscription, /users | unbounded aggregate/req |
| 11 | AR-02 | share() monolith; zero lazy/deferred props | Architecture | Medium | Proven | all Inertia responses | full prop recompute on partial reloads |
| 12 | AR-03/DB-07 | Ambient DB connection on Role/Permission | Architecture | Medium | Proven | Access module queries | wrong-DB risk; cache re-warm |
| 13 | FE-02 | Unbounded `v-for`; no pagination contract | Implementation | Med-High | Proven | Users/Roles index | linear DOM + O(rows×keys) filter |
| 14 | BE-10 | Console commands: unbatched loops, per-row INSERTs | Implementation | Medium | Proven | lifecycle/usage/backup commands | N×queries+N mails per run |
| 15 | NA-01 | Stale `public/hot` marker | Ops | Medium | Proven | all pages (dev) | total asset failure when vite off |
| 16 | NA-02/FE-05 | Dead Instrument Sans pipeline; render-blocking font CSS | Implementation | Medium | Proven | every first paint | ~118 KB dead + blocking CSS |
| 17 | DB-03 | Missing FK constraints on billing cols | Integrity | Medium | Proven | plans/subscriptions/tenants | orphan risk |
| 18 | DB-04/05/06 | Missing composite indexes (audit, users.created_at, endpoints.active) | Schema | Low | Proven | audit/user/webhook lists | scans at scale |
| 19 | BE-06/07 | Admin index mirrors BE-04; 4×`fresh()` | Implementation | Low-Med | Proven | `/landlord/admins`, user update | per-row EXISTS; +3 selects |
| 20 | FE-03/04/07/08 | Per-navigation glob maps; per-modal listener; per-call RegExp/Intl | Implementation | Low | Proven | app.js, Modal, useI18n, useCurrency | GC churn, µs-level per call |
| 21 | DEP-01/02/03/04 | Floating majors; dead dep; pinned deps; tinker in prod | Deps | Low | Proven | build/deploy | reproducibility/surface |
| 22 | NA-05 | No immutable cache headers for fingerprinted assets (repo-level) | Ops | Possible | Possible | `build/assets` | repeat downloads |
| 23 | BE-12 | `shared_routes_cache=false` | Config | Low | Proven | only if route caching adopted | per-tenant route cache waste |
| 24 | BE-13 | `AuthenticateSession` per request; guard scope unverified | Config | Low | Proven | every web request | hash check overhead |
| 25 | DB-09 | Full tenant provisioning per test (~40 call sites) | Test infra | Medium | Proven | CI suite | migrations+seeders per test |
| 26 | FE-06 | No manualChunks | Build | Low | Proven | vendor chunk | minor cache blast radius |

## Confirmed non-problems
Tenant-domain/slug indexing; bundle size; Vue reactivity discipline; Inertia navigation usage; CSS token hygiene; permission-table indexing; session/cookie config.
