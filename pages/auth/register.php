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
        $error = "Invalid session. Please try again.";
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $existingUser = db_fetch_one("SELECT id FROM users WHERE email = :email", [':email' => $email]);
            if ($existingUser) {
                $error = "This email is already in use.";
            } else {
                $hash   = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
                $userId = db_insert('users', [
                    'name'          => $name,
                    'email'         => $email,
                    'password_hash' => $hash,
                    'role'          => 'client'
                ]);

                if ($userId) {
                    $_SESSION['user_id'] = $userId;
                    session_regenerate_id(true);
                    redirect('/dashboard');
                } else {
                    $error = "Failed to create account. Please try again.";
                }
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
    <link rel="icon" type="image/x-icon" href="/fav.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — <?= APP_NAME ?></title>
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
            <p class="text-sm text-[#aba9bb] mt-2">Create your free account</p>
        </div>

        <!-- Card -->
        <div class="bg-[#181828] border border-white/5 rounded-2xl p-8 shadow-2xl">

            <?php if ($error): ?>
            <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/auth/register" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div>
                    <label for="name" class="block text-sm font-medium text-[#aba9bb] mb-2">Full Name</label>
                    <input type="text" id="name" name="name" required autocomplete="name"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           class="w-full bg-[#121220] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-[#aba9bb]/50 focus:outline-none focus:border-[#a9a4ff]/50 focus:ring-1 focus:ring-[#a9a4ff]/30 transition-all"
                           placeholder="Your full name">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-[#aba9bb] mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full bg-[#121220] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-[#aba9bb]/50 focus:outline-none focus:border-[#a9a4ff]/50 focus:ring-1 focus:ring-[#a9a4ff]/30 transition-all"
                           placeholder="you@example.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-[#aba9bb] mb-2">Password</label>
                    <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password"
                           class="w-full bg-[#121220] border border-white/10 rounded-xl px-4 py-3 text-white placeholder-[#aba9bb]/50 focus:outline-none focus:border-[#a9a4ff]/50 focus:ring-1 focus:ring-[#a9a4ff]/30 transition-all"
                           placeholder="At least 6 characters">
                </div>

                <button type="submit"
                        class="w-full signature-glow text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition-all shadow-lg shadow-[#685ef7]/25 mt-2">
                    Create Free Account
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-[#aba9bb] mt-6">
            Already have an account?
            <a href="<?= BASE_URL ?>/auth/login" class="text-[#a9a4ff] font-semibold hover:text-white transition-colors">Sign in</a>
        </p>
    </div>

</body>
</html>
