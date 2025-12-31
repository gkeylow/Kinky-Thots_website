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

// JWT Configuration - requires environment variable
if (!process.env.JWT_SECRET) {
  console.error('FATAL: JWT_SECRET environment variable is required');
  process.exit(1);
}
const JWT_SECRET = process.env.JWT_SECRET;
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '7d';
const BCRYPT_ROUNDS = 12;

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

wss.on('connection', (ws) => {
  chatClients.add(ws);
  viewerCount++;

  // Assign guest name and color
  ws.username = `Guest${Math.floor(Math.random() * 9999)}`;
  ws.userColor = getRandomColor();

  console.log(`Chat: ${ws.username} connected (${viewerCount} viewers)`);

  // Send welcome message and viewer count
  ws.send(JSON.stringify({
    type: 'welcome',
    username: ws.username,
    color: ws.userColor,
    viewerCount: viewerCount
  }));

  // Broadcast updated viewer count
  broadcast({ type: 'viewers', count: viewerCount });

  ws.on('message', (data) => {
    try {
      const msg = JSON.parse(data);

      switch (msg.type) {
        case 'chat':
          // Broadcast chat message to all
          broadcast({
            type: 'chat',
            username: ws.username,
            color: ws.userColor,
            message: msg.message.substring(0, 500), // Limit message length
            timestamp: Date.now()
          });
          break;

        case 'reaction':
          // Broadcast emoji reaction
          broadcast({
            type: 'reaction',
            emoji: msg.emoji,
            username: ws.username
          });
          break;

        case 'setName':
          // Allow username change
          const newName = msg.name.substring(0, 20).replace(/[^a-zA-Z0-9_-]/g, '');
          if (newName.length >= 2) {
            ws.username = newName;
            ws.send(JSON.stringify({ type: 'nameChanged', username: newName }));
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
