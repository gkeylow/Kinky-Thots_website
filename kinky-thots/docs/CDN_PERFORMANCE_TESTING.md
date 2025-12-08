# CDN Performance Testing Guide

## Quick Tests

### 1. Browser DevTools Network Tab

**Steps:**
1. Open your website in Chrome/Firefox
2. Press `F12` to open DevTools
3. Go to the **Network** tab
4. Reload the page (`Ctrl+Shift+R` for hard reload)
5. Look at the **Time** column for each resource

**What to Check:**
- Resources from `cdn.kinky-thots.com` and `cdn-video.kinky-thots.com` should load faster
- Check the **Timing** breakdown (hover over time):
  - **Waiting (TTFB)**: Time to First Byte - should be lower with CDN
  - **Content Download**: Should be faster from CDN
- Look for resources served from cache (status 304 or "from disk cache")

**Compare:**
- Direct server: `https://kinky-thots.com/image.jpg`
- CDN: `https://cdn.kinky-thots.com/image.jpg`

### 2. Command Line Speed Test

Test download speed from different locations:

```bash
# Test direct server
time curl -o /dev/null -s -w "Time: %{time_total}s\nSize: %{size_download} bytes\nSpeed: %{speed_download} bytes/sec\n" \
  https://kinky-thots.com/porn/video.mp4

# Test CDN
time curl -o /dev/null -s -w "Time: %{time_total}s\nSize: %{size_download} bytes\nSpeed: %{speed_download} bytes/sec\n" \
  https://cdn-video.kinky-thots.com/porn/video.mp4
```

### 3. Online Speed Testing Tools

#### WebPageTest (Recommended)
- URL: https://www.webpagetest.org/
- Enter your URL: `https://kinky-thots.com`
- Select test location (try multiple locations)
- Click "Start Test"

**Metrics to Watch:**
- **First Contentful Paint (FCP)**: When first content appears
- **Largest Contentful Paint (LCP)**: When main content loads
- **Time to Interactive (TTI)**: When page becomes interactive
- **Total Blocking Time (TBT)**: How long page is unresponsive

#### GTmetrix
- URL: https://gtmetrix.com/
- Test your page
- Compare "Fully Loaded Time" and "Total Page Size"

#### Pingdom
- URL: https://tools.pingdom.com/
- Test from multiple locations
- Compare load times

### 4. Chrome Lighthouse

**Steps:**
1. Open your site in Chrome
2. Press `F12` → **Lighthouse** tab
3. Select "Performance" category
4. Click "Generate report"

**Key Metrics:**
- Performance Score (0-100)
- First Contentful Paint
- Largest Contentful Paint
- Speed Index
- Time to Interactive

## Detailed Performance Testing

### Create Test Script

Save this as `/var/www/kinky-thots/scripts/test-cdn-performance.sh`:

```bash
#!/bin/bash

echo "================================"
echo "CDN Performance Test"
echo "================================"
echo ""

# Test URLs
DIRECT_IMAGE="https://kinky-thots.com/uploads/test-image.jpg"
CDN_IMAGE="https://cdn.kinky-thots.com/uploads/test-image.jpg"
DIRECT_VIDEO="https://kinky-thots.com/porn/test-video.mp4"
CDN_VIDEO="https://cdn-video.kinky-thots.com/porn/test-video.mp4"

echo "Testing Image Loading..."
echo "------------------------"

echo "Direct Server:"
curl -o /dev/null -s -w "  Time: %{time_total}s | TTFB: %{time_starttransfer}s | Speed: %{speed_download} B/s\n" "$DIRECT_IMAGE"

echo "CDN:"
curl -o /dev/null -s -w "  Time: %{time_total}s | TTFB: %{time_starttransfer}s | Speed: %{speed_download} B/s\n" "$CDN_IMAGE"

echo ""
echo "Testing Video Loading..."
echo "------------------------"

echo "Direct Server:"
curl -o /dev/null -s -w "  Time: %{time_total}s | TTFB: %{time_starttransfer}s | Speed: %{speed_download} B/s\n" "$DIRECT_VIDEO"

echo "CDN:"
curl -o /dev/null -s -w "  Time: %{time_total}s | TTFB: %{time_starttransfer}s | Speed: %{speed_download} B/s\n" "$CDN_VIDEO"

echo ""
echo "================================"
```

### Run Multiple Tests

