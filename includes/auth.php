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

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
