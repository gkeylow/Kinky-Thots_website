#!/bin/bash
# PushrCDN Configuration Checker
# Tests if CDN zones are properly configured and serving content

API_KEY="${PUSHR_API_KEY:?Set PUSHR_API_KEY env var}"
API_URL="https://www.pushrcdn.com/api/v3"
ORIGIN_URL="https://kinky-thots.com"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "======================================"
echo "  PushrCDN Configuration Checker"
echo "======================================"
echo ""

# Function to check zone details
check_zone() {
    local zone_id=$1
    local zone_name=$2
    
    echo -e "${BLUE}Checking Zone: $zone_name (ID: $zone_id)${NC}"
    echo "--------------------------------------"
    
    # Get zone details
    response=$(curl -s -d "zone_id=$zone_id" \
        -H "Accept: application/json" \
        -H "APIKEY: $API_KEY" \
        -X POST "$API_URL/zones/get")
    
    # Check if request succeeded
    if echo "$response" | grep -q '"zone"'; then
        echo -e "${GREEN}✓${NC} Zone exists and API is accessible"
        
        # Extract zone details
        zone_pull=$(echo "$response" | grep -o '"zone_pull":"[^"]*"' | cut -d'"' -f4)
        zone_active=$(echo "$response" | grep -o '"zone_active":"[^"]*"' | cut -d'"' -f4)
        zone_storage=$(echo "$response" | grep -o '"zone_storage":"[^"]*"' | cut -d'"' -f4)
        
        # Check zone type
        if [ "$zone_pull" = "1" ]; then
            echo -e "${YELLOW}⚠${NC}  Zone Type: PULL (requires origin configuration)"
            echo "   You need to configure the origin URL in PushrCDN dashboard"
        else
            echo -e "${GREEN}✓${NC} Zone Type: STORAGE/PUSH"
        fi
        
        # Check if active
        if [ "$zone_active" = "1" ]; then
            echo -e "${GREEN}✓${NC} Zone Status: ACTIVE"
        else
            echo -e "${RED}✗${NC} Zone Status: INACTIVE"
        fi
        
        # Storage info
        echo "   Storage Limit: ${zone_storage}GB"
        
    else
        echo -e "${RED}✗${NC} Failed to get zone details"
        echo "   Response: $response"
    fi
    
    echo ""
}

# Function to test if CDN is serving content
test_cdn_serving() {
    local test_url=$1
    local zone_name=$2
    
    echo -e "${BLUE}Testing if CDN serves: $zone_name${NC}"
    echo "--------------------------------------"
    echo "Test URL: $test_url"
    
    # Test origin URL
    echo -n "Origin response: "
    origin_status=$(curl -s -o /dev/null -w "%{http_code}" "$test_url")
    if [ "$origin_status" = "200" ]; then
        echo -e "${GREEN}$origin_status OK${NC}"
    else
        echo -e "${RED}$origin_status FAILED${NC}"
    fi
    
    # Check for CDN headers
    echo -n "Checking for CDN headers: "
    headers=$(curl -s -I "$test_url")
    
    if echo "$headers" | grep -qi "pushr\|r-cdn\|x-cache\|cf-cache"; then
        echo -e "${GREEN}✓ CDN headers detected${NC}"
        echo "$headers" | grep -i "cache\|cdn\|pushr\|server" | sed 's/^/   /'
    else
        echo -e "${YELLOW}⚠ No CDN headers detected${NC}"
        echo "   This URL is being served directly from origin"
        echo "   CDN is NOT configured to serve this content"
    fi
    
    echo ""
}

