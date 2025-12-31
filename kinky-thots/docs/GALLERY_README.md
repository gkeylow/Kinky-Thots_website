# Photo Gallery Web Application - Rebuilt

## Overview
Complete photo upload and gallery display system for kinky-thots.com

## Architecture

### Backend (Node.js + Express)
- **Location**: `/var/www/kinky-thots/backend/server.js`
- **Port**: 3001
- **Database**: MariaDB (gallery_db)
- **Uploads**: `/var/www/kinky-thots/backend/uploads/`

### Frontend (JavaScript)
- **Main Gallery**: `/var/www/kinky-thots/assets/gallery.js`
- **Sissy Gallery**: `/var/www/kinky-thots/assets/sissygallery.js`

### Web Server (Apache)
- Proxies `/api/*` requests to Node.js backend on port 3001
- Serves static files from `/var/www/kinky-thots/`
- Serves uploads from `/var/www/kinky-thots/backend/uploads/`

## API Endpoints

### GET /api/gallery
Returns list of all uploaded images
```json
[
  {
    "id": 1,
    "filename": "1234567890_image.jpg",
    "upload_time": "2025-12-05T07:03:22.000Z"
  }
]
```

### POST /api/upload
Upload a new image
- **Content-Type**: multipart/form-data
- **Field**: image (file)
- **Max Size**: 50MB
- **Allowed Types**: JPEG, PNG, GIF, WebP

Response:
```json
{
  "success": true,
  "filename": "1234567890_image.jpg",
  "id": 1
}
```

### DELETE /api/delete/:id
Delete an image by ID
Response:
```json
{
  "success": true
}
```

### GET /health
Health check endpoint
```json
{
  "status": "OK",
  "timestamp": "2025-12-05T07:12:32.376Z",
  "uploadsDir": "/var/www/kinky-thots/backend/uploads"
}
```

## Database Schema

### Table: images
```sql
CREATE TABLE images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  upload_time DATETIME NOT NULL,
  album VARCHAR(255) NULL
);
```

### Database User
- **User**: `<DB_USER>` (see .env)
- **Password**: `<DB_PASSWORD>` (see .env)
- **Database**: kinky_thots
- **Privileges**: ALL on kinky_thots.*

## File Structure

```
/var/www/kinky-thots/
├── backend/
│   ├── server.js           # Node.js backend
│   ├── package.json        # Dependencies
│   └── uploads/            # Uploaded images
├── assets/
│   ├── gallery.js          # Main gallery JavaScript
│   ├── gallery.css         # Gallery styles
│   ├── sissygallery.js     # Sissy page gallery
│   └── sissylonglegs.css   # Sissy page styles
├── gallery.html            # Main gallery page
└── sissylonglegs.html      # Sissy page with gallery
```

## Apache Configuration

```apache
<VirtualHost *:80>
    ServerName kinky-thots.com
    DocumentRoot /var/www/kinky-thots/

    # Proxy API to Node.js
    ProxyPass /api http://localhost:3001/api
    ProxyPassReverse /api http://localhost:3001/api

    # Serve uploads
    Alias /uploads /var/www/kinky-thots/backend/uploads
    <Directory /var/www/kinky-thots/backend/uploads>
        Options -Indexes +FollowSymLinks
        Require all granted
    </Directory>
</VirtualHost>
```

## Starting the Backend

```bash
cd /var/www/kinky-thots/backend
node server.js
```

Or run in background:
```bash
cd /var/www/kinky-thots/backend
node server.js > /tmp/gallery-server.log 2>&1 &
```

## Checking Status

### Backend Status
```bash
curl http://localhost:3001/health
```

### Gallery API
```bash
curl http://localhost:3001/api/gallery
```

### Through Apache
```bash
curl http://kinky-thots.com/api/gallery
```

## Troubleshooting

### Gallery not loading
1. Check backend is running: `ps aux | grep node`
2. Check Apache proxy: `curl http://localhost/api/gallery`
3. Check browser console for errors (F12)
4. Clear browser cache (Ctrl+Shift+R)

### Upload failing
1. Check uploads directory permissions: `ls -la /var/www/kinky-thots/backend/uploads/`
2. Check backend logs: `cat /tmp/gallery-server.log`
3. Verify file size < 50MB
4. Verify file type is image

### Database errors
1. Test connection: `mysql -u $MARIADB_USER -p$MARIADB_PASSWORD kinky_thots -e "SELECT COUNT(*) FROM images;"`
2. Check user privileges: `mysql -u root -e "SHOW GRANTS FOR '$MARIADB_USER'@'localhost';"`

### Images not displaying
1. Check uploads directory: `ls /var/www/kinky-thots/backend/uploads/`
2. Test image URL: `curl -I http://kinky-thots.com/uploads/FILENAME`
3. Check Apache alias configuration

## Features

### Main Gallery (gallery.html)
- ✅ Upload images with drag & drop
- ✅ Progress bar during upload
- ✅ Mosaic grid layout
- ✅ Lightbox view with navigation
- ✅ Delete images
- ✅ Auto-refresh every 30 seconds

### Sissy Gallery (sissylonglegs.html)
- ✅ Display uploaded images
- ✅ Mosaic grid layout
- ✅ Lightbox view with navigation
- ✅ Read-only (no upload/delete)

## Security Notes

- CORS enabled for all origins (development mode)
- No authentication required (as per requirements)
- File type validation on upload
- File size limit: 50MB
- SQL injection protection via parameterized queries

## Maintenance

### Restart Backend
```bash
pkill -f "node.*server.js"
cd /var/www/kinky-thots/backend
node server.js > /tmp/gallery-server.log 2>&1 &
```

### Reload Apache
```bash
systemctl reload apache2
```

### Clear Old Uploads
```bash
# Delete images older than 30 days
find /var/www/kinky-thots/backend/uploads/ -type f -mtime +30 -delete
```

### Backup Database
```bash
mysqldump -u root gallery_db > gallery_backup_$(date +%Y%m%d).sql
```

## Version History

### v3.0 (2025-12-05)
- Complete rebuild of gallery system
- New API endpoints with `/api` prefix
- Improved error handling and logging
- Simplified JavaScript with better debugging
- Updated Apache proxy configuration
- Fixed all path issues
- Added comprehensive documentation
