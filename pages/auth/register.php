<?php
// Autenticação - Registro/Cadastro

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    redirect('/dashboard');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Sessão inválida. Tente novamente.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            $error = "Todos os campos são obrigatórios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Formato de e-mail inválido.";
        } elseif (strlen($password) < 6) {
            $error = "A senha deve ter no mínimo 6 caracteres.";
        } else {
            // Verifica se o email já existe
            $existingUser = db_fetch_one("SELECT id FROM users WHERE email = :email", [':email' => $email]);
            if ($existingUser) {
                $error = "Este e-mail já está em uso.";
            } else {
                // Insere novo usuário
                $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
                $userId = db_insert('users', [
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => $hash,
                    'role' => 'client'
                ]);

                if ($userId) {
                    // Log automático (opcional, aqui pediremos para fazer login)
                    $_SESSION['user_id'] = $userId;
                    session_regenerate_id(true);
                    redirect('/dashboard');
                } else {
                    $error = "Erro ao criar conta. Tente novamente.";
                }
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
    <title>Criar Conta - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Criar sua Conta</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/auth/register" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nome Completo</label>
                <input type="text" id="name" name="name" required 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                <input type="email" id="email" name="email" required 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="seu@email.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                <input type="password" id="password" name="password" required minlength="6"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <button type="submit" 
                    class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Criar Conta Grátis
            </button>
        </form>
        
        <p class="mt-4 text-center text-sm text-gray-600">
            Já tem uma conta? <a href="<?= BASE_URL ?>/auth/login" class="text-indigo-600 font-semibold hover:text-indigo-500">Entrar</a>.
        </p>
    </div>
</body>
</html>
