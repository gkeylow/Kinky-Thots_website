const express = require('express');
const fileUpload = require('express-fileupload');
const cors = require('cors');
const mariadb = require('mariadb');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = 3001;

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
  limits: { fileSize: 500 * 1024 * 1024 }, // 500MB for videos
  abortOnLimit: true,
  createParentPath: true
}));

// Create uploads directory if it doesn't exist
const uploadsDir = path.join(__dirname, 'uploads');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

// Serve static files
app.use('/uploads', express.static(uploadsDir));

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    status: 'OK', 
    timestamp: new Date().toISOString(),
    uploadsDir: uploadsDir
  });
});

// Get all images
app.get('/api/gallery', async (req, res) => {
  let conn;
  try {
    conn = await pool.getConnection();
    const rows = await conn.query(
      'SELECT id, filename, upload_time FROM images ORDER BY upload_time DESC'
    );
    res.json(rows);
  } catch (err) {
    console.error('Database error:', err);
    res.status(500).json({ error: 'Database error', details: err.message });
  } finally {
    if (conn) conn.release();
  }
});

// Upload image
app.post('/api/upload', async (req, res) => {
  let conn;
  try {
    if (!req.files || !req.files.image) {
      return res.status(400).json({ error: 'No file uploaded' });
    }

    const image = req.files.image;
    
    // Validate file type - images and videos
    const allowedTypes = [
      'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
      'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska',
      'video/webm', 'video/mpeg', 'video/x-flv'
    ];
    if (!allowedTypes.includes(image.mimetype)) {
      return res.status(400).json({ error: 'Invalid file type. Only images and videos allowed.' });
    }

    // Generate unique filename
    const timestamp = Date.now();
    const safeName = image.name.replace(/[^a-zA-Z0-9.-]/g, '_');
    const filename = `${timestamp}_${safeName}`;
    const uploadPath = path.join(uploadsDir, filename);

    // Move file
    await image.mv(uploadPath);

    // Save to database
    conn = await pool.getConnection();
    const result = await conn.query(
      'INSERT INTO images (filename, upload_time) VALUES (?, NOW())',
      [filename]
    );

    res.json({ 
      success: true, 
      filename: filename,
      id: Number(result.insertId)
    });

  } catch (error) {
    console.error('Upload error:', error);
    res.status(500).json({ error: 'Upload failed', details: error.message });
  } finally {
    if (conn) conn.release();
  }
});

// Delete image
app.delete('/api/delete/:id', async (req, res) => {
  let conn;
  try {
    const imageId = parseInt(req.params.id);
    
    conn = await pool.getConnection();
    
    // Get filename
    const rows = await conn.query('SELECT filename FROM images WHERE id = ?', [imageId]);
    
    if (rows.length === 0) {
      return res.status(404).json({ error: 'Image not found' });
    }

    const filename = rows[0].filename;
    
    // Delete from database
    await conn.query('DELETE FROM images WHERE id = ?', [imageId]);
    
    // Delete file
    const filePath = path.join(uploadsDir, filename);
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }

    res.json({ success: true });

  } catch (error) {
    console.error('Delete error:', error);
    res.status(500).json({ error: 'Delete failed', details: error.message });
  } finally {
    if (conn) conn.release();
  }
});

// Start server
if (require.main === module) {
  app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 Gallery server running on port ${PORT}`);
    console.log(`📁 Uploads directory: ${uploadsDir}`);
    console.log(`🔗 API endpoints:`);
    console.log(`   GET  /api/gallery - List all images`);
    console.log(`   POST /api/upload  - Upload image`);
    console.log(`   DELETE /api/delete/:id - Delete image`);
  });
}

module.exports = app;
