<?php
// Dashboard - Minha Conta (Configurações do Usuário)

require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF inválido.";
    } else {
        $action = $_POST['action'] ?? '';
        
        // Atualizar Nome e E-mail
        if ($action === 'update_profile') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (empty($name) || empty($email)) {
                $error = "Nome e E-mail são obrigatórios.";
            } else {
                // Checar e-mail único se foi alterado
                if ($email !== $user['email']) {
                    $checkEmail = db_fetch_one("SELECT id FROM users WHERE email = :email AND id != :id", [
                        ':email' => $email,
                        ':id' => $user['id']
                    ]);
                    
                    if ($checkEmail) {
                        $error = "Este e-mail já está em uso por outra conta.";
                    }
                }
                
                if (empty($error)) {
                    db_update('users', ['name' => $name, 'email' => $email], 'id = :id', [':id' => $user['id']]);
                    $success = "Perfil atualizado com sucesso.";
                    // Força refresh local do cache
                    $user['name'] = $name;
                    $user['email'] = $email;
                }
            }
        }
        
        // Atualizar Senha
        if ($action === 'update_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = "Preencha todos os campos de senha.";
            } elseif ($new_password !== $confirm_password) {
                $error = "A nova senha e a confirmação não coincidem.";
            } elseif (strlen($new_password) < 6) {
                $error = "A nova senha deve ter pelo menos 6 caracteres.";
            } else {
                // Verificar senha atual
                $dbUser = db_fetch_one("SELECT password_hash FROM users WHERE id = :id", [':id' => $user['id']]);
                
                if (!password_verify($current_password, $dbUser['password_hash'])) {
                    $error = "A senha atual está incorreta.";
                } else {
                    $newHash = password_hash($new_password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
                    db_update('users', ['password_hash' => $newHash], 'id = :id', [':id' => $user['id']]);
                    $success = "Sua senha foi alterada com segurança.";
                }
            }
        }
    }
}

$csrf_token = generate_csrf_token();
render_dashboard_header("Configurações Minha Conta");
?>

<div class="max-w-4xl mx-auto pb-10">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">Configurações Minha Conta</h2>
            <p class="mt-1 text-sm text-gray-500">Gerencie seus dados pessoais, senha e informações de acesso.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6"><p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6"><p class="text-sm text-green-700"><?= htmlspecialchars($success) ?></p></div>
    <?php endif; ?>

    <!-- Formulário de Perfil -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Perfil Pessoal</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Suas informações de contato base.</p>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <form method="POST" action="<?= BASE_URL ?>/dashboard/settings">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nome Completo</label>
                        <div class="mt-1">
                            <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" required
                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="email" class="block text-sm font-medium text-gray-700">Endereço de E-mail</label>
                        <div class="mt-1">
                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required
                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Atualizar Perfil
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Formulário de Senha -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Segurança da Conta</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Altere sua senha de acesso ao painel.</p>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <form method="POST" action="<?= BASE_URL ?>/dashboard/settings">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="update_password">
                
                <div class="space-y-6 sm:max-w-md">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Senha Atual</label>
                        <div class="mt-1">
                            <input type="password" name="current_password" id="current_password" required
                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        </div>
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">Nova Senha</label>
                        <div class="mt-1">
                            <input type="password" name="new_password" id="new_password" required minlength="6"
                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirme a Nova Senha</label>
                        <div class="mt-1">
                            <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="bg-gray-800 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                        Alterar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php render_dashboard_footer(); ?>
