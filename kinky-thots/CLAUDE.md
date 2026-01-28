# Kinky-Thots Project Documentation

> **IMPORTANT**: Read this file before starting any work. Document completed work here for future sessions.

## Current Version: 1.9.2

See [CHANGELOG.md](./CHANGELOG.md) for detailed release notes.

### Version History (Summary)
| Version | Date | Highlights |
|---------|------|------------|
| 1.9.2 | Jan 28, 2026 | Email verification + Cloudflare Turnstile anti-bot |
| 1.9.1 | Jan 26, 2026 | Reverted to home server with SSH tunnel (lower latency than Linode) |
| 1.9.0 | Jan 26, 2026 | Temporary migration to Linode (reverted) |
| 1.8.0 | Jan 20, 2026 | Members page, private messaging (DM), admin nav link |
| 1.7.2 | Jan 15, 2026 | Inline crypto payments, password toggle, file cleanup |
| 1.7.1 | Jan 14, 2026 | Login page, lightbox fix, checkout redirect fix |
| 1.7.0 | Jan 14, 2026 | Linode reverse proxy, WireGuard VPN, removed localtonet |
| 1.6.0 | Jan 5, 2026 | Migrated from PayPal to NOWPayments (crypto) |
| 1.5.1 | Jan 3, 2026 | Cleanup unused files, CDN cache cleanup |
| 1.5.0 | Jan 3, 2026 | Benchmark #3, cache optimizations, immutable headers |
| 1.4.1 | Jan 1, 2026 | Security fixes, credential fallback removal |
| 1.4.0 | Dec 31, 2024 | Duration-based subscription tiers, content pages |
| 1.3.0 | Dec 31, 2024 | User auth, subscriptions, payment integration |
| 1.2.0 | Dec 30, 2024 | Security hardening, Docker SSL, CDN sync |
| 1.1.0 | Dec 29, 2024 | Vite build system, CSS architecture refactor |
| 1.0.0 | Dec 28, 2024 | Docker Compose setup, initial deployment |

### Versioning Policy
- **MAJOR** (X.0.0): Breaking changes, major rewrites
- **MINOR** (1.X.0): New features, significant additions
- **PATCH** (1.0.X): Bug fixes, cleanup, minor optimizations

## Tech Stack
- **Web Server**: Apache2 with PHP (Docker: kinky-web)
- **Backend**: Node.js WebSocket chat server on port 3002 (Docker: kinky-backend)
- **Database**: MariaDB 10.11 (Docker: kinky-db)
- **Streaming**: nginx-rtmp on Linode (45.79.208.9:1935) - direct HLS delivery
- **CDN**: Pushr CDN with S3-compatible storage (Sonic)
- **Build Tools**: Vite, Tailwind CSS, ESLint, Prettier
- **SSL**: Let's Encrypt via Linode reverse proxy (nginx + certbot)

### Infrastructure (Jan 26, 2026)
| Component | Server | IP | Services |
|-----------|--------|-----|----------|
| **Production** | Home Server | local | Docker: kinky-web, kinky-backend, kinky-db |
| **Reverse Proxy** | Linode (mail.kinky-thots) | 45.79.208.9 | nginx, SSL, mail, RTMP |
| **Tunnel** | SSH Reverse Tunnel (autossh) | localhost:8081, :3003 | Persistent connection to Linode |

## CDN Configuration (Pushr/Sonic)

### Pull Zones
| Name | ID | URL |
|------|----|----|
| images | 6292 | c5988z6292.r-cdn.com |
| videos | 6293 | c5988z6293.r-cdn.com |

### Push Zones
| Name | ID | URL | Endpoint | Bucket |
|------|----|----|----------|--------|
| my-images | 6294 | c5988z6294.r-cdn.com | https://s3.eu-central.r-cdn.com | 6406 |
| xxx-videos | 6318 | (Sonic S3) | https://s3.nvme.eu-central.r-cdn.com | 6318 |

### Push Zone Credentials
```
# my-images (6294)
Access Key: F7CLSY3KHFVQJYCOLYUNN
Secret Key: hDg5U1VPw1JHRkM1M1Q3VlU4M1mcTFEzUbbDVE45

# xxx-videos (6318) - Current video storage
Access Key: Z1Z2BU5WTNB6S28P6OW4M
Secret Key: TrwzRLw1U8NPS0g3hDKWNkxBw7ZSw8NYRcNZNFQ1
```

## Quick Commands

### Docker Services

#### Production (Home Server - local)
```bash
# View containers
docker ps

# View logs
docker logs -f kinky-web
docker logs -f kinky-backend
docker logs -f kinky-db

# Restart services
docker-compose restart

# Portainer Agent: http://localhost:9001
```

