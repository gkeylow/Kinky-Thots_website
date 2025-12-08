# Kinky Thots - Project Structure

This document describes the organized file structure of the Kinky Thots web application.

## Directory Structure

```
/var/www/kinky-thots/
├── backend/              # Backend server and API code
│   ├── server.js         # Node.js Express server
│   ├── pushr-prefetch.php # CDN prefetch functionality
│   ├── node_modules/     # Node.js dependencies
│   ├── uploads/          # Backend upload handling
│   └── __tests__/        # Test files
│
├── public/               # Public-facing web files
│   ├── assets/           # CSS, JS, and static assets
│   │   ├── *.css         # Stylesheets
│   │   └── *.js          # Client-side JavaScript
│   ├── index.html        # Main homepage
│   ├── gallery.html      # Gallery page
│   ├── sissylonglegs.php # Sissy profile page
│   ├── bustersherry.html # Buster profile page
│   ├── porn.html         # Porn content page
│   ├── porn.php          # Porn PHP handler
│   ├─�� terms.html        # Terms of service
│   └── Terms.pdf         # Terms PDF version
│
├── scripts/              # Utility and maintenance scripts
│   ├── compress-videos.sh       # Video compression utility
│   ├── convert-mov-to-mp4.sh    # Video format converter
│   ├── generate-thumbnails.sh   # Thumbnail generator
│   └── update-porn-html.sh      # Content updater
│
├── docs/                 # Documentation files
│   ├── COMPRESSION_GUIDE.md     # Video compression guide
│   ├── COMPRESSION_README.txt   # Compression notes
│   ├── CONVERSION_README.md     # Format conversion guide
│   ├── GALLERY_README.md        # Gallery system docs
│   ├── GALLERY_UPLOAD_FIX.md    # Upload troubleshooting
│   ├── PUSHR_CDN_PREFETCH.md    # CDN prefetch documentation
│   ├── SISSY_BIO_UPDATE.md      # Bio update guide
│   ├── SITE_IMPROVEMENTS.md     # Site improvement notes
│   └── THUMBNAIL_SYSTEM.md      # Thumbnail system docs
│
├── logs/                 # Application logs
│   ├── conversion.log           # Video conversion logs
│   ├── thumbnail-generation.log # Thumbnail generation logs
│   └── pushr-prefetch.log       # CDN prefetch logs
│
├── backups/              # Backup and broken files
│   ├── porn.html.backup         # Backup of porn.html
│   └── porn.php.broken          # Broken version of porn.php
│
├── config/               # Configuration files
│   └── pushr-cdn.json           # CDN configuration
│
├── uploads/              # User uploaded content
│   └── *.gif, *.MP4             # Uploaded media files
│
├── porn/                 # Symlink to /media/porn
│
├── .well-known/          # SSL/TLS and domain verification
├── .vscode/              # VS Code configuration
├── .qodo/                # Qodo AI configuration
├── .gitignore            # Git ignore rules
└── .htaccess             # Apache configuration

```

## Key Files

- **Backend Server**: `backend/server.js` - Main Node.js/Express application
- **Homepage**: `public/index.html` - Main entry point
- **Gallery**: `public/gallery.html` - Media gallery interface
- **Configuration**: `.htaccess` - Apache web server configuration

## Running the Application

### Backend Server
```bash
cd backend
npm install
node server.js
```

### Scripts
All utility scripts are located in the `scripts/` directory and can be executed directly:
```bash
./scripts/compress-videos.sh
./scripts/generate-thumbnails.sh
```

## Documentation

All project documentation is now centralized in the `docs/` directory. Refer to specific guides for:
- Video compression and conversion
- Gallery system usage
- CDN configuration
- Site improvements and updates

## Logs

Application logs are stored in the `logs/` directory for debugging and monitoring purposes.

## Backups

Old and broken files are preserved in the `backups/` directory for reference.

---

**Last Updated**: December 2024
**Structure Organized**: December 7, 2024
