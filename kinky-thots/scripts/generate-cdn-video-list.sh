#!/bin/bash
# Generate a list of all videos and their CDN URLs

LOCAL_DIR="/media/porn/kinky-thots-shorts"
CDN_BASE="https://c5988z6295.r-cdn.com"
OUTPUT_JSON="/var/www/kinky-thots/cdn-video-list.json"
OUTPUT_TXT="/var/www/kinky-thots/cdn-video-list.txt"

echo "Scanning local videos in: $LOCAL_DIR"
echo ""

# Clear output files
echo "[" > "$OUTPUT_JSON"
> "$OUTPUT_TXT"

# Counter
count=0
total=$(find "$LOCAL_DIR" -name "*.mp4" 2>/dev/null | wc -l)

echo "Found $total videos"
echo ""

# Find all MP4 files
first=true
find "$LOCAL_DIR" -name "*.mp4" -type f 2>/dev/null | sort | while read filepath; do
    filename=$(basename "$filepath")
    cdn_url="$CDN_BASE/$filename"
    local_path="$filepath"
    filesize=$(stat -c%s "$filepath" 2>/dev/null || stat -f%z "$filepath" 2>/dev/null)
    filesize_mb=$(awk "BEGIN {printf \"%.2f\", $filesize / 1024 / 1024}")
    
    count=$((count + 1))
    
    # Add comma if not first item
    if [ "$first" = false ]; then
        echo "," >> "$OUTPUT_JSON"
    fi
    first=false
    
    # Write JSON object (without trailing comma)
    printf '  {\n    "filename": "%s",\n    "cdn_url": "%s",\n    "local_path": "%s",\n    "size_mb": %s\n  }' \
        "$filename" "$cdn_url" "$local_path" "$filesize_mb" >> "$OUTPUT_JSON"
    
    # Write to text file
    echo "$cdn_url" >> "$OUTPUT_TXT"
    
    echo "[$count/$total] $filename"
done

# Close JSON array
echo "" >> "$OUTPUT_JSON"
echo "]" >> "$OUTPUT_JSON"

echo ""
echo "======================================"
echo "  Complete!"
echo "======================================"
echo ""
echo "JSON list: $OUTPUT_JSON"
echo "Text list: $OUTPUT_TXT"
echo ""
echo "Total videos: $total"
echo ""
echo "First 5 CDN URLs:"
head -5 "$OUTPUT_TXT"
