#!/bin/bash
# Generate thumbnails for all videos in the manifest
# Thumbnails are saved as JPG files with the same base name

VIDEO_DIR="/var/www/kinky-thots/porn/kinky-thots-shorts"
THUMB_DIR="/var/www/kinky-thots/assets/thumbnails"
THUMB_WIDTH=320

mkdir -p "$THUMB_DIR"

count=0
skipped=0

for video in "$VIDEO_DIR"/*.mp4; do
    if [ -f "$video" ]; then
        filename=$(basename "$video")
        # Replace .mp4 with .jpg for thumbnail
        thumb_name="${filename%.mp4}.jpg"
        thumb_path="$THUMB_DIR/$thumb_name"

        if [ -f "$thumb_path" ]; then
            echo "SKIP: $thumb_name (already exists)"
            ((skipped++))
            continue
        fi

        echo "Generating: $thumb_name"

        # Extract frame at 1 second (or first frame if video is shorter)
        ffmpeg -y -i "$video" -ss 00:00:01 -vframes 1 -vf "scale=$THUMB_WIDTH:-1" -q:v 3 "$thumb_path" 2>/dev/null

        # If that failed, try first frame
        if [ ! -f "$thumb_path" ]; then
            ffmpeg -y -i "$video" -vframes 1 -vf "scale=$THUMB_WIDTH:-1" -q:v 3 "$thumb_path" 2>/dev/null
        fi

        if [ -f "$thumb_path" ]; then
            ((count++))
        else
            echo "FAILED: $filename"
        fi
    fi
done

echo ""
echo "Done! Generated $count new thumbnails, skipped $skipped existing."
echo "Total thumbnails: $(ls -1 "$THUMB_DIR"/*.jpg 2>/dev/null | wc -l)"
