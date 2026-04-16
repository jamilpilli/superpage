<?php
// Constantes da aplicação (idempotente — seguro para multiple includes)
if (!defined('APP_NAME')) {
    define('APP_NAME', 'SuperPage');
    define('HASH_COST', 12);
    define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
    define('APP_DEBUG', getenv('APP_DEBUG') === 'true');
    define('UPLOAD_DIR', __DIR__ . '/../uploads');

    // Helper de base path para ambiente Dev XAMPP vs Produção
    $basePathLocal = (strpos($_SERVER['REQUEST_URI'] ?? '', '/superpage') === 0) ? '/superpage' : '';
    define('BASE_URL', $basePathLocal);
}
