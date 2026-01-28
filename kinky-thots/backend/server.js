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
const SonicS3Client = require('./sonic-s3-client');
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

const SITE_URL = process.env.SITE_URL || 'https://kinky-thots.xxx';
const SITE_NAME = 'Kinky-Thots';
const SMTP_FROM = `"${process.env.SMTP_FROM_NAME || SITE_NAME}" <${process.env.SMTP_FROM_EMAIL || process.env.SMTP_USER || 'noreply@kinky-thots.xxx'}>`;

// ============================================
// EMAIL HELPER FUNCTIONS
// ============================================

/**
 * Send subscription-related emails
 * @param {string} type - Email type (welcome, receipt, renewed, cancelled, expiring, failed, lifetime)
 * @param {object} user - User object with email, username
 * @param {object} details - Additional details (tier, amount, date, etc.)
 */
async function sendSubscriptionEmail(type, user, details = {}) {
  if (!process.env.SMTP_USER || !process.env.SMTP_PASS) {
    console.warn('Email not configured - skipping', type, 'email to', user.email);
    return false;
  }

  const templates = {
    welcome: {
      subject: `Welcome to ${SITE_NAME} ${details.tierName || 'Premium'}!`,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f0f0f; padding: 30px; border-radius: 16px;">
          <h2 style="color: #f805a7; margin-bottom: 20px;">Welcome to ${SITE_NAME}!</h2>
          <p style="color: #fff;">Hi ${user.username},</p>
          <p style="color: #ccc;">Thank you for subscribing to <strong style="color: #0bd0f3;">${details.tierName || 'Premium'}</strong>!</p>
          <p style="color: #ccc;">You now have access to:</p>
          <ul style="color: #ccc;">
            ${(details.features || ['Premium content', 'HD streaming', 'Chat badge']).map(f => `<li>${f}</li>`).join('')}
          </ul>
          <p style="text-align: center; margin: 30px 0;">
            <a href="${SITE_URL}/premium-content.php" style="background: linear-gradient(135deg, #f805a7, #0bd0f3); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block;">Start Watching</a>
          </p>
          <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
          <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
        </div>
      `
    },
    receipt: {
      subject: `Payment Receipt - ${SITE_NAME}`,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f0f0f; padding: 30px; border-radius: 16px;">
          <h2 style="color: #f805a7; margin-bottom: 20px;">Payment Received</h2>
          <p style="color: #fff;">Hi ${user.username},</p>
          <p style="color: #ccc;">We've received your payment. Here are the details:</p>
          <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="color: #ccc; margin: 5px 0;"><strong>Plan:</strong> ${details.tierName || 'Premium'}</p>
            <p style="color: #ccc; margin: 5px 0;"><strong>Amount:</strong> $${details.amount || '0.00'}</p>
            <p style="color: #ccc; margin: 5px 0;"><strong>Date:</strong> ${details.date || new Date().toLocaleDateString()}</p>
            ${details.nextBilling ? `<p style="color: #ccc; margin: 5px 0;"><strong>Next billing:</strong> ${details.nextBilling}</p>` : ''}
          </div>
          <p style="text-align: center; margin: 30px 0;">
            <a href="${SITE_URL}/profile.html" style="background: linear-gradient(135deg, #f805a7, #0bd0f3); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block;">View Account</a>
          </p>
          <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
          <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
        </div>
      `
    },
    renewed: {
      subject: `Subscription Renewed - ${SITE_NAME}`,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f0f0f; padding: 30px; border-radius: 16px;">
          <h2 style="color: #f805a7; margin-bottom: 20px;">Subscription Renewed</h2>
          <p style="color: #fff;">Hi ${user.username},</p>
          <p style="color: #ccc;">Your ${details.tierName || 'Premium'} subscription has been renewed successfully.</p>
          <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="color: #ccc; margin: 5px 0;"><strong>Amount charged:</strong> $${details.amount || '0.00'}</p>
            <p style="color: #ccc; margin: 5px 0;"><strong>Next renewal:</strong> ${details.nextBilling || 'N/A'}</p>
          </div>
          <p style="color: #ccc;">Thank you for your continued support!</p>
          <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
          <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
        </div>
      `
    },
    cancelled: {
      subject: `Subscription Cancelled - ${SITE_NAME}`,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f0f0f; padding: 30px; border-radius: 16px;">
          <h2 style="color: #f805a7; margin-bottom: 20px;">Subscription Cancelled</h2>
          <p style="color: #fff;">Hi ${user.username},</p>
          <p style="color: #ccc;">Your subscription has been cancelled as requested.</p>
          ${details.accessUntil ? `<p style="color: #ccc;">You'll continue to have access until <strong style="color: #0bd0f3;">${details.accessUntil}</strong>.</p>` : ''}
          <p style="color: #ccc;">We're sorry to see you go. If you change your mind, you can resubscribe anytime.</p>
          <p style="text-align: center; margin: 30px 0;">
            <a href="${SITE_URL}/subscriptions.html" style="background: linear-gradient(135deg, #f805a7, #0bd0f3); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block;">Resubscribe</a>
          </p>
          <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
          <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
        </div>
      `
    },
    expiring: {
      subject: `Subscription Expiring Soon - ${SITE_NAME}`,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f0f0f; padding: 30px; border-radius: 16px;">
          <h2 style="color: #f805a7; margin-bottom: 20px;">Subscription Expiring Soon</h2>
          <p style="color: #fff;">Hi ${user.username},</p>
          <p style="color: #ccc;">Your ${details.tierName || 'Premium'} subscription will expire on <strong style="color: #0bd0f3;">${details.expiresAt || 'soon'}</strong>.</p>
          <p style="color: #ccc;">Renew now to keep your access to premium content!</p>
          <p style="text-align: center; margin: 30px 0;">
            <a href="${SITE_URL}/subscriptions.html" style="background: linear-gradient(135deg, #f805a7, #0bd0f3); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block;">Renew Now</a>
          </p>
          <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
          <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
        </div>
      `
    },
    failed: {
      subject: `Payment Failed - ${SITE_NAME}`,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f0f0f; padding: 30px; border-radius: 16px;">
          <h2 style="color: #e74c3c; margin-bottom: 20px;">Payment Failed</h2>
          <p style="color: #fff;">Hi ${user.username},</p>
          <p style="color: #ccc;">We were unable to process your subscription payment.</p>
          <p style="color: #ccc;">Please update your payment method to continue your access.</p>
          <p style="text-align: center; margin: 30px 0;">
            <a href="${SITE_URL}/profile.html" style="background: linear-gradient(135deg, #f805a7, #0bd0f3); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block;">Update Payment</a>
          </p>
          <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
          <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
        </div>
      `
    },
    yearly: {
      subject: `Welcome to Yearly Premium - ${SITE_NAME}`,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #0f0f0f; padding: 30px; border-radius: 16px;">
          <h2 style="background: linear-gradient(135deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 20px;">Yearly Premium Unlocked!</h2>
          <p style="color: #fff;">Hi ${user.username},</p>
          <p style="color: #ccc;">Congratulations! You now have <strong style="color: #FFD700;">12 MONTHS</strong> of premium access on ${SITE_NAME}.</p>
          <div style="background: rgba(255,215,0,0.1); padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid rgba(255,215,0,0.3);">
            <p style="color: #ccc; margin: 5px 0;"><strong>Amount paid:</strong> $${details.amount || '120.00'}</p>
            <p style="color: #ccc; margin: 5px 0;"><strong>Access expires:</strong> ${details.expiresAt || '1 year from now'}</p>
          </div>
          <p style="color: #ccc;">Your benefits include:</p>
          <ul style="color: #ccc;">
            <li>All videos - no restrictions</li>
            <li>4K streaming</li>
            <li>Exclusive content</li>
            <li>VIP chat badge</li>
            <li>Priority support</li>
          </ul>
          <p style="text-align: center; margin: 30px 0;">
            <a href="${SITE_URL}/premium-content.php" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;">Start Watching</a>
          </p>
          <hr style="border: none; border-top: 1px solid #333; margin: 20px 0;">
          <p style="color: #666; font-size: 12px;">${SITE_NAME} - ${SITE_URL}</p>
        </div>
      `
    }
  };

  const template = templates[type];
  if (!template) {
    console.error('Unknown email template type:', type);
    return false;
  }

  try {
    await emailTransporter.sendMail({
      from: SMTP_FROM,
      to: user.email,
      subject: template.subject,
      html: template.html
    });
    console.log(`Email sent: ${type} to ${user.email}`);
    return true;
  } catch (err) {
    console.error(`Failed to send ${type} email to ${user.email}:`, err.message);
    return false;
  }
}

