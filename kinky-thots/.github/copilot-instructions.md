# Copilot Instructions for Kinky Thots

## Project Overview

**Kinky Thots** is a media gallery application with a dual-stack architecture:
- **Frontend**: Static HTML/CSS with vanilla JavaScript (Apache server)
- **Backend**: Node.js/Express API (port 3001) serving images and videos
- **Database**: MariaDB for image metadata
- **CDN**: PushrCDN integration for distributed content delivery

## Key Architecture Patterns

### Media Handling & Storage
The system distinguishes between **image** and **video** uploads using file extensions:
- **Images** → stored in `/var/www/kinky-thots/uploads/` with responsive size generation
- **Videos** → stored in `/media/porn/kinky-thots-shorts/` (symlinked at `./porn/`)
- File type detection: `getFileType()` in `backend/server.js` (lines 59-63)
- Storage paths configured in `PATHS` object (lines 24-27)

### Responsive Image Generation
When images are uploaded, `generateResponsiveImages()` (in `backend/image-optimizer.js`) creates four versions:
- **thumb** (150px) - 75% quality
- **small** (480px) - 80% quality  
- **medium** (1024px) - 85% quality
- **large** (1920px) - 90% quality

Both JPEG and WebP variants are generated for each size. Process runs async in background; don't wait for completion.

### CDN Integration (PushrCDN & Sonic S3)

**PushrCDN** (Primary):
1. **Images** prefetched via `prefetchToCDN()` (lines ~110-160)
2. **Videos** use pull-zone: served from origin, CDN caches on first request
3. Config: `backend/server.js` lines 32-49 (`PUSHR_CONFIG`)

**Sonic S3 CDN** (New - Parallel):
1. **Both images and videos** uploaded to S3-compatible storage via `uploadToSonicS3()` (lines ~165-190)
2. Automatic folder organization: `images/<filename>`, `videos/<filename>`
3. Background async upload after file saved locally
4. Config: `config/sonic-s3-cdn.json` with credentials and CDN URLs
5. Node.js client: `backend/sonic-s3-client.js` (AWS SDK v3 S3 implementation)
6. CLI tool: `backend/sonic-cli.js` for manual testing (`npm run sonic:*` commands)

**Why dual CDN?** Redundancy + different strengths:
- PushrCDN: Quick prefetch, immediate distribution
- Sonic S3: Long-term storage, unlimited requests, cost-effective egress

Video manifest at `data/video-manifest.json` replaces file scanning in content pages.

**Database**: MariaDB pool at `localhost` (lines ~155-165):
```javascript
database: 'gallery_db',
connectionLimit: 5
```
Credentials: `gkeylow` user. Images table: `id`, `filename`, `file_type`, `file_path`, `upload_time`.

## API Endpoints

All backend routes expose `/api/` prefix (relative paths via Apache proxy):

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/gallery` | List all images with CDN/origin URLs |
| POST | `/api/upload` | Upload image or video (sanitizes name, generates thumbnails, prefetches to CDN) |
| DELETE | `/api/gallery/:id` | Delete file from disk and database |
| GET | `/health` | Server health check |

Response format for gallery list: array of objects with `id`, `filename`, `file_type`, `cdn_url` (images only), `origin_url`, `full_url`, `upload_time`.

## Frontend Integration

**Gallery JavaScript** (`assets/gallery.js`):
- Uses relative URLs (`CONFIG.apiBase = ''`) to work through Apache proxy
- Fetches from `/api/gallery`, uploads via `/api/upload`
- Renders responsive image grid with lightbox viewer
- Handles drag-and-drop uploads via HTML5 file input

**Backend Proxy**: Apache routes `/api/*` requests to Node.js backend via `.htaccess` configuration.

## Testing & Development

### Run Backend
```bash
cd backend
npm install
node server.js  # Starts on port 3001
```

### Tests
```bash
cd backend
npm test  # Runs Jest with pattern: **/__tests__/**/*.js
```
Jest config in `backend/package.json` uses `testEnvironment: node` and verbose output.

## Project-Specific Patterns

1. **File Sanitization**: Filenames use timestamp prefix (`${Date.now()}_${sanitized}`) to ensure uniqueness and prevent collisions
2. **Error Handling**: All async operations try/catch with database connection cleanup in finally blocks
3. **Async Logging**: CDN prefetch and responsive image generation run in background; success/failure logged to console
4. **No Local Video Scanning**: Content pages prefer manifest over directory scanning to avoid filesystem I/O for large media libraries
5. **CORS Enabled**: All origins allowed for gallery API (`origin: '*'`)

## Critical Files by Role

| File | Purpose |
|------|---------|
| `backend/server.js` | Main Express app, API routes, database logic, CDN integration |
| `backend/sonic-s3-client.js` | Sonic S3 CDN client (AWS SDK wrapper) |
| `backend/sonic-cli.js` | CLI tool for manual Sonic S3 uploads/management |
| `backend/image-optimizer.js` | Responsive image generation using Sharp library |
| `assets/gallery.js` | Frontend gallery UI and upload form |
| `free-content.php` | Free video gallery (<1 min) using manifest |
| `basic-content.php` | Extended video gallery (1-5 min) using manifest |
| `premium-content.php` | Full-length video gallery (>5 min) using manifest |
| `config/sonic-s3-cdn.json` | Sonic S3 credentials, endpoint, bucket config |
| `data/video-manifest.json` | Source of truth for video metadata |
| `docs/SONIC_S3_CDN.md` | Full Sonic S3 documentation and architecture |
| `docs/SONIC_S3_SETUP_TESTING.md` | Setup guide, test procedures, troubleshooting |

## Common Tasks

**Adding a new media type** (beyond image/video):
1. Extend `VIDEO_EXTENSIONS` or `IMAGE_EXTENSIONS` in `backend/server.js` (lines 16-17)
2. Update `getMimeType()` in content pages if needed
3. Add to Sonic S3 config: `config/sonic-s3-cdn.json` under `allowed_extensions`
4. May need new storage path in `PATHS` object

**Testing Sonic S3 uploads**:
```bash
cd backend
npm run sonic:test              # Test connection
npm run sonic:upload -- file.mp4  # Manual upload
npm run sonic:list              # List uploaded files
npm run sonic:delete -- key     # Remove file
```

**Debugging CDN issues**:
- PushrCDN: Check `backend/server.js` console logs for prefetch success/failure
- Sonic S3: Check logs for `[Sonic S3]` prefixed messages
- Review `config/sonic-s3-cdn.json` and `config/pushr-cdn.json` for credentials
- Run `npm run sonic:test` to verify connectivity
- See `docs/SONIC_S3_CDN.md` for detailed troubleshooting

**Modifying database schema**:
- Images table lives in `gallery_db` database
- Connection pool details in `backend/server.js` lines 136-142
- Update queries use parameterized statements to prevent SQL injection
