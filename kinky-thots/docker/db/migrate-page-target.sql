-- Add page_target column to images table
-- Allows associating gallery uploads with a specific page (sissy, buster, index)
ALTER TABLE images
  ADD COLUMN page_target ENUM('index', 'sissy', 'buster') NOT NULL DEFAULT 'index'
  AFTER file_path;

ALTER TABLE images
  ADD INDEX idx_page_target (page_target);
