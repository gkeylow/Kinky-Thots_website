-- Discord OAuth support
-- Run once: adds discord columns and makes password_hash nullable for OAuth-only accounts
ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS discord_id VARCHAR(30) NULL UNIQUE AFTER email;
ALTER TABLE users ADD COLUMN IF NOT EXISTS discord_username VARCHAR(100) NULL AFTER discord_id;
