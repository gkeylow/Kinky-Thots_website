# Kinky-Thots Performance Benchmark Log

This document tracks performance benchmarks over time to monitor improvements and regressions.

---

## Benchmark #1 - December 31, 2024 @ 02:30 UTC

### Test Environment
- **Server**: Apache2/PHP 8.4 (local)
- **Database**: MariaDB 10.11
- **CDN**: Pushr CDN (Sonic S3)
- **Backend**: Node.js on port 3001
- **Test Tool**: Apache Bench (ab), curl

---

### Page Response Times (TTFB)

| Page | TTFB | Total Time | Size |
|------|------|------------|------|
| index.html | 0.001s | 0.001s | 10,880b |
| free-content.php | 0.001s | 0.001s | 10,285b |
| gallery.php | 0.001s | 0.001s | 8,163b |
| live.html | 0.001s | 0.001s | 6,393b |
| sissylonglegs.html | 0.001s | 0.001s | 11,776b |
| bustersherry.html | 0.001s | 0.001s | 11,652b |
| terms.html | 0.001s | 0.001s | 6,039b |

**Result**: All pages under 2ms TTFB ✅

---

### Load Testing (Apache Bench)

#### Light Load (100 requests, 10 concurrent)
```
Server Software:        Apache/2.4.62
Document Path:          /
Requests per second:    2849.14 [#/sec]
Time per request:       3.510 [ms]
Failed requests:        0
```

#### Heavy Load (1000 requests, 50 concurrent)
```
Server Software:        Apache/2.4.62
Document Path:          /
Requests per second:    3252.17 [#/sec]
Time per request:       15.374 [ms]
Failed requests:        0

Connection Times (ms):
              min  mean[+/-sd] median   max
Connect:        0    0   0.1      0       1
Processing:     2   15   4.6     15      36
Waiting:        2   15   4.5     14      36
Total:          2   15   4.6     15      36

Percentage of requests served within a certain time (ms):
  50%     15
  66%     17
  75%     18
  90%     21
  95%     23
  99%     29
 100%     36 (longest request)
```

**Result**: 3,252 req/sec with zero failures ✅

---

### Asset Compression (Gzip)

| Asset | Uncompressed | Compressed | Reduction |
|-------|--------------|------------|-----------|
| assets/index.css | 8,787b | 2,097b | 76% |
| assets/porn.css | 6,428b | 1,585b | 75% |
| assets/live.css | 3,883b | 1,097b | 72% |
| assets/gallery.css | 3,413b | 1,020b | 70% |
| assets/dist/main.js | 26,578b | 5,231b | 80% |

**Result**: Average 75% compression ratio ✅

---

### CDN Performance (Pushr/Sonic)

| Metric | Value |
|--------|-------|
| CDN TTFB | ~531ms |
| Video Count | 21 |
| Total Size | ~3.3GB |
| Base URL | https://6318.s3.nvme.de01.sonic.r-cdn.com |

**Note**: CDN latency is external and depends on geographic location.

---

### Service Health

| Service | Status | Port |
|---------|--------|------|
| Apache | ✅ Running | 80 |
| Node.js Backend | ✅ Running | 3001 |
| MariaDB | ✅ Running | 3306 |

---

### Summary

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| TTFB | < 200ms | < 2ms | ✅ PASS |
| Requests/sec | > 1000 | 3,252 | ✅ PASS |
| Failed Requests | 0 | 0 | ✅ PASS |
| Gzip Enabled | Yes | Yes | ✅ PASS |
| 99th Percentile | < 500ms | 29ms | ✅ PASS |

**Overall Score**: EXCELLENT

---

## How to Run Benchmarks

### Quick Page Test
```bash
for page in index.html free-content.php gallery.php live.html; do
  curl -so /dev/null -w "$page: %{time_total}s (TTFB: %{time_starttransfer}s)\n" http://localhost/$page
done
```

### Load Test (Apache Bench)
```bash
# Light load
ab -n 100 -c 10 http://localhost/

# Heavy load
ab -n 1000 -c 50 http://localhost/

# Stress test
ab -n 5000 -c 100 http://localhost/
```

### Check Gzip Compression
```bash
# Compare compressed vs uncompressed
curl -so /dev/null -w "Compressed: %{size_download}b\n" -H "Accept-Encoding: gzip" http://localhost/assets/index.css
curl -so /dev/null -w "Uncompressed: %{size_download}b\n" http://localhost/assets/index.css
```

### CDN Latency Test
```bash
curl -so /dev/null -w "CDN TTFB: %{time_starttransfer}s\n" "https://6318.s3.nvme.de01.sonic.r-cdn.com/videos/IMG_0291.mp4" -r 0-1
```

