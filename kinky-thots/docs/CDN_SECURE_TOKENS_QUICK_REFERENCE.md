# PushrCDN Secure Tokens - Quick Reference

## Configuration

**Pull CDN**: c5988z6292.r-cdn.com (Zone 6292)
**Push CDN**: c5988z6294.r-cdn.com (Zone 6294)
**Secret Token**: e872d33deed25bcbcd1ddcb596dfc1872f9a6a07

## Quick Usage

```php
require_once 'backend/pushr-secure-tokens.php';
$tokenGen = new PushrSecureTokens();

// Secure an image
$url = $tokenGen->secureImage('/uploads/photo.jpg');

// Secure a video
$url = $tokenGen->secureVideo('/porn/video.mp4', 7200);

// Secure HLS
$url = $tokenGen->secureHLS('/porn/hls/', 'playlist.m3u8');
```

## Common Expiration Times

- Images: 3600 (1 hour)
- Videos: 7200 (2 hours)
- Downloads: 900 (15 minutes)
- Thumbnails: 86400 (24 hours)

## Testing

```bash
php backend/pushr-secure-tokens.php
```

## URL Format

File Token:
```
https://c5988z6292.r-cdn.com/[TOKEN]/[TIMESTAMP]/[PATH]/[FILE]
```

Title Token:
```
https://c5988z6292.r-cdn.com/[TOKEN]/[TIMESTAMP]/[PATH]/[FILE]?pushr=[PATH]
```
