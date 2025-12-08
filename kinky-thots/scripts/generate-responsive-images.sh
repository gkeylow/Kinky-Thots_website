#!/bin/bash
# Generate responsive image sizes for web optimization
# Creates thumbnail, small, medium, and large versions

SOURCE_DIR="${1:-/var/www/kinky-thots/uploads}"
OUTPUT_BASE="${2:-/var/www/kinky-thots/uploads/optimized}"

# Image sizes (width in pixels)
THUMB_SIZE=150
SMALL_SIZE=480
MEDIUM_SIZE=1024
LARGE_SIZE=1920

# Quality settings
THUMB_QUALITY=75
SMALL_QUALITY=80
MEDIUM_QUALITY=85
LARGE_QUALITY=90

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "======================================"
echo "  Responsive Image Generator"
echo "======================================"
echo ""
echo "Source: $SOURCE_DIR"
echo "Output: $OUTPUT_BASE"
echo ""

# Check if ImageMagick is installed
if ! command -v convert &> /dev/null; then
    echo "Installing ImageMagick..."
    apt-get update && apt-get install -y imagemagick
fi

# Create output directories
mkdir -p "$OUTPUT_BASE"/{thumb,small,medium,large}

# Process images
process_image() {
    local file="$1"
    local filename=$(basename "$file")
    local name="${filename%.*}"
    local ext="${filename##*.}"
    
    # Skip if already optimized
    if [[ "$file" == *"/optimized/"* ]]; then
        return
    fi
    
    # Only process images (not videos)
    if [[ ! "$ext" =~ ^(jpg|jpeg|png|gif|webp|JPG|JPEG|PNG|GIF|WEBP)$ ]]; then
        return
    fi
    
    echo -e "${BLUE}Processing:${NC} $filename"
    
    # Get original dimensions
    dimensions=$(identify -format "%wx%h" "$file" 2>/dev/null)
    if [ -z "$dimensions" ]; then
        echo "  ⚠ Skipped (invalid image)"
        return
    fi
    
    width=$(echo $dimensions | cut -d'x' -f1)
    height=$(echo $dimensions | cut -d'x' -f2)
    echo "  Original: ${width}x${height}"
    
    # Thumbnail (150px width)
    if [ ! -f "$OUTPUT_BASE/thumb/${name}.jpg" ]; then
        convert "$file" -resize ${THUMB_SIZE}x -quality $THUMB_QUALITY \
            -strip "$OUTPUT_BASE/thumb/${name}.jpg" 2>/dev/null
        echo -e "  ${GREEN}✓${NC} Thumbnail (${THUMB_SIZE}px)"
    fi
    
    # Small (480px width) - for mobile
    if [ $width -gt $SMALL_SIZE ] && [ ! -f "$OUTPUT_BASE/small/${name}.jpg" ]; then
        convert "$file" -resize ${SMALL_SIZE}x -quality $SMALL_QUALITY \
            -strip "$OUTPUT_BASE/small/${name}.jpg" 2>/dev/null
        echo -e "  ${GREEN}✓${NC} Small (${SMALL_SIZE}px)"
    fi
    
    # Medium (1024px width) - for tablets
    if [ $width -gt $MEDIUM_SIZE ] && [ ! -f "$OUTPUT_BASE/medium/${name}.jpg" ]; then
        convert "$file" -resize ${MEDIUM_SIZE}x -quality $MEDIUM_QUALITY \
            -strip "$OUTPUT_BASE/medium/${name}.jpg" 2>/dev/null
        echo -e "  ${GREEN}✓${NC} Medium (${MEDIUM_SIZE}px)"
    fi
    
    # Large (1920px width) - for desktop
    if [ $width -gt $LARGE_SIZE ] && [ ! -f "$OUTPUT_BASE/large/${name}.jpg" ]; then
        convert "$file" -resize ${LARGE_SIZE}x -quality $LARGE_QUALITY \
            -strip "$OUTPUT_BASE/large/${name}.jpg" 2>/dev/null
        echo -e "  ${GREEN}✓${NC} Large (${LARGE_SIZE}px)"
    fi
    
    # WebP versions for modern browsers
    if [ ! -f "$OUTPUT_BASE/thumb/${name}.webp" ]; then
        convert "$file" -resize ${THUMB_SIZE}x -quality $THUMB_QUALITY \
            -define webp:method=6 "$OUTPUT_BASE/thumb/${name}.webp" 2>/dev/null
    fi
    
    if [ $width -gt $SMALL_SIZE ] && [ ! -f "$OUTPUT_BASE/small/${name}.webp" ]; then
        convert "$file" -resize ${SMALL_SIZE}x -quality $SMALL_QUALITY \
            -define webp:method=6 "$OUTPUT_BASE/small/${name}.webp" 2>/dev/null
    fi
    
    echo ""
}

# Find and process all images
count=0
find "$SOURCE_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" -o -iname "*.gif" \) | while read file; do
    process_image "$file"
    ((count++))
done

echo "======================================"
echo "  Complete!"
echo "======================================"
echo ""
echo "Optimized images saved to: $OUTPUT_BASE"
echo ""
echo "Directory structure:"
echo "  thumb/  - 150px thumbnails"
echo "  small/  - 480px (mobile)"
echo "  medium/ - 1024px (tablet)"
echo "  large/  - 1920px (desktop)"
