-- Migration 002: Add avatar_url and bio columns for user profiles
-- Run this on existing databases to update schema

-- Add avatar_url column for profile pictures (CDN URL)
ALTER TABLE users
ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(500) NULL AFTER display_color;

-- Add bio column for user biography
ALTER TABLE users
ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER avatar_url;

-- Verify changes
DESCRIBE users;
SELECT 'Migration 002 completed successfully - avatar_url and bio columns added' AS status;
