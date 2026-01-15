const fs = require('fs');
const path = require('path');
const { S3Client, PutObjectCommand, GetObjectCommand, HeadObjectCommand, DeleteObjectCommand, ListObjectsV2Command } = require('@aws-sdk/client-s3');

/**
 * Sonic S3 CDN Client for Node.js
 * Provides S3-compatible API access to Sonic CDN for uploading and managing files
 */
class SonicS3Client {
  constructor(configPath = null, bucketType = 'videos') {
    // Load configuration
    const configFile = configPath || path.join(__dirname, '../config/sonic-s3-cdn.json');

    if (!fs.existsSync(configFile)) {
      throw new Error(`Configuration file not found: ${configFile}`);
    }

    const config = JSON.parse(fs.readFileSync(configFile, 'utf8'));
    this.config = config;
    this.bucketType = bucketType;

    // Get bucket-specific config (videos or images)
    const s3Config = config.s3[bucketType] || config.s3.videos;

    // Initialize S3 client
    this.s3Client = new S3Client({
      region: s3Config.region,
      endpoint: s3Config.endpoint,
      credentials: {
        accessKeyId: s3Config.access_key,
        secretAccessKey: s3Config.secret_key
      },
      forcePathStyle: config.settings.path_style,
      tls: config.settings.use_ssl
    });

    this.bucket = s3Config.bucket;
    this.cdnBaseUrl = `https://${s3Config.cdn_hostname}`;
  }