#### SSH Tunnel Management
```bash
# Check tunnel status
sudo systemctl status ssh-tunnel

# Restart tunnel
sudo systemctl restart ssh-tunnel

# View tunnel logs
journalctl -u ssh-tunnel -f

# Manual tunnel test
ssh root@45.79.208.9 "curl -s http://127.0.0.1:8081/ | head -1"
```

#### Development (optional - use docker-compose.dev.yml)
```bash
# Start dev containers (separate from production)
docker-compose -f docker-compose.dev.yml up -d

# Dev containers use -dev suffix: kinky-web-dev, kinky-backend-dev, kinky-db-dev
```

### CDN Management
```bash
cd /var/www/kinky-thots/backend

# Test CDN connection
npm run sonic:test

# List files on CDN
npm run sonic:list

# Sync video manifest from CDN (updates data/video-manifest.json)
npm run sonic:sync-manifest

# Upload file to CDN
npm run sonic:upload -- /path/to/file.mp4
```

### Video Optimization
```bash
cd /var/www/kinky-thots/scripts

# Preview what needs transcoding (no changes made)
./transcode-videos.sh --dry-run

# Transcode ALL videos (downloads, converts, uploads)
./transcode-videos.sh

# Transcode a single video
./transcode-videos.sh "IMG_0287.MOV"
```

**What it does:**
- Downloads videos from CDN
- Converts to web-optimized MP4 (H.264, CRF 23, faststart)
- Scales down videos larger than 1080p
- Uploads optimized version back to CDN
- Deletes original MOV files (replaced with .mp4)
- Updates video manifest

**Settings** (in script):
- `CRF=23` - Quality (18-28, lower = better quality, larger file)
- `PRESET=medium` - Speed/compression balance
- `AUDIO_BITRATE=128k` - Audio quality

**Expected results:**
- 70-90% file size reduction
- Faster video loading (faststart = instant playback)
- MOV files converted to MP4

### Development
```bash
# Start Vite dev server (hot reload)
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Lint JavaScript
npm run lint
npm run lint:fix

# Format code
npm run format

# Test Apache config
apache2ctl configtest

# View streaming logs
journalctl -u stream-watcher -u rtmp-hls -f

# Generate video thumbnails
/var/www/kinky-thots/scripts/generate-thumbnails.sh
```

## Directory Structure
```
/var/www/kinky-thots/
├── src/              # Source files (development)
│   ├── js/           # JavaScript modules (ES6+)
│   │   ├── main.js   # Navigation/dropdown handler
│   │   ├── index.js  # Homepage (imports landing.css)
│   │   ├── live.js   # Live streaming module
│   │   ├── gallery.js # Photo gallery (imports media-gallery.css)
│   │   ├── porn.js   # Video gallery (imports media-gallery.css)
│   │   ├── sissylonglegs.js # Model page (imports landing.css)
│   │   └── content.js # Content pages (imports content.css)
│   └── css/          # Modular CSS (Dec 2024 refactor)
│       ├── main.css  # Tailwind entry + imports layout.css
│       ├── layout.css # Header (nav) + Footer - ALL pages
│       ├── landing.css # Hero, About, Skills, Portfolio, Contact
│       ├── media-gallery.css # Photo/video grid, upload, lightbox
│       ├── content.css # Text pages (terms, etc.)
│       ├── live.css  # Live streaming + chat
│       └── chat.css  # Chat component styles
├── assets/           # Public assets
│   ├── dist/         # Built JS/CSS (from Vite)
│   ├── thumbnails/   # Video thumbnails
│   └── *.css/*.js    # Legacy assets (being migrated)
├── backend/          # Node.js backend (protected)
├── config/           # Configuration files (protected)
├── scripts/          # Shell scripts (protected)
│   ├── rtmp-to-hls.sh
│   ├── stream-watcher.sh
│   └── *.sh
├── hls/              # HLS stream output (public)
├── uploads/          # User uploads (protected)
├── logs/             # Application logs (protected)
├── data/             # Data files (protected)
├── backups/          # Backups (protected)
└── docs/             # Documentation (protected)
```

## Key Pages
- `index.html` - Homepage
- `login.html` - Standalone login/register page with redirect support
- `live.html` - Live streaming page (uses HLS.js + nginx-rtmp)
- `members.html` - Member list with private messaging (DM) feature
- `free-content.php` - Free videos (< 1 min) - open to all
- `basic-content.php` - Extended videos (1-5 min) - Basic+ subscribers
- `premium-content.php` - Full-length videos (> 5 min) - Premium/Lifetime subscribers
- `gallery.php` - Photo gallery (password protected)
- `subscriptions.html` - Subscription tiers with Lifetime toggle
- `checkout.html` - Payment checkout with crypto (NOWPayments)
- `profile.html` - User profile and settings
- `admin.html` - Admin dashboard (admin users only)
- `sissylonglegs.html` - Model page with skills hover images
- `bustersherry.html` - Model page with skills hover images
- `terms.html` - Terms & conditions

