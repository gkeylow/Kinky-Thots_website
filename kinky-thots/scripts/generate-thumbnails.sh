#!/bin/bash

################################################################################
# Video Thumbnail Generator
# Automatically generates preview images for all videos in /media/porn
################################################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
VIDEO_DIR="/media/porn"
THUMB_DIR="/var/www/kinky-thots/porn/thumbnails"
LOG_FILE="/var/www/kinky-thots/thumbnail-generation.log"

# Thumbnail settings
THUMB_TIME="00:00:05"      # Capture frame at 5 seconds
THUMB_WIDTH="400"          # Width in pixels (height auto-calculated)
THUMB_QUALITY="2"          # Quality 2-31 (lower = better, 2 is high quality)

################################################################################
# Functions
################################################################################

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Video Thumbnail Generator${NC}"
    echo -e "${BLUE}================================${NC}"
    echo ""
}

check_ffmpeg() {
    if ! command -v ffmpeg &> /dev/null; then
        echo -e "${RED}ERROR: ffmpeg is not installed${NC}"
        echo "Install with: apt-get install ffmpeg"
        exit 1
    fi
    echo -e "${GREEN}✓ ffmpeg found${NC}"
}

check_directories() {
    if [ ! -d "$VIDEO_DIR" ]; then
        echo -e "${RED}ERROR: Video directory not found: $VIDEO_DIR${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Video directory found: $VIDEO_DIR${NC}"
    
    # Create thumbnail directory if it doesn't exist
    if [ ! -d "$THUMB_DIR" ]; then
        mkdir -p "$THUMB_DIR"
        echo -e "${GREEN}✓ Created thumbnail directory: $THUMB_DIR${NC}"
    else
        echo -e "${YELLOW}! Thumbnail directory exists: $THUMB_DIR${NC}"
    fi
}

count_videos() {
    local count=$(find "$VIDEO_DIR" -type f \( -iname "*.mp4" -o -iname "*.mov" -o -iname "*.webm" -o -iname "*.mkv" -o -iname "*.avi" \) 2>/dev/null | wc -l)
    echo "$count"
}

generate_thumbnail() {
    local video_file="$1"
    local relative_path="${video_file#$VIDEO_DIR/}"
    local thumb_name="${relative_path%.*}.jpg"
    local thumb_path="$THUMB_DIR/$thumb_name"
    local thumb_dir=$(dirname "$thumb_path")
    
    # Create subdirectory structure in thumbnails folder
    mkdir -p "$thumb_dir"
    
    # Skip if thumbnail already exists
    if [ -f "$thumb_path" ]; then
        echo -e "${YELLOW}⊘ Skipping (exists): $relative_path${NC}"
        return 2
    fi
    
    echo -e "${BLUE}▶ Generating: $relative_path${NC}"
    
    # Generate thumbnail with ffmpeg (added -update 1 for single image output)
    if ffmpeg -i "$video_file" \
        -ss "$THUMB_TIME" \
        -vframes 1 \
        -vf "scale=$THUMB_WIDTH:-1" \
        -q:v "$THUMB_QUALITY" \
        -update 1 \
        "$thumb_path" \
        >> "$LOG_FILE" 2>&1; then
        
        echo -e "${GREEN}✓ Created: $thumb_name${NC}"
        return 0
    else
        echo -e "${RED}✗ Failed: $relative_path${NC}"
        echo -e "${RED}  Check log: $LOG_FILE${NC}"
        return 1
    fi
}

################################################################################
# Main Script
################################################################################

main() {
    print_header
    
    # Initialize log
    echo "=== Thumbnail generation started at $(date) ===" > "$LOG_FILE"
    
    # Pre-flight checks
    check_ffmpeg
    check_directories
    echo ""
    
    # Count videos
    total_videos=$(count_videos)
    
    if [ "$total_videos" -eq 0 ]; then
        echo -e "${YELLOW}No video files found in $VIDEO_DIR${NC}"
        exit 0
    fi
    
    echo -e "${BLUE}Found $total_videos video file(s)${NC}"
    echo ""
    
    # Ask for confirmation
    read -p "Generate thumbnails for all videos? (y/n): " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Thumbnail generation cancelled${NC}"
        exit 0
    fi
    
    echo ""
    
    # Process videos
    generated=0
    failed=0
    skipped=0
    
    while IFS= read -r -d '' video_file; do
        result=$(generate_thumbnail "$video_file"; echo $?)
        case $result in
            0) ((generated++)) ;;
            1) ((failed++)) ;;
            2) ((skipped++)) ;;
        esac
    done < <(find "$VIDEO_DIR" -type f \( -iname "*.mp4" -o -iname "*.mov" -o -iname "*.webm" -o -iname "*.mkv" -o -iname "*.avi" \) -print0 2>/dev/null)
    
    # Summary
    echo ""
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Generation Summary${NC}"
    echo -e "${BLUE}================================${NC}"
    echo -e "${GREEN}✓ Generated: $generated${NC}"
    echo -e "${RED}✗ Failed: $failed${NC}"
    echo -e "${YELLOW}⊘ Skipped: $skipped${NC}"
    echo ""
    echo -e "Thumbnails saved to: ${YELLOW}$THUMB_DIR${NC}"
    echo -e "Generation log: ${YELLOW}$LOG_FILE${NC}"
    echo ""
    
    if [ "$failed" -gt 0 ]; then
        echo -e "${YELLOW}Some thumbnails failed. Check the log file for details.${NC}"
        echo -e "${BLUE}Thumbnails that succeeded will still appear on porn.php${NC}"
    else
        echo -e "${GREEN}All thumbnails generated successfully!${NC}"
    fi
    
    echo ""
    echo -e "${BLUE}Next step: Refresh porn.php to see thumbnails!${NC}"
}

# Run main function
main
