-- 014_prospecting.sql

-- Add 'draft' status to sites
ALTER TABLE sites MODIFY COLUMN status ENUM('active','inactive','suspended','draft') DEFAULT 'active';

-- Prospect log: tracks when/how client was notified
CREATE TABLE prospect_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    site_id     INT NOT NULL,
    admin_id    INT NOT NULL,
    notified_via ENUM('whatsapp','email','both') NOT NULL,
    notified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id)  REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
