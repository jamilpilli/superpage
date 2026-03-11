CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    key_value TEXT,
    group_name VARCHAR(50) DEFAULT 'general',
    type ENUM('string', 'boolean', 'json', 'integer') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE redirects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    old_url VARCHAR(255) NOT NULL,
    new_url VARCHAR(255) NOT NULL,
    site_id INT DEFAULT NULL,
    status_code INT DEFAULT 301,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_old_url (old_url, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hub_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    old_data JSON,
    new_data JSON,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_action (admin_id, action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Settings
INSERT INTO settings (key_name, key_value, group_name, type) VALUES 
('site_name', 'Superpage Platform', 'general', 'string'),
('allow_registration', 'true', 'general', 'boolean'),
('annual_plan_price', '199.90', 'billing', 'string'),
('smtp_host', 'smtp.mailtrap.io', 'email', 'string');