// Subscription Tiers Configuration
const SUBSCRIPTION_TIERS = {
  free: {
    name: 'Free',
    price: 0,
    priceId: null,
    maxDuration: 60, // Videos under 1 minute
    features: ['Teaser videos under 1 min', 'Chat access', 'Stream viewing']
  },
  basic: {
    name: 'Basic',
    price: 8,
    maxDuration: 300, // Videos 1-5 minutes
    features: ['Videos up to 5 minutes', 'HD streams', 'Chat badge', 'Priority support']
  },
  premium: {
    name: 'Premium',
    price: 15,
    maxDuration: Infinity, // All videos
    features: ['All videos (5+ min)', '4K streams', 'Exclusive content', 'Direct messaging']
  },
  yearly: {
    name: 'Yearly',
    price: 120,
    maxDuration: Infinity,
    isYearly: true,
    durationDays: 365,
    features: ['All Premium features', '12 months access', 'Save $60 vs monthly']
  },
  vip: {
    name: 'VIP',
    price: 0, // Manually granted
    priceId: null,
    maxDuration: Infinity,
    features: ['All Premium features', 'Moderator access', 'Early access']
  }
};

// Get tier max duration (in seconds)
function getTierMaxDuration(tier) {
  return SUBSCRIPTION_TIERS[tier]?.maxDuration || 60;
}

// Get tier access level (0-1) for legacy percentage-based content gating
function getTierAccessLevel(tier) {
  const tierConfig = SUBSCRIPTION_TIERS[tier];
  if (!tierConfig) return 0.2;
  if (tierConfig.maxDuration === Infinity) return 1.0;
  if (tierConfig.maxDuration >= 300) return 1.0;
  if (tierConfig.maxDuration >= 60) return 0.6;
  return 0.2;
}

// Check if user can access video by duration
function canAccessVideo(userTier, videoDuration) {
  const maxDuration = getTierMaxDuration(userTier);
  return videoDuration <= maxDuration;
}

// Legacy: Check content by index (for backward compatibility)
function canAccessContent(userTier, contentIndex, totalContent) {
  // Map old percentage-based to duration tiers
  const tier = SUBSCRIPTION_TIERS[userTier];
  if (!tier) return false;
  if (tier.maxDuration === Infinity) return true;
  // Free: first 20%, Basic: first 60%
  const accessLevel = tier.maxDuration >= 300 ? 1.0 : (tier.maxDuration >= 60 ? 0.6 : 0.2);
  const accessibleCount = Math.ceil(totalContent * accessLevel);
  return contentIndex < accessibleCount;
}

