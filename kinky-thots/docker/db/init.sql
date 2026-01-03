-- Initialize kinky_thots database tables

-- Images/videos table
CREATE TABLE IF NOT EXISTS images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    file_type VARCHAR(20) NOT NULL DEFAULT 'image',
    file_path VARCHAR(255) NOT NULL DEFAULT '/uploads',
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_file_type (file_type),
    INDEX idx_upload_time (upload_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_color VARCHAR(7) DEFAULT '#0bd0f3',

    -- Subscription fields
    subscription_tier ENUM('free', 'basic', 'premium', 'lifetime', 'vip') DEFAULT 'free',
    subscription_status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active',
    subscription_expires_at TIMESTAMP NULL,
    payment_customer_id VARCHAR(255) NULL,
    payment_provider VARCHAR(50) NULL,

    -- Account metadata
    is_admin BOOLEAN DEFAULT FALSE,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_subscription_status (subscription_status),
    INDEX idx_payment_customer_id (payment_customer_id),
    INDEX idx_password_reset_expires (password_reset_expires),
    INDEX idx_subscription_expires (subscription_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
