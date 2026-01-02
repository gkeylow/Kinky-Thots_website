# 🗜️ Video Compression System

## Overview
Automatically compress large videos to web-optimized sizes while maintaining quality.

**Target:** Reduce 300-900MB videos to 100-200MB without visible quality loss.

---

## 📋 Quick Start

### Compress All Large Videos
```bash
cd /var/www/kinky-thots
./compress-videos.sh
```

The script will:
1. Find all videos larger than 200MB
2. Show compression settings
3. Ask for confirmation
4. Compress each video
5. Backup originals to `/media/porn/originals/`
6. Replace with compressed versions

---

## ⚙️ Compression Settings

### Current Configuration
```bash
CRF: 23                    # Quality (18-28, lower=better)
Preset: medium             # Speed vs size balance
Max Resolution: 1920px     # 1080p max
Audio: 128k AAC           # Good quality audio
Min Size: 200MB           # Only compress videos > 200MB
```

### Quality Levels (CRF)
- **18-20:** Near lossless (larger files)
- **23:** Recommended (good balance) ⭐
- **26-28:** Smaller files (slight quality loss)

### Encoding Presets
- **ultrafast:** Fastest, largest files
- **fast:** Quick, good size
- **medium:** Balanced (recommended) ⭐
- **slow:** Better compression, slower
- **veryslow:** Best compression, very slow

---

## 📊 Expected Results

### Example Compressions
```
Original: 680MB → Compressed: 120MB (82% savings)
Original: 897MB → Compressed: 150MB (83% savings)
Original: 580MB → Compressed: 110MB (81% savings)
```

### Typical Savings
- **HD 1080p:** 70-85% size reduction
- **720p:** 60-75% size reduction
- **Quality:** Visually identical to original

---

## �� Customization

### Change Quality Settings
Edit `/var/www/kinky-thots/compress-videos.sh`:

```bash
# For better quality (larger files)
CRF="20"
PRESET="slow"

# For smaller files (slight quality loss)
CRF="26"
PRESET="fast"

# For maximum compression
CRF="28"
PRESET="veryslow"
```

### Change Size Threshold
```bash
# Only compress videos larger than 500MB
MIN_SIZE_MB="500"

# Compress all videos
MIN_SIZE_MB="0"
```

### Change Target Resolution
```bash
# 720p max
MAX_WIDTH="1280"

# 4K max
MAX_WIDTH="3840"
```

---

## 📁 Directory Structure

```
/media/porn/
├── video1.mp4              (compressed version)
├── video2.mp4              (compressed version)
├── originals/              (backup of originals)
│   ├── video1.mp4          (original 680MB)
│   └── video2.mp4          (original 897MB)
└── compressed/             (temporary working directory)
```

---

## 🚀 Usage Examples

### Compress All Videos
```bash
./compress-videos.sh
```

### Compress Single Video Manually
```bash
ffmpeg -i input.mp4 \
  -c:v libx264 -crf 23 -preset medium \
  -vf "scale='min(1920,iw)':'min(1920*ih/iw,ih)'" \
  -c:a aac -b:a 128k \
  -movflags +faststart \
  output.mp4
```

### Check Video Size Before/After
```bash
ls -lh /media/porn/*.mp4
ls -lh /media/porn/originals/*.mp4
```

---

## 🔍 Troubleshooting

### Compression Failed
Check the log:
```bash
cat /var/www/kinky-thots/compression.log
```

Common issues:
- Corrupted video file
- Insufficient disk space
- Unsupported codec

### Quality Not Good Enough
Lower the CRF value:
```bash
CRF="20"  # Better quality
```

### Files Still Too Large
Increase CRF or change preset:
```bash
CRF="26"
PRESET="slow"
```

### Restore Original
```bash
# Copy from backup
cp /media/porn/originals/video.mp4 /media/porn/video.mp4
```

---

## 💾 Disk Space Management

### Check Space Usage
```bash
# Current videos
du -sh /media/porn

# Backups
du -sh /media/porn/originals

# Total
du -sh /media/porn/*
```

### Delete Backups (After Testing)
```bash
# WARNING: This deletes original files permanently!
rm -rf /media/porn/originals/*
```

### Keep Backups for Important Videos
```bash
# Move specific originals to safe location
mv /media/porn/originals/important.mp4 /backup/location/
```

---

## 🎯 Best Practices

### 1. Test First
Compress 1-2 videos and check quality before doing all.

### 2. Keep Backups Initially
Don't delete originals until you've verified compressed versions.

### 3. Check on Multiple Devices
Test playback on desktop, mobile, and different browsers.

### 4. Monitor Bandwidth
Track bandwidth usage before/after compression.

### 5. Regenerate Thumbnails
After compression, regenerate thumbnails if needed:
```bash
./generate-thumbnails.sh
```

---

## 📈 Performance Impact

### Bandwidth Savings
**Before:**
- 30 videos × 400MB avg = 12GB total
- 1000 views/day × 400MB = 400GB/day bandwidth

**After:**
- 30 videos × 120MB avg = 3.6GB total (70% savings)
- 1000 views/day × 120MB = 120GB/day bandwidth (70% savings)

### Page Load Speed
- Faster buffering
- Better mobile experience
- Lower data usage for users

### Storage Savings
- 70-85% disk space saved
- More videos can be stored
- Lower hosting costs

---

## �� Automation

### Auto-Compress New Videos
Add to cron job:
```bash
# Run daily at 3 AM
0 3 * * * /var/www/kinky-thots/compress-videos.sh -y >> /var/log/compression.log 2>&1
```

### Compress on Upload
Add to upload script:
```bash
# After video upload
ffmpeg -i "$uploaded_file" \
  -c:v libx264 -crf 23 -preset medium \
  -c:a aac -b:a 128k \
  -movflags +faststart \
  "$compressed_file"
```

---

## 📝 Technical Details

### What the Script Does
1. Scans `/media/porn` for videos > 200MB
2. Re-encodes with H.264 (libx264) codec
3. Uses CRF 23 for quality control
4. Limits resolution to 1920px width
5. Converts audio to 128k AAC
6. Adds faststart flag for web streaming
7. Backs up originals
8. Replaces with compressed versions

### Why These Settings?
- **H.264:** Best browser compatibility
- **CRF 23:** Sweet spot for quality/size
- **AAC 128k:** Good audio, small size
- **Faststart:** Enables progressive download
- **1920px:** 1080p is sufficient for web

---

## ⚠️ Important Notes

1. **Backup First:** Always keep originals until verified
2. **Test Quality:** Check compressed videos before deleting originals
3. **Disk Space:** Ensure enough space for originals + compressed
4. **Time:** Compression takes time (5-10 min per video)
5. **CPU Usage:** High CPU usage during compression

---

## 🎬 Next Steps After Compression

1. ✅ Test videos on content pages
2. ✅ Check quality on mobile devices
3. ✅ Regenerate thumbnails if needed
4. ✅ Monitor bandwidth usage
5. ✅ Delete backups after 1-2 weeks if satisfied

---

## 📞 Support

If compression fails or quality is poor:
1. Check compression.log for errors
2. Try different CRF values (20-26)
3. Test different presets
4. Ensure ffmpeg is up to date

**Current Setup:**
- Script: `/var/www/kinky-thots/compress-videos.sh`
- Log: `/var/www/kinky-thots/compression.log`
- Backups: `/media/porn/originals/`
