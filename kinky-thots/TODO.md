# Kinky-Thots Implementation TODO

## ✅ COMPLETED (Jan 5, 2026)

### NOWPayments Crypto Integration
- [x] Removed PayPal integration (policy violation for adult content)
- [x] Added NOWPayments API configuration
- [x] JWT authentication for subscription API
- [x] Created subscription plans in NOWPayments dashboard:
  - Basic (kinky_thot_basic): `1682032527` - $8/31 days
  - Premium (kinky_thot_premium): `381801900` - $15/31 days
- [x] Configured redirect URLs (success/failed/partial)
- [x] Updated checkout.html with crypto payment UI
- [x] Added partial payment handling page
- [x] Backend endpoints:
  - `GET /api/payments/status` - Check API connection
  - `GET /api/payments/currencies` - List 227+ cryptocurrencies
  - `POST /api/subscriptions/checkout` - Create subscription/invoice
  - `POST /api/nowpayments/webhook` - Handle IPN callbacks

---

## ✅ COMPLETED (Jan 14, 2026)

### NOWPayments Webhook Testing
- [x] Verified API connection status (production mode)
- [x] Tested webhook endpoint reachability externally
- [x] Verified IPN signature validation (HMAC-SHA512)
- [x] Tested full payment flow end-to-end with test user
- [x] Fixed database ENUM to include yearly tier

### Subscription Tier Changes
- [x] Changed "Lifetime" ($250) to "Yearly" ($120)
- [x] Updated backend/server.js - SUBSCRIPTION_TIERS and webhook handler
- [x] Updated subscriptions.html - toggle, prices, FAQ
- [x] Updated checkout.html - pricing display
- [x] Updated database ENUM: `free`, `basic`, `premium`, `yearly`, `vip`
- [x] Updated email templates for yearly tier

---

## 🔄 IN PROGRESS

### Subscription Renewal Testing
- [ ] Test subscription renewal flow (auto-charge after 31 days)

---

## 🔒 SECURITY HARDENING (Post-Compromise — Feb 5, 2026)

Following the Feb 4, 2026 mail server compromise (see `docs/2026-02-04-mail-server-compromise.md`).
Full audit: `docs/security-audit-2026-02-05.md`

### Already Completed
- [x] Changed compromised mail password
- [x] Blocked attacker IP (iptables + fail2ban 180-day ban)
- [x] Flushed spam from mail queue
- [x] Tightened fail2ban (3 retries / 10 min)
- [x] Added Postfix rate limiting (30 msg/min, 50 rcpt/msg, 100 conn/min)
- [x] Updated DMARC to `p=reject` with strict alignment
- [x] Fixed SPF to correct IP with hard fail (`-all`)
- [x] Updated backend SMTP credentials
- [x] Disabled SSH password auth on Linode (key-only)
- [x] Installed fail2ban for SSH on Linode host
- [x] Restricted Portainer Agent to localhost (127.0.0.1:9001)
- [x] Enabled unattended-upgrades on Linode

### Remaining Hardening Tasks

**1. Install rkhunter on Linode** — LOW ✅
- [x] Install and configure rkhunter (rootkit scanner)
- [x] Run initial baseline scan (clean — 0 warnings)
- [x] Set up weekly automated scan via cron (`/etc/cron.weekly/rkhunter-scan`)
- [x] Disabled host postfix (installed as dependency, masked via systemd)
- [x] Auto-updates on apt install (`APT_AUTOGEN=true`)

**2. Fix MX record for kinky-thots.com** — HIGH ✅
- [x] Fixed MX record: `kinky-thots.com MX 10 mail.kinky-thots.com` (→ 45.79.208.9)
- [x] Was: MX on `mail` subdomain pointing backwards to `kinky-thots.com` (wrong IP)
- [x] Verified: MX, SPF, DKIM, DMARC all correct

**3. Move Linode API token out of docs** — MEDIUM ✅
- [x] Token added to `config/.credentials.md`
- [x] Redacted `docs/.linode-api.md` (kept non-sensitive reference info only)

**4. Restrict MariaDB user `gkeylow` host** — HIGH ✅
- [x] Changed `gkeylow` Host from `%` → `172.%` (Docker network only)
- [x] Dropped `root@%` (wildcard) — `root@localhost` retained
- [x] Verified backend still connects (3 users in DB)

**5. CDN API key rotation** — N/A
- Pushr CDN only issues one API key per account; no rotation mechanism available
- No action needed

**6. Daily/Weekly security cron scripts** — MEDIUM ✅
- [x] Created `/etc/cron.daily/security-check` (mail queue, fail2ban, container health, disk)
- [x] Created `/etc/cron.weekly/security-review` (SSH attacks, fail2ban, SSL expiry, mail volume, packages)
- [x] Tested both scripts — all checks passing
- Logs: `/var/log/security-daily.log`, `/var/log/security-weekly.log`

