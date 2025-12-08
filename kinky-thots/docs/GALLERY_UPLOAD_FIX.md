# Gallery Upload Fix - December 7, 2025

## Problem
Server errors were occurring when trying to upload images on gallery.html. The error was:
```
Field 'upload_time' doesn't have a default value
```

## Root Cause
The database table `images` had a column `upload_time` that was defined as `NOT NULL` but had no default value. When the server tried to insert a new image record, it only provided the `filename` field, causing the database to reject the insert.

## Solutions Applied

### 1. Database Schema Fix
Updated the `upload_time` column to have a default value:
```sql
ALTER TABLE images MODIFY upload_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
```

### 2. Server Code Fix
Updated the INSERT query in `/var/www/kinky-thots/backend/server.js` to explicitly set the upload_time:
```javascript
// Before:
'INSERT INTO images (filename) VALUES (?)'

// After:
'INSERT INTO images (filename, upload_time) VALUES (?, NOW())'
```

Also added filename sanitization to prevent issues with special characters:
```javascript
// Sanitize filename: remove special characters except dots, dashes, and underscores
const sanitizedName = image.name.replace(/[^a-zA-Z0-9._-]/g, '_');
const filename = `${timestamp}_${sanitizedName}`;
```

### 3. Static File Serving
Added static file serving for uploaded files in the backend server:
```javascript
app.use('/uploads', express.static(uploadsDir));
```

### 4. Frontend Configuration
Updated `/var/www/kinky-thots/assets/gallery.js` to correctly point to the backend server:
```javascript
const CONFIG = {
  apiBase: window.location.hostname === 'localhost' ? 'http://localhost:3001' : 'http://kinky-thots.com:3001',
  endpoints: {
    gallery: '/api/gallery',
    upload: '/api/upload',
    delete: '/api/gallery'
  },
  uploadsPath: '/uploads/'
};
```

## Testing
Verified the fix with successful upload tests:
- Upload endpoint returns success: ✓
- File is saved to disk: ✓
- Database record is created with timestamp: ✓
- Uploaded file is accessible via HTTP: ✓

## Server Status
The backend server is running on port 3001 and serving:
- API endpoints: `/api/gallery`, `/api/upload`, `/api/gallery/:id`
- Static files: `/uploads/*`
- Health check: `/health`

## Placeholder / "Image Not Found" Fix

### Problem
Gallery items showed "Image not found" placeholder because:
1. Files were in `/var/www/kinky-thots/backend/uploads/` instead of `/var/www/kinky-thots/uploads/`
2. Database entries existed for files that weren't accessible
3. **Filenames with special characters (like parentheses) caused URL encoding issues**

### Root Cause
Files with names like `IMG_9646(1).gif` contain parentheses which can cause issues with:
- URL encoding/decoding
- Browser image loading
- File system operations

### Solution
1. **Added filename sanitization** - Server now automatically replaces special characters with underscores
2. **Fixed existing problematic file** - Renamed `IMG_9646(1).gif` to `IMG_9646_1_.gif`
3. Moved files to the correct directory
4. Cleaned up orphaned files (files without database entries)
5. Added better error handling and logging to the delete function
6. Created a cleanup utility script: `/var/www/kinky-thots/backend/cleanup-gallery.sh`

**Important:** All future uploads will automatically have special characters replaced with underscores, preventing this issue.

### Testing Delete Functionality
The delete button should now work correctly. If you encounter issues:
1. Open browser console (F12) to see detailed error messages
2. Check that the backend server is running on port 3001
3. Verify CORS is not blocking the request (should see detailed logs in console)

## Maintenance

### Cleanup Utility
Run the cleanup script to sync database and files:
```bash
cd /var/www/kinky-thots/backend
./cleanup-gallery.sh
```

This will:
- Find database entries without files
- Find files without database entries
- Prompt you to clean up inconsistencies

## Notes
- The server must be running for uploads/deletes to work
- Uploads are stored in `/var/www/kinky-thots/uploads/`
- Database records are stored in the `gallery_db.images` table
- Maximum file size: 1000MB (1GB)
- Supported formats: Images (JPEG, PNG, GIF, WebP) and Videos (MP4, MOV, AVI, MKV, WebM, MPEG, FLV)
- Delete operations remove both the file and database entry
- Console logging is enabled for debugging upload/delete operations
