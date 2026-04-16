<?php
// Dispatcher Auxiliar de Rotas

$basePath = __DIR__ . '/pages';
$method = $_SERVER['REQUEST_METHOD'];

// Roteamento padrão
if ($requestUri === '/' || $requestUri === '') {
    require $basePath . '/public/home.php';
    exit;
}

// Sitemap dinâmico
if ($requestUri === '/sitemap.xml') {
    require $basePath . '/public/sitemap.php';
    exit;
}

// Rotas de Autenticação
if (str_starts_with($requestUri, '/auth/')) {
    $action = str_replace('/auth/', '', $requestUri);
    $authFile = $basePath . "/auth/{$action}.php";
    
    if (file_exists($authFile)) {
        require $authFile;
        exit;
    }
}

// Rotas da API
if (str_starts_with($requestUri, '/api/')) {
    header('Content-Type: application/json');
    $endpoint = str_replace('/api/', '', $requestUri);
    $apiFile = __DIR__ . "/api/endpoints/{$endpoint}.php";
    
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Rotas do Dashboard
if (str_starts_with($requestUri, '/dashboard')) {
    require_login(); // Protege o acesso ao dashboard
    
    $path = str_replace('/dashboard', '', $requestUri);
    if (empty($path) || $path === '/') {
        require $basePath . '/dashboard/index.php';
        exit;
    }
    
    $dashboardFile = $basePath . "/dashboard{$path}.php";
    if (file_exists($dashboardFile)) {
        require $dashboardFile;
        exit;
    }
    
    // Se chegou aqui, a página do dashboard não existe, não deve cair pro resolver
    http_response_code(404);
    echo "Página interna do Dashboard não encontrada: " . htmlspecialchars($path);
    exit;
}

// Rotas do HUB (SuperAdmin)
if (str_starts_with($requestUri, '/hub')) {
    require_login(); // Protege o acesso
    
    $path = str_replace('/hub', '', $requestUri);
    if (empty($path) || $path === '/') {
        require $basePath . '/hub/index.php';
        exit;
    }
    
    // Tratamento clean-uris igual do dashboard
    $hubFile = $basePath . "/hub{$path}.php";
    if (file_exists($hubFile)) {
        require $hubFile;
        exit;
    }
}

// Resolver Slug (Site do Usuário)
// Se nada foi capturado nas rotas fixas, assumimos que é uma tentativa de carregar o site OnePage (Ex: /minha-empresa)
require $basePath . '/site/resolver.php';

// Fallback preventivo (O resolver cuida do 404 agora)
exit;
