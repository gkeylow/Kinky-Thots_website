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
