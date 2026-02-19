#!/bin/bash
# List all files in PushrCDN storage zone

API_KEY="${PUSHR_API_KEY:?Set PUSHR_API_KEY env var}"
ZONE_ID="${1:-6295}"  # Default to my-videos zone
OUTPUT_FILE="/var/www/kinky-thots/cdn-files-zone-${ZONE_ID}.json"

echo "Fetching file list from PushrCDN zone $ZONE_ID..."
echo ""

# Get file list from API
response=$(curl -s -X GET \
  "https://www.pushrcdn.com/api/v3/files/list?zone_id=$ZONE_ID" \
  -H "APIKEY: $API_KEY" \
  -H "Accept: application/json")

# Save to file
echo "$response" > "$OUTPUT_FILE"

# Check if successful
if echo "$response" | grep -q '"status":"success"' || echo "$response" | grep -q '"files"'; then
    echo "✓ File list saved to: $OUTPUT_FILE"
    echo ""
    
    # Pretty print if jq is available
    if command -v jq &> /dev/null; then
        echo "File count:"
        echo "$response" | jq '.files | length' 2>/dev/null || echo "N/A"
        echo ""
        echo "First 10 files:"
        echo "$response" | jq -r '.files[0:10][] | .path' 2>/dev/null || echo "Unable to parse"
    else
        echo "Install 'jq' for pretty output: apt-get install jq"
        echo ""
        echo "Raw response preview:"
        echo "$response" | head -50
    fi
else
    echo "✗ Failed to fetch file list"
    echo ""
    echo "Response:"
    echo "$response"
fi

echo ""
echo "Full JSON saved to: $OUTPUT_FILE"