## Streaming Architecture (Linode)
RTMP streaming moved to Linode for better performance (Jan 25, 2026).

1. **OBS/Broadcaster** → `rtmp://45.79.208.9:1935/live/stream`
2. **nginx-rtmp on Linode** receives RTMP and converts to HLS
3. **HLS output** → `/var/www/hls/` on Linode
4. **live.html** fetches HLS directly from Linode (no VPN hop)

### OBS Settings
- Server: `rtmp://45.79.208.9/live`
- Stream Key: `stream`

### OBS Low-Latency Settings (Recommended)
- **Keyframe Interval**: 1 second (critical for low latency)
- **Rate Control**: CBR
- **Bitrate**: 3000-6000 Kbps
- **Encoder Preset**: veryfast or superfast
- **Tune**: zerolatency (if using x264)

## Security
- All sensitive directories protected via .htaccess (config/, backend/, scripts/, data/, logs/)
- Dotfiles blocked (`.env`, `.htaccess`, etc.) - returns 403
- Package files blocked (`package.json`, `docker-compose.yml`, `Dockerfile`)
- Security headers enabled:
  - X-Frame-Options: SAMEORIGIN
  - X-Content-Type-Options: nosniff
  - X-XSS-Protection: 1; mode=block
  - Referrer-Policy: strict-origin-when-cross-origin
- Directory listing disabled (Options -Indexes)
- PHP uses `htmlspecialchars()` for XSS prevention
- No hardcoded API keys in source code (environment variables only)
- `.env` files have 600 permissions

### SSL/HTTPS & Reverse Proxy
- **Linode Reverse Proxy** (45.33.100.131) handles all incoming traffic
- **WireGuard VPN** tunnel connects Linode to home server (CG-NAT bypass)
- **Let's Encrypt SSL** for all domains:
  - kinky-thots.com (redirects to .xxx)
  - kinky-thots.xxx (primary domain)
  - mail.kinky-thots.com (mail webui)

### Network Architecture (Updated Jan 26, 2026)
```
Internet
    │
    ▼
┌─────────────────────────────────────────────────────────┐
│  Linode: mail.kinky-thots (45.79.208.9)                 │
│  - nginx reverse proxy (SSL termination)                │
│  - Mail server (docker-mailserver)                      │
│  - RTMP streaming (nginx-rtmp → /var/www/hls/)          │
│  - SSH tunnel endpoints: localhost:8081, :3003          │
└─────────────────────────────────────────────────────────┘
                        ▲
                        │ SSH Reverse Tunnel (autossh)
                        │ Ports: 8081→80, 3003→3002
                        │
┌─────────────────────────────────────────────────────────┐
│  Home Server (Production)                               │
│  - Docker: kinky-web (Apache/PHP) :80                   │
│  - Docker: kinky-backend (Node.js) :3002                │
│  - Docker: kinky-db (MariaDB) :3306                     │
│  - Portainer Agent :9001                                │
│  - systemd: ssh-tunnel.service (persistent)             │
└─────────────────────────────────────────────────────────┘
```

| Component | IP/Address | Purpose |
|-----------|------------|---------|
| Linode Reverse Proxy | 45.79.208.9 | SSL, mail, RTMP |
| Home Server | localhost | Production Docker |
| SSH Tunnel | localhost:8081, :3003 | Persistent reverse tunnel |
| Tunnel Service | ssh-tunnel.service | Auto-restart on failure |

## Recent Changes (Jan 28, 2026)

### Email Verification + Cloudflare Turnstile (v1.9.2)
**Status: Complete and deployed**

Added two anti-bot security measures:
1. **Email verification** - New users must verify their email before accessing the site
2. **Cloudflare Turnstile** - Invisible CAPTCHA on login form

**Features:**
- Registration sends verification email with secure token link
- Users cannot login until email is verified
- Resend verification endpoint with 5-minute rate limiting
- Turnstile CAPTCHA validates on login (managed mode - Cloudflare decides when to challenge)
- New `/api/config` endpoint provides public site configuration

**Files changed:**
- `backend/server.js` - New endpoints: `/api/auth/verify-email`, `/api/auth/resend-verification`, `/api/config`
- `login.html` - Turnstile widget, verification pending UI, updated registration flow
- `verify-email.html` - New page for email verification
- `docker-compose.yml` - Added `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` env vars
- `.env` - Turnstile credentials configured

**Database note:** Existing users were set to `email_verified = TRUE` to allow continued login.

---

## Recent Changes (Jan 26, 2026)

