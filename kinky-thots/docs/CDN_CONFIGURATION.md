# CDN Configuration Guide

## Overview

The Kinky-Thots website uses PushrCDN for content delivery.

## Current Configuration

**Status**: Using default PushrCDN endpoints (no custom CNAME)

All zones currently use the base URL: `https://kinky-thots.com`

## Configuration File

Location: `/var/www/kinky-thots/config/pushr-cdn.json`

```json
{
  "api_key": "REDACTED_PUSHR_API_KEY",
  "api_url": "https://www.pushrcdn.com/api/v3",
  "zones": {
    "videos": {
      "zone_id": "6293",
      "base_url": "https://kinky-thots.com"
    },
    "my-videos": {
      "zone_id": "6295",
      "base_url": "https://kinky-thots.com"
    },
    "images": {
      "zone_id": "6292",
      "base_url": "https://kinky-thots.com"
    },
    "my-images": {
      "zone_id": "6294",
      "base_url": "https://kinky-thots.com"
    }
  },
  "default_zone": "videos",
  "prefetch_log": "/var/www/kinky-thots/logs/pushr-prefetch.log"
}
```

## Available Zones

### Videos
- **Zone ID**: 6293
- **Purpose**: Video content
- **Base URL**: https://kinky-thots.com

### My Videos
- **Zone ID**: 6295
- **Purpose**: Personal video content
- **Base URL**: https://kinky-thots.com

### Images
- **Zone ID**: 6292
- **Purpose**: Image content
- **Base URL**: https://kinky-thots.com

### My Images
- **Zone ID**: 6294
- **Purpose**: Personal image content
- **Base URL**: https://kinky-thots.com

## Using the CDN

### Prefetch Content

The `pushr-prefetch.php` script can be used to push content to the CDN edge cache:

```bash
# Prefetch a single URL
php backend/pushr-prefetch.php prefetch https://kinky-thots.com/porn/video.mp4

# Prefetch all videos
php backend/pushr-prefetch.php prefetch-videos

# Prefetch all gallery uploads
php backend/pushr-prefetch.php prefetch-uploads

# List available zones
php backend/pushr-prefetch.php list-zones

# Check prefetch status
php backend/pushr-prefetch.php status
```

## Setting Up Custom CNAME (Optional)

If you want to use custom CDN hostnames in the future:

### 1. Configure DNS CNAME Records

Example:
```
cdn.kinky-thots.com       CNAME   [PushrCDN endpoint for images]
cdn-video.kinky-thots.com CNAME   [PushrCDN endpoint for videos]
```

### 2. Update Configuration

Edit `/var/www/kinky-thots/config/pushr-cdn.json`:

```json
{
  "zones": {
    "videos": {
      "zone_id": "6293",
      "base_url": "https://cdn-video.kinky-thots.com"
    },
    "images": {
      "zone_id": "6292",
      "base_url": "https://cdn.kinky-thots.com"
    }
  }
}
```

### 3. Update Your Code

Change resource URLs to use CDN hostnames:

**For Images:**
```html
<img src="https://cdn.kinky-thots.com/path/to/image.jpg">
```

**For Videos:**
```html
<video src="https://cdn-video.kinky-thots.com/porn/video.mp4"></video>
```

## Benefits

1. **Faster Load Times**: Content served from edge locations closer to users
2. **Reduced Server Load**: Static content offloaded to CDN
3. **Better Performance**: Optimized delivery for images and videos
4. **Scalability**: Handle traffic spikes without server strain

## Monitoring

- Check prefetch logs: `/var/www/kinky-thots/logs/pushr-prefetch.log`
- Monitor CDN usage through PushrCDN dashboard
- Use browser DevTools to verify content delivery

## Troubleshooting

### Prefetch Failures

1. Check API key validity
2. Verify zone IDs are correct
3. Review logs at `/var/www/kinky-thots/logs/pushr-prefetch.log`
4. Ensure URLs are properly formatted

### Content Not Loading

1. Verify zone configuration
2. Check that content has been prefetched to CDN
3. Ensure zone IDs match your PushrCDN account
4. Verify API key is correct

## Security Notes

- API key is stored in config file - ensure proper file permissions (600 or 640)
- Config file is gitignored to prevent accidental exposure
- Use HTTPS for all CDN URLs

---

**Last Updated**: December 2024
**Configuration Reverted**: December 7, 2024
