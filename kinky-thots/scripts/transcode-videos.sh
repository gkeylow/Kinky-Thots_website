#!/bin/bash
#
# Video Transcoding Script for CDN Optimization
# Converts videos to web-optimized MP4 with faststart
#
# Usage:
#   ./transcode-videos.sh              # Process all videos
#   ./transcode-videos.sh --dry-run    # Show what would be done
#   ./transcode-videos.sh <filename>   # Process single video
#

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_DIR/backend"
MANIFEST_FILE="$PROJECT_DIR/data/video-manifest.json"
WORK_DIR="/tmp/transcode-work"
ORIGINALS_DIR="$WORK_DIR/originals"
TRANSCODED_DIR="$WORK_DIR/transcoded"
LOG_FILE="$WORK_DIR/transcode.log"

# FFmpeg settings for web optimization
# CRF 23 = good quality/size balance (18-28 range, lower = better quality)
# preset medium = good speed/compression balance
# faststart = moov atom at beginning for streaming
CRF=23
PRESET="medium"
AUDIO_BITRATE="128k"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date '+%H:%M:%S')]${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

warn() {
    echo -e "${YELLOW}[$(date '+%H:%M:%S')] WARNING:${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: $1" >> "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date '+%H:%M:%S')] ERROR:${NC} $1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"
}

# Format bytes to human readable
format_size() {
    local bytes=$1
    if [ "$bytes" -ge 1073741824 ]; then
        awk "BEGIN {printf \"%.2f GB\", $bytes / 1073741824}"
    elif [ "$bytes" -ge 1048576 ]; then
        awk "BEGIN {printf \"%.2f MB\", $bytes / 1048576}"
    else
        awk "BEGIN {printf \"%.2f KB\", $bytes / 1024}"
    fi
}

# Get CDN base URL from manifest
get_cdn_url() {
    jq -r '.cdn_base_url' "$MANIFEST_FILE"
}

# Get list of videos from manifest
get_videos() {
    jq -r '.videos[].filename' "$MANIFEST_FILE"
}

# Get video info from manifest
get_video_info() {
    local filename="$1"
    jq -r ".videos[] | select(.filename == \"$filename\")" "$MANIFEST_FILE"
}

# Check if video needs transcoding (MOV files or large size ratio)
needs_transcoding() {
    local filename="$1"
    local ext="${filename##*.}"
    ext=$(echo "$ext" | tr '[:upper:]' '[:lower:]')

    # Always transcode MOV files
    if [ "$ext" = "mov" ]; then
        return 0
    fi

    # Check size/duration ratio (bytes per second)
    local info=$(get_video_info "$filename")
    local size=$(echo "$info" | jq -r '.size_bytes')
    local duration=$(echo "$info" | jq -r '.duration_seconds')

    if [ "$duration" -gt 0 ]; then
        local bytes_per_sec=$((size / duration))
        # If more than 500KB/s, it's probably not optimized
        # Well-optimized 1080p is typically 200-400KB/s
        if [ "$bytes_per_sec" -gt 500000 ]; then
            return 0
        fi
    fi

    return 1
}

# Download video from CDN
download_video() {
    local filename="$1"
    local cdn_url=$(get_cdn_url)
    local encoded_filename=$(python3 -c "import urllib.parse; print(urllib.parse.quote('$filename'))")
    local url="${cdn_url}/${encoded_filename}"
    local output="$ORIGINALS_DIR/$filename"

    log "Downloading: $filename"

    if [ -f "$output" ]; then
        log "  Already downloaded, skipping"
        return 0
    fi

    mkdir -p "$ORIGINALS_DIR"

    if curl -L --progress-bar -o "$output" "$url" < /dev/null; then
        local size=$(stat -c%s "$output" 2>/dev/null || stat -f%z "$output")
        log "  Downloaded: $(format_size $size)"
        return 0
    else
        error "  Download failed!"
        rm -f "$output"
        return 1
    fi
}

# Transcode video to web-optimized MP4
transcode_video() {
    local input="$1"
    local filename=$(basename "$input")
    local name="${filename%.*}"
    local output="$TRANSCODED_DIR/${name}.mp4"

    mkdir -p "$TRANSCODED_DIR"

    if [ -f "$output" ]; then
        log "  Already transcoded, skipping"
        return 0
    fi

    local input_size=$(stat -c%s "$input" 2>/dev/null || stat -f%z "$input")
    log "Transcoding: $filename ($(format_size $input_size))"

    # Get input video info
    local input_info=$(ffprobe -v quiet -print_format json -show_streams "$input" 2>/dev/null)
    local width=$(echo "$input_info" | jq -r '.streams[] | select(.codec_type=="video") | .width' | head -1)
    local height=$(echo "$input_info" | jq -r '.streams[] | select(.codec_type=="video") | .height' | head -1)

    log "  Input resolution: ${width}x${height}"

    # Scale down if larger than 1080p
    local scale_filter=""
    if [ "$height" -gt 1080 ] 2>/dev/null; then
        scale_filter="-vf scale=-2:1080"
        log "  Scaling down to 1080p"
    elif [ "$width" -gt 1920 ] 2>/dev/null; then
        scale_filter="-vf scale=1920:-2"
        log "  Scaling down to 1080p"
    fi

    # Run ffmpeg (-nostdin prevents consuming loop's stdin)
    if ffmpeg -nostdin -y -i "$input" \
        -c:v libx264 -crf $CRF -preset $PRESET \
        $scale_filter \
        -c:a aac -b:a $AUDIO_BITRATE \
        -movflags +faststart \
        -hide_banner -loglevel warning \
        "$output" 2>&1; then

        local output_size=$(stat -c%s "$output" 2>/dev/null || stat -f%z "$output")
        local reduction=$((100 - (output_size * 100 / input_size)))

        log "  Output: $(format_size $output_size) (${reduction}% smaller)"
        return 0
    else
        error "  Transcoding failed!"
        rm -f "$output"
        return 1
    fi
}

# Upload transcoded video to CDN
upload_video() {
    local filepath="$1"
    local filename=$(basename "$filepath")

    log "Uploading: $filename"

    cd "$BACKEND_DIR"
    if node sonic-cli.js upload "$filepath" "$filename" < /dev/null 2>&1 | tee -a "$LOG_FILE"; then
        log "  Upload complete"
        return 0
    else
        error "  Upload failed!"
        return 1
    fi
}

# Delete original from CDN (optional, after verification)
delete_original() {
    local filename="$1"

    log "Deleting original: $filename"

    cd "$BACKEND_DIR"
    if node sonic-cli.js delete "$filename" < /dev/null 2>&1 | tee -a "$LOG_FILE"; then
        log "  Deleted from CDN"
        return 0
    else
        warn "  Could not delete (may not exist or different name)"
        return 1
    fi
}

# Process a single video
process_video() {
    local filename="$1"
    local dry_run="$2"

    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log "Processing: $filename"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    local info=$(get_video_info "$filename")
    local size=$(echo "$info" | jq -r '.size_bytes')
    local duration=$(echo "$info" | jq -r '.duration_seconds')

    info "  Size: $(format_size $size)"
    info "  Duration: ${duration}s"

    if needs_transcoding "$filename"; then
        log "  Needs transcoding: YES"
    else
        log "  Needs transcoding: NO (already optimized)"
        return 0
    fi

    if [ "$dry_run" = "true" ]; then
        info "  [DRY RUN] Would download, transcode, and upload"
        return 0
    fi

    # Download
    if ! download_video "$filename"; then
        return 1
    fi

    # Transcode
    local input="$ORIGINALS_DIR/$filename"
    if ! transcode_video "$input"; then
        return 1
    fi

    # Get output filename (always .mp4 now)
    local name="${filename%.*}"
    local output="$TRANSCODED_DIR/${name}.mp4"

    # Upload
    if ! upload_video "$output"; then
        return 1
    fi

    # If original had different extension, delete it
    local ext="${filename##*.}"
    ext=$(echo "$ext" | tr '[:upper:]' '[:lower:]')
    if [ "$ext" != "mp4" ]; then
        delete_original "$filename"
    fi

    log "✓ Completed: $filename"
    return 0
}

# Main
main() {
    local dry_run="false"
    local single_file=""

    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dry-run)
                dry_run="true"
                shift
                ;;
            *)
                single_file="$1"
                shift
                ;;
        esac
    done

    # Setup
    mkdir -p "$WORK_DIR"

    echo ""
    echo "╔═══════════════════════════════════════════════════════════════╗"
    echo "║           Video Transcoding for CDN Optimization              ║"
    echo "╚═══════════════════════════════════════════════════════════════╝"
    echo ""

    if [ "$dry_run" = "true" ]; then
        warn "DRY RUN MODE - No changes will be made"
    fi

    log "Work directory: $WORK_DIR"
    log "Log file: $LOG_FILE"

    # Check dependencies
    if ! command -v ffmpeg &> /dev/null; then
        error "ffmpeg not found. Please install ffmpeg."
        exit 1
    fi

    if ! command -v jq &> /dev/null; then
        error "jq not found. Please install jq."
        exit 1
    fi

    if [ ! -f "$MANIFEST_FILE" ]; then
        error "Manifest not found: $MANIFEST_FILE"
        exit 1
    fi

    local cdn_url=$(get_cdn_url)
    log "CDN URL: $cdn_url"

    # Process videos
    local total=0
    local processed=0
    local failed=0

    if [ -n "$single_file" ]; then
        # Process single file
        if process_video "$single_file" "$dry_run"; then
            processed=1
        else
            failed=1
        fi
        total=1
    else
        # Process all videos
        local videos=$(get_videos)
        total=$(echo "$videos" | wc -l)

        log "Found $total videos in manifest"

        local current=0
        while IFS= read -r filename; do
            current=$((current + 1))
            info "[$current/$total] Processing..."

            if process_video "$filename" "$dry_run"; then
                processed=$((processed + 1))
            else
                failed=$((failed + 1))
            fi
        done <<< "$videos"
    fi

    # Summary
    echo ""
    echo "╔═══════════════════════════════════════════════════════════════╗"
    echo "║                         Summary                               ║"
    echo "╚═══════════════════════════════════════════════════════════════╝"
    echo ""
    log "Total videos: $total"
    log "Processed: $processed"
    log "Failed: $failed"

    if [ "$dry_run" != "true" ] && [ "$processed" -gt 0 ]; then
        echo ""
        log "Updating manifest..."
        cd "$BACKEND_DIR"
        npm run sonic:sync-manifest
    fi

    echo ""
    log "Done!"

    if [ "$failed" -gt 0 ]; then
        exit 1
    fi
}

main "$@"