### Reverted to Home Server with SSH Tunnel (v1.9.1)
After benchmarking, reverted production back to home server. The Mac Mini performs better than Linode for this workload. Replaced WireGuard VPN with SSH reverse tunnel for lower overhead.

**SSH Tunnel Setup:**
- Service: `/etc/systemd/system/ssh-tunnel.service`
- Tool: autossh (auto-reconnect on failure)
- Tunneled ports:
  - `localhost:80` → Linode `localhost:8081` (web)
  - `localhost:3002` → Linode `localhost:3003` (API)
- nginx on Linode proxies to tunneled localhost ports

**Commands:**
```bash
# Check tunnel status
sudo systemctl status ssh-tunnel

# Restart tunnel
sudo systemctl restart ssh-tunnel

# View logs
journalctl -u ssh-tunnel -f
```

**Files:**
- `docker-compose.yml` - Production containers
- `docker-compose.dev.yml` - Development containers (optional)

---

### Previous: Temporary Migration to Linode (v1.9.0 - Reverted)
Migrated production infrastructure from Mac Mini (home server) to dedicated Linode instance. Reverted after benchmarks showed home server performs better.

**New Production Server (Linode):**
- **Label**: kinky-thots-web
- **IP**: 50.116.47.91
- **Type**: g6-standard-1 (2GB RAM, 1 vCPU, 50GB disk)
- **Region**: us-southeast (same as mail server)
- **OS**: Ubuntu 24.04

**Architecture Changes:**
- Production Docker containers now run on Linode (kinky-web, kinky-backend, kinky-db)
- Mac Mini repurposed as development server (kinky-web-dev, kinky-backend-dev, kinky-db-dev)
- nginx reverse proxy updated to route traffic to production Linode instead of WireGuard VPN
- Portainer Agent installed on both servers for container management
- WireGuard VPN retained for development access

**Migration Steps Performed:**
1. Created new Linode instance (g6-standard-1, us-southeast)
2. Installed Docker and Docker Compose
3. Transferred all source files, configuration, and Docker build contexts
4. Exported and imported MariaDB database (4 users, all tables)
5. Transferred uploads directory (114MB)
6. Updated nginx config on mail Linode (`10.100.0.2` → `50.116.47.91`)
7. Renamed local containers to `-dev` suffix
8. Installed Portainer Agent on production server

**Files Updated:**
- `docker-compose.yml` - Container names changed to `-dev` on Mac Mini
- `docs/.linode-api.md` - Added new production server details

---

## Recent Changes (Jan 20, 2026)

### Members Page with Private Messaging
Created `members.html` - a member list page with DM (Direct Messaging) feature:

**Features**:
- Lists all registered users with username, avatar, subscription tier, and "last seen" timestamp
- Search input to filter members by username
- Tier filter dropdown (All, Free, Basic, Premium, Lifetime, VIP)
- Responsive card grid layout
- DM button on each member card
- Lock icon on DM button for free users (subscription required)

**Access Control**:
- All registered users can VIEW the member list
- Only subscribers (Basic+) can USE the DM feature
- Free users see upgrade prompt when clicking DM

**DM Modal**:
- Full-screen modal with recipient info header
- Scrollable message history with sent/received bubbles
- Real-time message notifications via WebSocket
- Messages persisted in database for offline reading

### Database Migration
**New file:** `docker/db/migrate-003-add-private-messages.sql`

```sql
CREATE TABLE private_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Backend API Endpoints
Added to `backend/server.js`:

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/members` | GET | JWT | List members with pagination, search, tier filter |
| `/api/messages/conversations` | GET | JWT + Subscriber | List user's DM conversations |
| `/api/messages/conversation/:userId` | GET | JWT + Subscriber | Get messages with specific user |
| `/api/messages/send` | POST | JWT + Subscriber | Send a private message |
| `/api/messages/read/:userId` | POST | JWT + Subscriber | Mark messages as read |
| `/api/messages/unread-count` | GET | JWT | Get unread count for nav badge |

**Middleware added**:
- `requireSubscriber` - Gates DM endpoints to Basic+ subscribers
- `messageLimiter` - Rate limits message sending (30/minute)

**WebSocket Extension**:
- Added `dmClients` Map to track connected users by userId
- `notifyNewDM()` function sends real-time notifications to online recipients
- New message type `new_dm` for client-side handling

### Admin Dashboard Navigation
Added conditional Admin Dashboard link to navigation across all pages:
- Hidden by default (`style="display:none;"`)
- Shown only when `user.is_admin` is true in localStorage
- Pink highlight color (`#f805a7`) for visibility
- Links to `/admin.html`

**Pages updated** with admin nav link:
- index.html, login.html, live.html, profile.html, members.html
- subscriptions.html, checkout.html, terms.html
- sissylonglegs.html, bustersherry.html, admin.html

