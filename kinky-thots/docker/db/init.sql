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
