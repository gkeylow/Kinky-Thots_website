#!/bin/bash

################################################################################
# MOV to MP4 Batch Converter
# Converts all .mov/.MOV files to web-compatible MP4 format
################################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SOURCE_DIR="/media/porn"
BACKUP_DIR="/media/porn/originals"
LOG_FILE="/var/www/kinky-thots/conversion.log"

# FFmpeg settings for web-optimized video
VIDEO_CODEC="libx264"
AUDIO_CODEC="aac"
VIDEO_QUALITY="23"  # Lower = better quality (18-28 recommended)
PRESET="medium"     # ultrafast, superfast, veryfast, faster, fast, medium, slow, slower, veryslow
AUDIO_BITRATE="128k"

################################################################################
# Functions
################################################################################

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  MOV to MP4 Batch Converter${NC}"
    echo -e "${BLUE}================================${NC}"
    echo ""
}

check_ffmpeg() {
    if ! command -v ffmpeg &> /dev/null; then
        echo -e "${RED}ERROR: ffmpeg is not installed${NC}"
        echo "Install with: apt-get install ffmpeg"
        exit 1
    fi
    echo -e "${GREEN}✓ ffmpeg found: $(ffmpeg -version | head -n1)${NC}"
}

check_directory() {
    if [ ! -d "$SOURCE_DIR" ]; then
        echo -e "${RED}ERROR: Source directory not found: $SOURCE_DIR${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Source directory found: $SOURCE_DIR${NC}"
}

create_backup_dir() {
    if [ ! -d "$BACKUP_DIR" ]; then
        mkdir -p "$BACKUP_DIR"
        echo -e "${GREEN}✓ Created backup directory: $BACKUP_DIR${NC}"
    else
        echo -e "${YELLOW}! Backup directory already exists: $BACKUP_DIR${NC}"
    fi
}

count_mov_files() {
    local count=$(find "$SOURCE_DIR" -type f \( -iname "*.mov" \) | wc -l)
    echo "$count"
}

convert_file() {
    local input_file="$1"
    local output_file="${input_file%.*}.mp4"
    local filename=$(basename "$input_file")
    
    # Skip if MP4 already exists
    if [ -f "$output_file" ]; then
        echo -e "${YELLOW}⊘ Skipping (MP4 exists): $filename${NC}"
        return 0
    fi
    
    echo -e "${BLUE}▶ Converting: $filename${NC}"
    
    # Convert with ffmpeg
    if ffmpeg -i "$input_file" \
        -c:v "$VIDEO_CODEC" \
        -crf "$VIDEO_QUALITY" \
        -preset "$PRESET" \
        -c:a "$AUDIO_CODEC" \
        -b:a "$AUDIO_BITRATE" \
        -movflags +faststart \
        -y \
        "$output_file" \
        >> "$LOG_FILE" 2>&1; then
        
        echo -e "${GREEN}✓ Converted: $filename${NC}"
        
        # Move original to backup
        local relative_path="${input_file#$SOURCE_DIR/}"
        local backup_path="$BACKUP_DIR/$(dirname "$relative_path")"
        mkdir -p "$backup_path"
        mv "$input_file" "$backup_path/"
        echo -e "${GREEN}  → Original moved to: $backup_path/${NC}"
        
        return 0
    else
        echo -e "${RED}✗ Failed: $filename${NC}"
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
    echo "=== Conversion started at $(date) ===" > "$LOG_FILE"
    
    # Pre-flight checks
    check_ffmpeg
    check_directory
    
    # Count files
    total_files=$(count_mov_files)
    
    if [ "$total_files" -eq 0 ]; then
        echo -e "${YELLOW}No MOV files found in $SOURCE_DIR${NC}"
        exit 0
    fi
    
    echo -e "${BLUE}Found $total_files MOV file(s) to convert${NC}"
    echo ""
    
    # Ask for confirmation
    read -p "Create backup directory and proceed with conversion? (y/n): " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Conversion cancelled${NC}"
        exit 0
    fi
    
    create_backup_dir
    echo ""
    
    # Process files
    converted=0
    failed=0
    skipped=0
    
    while IFS= read -r -d '' file; do
        if convert_file "$file"; then
            ((converted++))
        else
            ((failed++))
        fi
    done < <(find "$SOURCE_DIR" -type f \( -iname "*.mov" \) -print0)
    
    # Summary
    echo ""
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Conversion Summary${NC}"
    echo -e "${BLUE}================================${NC}"
    echo -e "${GREEN}✓ Converted: $converted${NC}"
    echo -e "${RED}✗ Failed: $failed${NC}"
    echo -e "${YELLOW}⊘ Skipped: $skipped${NC}"
    echo ""
    echo -e "Original files backed up to: ${YELLOW}$BACKUP_DIR${NC}"
    echo -e "Conversion log: ${YELLOW}$LOG_FILE${NC}"
    echo ""
    
    if [ "$failed" -gt 0 ]; then
        echo -e "${RED}Some conversions failed. Check the log file for details.${NC}"
        exit 1
    else
        echo -e "${GREEN}All conversions completed successfully!${NC}"
    fi
}

# Run main function
main
