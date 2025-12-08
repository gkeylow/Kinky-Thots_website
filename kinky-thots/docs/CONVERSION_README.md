# MOV to MP4 Conversion Guide

## Overview
This script converts all `.mov` and `.MOV` files in `/media/porn` to web-compatible MP4 format.

## Prerequisites

### Install ffmpeg
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install ffmpeg -y

# Verify installation
ffmpeg -version
```

## Usage

### Run the conversion script
```bash
cd /var/www/kinky-thots
./convert-mov-to-mp4.sh
```

### What the script does:
1. ✅ Checks if ffmpeg is installed
2. ✅ Scans `/media/porn` for all MOV files (including subdirectories)
3. ✅ Shows count and asks for confirmation
4. ✅ Creates backup directory `/media/porn/originals`
5. ✅ Converts each MOV to MP4 with web-optimized settings:
   - H.264 video codec (universally supported)
   - AAC audio codec
   - Fast-start enabled (streaming-ready)
   - CRF 23 quality (good balance of quality/size)
6. ✅ Moves original MOV files to backup directory
7. ✅ Provides detailed summary and logs

### Output
- **Converted MP4 files**: Same location as originals with `.mp4` extension
- **Original MOV files**: Moved to `/media/porn/originals/`
- **Conversion log**: `/var/www/kinky-thots/conversion.log`

## After Conversion

### Update porn.html
The script creates MP4 versions alongside the originals. You can either:

**Option 1**: Update video list to use `.mp4` extensions
```javascript
const videos = [
  { src: '2EBBFD93-4A9B-423C-A467-1219A6738673.mp4' }, // Changed from .mov
  { src: 'IMG_0391.mp4' }, // Changed from .mov
  // ... etc
];
```

**Option 2**: Use the auto-update script (see below)

### Auto-update porn.html script
```bash
# Replace .mov with .mp4 in the video list
sed -i 's/\.mov/\.mp4/gi' /var/www/kinky-thots/porn.html
```

## Manual Conversion (Single File)
If you need to convert a single file manually:
```bash
ffmpeg -i input.mov \
  -c:v libx264 \
  -crf 23 \
  -preset medium \
  -c:a aac \
  -b:a 128k \
  -movflags +faststart \
  output.mp4
```

## Troubleshooting

### Script says "ffmpeg not found"
Install ffmpeg first (see Prerequisites above)

### Permission denied
Make script executable:
```bash
chmod +x /var/www/kinky-thots/convert-mov-to-mp4.sh
```

### Conversion fails for specific files
Check the log file:
```bash
cat /var/www/kinky-thots/conversion.log
```

### Need to restore originals
Original files are in `/media/porn/originals/` - you can move them back if needed

## Configuration
Edit the script to customize settings:

```bash
VIDEO_QUALITY="23"    # Lower = better quality (18-28 recommended)
PRESET="medium"       # Speed vs compression (ultrafast to veryslow)
AUDIO_BITRATE="128k"  # Audio quality
```

## Estimated Time
Conversion time depends on:
- Video length and resolution
- Server CPU power
- Preset chosen (faster = quicker but larger files)

Rough estimate: **1-2 minutes per minute of video** with medium preset

## Storage Requirements
MP4 files are typically **smaller** than MOV files, but keep originals backed up until you verify all conversions worked correctly.
