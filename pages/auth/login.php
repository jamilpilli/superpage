<?php
// Autenticação - Login

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    redirect('/dashboard');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Sessão inválida. Tente novamente.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Rate limiting verification
        $limit = db_fetch_one("SELECT attempts, blocked_until FROM rate_limits WHERE ip_address = :ip AND action = 'login'", [':ip' => $ip]);
        if ($limit && $limit['blocked_until'] && strtotime($limit['blocked_until']) > time()) {
            $error = "Muitas tentativas falhas. Tente novamente mais tarde.";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $user = db_fetch_one("SELECT id, password_hash FROM users WHERE email = :email", [':email' => $email]);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Reset rate limit on success
                if ($limit) {
                    db_update('rate_limits', ['attempts' => 0, 'blocked_until' => null], 'ip_address = :ip AND action = "login"', [':ip' => $ip]);
                }
                
                $_SESSION['user_id'] = $user['id'];
                session_regenerate_id(true);
                redirect('/dashboard');
            } else {
                // Registrar tentativa falha
                if ($limit) {
                    $attempts = $limit['attempts'] + 1;
                    $blocked_until = $attempts >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
                    db_update('rate_limits', [
                        'attempts' => $attempts,
                        'blocked_until' => $blocked_until
                    ], 'ip_address = :ip AND action = "login"', [':ip' => $ip]);
                } else {
                    db_insert('rate_limits', [
                        'ip_address' => $ip,
                        'action' => 'login',
                        'attempts' => 1
                    ]);
                }
                $error = "Credenciais inválidas.";
            }
        }
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Acessar <?= APP_NAME ?></h2>
        
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/auth/login" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input type="email" id="email" name="email" required 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="seu@email.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                <input type="password" id="password" name="password" required 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="flex items-center justify-between">
                <a href="<?= BASE_URL ?>/auth/forgot_password" class="text-sm text-indigo-600 hover:text-indigo-500">Esqueceu a senha?</a>
            </div>

            <button type="submit" 
                    class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Entrar
            </button>
        </form>
        
        <p class="mt-4 text-center text-sm text-gray-600">
            Não tem uma conta? <a href="<?= BASE_URL ?>/auth/register" class="text-indigo-600 font-semibold hover:text-indigo-500">Crie aqui</a>.
        </p>
    </div>
</body>
</html>
