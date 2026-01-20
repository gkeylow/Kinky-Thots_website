#!/usr/bin/env node

/**
 * Sonic S3 CDN CLI Tool
 * Test and manage uploads to Sonic S3 CDN
 */

const SonicS3Client = require('./sonic-s3-client');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const client = new SonicS3Client();

/**
 * Get video duration using ffprobe
 * @param {string} url - CDN URL of the video
 * @returns {number|null} Duration in seconds, or null on error
 */
function getVideoDuration(url) {
  try {
    const result = execSync(
      `ffprobe -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "${url}"`,
      { timeout: 30000, encoding: 'utf8' }
    );
    const duration = parseFloat(result.trim());
    return isNaN(duration) ? null : Math.round(duration);
  } catch (error) {
    console.error(`  ⚠ Could not probe duration: ${error.message}`);
    return null;
  }
}
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

      case 'sync-manifest':
        console.log('\n🔄 Syncing video manifest from CDN...\n');
        const manifestPath = path.join(__dirname, '../data/video-manifest.json');

        // List all objects (increase limit to get all)
        const syncResult = await client.listObjects('', 1000);
        if (!syncResult.success) {
          console.error('Error listing objects:', syncResult.error);
          process.exit(1);
        }

        // Filter for video files only
        const videoExtensions = ['.mp4', '.webm', '.mkv', '.mov', '.avi'];
        const videos = syncResult.objects.filter(obj => {
          const ext = path.extname(obj.key).toLowerCase();
          return videoExtensions.includes(ext);
        });

        console.log(`Found ${videos.length} video(s) on CDN\n`);

        // Build manifest with duration probing
        console.log('Probing video durations (this may take a while)...\n');
        const videoData = [];

        for (let i = 0; i < videos.length; i++) {
          const obj = videos[i];
          const cdnUrl = obj.cdn_url;
          console.log(`[${i + 1}/${videos.length}] ${obj.key}`);

          const duration = getVideoDuration(cdnUrl);
          if (duration !== null) {
            console.log(`  ✓ Duration: ${Math.floor(duration / 60)}:${(duration % 60).toString().padStart(2, '0')}`);
          } else {
            console.log(`  ⚠ Duration: unknown (defaulting to 60s)`);
          }

          videoData.push({
            filename: obj.key,
            duration_seconds: duration || 60,
            width: null,
            height: null,
            size_bytes: obj.size,
            on_cdn: true
          });
        }

        const manifest = {
          generated: new Date().toISOString(),
          cdn_base_url: client.getCdnBaseUrl(),
          videos: videoData
        };

        // Write manifest
        fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
        console.log(`✓ Manifest saved to: ${manifestPath}`);
        console.log(`  Videos: ${manifest.videos.length}`);
        console.log(`  CDN: ${manifest.cdn_base_url}`);

        // List videos
        console.log('\nVideos in manifest:');
        manifest.videos.forEach((v, i) => {
          console.log(`  ${i + 1}. ${v.filename}`);
        });

        process.exit(0);
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
