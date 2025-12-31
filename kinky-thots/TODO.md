# Kinky-Thots Implementation TODO

## ✅ COMPLETED (Dec 31, 2024)

### User Authentication System
- [x] Database: `users` table created in `kinky_thots` database
- [x] Dependencies: `bcrypt`, `jsonwebtoken`, `validator`, `express-rate-limit` installed
- [x] Environment: `JWT_SECRET` and `JWT_EXPIRES_IN` added to `.env`
- [x] Backend Auth Endpoints added to `server.js`:
  - `POST /api/auth/register` - Create account
  - `POST /api/auth/login` - Login, returns JWT
  - `GET /api/auth/me` - Get current user (requires JWT)
  - `PUT /api/auth/profile` - Update display color
- [x] WebSocket JWT auth in `server.js` - Authenticated users get their saved username/color
- [x] Created `src/css/auth-modal.css` - Modal styles + chat badges
- [x] Created `src/js/auth.js` - AuthManager class for login/register
- [x] Updated `live.html` - Added auth modal HTML + enabled login button
- [x] Updated `src/js/live.js` - Integrated auth with WebSocket chat
- [x] Updated `docker-compose.yml` - Added JWT_SECRET/JWT_EXPIRES_IN env vars

### Password Reset Flow
- [x] Added nodemailer dependency
- [x] Email transporter configuration in server.js
- [x] Endpoints: `/api/auth/forgot-password`, `/api/auth/reset-password`, `/api/auth/change-password`
- [x] Created `reset-password.html` page
- [x] Added forgot password form to auth modal
- [x] Updated `src/js/auth.js` with forgot password handlers

### Chat Moderation Tools
- [x] Added moderation state (bannedUsers, mutedUsers, slowModeSeconds)
- [x] Implemented `isModerator()` and `parseModCommand()` functions
- [x] Commands: `/ban`, `/unban`, `/mute`, `/unmute`, `/slow`, `/clear`
- [x] VIP tier users get moderator access
- [x] Updated `src/js/live.js` to handle modAction and banned message types
- [x] Added CSS for mod action messages in `auth-modal.css`

### Content Gating by Subscription Tier
- [x] Added `SUBSCRIPTION_TIERS` configuration to server.js
- [x] Created API endpoints: `/api/subscriptions/tiers`, `/api/content`, `/api/content/:id/access`
- [x] Created `subscriptions.html` page with tier pricing display
- [x] Created `checkout.html` page (PayPal placeholder)
- [x] Updated `porn.php` with content gating JavaScript
- [x] Added locked content CSS (blur, lock icon, upgrade buttons) to `media-gallery.css`

### Security Hardening
- [x] Removed hardcoded API key fallbacks from server.js
- [x] JWT_SECRET now required - fail-fast on missing env vars
- [x] Added env vars to docker-compose.yml

### User Profile Page
- [x] Created `profile.html` page
- [x] Shows user info: username, email, tier, member since, last login
- [x] Chat color customization with color picker
- [x] Password change form
- [x] Subscription status with upgrade CTA
- [x] Cancel subscription button for paid tiers
- [x] User dropdown menu in nav (Profile, Subscription, Logout)

### PayPal Payment Integration
- [x] PayPal API configuration in server.js
- [x] Environment variables setup (PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET, etc.)
- [x] Subscription creation endpoint (`/api/subscriptions/create`)
- [x] Subscription activation endpoint (`/api/subscriptions/activate`)
- [x] Subscription cancellation endpoint (`/api/subscriptions/cancel`)
- [x] PayPal webhook handler (`/api/paypal/webhook`)
- [x] Updated checkout.html with PayPal redirect flow
- [x] Graceful fallback when PayPal not configured

---

## 🔄 IN PROGRESS

(none currently)

---

## 📋 DEFERRED FOR FUTURE IMPLEMENTATION

### Stream Notifications (Deferred)
- [ ] Add web-push dependency (already in package.json)
- [ ] Create VAPID keys for push notifications
- [ ] Implement service worker for push notifications
- [ ] Add notification permission prompt on live.html
- [ ] Create endpoint to store push subscriptions
- [ ] Send push notification when stream goes live

### PWA Support (Deferred)
- [ ] Create `manifest.json` with app metadata
- [ ] Add PWA meta tags to all pages
- [ ] Create service worker for offline caching
- [ ] Add install prompt for mobile users
- [ ] Cache static assets and pages for offline access

> **Note**: Stream Notifications and PWA Support have been deferred to a future session. Priority was given to core user authentication, subscription, and payment features.

---

## 🗄️ SHELVED (Future Implementation)

### Video Features
- Like/favorite system
- Save to watch later
- Watch history tracking
- Video recommendations based on views

### Admin Dashboard
- User management (ban, tier changes)
- Content management (upload, organize)
- Analytics (views, signups, revenue)
- Real-time chat moderation panel

### Chat Enhancements
- Custom emoji support
- @mentions with notifications
- Message reactions
- Animated stickers/GIFs

---

## Database Schema Reference

```sql
-- Users table (already created)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_color VARCHAR(7) DEFAULT '#0bd0f3',
    subscription_tier ENUM('free', 'basic', 'premium', 'vip') DEFAULT 'free',
    subscription_status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active',
    subscription_expires_at TIMESTAMP NULL,
    payment_customer_id VARCHAR(255) NULL,
    payment_provider VARCHAR(50) NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## Key Files Reference

| Feature | Files |
|---------|-------|
| Auth | `src/js/auth.js`, `src/css/auth-modal.css`, `live.html` |
| Backend | `backend/server.js`, `backend/package.json` |
| Subscriptions | `subscriptions.html`, `checkout.html` |
| Content Gating | `porn.php`, `src/css/media-gallery.css` |
| Password Reset | `reset-password.html` |