# Function to check if files exist in zone
check_zone_files() {
    local zone_id=$1
    local zone_name=$2
    
    echo -e "${BLUE}Checking files in zone: $zone_name${NC}"
    echo "--------------------------------------"
    
    # Try to list files (may not be supported in all zone types)
    response=$(curl -s -d "zone_id=$zone_id" \
        -H "Accept: application/json" \
        -H "APIKEY: $API_KEY" \
        -X POST "$API_URL/files/list" 2>&1)
    
    if echo "$response" | grep -q '"file"'; then
        file_count=$(echo "$response" | grep -o '"file_name"' | wc -l)
        echo -e "${GREEN}✓${NC} Found $file_count files in zone"
    elif echo "$response" | grep -q "404"; then
        echo -e "${YELLOW}⚠${NC}  File listing not available (normal for pull zones)"
    else
        echo -e "${YELLOW}⚠${NC}  No files found or listing not supported"
    fi
    
    echo ""
}

# Check API connectivity
echo -e "${BLUE}Testing API Connectivity${NC}"
echo "--------------------------------------"
zones_response=$(curl -s -H "Accept: application/json" -H "APIKEY: $API_KEY" -X POST "$API_URL/zones/list")

if echo "$zones_response" | grep -q '"zone"'; then
    echo -e "${GREEN}✓${NC} API is accessible"
    zone_count=$(echo "$zones_response" | grep -o '"zone_id"' | wc -l)
    echo "   Found $zone_count zones"
else
    echo -e "${RED}✗${NC} API connection failed"
    echo "   Response: $zones_response"
    exit 1
fi
echo ""

# Check each zone
check_zone "6292" "images"
check_zone "6293" "videos"
check_zone "6294" "my-images"
check_zone "6295" "my-videos"

# Test actual content serving
echo "======================================"
echo "  Testing Content Delivery"
echo "======================================"
echo ""

# Test image
test_cdn_serving "$ORIGIN_URL/uploads/1765147409150_IMG_0569.GIF" "Gallery Image"

# Test video
test_cdn_serving "$ORIGIN_URL/porn/kinky-thots-shorts/IMG_4210.mp4" "Video"

# Test animated gif from porn directory
test_cdn_serving "$ORIGIN_URL/porn/kinky-thots-animated-gifs/IMG_0569.GIF" "Animated GIF"

# Check prefetch functionality
echo "======================================"
echo "  Testing Prefetch API"
echo "======================================"
echo ""

echo -e "${BLUE}Testing prefetch for image zone${NC}"
echo "--------------------------------------"
prefetch_response=$(curl -s -d "zone_id=6292" \
    -d "url=$ORIGIN_URL/uploads/1765147409150_IMG_0569.GIF" \
    -H "Accept: application/json" \
    -H "APIKEY: $API_KEY" \
    -X POST "$API_URL/prefetch")

if echo "$prefetch_response" | grep -q '"status":"success"'; then
    echo -e "${GREEN}✓${NC} Prefetch API works"
else
    echo -e "${RED}✗${NC} Prefetch failed"
    echo "   Response: $prefetch_response"
fi
echo ""

# Summary and recommendations
echo "======================================"
echo "  Summary & Recommendations"
echo "======================================"
echo ""

echo -e "${YELLOW}IMPORTANT:${NC}"
echo "1. Your zones are in PULL mode"
echo "2. You MUST configure the origin URL in PushrCDN dashboard:"
echo "   - Go to https://www.pushrcdn.com"
echo "   - For each zone, set Origin URL to: $ORIGIN_URL"
echo "   - Get the CDN URL (e.g., https://c6292z6292.r-cdn.com)"
echo ""
echo "3. After configuration, you need to:"
echo "   - Update your site to use CDN URLs instead of origin URLs"
echo "   - OR use a CNAME to point cdn.kinky-thots.com to the CDN"
echo ""
echo -e "${BLUE}Current Status:${NC}"
if echo "$headers" | grep -qi "pushr\|r-cdn\|x-cache"; then
    echo -e "${GREEN}✓${NC} CDN is serving content"
else
    echo -e "${RED}✗${NC} CDN is NOT serving content (origin only)"
    echo "   Prefetch only warms cache, doesn't serve content"
    echo "   You need to configure pull zones or switch to storage zones"
fi
echo ""
