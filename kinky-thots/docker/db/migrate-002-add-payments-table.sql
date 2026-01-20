-- Migration 002: Add payments table for transaction tracking
-- Run: docker exec -i kinky-db mysql -u root -p kinky_thots < docker/db/migrate-002-add-payments-table.sql

CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id VARCHAR(255) NOT NULL UNIQUE,
    parent_payment_id VARCHAR(255) NULL,
    user_id INT NULL,
    order_id VARCHAR(255) NULL,

    -- Payment details
    price_amount DECIMAL(10,2) NOT NULL,
    price_currency VARCHAR(10) DEFAULT 'usd',
    pay_amount DECIMAL(20,10) NULL,
    pay_currency VARCHAR(20) NULL,
    actually_paid DECIMAL(20,10) NULL,

    -- Status tracking
    payment_status ENUM('waiting', 'confirming', 'confirmed', 'finished', 'partially_paid', 'failed', 'expired', 'refunded') DEFAULT 'waiting',

    -- Subscription info
    tier VARCHAR(50) NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_parent_payment_id (parent_payment_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at),

    -- Foreign key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add yearly to subscription_tier enum if not exists
ALTER TABLE users MODIFY COLUMN subscription_tier ENUM('free', 'basic', 'premium', 'yearly', 'lifetime', 'vip') DEFAULT 'free';
