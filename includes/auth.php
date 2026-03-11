<?php
// Autenticação e Autorização

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