---

## Benchmark #2 - December 31, 2025 @ 19:13 UTC

### Test Environment
- **Server**: Apache/2.4.65 + PHP (Docker: kinky-web)
- **Database**: MariaDB 10.11 (Docker: kinky-db)
- **CDN**: Pushr CDN (Sonic S3)
- **Backend**: Node.js on port 3001 (Docker: kinky-backend)
- **Test Tool**: Apache Bench (ab), curl
- **Changes Since Last**: Fixed gallery upload Docker volume path, added PayPal integration, user profiles

---

### Page Response Times (TTFB)

| Page | TTFB | Total Time | Size |
|------|------|------------|------|
| index.html | 0.001s | 0.001s | 12,782b |
| free-content.php | 0.002s | 0.003s | 35,705b |
| gallery.php | 0.002s | 0.002s | 2,701b |
| live.html | 0.001s | 0.001s | 12,314b |
| sissylonglegs.html | 0.001s | 0.001s | 10,964b |
| bustersherry.html | 0.001s | 0.001s | 11,195b |
| terms.html | 0.001s | 0.001s | 3,756b |

**Result**: All pages under 3ms TTFB ✅

---

### Load Testing (Apache Bench)

#### Light Load (100 requests, 10 concurrent)
```
Server Software:        Apache/2.4.65
Document Path:          /
Requests per second:    3378.15 [#/sec]
Time per request:       2.960 [ms]
Failed requests:        0
```

#### Heavy Load (1000 requests, 50 concurrent)
```
Server Software:        Apache/2.4.65
Document Path:          /
Requests per second:    2940.61 [#/sec]
Time per request:       17.003 [ms]
Failed requests:        0

Connection Times (ms):
              min  mean[+/-sd] median   max
Connect:        0    0   0.4      0       2
Processing:     1   16   3.3     17      30
Waiting:        1   16   3.1     16      30
Total:          4   17   3.0     17      30

Percentage of requests served within a certain time (ms):
  50%     17
  66%     18
  75%     18
  90%     20
  95%     21
  99%     22
 100%     30 (longest request)
```

**Result**: 2,940 req/sec with zero failures ✅

---

### Asset Compression (Gzip)

| Asset | Uncompressed | Compressed | Reduction |
|-------|--------------|------------|-----------|
| assets/dist/css/main.css | 22,755b | 4,546b | 81% |
| assets/dist/js/main.js | 2,386b | 872b | 64% |
| assets/dist/css/media-gallery.css | 11,309b | 2,739b | 76% |

**Result**: Average 74% compression ratio ✅

---

### CDN Performance (Pushr/Sonic)

| Metric | Value |
|--------|-------|
| CDN TTFB | ~523ms |
| Base URL | https://6318.s3.nvme.de01.sonic.r-cdn.com |

**Note**: CDN latency is external and depends on geographic location.

---

### Service Health

| Service | Container | Status | Port |
|---------|-----------|--------|------|
| Apache | kinky-web | ✅ Running (39h) | 80 |
| Node.js Backend | kinky-backend | ⚠️ Unhealthy* | 3001 |
| MariaDB | kinky-db | ✅ Healthy (45h) | 3306 |
| nginx-rtmp | kinky-rtmp | ✅ Running (45h) | 1935 |

*Backend API responding normally despite unhealthy status (health check timing issue)

---

### Summary

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| TTFB | < 200ms | < 3ms | ✅ PASS |
| Requests/sec | > 1000 | 2,940 | ✅ PASS |
| Failed Requests | 0 | 0 | ✅ PASS |
| Gzip Enabled | Yes | Yes | ✅ PASS |
| 99th Percentile | < 500ms | 22ms | ✅ PASS |

**Overall Score**: EXCELLENT

---

## Benchmark #3 - January 3, 2026 @ 22:16 UTC

### Test Environment
- **Server**: Apache/2.4.65 + PHP 8.4 (Docker: kinky-web)
- **Database**: MariaDB 10.11 (Docker: kinky-db)
- **CDN**: Pushr CDN (Sonic S3)
- **Backend**: Node.js on port 3001 (Docker: kinky-backend)
- **Test Tool**: Apache Bench (ab), curl
- **Changes Since Last**: Added immutable cache headers, font/video caching, JSON compression

---

### Page Response Times (TTFB)

