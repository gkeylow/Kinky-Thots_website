const { S3Client, GetObjectCommand } = require('@aws-sdk/client-s3');
const { getSignedUrl } = require('@aws-sdk/s3-request-presigner');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Load credentials from config file (same as sonic-s3-client.js)
const configFile = path.join(__dirname, '../config/sonic-s3-cdn.json');
if (!fs.existsSync(configFile)) {
  console.error('ERROR: Config file not found:', configFile);
  console.error('Please ensure config/sonic-s3-cdn.json exists with S3 credentials');
  process.exit(1);
}

const config = JSON.parse(fs.readFileSync(configFile, 'utf8'));

const s3 = new S3Client({
  endpoint: config.s3.endpoint,
  region: config.s3.region,
  credentials: {
    accessKeyId: config.s3.access_key,
    secretAccessKey: config.s3.secret_key
  },
  forcePathStyle: config.settings.path_style
});

const BUCKET = config.s3.bucket;

async function getDuration(filename) {
  const command = new GetObjectCommand({ Bucket: BUCKET, Key: filename });
  const url = await getSignedUrl(s3, command, { expiresIn: 3600 });

  try {
    const duration = execSync(
      `ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "${url}"`,
      { timeout: 120000, encoding: 'utf8' }
    ).trim();
    return parseFloat(duration);
  } catch (e) {
    console.error(`  Error: ${e.message.substring(0, 50)}`);
    return null;
  }
}

async function main() {
  const manifest = JSON.parse(fs.readFileSync('/var/www/kinky-thots/data/video-manifest.json', 'utf8'));

  console.log(`Probing ${manifest.videos.length} videos...\n`);

  for (let i = 0; i < manifest.videos.length; i++) {
    const video = manifest.videos[i];
    const shortName = video.filename.length > 40 ? video.filename.substring(0, 37) + '...' : video.filename;
    process.stdout.write(`[${String(i+1).padStart(2)}/${manifest.videos.length}] ${shortName.padEnd(40)} `);

    const duration = await getDuration(video.filename);
    if (duration !== null) {
      video.duration_seconds = Math.round(duration);
      const mins = Math.floor(duration / 60);
      const secs = Math.round(duration % 60);
      console.log(`${mins}:${String(secs).padStart(2, '0')}`);
    } else {
      video.duration_seconds = 60;
      console.log('(default 1:00)');
    }
  }

  manifest.generated = new Date().toISOString();
  fs.writeFileSync('/var/www/kinky-thots/data/video-manifest.json', JSON.stringify(manifest, null, 2));

  const free = manifest.videos.filter(v => v.duration_seconds < 60);
  const basic = manifest.videos.filter(v => v.duration_seconds >= 60 && v.duration_seconds <= 300);
  const premium = manifest.videos.filter(v => v.duration_seconds > 300);

  console.log('\n=== Summary ===');
  console.log(`Free (<1min):    ${free.length} videos`);
  console.log(`Basic (1-5min):  ${basic.length} videos`);
  console.log(`Premium (>5min): ${premium.length} videos`);
}

main().catch(console.error);
