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

const app = express();
const PORT = 3001;

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

// PushrCDN Configuration
const PUSHR_CONFIG = {
  enabled: true,
  apiKey: 'REDACTED_PUSHR_API_KEY',
  apiUrl: 'https://www.pushrcdn.com/api/v3/prefetch',
  secretToken: 'e872d33deed25bcbcd1ddcb596dfc1872f9a6a07',
  baseUrl: 'https://kinky-thots.com',
  cdnUrls: {
    images: 'https://c5988z6294.r-cdn.com',  // Push zone with uploaded content
    videos: 'https://c5988z6295.r-cdn.com'   // Push zone for videos
  },
  zones: {
    images: '6294',  // Using push zone
    videos: '6293'
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

// MariaDB connection pool
const pool = mariadb.createPool({
  host: 'localhost',
  user: 'gkeylow',
  password: 'REDACTED_DB_PASSWORD',
  database: 'gallery_db',
  connectionLimit: 5
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

// Start server
app.listen(PORT, () => {
  console.log(`Gallery backend running on port ${PORT}`);
  console.log(`Image uploads: ${PATHS.images}`);
  console.log(`Video uploads: ${PATHS.videos}`);
});
