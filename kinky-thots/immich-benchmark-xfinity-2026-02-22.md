# Immich Benchmark Report
**Date**: 2026-02-22  
**Immich Version**: v2.5.6  
**ISP**: Xfinity  

---

## Network (Server ISP)

| Metric | Xfinity | T-Mobile Home Internet |
|--------|---------|------------------------|
| Download (10MB) | **137.5 Mbps** | — |
| Download (50MB) | **434.8 Mbps** | — |
| Upload (10MB) | **29.1 Mbps** | — |
| Upload (50MB) | **23.4 Mbps** | — |
| Latency (ping 8.8.8.8) | **28.0 ms avg** | — |

---

## External API (Phone Experience: phone → Linode → SSH tunnel → server)

| Endpoint | Xfinity | T-Mobile |
|----------|---------|----------|
| SSL Handshake | **141 ms** | — |
| Server Ping | **328 ms** | — |
| Login (auth) | **505 ms** | — |
| Timeline Buckets | **252 ms** | — |
| Albums List | **364 ms** | — |
| Memories | **471 ms** | — |
| People | **309 ms** | — |
| Thumbnail (preview, 320KB) | **524 ms** | — |
| Thumbnail (small, 9.7KB) | **372 ms** | — |
| Original Download (2.35MB) | **1228 ms** @ 1.83 MB/s | — |
| Search (50 results) | **411 ms** | — |

---

## Upload Benchmark (External: simulates phone → tunnel → server)

| File Size | Time | Throughput | T-Mobile |
|-----------|------|------------|----------|
| 3 MB photo | **2.75s** | **1.09 MB/s** | — |
| 10 MB photo | **5.32s** | **1.88 MB/s** | — |

---

## Local API (On-box, no tunnel overhead)

| Endpoint | Xfinity |
|----------|---------|
| Server Ping | 2 ms |
| Server Version | 3 ms |
| Server Statistics | 5 ms |
| Login (auth) | 95 ms |
| Timeline Buckets | 15 ms |
| Albums List | 118 ms |
| Memories | 70 ms |
| People | 80 ms |
| Thumbnail (preview, 320KB) | 94 ms |
| Thumbnail (small, 9.7KB) | 42 ms |
| Original File (2.35MB) | 166 ms @ 13.5 MB/s |
| Search (50 results) | 21 ms |

---

## Notes
- External latency (~300-500ms per request) is dominated by the **SSH reverse tunnel** round-trip through Linode, not ISP speed.
- Upload throughput (~1-2 MB/s) is bottlenecked by the tunnel, not Xfinity's 23+ MB/s upload capacity.
- T-Mobile column to be filled in when server returns to T-Mobile network.
