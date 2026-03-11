<?php
// Página Home Pública

$pageTitle = APP_NAME . " - O Construtor de Sites Institucionais";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Tailwind CSS (CDN para dev, empacotar depois) -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
    
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-indigo-600"><?= APP_NAME ?></h1>
            <nav>
                <?php if (is_logged_in()): ?>
                    <a href="<?= BASE_URL ?>/dashboard" class="text-gray-600 hover:text-indigo-600 px-3 py-2">Dashboard</a>
                    <a href="<?= BASE_URL ?>/auth/logout" class="text-red-600 hover:text-red-800 px-3 py-2">Sair</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login" class="text-gray-600 hover:text-indigo-600 px-3 py-2">Entrar</a>
                    <a href="<?= BASE_URL ?>/auth/register" class="bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-md transition">Criar Conta Grátis</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl md:text-6xl">
                Crie seu site institucional em minutos
            </h2>
            <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-500">
                Uma solução rápida, bonita e profissional. O primeiro ano é grátis.
            </p>
        </div>
    </main>

</body>
</html>