const app = express();
app.set('trust proxy', 1); // Trust first proxy (Apache) - fixes rate-limit X-Forwarded-For errors
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
  baseUrl: process.env.PUSHR_BASE_URL || 'https://kinky-thots.xxx',
  cdnUrls: {
    images: process.env.PUSHR_CDN_IMAGES || 'https://6406.s3.de01.sonic.r-cdn.com',
    videos: process.env.PUSHR_CDN_VIDEOS || 'https://6318.s3.nvme.de01.sonic.r-cdn.com'
  },
  zones: {
    images: process.env.PUSHR_ZONE_IMAGES || '6406',
    videos: process.env.PUSHR_ZONE_VIDEOS || '6318'
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
      `SELECT id, username, email, password_hash, display_color, avatar_url, bio, subscription_tier, is_admin, admin_role, force_password_change
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

    // Generate JWT (include is_admin and admin_role for permissions)
    const token = jwt.sign(
      {
        userId: Number(user.id),
        username: user.username,
        isAdmin: Boolean(user.is_admin),
        adminRole: user.admin_role || 'none'
      },
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
        avatar_url: user.avatar_url || null,
        bio: user.bio || null,
        subscription_tier: user.subscription_tier || 'free',
        is_admin: Boolean(user.is_admin),
        admin_role: user.admin_role || 'none',
        force_password_change: Boolean(user.force_password_change)
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
      `SELECT id, username, email, display_color, avatar_url, bio, subscription_tier, subscription_status,
              subscription_expires_at, created_at, last_login_at, is_admin, admin_role, force_password_change
       FROM users WHERE id = ?`,
      [req.user.userId]
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    const user = users[0];
    res.json({
      user: {
        id: Number(user.id),
        username: user.username,
        email: user.email,
        display_color: user.display_color,
        avatar_url: user.avatar_url,
        bio: user.bio,
        subscription_tier: user.subscription_tier || 'free',
        subscription_status: user.subscription_status || 'active',
        subscription_expires_at: user.subscription_expires_at,
        created_at: user.created_at,
        last_login_at: user.last_login_at,
        is_admin: Boolean(user.is_admin),
        admin_role: user.admin_role || 'none',
        force_password_change: Boolean(user.force_password_change)
      }
    });

  } catch (err) {
    console.error('Get user error:', err);
    res.status(500).json({ error: 'Failed to get user info' });
  } finally {
    if (conn) conn.release();
  }
});

// Update user profile (color, bio)
app.put('/api/auth/profile', authenticateToken, async (req, res) => {
  let conn;
  try {
    const { display_color, bio } = req.body;
    const userId = req.user.userId;

    conn = await pool.getConnection();

    // Validate color format
    if (display_color && !/^#[0-9A-Fa-f]{6}$/.test(display_color)) {
      return res.status(400).json({ error: 'Invalid color format. Use #RRGGBB' });
    }

    // Validate bio length (max 500 chars)
    if (bio !== undefined && bio.length > 500) {
      return res.status(400).json({ error: 'Bio must be 500 characters or less' });
    }

    // Build dynamic update query
    const updates = [];
    const values = [];

    if (display_color) {
      updates.push('display_color = ?');
      values.push(display_color);
    }

    if (bio !== undefined) {
      updates.push('bio = ?');
      values.push(bio.trim() || null);
    }

    if (updates.length > 0) {
      values.push(userId);
      await conn.query(`UPDATE users SET ${updates.join(', ')} WHERE id = ?`, values);
    }

    // Fetch updated user
    const users = await conn.query(
      'SELECT id, username, email, display_color, avatar_url, bio, subscription_tier FROM users WHERE id = ?',
      [userId]
    );

    res.json({
      success: true,
      user: {
        id: Number(users[0].id),
        username: users[0].username,
        email: users[0].email,
        display_color: users[0].display_color,
        avatar_url: users[0].avatar_url,
        bio: users[0].bio,
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

// Upload avatar image to CDN
app.post('/api/auth/avatar', authenticateToken, async (req, res) => {
  let conn;
  try {
    const userId = req.user.userId;

    // Check if file was uploaded
    if (!req.files || !req.files.avatar) {
      return res.status(400).json({ error: 'No avatar file uploaded' });
    }

    const file = req.files.avatar;

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.mimetype)) {
      return res.status(400).json({ error: 'Invalid file type. Use JPG, PNG, GIF, or WebP' });
    }

    // Validate file size (max 5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
      return res.status(400).json({ error: 'File too large. Maximum size is 5MB' });
    }

    // Generate unique filename
    const ext = path.extname(file.name).toLowerCase() || '.jpg';
    const filename = `avatars/user-${userId}-${Date.now()}${ext}`;

    // Upload to CDN via S3
    let s3Client;
    try {
      s3Client = new SonicS3Client();
    } catch (configErr) {
      console.error('S3 config error:', configErr.message);
      return res.status(500).json({ error: 'CDN configuration error' });
    }

    // Read file data (useTempFiles mode stores data in temp file, not file.data)
    const fileData = file.tempFilePath ? fs.readFileSync(file.tempFilePath) : file.data;
    const uploadResult = await s3Client.uploadBuffer(fileData, filename, file.mimetype);

    if (!uploadResult.success) {
      console.error('S3 upload error:', uploadResult.error);
      return res.status(500).json({ error: 'Failed to upload avatar to CDN' });
    }

    // Update user's avatar_url in database
    conn = await pool.getConnection();

    // Get old avatar URL to potentially delete later
    const oldUser = await conn.query('SELECT avatar_url FROM users WHERE id = ?', [userId]);
    const oldAvatarUrl = oldUser[0]?.avatar_url;

    // Update with new avatar URL
    await conn.query('UPDATE users SET avatar_url = ? WHERE id = ?', [uploadResult.cdn_url, userId]);

    // Delete old avatar from CDN if it exists
    if (oldAvatarUrl && oldAvatarUrl.includes('avatars/user-')) {
      try {
        const oldKey = oldAvatarUrl.split('/').pop();
        await s3Client.deleteFile(`avatars/${oldKey}`);
      } catch (deleteErr) {
        console.warn('Could not delete old avatar:', deleteErr.message);
      }
    }

    // Fetch updated user
    const users = await conn.query(
      'SELECT id, username, email, display_color, avatar_url, bio, subscription_tier FROM users WHERE id = ?',
      [userId]
    );

    res.json({
      success: true,
      avatar_url: uploadResult.cdn_url,
      user: {
        id: Number(users[0].id),
        username: users[0].username,
        email: users[0].email,
        display_color: users[0].display_color,
        avatar_url: users[0].avatar_url,
        bio: users[0].bio,
        subscription_tier: users[0].subscription_tier || 'free'
      }
    });

  } catch (err) {
    console.error('Avatar upload error:', err);
    res.status(500).json({ error: 'Failed to upload avatar' });
  } finally {
    if (conn) conn.release();
  }
});

// Delete avatar
app.delete('/api/auth/avatar', authenticateToken, async (req, res) => {
  let conn;
  try {
    const userId = req.user.userId;

    conn = await pool.getConnection();

    // Get current avatar URL
    const users = await conn.query('SELECT avatar_url FROM users WHERE id = ?', [userId]);
    const avatarUrl = users[0]?.avatar_url;

    if (!avatarUrl) {
      return res.status(400).json({ error: 'No avatar to delete' });
    }

    // Delete from CDN if it's our avatar
    if (avatarUrl.includes('avatars/user-')) {
      try {
        const s3Client = new SonicS3Client();
        const key = avatarUrl.split('/').slice(-2).join('/'); // Get 'avatars/user-xxx.jpg'
        await s3Client.deleteFile(key);
      } catch (deleteErr) {
        console.warn('Could not delete avatar from CDN:', deleteErr.message);
      }
    }

    // Clear avatar_url in database
    await conn.query('UPDATE users SET avatar_url = NULL WHERE id = ?', [userId]);

    res.json({ success: true, message: 'Avatar deleted' });

  } catch (err) {
    console.error('Avatar delete error:', err);
    res.status(500).json({ error: 'Failed to delete avatar' });
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
        from: `"${SITE_NAME}" <${process.env.SMTP_USER || 'noreply@kinky-thots.xxx'}>`,
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

    // Hash and save new password, clear force_password_change flag
    const newPasswordHash = await bcrypt.hash(newPassword, BCRYPT_ROUNDS);
    await conn.query('UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?', [newPasswordHash, userId]);

    res.json({ success: true, message: 'Password changed successfully' });

  } catch (err) {
    console.error('Change password error:', err);
    res.status(500).json({ error: 'Failed to change password' });
  } finally {
    if (conn) conn.release();
  }
});

// Change email (for logged in users)
app.post('/api/auth/change-email', authenticateToken, async (req, res) => {
  let conn;
  try {
    const { newEmail, password } = req.body;
    const userId = req.user.userId;

    if (!newEmail || !password) {
      return res.status(400).json({ error: 'New email and current password are required' });
    }

    // Validate email format
    if (!validator.isEmail(newEmail)) {
      return res.status(400).json({ error: 'Invalid email format' });
    }

    conn = await pool.getConnection();

    // Get current password hash and email
    const users = await conn.query(
      'SELECT email, password_hash FROM users WHERE id = ?',
      [userId]
    );

    if (users.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    // Verify current password
    const validPassword = await bcrypt.compare(password, users[0].password_hash);
    if (!validPassword) {
      return res.status(401).json({ error: 'Password is incorrect' });
    }

    // Check if new email is same as current
    if (users[0].email.toLowerCase() === newEmail.toLowerCase()) {
      return res.status(400).json({ error: 'New email is the same as current email' });
    }

    // Check if new email is already in use
    const existingUser = await conn.query(
      'SELECT id FROM users WHERE email = ? AND id != ?',
      [newEmail.toLowerCase(), userId]
    );

    if (existingUser.length > 0) {
      return res.status(409).json({ error: 'Email is already in use by another account' });
    }

    // Update email
    await conn.query('UPDATE users SET email = ? WHERE id = ?', [newEmail.toLowerCase(), userId]);

    console.log(`Auth: Email changed for user ${userId} from ${users[0].email} to ${newEmail}`);

    res.json({ success: true, message: 'Email updated successfully', email: newEmail.toLowerCase() });

  } catch (err) {
    console.error('Change email error:', err);
    res.status(500).json({ error: 'Failed to change email' });
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
    let authConn;
    try {
      const decoded = jwt.verify(token, JWT_SECRET);
      authConn = await pool.getConnection();
      const users = await authConn.query(
        'SELECT subscription_tier FROM users WHERE id = ?',
        [decoded.userId]
      );
      if (users.length > 0) {
        userTier = users[0].subscription_tier || 'free';
      }
    } catch (err) {
      // Invalid token, use free tier
    } finally {
      if (authConn) authConn.release();
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
// NOWPAYMENTS CRYPTO PAYMENT ENDPOINTS
// ============================================

// NOWPayments configuration
const NOWPAYMENTS_CONFIG = {
  apiKey: process.env.NOWPAYMENTS_API_KEY,
  ipnSecret: process.env.NOWPAYMENTS_IPN_SECRET,
  sandbox: process.env.NOWPAYMENTS_SANDBOX === 'true',
  email: process.env.NOWPAYMENTS_EMAIL,
  password: process.env.NOWPAYMENTS_PASSWORD,
  basicPlanId: process.env.NOWPAYMENTS_BASIC_PLAN_ID,
  premiumPlanId: process.env.NOWPAYMENTS_PREMIUM_PLAN_ID,
  yearlyPlanId: process.env.NOWPAYMENTS_YEARLY_PLAN_ID,
  jwtToken: null,
  jwtExpiry: null,
  get baseUrl() {
    return this.sandbox
      ? 'https://api-sandbox.nowpayments.io'
      : 'https://api.nowpayments.io';
  }
};

// Get JWT token for NOWPayments subscription API
async function getNowPaymentsJwtToken() {
  // Return cached token if still valid (with 30s buffer)
  if (NOWPAYMENTS_CONFIG.jwtToken && NOWPAYMENTS_CONFIG.jwtExpiry > Date.now() + 30000) {
    return NOWPAYMENTS_CONFIG.jwtToken;
  }

  if (!NOWPAYMENTS_CONFIG.email || !NOWPAYMENTS_CONFIG.password) {
    throw new Error('NOWPayments email/password not configured');
  }

  const response = await fetch(`${NOWPAYMENTS_CONFIG.baseUrl}/v1/auth`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email: NOWPAYMENTS_CONFIG.email,
      password: NOWPAYMENTS_CONFIG.password
    })
  });

  const data = await response.json();

  if (!response.ok || !data.token) {
    console.error('NOWPayments auth error:', data);
    throw new Error(data.message || 'Failed to authenticate with NOWPayments');
  }

  // Cache token (expires in 5 minutes)
  NOWPAYMENTS_CONFIG.jwtToken = data.token;
  NOWPAYMENTS_CONFIG.jwtExpiry = Date.now() + 4 * 60 * 1000; // 4 min to be safe

  return data.token;
}

// Verify IPN signature from NOWPayments webhook
function verifyIpnSignature(payload, signature) {
  if (!NOWPAYMENTS_CONFIG.ipnSecret) {
    console.warn('NOWPAYMENTS_IPN_SECRET not set - skipping verification');
    return true;
  }
  const hmac = crypto.createHmac('sha512', NOWPAYMENTS_CONFIG.ipnSecret);
  const sortedPayload = JSON.stringify(payload, Object.keys(payload).sort());
  hmac.update(sortedPayload);
  const calculatedSignature = hmac.digest('hex');
  return calculatedSignature === signature;
}

// Check NOWPayments API status
app.get('/api/payments/status', async (req, res) => {
  try {
    const response = await fetch(`${NOWPAYMENTS_CONFIG.baseUrl}/v1/status`);
    const data = await response.json();
    res.json({
      configured: !!NOWPAYMENTS_CONFIG.apiKey,
      sandbox: NOWPAYMENTS_CONFIG.sandbox,
      apiStatus: data
    });
  } catch (err) {
    res.json({ configured: !!NOWPAYMENTS_CONFIG.apiKey, error: err.message });
  }
});

// Get available cryptocurrencies
app.get('/api/payments/currencies', async (req, res) => {
  try {
    const response = await fetch(`${NOWPAYMENTS_CONFIG.baseUrl}/v1/currencies`, {
      headers: { 'x-api-key': NOWPAYMENTS_CONFIG.apiKey }
    });
    const data = await response.json();
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch currencies' });
  }
});

// Get minimum payment amount for a currency
app.get('/api/payments/min-amount/:currency', async (req, res) => {
  try {
    const { currency } = req.params;
    const response = await fetch(
      `${NOWPAYMENTS_CONFIG.baseUrl}/v1/min-amount?currency_from=${currency.toLowerCase()}&currency_to=usd`,
      { headers: { 'x-api-key': NOWPAYMENTS_CONFIG.apiKey } }
    );
    const data = await response.json();
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch minimum amount' });
  }
});

// Get estimated crypto amount for a USD price
app.get('/api/payments/estimate', async (req, res) => {
  console.log('Estimate endpoint hit:', req.query);
  try {
    const { amount, currency } = req.query;

    if (!amount || !currency) {
      return res.status(400).json({ error: 'Amount and currency are required' });
    }

    if (!NOWPAYMENTS_CONFIG.apiKey) {
      return res.status(503).json({ error: 'Payment API not configured' });
    }

    const url = `${NOWPAYMENTS_CONFIG.baseUrl}/v1/estimate?amount=${amount}&currency_from=usd&currency_to=${currency.toLowerCase()}`;
    const response = await fetch(url, {
      headers: { 'x-api-key': NOWPAYMENTS_CONFIG.apiKey }
    });
    const data = await response.json();

    if (!response.ok) {
      console.error('NOWPayments estimate error:', response.status, data);
      return res.status(response.status).json(data);
    }

    res.json(data);
  } catch (err) {
    console.error('Estimate fetch error:', err.message);
    res.status(500).json({ error: 'Failed to fetch estimate' });
  }
});

// Create inline payment (user stays on site)
// Returns crypto address and amount for direct payment
app.post('/api/payments/create', authenticateToken, async (req, res) => {
  const { tier, pay_currency } = req.body;

  if (!NOWPAYMENTS_CONFIG.apiKey) {
    return res.status(503).json({ error: 'Payment system not configured' });
  }

  if (!pay_currency) {
    return res.status(400).json({ error: 'Payment currency is required' });
  }

  const tierConfig = SUBSCRIPTION_TIERS[tier];
  if (!tierConfig || tierConfig.price === 0) {
    return res.status(400).json({ error: 'Invalid subscription tier' });
  }

  try {
    const orderId = `${tier}-${req.user.userId}-${Date.now()}`;
    const isYearly = tier === 'yearly';

    const paymentData = {
      price_amount: tierConfig.price,
      price_currency: 'usd',
      pay_currency: pay_currency.toLowerCase(),
      ipn_callback_url: `${SITE_URL}/api/nowpayments/webhook`,
      order_id: orderId,
      order_description: `${tierConfig.name} ${isYearly ? 'Yearly' : ''} Subscription - ${SITE_NAME}`,
      is_fixed_rate: true,
      is_fee_paid_by_user: false
    };

    console.log('Creating NOWPayments payment:', paymentData);

    const response = await fetch(`${NOWPAYMENTS_CONFIG.baseUrl}/v1/payment`, {
      method: 'POST',
      headers: {
        'x-api-key': NOWPAYMENTS_CONFIG.apiKey,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(paymentData)
    });

    const payment = await response.json();
    console.log('NOWPayments payment response:', JSON.stringify(payment, null, 2));

    if (!response.ok) {
      console.error('NOWPayments payment error:', payment);
      return res.status(500).json({
        error: payment.message || 'Failed to create payment'
      });
    }

    // Store payment ID for webhook matching
    let conn;
    try {
      conn = await pool.getConnection();
      await conn.query(
        `UPDATE users SET
          payment_customer_id = ?,
          payment_provider = 'nowpayments'
        WHERE id = ?`,
        [payment.payment_id, req.user.userId]
      );
    } finally {
      if (conn) conn.release();
    }

    // Return payment details for inline display
    res.json({
      payment_id: payment.payment_id,
      payment_status: payment.payment_status,
      pay_address: payment.pay_address,
      pay_amount: payment.pay_amount,
      pay_currency: payment.pay_currency,
      price_amount: payment.price_amount,
      price_currency: payment.price_currency,
      order_id: payment.order_id,
      valid_until: payment.valid_until,
      payin_extra_id: payment.payin_extra_id, // Memo/tag for some currencies
      network: payment.network,
      burning_percent: payment.burning_percent, // Network fee %
      network_precision: payment.network_precision,
      tier,
      tierName: tierConfig.name,
      isYearly
    });

  } catch (err) {
    console.error('NOWPayments payment error:', err);
    res.status(500).json({ error: err.message || 'Payment service error' });
  }
});

// Get payment status by ID
app.get('/api/payments/:paymentId', authenticateToken, async (req, res) => {
  const { paymentId } = req.params;

  if (!NOWPAYMENTS_CONFIG.apiKey) {
    return res.status(503).json({ error: 'Payment system not configured' });
  }

  try {
    const response = await fetch(
      `${NOWPAYMENTS_CONFIG.baseUrl}/v1/payment/${paymentId}`,
      {
        headers: { 'x-api-key': NOWPAYMENTS_CONFIG.apiKey }
      }
    );

    const payment = await response.json();

    if (!response.ok) {
      return res.status(response.status).json({
        error: payment.message || 'Failed to fetch payment status'
      });
    }

    res.json({
      payment_id: payment.payment_id,
      payment_status: payment.payment_status,
      pay_address: payment.pay_address,
      pay_amount: payment.pay_amount,
      actually_paid: payment.actually_paid,
      pay_currency: payment.pay_currency,
      price_amount: payment.price_amount,
      price_currency: payment.price_currency,
      order_id: payment.order_id,
      outcome_amount: payment.outcome_amount,
      outcome_currency: payment.outcome_currency
    });

  } catch (err) {
    console.error('Payment status error:', err);
    res.status(500).json({ error: err.message || 'Failed to fetch payment status' });
  }
});

// Create payment for subscription checkout (legacy - redirects to NOWPayments)
// - Basic/Premium: Uses recurring subscription API (requires JWT)
// - Lifetime: Uses one-time invoice API
app.post('/api/subscriptions/checkout', authenticateToken, async (req, res) => {
  const { tier } = req.body;

  if (!NOWPAYMENTS_CONFIG.apiKey) {
    return res.status(503).json({ error: 'Payment system not configured' });
  }

  const tierConfig = SUBSCRIPTION_TIERS[tier];
  if (!tierConfig || tierConfig.price === 0) {
    return res.status(400).json({ error: 'Invalid subscription tier' });
  }

  try {
    const isYearly = tier === 'yearly';
    const isSubscription = tier === 'basic' || tier === 'premium' || tier === 'yearly';
    let paymentUrl, paymentId;

    if (isSubscription) {
      // Use subscription API for all subscription tiers (Basic/Premium/Yearly)
      const planId = tier === 'basic'
        ? NOWPAYMENTS_CONFIG.basicPlanId
        : tier === 'premium'
          ? NOWPAYMENTS_CONFIG.premiumPlanId
          : NOWPAYMENTS_CONFIG.yearlyPlanId;

      if (!planId) {
        return res.status(503).json({
          error: 'Subscription plans not configured. Please contact support.'
        });
      }

      // Get JWT token for subscription API
      const jwtToken = await getNowPaymentsJwtToken();

      // Get user email for subscription
      let conn;
      let userEmail;
      try {
        conn = await pool.getConnection();
        const rows = await conn.query('SELECT email FROM users WHERE id = ?', [req.user.userId]);
        userEmail = rows[0]?.email;
      } finally {
        if (conn) conn.release();
      }

      if (!userEmail) {
        return res.status(400).json({ error: 'User email not found' });
      }

      // Check for existing pending subscription first
      const existingResponse = await fetch(
        `${NOWPAYMENTS_CONFIG.baseUrl}/v1/subscriptions?email=${encodeURIComponent(userEmail)}&plan_id=${planId}&limit=10`,
        {
          headers: {
            'Authorization': `Bearer ${jwtToken}`,
            'x-api-key': NOWPAYMENTS_CONFIG.apiKey
          }
        }
      );

      let result = null;

      if (existingResponse.ok) {
        const existingData = await existingResponse.json();
        const subscriptions = existingData.result || existingData || [];

        // Find a pending subscription for this plan
        const pending = subscriptions.find(s =>
          s.subscription_plan_id === planId &&
          s.status === 'WAITING_PAY' &&
          s.subscriber?.email === userEmail
        );

        if (pending) {
          console.log('Found existing pending subscription:', pending.id);
          result = pending;
        }
      }

      // Create new subscription if no pending one exists
      if (!result) {
        const subscriptionData = {
          subscription_plan_id: planId,
          email: userEmail
        };

        const response = await fetch(`${NOWPAYMENTS_CONFIG.baseUrl}/v1/subscriptions`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${jwtToken}`,
            'x-api-key': NOWPAYMENTS_CONFIG.apiKey,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(subscriptionData)
        });

        const subscription = await response.json();
        console.log('NOWPayments subscription response:', JSON.stringify(subscription, null, 2));

        if (!response.ok) {
          // If already subscribed, try to get the existing subscription
          if (subscription.message && subscription.message.includes('already subscribed')) {
            console.log('Email already subscribed, fetching existing subscription...');

            // List all subscriptions and find the matching one
            const listResponse = await fetch(
              `${NOWPAYMENTS_CONFIG.baseUrl}/v1/subscriptions?limit=100`,
              {
                headers: {
                  'Authorization': `Bearer ${jwtToken}`,
                  'x-api-key': NOWPAYMENTS_CONFIG.apiKey
                }
              }
            );

            if (listResponse.ok) {
              const listData = await listResponse.json();
              const allSubs = listData.result || listData || [];

              // Find subscription matching this email and plan
              const existing = allSubs.find(s =>
                s.subscription_plan_id == planId &&
                s.subscriber?.email === userEmail
              );

              if (existing) {
                console.log('Found existing subscription:', existing.id, 'status:', existing.status);
                result = existing;
              }
            }

            if (!result) {
              console.error('Could not find existing subscription');
              return res.status(500).json({ error: 'Subscription exists but could not be retrieved' });
            }
          } else {
            console.error('NOWPayments subscription error:', subscription);
            return res.status(500).json({
              error: subscription.message || 'Failed to create subscription'
            });
          }
        } else {
          // NOWPayments returns result as array
          const resultArray = subscription.result || [subscription];
          result = Array.isArray(resultArray) ? resultArray[0] : resultArray;
        }
      }

      if (!result || !result.id) {
        console.error('Invalid subscription response:', result);
        return res.status(500).json({ error: 'Invalid subscription response' });
      }

      paymentId = result.id;
      // Build the payment link - NOWPayments subscription payments go to their hosted page
      paymentUrl = `https://nowpayments.io/payment/?id=${result.id}`;

      // Store subscription ID
      let conn2;
      try {
        conn2 = await pool.getConnection();
        await conn2.query(
          `UPDATE users SET
            payment_customer_id = ?,
            payment_provider = 'nowpayments'
          WHERE id = ?`,
          [paymentId, req.user.userId]
        );
      } finally {
        if (conn2) conn2.release();
      }

    } else {
      // Use invoice API for one-time payments (Lifetime)
      const invoiceData = {
        price_amount: tierConfig.price,
        price_currency: 'usd',
        ipn_callback_url: `${SITE_URL}/api/nowpayments/webhook`,
        order_id: `${tier}-${req.user.userId}-${Date.now()}`,
        order_description: `${tierConfig.name} Lifetime Access - ${SITE_NAME}`,
        success_url: `${SITE_URL}/checkout.html?status=success&tier=${tier}`,
        cancel_url: `${SITE_URL}/checkout.html?status=failed&tier=${tier}`,
        is_fixed_rate: true,
        is_fee_paid_by_user: false
      };

      const response = await fetch(`${NOWPAYMENTS_CONFIG.baseUrl}/v1/invoice`, {
        method: 'POST',
        headers: {
          'x-api-key': NOWPAYMENTS_CONFIG.apiKey,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(invoiceData)
      });

      const invoice = await response.json();

      if (!response.ok) {
        console.error('NOWPayments invoice error:', invoice);
        return res.status(500).json({
          error: invoice.message || 'Failed to create invoice'
        });
      }

      paymentId = invoice.id;
      paymentUrl = invoice.invoice_url;

      // Store invoice ID
      let conn;
      try {
        conn = await pool.getConnection();
        await conn.query(
          `UPDATE users SET
            payment_customer_id = ?,
            payment_provider = 'nowpayments'
          WHERE id = ?`,
          [invoice.id, req.user.userId]
        );
      } finally {
        if (conn) conn.release();
      }
    }

    res.json({
      paymentId,
      invoiceUrl: paymentUrl,
      tier,
      isSubscription
    });

  } catch (err) {
    console.error('NOWPayments error:', err);
    res.status(500).json({ error: err.message || 'Payment service error' });
  }
});

