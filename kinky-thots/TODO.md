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

## 📋 HIGH PRIORITY

### Email Migration - Google Workspace to Self-Hosted SMTP
Research and implement self-hosted email server in Docker to replace Google Workspace.

**Options to Research:**
- [ ] **Mailcow** - Full-featured, Docker-native, includes Roundcube/SOGo webmail
- [ ] **docker-mailserver** - Lightweight, production-ready, well-documented
- [ ] **Postal** - High-volume sending, good for transactional email
- [ ] **iRedMail** - Feature-rich, supports multiple domains
- [ ] **Mailu** - Lightweight alternative to Mailcow

**Migration Tasks:**
- [ ] Choose SMTP solution based on research
- [ ] Set up DNS records (MX, SPF, DKIM, DMARC)
- [ ] Configure SSL certificates for mail server
- [ ] Set up Docker container for mail server
- [ ] Create mailboxes (admin@kinky-thots.com)
- [ ] Migrate existing emails from Google Workspace
- [ ] Update application SMTP settings in `.env`
- [ ] Test transactional emails (password reset, subscription confirmations)

**Current Email Setup:**
```
Provider: Google Workspace
SMTP: smtp.gmail.com:587
Email: admin@kinky-thots.com
```

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
```bash
# .env
NOWPAYMENTS_API_KEY=DNF2V49-EF7MTCW-JHHWBNN-3T1AGQZ
NOWPAYMENTS_IPN_SECRET=rnuWG3MSWP3wn5j3asK1N0I39cCWOrRc
NOWPAYMENTS_EMAIL=admin@kinky-thots.com
NOWPAYMENTS_BASIC_PLAN_ID=1682032527
NOWPAYMENTS_PREMIUM_PLAN_ID=381801900
```

### NOWPayments Dashboard URLs
| Setting | URL |
|---------|-----|
| IPN Callback (Webhook) | `https://kinky-thots.xxx/api/nowpayments/webhook` |
| Success Page | `https://kinky-thots.xxx/checkout.html?status=success` |
| Failed Page | `https://kinky-thots.xxx/checkout.html?status=failed` |
| Partial Payment | `https://kinky-thots.xxx/checkout.html?status=partial` |

IPN Secret: `rnuWG3MSWP3wn5j3asK1N0I39cCWOrRc`

### Key Files
| Feature | Files |
|---------|-------|
| Payments | `backend/server.js`, `checkout.html` |
| Auth | `src/js/auth.js`, `live.html` |
| Subscriptions | `subscriptions.html`, `profile.html` |
| Content | `free-content.php`, `basic-content.php`, `premium-content.php` |
