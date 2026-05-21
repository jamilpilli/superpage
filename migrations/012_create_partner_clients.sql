CREATE TABLE partner_clients (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    client_id  INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_partner_client (partner_id, client_id),
    FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id)  REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
