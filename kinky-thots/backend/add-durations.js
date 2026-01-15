const fs = require('fs');
const { execSync } = require('child_process');
const { S3Client, GetObjectCommand } = require('@aws-sdk/client-s3');
const { getSignedUrl } = require('@aws-sdk/s3-request-presigner');

const s3 = new S3Client({
  endpoint: 'https://s3.eu-central.r-cdn.com',
  region: 'eu-central',
  credentials: {
    accessKeyId: 'Z1Z2BU5WTNB6S28P6OW4M',
    secretAccessKey: 'TrwzRLw1U8NPS0g3hDKWNkxBw7ZSw8NYRcNZNFQ1'
  },
  forcePathStyle: true
});

async function getVideoDuration(filename) {
  try {
    const command = new GetObjectCommand({ Bucket: '6318', Key: filename });
    const url = await getSignedUrl(s3, command, { expiresIn: 3600 });
    
    const duration = execSync(
      `ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "${url}"`,
      { timeout: 60000, encoding: 'utf8' }
    ).trim();
    
    return Math.round(parseFloat(duration));
  } catch (e) {
    console.error(`Error getting duration for ${filename}:`, e.message);
    return null;
  }
}

async function main() {
  const manifestPath = 'data/video-manifest.json';
  const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
  
  console.log(`Processing ${manifest.videos.length} videos...`);
  
  for (let i = 0; i < manifest.videos.length; i++) {
    const video = manifest.videos[i];
    
    if (video.duration_seconds) {
      console.log(`${i+1}/${manifest.videos.length}: ${video.filename} - already has duration (${video.duration_seconds}s)`);
      continue;
    }
    
    console.log(`${i+1}/${manifest.videos.length}: ${video.filename} - fetching duration...`);
    const duration = await getVideoDuration(video.filename);
    
    if (duration) {
      video.duration_seconds = duration;
      console.log(`  ✓ Duration: ${duration}s`);
    } else {
      console.log(`  ✗ Failed to get duration`);
    }
    
    // Save after each video to preserve progress
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
  }
  
  console.log('\nDone!');
}

main().catch(console.error);
