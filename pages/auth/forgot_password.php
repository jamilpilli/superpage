<?php
// Autenticação - Esqueci a Senha

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    redirect('/dashboard');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = db_fetch_one("SELECT id, name FROM users WHERE email = :email", [':email' => $email]);

            if ($user) {
                $token      = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                db_insert('password_resets', [
                    'user_id'    => $user['id'],
                    'token'      => $token,
                    'expires_at' => $expires_at
                ]);

                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/auth/reset_password?token=$token";

                if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] == 'true') {
                    $success = "Email sent! Reset link (Dev Mode): <a href='$resetLink' class='underline text-[#a9a4ff]'>$resetLink</a>";
                } else {
                    $success = "If that email is registered, you'll receive a reset link shortly.";
                }
            } else {
                $success = "If that email is registered, you'll receive a reset link shortly.";
            }
        } else {
            $error = "Invalid email address.";
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
    <title>Reset Password — <?= APP_NAME ?></title>
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
            <p class="text-sm text-[#aba9bb] mt-2">Reset your password</p>
        </div>

        <div class="bg-[#181828] border border-white/5 rounded-2xl p-8 shadow-2xl">

            <?php if ($error): ?>
            <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-400 px-4 py-4 rounded-xl mb-6 text-sm">
                <?= $success ?>
            </div>
            <a href="<?= BASE_URL ?>/auth/login"
               class="block w-full signature-glow text-white font-bold py-3.5 rounded-xl text-center hover:opacity-90 transition-all">
                Back to Sign In
            </a>
            <?php else: ?>
            <p class="text-sm text-[#aba9bb] mb-6 text-center">Enter your email and we'll send you a link to reset your password.</p>

            <form method="POST" action="<?= BASE_URL ?>/auth/forgot_password" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div>
                    <label for="email" class="block text-sm font-medium text-[#aba9bb] mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           class="w-full bg-[#121220] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-[#aba9bb]/50 focus:outline-none focus:border-[#a9a4ff]/50 focus:ring-1 focus:ring-[#a9a4ff]/30 transition-all"
                           placeholder="you@example.com">
                </div>

                <button type="submit"
                        class="w-full signature-glow text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition-all shadow-lg shadow-[#685ef7]/25">
                    Send Reset Link
                </button>
            </form>

            <p class="text-center text-sm text-[#aba9bb] mt-6">
                Remembered it?
                <a href="<?= BASE_URL ?>/auth/login" class="text-[#a9a4ff] font-semibold hover:text-white transition-colors">Sign in</a>
            </p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
