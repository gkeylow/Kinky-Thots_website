#!/bin/bash
# Daily backup script - emails critical data via Linode mail server
# Runs via cron at 3am daily

set -e

BACKUP_DIR="/tmp/kinky-backup"
DATE=$(date +%F)
BACKUP_FILE="kinky-thots-backup-${DATE}.tar.gz"
SITE_DIR="/var/www/kinky-thots"

# SMTP settings (Linode mail server)
SMTP_HOST="mail.kinky-thots.com"
SMTP_PORT="587"
SMTP_USER="admin@kinky-thots.com"
SMTP_PASS="${SMTP_PASSWORD:?Set SMTP_PASSWORD env var}"
EMAIL_TO="admin@kinky-thots.com"
EMAIL_FROM="backup@kinky-thots.com"

# Cleanup old temp files
rm -rf "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

echo "[$(date)] Starting backup: $DATE"

# 1. Database dump
echo "[$(date)] Dumping database..."
docker exec kinky-db mysqldump -u root -p"${MARIADB_PASSWORD:?Set MARIADB_PASSWORD env var}" --all-databases > "$BACKUP_DIR/database.sql" 2>/dev/null

# 2. Copy critical config files
echo "[$(date)] Copying config files..."
cp "$SITE_DIR/.env" "$BACKUP_DIR/"
cp -r "$SITE_DIR/config" "$BACKUP_DIR/" 2>/dev/null || true
cp -r "$SITE_DIR/data" "$BACKUP_DIR/" 2>/dev/null || true

# 3. Create compressed archive
echo "[$(date)] Creating archive..."
cd "$BACKUP_DIR"
tar -czf "/tmp/$BACKUP_FILE" .

BACKUP_SIZE=$(du -h "/tmp/$BACKUP_FILE" | cut -f1)
echo "[$(date)] Backup size: $BACKUP_SIZE"

# 4. Create email with attachment
EMAIL_FILE="/tmp/backup-email.txt"
cat > "$EMAIL_FILE" << EMAILEOF
From: Kinky-Thots Backup <$EMAIL_FROM>
To: $EMAIL_TO
Subject: Daily Backup - $DATE ($BACKUP_SIZE)
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="BACKUP_BOUNDARY"

--BACKUP_BOUNDARY
Content-Type: text/plain; charset=utf-8

Daily backup for kinky-thots.xxx
Date: $DATE
Size: $BACKUP_SIZE

Contents:
- database.sql (full MariaDB dump)
- .env (environment variables)
- config/ (configuration files)
- data/ (video manifest, etc.)

Note: uploads/ not included (too large for email).
Run weekly manual backup for uploads if needed.

--BACKUP_BOUNDARY
Content-Type: application/gzip; name="$BACKUP_FILE"
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="$BACKUP_FILE"

$(base64 "/tmp/$BACKUP_FILE")

--BACKUP_BOUNDARY--
EMAILEOF

# 5. Send email via SMTP
echo "[$(date)] Sending email via $SMTP_HOST..."
curl --silent --ssl-reqd \
    --url "smtp://$SMTP_HOST:$SMTP_PORT" \
    --user "$SMTP_USER:$SMTP_PASS" \
    --mail-from "$EMAIL_FROM" \
    --mail-rcpt "$EMAIL_TO" \
    --upload-file "$EMAIL_FILE"

# 6. Cleanup
rm -rf "$BACKUP_DIR" "/tmp/$BACKUP_FILE" "$EMAIL_FILE"

echo "[$(date)] Backup complete and emailed to $EMAIL_TO"
