# Kinky-Thots Project Documentation

## Tech Stack
- **Web Server**: Apache2 with PHP
- **Backend**: Node.js (WebSocket chat server on port 3001)
- **Database**: MariaDB
- **Streaming**: Red5 Media Server (RTMP on :1935, HTTP on :5080)
- **Video Processing**: FFmpeg for RTMP-to-HLS transcoding
- **CDN**: Pushr CDN with S3-compatible storage (Sonic)

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
├── assets/           # CSS, JS, images (public)
│   ├── *.css         # Stylesheets
│   ├── *.js          # JavaScript files
│   └── thumbnails/   # Video thumbnails
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

## Recent Changes (Dec 2024)
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
- External JS files in /assets/
- Keep inline scripts minimal
