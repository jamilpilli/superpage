<?php
// API - Endpoints de Gerenciamento de Blocos

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Requisito: Usuário logado
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}
$user = get_logged_user();

$method = $_SERVER['REQUEST_METHOD'];

// Helper para validar ownership da página/site
// Admins têm acesso a qualquer página
function verify_page_ownership($pageId, $userId) {
    global $pdo;
    $role = $pdo->prepare("SELECT role FROM users WHERE id = :uid");
    $role->execute([':uid' => $userId]);
    if ($role->fetchColumn() === 'admin') return true;

    $stmt = $pdo->prepare("
        SELECT p.id FROM pages p
        JOIN sites s ON p.site_id = s.id
        WHERE p.id = :pid AND s.user_id = :uid
    ");
    $stmt->execute([':pid' => $pageId, ':uid' => $userId]);
    return $stmt->fetchColumn() !== false;
}

if ($method === 'GET') {
    // Listar blocos de uma página
    $pageId = $_GET['page_id'] ?? 0;
    
    if (!$pageId || !verify_page_ownership($pageId, $user['id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado à esta página.']);
        exit;
    }
    
    $blocks = db_fetch_all("SELECT id, type, sort_order, config FROM blocks WHERE page_id = :pid ORDER BY sort_order ASC", [':pid' => $pageId]);
    
    // Decodifica os JSONs para enviar como objeto puro
    foreach ($blocks as &$block) {
        $block['config'] = $block['config'] ? json_decode($block['config'], true) : new stdClass();
    }
    
    echo json_encode(['data' => $blocks]);
    exit;
}

if ($method === 'POST') {
    // Adicionar novo bloco ou Reordenar
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'reorder') {
        // Recebe [{id: 1, sort_order: 0}, {id: 2, sort_order: 1}]
        $pageId = $data['page_id'] ?? 0;
        $orderInfo = $data['blocks'] ?? [];
        
        if (!$pageId || !verify_page_ownership($pageId, $user['id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Página inválida.']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE blocks SET sort_order = :ord WHERE id = :id AND page_id = :pid");
            
            foreach ($orderInfo as $b) {
                // A restrição `page_id = :pid` atua como proteção extra de ownership
                $stmt->execute([
                    ':ord' => $b['sort_order'],
                    ':id' => $b['id'],
                    ':pid' => $pageId
                ]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao reordenar: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'add') {
        $pageId = $data['page_id'] ?? 0;
        $type = $data['type'] ?? '';
        
        if (!$pageId || empty($type) || !verify_page_ownership($pageId, $user['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados inválidos.']);
            exit;
        }
        
        // Pega a o maior sort_order atual + 1
        $maxOrder = db_fetch_one("SELECT MAX(sort_order) as m FROM blocks WHERE page_id = :pid", [':pid' => $pageId]);
        $nextOrder = isset($maxOrder['m']) ? (int)$maxOrder['m'] + 1 : 0;
        
        $defaultTitles = [
            'header' => 'Cabeçalho',
            'hero' => 'Capa',
            'about' => 'Sobre Nós',
            'services' => 'Nossos Serviços',
            'products' => 'Nossos Produtos',
            'gallery' => 'Galeria de Fotos',
            'videos' => 'Vídeos',
            'testimonials' => 'Depoimentos',
            'contact' => 'Entre em Contato',
            'footer' => 'Rodapé'
        ];
        $blockTitle = $defaultTitles[$type] ?? ucfirst($type);
        
        $blockId = db_insert('blocks', [
            'page_id' => $pageId,
            'type' => $type,
            'sort_order' => $nextOrder,
            'config' => json_encode(['title' => $blockTitle])
        ]);
        
        echo json_encode(['success' => true, 'block_id' => $blockId, 'sort_order' => $nextOrder]);
        exit;
    }
}

if ($method === 'PUT') {
    // Atualizar Configuração do Bloco
    parse_str(file_get_contents("php://input"), $_PUT);
    $data = json_decode(file_get_contents('php://input'), true);
    
    $blockId = $data['block_id'] ?? 0;
    $config = $data['config'] ?? null;
    
    // Validar ownership através de JOIN indireto
    $blockOwner = db_fetch_one("
        SELECT b.id FROM blocks b 
        JOIN pages p ON b.page_id = p.id 
        JOIN sites s ON p.site_id = s.id 
        WHERE b.id = :bid AND s.user_id = :uid
    ", [':bid' => $blockId, ':uid' => $user['id']]);
    
    if (!$blockOwner) {
        http_response_code(403);
        echo json_encode(['error' => 'Bloco inválido.']);
        exit;
    }
    
    db_update('blocks', ['config' => json_encode($config)], 'id = :id', [':id' => $blockId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $blockId = $data['block_id'] ?? 0;
    
    $blockOwner = db_fetch_one("
        SELECT b.id FROM blocks b 
        JOIN pages p ON b.page_id = p.id 
        JOIN sites s ON p.site_id = s.id 
        WHERE b.id = :bid AND s.user_id = :uid
    ", [':bid' => $blockId, ':uid' => $user['id']]);
    
    if (!$blockOwner) {
        http_response_code(403);
        echo json_encode(['error' => 'Bloco inválido.']);
        exit;
    }
    
    $curr = db_fetch_one("SELECT config FROM blocks WHERE id = :id", [':id' => $blockId]);
    $cfg = $curr['config'] ? json_decode($curr['config'], true) : [];
    $cfg['is_active'] = false;
    
    db_update('blocks', ['config' => json_encode($cfg)], 'id = :id', [':id' => $blockId]);
    
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método não suportado.']);
