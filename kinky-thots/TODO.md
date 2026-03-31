# Kinky-Thots TODO

## 🔄 IN PROGRESS

- [ ] Subscription renewal testing — test auto-charge after 31 days (NOWPayments)

---

## 🔒 SECURITY

- [ ] **Spamhaus delisting** — IP `45.79.208.9` listed as SBL CSS (127.0.0.3) from Feb 2026 compromise spam. Request removal at https://check.spamhaus.org/listed/?searchterm=45.79.208.9 (24-48h after request). Note: this is the OLD reverse proxy IP (new: 173.230.140.170).
- [ ] CSP monitoring — watch browser console for violations, may need tuning

---

## 📋 MEDIUM PRIORITY

### Payment System
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
