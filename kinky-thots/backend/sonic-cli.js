#!/usr/bin/env node

/**
 * Sonic S3 CDN CLI Tool
 * Test and manage uploads to Sonic S3 CDN
 */

const SonicS3Client = require('./sonic-s3-client');
const fs = require('fs');
const path = require('path');

const client = new SonicS3Client();
const command = process.argv[2];

async function runCommand() {
  try {
    switch (command) {
      case 'test':
        console.log('\n🧪 Testing Sonic S3 Connection...\n');
        const connected = await client.testConnection();
        process.exit(connected ? 0 : 1);
        break;

      case 'upload':
        if (!process.argv[3]) {
          console.error('Usage: npm run sonic:upload <file> [remote_path]');
          process.exit(1);
        }
        console.log('\n📤 Uploading to Sonic S3...\n');
        const localPath = process.argv[3];
        const remotePath = process.argv[4];
        const uploadResult = await client.uploadFile(localPath, remotePath);
        console.log(JSON.stringify(uploadResult, null, 2));
        process.exit(uploadResult.success ? 0 : 1);
        break;

      case 'list':
        console.log('\n📋 Listing objects in Sonic S3...\n');
        const prefix = process.argv[3] || '';
        const listResult = await client.listObjects(prefix, 50);
        if (listResult.success) {
          console.log(`Total: ${listResult.count} object(s)\n`);
          listResult.objects.forEach(obj => {
            console.log(`  📄 ${obj.key}`);
            console.log(`     Size: ${obj.size_formatted}`);
            console.log(`     Modified: ${obj.last_modified}`);
            console.log(`     CDN: ${obj.cdn_url}\n`);
          });
        } else {
          console.error('Error:', listResult.error);
        }
        process.exit(listResult.success ? 0 : 1);
        break;

      case 'info':
        if (!process.argv[3]) {
          console.error('Usage: npm run sonic:info <key>');
          process.exit(1);
        }
        console.log('\n📊 Getting object info...\n');
        const key = process.argv[3];
        const infoResult = await client.getObjectInfo(key);
        if (infoResult.success) {
          console.log(`Key: ${infoResult.key}`);
          console.log(`Size: ${infoResult.size_formatted}`);
          console.log(`Type: ${infoResult.content_type}`);
          console.log(`Modified: ${infoResult.last_modified}`);
          console.log(`ETag: ${infoResult.etag}`);
          console.log(`CDN URL: ${infoResult.cdn_url}`);
        } else {
          console.error('Error:', infoResult.error);
        }
        process.exit(infoResult.success ? 0 : 1);
        break;

      case 'delete':
        if (!process.argv[3]) {
          console.error('Usage: npm run sonic:delete <key>');
          process.exit(1);
        }
        console.log('\n🗑️  Deleting from Sonic S3...\n');
        const delKey = process.argv[3];
        const delResult = await client.deleteFile(delKey);
        console.log(delResult.success ? `✓ Deleted: ${delKey}` : `✗ Error: ${delResult.error}`);
        process.exit(delResult.success ? 0 : 1);
        break;

      case 'help':
      default:
        console.log(`
Sonic S3 CDN CLI Tool
====================

Commands:
  test                      Test connection to Sonic S3
  upload <file> [remote]    Upload a file to Sonic S3
  list [prefix]             List objects in bucket (optional prefix filter)
  info <key>                Get object information
  delete <key>              Delete an object
  help                      Show this help message

Environment:
  Config file: ${path.join(__dirname, '../config/sonic-s3-cdn.json')}

Examples:
  npm run sonic:test
  npm run sonic:upload -- /tmp/video.mp4
  npm run sonic:upload -- /tmp/video.mp4 videos/myfile.mp4
  npm run sonic:list -- images/
  npm run sonic:info -- images/photo.jpg
  npm run sonic:delete -- images/photo.jpg

For use in upload workflows:
  - Files are organized as: <type>s/<filename> (e.g., images/photo.jpg, videos/clip.mp4)
  - CDN URLs are automatically generated
  - Responses include both S3 and CDN URLs
`);
        break;
    }
  } catch (error) {
    console.error('Error:', error.message);
    process.exit(1);
  }
}

runCommand();
