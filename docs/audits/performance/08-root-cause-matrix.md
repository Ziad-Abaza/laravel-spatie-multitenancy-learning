---
noteId: "777c4be0bdde11f19d227386d2fcdb80"
tags: []

---

# Phase 8 — Prioritized Root-Cause Matrix

Consolidation of Phases 2–7. Ranked by (severity × frequency × blast radius). "RC" = root cause; rows marked *symptom* are effects of a higher-ranked RC.

| # | ID | Finding | Type | Severity | Confidence | Effort |
|---|---|---|---|---|---|---|
| 1 | BE-01 | Database driver for cache+session+queue on landlord conn | RC (architectural) | Critical | Proven | Low (env) |
| 2 | BE-02 | `SettingService` no per-request memoization → ~14-20 cache SELECTs/req | RC (implementation) | High | Proven | Low |
| 3 | FE-01 | Grid search emits un-debounced event → Inertia request/keystroke | RC (implementation) | High | Proven | Trivial |
| 4 | BE-05/AR-04 | Sync listeners: mail + webhook fan-out inline in requests/jobs | RC (architectural) | High | Proven | Low-Med |
| 5 | BE-04 | `listUsers` unpaginated + avatar N+1 + per-row EXISTS + duplicate count | RC (implementation) | High | Proven | Low-Med |
| 6 | DB-01 | `tenant_backups`/`usage_records`/`webhook_deliveries` tables missing in live DB | RC (ops/drift) | High | Proven | Trivial |
| 7 | BE-03 | `share()` lazy-loads plan/media + re-reads translation JSON each request | RC (implementation) | Medium | Proven | Low |
| 8 | BE-11 | No config/route/event cache; 6× event discovery per request (dev; verify prod) | RC (ops) | Medium | Proven-env | Trivial |
| 9 | BE-09 | `TenantController::show` ~15-20 queries incl. tenant-DB diagnostics | RC (implementation) | Medium | Proven | Med |
| 10 | BE-08 | `QuotaService` aggregates (`Media::sum`, `User::count`) recomputed per request | RC (implementation) | Medium | Proven | Low |
| 11 | AR-02 | share() monolith — no lazy/deferred props anywhere | RC (architectural) | Medium | Proven | Med |
| 12 | AR-03 | Ambient connection on Role/Permission models | RC (architectural) | Medium | Proven | Med |
| 13 | FE-02 | Unbounded v-for / unpaginated index payloads | RC — consequence of BE-04 | Med-High | Proven | Med |
| 14 | BE-10 | Console commands: unbatched per-tenant loops, row-by-row inserts | RC | Medium | Proven | Low-Med |
| 15 | NA-01 | Stale `public/hot` → dev asset URLs despite built assets | RC (ops) | Medium | Proven | Trivial |
| 16 | NA-02/FE-05 | Dead Instrument Sans pipeline + render-blocking font CSS | RC | Medium | Proven | Trivial |
| 17 | DB-04/DB-05 | Missing composite indexes (audit action+created_at; users created_at) | Contributing | Low | Proven | Trivial |
| 18 | DB-03 | Missing FK constraints (tenants.plan_id, subscriptions.*) | Contributing (integrity) | Medium | Proven | Med |
| 19 | DB-06 | `webhook_endpoints.active` unindexed | Contributing | Low | Proven | Trivial |
| 20 | FE-03/04/07/08 | Glob maps per navigation; per-modal keydown; RegExp/Intl per call | Contributing | Low | Proven | Trivial |
| 21 | FE-06 | No manualChunks (184 KB vendor chunk — acceptable) | Minor | Low | Proven | Low |
| 22 | NA-03 | `public/storage` not linked → logo 404s | RC (ops) | Medium | Proven | Trivial |
| 23 | DEP-01/02/03/04 | Floating majors, dead dep, pinned deps, tinker in prod | Contributing | Low | Proven | Trivial |
| 24 | BE-12 | `shared_routes_cache` off (only matters if route caching adopted) | Contributing | Low | Proven | Trivial |
| 25 | BE-13 | `AuthenticateSession` per request; guard scope on landlord surface | Contributing | Low | Proven | — |
| 26 | DB-09 | Test suite: full tenant provisioning per test (~40 sites) | RC (CI perf) | Medium | Proven | Med |
| 27 | DB-07 | `Role`/`Permission` ambient-connection risk | same as AR-03 | Low | Proven | — |

## Causal chain (how the big items interact)

```
CACHE_STORE=database (BE-01)
   └─► every "cache hit" = landlord SELECT ──► amplified by SettingService
       lacking memoization (BE-02) ──► ~14-20 selects/request
                                              │
share() eager props (AR-02) ◄─────────────────┘
   └─► + plan/media lazy loads + JSON file reads (BE-03)
                                              │
FE-01 per-keystroke requests ─────────────────┘ multiply all of the above
        × N keystrokes × M concurrent users

Sync listeners (BE-05) add mail SMTP + webhook inserts to every mutation POST.

Missing tables (DB-01) → exceptions on webhook/backup paths → ErrorPageRenderer
rebuilds props → extra cost on the failure path.
```

## Verified-non-problems (do not "fix")
- tenants.domain/slug indexing — covered; tenant resolution is 1 indexed query.
- Bundle size — 0.6 MB total, 18 KB entry; healthy.
- Vue rendering discipline — no prop mutation, deep watchers, or listener leaks found.
- Internal navigation — consistently Inertia-based.
- CSS — pure design tokens, zero `@apply`.
