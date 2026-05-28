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
render_dashboard_header("Account Settings");
?>

<div class="max-w-2xl mx-auto flex flex-col gap-6">

    <!-- Page header -->
    <div>
        <div class="flex items-center gap-3 mb-2">
            <span class="px-3 py-1 bg-[#a9a4ff]/10 sp-primary text-xs font-black rounded-full tracking-widest uppercase">Settings</span>
        </div>
        <h1 class="text-3xl font-black font-headline sp-text">Account Settings</h1>
        <p class="sp-text-muted text-sm mt-1">Manage your personal details and account security.</p>
    </div>

    <?php if ($error): ?>
        <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">check_circle</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Personal Profile -->
    <div class="sp-surface rounded-xl border sp-border overflow-hidden">
        <div class="px-6 py-4 border-b sp-border flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined sp-primary" style="font-size:18px">person</span>
            </div>
            <div>
                <h3 class="text-base font-bold sp-text font-headline">Personal Profile</h3>
                <p class="text-xs sp-text-faint">Your name and email address.</p>
            </div>
        </div>
        <div class="p-6">
            <form method="POST" action="<?= BASE_URL ?>/dashboard/settings" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label for="name" class="text-xs font-bold uppercase tracking-widest sp-primary">Full Name</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" required
                               class="w-full rounded-xl p-4 text-sm transition-all sp-input">
                    </div>
                    <div class="space-y-1.5">
                        <label for="email" class="text-xs font-bold uppercase tracking-widest sp-primary">Email Address</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required
                               class="w-full rounded-xl p-4 text-sm transition-all sp-input">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm shadow-lg shadow-[#685ef7]/20 hover:brightness-110 transition-all">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Security -->
    <div class="sp-surface rounded-xl border sp-border overflow-hidden">
        <div class="px-6 py-4 border-b sp-border flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined sp-primary" style="font-size:18px">lock</span>
            </div>
            <div>
                <h3 class="text-base font-bold sp-text font-headline">Account Security</h3>
                <p class="text-xs sp-text-faint">Change your dashboard password.</p>
            </div>
        </div>
        <div class="p-6">
            <form method="POST" action="<?= BASE_URL ?>/dashboard/settings" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="update_password">

                <div class="space-y-1.5">
                    <label for="current_password" class="text-xs font-bold uppercase tracking-widest sp-primary">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required
                           class="w-full rounded-xl p-4 text-sm transition-all sp-input">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="space-y-1.5">
                        <label for="new_password" class="text-xs font-bold uppercase tracking-widest sp-primary">New Password</label>
                        <input type="password" name="new_password" id="new_password" required minlength="6"
                               class="w-full rounded-xl p-4 text-sm transition-all sp-input">
                    </div>
                    <div class="space-y-1.5">
                        <label for="confirm_password" class="text-xs font-bold uppercase tracking-widest sp-primary">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                               class="w-full rounded-xl p-4 text-sm transition-all sp-input">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 rounded-full sp-surface border sp-border-mid hover:bg-white/5 sp-text font-bold text-sm transition-all">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php render_dashboard_footer(); ?>
