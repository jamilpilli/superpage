<?php
// Entrada do HUB (SuperAdmin)
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Proteção para o HUB: Apenas usuários com role admin
require_login();
require_role('admin');

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Rotas do HUB
if (empty($requestUri) || $requestUri === '/') {
    require __DIR__ . '/pages/hub/dashboard.php';
    exit;
}

$hubFile = __DIR__ . "/pages/hub{$requestUri}.php";
if (file_exists($hubFile)) {
    require $hubFile;
    exit;
}

// Fallback 404 HUB
http_response_code(404);
echo "404 - Página do HUB não encontrada.";
exit;
