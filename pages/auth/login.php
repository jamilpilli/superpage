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
        $error = "Invalid session. Please try again.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $limit = db_fetch_one("SELECT attempts, blocked_until FROM rate_limits WHERE ip_address = :ip AND action = 'login'", [':ip' => $ip]);
        if ($limit && $limit['blocked_until'] && strtotime($limit['blocked_until']) > time()) {
            $error = "Too many failed attempts. Please try again later.";
        } else {
            $user = db_fetch_one("SELECT id, password_hash FROM users WHERE email = :email", [':email' => $email]);

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($limit) {
                    db_update('rate_limits', ['attempts' => 0, 'blocked_until' => null], 'ip_address = :ip AND action = "login"', [':ip' => $ip]);
                }
                $_SESSION['user_id'] = $user['id'];
                session_regenerate_id(true);
                redirect('/dashboard');
            } else {
                if ($limit) {
                    $attempts      = $limit['attempts'] + 1;
                    $blocked_until = $attempts >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
                    db_update('rate_limits', ['attempts' => $attempts, 'blocked_until' => $blocked_until], 'ip_address = :ip AND action = "login"', [':ip' => $ip]);
                } else {
                    db_insert('rate_limits', ['ip_address' => $ip, 'action' => 'login', 'attempts' => 1]);
                }
                $error = "Invalid email or password.";
            }
        }
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= APP_NAME ?></title>
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
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= BASE_URL ?>" class="text-3xl font-black text-white tracking-tighter" style="font-family:'Plus Jakarta Sans',sans-serif">
                <?= APP_NAME ?>
            </a>
            <p class="text-sm text-[#aba9bb] mt-2">Welcome back</p>
        </div>

        <!-- Card -->
        <div class="bg-[#181828] border border-white/5 rounded-2xl p-8 shadow-2xl">

            <?php if ($error): ?>
            <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/auth/login" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div>
                    <label for="email" class="block text-sm font-medium text-[#aba9bb] mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full bg-[#121220] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-[#aba9bb]/50 focus:outline-none focus:border-[#a9a4ff]/50 focus:ring-1 focus:ring-[#a9a4ff]/30 transition-all"
                           placeholder="you@example.com">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="text-sm font-medium text-[#aba9bb]">Password</label>
                        <a href="<?= BASE_URL ?>/auth/forgot_password" class="text-sm text-[#a9a4ff] hover:text-white transition-colors">Forgot password?</a>
                    </div>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="w-full bg-[#121220] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-[#aba9bb]/50 focus:outline-none focus:border-[#a9a4ff]/50 focus:ring-1 focus:ring-[#a9a4ff]/30 transition-all"
                           placeholder="Your password">
                </div>

                <button type="submit"
                        class="w-full signature-glow text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition-all shadow-lg shadow-[#685ef7]/25 mt-2">
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-[#aba9bb] mt-6">
            Don't have an account?
            <a href="<?= BASE_URL ?>/auth/register" class="text-[#a9a4ff] font-semibold hover:text-white transition-colors">Create one free</a>
        </p>
    </div>

</body>
</html>
