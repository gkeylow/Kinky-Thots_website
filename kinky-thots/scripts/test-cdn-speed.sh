#!/bin/bash

# CDN Speed Test Script
# Tests loading speed of resources from direct server vs CDN

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║              CDN Performance Speed Test                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test a sample image (you'll need to replace with actual file)
echo -e "${BLUE}Testing Image Loading Speed...${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test direct server
echo -e "${YELLOW}Direct Server (kinky-thots.com):${NC}"
curl -o /dev/null -s -w "  ⏱  Total Time: %{time_total}s\n  🚀 TTFB: %{time_starttransfer}s\n  📦 Size: %{size_download} bytes\n  ⚡ Speed: %{speed_download} bytes/sec\n\n" \
  https://kinky-thots.com/uploads/1765150122666_SissyLongLegs_BJ_1.gif 2>/dev/null || echo "  ❌ Failed to load from direct server"

# Test CDN
echo -e "${YELLOW}CDN (cdn.kinky-thots.com):${NC}"
curl -o /dev/null -s -w "  ⏱  Total Time: %{time_total}s\n  🚀 TTFB: %{time_starttransfer}s\n  📦 Size: %{size_download} bytes\n  ⚡ Speed: %{speed_download} bytes/sec\n\n" \
  https://cdn.kinky-thots.com/uploads/1765150122666_SissyLongLegs_BJ_1.gif 2>/dev/null || echo "  ⚠️  CDN not configured or content not available"

echo ""
echo -e "${BLUE}Testing Video Loading Speed...${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test direct server video (first 10MB only for speed)
echo -e "${YELLOW}Direct Server (kinky-thots.com):${NC}"
curl -o /dev/null -s -w "  ⏱  Total Time: %{time_total}s\n  🚀 TTFB: %{time_starttransfer}s\n  📦 Size: %{size_download} bytes\n  ⚡ Speed: %{speed_download} bytes/sec\n\n" \
  -r 0-10485760 https://kinky-thots.com/porn/Haley/Haley_Blowjob_1.mp4 2>/dev/null || echo "  ❌ Failed to load from direct server"

# Test CDN video
echo -e "${YELLOW}CDN (cdn-video.kinky-thots.com):${NC}"
curl -o /dev/null -s -w "  ⏱  Total Time: %{time_total}s\n  🚀 TTFB: %{time_starttransfer}s\n  📦 Size: %{size_download} bytes\n  ⚡ Speed: %{speed_download} bytes/sec\n\n" \
  -r 0-10485760 https://cdn-video.kinky-thots.com/porn/Haley/Haley_Blowjob_1.mp4 2>/dev/null || echo "  ⚠️  CDN not configured or content not available"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${GREEN}✓ Test Complete${NC}"
echo ""
echo "💡 Tips:"
echo "  • Lower TTFB (Time to First Byte) = Better CDN performance"
echo "  • CDN should be 2-5x faster for large files"
echo "  • Run test multiple times for accurate average"
echo "  • Test from different locations for geographic comparison"
echo ""
echo "📊 For detailed analysis, use:"
echo "  • Browser DevTools (F12 → Network tab)"
echo "  • https://www.webpagetest.org/"
echo "  • https://gtmetrix.com/"
echo ""
