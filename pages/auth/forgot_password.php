<?php
// Autenticação - Esqueci a Senha

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
// require_once __DIR__ . '/../../includes/SimpleSMTP.php'; // A ser implementado

if (is_logged_in()) {
    redirect('/dashboard');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Sessão inválida. Tente novamente.";
    } else {
        $email = trim($_POST['email'] ?? '');
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = db_fetch_one("SELECT id, name FROM users WHERE email = :email", [':email' => $email]);
            
            if ($user) {
                // Rate Limiter Check (A implementar na tabela `rate_limits` ou direto)
                
                // Gerar token de recuperação
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                
                // TODO: Limpar tokens velhos
                db_insert('password_resets', [
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expires_at
                ]);
                
                // Link de reset simulado
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/auth/reset_password?token=$token";
                
                // TODO: Enviar via SMTP real
                // Por hora, exibimos no painel de sucesso (apenas para ambiente dev/mock)
                if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] == 'true') {
                    $success = "Email enviado! Link (Modo Dev): <a href='$resetLink' class='underline'>$resetLink</a>";
                } else {
                    $success = "Se o e-mail estiver cadastrado, você receberá um link de recuperação em breve.";
                }
            } else {
                // Mensagem genérica por segurança (não revela se o e-mail existe)
                $success = "Se o e-mail estiver cadastrado, você receberá um link de recuperação em breve.";
            }
        } else {
            $error = "E-mail inválido.";
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
    <title>Recuperar Senha - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Recuperar Senha</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded mb-4 text-sm break-all">
                <?= $success ?> <!-- HTML Permitido apenas p/ debug seguro -->
            </div>
            <a href="/auth/login" class="block text-center w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">Voltar ao Login</a>
        <?php else: ?>
            <p class="text-sm text-gray-600 mb-4 text-center">Digite seu e-mail e lhe enviaremos as instruções para criar uma nova senha.</p>
            
            <form method="POST" action="/auth/forgot_password" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" id="email" name="email" required 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="seu@email.com">
                </div>

                <button type="submit" 
                        class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Enviar Link de Recuperação
                </button>
            </form>
            
            <p class="mt-4 text-center text-sm text-gray-600">
                Lembrou? <a href="/auth/login" class="text-indigo-600 font-semibold hover:text-indigo-500">Voltar ao login</a>.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