### Navigation Updates
Added "Members" link to Account dropdown on all pages.

---

## Recent Changes (Jan 15, 2026)

### Inline Crypto Payments
Replaced redirect-to-NOWPayments flow with inline payment on checkout page:

**Backend endpoints added** (`backend/server.js`):
- `POST /api/payments/create` - Create payment with user-selected currency
- `GET /api/payments/:paymentId` - Poll payment status
- `GET /api/payments/min-amount/:currency` - Get minimum payment amount

**Checkout page updates** (`checkout.html`):
- Currency selector (BTC, ETH, USDT TRC20, LTC, XMR, SOL)
- QR code display for easy mobile scanning
- Copy-to-clipboard for payment address
- Memo/tag warning for currencies that require it
- Real-time status polling (waiting → confirming → confirmed → finished)
- Countdown timer for fixed-rate payment expiration (~20 min)
- User never leaves the site during payment

### Password Visibility Toggle
Added eye icon to show/hide password on login page (`login.html`):
- Login form password field
- Register form password field
- Register form confirm password field
- SVG icons (open/closed eye) with hover effect

### File Cleanup
Removed unused files and directories:
- `/dist/` - Old Vite build (using `/assets/dist/` instead)
- `/.test/` - Empty directory
- `/.hls/` - Empty directory (using `/hls/`)
- `test-paths.php` - Diagnostic test file
- `backend/add-durations.js` - One-time utility script
- `backend/uploads/` - Old duplicate uploads (~25MB freed)

### Pending TODO Items
**NOWPayments Enhancements:**
- Add min payment amount validation to checkout
- Add estimated crypto price preview to checkout
- Build admin payments dashboard (list all transactions)
- Add re-deposit tracking (parent_payment_id in webhooks)
- Enable wrong-asset auto-processing in NOWPayments dashboard
- Add fee transparency to payment receipts

**Other:**
- Create Python Flask webmail page for site

---

## Recent Changes (Jan 14, 2026)

### Linode Reverse Proxy Migration
Replaced localtonet with self-hosted Linode reverse proxy:

- **WireGuard VPN**: Secure tunnel from Linode to home server (bypasses CG-NAT)
- **nginx on Linode**: Handles SSL termination and proxies to home server
- **Let's Encrypt**: SSL certificates for all domains (auto-renewal via certbot)
- **Cost Savings**: ~$10-15/mo (localtonet) → ~$5/mo (Linode Nanode)

**Files Created**:
- `docs/DNS-RECORDS.md` - DNS configuration for both domains
- `/etc/wireguard/wg0.conf` - WireGuard client config (home server)

**Services on Linode** (45.33.100.131):
- nginx reverse proxy (ports 80, 443)
- WireGuard server (port 51820/udp)
- Mail server (docker-mailserver)
- Mail webui (Flask/gunicorn on port 8080)

**Domains Configured**:
| Domain | Purpose |
|--------|---------|
| kinky-thots.xxx | Primary website |
| kinky-thots.com | Redirects to .xxx |
| mail.kinky-thots.com | Mail server webui |

### Mail Server SSL Fix
- Updated docker-mailserver from self-signed to Let's Encrypt certificates
- Changed `SSL_TYPE=self-signed` to `SSL_TYPE=letsencrypt` in docker-compose.yml
- Mounted `/etc/letsencrypt` volume for certificate access
- IMAP/SMTP now use trusted SSL (no security warnings)

### Mail Server Credentials
- Webui: https://mail.kinky-thots.com (admin / REDACTED_OLD_MAIL_PASSWORD)
- IMAP/SMTP: admin@kinky-thots.com (same password)

### Bug Fixes (Jan 14, 2026)

**Lightbox Image Error**:
- Fixed "Lightbox image failed to load: gallery.php" error
- Cause: Empty `src=""` attribute caused browser to load page URL as image
- Fix: Removed `src` attribute from lightbox img element in `gallery.php`
- Updated cache busting versions for `gallery.js` and `media-gallery.css`

**Checkout Login Redirect**:
- Fixed login link on checkout page redirecting to `live.html` instead of login page
- Checkout now links to `/login.html?redirect={checkout_url}`
- After login/register, user is redirected back to checkout with tier preserved

**New Login Page** (`login.html`):
- Standalone login/register page (previously only modal on live.html)
- Supports `?redirect=` parameter for post-login navigation
- Tab-based UI for Login, Register, and Forgot Password forms
- Both login and register now honor redirect parameter

---

## Recent Changes (Dec 31, 2024)

### Subscription System Redesign - Duration-Based Tiers
Replaced percentage-based content gating with duration-based access:

| Tier | Price | Video Access | Duration |
|------|-------|--------------|----------|
| Free | $0 | Teasers | < 1 min |
| Basic | $8/mo | Extended | 1-5 min |
| Premium | $15/mo | Full Access | > 5 min |
| Lifetime | $250 one-time | Full Access | > 5 min |

