# CDN Configuration Guide

## Overview

The Kinky-Thots website uses PushrCDN with custom CNAME domains for content delivery.

## CDN Hostnames

### Images CDN
- **Hostname**: `cdn.kinky-thots.com`
- **Purpose**: Serves all image content (photos, thumbnails, icons)
- **Zones**: 
  - `images` (Zone ID: 6292)
  - `my-images` (Zone ID: 6294)

### Videos CDN
- **Hostname**: `cdn-video.kinky-thots.com`
- **Purpose**: Serves all video content
- **Zones**:
  - `videos` (Zone ID: 6293)
  - `my-videos` (Zone ID: 6295)

## Configuration File

Location: `/var/www/kinky-thots/config/pushr-cdn.json`

```json
{
  "api_key": "REDACTED_PUSHR_API_KEY",
  "api_url": "https://www.pushrcdn.com/api/v3",
  "zones": {
    "videos": {
      "zone_id": "6293",
      "base_url": "https://cdn-video.kinky-thots.com"
    },
    "my-videos": {
      "zone_id": "6295",
      "base_url": "https://cdn-video.kinky-thots.com"
    },
    "images": {
      "zone_id": "6292",
      "base_url": "https://cdn.kinky-thots.com"
    },
    "my-images": {
      "zone_id": "6294",
      "base_url": "https://cdn.kinky-thots.com"
    }
  },
  "default_zone": "videos",
  "prefetch_log": "/var/www/kinky-thots/logs/pushr-prefetch.log"
}
```

## DNS Configuration

Ensure the following CNAME records are configured in your DNS:

```
cdn.kinky-thots.com       CNAME   [PushrCDN endpoint for images]
cdn-video.kinky-thots.com CNAME   [PushrCDN endpoint for videos]
```

## Using the CDN

### Prefetch Content

The `pushr-prefetch.php` script can be used to push content to the CDN edge cache:

```bash
# Prefetch a single URL
php backend/pushr-prefetch.php prefetch https://cdn-video.kinky-thots.com/porn/video.mp4

# Prefetch all videos
php backend/pushr-prefetch.php prefetch-videos

# Prefetch all gallery uploads
php backend/pushr-prefetch.php prefetch-uploads

# List available zones
php backend/pushr-prefetch.php list-zones

# Check prefetch status
php backend/pushr-prefetch.php status
```

### In Your Code

When referencing CDN content in your HTML/PHP/JS:

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
- Use browser DevTools to verify content is loading from CDN domains

## Troubleshooting

### Content Not Loading from CDN

1. Verify DNS CNAME records are properly configured
2. Check that content has been prefetched to CDN
3. Ensure zone IDs match your PushrCDN account
4. Verify API key is correct

### Prefetch Failures

1. Check API key validity
2. Verify zone IDs are correct
3. Review logs at `/var/www/kinky-thots/logs/pushr-prefetch.log`
4. Ensure URLs are properly formatted

## Security Notes

- API key is stored in config file - ensure proper file permissions (600 or 640)
- Config file is gitignored to prevent accidental exposure
- Use HTTPS for all CDN URLs

---

**Last Updated**: December 2024
**Configuration Updated**: December 7, 2024
