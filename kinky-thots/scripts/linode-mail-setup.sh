#!/bin/bash
# Docker Mailserver Setup for Linode VPS
# Run as root on fresh Debian/Ubuntu

set -e

echo "=== Updating system ==="
apt update && apt upgrade -y

echo "=== Installing Docker ==="
apt install -y ca-certificates curl gnupg
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

echo "=== Creating mail directories ==="
mkdir -p /opt/mailserver/{mail-data,mail-state,mail-logs,config}
cd /opt/mailserver

echo "=== Creating docker-compose.yml ==="
cat > docker-compose.yml << 'EOF'
services:
  mailserver:
    image: ghcr.io/docker-mailserver/docker-mailserver:latest
    container_name: mailserver
    hostname: mail.kinky-thots.com
    ports:
      - "25:25"
      - "465:465"
      - "587:587"
      - "993:993"
    volumes:
      - ./mail-data/:/var/mail/
      - ./mail-state/:/var/mail-state/
      - ./mail-logs/:/var/log/mail/
      - ./config/:/tmp/docker-mailserver/
      - /etc/localtime:/etc/localtime:ro
    environment:
      - ENABLE_RSPAMD=1
      - ENABLE_CLAMAV=0
      - ENABLE_FAIL2BAN=1
      - SSL_TYPE=self-signed
      - PERMIT_DOCKER=none
      - POSTMASTER_ADDRESS=admin@kinky-thots.com
      - ONE_DIR=1
    cap_add:
      - NET_ADMIN
    restart: unless-stopped
EOF

echo "=== Generating SSL certificates ==="
mkdir -p config/ssl/demoCA
openssl req -x509 -nodes -days 3650 -newkey rsa:4096 \
  -keyout config/ssl/mail.kinky-thots.com-key.pem \
  -out config/ssl/mail.kinky-thots.com-cert.pem \
  -subj "/C=US/ST=State/L=City/O=KinkyThots/CN=mail.kinky-thots.com"
cp config/ssl/mail.kinky-thots.com-cert.pem config/ssl/demoCA/cacert.pem

echo "=== Opening firewall ports ==="
apt install -y ufw
ufw allow 22/tcp
ufw allow 25/tcp
ufw allow 465/tcp
ufw allow 587/tcp
ufw allow 993/tcp
ufw --force enable

echo "=== Starting mailserver ==="
docker compose up -d

echo "=== Waiting for mailserver to initialize ==="
sleep 30

echo "=== Creating email accounts ==="
echo "Run these commands manually with your chosen passwords:"
echo "  docker exec mailserver setup email add admin@kinky-thots.com YOUR_PASSWORD"
echo "  docker exec mailserver setup email add sissylonglegs@kinky-thots.com YOUR_PASSWORD"

echo "=== Generating DKIM keys ==="
docker exec mailserver setup config dkim

echo ""
echo "=========================================="
echo "  SETUP COMPLETE!"
echo "=========================================="
echo ""
echo "Mail server IP: $(curl -s ifconfig.me)"
echo ""
echo "DNS Records needed:"
echo "  mail  A     $(curl -s ifconfig.me)"
echo "  @     MX    mail.kinky-thots.com (priority 10)"
echo ""
echo "DKIM record (add to DNS):"
cat config/opendkim/keys/kinky-thots.com/mail.txt 2>/dev/null || echo "Run: cat /opt/mailserver/config/opendkim/keys/kinky-thots.com/mail.txt"
echo ""
echo "Set PTR record in Linode dashboard:"
echo "  $(curl -s ifconfig.me) -> mail.kinky-thots.com"
echo ""
echo "Test with: docker exec mailserver setup email list"