**Backend Changes** (`backend/server.js`):
- Updated `SUBSCRIPTION_TIERS` with `maxDuration` instead of `contentAccess` percentage
- Added `getTierMaxDuration()` and `canAccessVideo()` helper functions
- Lifetime tier added with `isLifetime: true` flag

**New Content Pages**:
- `free-content.php` - 14 videos under 1 minute (open access)
- `basic-content.php` - 4 videos 1-5 minutes (locked for free users)
- `premium-content.php` - 3 videos over 5 minutes (locked for free/basic)

Each page features:
- Duration badges (MM:SS) on each video thumbnail
- Sort dropdown (shortest/longest/alphabetical)
- Content tier navigation bar with lock indicators
- Lock overlays with blur for unauthorized tiers
- Upgrade CTAs linking to subscriptions page

**Subscriptions Page** (`subscriptions.html`):
- Redesigned with 3 tier cards side-by-side
- Premium card has Monthly/Lifetime toggle switch
- Shows savings calculation when Lifetime selected ("Save $430+ vs 3 years")
- Updated FAQ for duration-based tiers

**CSS Updates** (`media-gallery.css`):
- Added `.duration-badge` - bottom-right timestamp overlay
- Added `.duration-badge.long` - gradient for videos 10+ min
- Added `.exclusive-badge` - top-left "Premium" label
- Added `.user-tier-badge.lifetime` - gold gradient styling

**Navigation Updated** (all pages):
- Changed "Media" dropdown to "Content"
- Links: Free Teasers, Extended Videos, Full Access, Photo Gallery, Live Cam
- Added user auth dropdown with Account menu

**Video Manifest** (`data/video-manifest.json`):
- Added `duration_seconds` field to all 21 videos
- Probed from CDN using S3 presigned URLs + ffprobe
- Distribution: 14 free, 4 basic, 3 premium

**Removed**:
- `porn.php` and `porn.html` - deleted (replaced by content tier pages)

---

### User Authentication System
- Implemented full JWT-based authentication with bcrypt password hashing
- Created `src/js/auth.js` - AuthManager class for login/register/logout
- Created `src/css/auth-modal.css` - Modal styles, chat badges, user menu
- Added auth modal HTML to `live.html` with login/register forms
- Updated `src/js/live.js` with WebSocket JWT authentication
- Backend endpoints: `/api/auth/register`, `/api/auth/login`, `/api/auth/me`, `/api/auth/profile`

### Password Reset Flow
- Added nodemailer for email sending (requires SMTP config in .env)
- Endpoints: `/api/auth/forgot-password`, `/api/auth/reset-password`, `/api/auth/change-password`
- Created `reset-password.html` - Standalone password reset page
- Uses secure SHA256-hashed tokens with 1-hour expiration
- Added forgot password form to auth modal

### Chat Moderation Tools
- VIP tier users automatically get moderator access
- Moderation commands: `/ban`, `/unban`, `/mute`, `/unmute`, `/slow`, `/clear`
- Moderation state managed server-side (bannedUsers, mutedUsers, slowModeSeconds)
- Mod action messages styled in `auth-modal.css`

### Content Gating by Subscription Tier
- **UPDATED**: Now uses duration-based access (see "Subscription System Redesign" above)
- Tier access: free (<60s), basic (60-300s), premium (>300s), lifetime/vip (all)
- Subscription tiers configuration in `backend/server.js` with `maxDuration` field
- API endpoints: `/api/subscriptions/tiers`, `/api/content`, `/api/content/:id/access`
- Created `subscriptions.html` - Subscription plans page with Lifetime toggle
- Created `checkout.html` - Checkout flow (PayPal placeholder)
- Created 3 content pages: `free-content.php`, `basic-content.php`, `premium-content.php`
- Added locked content CSS to `media-gallery.css` (blur overlay, lock icon, upgrade buttons)

### Security Audit & Hardening
- Removed hardcoded API key fallbacks from `backend/server.js`
- JWT_SECRET now required (server fails to start without it)
- Added JWT_SECRET and JWT_EXPIRES_IN to docker-compose.yml
- Removed credential fallbacks - fail-fast on missing env vars

### New Files Created
- `src/js/auth.js` - Authentication manager
- `src/css/auth-modal.css` - Auth modal and chat badge styles
- `reset-password.html` - Password reset page
- `subscriptions.html` - Subscription tiers page
- `checkout.html` - Payment checkout page
- `profile.html` - User profile and settings page

