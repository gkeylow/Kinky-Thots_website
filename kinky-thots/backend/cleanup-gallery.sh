#!/bin/bash
# Gallery Cleanup Utility
# Syncs database entries with actual files on disk

UPLOADS_DIR="/var/www/kinky-thots/uploads"
DB_USER="gkeylow"
DB_PASS="${MARIADB_PASSWORD:?Set MARIADB_PASSWORD env var}"
DB_NAME="gallery_db"

echo "=== Gallery Cleanup Utility ==="
echo ""

# Find orphaned database entries (no file on disk)
echo "Checking for orphaned database entries..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -se "SELECT id, filename FROM images" 2>/dev/null | while read id filename; do
    if [ ! -f "$UPLOADS_DIR/$filename" ]; then
        echo "  Found orphaned DB entry: ID=$id, filename=$filename"
        read -p "  Delete from database? (y/n): " confirm
        if [ "$confirm" = "y" ]; then
            mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DELETE FROM images WHERE id=$id" 2>/dev/null
            echo "  ✓ Deleted from database"
        fi
    fi
done

echo ""

# Find orphaned files (no database entry)
echo "Checking for orphaned files..."
cd "$UPLOADS_DIR"
for file in *; do
    if [ -f "$file" ]; then
        count=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -se "SELECT COUNT(*) FROM images WHERE filename='$file'" 2>/dev/null)
        if [ "$count" = "0" ]; then
            echo "  Found orphaned file: $file"
            read -p "  Delete file? (y/n): " confirm
            if [ "$confirm" = "y" ]; then
                rm "$file"
                echo "  ✓ Deleted file"
            fi
        fi
    fi
done

echo ""
echo "=== Summary ==="
db_count=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -se "SELECT COUNT(*) FROM images" 2>/dev/null)
file_count=$(ls -1 "$UPLOADS_DIR" | wc -l)
echo "Database entries: $db_count"
echo "Files on disk: $file_count"

if [ "$db_count" = "$file_count" ]; then
    echo "✓ Database and files are in sync!"
else
    echo "⚠ Warning: Database and files are out of sync"
fi
