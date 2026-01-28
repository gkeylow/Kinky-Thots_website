# System Architecture Diagram

```
                                    ┌─────────────────────────────────────────────────────────────────┐
                                    │                        EXTERNAL SERVICES                        │
                                    │  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐       │
                                    │  │ NOWPayments  │    │  Pushr CDN   │    │    Users     │       │
                                    │  │   (Crypto)   │    │   (Sonic)    │    │  (Browser)   │       │
                                    │  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘       │
                                    └─────────┼──────────────────┼──────────────────┼─────────────────┘
                                              │                  │                  │
                                              │ webhooks/api     │ S3 (videos)      │ HTTPS
                                              ▼                  ▼                  ▼
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                    LINODE VPS (45.33.100.131)                                          │
│  ┌────────────────────────────────────────────────────────────────────────────────────────────────┐    │
│  │                                      nginx reverse proxy                                       │    │
│  │                               (SSL termination, Let's Encrypt)                                 │    │
│  │         kinky-thots.xxx :443 ──────────────────────┐                                           │    │
│  │         kinky-thots.com :443 ──────────────────────┤                                           │    │
│  │         mail.kinky-thots.com :443 ─────────┐       │                                           │    │
│  └────────────────────────────────────────────┼───────┼───────────────────────────────────────────┘    │
│                                               │       │                                                │
│  ┌────────────────────┐                       │       │                                                │
│  │   Mail Server      │◄──────────────────────┘       │                                                │
│  │ (docker-mailserver)│                               │                                                │
│  │    :25/587/993     │                               │                                                │
│  └────────┬───────────┘                               │                                                │
│           │ SMTP                                      │                                                │
│           ▼                                           ▼                                                │
│  ┌────────────────────┐                    ┌──────────────────┐                                        │
│  │   Mail Webui       │                    │   WireGuard VPN  │                                        │
│  │  (Flask :8080)     │                    │  10.100.0.1/24   │                                        │
│  └────────────────────┘                    └────────┬─────────┘                                        │
└─────────────────────────────────────────────────────┼──────────────────────────────────────────────────┘
                                                      │
                                                      │ :51820/udp
                                                      │ encrypted tunnel
                                                      ▼
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                    HOME SERVER (10.100.0.2)                                            │
│                                                                                                        │
│  ┌──────────────────────────────────────────── Docker ─────────────────────────────────────────────┐   │
│  │                                                                                                 │   │
│  │   ┌─────────────────────┐         ┌─────────────────────┐         ┌─────────────────────┐       │   │
│  │   │     kinky-web       │         │    kinky-backend    │         │     kinky-rtmp      │       │   │
│  │   │    (Apache/PHP)     │ ◄─────► │     (Node.js)       │         │    (nginx-rtmp)     │       │   │
│  │   │       :80           │  API    │       :3002         │         │   :1935 (RTMP)      │       │   │
│  │   │                     │ proxy   │                     │         │   :8080 (HLS)       │       │   │
│  │   │  • Static files     │         │  • WebSocket chat   │         │                     │       │   │
│  │   │  • PHP pages        │         │  • REST API         │         │  • Receives RTMP    │       │   │
│  │   │  • HLS streaming    │ ◄───────│  • Auth (JWT)       │────────►│  • Outputs HLS      │       │   │
│  │   │                     │   HLS   │  • Payments         │  HLS    │                     │       │   │
│  │   └─────────────────────┘  files  │  • Subscriptions    │  notify └─────────────────────┘       │   │
│  │             │                     │                     │                   ▲                   │   │
│  │             │                     └──────────┬──────────┘                   │                   │   │
│  │             │                                │                              │                   │   │
│  │             │ uploads                        │ queries                      │ RTMP stream       │   │
│  │             ▼                                ▼                              │                   │   │
│  │   ┌─────────────────────┐         ┌─────────────────────┐                   │                   │   │
│  │   │     /uploads        │         │      kinky-db       │                   │                   │   │
│  │   │   (shared volume)   │         │     (MariaDB)       │                   │                   │   │
│  │   │                     │         │       :3306         │                   │                   │   │
│  │   │  • User uploads     │         │                     │                   │                   │   │
│  │   │  • Gallery images   │         │  • Users table      │                   │                   │   │
│  │   └─────────────────────┘         │  • Subscriptions    │                   │                   │   │
│  │                                   │  • Payments         │                   │                   │   │
│  │                                   │  • Chat logs        │                   │                   │   │
│  │                                   └─────────────────────┘                   │                   │   │
│  │                                                                             │                   │   │
│  └─────────────────────────────────────────────────────────────────────────────┼───────────────────┘   │
│                                                                                │                       │
└────────────────────────────────────────────────────────────────────────────────┼───────────────────────┘
                                                                                 │
                                                                                 │
                                                                    ┌────────────┴────────────┐
                                                                    │     OBS / Broadcaster   │
                                                                    │  rtmp://server:1935/    │
                                                                    │       live/stream       │
                                                                    └─────────────────────────┘
```

## Data Flow Summary

```
User Browser ──HTTPS──► Linode nginx ──WireGuard──► kinky-web ──proxy──► kinky-backend ──SQL──► kinky-db
                                                        │                      │
                                                        │                      ├──► NOWPayments (payments)
                                                        │                      └──► Pushr CDN (video fetch)
                                                        │
                                                        └──► /hls/*.m3u8 (live stream playback)

OBS ──RTMP──► kinky-rtmp ──HLS──► /hls/ volume ──► kinky-web ──► User Browser (HLS.js)

kinky-backend ──SMTP──► Mail Server ──► User (password reset emails)
```

## Key Connections

| From | To | Protocol | Purpose |
|------|-----|----------|---------|
| Browser | Linode nginx | HTTPS | All web traffic |
| Linode | Home Server | WireGuard | VPN tunnel (bypasses CG-NAT) |
| kinky-web | kinky-backend | HTTP | API proxy (`/api/*`) |
| kinky-backend | kinky-db | MySQL | User/subscription data |
| kinky-backend | NOWPayments | HTTPS | Create payments, webhooks |
| kinky-backend | Pushr CDN | S3 | Video storage/retrieval |
| kinky-backend | Mail Server | SMTP | Transactional emails |
| OBS | kinky-rtmp | RTMP | Live stream ingest |
| kinky-rtmp | kinky-web | Filesystem | HLS segments (`/hls/`) |

## Component Details

### External Services
- **NOWPayments**: Cryptocurrency payment processor for subscriptions
- **Pushr CDN (Sonic)**: S3-compatible storage for video content
- **Users**: End users accessing the site via web browsers

### Linode VPS (45.33.100.131)
- **nginx**: Reverse proxy with SSL termination (Let's Encrypt)
- **WireGuard**: VPN server for secure tunnel to home server
- **Mail Server**: docker-mailserver for transactional emails
- **Mail Webui**: Flask app for mail administration

### Home Server Docker Containers
- **kinky-web**: Apache/PHP serving static files and PHP pages
- **kinky-backend**: Node.js API server (WebSocket chat, REST API, auth, payments)
- **kinky-rtmp**: nginx-rtmp for live streaming (RTMP ingest, HLS output)
- **kinky-db**: MariaDB database for persistent storage

### Ports
| Service | Port | Protocol |
|---------|------|----------|
| HTTPS | 443 | TCP |
| HTTP | 80 | TCP |
| WireGuard | 51820 | UDP |
| RTMP | 1935 | TCP |
| Backend API | 3002 | TCP |
| MariaDB | 3306 | TCP |
| SMTP | 25/587 | TCP |
| IMAP | 993 | TCP |