### User Profile Page
- Created `profile.html` with account info display
- Shows: username, email, member since, last login, chat color, subscription tier/status
- Color picker to customize chat color (uses `/api/auth/profile`)
- Password change form (uses `/api/auth/change-password`)
- Subscription status with upgrade CTA for non-premium users
- Cancel subscription button for paid tiers
- Added user dropdown menu to nav on: subscriptions.html, checkout.html, profile.html
- Dropdown shows: My Profile, Subscription, Logout links

### NOWPayments Crypto Payment Integration
**Note**: PayPal was removed due to policy restrictions on adult content. NOWPayments provides crypto payments with explicit adult industry support.

- Added NOWPayments API configuration in `backend/server.js`
- Environment variables:
  - `NOWPAYMENTS_API_KEY` - API key from NOWPayments dashboard
  - `NOWPAYMENTS_IPN_SECRET` - IPN secret for webhook verification
  - `NOWPAYMENTS_SANDBOX` - Set to 'true' for sandbox mode
  - `NOWPAYMENTS_EMAIL` - Account email for JWT auth
  - `NOWPAYMENTS_PASSWORD` - Account password for JWT auth
  - `NOWPAYMENTS_BASIC_PLAN_ID` - Basic subscription plan ID (1682032527)
  - `NOWPAYMENTS_PREMIUM_PLAN_ID` - Premium subscription plan ID (381801900)
- Backend endpoints:
  - `GET /api/payments/status` - Check NOWPayments API connection
  - `GET /api/payments/currencies` - List available cryptocurrencies (227+)
  - `POST /api/subscriptions/checkout` - Create subscription/invoice, returns payment URL
  - `POST /api/nowpayments/webhook` - Handle IPN callbacks
  - `POST /api/subscriptions/cancel` - Cancel subscription
