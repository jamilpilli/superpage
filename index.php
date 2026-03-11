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

// 1. Detectar requisição HUB (SuperAdmin)
if (strpos($host, 'hub.') === 0) {
    require __DIR__ . '/hub_index.php';
    exit;
}

// 2. Verificar redirecionamentos 301 ativos na tabela `redirects`
// Busca pelo path relativo sem base (ex: /meu-antigo-slug)
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

// 3. Resolver domínio customizado
// Verifica se o HOST atual é um domínio customizado de algum cliente
$isLocalhost = in_array($host, ['localhost', '127.0.0.1']);
if (!$isLocalhost && strpos($host, 'superpage.com.br') === false) {
    // Host não é o domínio principal — tentar resolver como domínio customizado
    try {
        $siteByDomain = db_fetch_one(
            "SELECT id, slug FROM sites WHERE domain = :domain AND status = 'active' LIMIT 1",
            [':domain' => $host]
        );
        if ($siteByDomain) {
            // Sobres creve $requestUri para o slug do site dono do domínio
            $requestUri = '/' . $siteByDomain['slug'];
        }
    } catch (\PDOException $e) {
        // Ignorar se tabela não existir ainda
    }
}

// 4 & 5. Resolver roteamento interno e slugs
require __DIR__ . '/router.php';