// NOWPayments IPN (Instant Payment Notification) webhook
app.post('/api/nowpayments/webhook', express.json(), async (req, res) => {
  const signature = req.headers['x-nowpayments-sig'];
  const payload = req.body;

  // Verify signature
  if (!verifyIpnSignature(payload, signature)) {
    console.error('NOWPayments webhook: Invalid signature');
    return res.status(401).json({ error: 'Invalid signature' });
  }

  console.log('NOWPayments webhook:', payload.payment_status, payload.order_id);
  if (payload.parent_payment_id) {
    console.log('Re-deposit detected, parent_payment_id:', payload.parent_payment_id);
  }

  let conn;
  try {
    conn = await pool.getConnection();

    // Parse order_id: "tier-userId-timestamp"
    const orderParts = payload.order_id?.split('-') || [];
    const tier = orderParts[0];
    const orderId = payload.order_id;

    // Store/update payment record in payments table
    try {
      await conn.query(
        `INSERT INTO payments (payment_id, parent_payment_id, order_id, price_amount, price_currency,
          pay_amount, pay_currency, actually_paid, payment_status, tier)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          actually_paid = VALUES(actually_paid),
          payment_status = VALUES(payment_status),
          updated_at = CURRENT_TIMESTAMP`,
        [
          payload.payment_id,
          payload.parent_payment_id || null,
          orderId,
          payload.price_amount || 0,
          payload.price_currency || 'usd',
          payload.pay_amount || null,
          payload.pay_currency || null,
          payload.actually_paid || null,
          payload.payment_status,
          tier
        ]
      );
    } catch (paymentErr) {
      // Table may not exist yet - log and continue
      console.log('Note: payments table insert skipped:', paymentErr.code);
    }

    // Get user by payment_customer_id (invoice ID)
    const users = await conn.query(
      'SELECT id, username, email, subscription_tier FROM users WHERE payment_customer_id = ?',
      [payload.invoice_id || payload.payment_id]
    );

    // If this is a re-deposit, try finding user by parent payment
    let user = users[0];
    if (!user && payload.parent_payment_id) {
      const parentUsers = await conn.query(
        'SELECT id, username, email, subscription_tier FROM users WHERE payment_customer_id = ?',
        [payload.parent_payment_id]
      );
      user = parentUsers[0];
      if (user) console.log('Found user via parent_payment_id:', user.username);
    }

    // Update payment with user_id if found
    if (user) {
      try {
        await conn.query(
          'UPDATE payments SET user_id = ? WHERE payment_id = ?',
          [user.id, payload.payment_id]
        );
      } catch (e) { /* payments table may not exist */ }
    }

    switch (payload.payment_status) {
      case 'waiting':
        // Payment created, waiting for crypto
        console.log('Payment waiting:', payload.order_id);
        break;

      case 'confirming':
        // Payment received, confirming on blockchain
        console.log('Payment confirming:', payload.order_id);
        break;

      case 'confirmed':
      case 'finished':
        // Payment complete - activate subscription
        if (user) {
          const tierConfig = SUBSCRIPTION_TIERS[tier];
          const isYearly = tier === 'yearly';

          // Calculate expiration
          const expiresAt = new Date();
          if (isYearly) {
            expiresAt.setDate(expiresAt.getDate() + 365); // 1 year
          } else {
            expiresAt.setMonth(expiresAt.getMonth() + 1); // 1 month
          }

          await conn.query(
            `UPDATE users SET
              subscription_tier = ?,
              subscription_status = 'active',
              subscription_expires_at = ?,
              payment_provider = 'nowpayments'
            WHERE id = ?`,
            [tier, expiresAt, user.id]
          );

          // Send welcome email
          if (isYearly) {
            await sendSubscriptionEmail('yearly', user, {
              amount: payload.price_amount || tierConfig?.price,
              tierName: 'Yearly Premium',
              expiresAt: expiresAt.toLocaleDateString(),
              features: tierConfig?.features || []
            });
          } else {
            await sendSubscriptionEmail('welcome', user, {
              tierName: tierConfig?.name || tier,
              features: tierConfig?.features || []
            });
            await sendSubscriptionEmail('receipt', user, {
              tierName: tierConfig?.name || tier,
              amount: payload.price_amount || tierConfig?.price?.toFixed(2),
              date: new Date().toLocaleDateString(),
              nextBilling: expiresAt?.toLocaleDateString() || 'N/A'
            });
          }

          console.log(`Subscription activated: ${user.username} -> ${tier}`);
        }
        break;

      case 'partially_paid':
        console.log('Partial payment:', payload.order_id, payload.actually_paid);
        break;

      case 'failed':
      case 'expired':
        // Payment failed or expired
        if (user) {
          await sendSubscriptionEmail('failed', user, {});
        }
        console.log('Payment failed/expired:', payload.order_id);
        break;

      case 'refunded':
        // Refund processed
        if (user) {
          await conn.query(
            `UPDATE users SET subscription_status = 'cancelled' WHERE id = ?`,
            [user.id]
          );
        }
        console.log('Payment refunded:', payload.order_id);
        break;

      default:
        console.log('Unknown payment status:', payload.payment_status);
    }

    res.status(200).json({ received: true });

  } catch (err) {
    console.error('Webhook error:', err);
    res.status(500).json({ error: 'Webhook processing failed' });
  } finally {
    if (conn) conn.release();
  }
});

