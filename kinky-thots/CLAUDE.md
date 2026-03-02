# Kinky-Thots — Quick Reference

> Essential project info loaded every session. For full changelog and detailed history, see [CLAUDE1.md](./CLAUDE1.md).

## Version: 1.9.5 (Feb 8, 2026)

## Tech Stack
- **Web Server**: Apache2 + PHP (Docker: kinky-web)
- **Backend**: Node.js on port 3002 (Docker: kinky-backend) — NOT bind-mounted, rebuild image for changes
- **Database**: MariaDB 10.11 (Docker: kinky-db) — creds in `config/.credentials.md`
- **Streaming**: Owncast v0.2.4 on Linode (170.187.144.130) — creds in `config/.credentials.md`
- **CDN**: Pushr/Sonic S3-compatible storage
- **Build**: Vite + Tailwind CSS — `npm run build` from `/var/www/kinky-thots`
- **SSL**: Let's Encrypt via Linode nginx reverse proxy

## Infrastructure
| Component | IP | Details |
|-----------|-----|---------|
| **Reverse Proxy** | 173.230.140.170 | Linode — nginx, SSL, mail server (IP changed Feb 2026) |
| **Streaming** | 170.187.144.130 | Linode — Owncast (RTMP + HLS) |
| **Production** | local | Home server Docker: kinky-web, kinky-backend, kinky-db, pihole |
| **Tunnel** | localhost:8081, :3003 | SSH reverse tunnel (autossh) to Linode |

## Key Notes
- **live.html** is a redirect to `https://owncast.kinky-thots.xxx` (old page backed up in `/backup/live-page/`)
- **All nav "Live Cam" links** point directly to Owncast with `target="_blank"`
- **Backend changes require Docker rebuild**: `docker compose build backend && docker compose up -d backend`
- **Credentials**: stored in `config/.credentials.md` (gitignored, 600 permissions)
- **Owncast custom CSS/config**: modify via admin API (creds in `config/.credentials.md`)

## Quick Commands
```bash
# Build frontend
npm run build

# Docker
docker ps
docker logs -f kinky-backend
docker compose restart

# Backend rebuild (required for server.js changes)
docker compose build backend && docker compose up -d backend

# SSH tunnel
sudo systemctl status ssh-tunnel
sudo systemctl restart ssh-tunnel

# CDN
cd backend && npm run sonic:sync-manifest

# Dev
npm run dev
npm run lint
npm run format
```

## Rules — OPSEC
- **NEVER add credentials** (passwords, API keys, secrets, tokens) to any git-tracked file. All credentials go in `config/.credentials.md` (gitignored). Reference that file instead.
- **NEVER commit debug logging** that exposes user input, tokens, request bodies, or auth data. If debug logging is added temporarily, it MUST be removed before committing.
- **Verify .gitignore** before creating any new config, credential, or environment file. Confirm it's covered BEFORE staging or committing.
- **No internal infrastructure in client-facing code.** Never expose internal IPs, Docker container names, DB hostnames, or admin API paths in HTML/JS/CSS served to browsers.
- **Clean up temp files** before committing. Remove `.save` files, debug scripts, test dumps, and any throwaway files from the working tree.

## Rules — Code Style
- 4-space indent: PHP
- 2-space indent: JS/CSS
- ES6+ modules, Tailwind utility classes preferred
- Run `npm run lint` and `npm run format` before committing

## SSH Access & Tunnel Infrastructure

### How to SSH into this server
This server is behind NAT and only reachable via a reverse SSH tunnel through the mail/proxy Linode.

**From any machine with the Termius key:**
```bash
ssh -i termius_key \
  -o "ProxyCommand ssh -i termius_key -W 127.0.0.1:2222 -p 22 root@173.230.140.170" \
  -p 2222 root@127.0.0.1
```

**Or add to ~/.ssh/config:**
```
Host kinky-thots
    HostName 127.0.0.1
    Port 2222
    User root
    IdentityFile ~/.ssh/termius_key
    ProxyCommand ssh -i ~/.ssh/termius_key -W 127.0.0.1:2222 -p 22 root@173.230.140.170
```

### Reverse SSH Tunnel (ssh-tunnel.service)
- **Service**: `/etc/systemd/system/ssh-tunnel.service`
- **Managed by**: autossh (persistent, auto-reconnects)
- **Tunnels to**: `root@173.230.140.170` (mail/proxy Linode)
- **Port 2222** on mail proxy (`127.0.0.1:2222`) → this server SSH (port 22)
- Port 2222 is loopback-only on the mail proxy by design — use ProxyCommand above

**Ports forwarded via tunnel:**
| Remote (mail proxy localhost) | Local (this server) |
|-------------------------------|---------------------|
| :2222 | :22 (SSH) |
| :8081 | :80 (web) |
| :3003 | :3002 (backend) |
| :3001 | :3001 |
| :2283 | :2283 |

**Owncast tunnel**: `/etc/systemd/system/ssh-tunnel-owncast.service`
- Tunnels SSH access to Owncast Linode (`root@170.187.144.130`)

### Tunnel Fixes Applied (Feb 28, 2026)
- Removed `ExitOnForwardFailure=yes` from both tunnel services — a single port bind failure no longer tears down all tunnels
- Changed `RestartSec=10` → `RestartSec=30` on both services — reduces reconnect rate and prevents fail2ban triggering
- Added `172.59.114.22` (this server public IP) to `ignoreip` in `/etc/fail2ban/jail.local` on the mail proxy — prevents autossh reconnects from getting this server banned

### Mail Proxy fail2ban (173.230.140.170)
- Config: `/etc/fail2ban/jail.local`
- `maxretry=3`, `findtime=600s`, `bantime=604800s` (7 days)
- Whitelisted IPs: `127.0.0.1/8`, `::1`, `172.59.114.22`
