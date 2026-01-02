# Kinky-Thots Site Architecture & Development Guide

## Table of Contents
- [Site Architecture Overview](#site-architecture-overview)
- [Feature Map](#feature-map)
- [Request Flow](#request-flow)
- [Build System (Vite + Tailwind)](#build-system-vite--tailwind)
- [Development Workflow](#development-workflow)
- [Troubleshooting Guide](#troubleshooting-guide)

---

## Site Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           KINKY-THOTS ARCHITECTURE                          │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   BROWSER    │────▶│   APACHE2    │────▶│  PHP/HTML    │────▶│   RESPONSE   │
│   Request    │     │   Port 80    │     │   Pages      │     │   to User    │
└──────────────┘     └──────┬───────┘     └──────────────┘     └──────────────┘
                           │
                           │ Static Assets
                           ▼
                    ┌──────────────┐
                    │ /assets/dist │
                    │  CSS + JS    │
                    └──────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                              STREAMING FLOW                                  │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│     OBS      │────▶│  nginx-rtmp  │────▶│  /hls/*.m3u8 │
│  Broadcaster │RTMP │  Port 1935   │     │   Segments   │
└──────────────┘     │  (Docker)    │     │  (auto HLS)  │
                     └──────────────┘     └──────┬───────┘
                                                 │
                     ┌──────────────┐     ┌──────┴───────┐
                     │  live.html   │◀────│   HLS.js     │
                     │   Player     │     │   Library    │
                     └──────────────┘     └──────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                              CDN FLOW (Pushr/Sonic S3)                       │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Upload     │────▶│  Sonic S3    │────▶│  Pushr CDN   │────▶│   Browser    │
│   Script     │ API │   Storage    │     │   Delivery   │     │   Playback   │
└──────────────┘     └──────────────┘     └──────────────┘     └──────────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ video-       │
                    │ manifest.json│
                    └──────────────┘
```

---

## Feature Map

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              SITE FEATURES                                   │
└─────────────────────────────────────────────────────────────────────────────┘

                              ┌─────────────┐
                              │  index.html │
                              │  (Homepage) │
                              └──────┬──────┘
                                     │
        ┌────────────────────────────┼────────────────────────────┐
        │                            │                            │
        ▼                            ▼                            ▼
┌───────────────┐          ┌───────────────┐          ┌───────────────┐
│    MODELS     │          │     MEDIA     │          │     INFO      │
├───────────────┤          ├───────────────┤          ├───────────────┤
│sissylonglegs  │          │ *-content.php │          │  terms.html   │
│  .html        │          │ (Video Tiers) │          │               │
├───────────────┤          ├───────────────┤          └───────────────┘
│bustersherry   │          │  gallery.php  │
│  .html        │          │(Photo Gallery)│
└───────────────┘          ├───────────────┤
                           │  live.html    │
                           │ (Live Stream) │
                           └───────────────┘
```

### Page Features Detail

| Page | Features | Data Source |
|------|----------|-------------|
| `index.html` | Hero, About, Skills, Portfolio, Contact | Static |
| `sissylonglegs.html` | Hero, Skills (hover images), Mosaic Gallery, Contact | Static + ibb.co |
| `bustersherry.html` | Hero, Skills (hover images), Mosaic Gallery, Contact | Static + ibb.co |
| `free-content.php` | Free videos (<1 min), Lightbox player | `video-manifest.json` + CDN |
| `basic-content.php` | Extended videos (1-5 min), Lightbox player | `video-manifest.json` + CDN |
| `premium-content.php` | Full-length videos (>5 min), Lightbox player | `video-manifest.json` + CDN |
| `gallery.php` | Photo mosaic grid, Lightbox | CDN images |
| `live.html` | HLS video player, Chat, Status indicator | nginx-rtmp + WebSocket |
| `terms.html` | Terms & Conditions | Static |

---

## Request Flow

### Static Page Request (HTML)
```
User Request                 Apache                      Response
    │                           │                           │
    │  GET /index.html          │                           │
    │ ─────────────────────────▶│                           │
    │                           │  Read index.html          │
    │                           │  ──────────────▶          │
    │                           │                           │
    │                           │◀──────────────────────────│
    │◀──────────────────────────│  Return HTML + Assets     │
    │                           │                           │
```

### Dynamic Page Request (PHP)
```
User Request          Apache              PHP               Database/CDN
    │                    │                 │                     │
    │ GET /free-content  │                 │                     │
    │ ──────────────────▶│                 │                     │
    │                    │ Execute PHP     │                     │
    │                    │ ───────────────▶│                     │
    │                    │                 │ Read manifest.json  │
    │                    │                 │ ───────────────────▶│
    │                    │                 │◀────────────────────│
    │                    │◀────────────────│ Generate HTML       │
    │◀───────────────────│                 │                     │
    │  Return Page       │                 │                     │
```

### Live Stream Flow
```
Broadcaster           nginx-rtmp              Browser
    │                      │                     │
    │ RTMP Stream          │                     │
    │ ────────────────────▶│                     │
    │                      │                     │
    │                      │ Auto-converts to    │
    │                      │ HLS segments in     │
    │                      │ /hls/ directory     │
    │                      │                     │
    │                      │                     │ GET /hls/stream.m3u8
    │                      │                     │◀────────────────────
    │                      │ ───────────────────▶│
    │                      │  Deliver HLS        │
    │                      │                     │
```

**OBS Settings:**
- Server: `rtmp://YOUR_SERVER:1935/live`
- Stream Key: `stream`

---

## Build System (Vite + Tailwind)

### Directory Structure
```
/var/www/kinky-thots/
├── src/                    # SOURCE FILES (edit these)
│   ├── css/
│   │   ├── main.css        # Tailwind entry + base styles
│   │   ├── layout.css      # Navigation, footer, containers
│   │   ├── landing.css     # Homepage + model page styles
│   │   ├── media-gallery.css # Video/photo gallery styles
│   │   ├── live.css        # Live streaming page styles
│   │   ├── content.css     # Content page styles
│   │   └── chat.css        # Chat widget styles
│   └── js/
│       ├── main.js         # Navigation, dropdowns
│       ├── index.js        # Homepage scripts
│       ├── live.js         # HLS player, chat
│       ├── gallery.js      # Photo gallery
│       ├── porn.js         # Video gallery
│       └── sissylonglegs.js # Model page scripts
│
├── assets/dist/            # BUILT FILES (auto-generated)
│   ├── css/                # Compiled CSS
│   └── js/                 # Compiled JS
│
├── vite.config.js          # Vite configuration
├── tailwind.config.js      # Tailwind configuration
├── postcss.config.js       # PostCSS plugins
├── package.json            # NPM scripts & dependencies
└── .eslintrc.json          # Linting rules
```

### Build Commands

```bash
# Development (hot reload on localhost:3000)
npm run dev

# Production build (compiles to assets/dist/)
npm run build

# Preview production build
npm run preview

# Lint JavaScript
npm run lint
npm run lint:fix

# Format code with Prettier
npm run format
```

### CSS Workflow

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   src/css/      │     │    Vite +       │     │  assets/dist/   │
│   *.css         │────▶│    PostCSS      │────▶│   css/*.css     │
│   (Source)      │     │    Tailwind     │     │   (Minified)    │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

**Example: Adding new styles**

1. Edit source file:
   ```css
   /* src/css/landing.css */
   .my-new-class {
       color: #f805a7;
   }
   ```

2. Build:
   ```bash
   npm run build
   ```

3. Styles appear in `assets/dist/css/index.css`

### JavaScript Workflow

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   src/js/       │     │      Vite       │     │  assets/dist/   │
│   *.js          │────▶│    Rollup       │────▶│   js/*.js       │
│   (ES Modules)  │     │    Bundler      │     │   (Minified)    │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

### Which CSS File to Edit?

| Page | Edit This Source File | Compiles To |
|------|----------------------|-------------|
| index.html | `src/css/landing.css` | `assets/dist/css/index.css` |
| sissylonglegs.html | `src/css/landing.css` | `assets/dist/css/index.css` |
| bustersherry.html | `src/css/landing.css` | `assets/dist/css/index.css` |
| *-content.php | `src/css/media-gallery.css` | `assets/dist/css/media-gallery.css` |
| gallery.php | `src/css/media-gallery.css` | `assets/dist/css/media-gallery.css` |
| live.html | `src/css/live.css` | `assets/dist/css/live.css` |
| All pages (nav/footer) | `src/css/layout.css` | `assets/dist/css/main.css` |

---

## Development Workflow

### Making Changes Checklist

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         DEVELOPMENT WORKFLOW                                 │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌─────────────┐
    │   START     │
    └──────┬──────┘
           │
           ▼
    ┌─────────────┐     Yes    ┌─────────────┐
    │ CSS/JS      │───────────▶│ Edit src/   │
    │ Change?     │            │ css/ or js/ │
    └──────┬──────┘            └──────┬──────┘
           │ No                       │
           │                          ▼
           │                   ┌─────────────┐
           │                   │ npm run     │
           │                   │ build       │
           │                   └──────┬──────┘
           │                          │
           ▼                          │
    ┌─────────────┐                   │
    │ HTML/PHP    │                   │
    │ Change?     │                   │
    └──────┬──────┘                   │
           │                          │
           ▼                          │
    ┌─────────────┐                   │
    │ Edit HTML/  │                   │
    │ PHP file    │                   │
    └──────┬──────┘                   │
           │                          │
           ▼                          │
    ┌─────────────┐◀──────────────────┘
    │ Test in     │
    │ Browser     │
    └──────┬──────┘
           │
           ▼
    ┌─────────────┐     No     ┌─────────────┐
    │  Working?   │───────────▶│ See Trouble-│
    │             │            │ shooting    │
    └──────┬──────┘            └─────────────┘
           │ Yes
           ▼
    ┌─────────────┐
    │ git add     │
    │ git commit  │
    │ git push    │
    └─────────────┘
```

### CDN Video Management

```bash
# Sync video manifest from CDN (after adding/removing videos on Pushr)
cd /var/www/kinky-thots/backend
npm run sonic:sync-manifest

# List all CDN objects
npm run sonic:list

# Upload a video
npm run sonic:upload -- /path/to/video.mp4

# Test CDN connection
npm run sonic:test
```

---

## Troubleshooting Guide

### Quick Diagnostic Flowchart

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         TROUBLESHOOTING FLOWCHART                            │
└─────────────────────────────────────────────────────────────────────────────┘

                         ┌─────────────────┐
                         │  SITE PROBLEM   │
                         └────────┬────────┘
                                  │
              ┌───────────────────┼───────────────────┐
              │                   │                   │
              ▼                   ▼                   ▼
       ┌────────────┐      ┌────────────┐      ┌────────────┐
       │ Page not   │      │ Styles not │      │ Stream not │
       │ loading    │      │ working    │      │ working    │
       └─────┬──────┘      └─────┬──────┘      └─────┬──────┘
             │                   │                   │
             ▼                   ▼                   ▼
       ┌────────────┐      ┌────────────┐      ┌────────────┐
       │ Check      │      │ Check      │      │ Check      │
       │ Apache     │      │ Browser    │      │ nginx-rtmp │
       │ Status     │      │ Cache      │      │ Container  │
       └─────┬──────┘      └─────┬──────┘      └─────┬──────┘
             │                   │                   │
             ▼                   ▼                   ▼
        See Section:        See Section:        See Section:
        "Apache Issues"     "CSS Issues"        "Streaming"
```

### 1. Apache/Web Server Issues

```bash
# Check Apache status
sudo systemctl status apache2

# Test Apache config
apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2

# Check Apache error logs
tail -50 /var/log/apache2/error.log

# Check if port 80 is in use
sudo lsof -i :80
```

**Common Issues:**
| Symptom | Cause | Fix |
|---------|-------|-----|
| 403 Forbidden | File permissions | `sudo chown -R www-data:www-data /var/www/kinky-thots` |
| 404 Not Found | Wrong path / missing file | Check file exists and path is correct |
| 500 Error | PHP syntax error | Check `/var/log/apache2/error.log` |

### 2. CSS/Styling Issues

```
┌─────────────────┐
│ CSS Not Working │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     Yes    ┌─────────────────┐
│ Did you edit    │───────────▶│ Run:            │
│ src/css/?       │            │ npm run build   │
└────────┬────────┘            └─────────────────┘
         │ No
         ▼
┌─────────────────┐     Yes    ┌─────────────────┐
│ Browser showing │───────────▶│ Hard refresh:   │
│ old styles?     │            │ Ctrl+Shift+R    │
└────────┬────────┘            │ or add ?v=XXX   │
         │ No                  │ to CSS link     │
         ▼                     └─────────────────┘
┌─────────────────┐
│ Check DevTools  │
│ Console for     │
│ 404 errors      │
└─────────────────┘
```

**Cache Busting:**
```html
<!-- Add version query string to force reload -->
<link rel="stylesheet" href="/assets/dist/css/index.css?v=20241230">
```

**Verify CSS is loading:**
1. Open DevTools (F12)
2. Go to Network tab
3. Reload page
4. Look for CSS files - should be 200 status

### 3. JavaScript Issues

```bash
# Check for JS errors in browser console (F12)

# Lint your code
npm run lint

# Rebuild
npm run build
```

**Common Issues:**
| Symptom | Cause | Fix |
|---------|-------|-----|
| Navigation not working | JS not loading | Check Network tab for 404s |
| Dropdown not opening | Event listener issue | Check console for errors |
| Gallery not loading | API/data issue | Check Network tab for failed requests |

### 4. Live Streaming Issues

```bash
# Check nginx-rtmp container
docker ps | grep rtmp
docker logs kinky-rtmp

# Check if RTMP port is listening
ss -tlnp | grep 1935

# Check HLS output directory
ls -la /var/www/kinky-thots/hls/

# Restart rtmp container
docker-compose restart rtmp

# View rtmp container logs live
docker logs -f kinky-rtmp
```

**Streaming Troubleshooting Flow:**
```
┌─────────────────┐
│ Stream not      │
│ showing         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     No     ┌─────────────────┐
│ Is rtmp Docker  │───────────▶│ docker-compose  │
│ container up?   │            │ up -d rtmp      │
└────────┬────────┘            └─────────────────┘
         │ Yes
         ▼
┌─────────────────┐     No     ┌─────────────────┐
│ Is OBS          │───────────▶│ Start streaming │
│ streaming?      │            │ in OBS          │
└────────┬────────┘            └─────────────────┘
         │ Yes
         ▼
┌─────────────────┐     No     ┌─────────────────┐
│ HLS files in    │───────────▶│ Check docker    │
│ /hls/ folder?   │            │ logs kinky-rtmp │
└────────┬────────┘            └─────────────────┘
         │ Yes
         ▼
┌─────────────────┐
│ Check browser   │
│ console for     │
│ HLS.js errors   │
└─────────────────┘
```

### 5. CDN/Video Issues

```bash
# Test CDN connection
cd /var/www/kinky-thots/backend
npm run sonic:test

# List what's on CDN
npm run sonic:list

# Sync manifest after CDN changes
npm run sonic:sync-manifest

# Check manifest file
cat /var/www/kinky-thots/data/video-manifest.json | head -50
```

**Videos not showing on content pages:**
1. Check if videos exist on CDN: `npm run sonic:list`
2. Sync manifest: `npm run sonic:sync-manifest`
3. Verify manifest updated: `cat data/video-manifest.json`
4. Hard refresh the page

### 6. Docker Issues (if using Docker)

```bash
# Check container status
docker ps

# View container logs
docker logs kinky-thots-web

# Rebuild container after changes
docker-compose build web && docker-compose up -d web

# Shell into container
docker exec -it kinky-thots-web bash
```

### 7. Node.js Backend Issues

```bash
# Check backend status
sudo systemctl status node-backend

# View backend logs
journalctl -u node-backend -f

# Restart backend
sudo systemctl restart node-backend

# Check if port 3001 is listening
ss -tlnp | grep 3001
```

---

## Quick Reference Commands

```bash
# ═══════════════════════════════════════════════════════════
#                    QUICK REFERENCE
# ═══════════════════════════════════════════════════════════

# BUILD & DEVELOPMENT
npm run build              # Compile CSS/JS
npm run dev                # Dev server with hot reload
npm run lint               # Check JS for errors

# SERVICES (Docker)
docker ps                           # Check all containers
docker logs kinky-web               # Web server logs
docker logs kinky-rtmp              # RTMP/streaming logs
docker logs kinky-backend           # Chat/API backend logs
docker-compose restart <service>    # Restart a service

# CDN MANAGEMENT
cd backend && npm run sonic:test           # Test connection
cd backend && npm run sonic:list           # List files
cd backend && npm run sonic:sync-manifest  # Sync videos

# LOGS
docker logs -f kinky-web               # Apache/web errors
docker logs -f kinky-rtmp              # Streaming logs
docker logs -f kinky-backend           # Backend logs
docker logs -f kinky-db                # Database logs

# GIT
git status                 # See changes
git add . && git commit -m "message"  # Commit
git push                   # Push to remote
```

---

## File Quick Reference

| What to Change | Edit This File | Then Run |
|----------------|----------------|----------|
| Homepage styles | `src/css/landing.css` | `npm run build` |
| Model page styles | `src/css/landing.css` | `npm run build` |
| Navigation/footer | `src/css/layout.css` | `npm run build` |
| Video gallery | `src/css/media-gallery.css` | `npm run build` |
| Live page | `src/css/live.css` | `npm run build` |
| Any JavaScript | `src/js/*.js` | `npm run build` |
| HTML structure | `*.html` files | Nothing (direct edit) |
| PHP pages | `*.php` files | Nothing (direct edit) |
| Video list | Pushr CDN dashboard | `npm run sonic:sync-manifest` |

---

*Last updated: December 2024*