  /**
   * Upload a file to Sonic S3
   * @param {string} localPath - Path to local file
   * @param {string} remotePath - Remote path in S3 bucket (optional)
   * @returns {Promise<Object>} Upload result with CDN URL
   */
  async uploadFile(localPath, remotePath = null) {
    try {
      if (!fs.existsSync(localPath)) {
        throw new Error(`File not found: ${localPath}`);
      }

      const filename = path.basename(localPath);
      const key = remotePath ? remotePath.replace(/^\//, '') : filename;
      
      const fileContent = fs.readFileSync(localPath);
      const contentType = this.getMimeType(localPath);
      const fileSize = fs.statSync(localPath).size;

      console.log(`[Sonic S3] Uploading: ${filename} (${this.formatSize(fileSize)})`);
      console.log(`[Sonic S3] Remote path: ${key}`);

      const command = new PutObjectCommand({
        Bucket: this.bucket,
        Key: key,
        Body: fileContent,
        ContentType: contentType,
        Metadata: {
          'upload-date': new Date().toISOString(),
          'original-name': filename
        }
      });

      await this.s3Client.send(command);

      const cdnUrl = `${this.cdnBaseUrl}/${encodeURIComponent(key)}`;
      
      console.log(`[Sonic S3] ✓ Upload successful: ${cdnUrl}`);

      return {
        success: true,
        key: key,
        remote_path: key,
        cdn_url: cdnUrl,
        s3_endpoint: this.config.s3.endpoint,
        size: fileSize,
        content_type: contentType,
        bucket: this.bucket
      };
    } catch (error) {
      console.error(`[Sonic S3] ✗ Upload failed:`, error.message);
      return {
        success: false,
        error: error.message,
        details: error
      };
    }
  }

  /**
   * Upload file from stream/buffer (useful for processing)
   * @param {Buffer|Stream} content - File content
   * @param {string} key - S3 object key
   * @param {string} contentType - MIME type
   * @returns {Promise<Object>} Upload result
   */
  async uploadBuffer(content, key, contentType = 'application/octet-stream') {
    try {
      if (typeof content === 'string') {
        content = Buffer.from(content);
      }

      console.log(`[Sonic S3] Uploading buffer: ${key} (${this.formatSize(content.length)})`);

      const command = new PutObjectCommand({
        Bucket: this.bucket,
        Key: key.replace(/^\//, ''),
        Body: content,
        ContentType: contentType,
        Metadata: {
          'upload-date': new Date().toISOString()
        }
      });

      await this.s3Client.send(command);

      const cdnUrl = `${this.cdnBaseUrl}/${encodeURIComponent(key.replace(/^\//, ''))}`;
      
      console.log(`[Sonic S3] ✓ Buffer upload successful: ${cdnUrl}`);

      return {
        success: true,
        key: key.replace(/^\//, ''),
        cdn_url: cdnUrl,
        size: content.length,
        content_type: contentType
      };
    } catch (error) {
      console.error(`[Sonic S3] ✗ Buffer upload failed:`, error.message);
      return {
        success: false,
        error: error.message
      };
    }
  }

  /**
   * Delete a file from S3
   * @param {string} key - S3 object key
   * @returns {Promise<Object>} Deletion result
   */
  async deleteFile(key) {
    try {
      console.log(`[Sonic S3] Deleting: ${key}`);

      const command = new DeleteObjectCommand({
        Bucket: this.bucket,
        Key: key.replace(/^\//, '')
      });

      await this.s3Client.send(command);

      console.log(`[Sonic S3] ✓ Delete successful`);

      return {
        success: true,
        key: key.replace(/^\//, '')
      };
    } catch (error) {
      console.error(`[Sonic S3] ✗ Delete failed:`, error.message);
      return {
        success: false,
        error: error.message
      };
    }
  }

  /**
   * Get object metadata
   * @param {string} key - S3 object key
   * @returns {Promise<Object>} Object info
   */
  async getObjectInfo(key) {
    try {
      const command = new HeadObjectCommand({
        Bucket: this.bucket,
        Key: key.replace(/^\//, '')
      });

      const response = await this.s3Client.send(command);

      const cdnUrl = `${this.cdnBaseUrl}/${encodeURIComponent(key.replace(/^\//, ''))}`;

      return {
        success: true,
        key: key.replace(/^\//, ''),
        cdn_url: cdnUrl,
        size: response.ContentLength,
        size_formatted: this.formatSize(response.ContentLength),
        content_type: response.ContentType,
        last_modified: response.LastModified,
        etag: response.ETag
      };
    } catch (error) {
      console.error(`[Sonic S3] ✗ Get info failed:`, error.message);
      return {
        success: false,
        error: error.message
      };
    }
  }

  /**
   * List objects in bucket
   * @param {string} prefix - Optional prefix to filter objects
   * @param {number} maxKeys - Maximum number of keys to return
   * @returns {Promise<Object>} List of objects
   */
  async listObjects(prefix = '', maxKeys = 1000) {
    try {
      const command = new ListObjectsV2Command({
        Bucket: this.bucket,
        Prefix: prefix,
        MaxKeys: maxKeys
      });

      const response = await this.s3Client.send(command);

      const objects = (response.Contents || []).map(item => ({
        key: item.Key,
        size: item.Size,
        size_formatted: this.formatSize(item.Size),
        last_modified: item.LastModified,
        cdn_url: `${this.cdnBaseUrl}/${encodeURIComponent(item.Key)}`
      }));

      return {
        success: true,
        objects: objects,
        count: objects.length
      };
    } catch (error) {
      console.error(`[Sonic S3] ✗ List failed:`, error.message);
      return {
        success: false,
        error: error.message
      };
    }
  }

  /**
   * Test connection to Sonic S3
   * @returns {Promise<boolean>} Connection status
   */
  async testConnection() {
    try {
      console.log('[Sonic S3] Testing connection...');
      console.log(`[Sonic S3] Endpoint: ${this.config.s3.endpoint}`);
      console.log(`[Sonic S3] Bucket: ${this.bucket}`);
      console.log(`[Sonic S3] CDN URL: ${this.cdnBaseUrl}`);

      const result = await this.listObjects('', 1);

      if (result.success) {
        console.log('[Sonic S3] ✓ Connection successful!');
        console.log(`[Sonic S3] Objects in bucket: ${result.count}`);
        return true;
      } else {
        console.error(`[Sonic S3] ✗ Connection failed: ${result.error}`);
        return false;
      }
    } catch (error) {
      console.error(`[Sonic S3] ✗ Connection error: ${error.message}`);
      return false;
    }
  }

  /**
   * Get CDN URL for a key
   * @param {string} key - S3 object key
   * @returns {string} Full CDN URL
   */
  getCdnUrl(key) {
    return `${this.cdnBaseUrl}/${encodeURIComponent(key.replace(/^\//, ''))}`;
  }

  /**
   * Get CDN base URL
   * @returns {string} CDN base URL
   */
  getCdnBaseUrl() {
    return this.cdnBaseUrl;
  }

  /**
   * Helper: Get MIME type from file extension
   */
  getMimeType(filePath) {
    const ext = path.extname(filePath).toLowerCase();
    const mimeTypes = {
      '.jpg': 'image/jpeg',
      '.jpeg': 'image/jpeg',
      '.png': 'image/png',
      '.gif': 'image/gif',
      '.webp': 'image/webp',
      '.svg': 'image/svg+xml',
      '.mp4': 'video/mp4',
      '.mov': 'video/quicktime',
      '.avi': 'video/x-msvideo',
      '.mkv': 'video/x-matroska',
      '.webm': 'video/webm',
      '.pdf': 'application/pdf',
      '.json': 'application/json',
      '.html': 'text/html',
      '.css': 'text/css',
      '.js': 'application/javascript'
    };
    return mimeTypes[ext] || 'application/octet-stream';
  }

  /**
   * Helper: Format bytes to human-readable size
   */
  formatSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
      size /= 1024;
      unitIndex++;
    }

    return `${size.toFixed(2)} ${units[unitIndex]}`;
  }
}

module.exports = SonicS3Client;
