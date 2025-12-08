#!/bin/bash
# Upload videos from /media/porn to PushrCDN storage zone
# This saves local storage by hosting videos on the CDN

API_KEY="REDACTED_PUSHR_API_KEY"
API_URL="https://www.pushrcdn.com/api/v3"
ZONE_ID="6295"  # my-videos storage zone
SOURCE_DIR="/media/porn"
LOG_FILE="/var/www/kinky-thots/logs/cdn-upload.log"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Ensure log directory exists
mkdir -p "$(dirname "$LOG_FILE")"

echo "======================================"
echo "  PushrCDN Video Upload"
echo "======================================"
echo ""
echo "Source: $SOURCE_DIR"
echo "Zone ID: $ZONE_ID"
echo "Log: $LOG_FILE"
echo ""

# Function to upload a file
upload_file() {
    local file="$1"
    local filename=$(basename "$file")
    local filesize=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
    local filesize_mb=$(echo "scale=2; $filesize / 1024 / 1024" | bc)
    
    echo -e "${BLUE}Uploading:${NC} $filename (${filesize_mb}MB)"
    
    # Upload using curl
    response=$(curl -s -X POST "$API_URL/files/upload" \
        -H "APIKEY: $API_KEY" \
        -H "Accept: application/json" \
        -F "zone_id=$ZONE_ID" \
        -F "file=@$file" \
        --max-time 3600)
    
    # Check response
    if echo "$response" | grep -q '"status":"success"'; then
        echo -e "${GREEN}✓${NC} Success"
        echo "[$(date)] SUCCESS: $filename" >> "$LOG_FILE"
        return 0
    else
        echo -e "${RED}✗${NC} Failed: $response"
        echo "[$(date)] FAILED: $filename - $response" >> "$LOG_FILE"
        return 1
    fi
}

# Count files
total_files=$(find "$SOURCE_DIR" -type f \( -iname "*.mp4" -o -iname "*.mov" -o -iname "*.avi" -o -iname "*.mkv" -o -iname "*.webm" \) | wc -l)
echo "Found $total_files video files"
echo ""

# Upload videos
success=0
failed=0
count=0

find "$SOURCE_DIR" -type f \( -iname "*.mp4" -o -iname "*.mov" -o -iname "*.avi" -o -iname "*.mkv" -o -iname "*.webm" \) | while read file; do
    ((count++))
    echo "[$count/$total_files]"
    
    if upload_file "$file"; then
        ((success++))
    else
        ((failed++))
    fi
    
    echo ""
    
    # Small delay to avoid rate limiting
    sleep 1
done

echo "======================================"
echo "  Upload Complete"
echo "======================================"
echo ""
echo "Check log for details: $LOG_FILE"
