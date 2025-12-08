#!/bin/bash

################################################################################
# NOTE: This script is NO LONGER NEEDED!
# 
# The porn page is now DYNAMIC using PHP (porn.php)
# It automatically scans /media/porn and displays all videos in real-time.
# 
# Just add any MP4/MOV/WEBM/MKV file to /media/porn and refresh the page!
# No script execution required.
################################################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}  porn.php is now DYNAMIC!${NC}"
echo -e "${BLUE}================================${NC}"
echo ""
echo -e "${GREEN}✓ The video gallery is now powered by PHP${NC}"
echo -e "${GREEN}✓ Videos are automatically detected from /media/porn${NC}"
echo -e "${GREEN}✓ No manual updates needed!${NC}"
echo ""
echo -e "${YELLOW}How it works:${NC}"
echo -e "  1. Add any video file to /media/porn (or subdirectories)"
echo -e "  2. Refresh kinky-thots.com/porn.php in your browser"
echo -e "  3. New videos appear automatically!"
echo ""
echo -e "${YELLOW}Supported formats:${NC}"
echo -e "  • MP4, MOV, WEBM, MKV, AVI"
echo ""
echo -e "${YELLOW}Access the page:${NC}"
echo -e "  • kinky-thots.com/porn.php (direct)"
echo -e "  • kinky-thots.com/porn.html (redirects to porn.php)"
echo ""

# Show current video count
video_count=$(find /media/porn -type f \( -iname "*.mp4" -o -iname "*.mov" -o -iname "*.webm" -o -iname "*.mkv" -o -iname "*.avi" \) 2>/dev/null | wc -l)
echo -e "${BLUE}Current videos in /media/porn: ${GREEN}$video_count${NC}"
echo ""
