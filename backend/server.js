
const express = require('express');
const fileUpload = require('express-fileupload');
const cors = require('cors');
const mariadb = require('mariadb');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = 3001;

// MariaDB pool
const pool = mariadb.createPool({
  host: 'localhost',
  user: 'xxxxxxx',
  password: 'xxxxxx',
  database: 'xxxxxx',
  connectionLimit: 5
});

app.use(cors());
app.use(express.json());
app.use(fileUpload({
  limits: { fileSize: 50 * 1024 * 1024 }, // 50MB max file size
  abortOnLimit: true
}));

// Serve static files
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));
app.use('/assets', express.static(path.join(__dirname, 'assets')));
app.use('/', express.static(path.join(__dirname, 'public')));

// Create uploads directory if not exists
if (!fs.existsSync(path.join(__dirname, 'uploads'))){
    fs.mkdirSync(path.join(__dirname, 'uploads'));
}

// Upload endpoint (POST /upload)
app.post('/upload', async (req, res) => {
  try {
    if (!req.files || !req.files.image) {
      return res.status(400).json({ error: 'No file uploaded' });
    }
    
    const image = req.files.image;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(image.mimetype)) {
      return res.status(400).json({ error: 'Invalid file type. Only images allowed.' });
    }
    
    const filename = Date.now() + '_' + image.name.replace(/[^a-zA-Z0-9.-]/g, '_');
    const uploadPath = path.join(__dirname, 'uploads', filename);
    
    image.mv(uploadPath, async (err) => {
      if (err) {
        console.error('Upload error:', err);
        return res.status(500).json({ error: 'Upload failed' });
      }
      
      // Save metadata to DB
      try {
        const conn = await pool.getConnection();
        await conn.query('INSERT INTO images (filename, upload_time) VALUES (?, NOW())', [filename]);
        conn.release();
        res.json({ success: true, filename });
      } catch (dbErr) {
        console.error('Database error:', dbErr);
        res.status(500).json({ error: 'Database error' });
      }
    });
  } catch (error) {
    console.error('Server error:', error);
    res.status(500).json({ error: 'Server error' });
  }
});

// Gallery endpoint (GET /gallery)
app.get('/gallery', async (req, res) => {
  try {
    const conn = await pool.getConnection();
    const rows = await conn.query('SELECT id, filename, upload_time FROM images ORDER BY upload_time DESC');
    conn.release();
    res.json(rows);
  } catch (err) {
    console.error('Database error:', err);
    res.status(500).json({ error: 'Database error' });
  }
});

// Delete endpoint (DELETE /delete/:id) - No admin required
app.delete('/delete/:id', async (req, res) => {
  try {
    const imageId = req.params.id;
    const conn = await pool.getConnection();
    
    // Get filename before deleting
    const result = await conn.query('SELECT filename FROM images WHERE id = ?', [imageId]);
    if (result.length === 0) {
      conn.release();
      return res.status(404).json({ error: 'Image not found' });
    }
    
    const filename = result[0].filename;
    
    // Delete from database
    await conn.query('DELETE FROM images WHERE id = ?', [imageId]);
    conn.release();
    
    // Delete physical file
    const filePath = path.join(__dirname, 'uploads', filename);
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }
    
    res.json({ success: true });
  } catch (err) {
    console.error('Delete error:', err);
    res.status(500).json({ error: 'Delete failed' });
  }
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'OK', timestamp: new Date().toISOString() });
});

app.listen(PORT, () => {
  console.log(`🚀 Gallery server running on port ${PORT}`);
  console.log(`📁 Serving uploads from: ${path.join(__dirname, 'uploads')}`);
});
