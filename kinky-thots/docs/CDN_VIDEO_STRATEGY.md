# CDN Video Hosting Strategy

## Problem
- Videos in `/media/porn` are large (77MB+)
- PushrCDN API has file size limits (413 Request Entity Too Large)
- Need to save local storage space

## Solutions

### Option 1: Use Pull Zones (Recommended)
**How it works:**
- Configure PushrCDN pull zone to fetch from your origin
- Videos stay on your server initially
- CDN caches them on first request
- Subsequent requests served from CDN edge servers

**Advantages:**
- ✅ No manual upload needed
- ✅ Works with large files
- ✅ Automatic caching
- ✅ Can delete local files after they're cached

**Setup:**
1. Configure pull zone in PushrCDN dashboard:
   - Origin URL: `https://kinky-thots.com`
   - Zone: videos (6293)
2. Get CDN URL (e.g., `https://c5988z6293.r-cdn.com`)
3. Update site to use CDN URLs
4. After files are cached, optionally delete local copies

### Option 2: FTP Upload to Storage Zone
**How it works:**
- Upload videos via FTP to PushrCDN storage
- Videos hosted entirely on CDN
- No local storage needed

**Advantages:**
- ✅ Complete offload from local server
- ✅ No origin server load

**Disadvantages:**
- ❌ Requires FTP client
- ❌ Manual upload process
- ❌ Time-consuming for many files

**FTP Details:**
- Host: `ftp.pushrcdn.com` (check PushrCDN dashboard)
- Zone: my-videos (6295)
- Credentials: From PushrCDN dashboard

### Option 3: Hybrid Approach
**How it works:**
- Use pull zone for automatic caching
- Keep local files as backup
- Serve from CDN for bandwidth savings

**Advantages:**
- ✅ Best of both worlds
- ✅ Redundancy
- ✅ Easy setup

## Recommended Implementation

### Step 1: Configure Pull Zone
In PushrCDN dashboard:
1. Go to zone "videos" (6293)
2. Set Origin URL: `https://kinky-thots.com`
3. Enable pull mode
4. Get CDN URL

### Step 2: Update Site Configuration
Update `/var/www/kinky-thots/config/pushr-cdn.json`:
```json
{
  "zones": {
    "videos": {
      "zone_id": "6293",
      "cdn_url": "https://c5988z6293.r-cdn.com",
      "origin_url": "https://kinky-thots.com"
    }
  }
}
```

### Step 3: Update porn.php
Modify to serve videos from CDN URL instead of origin.

### Step 4: Test
1. Access a video via CDN URL
2. Verify it loads (CDN fetches from origin)
3. Check CDN headers on subsequent requests

### Step 5: Optional - Delete Local Files
After confirming CDN is serving files:
```bash
# Backup first!
tar -czf /backup/porn-videos-$(date +%Y%m%d).tar.gz /media/porn/

# Then delete to save space
rm -rf /media/porn/kinky-thots-shorts/*.mp4
```

## Current Status

- ✅ API is online
- ✅ Pull zones available (6293 videos)
- ✅ Storage zones available (6295 my-videos)
- ❌ API upload fails for large files (>77MB)
- ⚠️ Need CDN URL from pull zone configuration

## Next Steps

**What I need from you:**

1. **Configure Pull Zone** in PushrCDN dashboard:
   - Zone: "videos" (6293)
   - Set Origin: `https://kinky-thots.com`
   - Get the CDN URL

2. **Provide CDN URL** for videos zone
   - Format: `https://cXXXXzXXXX.r-cdn.com`

3. **Decide on file structure:**
   - Should CDN mirror `/porn/kinky-thots-shorts/video.mp4`?
   - Or flat structure at root?

Once configured, I'll update:
- `porn.php` to use CDN URLs
- Backend to return CDN URLs for videos
- Gallery to load videos from CDN

## Storage Savings

Current `/media/porn` usage:
```bash
du -sh /media/porn
```

After moving to CDN:
- Local storage freed
- Bandwidth reduced
- Faster delivery worldwide