- **Recurring Subscriptions** (Jan 5, 2026):
  - Basic ($8/31 days) and Premium ($15/31 days) use NOWPayments Subscription API
  - JWT authentication for subscription creation (token cached 4 min)
  - Lifetime uses 3-year plan (NOWPayments doesn't support true one-time)
  - Plan redirect URLs configured in NOWPayments dashboard
- Updated `checkout.html` with:
  - Crypto payment UI (BTC, ETH, USDT icons)
  - "Pay with Crypto" button
  - Redirect to NOWPayments payment page
  - Handles success/failed/partial return from payment
- Webhook handles: waiting, confirming, confirmed, finished, failed, expired, refunded
- IPN signature verification using HMAC-SHA512

### Bug Fixes (Dec 31, 2024)
- **Gallery uploads not displaying**: Fixed Docker volume path mismatch in `docker-compose.yml`
  - Backend writes to `/uploads` (via `path.join(__dirname, '../uploads')`)
  - Volume was mounted at `/var/www/kinky-thots/uploads` instead of `/uploads`
  - Fixed line 37: changed `./uploads:/var/www/kinky-thots/uploads` to `./uploads:/uploads`

### Deferred Features (For Future Implementation)
- **Stream Notifications**: Web push notifications when stream goes live
- **PWA Support**: Offline caching, install prompts, manifest.json

### Shelved Features (For Future Implementation)
- **Video Features**: Like/save, watch history, recommendations
- **Admin Dashboard Expansion**: Content moderation, analytics (basic admin page exists)
- **Chat Enhancements**: Emojis, mentions, message reactions

---

## Recent Changes (Dec 30, 2024)

### CDN Sync Feature
- Added `sync-manifest` command to sonic-cli.js for syncing video list from CDN
- Added `getCdnBaseUrl()` method to sonic-s3-client.js
- Content pages update automatically when running `npm run sonic:sync-manifest`

### Buster Skills Section
- Added `buster-skills` class to bustersherry.html for separate hover images
- Added Buster-specific hover image CSS in `src/css/landing.css`

### Security Audit & Fixes
- Removed hardcoded API key fallbacks from `backend/server.js`
- Added certbot and SSL module to Docker web container
- Added letsencrypt volume for certificate persistence
- Conducted full security audit - all sensitive files properly protected

### Documentation
- Created `docs/SITE_ARCHITECTURE.md` with:
  - ASCII flowcharts for site architecture
  - Feature map and request flow diagrams
  - Vite/Tailwind build workflow guide
  - Comprehensive troubleshooting guide
- Updated all docs to reflect nginx-rtmp instead of Red5

### Model Pages
- Converted sissylonglegs.php to sissylonglegs.html (removed PHP directory scanning)
- Fixed mosaic gallery layout with proper CSS column properties
- Added cache-busting to CSS links (`?v=20241230`)

---

## Recent Changes (Dec 29, 2024) - CSS Modularization

### CSS Architecture Refactor
Reorganized CSS into modular, reusable files:

| File | Purpose | Used By |
|------|---------|---------|
| `layout.css` | Header (nav) + Footer | All pages (via main.css) |
| `landing.css` | Hero, About, Skills, Portfolio, Contact | index, sissylonglegs, bustersherry |
| `media-gallery.css` | Photo/video grid, upload, lightbox, duration badges | gallery.php, *-content.php |
| `content.css` | Text content pages | terms.html |
| `live.css` | Live streaming + chat | live.html |

### Page → CSS Mapping
| Page | CSS Files Loaded |
|------|------------------|
| index.html | main.css + index.css |
| sissylonglegs.html | main.css + index.css + media-gallery.css |
| bustersherry.html | main.css + index.css + media-gallery.css |
| gallery.php | main.css + media-gallery.css |
| free-content.php | main.css + media-gallery.css |
| basic-content.php | main.css + media-gallery.css |
| premium-content.php | main.css + media-gallery.css |
| live.html | main.css + live.css |
| members.html | main.css (inline styles) |
| subscriptions.html | main.css (inline styles) |
| profile.html | main.css (inline styles) |
| admin.html | main.css (inline styles) |
| terms.html | main.css + content.css |

### Bug Fixes (Dec 29, 2024)
- **Gallery lightbox**: Fixed not opening on mobile/laptop - replaced inline onclick with event delegation
- **GIF animations**: Fixed incomplete animation on mobile - removed `loading="lazy"` for GIF files
- **Hamburger menu**: Fixed missing on gallery, porn, terms, index pages - removed duplicate nav styles that were overriding responsive media queries
- **Lightbox mobile**: Added touch swipe navigation, larger tap targets, fixed nav at bottom
- **Stream status**: Removed redundant status indicator banner (now in chat header only)

### Files Removed
- `src/css/dropdown-nav.css` → merged into `layout.css`
- `src/css/index.css` → replaced by `landing.css`
- `src/css/terms.css` → replaced by `content.css`
- `src/css/gallery.css` → replaced by `media-gallery.css`
- `src/css/porn.css` → replaced by `media-gallery.css`
- `src/css/sissylonglegs.css` → replaced by `landing.css`
- `assets/terms.css`, `assets/index.css` → legacy removed

## Recent Changes (Dec 2024)
- **Modernization**: Added Vite build system with Tailwind CSS, ESLint, Prettier
- **ES Modules**: Converted JS to ES6 modules with JSDoc type annotations
- **Source structure**: Created src/ directory for development sources
- Set up Red5 Media Server at /opt/red5
- Created stream-watcher service for auto-start/stop of HLS
- Extracted inline JS to separate files in assets/
- Secured all dot directories
- Moved shell scripts to scripts/
- Updated .htaccess with comprehensive security rules
- Added auto-cleanup of HLS segments when stream ends (prevents stale video on live.html)

## Docker Containers

### Production (Home Server)
| Container | Description | Ports | Tunneled To |
|-----------|-------------|-------|-------------|
| kinky-web | Apache/PHP web server | 80 | Linode :8081 |
| kinky-backend | Node.js chat/API server | 3002 | Linode :3003 |
| kinky-db | MariaDB database | 3306 | - |
| portainer_agent | Portainer management | 9001 | - |

### Development (optional - docker-compose.dev.yml)
| Container | Description | Ports |
|-----------|-------------|-------|
| kinky-web-dev | Apache/PHP web server | 80 |
| kinky-backend-dev | Node.js chat/API server | 3002 |
| kinky-db-dev | MariaDB database | 3306 |

**Note**: RTMP streaming runs on Linode (45.79.208.9) for lower latency.

### Docker Volumes
| Volume | Purpose |
|--------|---------|
| db_data | MariaDB persistent storage |
| letsencrypt | SSL certificates (certbot) |

## Code Style
- Use 4-space indentation for PHP
- Use 2-space indentation for JS/CSS
- JavaScript: ES6+ modules with JSDoc type annotations
- CSS: Tailwind utility classes preferred, custom CSS in src/css/
- External JS files in src/js/ (built to assets/dist/)
- Keep inline scripts minimal
- Run `npm run lint` before committing
- Run `npm run format` to auto-format code

## Build System
The project uses Vite for modern JavaScript/CSS bundling:

```bash
# Development (with hot reload)
npm run dev      # Starts dev server on port 3000

# Production build
npm run build    # Outputs to assets/dist/

# Code quality
npm run lint     # Check for JS errors
npm run format   # Auto-format with Prettier
```

### Key Configuration Files
| File | Purpose |
|------|---------|
| `package.json` | Project dependencies and scripts |
| `vite.config.js` | Vite build configuration |
| `tailwind.config.js` | Tailwind CSS customization |
| `postcss.config.js` | PostCSS plugins (Tailwind, autoprefixer) |
| `.eslintrc.json` | ESLint rules |
| `.prettierrc` | Prettier formatting rules |
