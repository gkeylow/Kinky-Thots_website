#!/bin/bash

################################################################################
# Video Compression Script
# Compresses large videos to web-optimized sizes (100-200MB target)
# Maintains quality while dramatically reducing file size
################################################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m'

# Configuration
VIDEO_DIR="/media/porn"
BACKUP_DIR="/media/porn/originals"
COMPRESSED_DIR="/media/porn/compressed"
LOG_FILE="/var/www/kinky-thots/compression.log"

# Compression settings (balanced quality/size)
VIDEO_CODEC="libx264"           # H.264 codec (best compatibility)
AUDIO_CODEC="aac"               # AAC audio (best compatibility)
CRF="23"                        # Constant Rate Factor (18-28, 23=good balance)
PRESET="medium"                 # Encoding speed (faster=bigger, slower=smaller)
AUDIO_BITRATE="128k"            # Audio quality
MAX_WIDTH="1920"                # Max resolution width (1080p)
TARGET_SIZE_MB="150"            # Target file size in MB (for reference)

# Size threshold - only compress videos larger than this (in MB)
MIN_SIZE_MB="200"

################################################################################
# Functions
################################################################################

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Video Compression Tool${NC}"
    echo -e "${BLUE}================================${NC}"
    echo ""
}

check_ffmpeg() {
    if ! command -v ffmpeg &> /dev/null; then
        echo -e "${RED}ERROR: ffmpeg is not installed${NC}"
        echo "Install with: apt-get install ffmpeg"
        exit 1
    fi
    echo -e "${GREEN}✓ ffmpeg found: $(ffmpeg -version | head -n1 | cut -d' ' -f3)${NC}"
}

check_directories() {
    if [ ! -d "$VIDEO_DIR" ]; then
        echo -e "${RED}ERROR: Video directory not found: $VIDEO_DIR${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Video directory found: $VIDEO_DIR${NC}"
    
    # Create backup directory
    if [ ! -d "$BACKUP_DIR" ]; then
        mkdir -p "$BACKUP_DIR"
        echo -e "${GREEN}✓ Created backup directory: $BACKUP_DIR${NC}"
    else
        echo -e "${YELLOW}! Backup directory exists: $BACKUP_DIR${NC}"
    fi
    
    # Create compressed directory for temporary files
    if [ ! -d "$COMPRESSED_DIR" ]; then
        mkdir -p "$COMPRESSED_DIR"
        echo -e "${GREEN}✓ Created compressed directory: $COMPRESSED_DIR${NC}"
    fi
}

human_readable_size() {
    local size=$1
    if [ $size -lt 1024 ]; then
        echo "${size}B"
    elif [ $size -lt 1048576 ]; then
        echo "$(($size / 1024))KB"
    elif [ $size -lt 1073741824 ]; then
        echo "$(($size / 1048576))MB"
    else
        echo "$(($size / 1073741824))GB"
    fi
}

get_video_info() {
    local video_file="$1"
    local size=$(stat -f%z "$video_file" 2>/dev/null || stat -c%s "$video_file" 2>/dev/null)
    local size_mb=$((size / 1048576))
    local duration=$(ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "$video_file" 2>/dev/null | cut -d. -f1)
    local resolution=$(ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 "$video_file" 2>/dev/null)
    
    echo "$size_mb|$duration|$resolution"
}

should_compress() {
    local video_file="$1"
    local size_mb=$(stat -f%z "$video_file" 2>/dev/null || stat -c%s "$video_file" 2>/dev/null)
    size_mb=$((size_mb / 1048576))
    
    if [ $size_mb -lt $MIN_SIZE_MB ]; then
        return 1  # Don't compress
    fi
    return 0  # Compress
}

compress_video() {
    local input_file="$1"
    local filename=$(basename "$input_file")
    local relative_path="${input_file#$VIDEO_DIR/}"
    local output_file="$COMPRESSED_DIR/$filename"
    
    # Get original video info
    local info=$(get_video_info "$input_file")
    local orig_size_mb=$(echo $info | cut -d'|' -f1)
    local duration=$(echo $info | cut -d'|' -f2)
    local resolution=$(echo $info | cut -d'|' -f3)
    
    # Check if should compress
    if ! should_compress "$input_file"; then
        echo -e "${YELLOW}⊘ Skipping (< ${MIN_SIZE_MB}MB): $filename (${orig_size_mb}MB)${NC}"
        return 2
    fi
    
    echo -e "${BLUE}▶ Compressing: $filename${NC}"
    echo -e "  Original: ${orig_size_mb}MB | ${resolution} | ${duration}s"
    
    # Compress with ffmpeg
    if ffmpeg -i "$input_file" \
        -c:v "$VIDEO_CODEC" \
        -crf "$CRF" \
        -preset "$PRESET" \
        -vf "scale='min($MAX_WIDTH,iw)':'min($MAX_WIDTH*ih/iw,ih)':force_original_aspect_ratio=decrease" \
        -c:a "$AUDIO_CODEC" \
        -b:a "$AUDIO_BITRATE" \
        -movflags +faststart \
        -y \
        "$output_file" \
        >> "$LOG_FILE" 2>&1; then
        
        # Get compressed size
        local new_size_mb=$(stat -f%z "$output_file" 2>/dev/null || stat -c%s "$output_file" 2>/dev/null)
        new_size_mb=$((new_size_mb / 1048576))
        local savings=$((orig_size_mb - new_size_mb))
        local percent=$((savings * 100 / orig_size_mb))
        
        echo -e "${GREEN}✓ Compressed: ${new_size_mb}MB (saved ${savings}MB / ${percent}%)${NC}"
        
        # Move original to backup
        local backup_path="$BACKUP_DIR/$(dirname "$relative_path")"
        mkdir -p "$backup_path"
        mv "$input_file" "$backup_path/"
        
        # Move compressed file to original location
        mv "$output_file" "$input_file"
        
        echo -e "${GREEN}  → Original backed up to: $backup_path/${NC}"
        echo "$orig_size_mb|$new_size_mb|$savings" # Return stats
        return 0
    else
        echo -e "${RED}✗ Failed: $filename${NC}"
        echo -e "${RED}  Check log: $LOG_FILE${NC}"
        rm -f "$output_file"
        return 1
    fi
}

