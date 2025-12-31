const express = require('express');
const fileUpload = require('express-fileupload');
const cors = require('cors');
const mariadb = require('mariadb');
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const { promisify } = require('util');
const execAsync = promisify(exec);
const { generateResponsiveImages, getResponsiveImageUrls } = require('./image-optimizer');
const http = require('http');
const WebSocket = require('ws');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const validator = require('validator');
const rateLimit = require('express-rate-limit');
const nodemailer = require('nodemailer');
const crypto = require('crypto');

// JWT Configuration - requires environment variable
if (!process.env.JWT_SECRET) {
  console.error('FATAL: JWT_SECRET environment variable is required');
  process.exit(1);
}
const JWT_SECRET = process.env.JWT_SECRET;
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '7d';
const BCRYPT_ROUNDS = 12;

// Email configuration for password reset
const emailTransporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'smtp.gmail.com',
  port: parseInt(process.env.SMTP_PORT) || 587,
  secure: false,
  auth: {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASS
  }
});

const SITE_URL = process.env.SITE_URL || 'https://kinky-thots.com';
const SITE_NAME = 'Kinky-Thots';

// Subscription Tiers Configuration
const SUBSCRIPTION_TIERS = {
  free: {
    name: 'Free',
    price: 0,
    priceId: null, // No payment needed
    contentAccess: 0.2, // 20% of content
    features: ['Limited gallery access', 'Chat access', 'Stream viewing']
  },
  basic: {
    name: 'Basic',
    price: 5,
    priceId: process.env.PAYPAL_BASIC_PLAN_ID,
    contentAccess: 0.6, // 60% of content
    features: ['Extended gallery access', 'Chat with badge', 'HD streams', 'Priority support']
  },
  premium: {
    name: 'Premium',
    price: 10,
    priceId: process.env.PAYPAL_PREMIUM_PLAN_ID,
    contentAccess: 1.0, // 100% of content
    features: ['Full gallery access', 'VIP chat badge', '4K streams', 'Exclusive content', 'Direct messaging']
  },
  vip: {
    name: 'VIP',
    price: 10, // Same as premium, but manually granted
    priceId: null,
    contentAccess: 1.0,
    features: ['All Premium features', 'Moderator access', 'Early access to content']
  }
};

// Get tier access level (0-1)
function getTierAccessLevel(tier) {
  return SUBSCRIPTION_TIERS[tier]?.contentAccess || 0.2;
}

// Check if user can access content
function canAccessContent(userTier, contentIndex, totalContent) {
  const accessLevel = getTierAccessLevel(userTier);
  const accessibleCount = Math.ceil(totalContent * accessLevel);
  return contentIndex < accessibleCount;
}

const app = express();
const PORT = process.env.PORT || 3001;
const HOST = process.env.HOST || '0.0.0.0';

// File type detection
const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'mpeg', 'flv', 'm4v'];
const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];

// Storage paths
const PATHS = {
  images: path.join(__dirname, '../uploads'),           // /var/www/kinky-thots/uploads
  videos: '/media/porn/kinky-thots-shorts'              // /media/porn/kinky-thots-shorts
};

// Web paths for serving files
const WEB_PATHS = {
  images: '/uploads',
  videos: '/porn/kinky-thots-shorts'
};

// PushrCDN Configuration (all sensitive values from environment variables)
const PUSHR_CONFIG = {
  enabled: !!process.env.PUSHR_API_KEY,
  apiKey: process.env.PUSHR_API_KEY,
  apiUrl: 'https://www.pushrcdn.com/api/v3/prefetch',
  secretToken: process.env.PUSHR_SECRET_TOKEN,
  baseUrl: process.env.PUSHR_BASE_URL || 'https://kinky-thots.com',
  cdnUrls: {
    images: process.env.PUSHR_CDN_IMAGES || 'https://c5988z6294.r-cdn.com',
    videos: process.env.PUSHR_CDN_VIDEOS || 'https://c5988z6295.r-cdn.com'
  },
  zones: {
    images: process.env.PUSHR_ZONE_IMAGES || '6294',
    videos: process.env.PUSHR_ZONE_VIDEOS || '6293'
  },
  useSecureTokens: true
};

// Ensure directories exist
Object.values(PATHS).forEach(dir => {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
});

/**
 * Determine if file is video or image based on extension
 */
function getFileType(filename) {
  const ext = path.extname(filename).toLowerCase().replace('.', '');
  if (VIDEO_EXTENSIONS.includes(ext)) return 'video';
  if (IMAGE_EXTENSIONS.includes(ext)) return 'image';
  return 'unknown';
}

/**
 * Get storage path based on file type
 */
function getStoragePath(fileType) {
  return PATHS[fileType === 'video' ? 'videos' : 'images'];
}

/**
 * Get web path based on file type
 */
function getWebPath(fileType) {
  return WEB_PATHS[fileType === 'video' ? 'videos' : 'images'];
}

/**
 * Prefetch file to PushrCDN
 */
async function prefetchToCDN(filename, fileType) {
  if (!PUSHR_CONFIG.enabled) return;
  
  const webPath = getWebPath(fileType);
  const url = `${PUSHR_CONFIG.baseUrl}${webPath}/${encodeURIComponent(filename)}`;
  const zoneId = PUSHR_CONFIG.zones[fileType === 'video' ? 'videos' : 'images'];
  
  try {
    const https = require('https');
    const querystring = require('querystring');
    
    const postData = querystring.stringify({
      zone_id: zoneId,
      url: url
    });
    
    const options = {
      hostname: 'www.pushrcdn.com',
      port: 443,
      path: '/api/v3/prefetch',
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(postData),
        'Accept': 'application/json',
        'APIKEY': PUSHR_CONFIG.apiKey
      }
    };
    
    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', (chunk) => { data += chunk; });
      res.on('end', () => {
        console.log(`CDN Prefetch [${fileType}] ${res.statusCode === 200 ? 'SUCCESS' : 'FAILED'}: ${url}`);
        if (res.statusCode !== 200) {
          console.log('CDN Response:', data);
        }
      });
    });
    
    req.on('error', (e) => {
      console.error('CDN Prefetch error:', e.message);
    });
    
    req.write(postData);
    req.end();
    
  } catch (err) {
    console.error('CDN Prefetch error:', err.message);
  }
}

