## Tech Stack
- **Web Server**: Apache2 with PHP (Docker: kinky-web)
- **Backend**: Node.js WebSocket chat server on port 3001 (Docker: kinky-backend)
- **Database**: MariaDB 10.11 (Docker: kinky-db)
- **Streaming**: nginx-rtmp (Docker: kinky-rtmp) - RTMP on :1935, auto HLS conversion
- **CDN**: Pushr CDN with S3-compatible storage (Sonic)
- **Build Tools**: Vite, Tailwind CSS, ESLint, Prettier
- **SSL**: Certbot available in Docker for future native HTTPS (currently using localtonet)

kinky-thots/
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