**7. CSP/Security headers** — MEDIUM ✅
- [x] `.xxx` — nginx snippet `/etc/nginx/snippets/security-headers.conf` on Linode (45.79.208.9)
- [x] `.com` — Apache conf `/etc/apache2/conf-available/security-headers.conf` on NJ Linode (192.155.91.241)
- [x] Headers: X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS, CSP
- [x] CSP whitelists CDN, Turnstile, NOWPayments, jsdelivr, Google Fonts
- [x] Removed duplicate headers (Apache upstream stripped via proxy_hide_header on Linode)
- [ ] Monitor browser console for CSP violations — may need tuning

**8. Linode Cloud Firewall** — MEDIUM ✅
- [x] Created "NotYourFirewall" (ID: 3774058) via API
- [x] Attached to mail.kinky-thots (90512880)
- [x] Inbound: SSH(22), HTTP(80), HTTPS(443), SMTP(25), SMTPS(465), Submission(587), IMAPS(993), RTMP(1935)
- [x] Default inbound: DROP — all other ports blocked at network level
- [x] Outbound: ACCEPT all

### Spamhaus Delisting (manual)
- [ ] Request removal at https://check.spamhaus.org/listed/?searchterm=45.79.208.9
- [ ] IP `45.79.208.9` listed as SBL CSS (127.0.0.3) due to compromise spam
- [ ] Monitor delisting — may take 24-48 hours after request

---

## ✅ COMPLETED (Jan 2026)

### Email Server (docker-mailserver)
- [x] docker-mailserver deployed on Linode (45.79.208.9)
- [x] DNS records: SPF, DKIM, DMARC configured
- [x] Let's Encrypt SSL for mail
- [x] Mailboxes: admin@, sissylonglegs@, lilsexfreak@
- [x] Aliases: info@ → admin@, gkeylow@ → admin@
- [x] Rspamd spam filtering active
- [x] Application SMTP updated in `.env`

---

## 📋 MEDIUM PRIORITY

### Payment System Enhancements
- [ ] Payment history page for users
- [ ] Email notifications on successful payment
- [ ] Email reminders before subscription expires
- [ ] Admin dashboard for viewing payments/subscriptions
- [ ] Handle subscription cancellation properly

### Content Management
- [ ] Video upload admin interface
- [ ] Automatic thumbnail generation on upload
- [ ] Content scheduling (publish date)

---

## 📋 LOW PRIORITY / DEFERRED

### Stream Notifications
- [ ] Web push notifications when stream goes live
- [ ] Service worker for push notifications
- [ ] Notification permission prompt

### PWA Support
- [ ] manifest.json with app metadata
- [ ] Service worker for offline caching
- [ ] Install prompt for mobile users

### Video Features
- [ ] Like/favorite system
- [ ] Watch history tracking
- [ ] Video recommendations

### Chat Enhancements
- [ ] Custom emoji support
- [ ] @mentions with notifications
- [ ] Message reactions

### Admin Dashboard
- [ ] User management (ban, tier changes)
- [ ] Content management
- [ ] Analytics (views, signups, revenue)

---

## ✅ COMPLETED (Dec 31, 2024)

### User Authentication System
- [x] Database: `users` table created
- [x] JWT-based auth with bcrypt password hashing
- [x] Backend endpoints: register, login, profile
- [x] WebSocket JWT auth for chat
- [x] Auth modal in live.html

### Password Reset Flow
- [x] Nodemailer email transporter
- [x] Forgot/reset password endpoints
- [x] reset-password.html page

### Chat Moderation Tools
- [x] Mod commands: /ban, /unban, /mute, /unmute, /slow, /clear
- [x] VIP tier gets moderator access

### Content Gating by Subscription Tier
- [x] Duration-based tiers (free <1min, basic 1-5min, premium >5min)
- [x] Content pages: free-content.php, basic-content.php, premium-content.php
- [x] subscriptions.html with tier display

### User Profile Page
- [x] profile.html with account info
- [x] Chat color customization
- [x] Password change form

---

## Reference

### NOWPayments Configuration
Credentials stored in `config/.credentials.md` (gitignored, 600 perms).

### NOWPayments Dashboard URLs
| Setting | URL |
|---------|-----|
| IPN Callback (Webhook) | `https://kinky-thots.xxx/api/nowpayments/webhook` |
| Success Page | `https://kinky-thots.xxx/checkout.html?status=success` |
| Failed Page | `https://kinky-thots.xxx/checkout.html?status=failed` |
| Partial Payment | `https://kinky-thots.xxx/checkout.html?status=partial` |

### Key Files
| Feature | Files |
|---------|-------|
| Payments | `backend/server.js`, `checkout.html` |
| Auth | `src/js/auth.js`, `live.html` |
| Subscriptions | `subscriptions.html`, `profile.html` |
| Content | `free-content.php`, `plus-content.php`, `premium-content.php` |
