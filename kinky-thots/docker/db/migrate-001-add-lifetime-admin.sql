-- Migration 001: Add lifetime tier and is_admin column
-- Run this on existing databases to update schema

-- Add 'lifetime' to subscription_tier ENUM
ALTER TABLE users
MODIFY COLUMN subscription_tier ENUM('free', 'basic', 'premium', 'lifetime', 'vip') DEFAULT 'free';

-- Add is_admin column if it doesn't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE AFTER payment_provider;

-- Add missing indexes for better query performance
ALTER TABLE users
ADD INDEX IF NOT EXISTS idx_payment_customer_id (payment_customer_id),
ADD INDEX IF NOT EXISTS idx_password_reset_expires (password_reset_expires),
ADD INDEX IF NOT EXISTS idx_subscription_expires (subscription_expires_at);

-- Verify changes
SELECT 'Migration 001 completed successfully' AS status;
