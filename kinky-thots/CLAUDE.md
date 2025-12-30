# Kinky-Thots Project Documentation

## Tech Stack
- **Web Server**: Apache2 with PHP
- **Backend**: Node.js (WebSocket chat server on port 3001)
- **Database**: MariaDB
- **Streaming**: Red5 Media Server (RTMP on :1935, HTTP on :5080)
- **Video Processing**: FFmpeg for RTMP-to-HLS transcoding
- **CDN**: Pushr CDN with S3-compatible storage (Sonic)
- **Build Tools**: Vite, Tailwind CSS, ESLint, Prettier (Dec 2024)

## Quick Commands

### Services
```bash
# Red5 RTMP Server
sudo systemctl start|stop|status red5

# Stream Watcher (auto-starts HLS when streaming)
sudo systemctl start|stop|status stream-watcher

# HLS Transcoder (managed by stream-watcher)
sudo systemctl start|stop|status rtmp-hls

# Node.js Chat Backend
sudo systemctl start|stop|status node-backend
```

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
- `live.html` - Live streaming page (uses HLS.js + Red5)
- `porn.php` - Video gallery
- `gallery.php` - Photo gallery
- `sissylonglegs.php` - Model page
- `bustersherry.html` - Model page
- `terms.html` - Terms & conditions

## Streaming Architecture
1. **OBS/Broadcaster** → `rtmp://SERVER:1935/live/stream`
2. **Red5** receives RTMP stream
3. **stream-watcher.sh** detects stream, starts rtmp-hls service
4. **rtmp-to-hls.sh** converts RTMP → HLS segments
5. **HLS output** → `/hls/playlist.m3u8`
6. **live.html** uses HLS.js to play stream

## Security
- All sensitive directories protected via .htaccess
- Dotfiles blocked (`.env`, `.htaccess`, etc.)
- Package files blocked (`package.json`, `Dockerfile`)
- Security headers enabled (X-Frame-Options, X-Content-Type-Options)
- Directory listing disabled

## Recent Changes (Dec 29, 2024) - CSS Modularization

### CSS Architecture Refactor
Reorganized CSS into modular, reusable files:

| File | Purpose | Used By |
|------|---------|---------|
| `layout.css` | Header (nav) + Footer | All pages (via main.css) |
| `landing.css` | Hero, About, Skills, Portfolio, Contact | index, sissylonglegs, bustersherry |
| `media-gallery.css` | Photo/video grid, upload, lightbox | gallery.php, porn.php |
| `content.css` | Text content pages | terms.html |
| `live.css` | Live streaming + chat | live.html |

### Page → CSS Mapping
| Page | CSS Files Loaded |
|------|------------------|
| index.html | main.css + index.css |
| sissylonglegs.php | main.css + index.css |
| bustersherry.html | main.css + index.css |
| gallery.php | main.css + media-gallery.css |
| porn.php | main.css + media-gallery.css |
| live.html | main.css + live.css |
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

## Systemd Services
| Service | Description | Config Location |
|---------|-------------|-----------------|
| red5 | RTMP Media Server | /etc/systemd/system/red5.service |
| stream-watcher | Monitors RTMP, triggers HLS | /etc/systemd/system/stream-watcher.service |
| rtmp-hls | FFmpeg HLS transcoder | /etc/systemd/system/rtmp-hls.service |

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
