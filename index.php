<?php
// Roteador Principal (Entrada Única)
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$host = $_SERVER['HTTP_HOST'];

// Em ambiente de desenvolvimento local (XAMPP/MAMP), remove o subdiretório base
$baseDir = '/superpage';
if (strpos($requestUri, $baseDir) === 0) {
    $requestUri = substr($requestUri, strlen($baseDir));
}
if ($requestUri === '') {
    $requestUri = '/';
}

define('MAIN_DOMAIN', 'superpage.co.uk');

// 1. Detectar requisição HUB (SuperAdmin)
if (strpos($host, 'hub.') === 0) {
    require __DIR__ . '/hub_index.php';
    exit;
}

// 2. Wildcard subdomain: slug.superpage.co.uk → servir o site do cliente
// Extrai o subdomínio e força $requestUri para /{slug} antes de qualquer rota
$isLocalhost = in_array($host, ['localhost', '127.0.0.1', 'localhost:80']);
$isMainDomain = ($host === MAIN_DOMAIN || $host === 'www.' . MAIN_DOMAIN);
if (!$isLocalhost && !$isMainDomain && str_ends_with($host, '.' . MAIN_DOMAIN)) {
    $subdomain = substr($host, 0, strlen($host) - strlen('.' . MAIN_DOMAIN));
    // Subdomínio simples (sem pontos) — é um slug de cliente
    if (!empty($subdomain) && strpos($subdomain, '.') === false) {
        $requestUri = '/' . $subdomain;
        // Vai directo para o resolver — não precisa de rotas internas
        require __DIR__ . '/pages/site/resolver.php';
        exit;
    }
}

// 3. Verificar redirecionamentos 301 ativos na tabela `redirects`
try {
    $redirect = db_fetch_one(
        "SELECT new_url FROM redirects WHERE old_url = :old AND is_active = 1 LIMIT 1",
        [':old' => $requestUri]
    );
    if ($redirect) {
        header('Location: ' . BASE_URL . $redirect['new_url'], true, 301);
        exit;
    }
} catch (\PDOException $e) {
    // Tabela pode não existir em fases iniciais — ignorar silenciosamente
}

// 4. Resolver domínio customizado (domínio próprio do cliente, ex: minhapizzaria.co.uk)
if (!$isLocalhost && !$isMainDomain && !str_ends_with($host, '.' . MAIN_DOMAIN)) {
    try {
        $siteByDomain = db_fetch_one(
            "SELECT id, slug FROM sites WHERE domain = :domain AND status = 'active' LIMIT 1",
            [':domain' => $host]
        );
        if ($siteByDomain) {
            $requestUri = '/' . $siteByDomain['slug'];
        }
    } catch (\PDOException $e) {
        // Ignorar se tabela não existir ainda
    }
}

// 4 & 5. Resolver roteamento interno e slugs
require __DIR__ . '/router.php';
