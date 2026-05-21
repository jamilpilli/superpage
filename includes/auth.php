<?php
// Autenticação e Autorização

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Session timeout: expirar após 30 minutos de inatividade
define('SESSION_TIMEOUT', 1800);
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_logged_user() {
    if (!is_logged_in()) return null;
    
    global $pdo;
    static $user = null;
    
    if ($user === null) {
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    
    return $user;
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/auth/login');
    }
}

function require_role($role) {
    $user = get_logged_user();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        die("Acesso Negado.");
    }
}

// Verifica se o utilizador logado pode aceder a um site específico:
// — dono do site, OU partner com esse cliente associado, OU admin
function can_access_site($siteId) {
    $user = get_logged_user();
    if (!$user) return false;
    if ($user['role'] === 'admin') return true;

    global $pdo;

    // Dono directo
    $stmt = $pdo->prepare("SELECT id FROM sites WHERE id = :sid AND user_id = :uid");
    $stmt->execute([':sid' => $siteId, ':uid' => $user['id']]);
    if ($stmt->fetch()) return true;

    // Partner: o dono do site é cliente deste partner
    if ($user['role'] === 'partner') {
        $stmt = $pdo->prepare("
            SELECT s.id FROM sites s
            JOIN partner_clients pc ON pc.client_id = s.user_id
            WHERE s.id = :sid AND pc.partner_id = :pid
        ");
        $stmt->execute([':sid' => $siteId, ':pid' => $user['id']]);
        if ($stmt->fetch()) return true;
    }

    return false;
}

// Devolve todos os sites acessíveis ao utilizador logado
function get_accessible_sites() {
    $user = get_logged_user();
    if (!$user) return [];

    global $pdo;

    if ($user['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT s.*, u.name as owner_name FROM sites s JOIN users u ON u.id = s.user_id WHERE s.status != 'inactive' ORDER BY s.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    if ($user['role'] === 'partner') {
        $stmt = $pdo->prepare("
            SELECT s.*, u.name as owner_name FROM sites s
            JOIN users u ON u.id = s.user_id
            WHERE s.user_id = :uid AND s.status != 'inactive'
            UNION
            SELECT s.*, u.name as owner_name FROM sites s
            JOIN users u ON u.id = s.user_id
            JOIN partner_clients pc ON pc.client_id = s.user_id
            WHERE pc.partner_id = :uid AND s.status != 'inactive'
            ORDER BY created_at DESC
        ");
        $stmt->execute([':uid' => $user['id']]);
        return $stmt->fetchAll();
    }

    // Cliente normal — só os seus
    $stmt = $pdo->prepare("SELECT s.*, u.name as owner_name FROM sites s JOIN users u ON u.id = s.user_id WHERE s.user_id = :uid AND s.status != 'inactive' ORDER BY s.created_at DESC");
    $stmt->execute([':uid' => $user['id']]);
    return $stmt->fetchAll();
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
