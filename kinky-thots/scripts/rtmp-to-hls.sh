#!/bin/bash
# RTMP to HLS Transcoder for Red5
# Converts RTMP stream from Red5 to HLS for browser playback

RTMP_URL="rtmp://127.0.0.1:1935/live/stream"
HLS_DIR="/var/www/kinky-thots/hls"
PLAYLIST="playlist.m3u8"

# Clean up old segments on start
rm -f "$HLS_DIR"/*.ts "$HLS_DIR"/*.m3u8 2>/dev/null

echo "Starting RTMP to HLS transcoder..."
echo "RTMP Source: $RTMP_URL"
echo "HLS Output: $HLS_DIR/$PLAYLIST"

# FFmpeg transcoding with HLS output
# -re: Read input at native frame rate
# -i: Input RTMP stream
# -c:v copy: Copy video codec (no re-encoding for low latency)
# -c:a aac: Transcode audio to AAC for HLS compatibility
# -f hls: Output format
# -hls_time 2: 2 second segments
# -hls_list_size 5: Keep 5 segments in playlist
# -hls_flags delete_segments: Auto-delete old segments
# -hls_allow_cache 0: Disable caching for live stream

exec ffmpeg -hide_banner -loglevel warning \
    -re -i "$RTMP_URL" \
    -c:v copy \
    -c:a aac -b:a 128k \
    -f hls \
    -hls_time 2 \
    -hls_list_size 5 \
    -hls_flags delete_segments+append_list \
    -hls_allow_cache 0 \
    -hls_segment_filename "$HLS_DIR/segment_%03d.ts" \
    "$HLS_DIR/$PLAYLIST"