// MariaDB connection pool - requires environment variables
const requiredDbVars = ['MARIADB_USER', 'MARIADB_PASSWORD', 'MARIADB_DATABASE'];
const missingDbVars = requiredDbVars.filter(v => !process.env[v]);
if (missingDbVars.length > 0) {
  console.error(`FATAL: Missing required database environment variables: ${missingDbVars.join(', ')}`);
  process.exit(1);
}
const pool = mariadb.createPool({
  host: process.env.MARIADB_HOST || 'localhost',
  user: process.env.MARIADB_USER,
  password: process.env.MARIADB_PASSWORD,
  database: process.env.MARIADB_DATABASE,
  connectionLimit: parseInt(process.env.MARIADB_POOL_SIZE) || 5
});

// Middleware
app.use(cors({
  origin: '*',
  methods: ['GET', 'POST', 'DELETE', 'OPTIONS'],
  credentials: true
}));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use(fileUpload({
  limits: { fileSize: 1000 * 1024 * 1024 }, // 1GB limit
  useTempFiles: true,
  tempFileDir: '/tmp/',
  createParentPath: true
}));

// Serve uploaded images statically
app.use('/uploads', express.static(PATHS.images));

// Rate limiter for auth endpoints
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 10, // 10 attempts per window
  message: { error: 'Too many login attempts, please try again later' },
  standardHeaders: true,
  legacyHeaders: false
});

// JWT Auth middleware
function authenticateToken(req, res, next) {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN

  if (!token) {
    return res.status(401).json({ error: 'Access token required' });
  }

  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Invalid or expired token' });
    }
    req.user = user;
    next();
  });
}

// ============================================
// AUTH ENDPOINTS
// ============================================

// Register new user
app.post('/api/auth/register', authLimiter, async (req, res) => {
  let conn;
  try {
    const { username, email, password } = req.body;

    // Validate input
    if (!username || !email || !password) {
      return res.status(400).json({ error: 'Username, email, and password are required' });
    }

    // Validate username (3-30 chars, alphanumeric + underscore)
    const usernameClean = username.trim();
    if (usernameClean.length < 3 || usernameClean.length > 30) {
      return res.status(400).json({ error: 'Username must be 3-30 characters' });
    }
    if (!/^[a-zA-Z0-9_]+$/.test(usernameClean)) {
      return res.status(400).json({ error: 'Username can only contain letters, numbers, and underscores' });
    }

    // Validate email
    if (!validator.isEmail(email)) {
      return res.status(400).json({ error: 'Invalid email address' });
    }

    // Validate password (min 8 chars, 1 upper, 1 lower, 1 number)
    if (password.length < 8) {
      return res.status(400).json({ error: 'Password must be at least 8 characters' });
    }
    if (!/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/\d/.test(password)) {
      return res.status(400).json({ error: 'Password must contain uppercase, lowercase, and a number' });
    }

    conn = await pool.getConnection();

    // Check if username or email already exists
    const existing = await conn.query(
      'SELECT id FROM users WHERE username = ? OR email = ?',
      [usernameClean.toLowerCase(), email.toLowerCase()]
    );
    if (existing.length > 0) {
      return res.status(409).json({ error: 'Username or email already registered' });
    }

    // Hash password
    const passwordHash = await bcrypt.hash(password, BCRYPT_ROUNDS);

    // Generate random display color
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'];
    const displayColor = colors[Math.floor(Math.random() * colors.length)];

    // Insert user
    const result = await conn.query(
      `INSERT INTO users (username, email, password_hash, display_color, created_at)
       VALUES (?, ?, ?, ?, NOW())`,
      [usernameClean, email.toLowerCase(), passwordHash, displayColor]
    );

    const userId = Number(result.insertId);

    // Generate JWT
    const token = jwt.sign(
      { userId, username: usernameClean },
      JWT_SECRET,
      { expiresIn: JWT_EXPIRES_IN }
    );

    // Update last login
    await conn.query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [userId]);

    console.log(`Auth: New user registered - ${usernameClean}`);

    res.status(201).json({
      success: true,
      token,
      user: {
        id: userId,
        username: usernameClean,
        email: email.toLowerCase(),
        display_color: displayColor,
        subscription_tier: 'free'
      }
    });

  } catch (err) {
    console.error('Registration error:', err);
    res.status(500).json({ error: 'Registration failed', details: err.message });
  } finally {
    if (conn) conn.release();
  }
});

// Login
app.post('/api/auth/login', authLimiter, async (req, res) => {
  let conn;
  try {
    const { email, password } = req.body;

    if (!email || !password) {
      return res.status(400).json({ error: 'Email and password are required' });
    }

    conn = await pool.getConnection();

    // Find user by email or username
    const users = await conn.query(
      `SELECT id, username, email, password_hash, display_color, subscription_tier
       FROM users WHERE email = ? OR username = ?`,
      [email.toLowerCase(), email.toLowerCase()]
    );

    if (users.length === 0) {
      return res.status(401).json({ error: 'Invalid email or password' });
    }

    const user = users[0];

    // Verify password
    const validPassword = await bcrypt.compare(password, user.password_hash);
    if (!validPassword) {
      return res.status(401).json({ error: 'Invalid email or password' });
    }

    // Generate JWT
    const token = jwt.sign(
      { userId: Number(user.id), username: user.username },
      JWT_SECRET,
      { expiresIn: JWT_EXPIRES_IN }
    );

    // Update last login
    await conn.query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [user.id]);

    console.log(`Auth: User logged in - ${user.username}`);

    res.json({
      success: true,
      token,
      user: {
        id: Number(user.id),
        username: user.username,
        email: user.email,
        display_color: user.display_color,
        subscription_tier: user.subscription_tier || 'free'
      }
    });

  } catch (err) {
    console.error('Login error:', err);
    res.status(500).json({ error: 'Login failed', details: err.message });
  } finally {
    if (conn) conn.release();
  }
});

