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
