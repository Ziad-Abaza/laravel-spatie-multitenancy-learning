---
noteId: "f94e5b00bddd11f19d227386d2fcdb80"
tags: []

---

# Phase 2 — Frontend Investigation (Vue 3 / Inertia / Pinia / Vite / Tailwind)

Method: full read of all 55 Vue files (`Modules/*/resources/js/**` + `resources/js/app.js`), `resources/css/app.css` (422 lines), `resources/views/app.blade.php`, `vite.config.js`, `tsconfig.json`. Repo-wide greps for listeners, watchers, `usePage`, `router.*`, `<a href`, `JSON.parse`. Baseline timings in `01-executive-summary.md`.

## Findings

### FE-01 — Grid search fires one Inertia request per keystroke (debounce bypassed)
- **Severity:** High · **Confidence:** Proven
- **Evidence:** `Modules/Core/resources/js/Components/EnterpriseDataGrid.vue:80-88` — `onSearchInput` emits `update:searchQuery` **immediately** on every input; the 300 ms debounce only feeds the separate `search` emit (line 87). Every consumer binds the un-debounced emit: `Modules/Access/.../Pages/Users/Index.vue:163` → `handleSearch` → `router.get('/users', …)` (line 80-84); same pattern at `Modules/Access/.../Pages/Audit/Index.vue:91`, `Modules/Landlord/.../Pages/Audit/Index.vue:54`, `Modules/Landlord/.../Pages/Tenants/Index.vue:149`.
- **Root cause:** the debounced channel exists but no page consumes it — the debounce is dead code.
- **Impact:** N keystrokes = N server round-trips, each paying the full ~20-query middleware budget (Phase 3). On this dev server each request is ~930 ms.
- **Reproduction:** open `/users` on a tenant host, type "alice" in the grid search, observe 5 network requests.
- **Fixes:** Minimal — bind consumers to `@search` (debounced emit). Balanced — debounce inside `onSearchInput` for `update:searchQuery` too. Long-term — keep server-search opt-in and local-filter the loaded page.
- **Affected areas:** `EnterpriseDataGrid.vue`; Users/Audit/Tenants index pages.

### FE-02 — Unbounded `v-for`; unpaginated collections rendered whole
- **Severity:** Medium-High · **Confidence:** Highly Likely (server side proven unpaginated for users — Phase 3)
- **Evidence:** `Modules/Access/.../Pages/Users/Index.vue:160-161` passes `users` (plain array, `:total-count="users.length"`, no `pagination` prop) → `EnterpriseDataGrid.vue:246` renders all rows. `Modules/Access/.../Pages/Roles/Index.vue:65,85` unbounded `v-for` incl. nested permission chips. `processedData` (`EnterpriseDataGrid.vue:137-148`) additionally runs an `Object.values(item).some(...)` full-row scan per keystroke client-side even when server search is active.
- **Root cause:** no pagination contract between grid and server; local filter duplicates the server filter.
- **Impact:** DOM grows linearly with tenant user count (plan limit up to 9999 — `Modules/Subscription/database/seeders/PlanSeeder.php:76`); O(rows × keys) scan per keystroke.
- **Reproduction:** seed >200 tenant users, open `/users`.
- **Fixes:** Minimal — server-side pagination prop for `listUsers`. Balanced — `EnterpriseDataGrid` pagination contract enforced for server collections. Long-term — windowed/virtualized rows for >1k rows.
- **Affected areas:** Users/Roles/Tenants/Audit index pages, `EnterpriseDataGrid`.

### FE-03 — `import.meta.glob` maps re-created inside Inertia `resolve` on every navigation
- **Severity:** Low · **Confidence:** Proven
- **Evidence:** `resources/js/app.js:11-12` — both glob calls sit inside `resolve: (name) => {...}`; each navigation allocates two fresh glob maps (~40 lazy closures). Also `./Pages/**/*.vue` matches zero files (no `resources/js/Pages/` exists — dead glob feeding the `resolvePageComponent` fallback at line 23).
- **Root cause:** glob maps not hoisted to module scope.
- **Impact:** small per-navigation GC churn; no functional issue.
- **Fixes:** hoist both globs to module scope; drop the dead `./Pages/**` glob or add the dir.
- **Affected areas:** `resources/js/app.js`.

