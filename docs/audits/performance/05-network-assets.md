---
noteId: "44b57c90bdde11f19d227386d2fcdb80"
tags: []

---

# Phase 5 — Network & Asset Investigation

Method: measured the committed production build (`public/build/`, exists — contrary to initial static assessment), `public/hot`, blade template, vite config, manifest.

## Measured Bundle

| Asset | Size |
|---|---|
| `app-*.js` (entry) | 17.9 KB |
| `dist-*.js` (shared vendor: vue+inertia+pinia+lucide) | 183.6 KB |
| `app-*.css` | 61.0 KB |
| Largest page chunk (`Show-*.js`) | 18.6 KB |
| `EnterpriseDataGrid` chunk | 9.4 KB |
| **Total `public/build/assets`** | **~0.60 MB** |

Verdict: **healthy**. Pages are dynamically imported (manifest `isDynamicEntry` per page); lucide tree-shakes into per-icon ~1 KB chunks; no >500 KB chunk exists. The `dist` vendor chunk at ~184 KB is acceptable without manualChunks.

## Findings

### NA-01 — Stale `public/hot` file forces dev-server asset URLs
- **Severity:** Medium (dev-env) · **Confidence:** Proven
- **Evidence:** `public/hot` contains `http://[::1]:5173` — Laravel's Vite integration treats its presence as "dev server running" and emits `http://[::1]:5173/build/...` asset URLs. If Vite is not actually running, **every page loads zero CSS/JS** (blank unstyled shell) despite a valid build existing.
- **Impact:** confusing dev breakage; on any environment where `public/hot` is accidentally shipped, total asset failure.
- **Fixes:** delete `public/hot` when stopping `npm run dev`; add to `.gitignore`/deployment exclude. (Verify it's untracked: it is a Vite-generated marker.)

### NA-02 — Render-blocking font CSS + dead `Instrument Sans` pipeline
- **Severity:** Medium · **Confidence:** Proven — see FE-05 (`vite.config.js:19-23`, `app.blade.php:14-15`). `fonts-C9MNnjVw.css` + 6 woff/woff2 Instrument Sans files (~118 KB) are in the build but no CSS references them (`app.css` declares Cairo/Inter only). Bunny `<link>` is render-blocking; `preconnect` lacks `crossorigin`.
- **Fixes:** drop the bunny() plugin entry + generated assets; add `crossorigin` to the preconnect; consider `font-display: swap` (already via `display=swap`).

### NA-03 — `public/storage` symlink missing → media URLs 404
- **Severity:** Medium (affects `getFirstMediaUrl('logo')` consumer) · **Confidence:** Proven — `artisan about`: "public/storage ... NOT LINKED". Tenant logo URLs in `share()` branding resolve to `/storage/...` which 404 in dev (Windows: `storage:link` requires admin or `mklink`).
- **Fixes:** `php artisan storage:link`; on Windows dev use elevated prompt or `mklink /J`.

### NA-04 — Inertia devtools + boost browser-logs routes registered
- **Severity:** Low · **Confidence:** Proven — `route:list` shows `_inertia/devtools/entries*`, `_boost/browser-logs`. Dev-only packages; ensure `composer install --no-dev` in production so they never register.

### NA-05 — No HTTP caching/compression evidence in `.htaccess`/server layer
- **Severity:** Low · **Confidence:** Possible — `public/.htaccess` is the stock Laravel rewrite file (no `mod_expires`/`mod_deflate` rules). Vite assets are fingerprinted (immutable-safe), but nothing sets long `Cache-Control` for them; `php artisan serve` sends no compression. Production web server config is outside the repo — mark deployment-dependent.
- **Fixes:** serve `build/assets` with `Cache-Control: public, max-age=31536000, immutable` (fingerprints make it safe); enable brotli/gzip at the edge.

## Verified-healthy
- Code splitting works: 87 routes → ~40 lazy page chunks; entry is 18 KB.
- No images/fonts beyond the (unused) Instrument Sans set in `public/`.
- Session cookie config is standard (`http_only`, `lax`); `SESSION_ENCRYPT=false` keeps session payloads small.
- Broadcasting is `log` driver — no realtime overhead.
