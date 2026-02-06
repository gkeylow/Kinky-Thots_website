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
 * Detect tier from CDN folder path
 * Videos should be organized in: .free/, .plus/, .premium/
 * @param {string} key - S3 object key (e.g., ".free/video.mp4" or ".plus/clip.mp4")
 * @returns {string} Tier name: 'free', 'plus', 'premium', or 'unassigned'
 */
function detectTierFromPath(key) {
  const normalizedKey = key.toLowerCase();
  if (normalizedKey.startsWith('.free/')) return 'free';
  if (normalizedKey.startsWith('.plus/')) return 'plus';
  if (normalizedKey.startsWith('.premium/')) return 'premium';
  // Videos not in a tier folder are unassigned
  return 'unassigned';
}

/**
 * Get just the filename from a path
 * @param {string} key - Full path like "free/video.mp4"
 * @returns {string} Just the filename like "video.mp4"
 */
function getFilename(key) {
  return path.basename(key);
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
        console.log('Folder-based tier detection:');
        console.log('  .free/    → Free tier (teasers)');
        console.log('  .plus/    → Plus tier ($8/mo)');
        console.log('  .premium/ → Premium tier ($15/mo)\n');

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

        // Build manifest with folder-based tier detection
        const videoData = [];
        const tierCounts = { free: 0, plus: 0, premium: 0, unassigned: 0 };

        for (let i = 0; i < videos.length; i++) {
          const obj = videos[i];
          const tier = detectTierFromPath(obj.key);
          const filename = getFilename(obj.key);

          tierCounts[tier]++;

          const tierIcon = {
            free: '🆓',
            plus: '➕',
            premium: '⭐',
            unassigned: '❓'
          }[tier];

          console.log(`${tierIcon} [${tier.padEnd(10)}] ${obj.key}`);

          videoData.push({
            filename: filename,
            path: obj.key,
            tier: tier,
            size_bytes: obj.size,
            on_cdn: true
          });
        }

        const manifest = {
          generated: new Date().toISOString(),
          cdn_base_url: client.getCdnBaseUrl(),
          tier_folders: ['.free', '.plus', '.premium'],
          videos: videoData
        };

        // Write manifest
        fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));

        console.log('\n' + '='.repeat(50));
        console.log('✓ Manifest saved to:', manifestPath);
        console.log('  CDN:', manifest.cdn_base_url);
        console.log('\nTier Summary:');
        console.log(`  🆓 Free:       ${tierCounts.free} videos`);
        console.log(`  ➕ Plus:       ${tierCounts.plus} videos`);
        console.log(`  ⭐ Premium:    ${tierCounts.premium} videos`);
        if (tierCounts.unassigned > 0) {
          console.log(`  ❓ Unassigned: ${tierCounts.unassigned} videos`);
          console.log('\n⚠️  Warning: Some videos are not in tier folders.');
          console.log('   Move them to .free/, .plus/, or .premium/ folders on CDN.');
        }
        console.log('='.repeat(50));

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
  sync-manifest             Sync video manifest from CDN (folder-based tiers)
  help                      Show this help message

Environment:
  Config file: ${path.join(__dirname, '../config/sonic-s3-cdn.json')}

Video Tier Folders:
  Organize videos on CDN into these folders for automatic tier assignment:
    .free/     → Free tier (teasers, open to all)
    .plus/     → Plus tier ($8/mo subscribers)
    .premium/  → Premium tier ($15/mo subscribers)

Examples:
  npm run sonic:test
  npm run sonic:sync-manifest
  npm run sonic:upload -- /tmp/video.mp4 .free/teaser.mp4
  npm run sonic:upload -- /tmp/video.mp4 .plus/extended.mp4
  npm run sonic:upload -- /tmp/video.mp4 .premium/full-video.mp4
  npm run sonic:list -- .free/
  npm run sonic:list -- .plus/
  npm run sonic:list -- .premium/
  npm run sonic:info -- .free/video.mp4
  npm run sonic:delete -- .free/video.mp4
`);
        break;
    }
  } catch (error) {
    console.error('Error:', error.message);
    process.exit(1);
  }
}

runCommand();