```bash
# Make script executable
chmod +x /var/www/kinky-thots/scripts/test-cdn-performance.sh

# Run test 5 times and average
for i in {1..5}; do
  echo "Test Run $i"
  ./scripts/test-cdn-performance.sh
  sleep 2
done
```

## What to Look For

### CDN is Working Well If:

✅ **Lower TTFB (Time to First Byte)**
- CDN: < 100ms
- Direct: > 200ms

✅ **Faster Download Speeds**
- CDN should be 2-5x faster for large files

✅ **Better Geographic Performance**
- Users far from your server see bigger improvements

✅ **Reduced Server Load**
- Check server CPU/bandwidth usage

✅ **Cache Hits**
- Look for `X-Cache: HIT` headers
- Check CDN dashboard for cache hit ratio

### Red Flags:

❌ **CDN Slower Than Direct**
- DNS not configured correctly
- Content not prefetched to CDN
- CDN not properly warmed up

❌ **High TTFB from CDN**
- Content not cached at edge
- Need to prefetch content

❌ **Inconsistent Performance**
- CDN cache expiring too quickly
- Need to adjust cache settings

## Real User Monitoring

### Add Performance Tracking to Your Pages

Add this JavaScript to your HTML:

```html
<script>
// Log page load performance
window.addEventListener('load', function() {
  setTimeout(function() {
    const perfData = window.performance.timing;
    const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
    const connectTime = perfData.responseEnd - perfData.requestStart;
    const renderTime = perfData.domComplete - perfData.domLoading;
    
    console.log('Page Performance:');
    console.log('  Total Load Time: ' + pageLoadTime + 'ms');
    console.log('  Server Response: ' + connectTime + 'ms');
    console.log('  Render Time: ' + renderTime + 'ms');
    
    // Optional: Send to analytics
    // gtag('event', 'timing_complete', {
    //   name: 'load',
    //   value: pageLoadTime
    // });
  }, 0);
});

// Log resource timing
window.addEventListener('load', function() {
  const resources = performance.getEntriesByType('resource');
  
  resources.forEach(function(resource) {
    if (resource.name.includes('cdn.kinky-thots.com') || 
        resource.name.includes('cdn-video.kinky-thots.com')) {
      console.log('CDN Resource: ' + resource.name);
      console.log('  Duration: ' + resource.duration + 'ms');
      console.log('  Transfer Size: ' + resource.transferSize + ' bytes');
    }
  });
});
</script>
```

## Benchmarking Checklist

- [ ] Test from multiple geographic locations
- [ ] Test with cold cache (first visit)
- [ ] Test with warm cache (repeat visit)
- [ ] Test on different connection speeds (3G, 4G, WiFi)
- [ ] Test on mobile and desktop
- [ ] Compare before/after CDN implementation
- [ ] Monitor over 24-48 hours for consistency
- [ ] Check CDN cache hit ratio in dashboard

## Expected Improvements

### Typical CDN Performance Gains:

| Metric | Without CDN | With CDN | Improvement |
|--------|-------------|----------|-------------|
| TTFB | 200-500ms | 50-150ms | 60-70% faster |
| Image Load | 1-3s | 0.3-1s | 50-70% faster |
| Video Start | 2-5s | 0.5-2s | 60-75% faster |
| Page Load | 3-8s | 1-3s | 50-65% faster |

### Geographic Impact:

- **Local users** (same region as server): 20-40% improvement
- **Distant users** (different continent): 60-80% improvement
- **Mobile users**: 40-70% improvement

## Troubleshooting Slow CDN

If CDN is not faster:

1. **Verify DNS**: `dig cdn.kinky-thots.com`
2. **Check prefetch**: Run prefetch commands
3. **Verify cache headers**: `curl -I https://cdn.kinky-thots.com/file.jpg`
4. **Check CDN dashboard**: Look for cache misses
5. **Test from different locations**: Use VPN or online tools

## Monitoring Commands

```bash
# Check if CDN is serving content
curl -I https://cdn.kinky-thots.com/uploads/test.jpg | grep -i "x-cache\|server\|age"

# Test from multiple locations (using online service)
# Use: https://www.dotcom-tools.com/website-speed-test

# Monitor real-time performance
watch -n 5 'curl -o /dev/null -s -w "CDN: %{time_total}s\n" https://cdn-video.kinky-thots.com/porn/video.mp4'
```

---

**Last Updated**: December 2024