// Cancel subscription (database only - user manages billing via NOWPayments)
app.post('/api/subscriptions/cancel', authenticateToken, async (req, res) => {
  let conn;
  try {
    conn = await pool.getConnection();

    // Update database - keep tier until expiration
    await conn.query(
      `UPDATE users SET subscription_status = 'cancelled' WHERE id = ?`,
      [req.user.userId]
    );

    // Get user for email
    const users = await conn.query(
      'SELECT id, username, email, subscription_tier, subscription_expires_at FROM users WHERE id = ?',
      [req.user.userId]
    );

    if (users[0]) {
      await sendSubscriptionEmail('cancelled', users[0], {
        accessUntil: users[0].subscription_expires_at ? new Date(users[0].subscription_expires_at).toLocaleDateString() : null
      });
    }

    res.json({ success: true, message: 'Subscription cancelled. Access continues until expiration.' });

  } catch (err) {
    console.error('Cancel error:', err);
    res.status(500).json({ error: 'Failed to cancel subscription' });
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

// ============================================
// MEMBERS & MESSAGING API ENDPOINTS
// ============================================

// Rate limiter for messaging
const messageLimiter = rateLimit({
  windowMs: 60 * 1000, // 1 minute
  max: 30, // 30 messages per minute
  message: { error: 'Too many messages, please slow down' },
  standardHeaders: true,
  legacyHeaders: false
});

// Subscriber middleware - check if user has active subscription
const requireSubscriber = async (req, res, next) => {
  if (!req.user || !req.user.userId) {
    return res.status(401).json({ error: 'Authentication required' });
  }

  let conn;
  try {
    conn = await pool.getConnection();
    const users = await conn.query(
      'SELECT subscription_tier, subscription_status FROM users WHERE id = ?',
      [req.user.userId]
    );
    if (!users[0]) {
      return res.status(404).json({ error: 'User not found' });
    }
    const tier = users[0].subscription_tier || 'free';
    const status = users[0].subscription_status || 'active';

    // Free users cannot use DM feature
    if (tier === 'free') {
      return res.status(403).json({
        error: 'DM feature requires Basic or higher subscription',
        upgradeUrl: '/subscriptions.html'
      });
    }
    // Check if subscription is active (or lifetime which never expires)
    if (status !== 'active' && tier !== 'lifetime' && tier !== 'vip') {
      return res.status(403).json({
        error: 'Your subscription has expired',
        upgradeUrl: '/subscriptions.html'
      });
    }
    req.user.subscriptionTier = tier;
    next();
  } catch (err) {
    console.error('Subscriber check error:', err);
    res.status(500).json({ error: 'Failed to verify subscription status' });
  } finally {
    if (conn) conn.release();
  }
};

// Get members list (public info only - no email)
app.get('/api/members', authenticateToken, async (req, res) => {
  const { page = 1, limit = 20, search, tier } = req.query;
  const offset = (parseInt(page) - 1) * parseInt(limit);
  const limitNum = Math.min(parseInt(limit), 50); // Max 50 per page

  let conn;
  try {
    conn = await pool.getConnection();

    let query = `SELECT id, username, avatar_url, subscription_tier, last_login_at, created_at
                 FROM users WHERE 1=1`;
    let countQuery = `SELECT COUNT(*) as total FROM users WHERE 1=1`;
    const params = [];
    const countParams = [];

    // Search by username only (no email for privacy)
    if (search) {
      query += ` AND username LIKE ?`;
      countQuery += ` AND username LIKE ?`;
      params.push(`%${search}%`);
      countParams.push(`%${search}%`);
    }

    // Filter by subscription tier
    if (tier) {
      query += ` AND subscription_tier = ?`;
      countQuery += ` AND subscription_tier = ?`;
      params.push(tier);
      countParams.push(tier);
    }

    query += ` ORDER BY last_login_at DESC NULLS LAST, created_at DESC LIMIT ? OFFSET ?`;
    params.push(limitNum, offset);

    const [members, totalResult] = await Promise.all([
      conn.query(query, params),
      conn.query(countQuery, countParams)
    ]);

    const total = Number(totalResult[0].total);

    res.json({
      members: members.map(m => ({
        id: Number(m.id),
        username: m.username,
        avatar_url: m.avatar_url,
        subscription_tier: m.subscription_tier || 'free',
        last_login_at: m.last_login_at,
        created_at: m.created_at
      })),
      pagination: {
        page: parseInt(page),
        limit: limitNum,
        total,
        totalPages: Math.ceil(total / limitNum)
      }
    });
  } catch (err) {
    console.error('Members list error:', err);
    res.status(500).json({ error: 'Failed to fetch members' });
  } finally {
    if (conn) conn.release();
  }
});

// Get user's conversations list
app.get('/api/messages/conversations', authenticateToken, requireSubscriber, async (req, res) => {
  const userId = req.user.userId;

  let conn;
  try {
    conn = await pool.getConnection();

    // Get all unique conversations with last message and unread count
    const conversations = await conn.query(`
      SELECT
        CASE WHEN pm.sender_id = ? THEN pm.recipient_id ELSE pm.sender_id END as other_user_id,
        u.username as other_username,
        u.avatar_url as other_avatar_url,
        u.subscription_tier as other_tier,
        (SELECT content FROM private_messages pm2
         WHERE (pm2.sender_id = ? AND pm2.recipient_id = other_user_id)
            OR (pm2.sender_id = other_user_id AND pm2.recipient_id = ?)
         ORDER BY pm2.created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM private_messages pm2
         WHERE (pm2.sender_id = ? AND pm2.recipient_id = other_user_id)
            OR (pm2.sender_id = other_user_id AND pm2.recipient_id = ?)
         ORDER BY pm2.created_at DESC LIMIT 1) as last_message_at,
        (SELECT COUNT(*) FROM private_messages pm2
         WHERE pm2.sender_id = other_user_id
           AND pm2.recipient_id = ?
           AND pm2.is_read = FALSE) as unread_count
      FROM private_messages pm
      JOIN users u ON u.id = CASE WHEN pm.sender_id = ? THEN pm.recipient_id ELSE pm.sender_id END
      WHERE pm.sender_id = ? OR pm.recipient_id = ?
      GROUP BY other_user_id
      ORDER BY last_message_at DESC
    `, [userId, userId, userId, userId, userId, userId, userId, userId, userId]);

    res.json({
      conversations: conversations.map(c => ({
        userId: Number(c.other_user_id),
        username: c.other_username,
        avatar_url: c.other_avatar_url,
        subscription_tier: c.other_tier || 'free',
        lastMessage: c.last_message ? c.last_message.substring(0, 100) : null,
        lastMessageAt: c.last_message_at,
        unreadCount: Number(c.unread_count)
      }))
    });
  } catch (err) {
    console.error('Conversations list error:', err);
    res.status(500).json({ error: 'Failed to fetch conversations' });
  } finally {
    if (conn) conn.release();
  }
});

// Get messages with specific user
app.get('/api/messages/conversation/:userId', authenticateToken, requireSubscriber, async (req, res) => {
  const currentUserId = req.user.userId;
  const otherUserId = parseInt(req.params.userId);
  const { page = 1, limit = 50 } = req.query;
  const offset = (parseInt(page) - 1) * parseInt(limit);
  const limitNum = Math.min(parseInt(limit), 100);

  if (isNaN(otherUserId) || otherUserId === currentUserId) {
    return res.status(400).json({ error: 'Invalid user ID' });
  }

  let conn;
  try {
    conn = await pool.getConnection();

    // Verify other user exists
    const otherUser = await conn.query(
      'SELECT id, username, avatar_url, subscription_tier FROM users WHERE id = ?',
      [otherUserId]
    );
    if (otherUser.length === 0) {
      return res.status(404).json({ error: 'User not found' });
    }

    // Get messages between the two users
    const messages = await conn.query(`
      SELECT id, sender_id, recipient_id, content, is_read, created_at
      FROM private_messages
      WHERE (sender_id = ? AND recipient_id = ?)
         OR (sender_id = ? AND recipient_id = ?)
      ORDER BY created_at DESC
      LIMIT ? OFFSET ?
    `, [currentUserId, otherUserId, otherUserId, currentUserId, limitNum, offset]);

    // Get total count
    const countResult = await conn.query(`
      SELECT COUNT(*) as total FROM private_messages
      WHERE (sender_id = ? AND recipient_id = ?)
         OR (sender_id = ? AND recipient_id = ?)
    `, [currentUserId, otherUserId, otherUserId, currentUserId]);

    const total = Number(countResult[0].total);

    res.json({
      otherUser: {
        id: Number(otherUser[0].id),
        username: otherUser[0].username,
        avatar_url: otherUser[0].avatar_url,
        subscription_tier: otherUser[0].subscription_tier || 'free'
      },
      messages: messages.reverse().map(m => ({
        id: Number(m.id),
        senderId: Number(m.sender_id),
        recipientId: Number(m.recipient_id),
        content: m.content,
        isRead: Boolean(m.is_read),
        createdAt: m.created_at,
        isMine: Number(m.sender_id) === currentUserId
      })),
      pagination: {
        page: parseInt(page),
        limit: limitNum,
        total,
        hasMore: offset + limitNum < total
      }
    });
  } catch (err) {
    console.error('Get conversation error:', err);
    res.status(500).json({ error: 'Failed to fetch messages' });
  } finally {
    if (conn) conn.release();
  }
});

// Send a message
app.post('/api/messages/send', authenticateToken, requireSubscriber, messageLimiter, async (req, res) => {
  const senderId = req.user.userId;
  const { recipientId, content } = req.body;

  // Validate input
  if (!recipientId || !content) {
    return res.status(400).json({ error: 'Recipient ID and content are required' });
  }

  const recipientIdNum = parseInt(recipientId);
  if (isNaN(recipientIdNum) || recipientIdNum === senderId) {
    return res.status(400).json({ error: 'Invalid recipient' });
  }

  // Validate content length
  const contentTrimmed = content.trim();
  if (contentTrimmed.length === 0) {
    return res.status(400).json({ error: 'Message cannot be empty' });
  }
  if (contentTrimmed.length > 2000) {
    return res.status(400).json({ error: 'Message too long (max 2000 characters)' });
  }

  let conn;
  try {
    conn = await pool.getConnection();

    // Verify recipient exists
    const recipient = await conn.query(
      'SELECT id, username FROM users WHERE id = ?',
      [recipientIdNum]
    );
    if (recipient.length === 0) {
      return res.status(404).json({ error: 'Recipient not found' });
    }

    // Sanitize content (basic XSS prevention)
    const sanitizedContent = validator.escape(contentTrimmed);

    // Insert message
    const result = await conn.query(
      'INSERT INTO private_messages (sender_id, recipient_id, content, created_at) VALUES (?, ?, ?, NOW())',
      [senderId, recipientIdNum, sanitizedContent]
    );

    const messageId = Number(result.insertId);

    // Get sender info for WebSocket notification
    const sender = await conn.query(
      'SELECT username, avatar_url FROM users WHERE id = ?',
      [senderId]
    );

    const newMessage = {
      id: messageId,
      senderId,
      recipientId: recipientIdNum,
      content: sanitizedContent,
      isRead: false,
      createdAt: new Date().toISOString(),
      isMine: true
    };

    // Send real-time notification to recipient if online
    notifyNewDM(recipientIdNum, {
      messageId,
      senderId,
      senderUsername: sender[0]?.username || 'Unknown',
      senderAvatar: sender[0]?.avatar_url,
      content: sanitizedContent,
      createdAt: newMessage.createdAt
    });

    res.json({
      success: true,
      message: newMessage
    });
  } catch (err) {
    console.error('Send message error:', err);
    res.status(500).json({ error: 'Failed to send message' });
  } finally {
    if (conn) conn.release();
  }
});

// Mark messages as read
app.post('/api/messages/read/:userId', authenticateToken, requireSubscriber, async (req, res) => {
  const currentUserId = req.user.userId;
  const otherUserId = parseInt(req.params.userId);

  if (isNaN(otherUserId)) {
    return res.status(400).json({ error: 'Invalid user ID' });
  }

  let conn;
  try {
    conn = await pool.getConnection();

    // Mark all messages from other user as read
    await conn.query(
      'UPDATE private_messages SET is_read = TRUE WHERE sender_id = ? AND recipient_id = ? AND is_read = FALSE',
      [otherUserId, currentUserId]
    );

    res.json({ success: true });
  } catch (err) {
    console.error('Mark read error:', err);
    res.status(500).json({ error: 'Failed to mark messages as read' });
  } finally {
    if (conn) conn.release();
  }
});

// Get unread message count (for nav badge)
app.get('/api/messages/unread-count', authenticateToken, async (req, res) => {
  const userId = req.user.userId;

  let conn;
  try {
    conn = await pool.getConnection();

    const result = await conn.query(
      'SELECT COUNT(*) as count FROM private_messages WHERE recipient_id = ? AND is_read = FALSE',
      [userId]
    );

    res.json({ count: Number(result[0].count) });
  } catch (err) {
    console.error('Unread count error:', err);
    res.status(500).json({ error: 'Failed to get unread count' });
  } finally {
    if (conn) conn.release();
  }
});

// ============================================
// ADMIN API ENDPOINTS
// ============================================

// Admin middleware - check if user is admin
const requireAdmin = async (req, res, next) => {
  if (!req.user || !req.user.userId) {
    return res.status(401).json({ error: 'Authentication required' });
  }

  let conn;
  try {
    conn = await pool.getConnection();
    const users = await conn.query('SELECT is_admin FROM users WHERE id = ?', [req.user.userId]);
    if (!users[0] || !users[0].is_admin) {
      return res.status(403).json({ error: 'Admin access required' });
    }
    next();
  } catch (err) {
    res.status(500).json({ error: 'Failed to verify admin status' });
  } finally {
    if (conn) conn.release();
  }
};

// Check if current user is admin
app.get('/api/admin/check', authenticateToken, async (req, res) => {
  let conn;
  try {
    conn = await pool.getConnection();
    const users = await conn.query('SELECT is_admin FROM users WHERE id = ?', [req.user.userId]);
    res.json({ isAdmin: users[0]?.is_admin === 1 });
  } catch (err) {
    res.status(500).json({ error: 'Failed to check admin status' });
  } finally {
    if (conn) conn.release();
  }
});

// Get dashboard stats
app.get('/api/admin/stats', authenticateToken, requireAdmin, async (req, res) => {
  let conn;
  try {
    conn = await pool.getConnection();

    // Total users
    const totalUsersResult = await conn.query('SELECT COUNT(*) as count FROM users');
    const totalUsers = Number(totalUsersResult[0].count);

    // Active subscribers (non-free, active status)
    const activeSubsResult = await conn.query(
      `SELECT COUNT(*) as count FROM users WHERE subscription_tier != 'free' AND subscription_status = 'active'`
    );
    const activeSubscribers = Number(activeSubsResult[0].count);

    // Monthly revenue (from payments table, this month, finished status)
    const monthStart = new Date();
    monthStart.setDate(1);
    monthStart.setHours(0, 0, 0, 0);

    let monthlyRevenue = 0;
    let totalRevenue = 0;
    let pendingPayments = 0;

    try {
      const monthlyResult = await conn.query(
        `SELECT COALESCE(SUM(price_amount), 0) as total FROM payments
         WHERE payment_status IN ('finished', 'confirmed') AND created_at >= ?`,
        [monthStart]
      );
      monthlyRevenue = parseFloat(monthlyResult[0].total) || 0;

      const totalResult = await conn.query(
        `SELECT COALESCE(SUM(price_amount), 0) as total FROM payments WHERE payment_status IN ('finished', 'confirmed')`
      );
      totalRevenue = parseFloat(totalResult[0].total) || 0;

      const pendingResult = await conn.query(
        `SELECT COUNT(*) as count FROM payments WHERE payment_status IN ('waiting', 'confirming')`
      );
      pendingPayments = Number(pendingResult[0].count);
    } catch (e) {
      // payments table may not exist yet
      console.log('Note: payments table not found for stats');
    }

    // Total videos from manifest
    let totalVideos = 0;
    try {
      const manifest = JSON.parse(fs.readFileSync(path.join(__dirname, '../data/video-manifest.json'), 'utf-8'));
      totalVideos = manifest.videos?.length || 0;
    } catch (e) {
      totalVideos = 0;
    }

    // Recent activity (new users + completed payments)
    let recentActivity = [];
    try {
      const recentUsers = await conn.query(
        `SELECT username, created_at, 'New registration' as action FROM users ORDER BY created_at DESC LIMIT 5`
      );
      const recentPayments = await conn.query(
        `SELECT u.username, p.created_at, CONCAT('Payment: $', p.price_amount, ' (', p.tier, ')') as action
         FROM payments p LEFT JOIN users u ON p.user_id = u.id
         WHERE p.payment_status IN ('finished', 'confirmed')
         ORDER BY p.created_at DESC LIMIT 5`
      );
      recentActivity = [...recentUsers, ...recentPayments]
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
        .slice(0, 10);
    } catch (e) {
      // Just return empty activity
    }

    res.json({
      totalUsers,
      activeSubscribers,
      monthlyRevenue,
      totalRevenue,
      pendingPayments,
      totalVideos,
      recentActivity
    });
  } catch (err) {
    console.error('Admin stats error:', err);
    res.status(500).json({ error: 'Failed to fetch stats' });
  } finally {
    if (conn) conn.release();
  }
});

// Get members list with filtering
app.get('/api/admin/members', authenticateToken, requireAdmin, async (req, res) => {
  const { page = 1, limit = 20, search, tier, status } = req.query;
  const offset = (parseInt(page) - 1) * parseInt(limit);

  let conn;
  try {
    conn = await pool.getConnection();

    let query = `SELECT id, username, email, subscription_tier, subscription_status,
                 subscription_expires_at, is_admin, created_at, last_login_at FROM users WHERE 1=1`;
    const params = [];

    if (search) {
      query += ` AND (username LIKE ? OR email LIKE ?)`;
      params.push(`%${search}%`, `%${search}%`);
    }
    if (tier) {
      query += ` AND subscription_tier = ?`;
      params.push(tier);
    }
    if (status) {
      query += ` AND subscription_status = ?`;
      params.push(status);
    }

    query += ` ORDER BY created_at DESC LIMIT ? OFFSET ?`;
    params.push(parseInt(limit), offset);

    const members = await conn.query(query, params);
    res.json({ members, page: parseInt(page), limit: parseInt(limit) });
  } catch (err) {
    console.error('Admin members error:', err);
    res.status(500).json({ error: 'Failed to fetch members' });
  } finally {
    if (conn) conn.release();
  }
});

// Get single member
app.get('/api/admin/members/:id', authenticateToken, requireAdmin, async (req, res) => {
  const { id } = req.params;

  let conn;
  try {
    conn = await pool.getConnection();
    const users = await conn.query(
      `SELECT id, username, email, subscription_tier, subscription_status,
       subscription_expires_at, is_admin, created_at, last_login_at FROM users WHERE id = ?`,
      [id]
    );

    if (!users[0]) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json(users[0]);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch user' });
  } finally {
    if (conn) conn.release();
  }
});

// Update member
app.put('/api/admin/members/:id', authenticateToken, requireAdmin, async (req, res) => {
  const { id } = req.params;
  const { subscription_tier, subscription_status, is_admin } = req.body;

  let conn;
  try {
    conn = await pool.getConnection();

    // Prevent admin from removing their own admin status
    if (parseInt(id) === req.user.userId && is_admin === false) {
      return res.status(400).json({ error: 'Cannot remove your own admin status' });
    }

    await conn.query(
      `UPDATE users SET subscription_tier = ?, subscription_status = ?, is_admin = ? WHERE id = ?`,
      [subscription_tier, subscription_status, is_admin ? 1 : 0, id]
    );

    res.json({ success: true });
  } catch (err) {
    console.error('Admin update user error:', err);
    res.status(500).json({ error: 'Failed to update user' });
  } finally {
    if (conn) conn.release();
  }
});

// Delete a member
app.delete('/api/admin/members/:id', authenticateToken, requireAdmin, async (req, res) => {
  const { id } = req.params;

  let conn;
  try {
    conn = await pool.getConnection();

    // Prevent admin from deleting themselves
    if (parseInt(id) === req.user.userId) {
      return res.status(400).json({ error: 'Cannot delete your own account' });
    }

    // Check if user exists
    const users = await conn.query('SELECT id, username FROM users WHERE id = ?', [id]);
    if (!users[0]) {
      return res.status(404).json({ error: 'User not found' });
    }
    const username = users[0].username;

    // Delete user (cascades to related tables via foreign keys)
    await conn.query('DELETE FROM users WHERE id = ?', [id]);

    console.log(`Admin ${req.user.userId} deleted user ${id} (${username})`);
    res.json({ success: true, message: `User ${username} deleted` });
  } catch (err) {
    console.error('Admin delete user error:', err);
    res.status(500).json({ error: 'Failed to delete user' });
  } finally {
    if (conn) conn.release();
  }
});

// Get transactions list with filtering
app.get('/api/admin/transactions', authenticateToken, requireAdmin, async (req, res) => {
  const { page = 1, limit = 20, status, tier, dateFrom, dateTo } = req.query;
  const offset = (parseInt(page) - 1) * parseInt(limit);

  let conn;
  try {
    conn = await pool.getConnection();

    let query = `SELECT p.*, u.username FROM payments p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1`;
    const params = [];

    if (status) {
      query += ` AND p.payment_status = ?`;
      params.push(status);
    }
    if (tier) {
      query += ` AND p.tier = ?`;
      params.push(tier);
    }
    if (dateFrom) {
      query += ` AND p.created_at >= ?`;
      params.push(dateFrom);
    }
    if (dateTo) {
      query += ` AND p.created_at <= ?`;
      params.push(dateTo + ' 23:59:59');
    }

    query += ` ORDER BY p.created_at DESC LIMIT ? OFFSET ?`;
    params.push(parseInt(limit), offset);

    const transactions = await conn.query(query, params);
    res.json({ transactions, page: parseInt(page), limit: parseInt(limit) });
  } catch (err) {
    console.error('Admin transactions error:', err);
    res.status(500).json({ error: 'Failed to fetch transactions' });
  } finally {
    if (conn) conn.release();
  }
});

// Get content (videos) list
app.get('/api/admin/content', authenticateToken, requireAdmin, async (req, res) => {
  try {
    const manifestPath = path.join(__dirname, '../data/video-manifest.json');
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf-8'));
    res.json({ videos: manifest.videos || [] });
  } catch (err) {
    res.status(500).json({ error: 'Failed to load video manifest', videos: [] });
  }
});

// ============================================
// END ADMIN API ENDPOINTS
// ============================================

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

    // Upload to CDN (images go to images bucket, videos to videos bucket)
    let cdnUrl = null;
    try {
      const s3Client = new SonicS3Client(null, fileType === 'image' ? 'images' : 'videos');
      const uploadResult = await s3Client.uploadFile(uploadPath, filename);
      if (uploadResult.success) {
        cdnUrl = uploadResult.cdn_url;
        console.log(`[CDN] Uploaded to: ${cdnUrl}`);
      } else {
        console.error(`[CDN] Upload failed: ${uploadResult.error}`);
      }
    } catch (cdnErr) {
      console.error(`[CDN] Error uploading to CDN: ${cdnErr.message}`);
    }

    // Insert into database with file type and path
    conn = await pool.getConnection();
    const result = await conn.query(
      'INSERT INTO images (filename, file_type, file_path, upload_time) VALUES (?, ?, ?, NOW())',
      [filename, fileType, webPath]
    );

    // Trigger CDN prefetch in background (for pull zone caching)
    prefetchToCDN(filename, fileType);

    res.json({
      success: true,
      id: Number(result.insertId),
      filename: filename,
      file_type: fileType,
      web_path: webPath,
      full_url: cdnUrl || `${webPath}/${encodeURIComponent(filename)}`,
      cdn_url: cdnUrl,
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

// DM clients - map userId to WebSocket for real-time notifications
const dmClients = new Map();

// Send real-time DM notification to recipient
function notifyNewDM(recipientId, messageData) {
  const recipientWs = dmClients.get(recipientId);
  if (recipientWs && recipientWs.readyState === WebSocket.OPEN) {
    recipientWs.send(JSON.stringify({
      type: 'new_dm',
      messageId: messageData.messageId,
      fromId: messageData.senderId,
      fromUsername: messageData.senderUsername,
      fromAvatar: messageData.senderAvatar,
      preview: messageData.content.substring(0, 100),
      createdAt: messageData.createdAt
    }));
  }
}

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
        // Register for DM notifications
        dmClients.set(ws.userId, ws);
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
    // Remove from DM clients if authenticated
    if (ws.userId) {
      dmClients.delete(ws.userId);
    }
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
