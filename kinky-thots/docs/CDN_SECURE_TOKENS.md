# PushrCDN Secure Tokens Guide

## Overview

Secure tokens protect your CDN content from unauthorized access and hotlinking. Every request must include a valid token that expires after a set time period.

## Configuration

### CDN Hostnames

- **Pull CDN (Images)**: `c5988z6292.r-cdn.com` (Zone 6292)
- **Push CDN (My Images)**: `c5988z6294.r-cdn.com` (Zone 6294)

### Credentials

- **API Key**: `REDACTED_PUSHR_API_KEY`
- **Secret Token**: `e872d33deed25bcbcd1ddcb596dfc1872f9a6a07`

## How It Works

1. Your server generates a unique token using:
   - Client IP address
   - File/directory path
   - Expiration timestamp
   - Secret token
   
2. The token is inserted into the URL

3. PushrCDN validates the token on each request

4. Valid tokens → Content served | Invalid/expired tokens → HTTP 403 error

## Token Types

### File Token
Protects a single file. Use for:
- Individual images
- Single video files
- Downloads

### Title Token
Protects entire directories. Use for:
- HLS video (multiple .ts and .m3u8 files)
- Image galleries
- Multiple related files

## PHP Implementation

### Basic Usage

```php
<?php
require_once 'backend/pushr-secure-tokens.php';

$tokenGen = new PushrSecureTokens();

// Secure an image (valid for 1 hour)
$secureImageUrl = $tokenGen->secureImage('/uploads/photo.jpg');

// Secure a video (valid for 2 hours)
$secureVideoUrl = $tokenGen->secureVideo('/porn/video.mp4', 7200);

// Secure HLS video directory
$secureHlsUrl = $tokenGen->secureHLS('/porn/Haley/hls/', 'playlist.m3u8');
?>

<img src="<?php echo $secureImageUrl; ?>" alt="Protected Image">
<video src="<?php echo $secureVideoUrl; ?>" controls></video>
```

## Quick Start

1. Include the library:
```php
require_once 'backend/pushr-secure-tokens.php';
$tokenGen = new PushrSecureTokens();
```

2. Generate secure URLs:
```php
$secureUrl = $tokenGen->secureImage('/uploads/photo.jpg');
```

3. Use in HTML:
```php
<img src="<?php echo $secureUrl; ?>">
```

## Testing

Run the test script:
```bash
cd /var/www/kinky-thots
php backend/pushr-secure-tokens.php
```

---

**Last Updated**: December 2024
