# PushrCDN Content Prefetch System

## Overview

This system automatically pushes content to PushrCDN's edge cache servers, ensuring faster delivery to users worldwide.

## How It Works

1. **Automatic Prefetch on Upload**: When files are uploaded to the gallery, they're automatically prefetched to PushrCDN
2. **Manual Prefetch**: Use the CLI tool to prefetch existing content
3. **Batch Prefetch**: Prefetch entire directories of videos or images

## Your CDN Zones

| Zone | ID | Use For |
|------|-----|---------|
| videos | 6293 | Video content |
| my-videos | 6295 | Private videos |
| images | 6292 | Images & GIFs |
| my-images | 6294 | Private images |

## Quick Commands

```bash
# List all zones
php backend/pushr-prefetch.php list-zones

# Prefetch a single URL
php backend/pushr-prefetch.php prefetch "https://kinky-thots.com/porn/video.mp4" videos

# Prefetch a local file
php backend/pushr-prefetch.php prefetch-file /media/porn/kinky-thots-shorts/video.mp4

# Prefetch all videos
php backend/pushr-prefetch.php prefetch-videos

# Prefetch all gallery uploads
php backend/pushr-prefetch.php prefetch-uploads

# Prefetch all images
php backend/pushr-prefetch.php prefetch-images

# Prefetch a directory with specific extensions
php backend/pushr-prefetch.php prefetch-dir /media/porn mp4,webm videos

# Check prefetch status/logs
php backend/pushr-prefetch.php status
```

## Automatic Prefetch

The gallery backend (`server.js`) automatically prefetches new uploads to PushrCDN. When a file is uploaded:

1. File is saved to `/var/www/kinky-thots/uploads/`
2. Database record is created
3. PushrCDN prefetch is triggered in background
4. File is cached on CDN edge servers worldwide

## Configuration

### Config File: `/var/www/kinky-thots/config/pushr-cdn.json`

```json
{
  "api_key": "your-api-key",
  "api_url": "https://www.pushrcdn.com/api/v3",
  "zones": {
    "videos": { "zone_id": "6293", "base_url": "https://kinky-thots.com" },
    "images": { "zone_id": "6292", "base_url": "https://kinky-thots.com" }
  },
  "default_zone": "videos"
}
```

### Backend Config: `/var/www/kinky-thots/backend/server.js`

```javascript
const PUSHR_CONFIG = {
  enabled: true,  // Set to false to disable auto-prefetch
  apiKey: 'your-api-key',
  zoneId: '6292',
  baseUrl: 'https://kinky-thots.com'
};
```

## Recommended Workflow

### For New Content

1. Upload via gallery - auto-prefetched
2. Or upload manually and run:
   ```bash
   php backend/pushr-prefetch.php prefetch-file /path/to/file
   ```

### For Existing Content (First Time Setup)

```bash
# Prefetch all videos (may take a while)
php backend/pushr-prefetch.php prefetch-videos

# Prefetch all images
php backend/pushr-prefetch.php prefetch-images

# Prefetch gallery uploads
php backend/pushr-prefetch.php prefetch-uploads
```

### Scheduled Prefetch (Cron)

Add to crontab for regular prefetch of new content:

```bash
# Prefetch new uploads every hour
0 * * * * php /var/www/kinky-thots/backend/pushr-prefetch.php prefetch-uploads >> /var/www/kinky-thots/logs/cron-prefetch.log 2>&1
```

## Logs

- **Prefetch Log**: `/var/www/kinky-thots/logs/pushr-prefetch.log`
- **Server Log**: `pm2 logs server`

## Troubleshooting

### Prefetch Fails

1. Check API key is correct
2. Verify zone ID exists
3. Ensure URL is publicly accessible
4. Check logs: `php backend/pushr-prefetch.php status`

### Content Not Cached

1. Prefetch only works for publicly accessible URLs
2. Large files may take several minutes to propagate
3. Check PushrCDN dashboard for zone status

### API Errors

```bash
# Test API connection
curl -H "Accept: application/json" -H "APIKEY: your-key" \
  -X POST "https://www.pushrcdn.com/api/v3/zones/list"
```

## File Locations

| File | Purpose |
|------|---------|
| `config/pushr-cdn.json` | API configuration |
| `backend/pushr-prefetch.php` | CLI prefetch tool |
| `backend/server.js` | Auto-prefetch on upload |
| `logs/pushr-prefetch.log` | Prefetch activity log |

## Security Notes

- API key stored in config file (excluded from git)
- Config file permissions should be restricted
- Never expose API key in client-side code
