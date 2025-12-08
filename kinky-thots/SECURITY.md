# Security Configuration

## Directory Access Protection

All media directories are secured to prevent directory listing while allowing individual file access.

### Protected Directories

| Directory | Status | Access |
|-----------|--------|--------|
| `/uploads/` | 🔒 403 Forbidden | Files only |
| `/uploads/optimized/` | 🔒 403 Forbidden | Files only |
| `/porn/` | 🔒 403 Forbidden | Files only |
| `/porn/kinky-thots-shorts/` | 🔒 403 Forbidden | Files only |
| `/porn/kinky-thots-animated-gifs/` | 🔒 403 Forbidden | Files only |

### What's Protected

✅ **Directory Listing Disabled** - Visitors cannot browse directories  
✅ **Hidden Files Protected** - `.htaccess`, `.git`, etc. are blocked  
✅ **Script Execution Prevented** - PHP, Python, shell scripts cannot run  
✅ **Only Media Files Allowed** - Only images and videos are accessible  
✅ **Security Headers** - X-Content-Type-Options, X-Frame-Options set

### Apache Configuration

Located in `/etc/apache2/sites-available/kinky-thots.conf`:

```apache
<Directory /var/www/kinky-thots/uploads>
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
```

The `-Indexes` option prevents directory listing.

### .htaccess Protection

Each media directory contains an `.htaccess` file with:

```apache
# Prevent directory listing
Options -Indexes

# Deny access to hidden files
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

# Only allow media files
<FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|mov|avi|mkv|webm|mpeg|flv)$">
    Require all granted
</FilesMatch>

# Prevent execution of scripts
<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$">
    Require all denied
</FilesMatch>

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
```

### Testing Security

#### Test Directory Listing (Should Return 403)
```bash
curl -I http://kinky-thots.com/uploads/
curl -I http://kinky-thots.com/porn/
curl -I http://kinky-thots.com/porn/kinky-thots-shorts/
```

Expected: `HTTP/1.1 403 Forbidden`

#### Test File Access (Should Return 200)
```bash
curl -I http://kinky-thots.com/uploads/filename.jpg
curl -I http://kinky-thots.com/porn/kinky-thots-shorts/video.mp4
```

Expected: `HTTP/1.1 200 OK`

#### Test Script Execution (Should Return 403)
```bash
curl -I http://kinky-thots.com/uploads/test.php
```

Expected: `HTTP/1.1 403 Forbidden`

### Additional Security Measures

#### 1. File Upload Validation
Backend validates:
- File type (images and videos only)
- File size (max 1GB)
- Filename sanitization (removes special characters)

#### 2. Database Security
- Prepared statements prevent SQL injection
- User input sanitized
- Connection pooling with limits

#### 3. API Security
- CORS configured
- Rate limiting (via Apache)
- Input validation

#### 4. File Permissions
```bash
# Uploads directory
chmod 755 /var/www/kinky-thots/uploads
chmod 644 /var/www/kinky-thots/uploads/*

# Media directory
chmod 755 /media/porn
chmod 644 /media/porn/**/*
```

### Security Headers

All responses include:
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-Frame-Options: SAMEORIGIN` - Prevents clickjacking
- `Access-Control-Allow-Origin: *` - CORS for API access

### Monitoring

Check for unauthorized access attempts:

```bash
# Check Apache access logs
tail -f /var/log/apache2/access.log | grep "403"

# Check for suspicious uploads
find /var/www/kinky-thots/uploads -name "*.php" -o -name "*.sh"

# Check file permissions
find /var/www/kinky-thots/uploads -type f ! -perm 644
```

### Incident Response

If unauthorized files are found:

```bash
# Remove suspicious files
find /var/www/kinky-thots/uploads -name "*.php" -delete

# Reset permissions
chmod 755 /var/www/kinky-thots/uploads
chmod 644 /var/www/kinky-thots/uploads/*

# Check for backdoors
grep -r "eval\|base64_decode\|system\|exec" /var/www/kinky-thots/uploads/
```

### Maintenance

#### Regular Security Checks
```bash
# Run security audit
bash /var/www/kinky-thots/scripts/security-audit.sh
```

#### Update .htaccess
If you need to allow additional file types:

1. Edit `/var/www/kinky-thots/uploads/.htaccess`
2. Add extension to FilesMatch regex
3. Test with curl

### Best Practices

✅ Never store sensitive data in uploads directory  
✅ Regularly audit uploaded files  
✅ Keep Apache and PHP updated  
✅ Monitor access logs for suspicious activity  
✅ Use HTTPS for all traffic (currently via localtonet.com)  
✅ Backup regularly  
✅ Test security after configuration changes

### Compliance

- **GDPR**: EXIF data stripped from images
- **Privacy**: No directory listing exposes user data
- **Security**: Industry-standard protections in place

## Summary

✅ **Directory listing**: BLOCKED  
✅ **Script execution**: BLOCKED  
✅ **Hidden files**: BLOCKED  
✅ **Media files**: ALLOWED  
✅ **Security headers**: ENABLED  
✅ **File validation**: ENABLED  

All upload directories return **403 Forbidden** when accessed directly, but individual media files are accessible via direct URL.