### FE-04 — Per-instance global `keydown` listener in `Modal` (attached even when hidden)
- **Severity:** Low-Medium · **Confidence:** Proven
- **Evidence:** `Modules/Core/.../Components/Modal.vue:49-57` — `onMounted` registers `window.addEventListener('keydown')` for every Modal/FormModal/ConfirmDialog instance regardless of visibility. `Users/Index.vue` mounts 3. Cleanup exists (no leak) but violates the project's own singleton-listener rule.
- **Impact:** one dispatch per keydown per mounted modal; scales with modal count.
- **Fixes:** attach only while `isVisible`, or route through `useScrollLock`-style shared composable.

### FE-05 — Dead font pipeline + doubled font loading
- **Severity:** Medium · **Confidence:** Proven
- **Evidence:** `vite.config.js:19-23` embeds `bunny('Instrument Sans')` but `resources/css/app.css` only declares `Cairo`/`Inter`; `app.blade.php:15` loads cairo/inter via render-blocking `<link>`; `preconnect` (line 14) lacks `crossorigin` (font fetch is cross-origin → preconnect partially wasted).
- **Impact:** wasted build artifact; render-blocking font CSS on first paint.
- **Fixes:** drop the unused bunny() entry; add `crossorigin` to preconnect.

### FE-06 — No vendor/manualChunks split (single vendor chunk)
- **Severity:** Low · **Confidence:** Proven (config); impact unverifiable — **no `public/build/` exists**
- **Evidence:** `vite.config.js:8-40` — no `build.rollupOptions.output.manualChunks`. Lazy page globs do give per-page chunks; `vue`+`inertia`+`pinia`+`lucide` share one vendor chunk with no cache hints.
- **Impact:** minor cache-invalidation blast radius; needs `npm run build` to quantify.

### FE-07 — `trans()` compiles `new RegExp` per placeholder per call
- **Severity:** Low · **Confidence:** Proven
- **Evidence:** `Modules/Core/.../Composables/useI18n.ts:24-30` — `new RegExp(':placeholder','g')` inside loop on every `trans()` call (used in `columns` computeds, e.g. `Users/Index.vue:69-76`).
- **Fixes:** cache RegExp per placeholder or use `split/join`/`replaceAll`.

### FE-08 — `useCurrency` builds `Intl.NumberFormat` per format call
- **Severity:** Low · **Confidence:** Proven
- **Evidence:** `Modules/Core/.../Composables/useCurrency.ts:14-20` — `new Intl.NumberFormat(...)` per cell render via `CurrencyCell`.
- **Fixes:** memoize per (locale, currency) — WeakMap/Map per AGENTS.md guidance.

## Verified-healthy areas (no finding)
- Navigation uses `<Link>`/`router.visit` correctly; no internal `<a href>` full reloads (`ErrorPage.vue:138` `window.location.reload()` is deliberate).
- No `deep: true` watchers, no prop/Pinia mutation, no leaked `setInterval`, no `JSON.parse` of large props.
- `app.css` is pure token/`@theme` CSS — zero `@apply`, no hardcoded colors. Healthy.
- Lucide imports are per-icon named imports (tree-shakeable ESM); no whole-library import found.
- `setupInertiaStateBridge` registers `router.on('navigate')` once — correct singleton pattern.
- Pages are lazy-loaded via `import.meta.glob` (correct code-splitting).
- `tsconfig.json` is strict; note `vue-tsc` is absent from `package.json` scripts — type check never enforced at build.

## Unproven / needs runtime
- Production bundle sizes (no `public/build` exists — run `npm run build` to measure).
- `lucide-vue-next@^1.0.0` in package.json — lucide's real line is 0.x; installed 1.0.0 per lockfile; tree-shaking assumed correct from ESM named imports.
