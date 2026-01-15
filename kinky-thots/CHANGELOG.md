# Changelog

All notable changes to Kinky-Thots are documented in this file.

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- PayPal one-time payment for Lifetime tier (Orders API v2)
- Email notifications for all payment events (7 templates)
- PayPal webhook signature verification
- SMTP configuration placeholders

### Changed
- Checkout flow now handles both recurring and one-time payments
- Lifetime tier uses one-time payment instead of subscription

---

## [1.5.1] - 2026-01-03

### Removed
- Empty directories: `assets/.qodo`, `.well-known`
- Deprecated CDN cache files: `cdn-files-zone-6295.json`, `cdn-video-cache.json`, `cdn-video-list.json`, `cdn-video-list.txt`

---

## [1.5.0] - 2026-01-03

### Added
- Benchmark #3 documentation with stress test results
- `immutable` directive to cache headers for static assets
- Font caching (1 year TTL)
- Video/audio caching (30 days TTL)
- AVIF image format support
- JSON/SVG/manifest compression

### Performance
- 1,965 req/sec sustained under stress (5000 requests, 100 concurrent)
- All pages under 2ms TTFB
- 64-81% gzip compression ratio

---

## [1.4.1] - 2026-01-01

### Security
- Removed hardcoded API key fallbacks from backend
- JWT_SECRET now required (server fails to start without it)
- Fail-fast on missing environment variables

---

## [1.4.0] - 2024-12-31

### Added
- Duration-based subscription tiers:
  - Free: Videos under 1 minute
  - Basic ($8/mo): Videos 1-5 minutes
  - Premium ($15/mo): Videos over 5 minutes
  - Lifetime ($250): Full access
- Three content pages: `free-content.php`, `basic-content.php`, `premium-content.php`
- Duration badges on video thumbnails
- Sort dropdown (shortest/longest/alphabetical)
- Content tier navigation with lock indicators
- Lock overlays with blur for unauthorized content

### Changed
- Replaced percentage-based content gating with duration-based access
- Updated navigation: "Media" dropdown renamed to "Content"
- Updated `subscriptions.html` with Lifetime toggle and savings calculation

### Removed
- `porn.php` and `porn.html` (replaced by tier-based content pages)

---

## [1.3.0] - 2024-12-31

### Added
- JWT-based user authentication with bcrypt password hashing
- User registration and login endpoints
- Password reset flow with email tokens
- User profile page with settings
- Chat moderation tools (ban, mute, slow mode)
- PayPal subscription integration (Basic, Premium tiers)
- Webhook handler for PayPal events
- `subscriptions.html` - Subscription plans page
- `checkout.html` - Payment checkout page
- `profile.html` - User profile and settings
- `reset-password.html` - Password reset page
- `src/js/auth.js` - Authentication manager
- `src/css/auth-modal.css` - Auth modal styles

### Fixed
- Gallery uploads not displaying (Docker volume path mismatch)

---

## [1.2.0] - 2024-12-30

### Added
- CDN sync-manifest command for video gallery automation
- Certbot and SSL module to Docker web container
- Letsencrypt volume for certificate persistence
- `docs/SITE_ARCHITECTURE.md` with flowcharts and diagrams
- Buster skills section with hover images

### Security
- Full security audit completed
- All sensitive directories protected via .htaccess

### Changed
- Cache-busting added to CSS links on model pages

---

## [1.1.0] - 2024-12-29

### Added
- Vite build system with hot reload
- Tailwind CSS integration
- ESLint and Prettier configuration
- ES6 module architecture with JSDoc annotations
- Modular CSS architecture:
  - `layout.css` - Header/Footer (all pages)
  - `landing.css` - Homepage sections
  - `media-gallery.css` - Photo/video grids
  - `content.css` - Text content pages
  - `live.css` - Streaming and chat

### Fixed
- Gallery lightbox not opening on mobile
- GIF animations incomplete on mobile
- Hamburger menu missing on several pages
- Lightbox mobile navigation

### Removed
- Legacy CSS files: `dropdown-nav.css`, `index.css`, `terms.css`, `gallery.css`, `porn.css`, `sissylonglegs.css`

---

## [1.0.0] - 2024-12-28

### Added
- Docker Compose setup with 4 services:
  - `kinky-web` - Apache/PHP web server
  - `kinky-backend` - Node.js chat/API server
  - `kinky-rtmp` - nginx-rtmp streaming
  - `kinky-db` - MariaDB database
- HLS auto-cleanup when stream ends
- Comprehensive mobile support
- Touch optimizations for gallery

### Infrastructure
- Apache2 with PHP 8.4
- MariaDB 10.11
- nginx-rtmp for RTMP to HLS conversion
- Pushr CDN integration (Sonic S3)

---

## Pre-1.0 Development

Early development phase before Docker containerization. Features included:
- Basic HTML/CSS/JS pages
- PHP gallery with password protection
- Initial streaming setup with Red5 (later replaced by nginx-rtmp)
- Basic chat functionality