count_large_videos() {
    local count=0
    while IFS= read -r -d '' file; do
        if should_compress "$file"; then
            ((count++))
        fi
    done < <(find "$VIDEO_DIR" -type f \( -iname "*.mp4" -o -iname "*.mov" -o -iname "*.webm" -o -iname "*.mkv" -o -iname "*.avi" \) -print0 2>/dev/null)
    echo "$count"
}

################################################################################
# Main Script
################################################################################

main() {
    print_header
    
    # Initialize log
    echo "=== Compression started at $(date) ===" > "$LOG_FILE"
    
    # Pre-flight checks
    check_ffmpeg
    check_directories
    echo ""
    
    # Count videos that need compression
    echo -e "${BLUE}Scanning for videos larger than ${MIN_SIZE_MB}MB...${NC}"
    large_videos=$(count_large_videos)
    
    if [ "$large_videos" -eq 0 ]; then
        echo -e "${GREEN}No videos need compression (all under ${MIN_SIZE_MB}MB)${NC}"
        exit 0
    fi
    
    echo -e "${BLUE}Found $large_videos video(s) that need compression${NC}"
    echo ""
    
    # Show compression settings
    echo -e "${MAGENTA}Compression Settings:${NC}"
    echo -e "  CRF: $CRF (lower=better quality, 18-28 recommended)"
    echo -e "  Preset: $PRESET (faster encoding)"
    echo -e "  Max Resolution: ${MAX_WIDTH}p"
    echo -e "  Audio: ${AUDIO_BITRATE} AAC"
    echo -e "  Target: ~${TARGET_SIZE_MB}MB per video"
    echo ""
    
    # Ask for confirmation
    read -p "Proceed with compression? (y/n): " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Compression cancelled${NC}"
        exit 0
    fi
    
    echo ""
    
    # Process videos
    compressed=0
    failed=0
    skipped=0
    total_saved=0
    
    while IFS= read -r -d '' video_file; do
        result=$(compress_video "$video_file")
        exit_code=$?
        
        case $exit_code in
            0)
                ((compressed++))
                saved=$(echo "$result" | cut -d'|' -f3)
                total_saved=$((total_saved + saved))
                ;;
            1) ((failed++)) ;;
            2) ((skipped++)) ;;
        esac
    done < <(find "$VIDEO_DIR" -type f \( -iname "*.mp4" -o -iname "*.mov" -o -iname "*.webm" -o -iname "*.mkv" -o -iname "*.avi" \) -print0 2>/dev/null)
    
    # Summary
    echo ""
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Compression Summary${NC}"
    echo -e "${BLUE}================================${NC}"
    echo -e "${GREEN}✓ Compressed: $compressed${NC}"
    echo -e "${RED}✗ Failed: $failed${NC}"
    echo -e "${YELLOW}⊘ Skipped: $skipped (< ${MIN_SIZE_MB}MB)${NC}"
    echo -e "${MAGENTA}💾 Total saved: ${total_saved}MB${NC}"
    echo ""
    echo -e "Original files backed up to: ${YELLOW}$BACKUP_DIR${NC}"
    echo -e "Compression log: ${YELLOW}$LOG_FILE${NC}"
    echo ""
    
    if [ "$compressed" -gt 0 ]; then
        echo -e "${GREEN}Compression complete!${NC}"
        echo -e "${BLUE}Next steps:${NC}"
        echo -e "  1. Test videos on porn.php to ensure quality"
        echo -e "  2. If satisfied, you can delete backups to save space"
        echo -e "  3. Run generate-thumbnails.sh if needed"
    fi
    
    if [ "$failed" -gt 0 ]; then
        echo -e "${RED}Some compressions failed. Check the log file for details.${NC}"
        exit 1
    fi
}

# Run main function
main
