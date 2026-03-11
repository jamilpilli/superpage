<?php
// Autenticação - Redefinir Senha

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    redirect('/dashboard');
}

$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$userId = null;

if (empty($token)) {
    redirect('/auth/login');
}

// Validar se o token existe e não expirou
$resetRecord = db_fetch_one("SELECT user_id, expires_at FROM password_resets WHERE token = :token", [':token' => $token]);

if (!$resetRecord || strtotime($resetRecord['expires_at']) < time()) {
    $error = "Link de recuperação inválido ou expirado. Por favor, solicite um novo.";
} else {
    $userId = $resetRecord['user_id'];
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Sessão inválida. Tente novamente.";
    } else {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        if (strlen($password) < 6) {
            $error = "A senha deve ter no mínimo 6 caracteres.";
        } elseif ($password !== $passwordConfirm) {
            $error = "As senhas não coincidem.";
        } else {
            // Atualizar a senha
            $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
            db_update('users', ['password_hash' => $hash], 'id = :id', [':id' => $userId]);
            
            // Invalida o token usado (deleta todos do usuário para segurança extra)
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            
            $success = true;
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
    <title>Redefinir Senha - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Criar Nova Senha</h2>
        
        <?php if ($success): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded mb-4 text-center">
                <p class="font-bold mb-2">Senha alterada com sucesso!</p>
                <a href="/auth/login" class="inline-block mt-2 bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">Fazer Login</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm text-center">
                    <?= htmlspecialchars($error) ?>
                    <?php if (strpos($error, 'inválido ou expirado') !== false): ?>
                        <br><a href="/auth/forgot_password" class="underline mt-2 inline-block">Solicitar novo link</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$error || strpos($error, 'Sessão inválida') !== false || strpos($error, 'mínimo 6') !== false || strpos($error, 'não coincidem') !== false): ?>
                <form method="POST" action="/auth/reset_password?token=<?= htmlspecialchars($token) ?>" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Nova Senha</label>
                        <input type="password" id="password" name="password" required minlength="6"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700">Confirmar Nova Senha</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="6"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <button type="submit" 
                            class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Salvar Nova Senha
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
