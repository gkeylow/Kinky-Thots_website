# Sonic S3 CDN Integration

## Overview

Kinky Thots now integrates with **Sonic S3 CDN** (via PushrCDN's S3-compatible storage) for enhanced media distribution. This provides:

- **S3-compatible API** for reliable file uploads
- **Direct CDN delivery** from edge servers worldwide
- **Scalable storage** with unlimited requests
- **Automatic organization** by media type (images, videos)
- **Multipart upload support** for large files (100MB+)

## Configuration

### Credentials

Located in `config/sonic-s3-cdn.json`:

```json
{
  "s3": {
    "endpoint": "https://s3.eu-central.r-cdn.com",
    "access_key": "L81UC90ZDB5TMEAY7PUUU",
    "secret_key": "QU9DNUI5VzVGN05FQ01IpEzDVjZEUkZURcNGSjFR",
    "bucket": "6317",
    "region": "eu-central"
  },
  "cdn": {
    "hostname": "6317.s3.de01.sonic.r-cdn.com",
    "base_url": "https://6317.s3.de01.sonic.r-cdn.com"
  }
}
```

### Settings

- **Path Style**: Enabled (URLs use path-based S3 access)
- **SSL/TLS**: Enabled
- **Max Upload Size**: 5000 MB (5 GB)
- **Supported Media Types**:
  - Images: jpg, jpeg, png, gif, webp, bmp, tiff, svg
  - Videos: mp4, mov, avi, mkv, webm, mpeg, flv, m4v

## Node.js Integration

### API Usage

```javascript
const SonicS3Client = require('./sonic-s3-client');

const client = new SonicS3Client();

// Upload file
const result = await client.uploadFile('/path/to/file.mp4', 'videos/myfile.mp4');
if (result.success) {
  console.log('CDN URL:', result.cdn_url);
}

// Upload buffer
const buffer = Buffer.from('content');
await client.uploadBuffer(buffer, 'files/data.bin', 'application/octet-stream');

// Get object info
const info = await client.getObjectInfo('videos/myfile.mp4');
console.log('Size:', info.size_formatted);

// List objects
const list = await client.listObjects('videos/', 50);
console.log('Objects:', list.count);

// Delete object
await client.deleteFile('videos/myfile.mp4');

// Test connection
const connected = await client.testConnection();
```

### CLI Commands

```bash
# Test connection
npm run sonic:test

# Upload file
npm run sonic:upload -- /tmp/video.mp4
npm run sonic:upload -- /tmp/video.mp4 videos/custom-name.mp4

# List objects
npm run sonic:list -- images/
npm run sonic:list

# Get object info
npm run sonic:info -- images/photo.jpg

# Delete object
npm run sonic:delete -- images/photo.jpg
```

### In Upload Workflow

When a file is uploaded via the API (`/api/upload`):

1. File is saved locally (for responsive images, etc.)
2. Database record is created
3. **Background**: Uploaded to Sonic S3 CDN
4. **Background**: CDN URL is logged to console

Files are automatically organized:
```
images/<filename>   # For image uploads
videos/<filename>   # For video uploads
```

Example upload response:
```json
{
  "success": true,
  "id": 123,
  "filename": "1765265654758_photo.jpg",
  "file_type": "image",
  "sonic_cdn_url": "https://6317.s3.de01.sonic.r-cdn.com/images/1765265654758_photo.jpg"
}
```

## API Endpoints Supported by Sonic S3

### Object Operations
- ✅ **PutObject** - Upload files
- ✅ **GetObject** - Download files (rate-limited from S3 endpoint)
- ✅ **HeadObject** - Get object metadata
- ✅ **CopyObject** - Server-side copy
- ✅ **DeleteObject** - Delete single or multiple objects
- ✅ **ListObjectsV2** - List with pagination
- ✅ **ListObjectsV1** - Legacy listing

### Object Tagging
- ✅ **GetObjectTagging**
- ✅ **PutObjectTagging**
- ✅ **DeleteObjectTagging**

### Multipart Upload
- ✅ **NewMultipartUpload** - Initiate for large files
- ✅ **UploadPart** - Upload 10MB+ chunks in parallel
- ✅ **CompleteMultipartUpload** - Finalize upload
- ✅ **AbortMultipartUpload** - Cancel in-progress upload
- ✅ **ListMultipartUploads**
- ✅ **ListObjectParts**

### Special Features
- ✅ **Byte Range Requests** - Request partial file content
- ✅ **Folder Deletion** - DeleteObject can remove folders

### NOT Supported
- ❌ Bucket policies (default mixed access policy applies)
- ❌ Block Public Access settings

## Egress & Performance

### Free Egress Policy
- **Download from S3 endpoint**: Limited speed for cost management
  - Use only for backup/restore operations
  - Example: `https://s3.eu-central.r-cdn.com/6317/images/photo.jpg`

- **Download from CDN hostname**: Full speed, **no egress charges**
  - All user downloads should use CDN URLs
  - Example: `https://6317.s3.de01.sonic.r-cdn.com/images/photo.jpg`

### API Rates
- **No request limits** - Unlimited API calls
- **Concurrent connections**: Limited from S3 endpoint, unlimited from CDN
- **Bandwidth**: Full CDN speeds from edge servers

## Architecture Decisions

### Why Sonic S3 for Images & Videos?

1. **Scalability**: Unlimited storage and requests
2. **Redundancy**: Geographically distributed edge servers
3. **Cost**: Pay only for bandwidth to users (CDN), not for uploads
4. **Reliability**: Built on PushrCDN infrastructure

### Dual CDN Strategy

| Content | Primary | Secondary | Strategy |
|---------|---------|-----------|----------|
| **Images** | Sonic S3 | PushrCDN | Upload to both for redundancy |
| **Videos** | Sonic S3 (S3 endpoint) | - | CDN pull-zone caches on first request |
| **Responsive** | Local + Sonic S3 | - | Generated locally, synced to S3 |

### Local Storage Still Needed?

- ✅ **Images**: Keep locally for responsive image generation
- ✅ **Videos**: Keep locally or rely on CDN (configurable)
- ✅ **Database**: Track all files with S3 keys

Future enhancement: Auto-delete local files after confirmed upload and CDN cache.

## Monitoring & Debugging

### Check Upload Status
```bash
# List recent uploads
npm run sonic:list -- images/

# Check specific file
npm run sonic:info -- images/1765265654758_photo.jpg
```

### Server Logs
When running backend (`node server.js`), check console output:

```
[Sonic S3] Uploading: photo.jpg (2.5 MB)
[Sonic S3] Remote path: images/1765265654758_photo.jpg
[Sonic S3] ✓ Upload successful: https://6317.s3.de01.sonic.r-cdn.com/images/1765265654758_photo.jpg
```

### Test Connectivity
```bash
npm run sonic:test
# Output:
# [Sonic S3] Testing connection...
# [Sonic S3] Endpoint: https://s3.eu-central.r-cdn.com
# [Sonic S3] Bucket: 6317
# [Sonic S3] CDN URL: https://6317.s3.de01.sonic.r-cdn.com
# [Sonic S3] ✓ Connection successful!
# [Sonic S3] Objects in bucket: 42
```

## Troubleshooting

### Connection Fails
- Verify credentials in `config/sonic-s3-cdn.json`
- Check endpoint URL is correct
- Test with: `npm run sonic:test`

### Upload Errors
- File not found? Check local path exists
- Permission denied? Run as appropriate user
- Network timeout? File may be too large or network slow
- Large files use multipart upload automatically

### Files Not in CDN
- New files appear in S3 immediately
- CDN caches them on first request from users
- Use CDN URL, not S3 endpoint URL for distribution

### Listing Shows Empty
- Files may be in different prefix
- Try: `npm run sonic:list` (no prefix)
- Check bucket name is correct in config

## Development Setup

### Install Dependencies
```bash
cd backend
npm install
```

### Initialize Client
```javascript
// In your code:
const SonicS3Client = require('./sonic-s3-client');
const client = new SonicS3Client(); // Uses config/sonic-s3-cdn.json

// Or with custom config:
const client = new SonicS3Client('/path/to/custom-config.json');
```

### Use in Express Middleware

```javascript
const SonicS3Client = require('./sonic-s3-client');
let sonicClient = null;

try {
  sonicClient = new SonicS3Client();
} catch (err) {
  console.warn('Sonic S3 unavailable:', err.message);
}

// In upload endpoint:
if (sonicClient) {
  uploadToSonicS3(filePath, filename, fileType)
    .then(result => console.log('S3 upload done:', result.cdn_url));
}
```

## Future Enhancements

- [ ] Enable lifecycle policies to auto-delete old local files after CDN confirmation
- [ ] Add S3 event notifications for upload tracking
- [ ] Implement bandwidth analytics dashboard
- [ ] Add CloudFront-style cache invalidation
- [ ] Multi-region replication for disaster recovery
- [ ] Implement object versioning for media library

## References

- **Sonic S3 Docs**: https://www.pushrcdn.com/
- **AWS SDK for JavaScript**: https://docs.aws.amazon.com/sdk-for-javascript/
- **S3 API Reference**: https://docs.aws.amazon.com/s3/latest/API/Welcome.html
- **PushrCDN**: https://www.pushrcdn.com/

## Support

For issues or questions:
1. Check server logs for error messages
2. Run `npm run sonic:test` to verify connectivity
3. Review this documentation for troubleshooting steps
4. Check config file credentials are correct
