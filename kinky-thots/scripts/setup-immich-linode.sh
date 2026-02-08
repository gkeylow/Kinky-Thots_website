#!/bin/bash
# =============================================================
# Immich Setup Script for Fresh Ubuntu Linode
# =============================================================
# Run as root on a fresh Ubuntu 24.04 Linode instance
# Usage: bash setup-immich-linode.sh
# =============================================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# Must run as root
[[ $EUID -ne 0 ]] && err "This script must be run as root"

echo "============================================="
echo "  Immich Setup - Fresh Ubuntu Linode"
echo "============================================="
echo ""

# -------------------------------------------
# 1. System Update
# -------------------------------------------
log "Updating system packages..."
apt-get update -qq && apt-get upgrade -y -qq

# -------------------------------------------
# 2. Install Docker (official method)
# -------------------------------------------
if command -v docker &>/dev/null; then
    log "Docker already installed: $(docker --version)"
else
    log "Installing Docker..."
    apt-get install -y -qq ca-certificates curl gnupg

    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc

    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
    https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
    | tee /etc/apt/sources.list.d/docker.list > /dev/null

    apt-get update -qq
    apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

    systemctl enable docker
    systemctl start docker
    log "Docker installed: $(docker --version)"
fi

# Verify docker compose works
docker compose version &>/dev/null || err "docker compose not available"
log "Docker Compose: $(docker compose version --short)"

# -------------------------------------------
# 3. Create Immich directory
# -------------------------------------------
IMMICH_DIR="/opt/immich"
mkdir -p "$IMMICH_DIR"
cd "$IMMICH_DIR"
log "Immich directory: $IMMICH_DIR"

# -------------------------------------------
# 4. Download Immich docker-compose and env
# -------------------------------------------
log "Downloading Immich docker-compose.yml..."
wget -q -O docker-compose.yml \
    https://github.com/immich-app/immich/releases/latest/download/docker-compose.yml

log "Downloading Immich .env template..."
wget -q -O .env \
    https://github.com/immich-app/immich/releases/latest/download/example.env

# -------------------------------------------
# 5. Configure .env
# -------------------------------------------
UPLOAD_DIR="/opt/immich/uploads"
DB_DIR="/opt/immich/db"
mkdir -p "$UPLOAD_DIR" "$DB_DIR"

# Generate a random DB password
DB_PASSWORD=$(openssl rand -base64 24 | tr -d '/+=' | head -c 32)

# Update .env values
sed -i "s|UPLOAD_LOCATION=.*|UPLOAD_LOCATION=${UPLOAD_DIR}|" .env
sed -i "s|DB_DATA_LOCATION=.*|DB_DATA_LOCATION=${DB_DIR}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env

# Pin to latest stable v2
sed -i "s|^# IMMICH_VERSION=.*|IMMICH_VERSION=release|" .env 2>/dev/null
grep -q "IMMICH_VERSION" .env || echo "IMMICH_VERSION=release" >> .env

log "Configured .env (DB password auto-generated)"

# -------------------------------------------
# 6. Open firewall (if UFW is active)
# -------------------------------------------
if command -v ufw &>/dev/null && ufw status | grep -q "active"; then
    ufw allow 2283/tcp comment "Immich Web UI"
    log "Firewall: port 2283 opened"
else
    warn "UFW not active - make sure port 2283 is accessible"
fi

# -------------------------------------------
# 7. Start Immich
# -------------------------------------------
log "Starting Immich containers..."
cd "$IMMICH_DIR"
docker compose up -d

# Wait for containers to be healthy
log "Waiting for Immich to start (this may take a minute)..."
sleep 15

# -------------------------------------------
# 8. Status check
# -------------------------------------------
echo ""
echo "============================================="
echo "  Immich Installation Complete"
echo "============================================="
echo ""
docker compose ps
echo ""
SERVER_IP=$(hostname -I | awk '{print $1}')
log "Web UI: http://${SERVER_IP}:2283"
log "Upload location: ${UPLOAD_DIR}"
log "DB location: ${DB_DIR}"
log "DB password: ${DB_PASSWORD}"
echo ""
warn "SAVE THE DB PASSWORD ABOVE - you'll need it for backups"
echo ""
log "Next steps:"
echo "   1. Open http://${SERVER_IP}:2283 in your browser"
echo "   2. Click 'Getting Started' to create admin account"
echo "   3. Configure object storage in Admin > Settings > Storage"
echo ""
