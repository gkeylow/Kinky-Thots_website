#!/bin/bash
# Stream Watcher - Starts/stops rtmp-hls based on active RTMP streams
# Monitors Red5 for incoming streams and triggers HLS transcoding

RTMP_URL="rtmp://127.0.0.1:1935/live/stream"
CHECK_INTERVAL=5  # seconds between checks
HLS_SERVICE="rtmp-hls"
HLS_DIR="/var/www/kinky-thots/hls"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

is_stream_active() {
    # Try to probe the RTMP stream with a short timeout
    # Returns 0 if stream is active, 1 if not
    ffprobe -v quiet -timeout 2000000 -i "$RTMP_URL" -show_entries format=duration -of csv=p=0 2>/dev/null
    return $?
}

is_hls_running() {
    systemctl is-active --quiet "$HLS_SERVICE"
    return $?
}

start_hls() {
    if ! is_hls_running; then
        log "Stream detected! Starting HLS transcoder..."
        systemctl start "$HLS_SERVICE"
        log "HLS service started"
    fi
}

stop_hls() {
    if is_hls_running; then
        log "Stream ended. Stopping HLS transcoder..."
        systemctl stop "$HLS_SERVICE"
        log "HLS service stopped"
    fi
    # Clean up leftover HLS segments
    if [ -d "$HLS_DIR" ]; then
        rm -f "$HLS_DIR"/*.ts "$HLS_DIR"/*.m3u8 2>/dev/null
        log "HLS segments cleaned up"
    fi
}

cleanup() {
    log "Stream watcher shutting down..."
    exit 0
}

trap cleanup SIGTERM SIGINT

log "Stream watcher started"
log "Monitoring: $RTMP_URL"
log "Check interval: ${CHECK_INTERVAL}s"

# Track stream state to avoid repeated start/stop
stream_was_active=false

while true; do
    if is_stream_active; then
        if [ "$stream_was_active" = false ]; then
            log "Stream became active"
            start_hls
            stream_was_active=true
        fi
    else
        if [ "$stream_was_active" = true ]; then
            log "Stream became inactive"
            stop_hls
            stream_was_active=false
        fi
    fi

    sleep "$CHECK_INTERVAL"
done
