const express = require('express');
const fileUpload = require('express-fileupload');
const cors = require('cors');
const mariadb = require('mariadb');
const fs = require('fs');
const path = require('path');

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

const uploadsDir = path.join(__dirname, '../uploads');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

app.use(fileUpload({
  limits: { fileSize: 1000 * 1024 * 1024 },
  useTempFiles: true,
  tempFileDir: '/tmp/',
  createParentPath: true,
  uploadsDir: uploadsDir
}));

// Serve uploaded files statically
app.use('/uploads', express.static(uploadsDir));

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
    const timestamp = Date.now();
    
    // Sanitize filename: remove special characters except dots, dashes, and underscores
    const sanitizedName = image.name.replace(/[^a-zA-Z0-9._-]/g, '_');
    const filename = `${timestamp}_${sanitizedName}`;
    const uploadPath = path.join(uploadsDir, filename);

    await image.mv(uploadPath);

    conn = await pool.getConnection();
    const result = await conn.query(
      'INSERT INTO images (filename, upload_time) VALUES (?, NOW())',
      [filename]
    );

    res.json({
      success: true,
      id: Number(result.insertId),
      filename: filename,
      message: 'File uploaded successfully'
    });
  } catch (err) {
    console.error('Upload error:', err);
    res.status(500).json({ error: 'Upload failed', details: err.message });
  } finally {
    if (conn) conn.release();
  }
});

// Delete image
app.delete('/api/gallery/:id', async (req, res) => {
  let conn;
  try {
    const id = parseInt(req.params.id);
    
    conn = await pool.getConnection();
    const rows = await conn.query('SELECT filename FROM images WHERE id = ?', [id]);
    
    if (rows.length === 0) {
      return res.status(404).json({ error: 'Image not found' });
    }

    const filename = rows[0].filename;
    const filePath = path.join(uploadsDir, filename);

    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }

    await conn.query('DELETE FROM images WHERE id = ?', [id]);

    res.json({ success: true, message: 'Image deleted successfully' });
  } catch (err) {
    console.error('Delete error:', err);
    res.status(500).json({ error: 'Delete failed', details: err.message });
  } finally {
    if (conn) conn.release();
  }
});

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Start server
app.listen(PORT, () => {
  console.log(`Gallery backend running on port ${PORT}`);
  console.log(`Uploads directory: ${uploadsDir}`);
});
