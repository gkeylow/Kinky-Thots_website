#!/bin/bash
# Video Manifest Manager
# Manages the video-manifest.json file for CDN-based video serving

MANIFEST="/var/www/kinky-thots/data/video-manifest.json"
CDN_BASE="https://c5988z6295.r-cdn.com"

show_help() {
    echo "Video Manifest Manager"
    echo "======================"
    echo ""
    echo "Usage: $0 <command> [options]"
    echo ""
    echo "Commands:"
    echo "  list              List all videos in manifest"
    echo "  check             Check CDN status for all videos"
    echo "  add <file>        Add a video to manifest (scans local file for dimensions)"
    echo "  remove <filename> Remove a video from manifest"
    echo "  sync              Sync manifest with local /media/porn directory"
    echo "  missing           Show videos not on CDN"
    echo ""
}

list_videos() {
    echo "Videos in manifest:"
    echo "==================="
    jq -r '.videos[] | "\(.filename) [\(.width)x\(.height)] CDN:\(.on_cdn)"' "$MANIFEST"
    echo ""
    echo "Total: $(jq '.count' "$MANIFEST") videos"
}

check_cdn() {
    echo "Checking CDN status for all videos..."
    echo "======================================"
    
    jq -r '.videos[].filename' "$MANIFEST" | while read filename; do
        encoded=$(python3 -c "import urllib.parse; print(urllib.parse.quote('$filename'))")
        status=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$CDN_BASE/$encoded")
        
        if [ "$status" = "200" ]; then
            echo "✓ $filename"
        else
            echo "✗ $filename (HTTP $status)"
        fi
    done
}

show_missing() {
    echo "Videos NOT on CDN (on_cdn: false):"
    echo "==================================="
    jq -r '.videos[] | select(.on_cdn == false) | .filename' "$MANIFEST"
    echo ""
    echo "These need to be uploaded to CDN before removing local files."
}

add_video() {
    local filepath="$1"
    
    if [ ! -f "$filepath" ]; then
        echo "Error: File not found: $filepath"
        exit 1
    fi
    
    local filename=$(basename "$filepath")
    
    # Check if already exists
    if jq -e ".videos[] | select(.filename == \"$filename\")" "$MANIFEST" > /dev/null 2>&1; then
        echo "Video already in manifest: $filename"
        exit 1
    fi
    
    # Get dimensions
    local dims=$(ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 "$filepath" 2>/dev/null)
    local width=$(echo "$dims" | cut -d'x' -f1)
    local height=$(echo "$dims" | cut -d'x' -f2)
    local size=$(stat -c%s "$filepath" 2>/dev/null || stat -f%z "$filepath" 2>/dev/null)
    
    # Check if on CDN
    local encoded=$(python3 -c "import urllib.parse; print(urllib.parse.quote('$filename'))")
    local cdn_status=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$CDN_BASE/$encoded")
    local on_cdn="false"
    [ "$cdn_status" = "200" ] && on_cdn="true"
    
    # Add to manifest
    local tmp=$(mktemp)
    jq ".videos += [{\"filename\": \"$filename\", \"width\": ${width:-null}, \"height\": ${height:-null}, \"size_bytes\": ${size:-0}, \"on_cdn\": $on_cdn}] | .count = (.videos | length) | .generated = \"$(date -Iseconds)\"" "$MANIFEST" > "$tmp"
    mv "$tmp" "$MANIFEST"
    
    echo "Added: $filename (${width}x${height}, CDN: $on_cdn)"
}

remove_video() {
    local filename="$1"
    
    if ! jq -e ".videos[] | select(.filename == \"$filename\")" "$MANIFEST" > /dev/null 2>&1; then
        echo "Video not in manifest: $filename"
        exit 1
    fi
    
    local tmp=$(mktemp)
    jq "del(.videos[] | select(.filename == \"$filename\")) | .count = (.videos | length) | .generated = \"$(date -Iseconds)\"" "$MANIFEST" > "$tmp"
    mv "$tmp" "$MANIFEST"
    
    echo "Removed: $filename"
}

sync_local() {
    echo "Syncing manifest with local files in /media/porn..."
    echo "===================================================="
    
    # Find all local MP4 files
    find /media/porn -name "*.mp4" -type f 2>/dev/null | while read filepath; do
        filename=$(basename "$filepath")
        
        # Check if in manifest
        if ! jq -e ".videos[] | select(.filename == \"$filename\")" "$MANIFEST" > /dev/null 2>&1; then
            echo "Adding missing: $filename"
            add_video "$filepath"
        fi
    done
    
    echo ""
    echo "Sync complete."
}

# Main
case "$1" in
    list)
        list_videos
        ;;
    check)
        check_cdn
        ;;
    add)
        [ -z "$2" ] && { echo "Usage: $0 add <filepath>"; exit 1; }
        add_video "$2"
        ;;
    remove)
        [ -z "$2" ] && { echo "Usage: $0 remove <filename>"; exit 1; }
        remove_video "$2"
        ;;
    sync)
        sync_local
        ;;
    missing)
        show_missing
        ;;
    *)
        show_help
        ;;
esac