| Page | TTFB | Total Time | Size |
|------|------|------------|------|
| index.html | 0.001s | 0.001s | 13,931b |
| free-content.php | 0.001s | 0.002s | 21,079b |
| basic-content.php | 0.001s | 0.001s | 14,819b |
| premium-content.php | 0.001s | 0.001s | 16,881b |
| gallery.php | 0.002s | 0.002s | 3,413b |
| live.html | 0.001s | 0.001s | 13,496b |
| sissylonglegs.html | 0.001s | 0.001s | 12,213b |
| bustersherry.html | 0.001s | 0.001s | 12,448b |
| terms.html | 0.001s | 0.001s | 4,999b |
| subscriptions.html | 0.001s | 0.001s | 19,628b |
| profile.html | 0.001s | 0.001s | 24,360b |

**Result**: All pages under 2ms TTFB ✅

---

### Load Testing (Apache Bench)

#### Light Load (100 requests, 10 concurrent)
```
Server Software:        Apache/2.4.65
Document Path:          /
Requests per second:    2167.04 [#/sec]
Time per request:       4.615 [ms]
Failed requests:        0
```

#### Heavy Load (1000 requests, 50 concurrent)
```
Server Software:        Apache/2.4.65
Document Path:          /
Requests per second:    1732.84 [#/sec]
Time per request:       28.854 [ms]
Failed requests:        0

Connection Times (ms):
              min  mean[+/-sd] median   max
Connect:        0    0   0.7      0       4
Processing:     4   28   6.8     28      59
Waiting:        1   26   6.4     27      56
Total:          4   28   6.8     28      60

Percentage of requests served within a certain time (ms):
  50%     28
  66%     31
  75%     32
  90%     35
  95%     38
  99%     52
 100%     60 (longest request)
```

#### Stress Test (5000 requests, 100 concurrent)
```
Server Software:        Apache/2.4.65
Document Path:          /
Requests per second:    1965.36 [#/sec]
Time per request:       50.881 [ms]
Failed requests:        0

Connection Times (ms):
              min  mean[+/-sd] median   max
Connect:        0    0   1.0      0       9
Processing:     3   50   9.3     50      98
Waiting:        1   49   8.7     48      98
Total:          6   50   9.2     50      98

Percentage of requests served within a certain time (ms):
  50%     50
  66%     54
  75%     56
  90%     63
  95%     67
  99%     74
 100%     98 (longest request)
```

**Result**: ~2,000 req/sec sustained under stress with zero failures ✅

---

### Asset Compression (Gzip)

| Asset | Uncompressed | Compressed | Reduction |
|-------|--------------|------------|-----------|
| assets/dist/css/main.css | 22,744b | 4,531b | 81% |
| assets/dist/js/main.js | 2,386b | 872b | 64% |
| assets/dist/css/media-gallery.css | 12,356b | 2,941b | 77% |
| assets/dist/css/live.css | 16,821b | 3,913b | 77% |
| assets/dist/js/live.js | 17,007b | 5,023b | 71% |

**Result**: Average 74% compression ratio ✅

---

### CDN Performance (Pushr/Sonic)

| Metric | Value |
|--------|-------|
| CDN TTFB | ~526ms |
| Base URL | https://6318.s3.nvme.de01.sonic.r-cdn.com |

**Note**: CDN latency is external and depends on geographic location.

---

### Service Health

| Service | Container | Status | Port |
|---------|-----------|--------|------|
| Apache | kinky-web | ✅ Running (2d) | 80 |
| Node.js Backend | kinky-backend | ✅ Healthy (2d) | 3002→3001 |
| MariaDB | kinky-db | ✅ Healthy (5d) | 3306 |
| nginx-rtmp | kinky-rtmp | ✅ Running (5d) | 1935 |

---

### Optimizations Applied

1. **Improved Cache Headers**:
   - Added `immutable` directive for static assets
   - Added font caching (1 year TTL)
   - Added video/audio caching (30 days TTL)
   - Added AVIF image format support

2. **Additional Compression**:
   - Added JSON response compression
   - Added SVG compression
   - Added manifest.json compression

---

### Summary

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| TTFB | < 200ms | < 2ms | ✅ PASS |
| Requests/sec | > 1000 | 1,965 | ✅ PASS |
| Failed Requests | 0 | 0 | ✅ PASS |
| Gzip Enabled | Yes | Yes | ✅ PASS |
| 99th Percentile | < 500ms | 74ms | ✅ PASS |

**Overall Score**: EXCELLENT

---

## Future Benchmarks

Add new benchmark entries below following the same format.

<!-- BENCHMARK TEMPLATE
## Benchmark #N - [DATE] @ [TIME] UTC

### Test Environment
- **Server**:
- **Changes Since Last**:

### Results
[Add results here]

### Notes
[Any observations]
-->
