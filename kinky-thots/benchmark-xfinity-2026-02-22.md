# Full Stack Benchmark Report
**Date**: 2026-02-22
**ISP**: Xfinity
**Immich Version**: v2.5.6
**Measured from**: Home server (on-box curl — simulates Linode's view of the tunnel)

---

## Network (Xfinity)

| Metric | Xfinity | T-Mobile |
|--------|---------|----------|
| Download (10MB) | 137.5 Mbps | — |
| Download (50MB) | 434.8 Mbps | — |
| Upload (10MB) | 29.1 Mbps | — |
| Upload (50MB) | 23.4 Mbps | — |
| Latency to 8.8.8.8 | 28ms avg | — |

---

## Infrastructure Timing Breakdown (DNS → TCP → SSL → TTFB)

| Service | DNS | TCP | SSL | TTFB | Total |
|---------|-----|-----|-----|------|-------|
| kinky-thots.xxx | 43ms | 88ms | 141ms | 377ms | 377ms |
| kinky-thots.xxx/api | 34ms | 74ms | 123ms | 257ms | 257ms |
| photos.kinky-thots.xxx | 34ms | 59ms | 107ms | 236ms | 236ms |
| owncast.kinky-thots.xxx | 50ms | 87ms | 137ms | 183ms | 183ms |
| Sonic CDN | 124ms | 161ms | 238ms | 451ms | — |

> Note: ~85-140ms of TTFB on main site / Immich is the SSH tunnel round-trip (Linode ↔ home server). Owncast is faster because it lives directly on Linode with no tunnel.

---

## Pages — HTML

| Page | Before (HTTP / TTFB) | After (HTTP / TTFB) | Delta |
|------|----------------------|---------------------|-------|
| / (homepage) | 200 / 471ms | 200 / 471ms | — |
| /landing/ | 200 / 423ms | 200 / 423ms | — |
| /login | **404** / 357ms | **200** / 338ms | ✅ Fixed |
| /register | **404** / 1502ms | **404** / — | ⚠️ register.html missing |
| /verify-email | **404** / 296ms | **200** / 310ms | ✅ Fixed |
| /reset-password | **404** / 371ms | **200** / 274ms | ✅ Fixed |
| /profile | **404** / 304ms | **200** / 331ms | ✅ Fixed |
| /members | **404** / 447ms | **200** / 371ms | ✅ Fixed |
| /subscriptions | **404** / 280ms | **200** / 323ms | ✅ Fixed |
| /checkout | **404** / 2389ms | **200** / 347ms | ✅ Fixed (+transient spike resolved) |
| /admin | **404** / 328ms | **200** / 341ms | ✅ Fixed |
| /live | **404** / 343ms | **200** / 246ms | ✅ Fixed |
| /terms | **404** / 344ms | **200** / 306ms | ✅ Fixed |
| /bustersherry | **404** / 288ms | **200** / 299ms | ✅ Fixed |
| /sissylonglegs | **404** / 283ms | **200** / 272ms | ✅ Fixed |

**Fix applied**: Added extensionless → `.html` rewrite rule to `.htaccess`
> ⚠️ /register still 404 — `register.html` does not exist (missing page, not a routing issue)

---

## Pages — PHP

| Page | Before TTFB | After TTFB | Delta |
|------|-------------|------------|-------|
| /landing/ | 423ms | 423ms | — |
| /gallery.php | **1532ms** | **258ms** | ✅ -1274ms (was transient spike) |
| /gallery (rewrite) | 302ms | 302ms | — |
| /2257.php | 384ms | 384ms | — |
| /dmca.php | 389ms | 389ms | — |
| /privacy.php | 628ms | 321ms | ✅ -307ms (was transient spike) |
| /cookies.php | **1895ms** | **321ms** | ✅ -1574ms (was transient spike) |
| /billing.php | **1014ms** | **304ms** | ✅ -710ms (was transient spike) |
| /free-content.php | 892ms | ~350ms | ✅ normalised |
| /plus-content.php | 608ms | ~350ms | ✅ normalised |
| /premium-content.php | 699ms | ~350ms | ✅ normalised |
| /bustersherry.php | 405ms | 329ms | ✅ normalised |
| /sissylonglegs.php | 851ms | 335ms | ✅ normalised |

> Initial benchmark caught several pages mid-spike (elevated CPU load 2.17 at time of measurement).
> Stable baseline is 250–400ms TTFB for all PHP pages on Xfinity.

---

## Static Assets

| Asset | HTTP | TTFB | Total | Size |
|-------|------|------|-------|------|
| /assets/dist/css/index.css | 200 | 427ms | 427ms | 8.5KB |
| /assets/dist/css/main.css | 200 | 429ms | 429ms | 7.5KB |
| /assets/dist/css/gallery.css | 200 | 431ms | 431ms | 12.1KB |
| /assets/dist/js/main.js | 200 | 639ms | 639ms | 2.3KB |
| /assets/dist/js/gallery.js | 200 | 423ms | 423ms | 7.4KB |
| /assets/dist/js/sissylonglegs.js | 200 | 254ms | 254ms | 1.2KB |

> Note: Static assets cached 7 days (immutable). All times include tunnel overhead (~250ms baseline).

---

## Backend API — Public Endpoints

| Endpoint | HTTP | TTFB | Total |
|----------|------|------|-------|
| /api/config | 200 | 262ms | 262ms |
| /api/subscriptions/tiers | 200 | 323ms | 323ms |
| /api/content | 200 | 242ms | 243ms |
| /api/gallery | 200 | 261ms | 261ms |
| /api/payments/status | 200 | 675ms | 675ms |
| /api/payments/currencies | 200 | 577ms | 577ms |
| /health | 404 | 590ms | 590ms |
| POST /api/auth/login | 403 | 5368ms | 5368ms |

> ⚠️ Login blocked externally by Turnstile (by design — not a bug).
> ⚠️ /api/payments/status and /currencies slow (575-675ms) — likely external API call.
> ⚠️ /health returning 404 — health check endpoint not registered.

---

## Backend API — Authenticated Endpoints

> Cannot benchmark externally — Cloudflare Turnstile required for login.
> Local login (port 3002) also enforces Turnstile — no bypass available.
> T-Mobile baseline needed for comparison when available.

---

## Immich (photos.kinky-thots.xxx)

| Endpoint | Xfinity | T-Mobile |
|----------|---------|----------|
| SSL Handshake | 141ms | — |
| Server Ping | 328ms | — |
| Login | 505ms | — |
| Timeline Buckets | 252ms | — |
| Albums | 364ms | — |
| Memories | 471ms | — |
| People | 309ms | — |
| Thumbnail preview (320KB) | 524ms | — |
| Thumbnail small (9.7KB) | 372ms | — |
| Original download (2.35MB) | 1228ms @ 1.8MB/s | — |
| Search (50 results) | 411ms | — |
| Upload 3MB photo | 2.75s @ 1.1MB/s | — |
| Upload 10MB photo | 5.32s @ 1.9MB/s | — |

---

## Owncast Streaming (owncast.kinky-thots.xxx)

| Endpoint | HTTP | TTFB | Total | Size |
|----------|------|------|-------|------|
| Homepage | 200 | 231ms | 403ms | 637KB |
| /api/status | 200 | 191ms | 191ms | — |
| /api/config | 200 | 238ms | 252ms | — |

**Stream Status**: Offline (expected — not actively streaming)
**Stream Title**: Kinky-Thots

> Owncast is fastest service (183ms TTFB) — lives directly on Linode, no tunnel overhead.

---

## CDN — Sonic S3 (Germany)

| File | Tier | Size | TTFB | DL Speed (1MB chunk) |
|------|------|------|------|----------------------|
| 017e36...mp4 | free | 10.6MB | 907ms | 863 KB/s |
| 1403229...mp4 | free | 6.6MB | 267ms | 2.16 MB/s |
| 20190821...mp4 | free | 18.8MB | 757ms | 1.0 MB/s |
| 24.4MB mp4 | free | 24.4MB | 274ms | 2.18 MB/s |

**CDN Latency (HEAD)**: DNS 120ms, TCP 164ms, SSL 284ms TTFB
> CDN speed varies 0.8–2.2 MB/s — inconsistent. Germany-hosted, US viewers will see higher latency.

---

## SSL Certificates

| Domain | Expires |
|--------|---------|
| kinky-thots.xxx | May 19, 2026 |
| photos.kinky-thots.xxx | May 19, 2026 |
| owncast.kinky-thots.xxx | May 9, 2026 |

---

## Server Resources (at time of benchmark)

| Resource | Value |
|----------|-------|
| CPU Load (1/5/15min) | 2.17 / 1.96 / 1.88 |
| Memory used | 3.0GB / 7.6GB |
| Memory available | 4.6GB |
| Swap used | 0B |
| Disk (/) | 89GB / 908GB (11%) |

---

## Issues Found & Resolved

| Severity | Issue | Status |
|----------|-------|--------|
| 🔴 High | HTML pages (login, profile, members, etc.) returning 404 | ✅ Fixed — added extensionless rewrite rule to `.htaccess` |
| 🔴 High | CSS cache buster using `date('YmdHi')` — busting every 60s, defeating 7-day immutable cache | ✅ Fixed — replaced with `filemtime()` in `includes/header.php` |
| 🟡 Medium | Page TTFB spikes (cookies 1895ms, gallery 1532ms, billing 1014ms) | ✅ Resolved — were transient spikes during high CPU load; stable baseline 250–400ms |
| 🟡 Medium | /checkout spike 2389ms | ✅ Resolved — transient; now consistently ~350ms |
| 🟡 Medium | /api/payments/status + /currencies slow (575-675ms) | ⚠️ Open — likely synchronous external API call |
| 🟡 Medium | /health returning 404 | ⚠️ Open — endpoint not registered in backend |
| 🟢 Info | /register returning 404 | ⚠️ Open — register.html does not exist |
| 🟢 Info | CDN throughput inconsistent (0.8–2.2 MB/s) | ⚠️ Open — Germany-hosted CDN, US latency expected |
| 🟢 Info | CPU load slightly elevated (2.17) at benchmark time | ✅ Resolved — normalised after Immich update settled |

---

## T-Mobile Column
> To be filled in when server returns to T-Mobile network. Re-run: `bash /tmp/benchmark.sh` (or recreate from this doc).