// Get current user info
app.get('/api/auth/me', authenticateToken, async (req, res) => {
  let conn;
  try {
    conn = await pool.getConnection();
    const users = await conn.query(
      `SELECT id, username, email, display_color, subscription_tier, subscription_status,
              subscription_expires_at, created_at, last_login_at
       FROM users WHERE id = ?`,
      [req.user.userId]
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    const user = users[0];
    res.json({
      id: Number(user.id),
      username: user.username,
      email: user.email,
      display_color: user.display_color,
      subscription_tier: user.subscription_tier || 'free',
      subscription_status: user.subscription_status || 'active',
      subscription_expires_at: user.subscription_expires_at,
      created_at: user.created_at,
      last_login_at: user.last_login_at
    });

  } catch (err) {
    console.error('Get user error:', err);
    res.status(500).json({ error: 'Failed to get user info' });
  } finally {
    if (conn) conn.release();
  }
});

// Update user profile (color, username)
app.put('/api/auth/profile', authenticateToken, async (req, res) => {
  let conn;
  try {
    const { display_color } = req.body;
    const userId = req.user.userId;

    conn = await pool.getConnection();

    // Validate color format
    if (display_color && !/^#[0-9A-Fa-f]{6}$/.test(display_color)) {
      return res.status(400).json({ error: 'Invalid color format. Use #RRGGBB' });
    }

    if (display_color) {
      await conn.query('UPDATE users SET display_color = ? WHERE id = ?', [display_color, userId]);
    }

    // Fetch updated user
    const users = await conn.query(
      'SELECT id, username, email, display_color, subscription_tier FROM users WHERE id = ?',
      [userId]
    );

    res.json({
      success: true,
      user: {
        id: Number(users[0].id),
        username: users[0].username,
        email: users[0].email,
        display_color: users[0].display_color,
        subscription_tier: users[0].subscription_tier || 'free'
      }
    });

  } catch (err) {
    console.error('Profile update error:', err);
    res.status(500).json({ error: 'Failed to update profile' });
  } finally {
    if (conn) conn.release();
  }
});

// Request password reset
app.post('/api/auth/forgot-password', authLimiter, async (req, res) => {
  let conn;
  try {
    const { email } = req.body;

    if (!email) {
      return res.status(400).json({ error: 'Email is required' });
    }

    conn = await pool.getConnection();

    // Find user by email
    const users = await conn.query(
      'SELECT id, username, email FROM users WHERE email = ?',
      [email.toLowerCase()]
    );

    // Always return success to prevent email enumeration
    if (users.length === 0) {
      return res.json({ success: true, message: 'If an account exists, a reset link has been sent' });
    }

    const user = users[0];

    // Generate reset token
    const resetToken = crypto.randomBytes(32).toString('hex');
    const resetTokenHash = crypto.createHash('sha256').update(resetToken).digest('hex');
    const resetExpires = new Date(Date.now() + 3600000); // 1 hour

    // Save token to database
    await conn.query(
      'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?',
      [resetTokenHash, resetExpires, user.id]
    );

    // Send reset email
    const resetUrl = `${SITE_URL}/reset-password.html?token=${resetToken}`;

    try {
      await emailTransporter.sendMail({
        from: `"${SITE_NAME}" <${process.env.SMTP_USER || 'noreply@kinky-thots.com'}>`,
        to: user.email,
        subject: `Password Reset - ${SITE_NAME}`,
        html: `
          <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #f805a7;">Password Reset Request</h2>
            <p>Hi ${user.username},</p>
            <p>You requested a password reset. Click the button below to reset your password:</p>
            <p style="text-align: center; margin: 30px 0;">
              <a href="${resetUrl}" style="background: linear-gradient(135deg, #f805a7, #0bd0f3); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block;">Reset Password</a>
            </p>
            <p>This link expires in 1 hour.</p>
            <p>If you didn't request this, please ignore this email.</p>
            <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
            <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
          </div>
        `
      });
      console.log(`Password reset email sent to ${user.email}`);
    } catch (emailErr) {
      console.error('Failed to send reset email:', emailErr.message);
      // Don't fail the request - token is still valid
    }

    res.json({ success: true, message: 'If an account exists, a reset link has been sent' });

  } catch (err) {
    console.error('Forgot password error:', err);
    res.status(500).json({ error: 'Failed to process request' });
  } finally {
    if (conn) conn.release();
  }
});

// Reset password with token
app.post('/api/auth/reset-password', authLimiter, async (req, res) => {
  let conn;
  try {
    const { token, password } = req.body;

    if (!token || !password) {
      return res.status(400).json({ error: 'Token and password are required' });
    }

    // Validate password strength
    if (password.length < 8) {
      return res.status(400).json({ error: 'Password must be at least 8 characters' });
    }
    if (!/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/\d/.test(password)) {
      return res.status(400).json({ error: 'Password must contain uppercase, lowercase, and a number' });
    }

    // Hash the token to compare with database
    const tokenHash = crypto.createHash('sha256').update(token).digest('hex');

    conn = await pool.getConnection();

    // Find user with valid token
    const users = await conn.query(
      `SELECT id, username FROM users
       WHERE password_reset_token = ? AND password_reset_expires > NOW()`,
      [tokenHash]
    );

    if (users.length === 0) {
      return res.status(400).json({ error: 'Invalid or expired reset token' });
    }

    const user = users[0];

    // Hash new password
    const passwordHash = await bcrypt.hash(password, BCRYPT_ROUNDS);

    // Update password and clear reset token
    await conn.query(
      `UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?`,
      [passwordHash, user.id]
    );

    console.log(`Password reset successful for user: ${user.username}`);

    res.json({ success: true, message: 'Password has been reset successfully' });

  } catch (err) {
    console.error('Reset password error:', err);
    res.status(500).json({ error: 'Failed to reset password' });
  } finally {
    if (conn) conn.release();
  }
});

// Change password (for logged in users)
app.post('/api/auth/change-password', authenticateToken, async (req, res) => {
  let conn;
  try {
    const { currentPassword, newPassword } = req.body;
    const userId = req.user.userId;

    if (!currentPassword || !newPassword) {
      return res.status(400).json({ error: 'Current and new password are required' });
    }

    // Validate new password strength
    if (newPassword.length < 8) {
      return res.status(400).json({ error: 'New password must be at least 8 characters' });
    }
    if (!/[a-z]/.test(newPassword) || !/[A-Z]/.test(newPassword) || !/\d/.test(newPassword)) {
      return res.status(400).json({ error: 'Password must contain uppercase, lowercase, and a number' });
    }

    conn = await pool.getConnection();

    // Get current password hash
    const users = await conn.query(
      'SELECT password_hash FROM users WHERE id = ?',
      [userId]
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    // Verify current password
    const validPassword = await bcrypt.compare(currentPassword, users[0].password_hash);
    if (!validPassword) {
      return res.status(401).json({ error: 'Current password is incorrect' });
    }

    // Hash and save new password
    const newPasswordHash = await bcrypt.hash(newPassword, BCRYPT_ROUNDS);
    await conn.query('UPDATE users SET password_hash = ? WHERE id = ?', [newPasswordHash, userId]);

    res.json({ success: true, message: 'Password changed successfully' });

  } catch (err) {
    console.error('Change password error:', err);
    res.status(500).json({ error: 'Failed to change password' });
  } finally {
    if (conn) conn.release();
  }
});

// ============================================
// SUBSCRIPTION ENDPOINTS
// ============================================

// Get all subscription tiers
app.get('/api/subscriptions/tiers', (req, res) => {
  const tiers = Object.entries(SUBSCRIPTION_TIERS).map(([id, tier]) => ({
    id,
    name: tier.name,
    price: tier.price,
    features: tier.features,
    contentAccess: Math.round(tier.contentAccess * 100)
  }));
  res.json({ tiers });
});

// Get content with access control
app.get('/api/content', async (req, res) => {
  // Get user tier from JWT if authenticated
  let userTier = 'free';
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (token) {
    try {
      const decoded = jwt.verify(token, JWT_SECRET);
      const conn = await pool.getConnection();
      const users = await conn.query(
        'SELECT subscription_tier FROM users WHERE id = ?',
        [decoded.userId]
      );
      conn.release();
      if (users.length > 0) {
        userTier = users[0].subscription_tier || 'free';
      }
    } catch (err) {
      // Invalid token, use free tier
    }
  }

  let conn;
  try {
    conn = await pool.getConnection();
    const rows = await conn.query(
      'SELECT id, filename, file_type, file_path, upload_time FROM images ORDER BY upload_time DESC'
    );

    const totalContent = rows.length;
    const accessLevel = getTierAccessLevel(userTier);
    const accessibleCount = Math.ceil(totalContent * accessLevel);

    const items = rows.map((row, index) => {
      const isAccessible = index < accessibleCount;
      const fileType = row.file_type || 'image';

      return {
        id: Number(row.id),
        filename: isAccessible ? String(row.filename) : null,
        file_type: fileType,
        accessible: isAccessible,
        locked: !isAccessible,
        requiredTier: !isAccessible ? (index < Math.ceil(totalContent * 0.6) ? 'basic' : 'premium') : null,
        thumbnail: isAccessible ? `/uploads/${encodeURIComponent(row.filename)}` : '/assets/locked-content.jpg',
        upload_time: row.upload_time ? row.upload_time.toISOString() : null
      };
    });

    res.json({
      items,
      userTier,
      totalContent,
      accessibleCount,
      accessPercent: Math.round(accessLevel * 100)
    });

  } catch (err) {
    console.error('Content error:', err);
    res.status(500).json({ error: 'Failed to load content' });
  } finally {
    if (conn) conn.release();
  }
});

// ============================================
// PAYPAL SUBSCRIPTION ENDPOINTS
// ============================================

// PayPal API configuration
const PAYPAL_CONFIG = {
  clientId: process.env.PAYPAL_CLIENT_ID,
  clientSecret: process.env.PAYPAL_CLIENT_SECRET,
  mode: process.env.PAYPAL_MODE || 'sandbox', // 'sandbox' or 'live'
  get baseUrl() {
    return this.mode === 'live'
      ? 'https://api-m.paypal.com'
      : 'https://api-m.sandbox.paypal.com';
  }
};

// Get PayPal access token
async function getPayPalAccessToken() {
  const auth = Buffer.from(`${PAYPAL_CONFIG.clientId}:${PAYPAL_CONFIG.clientSecret}`).toString('base64');

  const response = await fetch(`${PAYPAL_CONFIG.baseUrl}/v1/oauth2/token`, {
    method: 'POST',
    headers: {
      'Authorization': `Basic ${auth}`,
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'grant_type=client_credentials'
  });

  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.error_description || 'Failed to get PayPal access token');
  }

  return data.access_token;
}

// Create PayPal subscription
app.post('/api/subscriptions/create', authenticateToken, async (req, res) => {
  const { tier } = req.body;

  if (!PAYPAL_CONFIG.clientId || !PAYPAL_CONFIG.clientSecret) {
    return res.status(503).json({ error: 'PayPal is not configured' });
  }

  const tierConfig = SUBSCRIPTION_TIERS[tier];
  if (!tierConfig || !tierConfig.priceId) {
    return res.status(400).json({ error: 'Invalid subscription tier' });
  }

  try {
    const accessToken = await getPayPalAccessToken();

    // Create subscription
    const response = await fetch(`${PAYPAL_CONFIG.baseUrl}/v1/billing/subscriptions`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${accessToken}`,
        'Content-Type': 'application/json',
        'PayPal-Request-Id': `sub-${req.user.userId}-${Date.now()}`
      },
      body: JSON.stringify({
        plan_id: tierConfig.priceId,
        subscriber: {
          email_address: req.user.email
        },
        application_context: {
          brand_name: 'Kinky-Thots',
          locale: 'en-US',
          shipping_preference: 'NO_SHIPPING',
          user_action: 'SUBSCRIBE_NOW',
          return_url: `${SITE_URL}/checkout.html?success=true&tier=${tier}`,
          cancel_url: `${SITE_URL}/checkout.html?cancelled=true&tier=${tier}`
        }
      })
    });

    const subscription = await response.json();

    if (!response.ok) {
      console.error('PayPal subscription error:', subscription);
      return res.status(500).json({ error: 'Failed to create subscription' });
    }

    // Find approval URL
    const approvalUrl = subscription.links?.find(link => link.rel === 'approve')?.href;

    res.json({
      subscriptionId: subscription.id,
      approvalUrl,
      status: subscription.status
    });

  } catch (err) {
    console.error('PayPal error:', err);
    res.status(500).json({ error: 'PayPal service error' });
  }
});

// Activate subscription after PayPal approval
app.post('/api/subscriptions/activate', authenticateToken, async (req, res) => {
  const { subscriptionId, tier } = req.body;

  if (!subscriptionId || !tier) {
    return res.status(400).json({ error: 'Missing subscriptionId or tier' });
  }

  let conn;
  try {
    const accessToken = await getPayPalAccessToken();

    // Get subscription details from PayPal
    const response = await fetch(`${PAYPAL_CONFIG.baseUrl}/v1/billing/subscriptions/${subscriptionId}`, {
      headers: {
        'Authorization': `Bearer ${accessToken}`,
        'Content-Type': 'application/json'
      }
    });

    const subscription = await response.json();

    if (!response.ok || subscription.status !== 'ACTIVE') {
      return res.status(400).json({ error: 'Subscription not active', status: subscription.status });
    }

    // Calculate expiration (1 month from now for monthly subscriptions)
    const expiresAt = new Date();
    expiresAt.setMonth(expiresAt.getMonth() + 1);

    // Update user subscription in database
    conn = await pool.getConnection();
    await conn.query(
      `UPDATE users SET
        subscription_tier = ?,
        subscription_status = 'active',
        subscription_expires_at = ?,
        payment_customer_id = ?,
        payment_provider = 'paypal'
      WHERE id = ?`,
      [tier, expiresAt, subscriptionId, req.user.userId]
    );

    // Get updated user
    const users = await conn.query(
      `SELECT id, username, email, display_color, subscription_tier, subscription_status, subscription_expires_at
       FROM users WHERE id = ?`,
      [req.user.userId]
    );

    res.json({
      success: true,
      message: 'Subscription activated',
      user: users[0]
    });

  } catch (err) {
    console.error('Activation error:', err);
    res.status(500).json({ error: 'Failed to activate subscription' });
  } finally {
    if (conn) conn.release();
  }
});

// Cancel subscription
app.post('/api/subscriptions/cancel', authenticateToken, async (req, res) => {
  let conn;
  try {
    conn = await pool.getConnection();

    // Get user's subscription ID
    const users = await conn.query(
      'SELECT payment_customer_id, payment_provider FROM users WHERE id = ?',
      [req.user.userId]
    );

    if (users.length === 0 || !users[0].payment_customer_id) {
      return res.status(400).json({ error: 'No active subscription found' });
    }

    const subscriptionId = users[0].payment_customer_id;

    // Cancel on PayPal
    if (users[0].payment_provider === 'paypal') {
      const accessToken = await getPayPalAccessToken();

      const response = await fetch(`${PAYPAL_CONFIG.baseUrl}/v1/billing/subscriptions/${subscriptionId}/cancel`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${accessToken}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          reason: 'Customer requested cancellation'
        })
      });

      if (!response.ok && response.status !== 204) {
        const error = await response.json();
        console.error('PayPal cancel error:', error);
        return res.status(500).json({ error: 'Failed to cancel on PayPal' });
      }
    }

    // Update database - keep tier until expiration
    await conn.query(
      `UPDATE users SET subscription_status = 'cancelled' WHERE id = ?`,
      [req.user.userId]
    );

    res.json({ success: true, message: 'Subscription cancelled. Access continues until expiration.' });

  } catch (err) {
    console.error('Cancel error:', err);
    res.status(500).json({ error: 'Failed to cancel subscription' });
  } finally {
    if (conn) conn.release();
  }
});

// PayPal webhook handler
app.post('/api/paypal/webhook', express.raw({ type: 'application/json' }), async (req, res) => {
  // Note: In production, verify webhook signature using PayPal-Transmission-Sig header
  const event = typeof req.body === 'string' ? JSON.parse(req.body) : req.body;

  console.log('PayPal webhook event:', event.event_type);

  let conn;
  try {
    conn = await pool.getConnection();

    switch (event.event_type) {
      case 'BILLING.SUBSCRIPTION.ACTIVATED':
      case 'BILLING.SUBSCRIPTION.RENEWED':
        // Subscription was activated or renewed
        const subId = event.resource.id;
        const expiresAt = new Date();
        expiresAt.setMonth(expiresAt.getMonth() + 1);

        await conn.query(
          `UPDATE users SET
            subscription_status = 'active',
            subscription_expires_at = ?
          WHERE payment_customer_id = ?`,
          [expiresAt, subId]
        );
        break;

      case 'BILLING.SUBSCRIPTION.CANCELLED':
      case 'BILLING.SUBSCRIPTION.EXPIRED':
        // Subscription was cancelled or expired
        await conn.query(
          `UPDATE users SET subscription_status = 'expired' WHERE payment_customer_id = ?`,
          [event.resource.id]
        );
        break;

      case 'BILLING.SUBSCRIPTION.SUSPENDED':
        // Payment failed
        await conn.query(
          `UPDATE users SET subscription_status = 'pending' WHERE payment_customer_id = ?`,
          [event.resource.id]
        );
        break;

      case 'PAYMENT.SALE.COMPLETED':
        // Successful payment
        console.log('Payment completed:', event.resource.id);
        break;

      default:
        console.log('Unhandled webhook event:', event.event_type);
    }

    res.status(200).json({ received: true });

  } catch (err) {
    console.error('Webhook error:', err);
    res.status(500).json({ error: 'Webhook processing failed' });
  } finally {
    if (conn) conn.release();
  }
});

// Get user's subscription status
app.get('/api/subscriptions/status', authenticateToken, async (req, res) => {
  let conn;
  try {
    conn = await pool.getConnection();
    const users = await conn.query(
      `SELECT subscription_tier, subscription_status, subscription_expires_at, payment_provider
       FROM users WHERE id = ?`,
      [req.user.userId]
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    const user = users[0];
    const tierConfig = SUBSCRIPTION_TIERS[user.subscription_tier] || SUBSCRIPTION_TIERS.free;

    res.json({
      tier: user.subscription_tier || 'free',
      tierName: tierConfig.name,
      status: user.subscription_status || 'active',
      expiresAt: user.subscription_expires_at,
      provider: user.payment_provider,
      contentAccess: Math.round(tierConfig.contentAccess * 100)
    });

  } catch (err) {
    console.error('Status error:', err);
    res.status(500).json({ error: 'Failed to get subscription status' });
  } finally {
    if (conn) conn.release();
  }
});

// Check if user can access specific content
app.get('/api/content/:id/access', async (req, res) => {
  const contentId = parseInt(req.params.id);
  let userTier = 'free';

  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (token) {
    try {
      const decoded = jwt.verify(token, JWT_SECRET);
      const conn = await pool.getConnection();
      const users = await conn.query(
        'SELECT subscription_tier FROM users WHERE id = ?',
        [decoded.userId]
      );
      conn.release();
      if (users.length > 0) {
        userTier = users[0].subscription_tier || 'free';
      }
    } catch (err) {
      // Invalid token
    }
  }

  let conn;
  try {
    conn = await pool.getConnection();

    // Get total content count
    const countResult = await conn.query('SELECT COUNT(*) as total FROM images');
    const totalContent = Number(countResult[0].total);

    // Get content position
    const posResult = await conn.query(
      `SELECT COUNT(*) as position FROM images WHERE id <= ? ORDER BY upload_time DESC`,
      [contentId]
    );
    const contentIndex = Number(posResult[0].position) - 1;

    const accessible = canAccessContent(userTier, contentIndex, totalContent);

    res.json({
      accessible,
      userTier,
      requiredTier: !accessible ? (contentIndex < Math.ceil(totalContent * 0.6) ? 'basic' : 'premium') : null
    });

  } catch (err) {
    console.error('Access check error:', err);
    res.status(500).json({ error: 'Failed to check access' });
  } finally {
    if (conn) conn.release();
  }
});

// Get all gallery items (images and videos)
app.get('/api/gallery', async (req, res) => {
  // Set CORS headers explicitly
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  res.setHeader('Content-Type', 'application/json');
  
  let conn;
  try {
    conn = await pool.getConnection();
    const rows = await conn.query(
      'SELECT id, filename, file_type, file_path, upload_time FROM images ORDER BY upload_time DESC'
    );
    
    // Convert MariaDB result to plain JSON objects
    const items = [];
    for (const row of rows) {
      const fileType = row.file_type || getFileType(row.filename);
      const webPath = row.file_path || getWebPath(fileType);
      
      // Use CDN URL for images if available
      let fullUrl;
      if (fileType === 'image' && PUSHR_CONFIG.cdnUrls.images) {
        // Files are at root of CDN
        fullUrl = `${PUSHR_CONFIG.cdnUrls.images}/${encodeURIComponent(row.filename)}`;
      } else {
        // Fallback to origin
        fullUrl = `${webPath}/${encodeURIComponent(row.filename)}`;
      }
      
      items.push({
        id: Number(row.id),
        filename: String(row.filename),
        file_type: fileType,
        web_path: webPath,
        full_url: fullUrl,
        cdn_url: fileType === 'image' ? `${PUSHR_CONFIG.cdnUrls.images}/${encodeURIComponent(row.filename)}` : null,
        origin_url: `${webPath}/${encodeURIComponent(row.filename)}`,
        upload_time: row.upload_time ? row.upload_time.toISOString() : null
      });
    }
    
    res.setHeader('Content-Type', 'application/json');
    res.send(JSON.stringify(items));
  } catch (err) {
    console.error('Database error:', err);
    res.status(500).json({ error: 'Database error', details: err.message });
  } finally {
    if (conn) conn.release();
  }
});

// Upload file (image or video)
app.post('/api/upload', async (req, res) => {
  let conn;
  try {
    if (!req.files || !req.files.image) {
      return res.status(400).json({ error: 'No file uploaded' });
    }

    const file = req.files.image;
    const timestamp = Date.now();
    
    // Sanitize filename
    const sanitizedName = file.name.replace(/[^a-zA-Z0-9._-]/g, '_');
    const filename = `${timestamp}_${sanitizedName}`;
    
    // Determine file type
    const fileType = getFileType(file.name);
    
    if (fileType === 'unknown') {
      return res.status(400).json({ 
        error: 'Unsupported file type',
        allowed: [...IMAGE_EXTENSIONS, ...VIDEO_EXTENSIONS]
      });
    }
    
    // Get appropriate storage path
    const storagePath = getStoragePath(fileType);
    const uploadPath = path.join(storagePath, filename);
    const webPath = getWebPath(fileType);
    
    console.log(`Uploading ${fileType}: ${filename}`);
    console.log(`Storage path: ${uploadPath}`);
    console.log(`Web path: ${webPath}`);
    
    // Move file to appropriate directory
    await file.mv(uploadPath);
    
    // Set proper permissions
    fs.chmodSync(uploadPath, 0o644);

    // Generate responsive images for image uploads (in background)
    if (fileType === 'image') {
      const optimizedDir = path.join(storagePath, 'optimized');
      generateResponsiveImages(uploadPath, optimizedDir)
        .then(() => console.log(`Generated responsive images for: ${filename}`))
        .catch(err => console.error(`Failed to generate responsive images: ${err.message}`));
    }

    // Insert into database with file type and path
    conn = await pool.getConnection();
    const result = await conn.query(
      'INSERT INTO images (filename, file_type, file_path, upload_time) VALUES (?, ?, ?, NOW())',
      [filename, fileType, webPath]
    );

    // Trigger CDN prefetch in background
    prefetchToCDN(filename, fileType);

    res.json({
      success: true,
      id: Number(result.insertId),
      filename: filename,
      file_type: fileType,
      web_path: webPath,
      full_url: `${webPath}/${encodeURIComponent(filename)}`,
      message: `${fileType === 'video' ? 'Video' : 'Image'} uploaded successfully`
    });
  } catch (err) {
    console.error('Upload error:', err);
    res.status(500).json({ error: 'Upload failed', details: err.message });
  } finally {
    if (conn) conn.release();
  }
});

// Delete file
app.delete('/api/gallery/:id', async (req, res) => {
  let conn;
  try {
    const id = parseInt(req.params.id);
    
    conn = await pool.getConnection();
    const rows = await conn.query('SELECT filename, file_type, file_path FROM images WHERE id = ?', [id]);
    
    if (rows.length === 0) {
      return res.status(404).json({ error: 'File not found' });
    }

    const { filename, file_type, file_path } = rows[0];
    const fileType = file_type || getFileType(filename);
    const storagePath = getStoragePath(fileType);
    const filePath = path.join(storagePath, filename);

    // Delete physical file
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
      console.log(`Deleted file: ${filePath}`);
    }

    // Delete database record
    await conn.query('DELETE FROM images WHERE id = ?', [id]);

    res.json({ 
      success: true, 
      message: `${fileType === 'video' ? 'Video' : 'Image'} deleted successfully` 
    });
  } catch (err) {
    console.error('Delete error:', err);
    res.status(500).json({ error: 'Delete failed', details: err.message });
  } finally {
    if (conn) conn.release();
  }
});
// Health check
app.get('/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    paths: {
      images: PATHS.images,
      videos: PATHS.videos
    }
  });
});

// ============================================
// WEBSOCKET CHAT SERVER
// ============================================

const server = http.createServer(app);
const wss = new WebSocket.Server({ server, path: '/ws/chat' });

// Chat state
const chatClients = new Set();
let viewerCount = 0;

// Moderation state
const bannedUsers = new Map(); // username -> { reason, bannedBy, timestamp }
const mutedUsers = new Map();  // username -> { until, mutedBy }
let slowModeSeconds = 0;       // 0 = disabled
const lastMessageTime = new Map(); // username -> timestamp

// Check if user is moderator (VIP tier)
function isModerator(ws) {
  return ws.isAuthenticated && ws.subscriptionTier === 'vip';
}

// Parse moderation command
function parseModCommand(message) {
  const match = message.match(/^\/(\w+)\s*(.*)?$/);
  if (!match) return null;
  return { command: match[1].toLowerCase(), args: (match[2] || '').trim() };
}

// Generate random color for usernames
function getRandomColor() {
  const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'];
  return colors[Math.floor(Math.random() * colors.length)];
}

// Broadcast to all connected clients
function broadcast(data, exclude = null) {
  const message = JSON.stringify(data);
  chatClients.forEach(client => {
    if (client !== exclude && client.readyState === WebSocket.OPEN) {
      client.send(message);
    }
  });
}

wss.on('connection', async (ws, req) => {
  chatClients.add(ws);
  viewerCount++;

  // Extract token from query string for JWT auth
  const url = new URL(req.url, 'ws://localhost');
  const token = url.searchParams.get('token');

  // Try to authenticate with JWT
  if (token) {
    try {
      const decoded = jwt.verify(token, JWT_SECRET);
      const conn = await pool.getConnection();
      const users = await conn.query(
        'SELECT id, username, display_color, subscription_tier FROM users WHERE id = ?',
        [decoded.userId]
      );
      conn.release();

      if (users.length > 0) {
        const user = users[0];
        ws.userId = Number(user.id);
        ws.username = user.username;
        ws.userColor = user.display_color;
        ws.subscriptionTier = user.subscription_tier || 'free';
        ws.isAuthenticated = true;
      }
    } catch (err) {
      // Token invalid - fall through to guest mode
      console.log('WebSocket auth failed:', err.message);
    }
  }

  // Default to guest if not authenticated
  if (!ws.isAuthenticated) {
    ws.username = `Guest${Math.floor(Math.random() * 9999)}`;
    ws.userColor = getRandomColor();
    ws.isAuthenticated = false;
    ws.subscriptionTier = null;
  }

  console.log(`Chat: ${ws.username} connected (${viewerCount} viewers) [${ws.isAuthenticated ? 'authenticated' : 'guest'}]`);

  // Send welcome message with auth status
  ws.send(JSON.stringify({
    type: 'welcome',
    username: ws.username,
    color: ws.userColor,
    viewerCount: viewerCount,
    isAuthenticated: ws.isAuthenticated,
    subscriptionTier: ws.subscriptionTier
  }));

  // Broadcast updated viewer count
  broadcast({ type: 'viewers', count: viewerCount });

  ws.on('message', (data) => {
    try {
      const msg = JSON.parse(data);

      switch (msg.type) {
        case 'chat':
          const chatMessage = msg.message.substring(0, 500);

          // Check if banned
          if (bannedUsers.has(ws.username.toLowerCase())) {
            ws.send(JSON.stringify({ type: 'error', message: 'You are banned from chat' }));
            return;
          }

          // Check if muted
          const muteInfo = mutedUsers.get(ws.username.toLowerCase());
          if (muteInfo && muteInfo.until > Date.now()) {
            const remaining = Math.ceil((muteInfo.until - Date.now()) / 1000);
            ws.send(JSON.stringify({ type: 'error', message: `You are muted for ${remaining} more seconds` }));
            return;
          } else if (muteInfo) {
            mutedUsers.delete(ws.username.toLowerCase());
          }

          // Check slow mode
          if (slowModeSeconds > 0 && !isModerator(ws)) {
            const lastMsg = lastMessageTime.get(ws.username.toLowerCase()) || 0;
            const elapsed = (Date.now() - lastMsg) / 1000;
            if (elapsed < slowModeSeconds) {
              const wait = Math.ceil(slowModeSeconds - elapsed);
              ws.send(JSON.stringify({ type: 'error', message: `Slow mode: wait ${wait} seconds` }));
              return;
            }
          }
          lastMessageTime.set(ws.username.toLowerCase(), Date.now());

          // Check for moderation commands
          const modCmd = parseModCommand(chatMessage);
          if (modCmd && isModerator(ws)) {
            switch (modCmd.command) {
              case 'ban':
                if (modCmd.args) {
                  const targetUser = modCmd.args.split(' ')[0].toLowerCase();
                  const reason = modCmd.args.substring(targetUser.length).trim() || 'No reason given';
                  bannedUsers.set(targetUser, { reason, bannedBy: ws.username, timestamp: Date.now() });
                  // Disconnect banned user
                  chatClients.forEach(client => {
                    if (client.username.toLowerCase() === targetUser) {
                      client.send(JSON.stringify({ type: 'banned', reason }));
                      client.close();
                    }
                  });
                  broadcast({ type: 'modAction', action: 'ban', target: targetUser, moderator: ws.username });
                  console.log(`Mod: ${ws.username} banned ${targetUser} - ${reason}`);
                }
                break;

              case 'unban':
                if (modCmd.args) {
                  const targetUser = modCmd.args.toLowerCase();
                  if (bannedUsers.delete(targetUser)) {
                    broadcast({ type: 'modAction', action: 'unban', target: targetUser, moderator: ws.username });
                    console.log(`Mod: ${ws.username} unbanned ${targetUser}`);
                  }
                }
                break;

              case 'mute':
                if (modCmd.args) {
                  const parts = modCmd.args.split(' ');
                  const targetUser = parts[0].toLowerCase();
                  const duration = parseInt(parts[1]) || 300; // Default 5 minutes
                  mutedUsers.set(targetUser, { until: Date.now() + (duration * 1000), mutedBy: ws.username });
                  broadcast({ type: 'modAction', action: 'mute', target: targetUser, duration, moderator: ws.username });
                  console.log(`Mod: ${ws.username} muted ${targetUser} for ${duration}s`);
                }
                break;

              case 'unmute':
                if (modCmd.args) {
                  const targetUser = modCmd.args.toLowerCase();
                  if (mutedUsers.delete(targetUser)) {
                    broadcast({ type: 'modAction', action: 'unmute', target: targetUser, moderator: ws.username });
                    console.log(`Mod: ${ws.username} unmuted ${targetUser}`);
                  }
                }
                break;

              case 'slow':
                const seconds = parseInt(modCmd.args) || 0;
                slowModeSeconds = Math.min(seconds, 300); // Max 5 minutes
                broadcast({ type: 'modAction', action: 'slow', seconds: slowModeSeconds, moderator: ws.username });
                console.log(`Mod: ${ws.username} set slow mode to ${slowModeSeconds}s`);
                break;

              case 'clear':
                broadcast({ type: 'modAction', action: 'clear', moderator: ws.username });
                console.log(`Mod: ${ws.username} cleared chat`);
                break;

              default:
                ws.send(JSON.stringify({ type: 'error', message: `Unknown command: /${modCmd.command}` }));
            }
            return; // Don't broadcast mod commands as chat
          }

          // Check if non-mod trying to use command
          if (modCmd) {
            ws.send(JSON.stringify({ type: 'error', message: 'Only moderators can use commands' }));
            return;
          }

          // Broadcast chat message to all with auth info
          broadcast({
            type: 'chat',
            username: ws.username,
            color: ws.userColor,
            message: chatMessage,
            timestamp: Date.now(),
            isAuthenticated: ws.isAuthenticated,
            subscriptionTier: ws.subscriptionTier
          });
          break;

        case 'reaction':
          // Check if banned
          if (bannedUsers.has(ws.username.toLowerCase())) return;
          // Broadcast emoji reaction
          broadcast({
            type: 'reaction',
            emoji: msg.emoji,
            username: ws.username
          });
          break;

        case 'setName':
          // Only allow name change for guests
          if (!ws.isAuthenticated) {
            const newName = msg.name.substring(0, 20).replace(/[^a-zA-Z0-9_-]/g, '');
            if (newName.length >= 2) {
              ws.username = newName;
              ws.send(JSON.stringify({ type: 'nameChanged', username: newName }));
            }
          } else {
            ws.send(JSON.stringify({ type: 'error', message: 'Authenticated users cannot change name' }));
          }
          break;
      }
    } catch (err) {
      console.error('Chat message error:', err.message);
    }
  });

  ws.on('close', () => {
    chatClients.delete(ws);
    viewerCount--;
    console.log(`Chat: ${ws.username} disconnected (${viewerCount} viewers)`);
    broadcast({ type: 'viewers', count: viewerCount });
  });

  ws.on('error', (err) => {
    console.error('WebSocket error:', err.message);
    chatClients.delete(ws);
  });
});

// Start server
server.listen(PORT, HOST, () => {
  console.log(`Gallery backend running on ${HOST}:${PORT}`);
  console.log(`WebSocket chat available at ws://${HOST}:${PORT}/ws/chat`);
  console.log(`Image uploads: ${PATHS.images}`);
  console.log(`Video uploads: ${PATHS.videos}`);
  console.log(`Database host: ${process.env.MARIADB_HOST || 'localhost'}`);
});
