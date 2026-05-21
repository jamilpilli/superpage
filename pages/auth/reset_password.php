<?php
// Autenticação - Redefinir Senha

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    redirect('/dashboard');
}

$token   = $_GET['token'] ?? '';
$error   = '';
$success = false;
$userId  = null;

if (empty($token)) {
    redirect('/auth/login');
}

$tokenHash   = hash('sha256', $token);
$resetRecord = db_fetch_one("SELECT user_id, expires_at FROM password_resets WHERE token = :token", [':token' => $tokenHash]);

if (!$resetRecord || strtotime($resetRecord['expires_at']) < time()) {
    $error = "This reset link is invalid or has expired. Please request a new one.";
} else {
    $userId = $resetRecord['user_id'];
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session. Please try again.";
    } else {
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $passwordConfirm) {
            $error = "Passwords do not match.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
            db_update('users', ['password_hash' => $hash], 'id = :id', [':id' => $userId]);

            global $pdo;
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);

            $success = true;
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/fav.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password — <?= APP_NAME ?></title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <style>
        body { background-color: #0d0d1a; color: #e9e6f9; font-family: 'Inter', sans-serif; }
        .signature-glow { background: linear-gradient(135deg, #685ef7 0%, #914feb 100%); }
        input:-webkit-autofill { -webkit-box-shadow: 0 0 0 1000px #181828 inset !important; -webkit-text-fill-color: #e9e6f9 !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="<?= BASE_URL ?>" class="text-3xl font-black text-white tracking-tighter" style="font-family:'Plus Jakarta Sans',sans-serif">
                <?= APP_NAME ?>
            </a>
            <p class="text-sm text-[#aba9bb] mt-2">Create a new password</p>
        </div>

        <div class="bg-[#181828] border border-white/5 rounded-2xl p-8 shadow-2xl">

            <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-400 px-4 py-4 rounded-xl mb-6 text-sm text-center">
                <p class="font-bold mb-1">Password updated!</p>
                <p>You can now sign in with your new password.</p>
            </div>
            <a href="<?= BASE_URL ?>/auth/login"
               class="block w-full signature-glow text-white font-bold py-3.5 rounded-xl text-center hover:opacity-90 transition-all">
                Sign In
            </a>

            <?php else: ?>

            <?php if ($error): ?>
            <div class="flex items-start gap-3 bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>
                    <?= htmlspecialchars($error) ?>
                    <?php if (str_contains($error, 'expired')): ?>
                    <br><a href="<?= BASE_URL ?>/auth/forgot_password" class="underline mt-1 inline-block">Request a new link</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$resetRecord || strtotime($resetRecord['expires_at']) >= time()): ?>
            <form method="POST" action="<?= BASE_URL ?>/auth/reset_password?token=<?= htmlspecialchars($token) ?>" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div>
                    <label for="password" class="block text-sm font-medium text-[#aba9bb] mb-2">New Password</label>
                    <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password"
                           class="w-full bg-[#121220] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-[#aba9bb]/50 focus:outline-none focus:border-[#a9a4ff]/50 focus:ring-1 focus:ring-[#a9a4ff]/30 transition-all"
                           placeholder="At least 6 characters">
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-[#aba9bb] mb-2">Confirm New Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6" autocomplete="new-password"
                           class="w-full bg-[#121220] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-[#aba9bb]/50 focus:outline-none focus:border-[#a9a4ff]/50 focus:ring-1 focus:ring-[#a9a4ff]/30 transition-all"
                           placeholder="Repeat your password">
                </div>

                <button type="submit"
                        class="w-full signature-glow text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition-all shadow-lg shadow-[#685ef7]/25">
                    Save New Password
                </button>
            </form>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

</body>
</html>
