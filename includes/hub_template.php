<?php
// Layout Base do HUB do SuperAdmin

function render_hub_header($title = "HUB Administrativo") {
    $user = get_logged_user();
    
    // Proteção dupla de rota (Middleware de View)
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        die("Acesso Restrito ao SuperAdmin.");
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - HUB <?= APP_NAME ?></title>
    <!-- CSS Nativo + Tailwind base -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="h-screen flex overflow-hidden bg-gray-100" x-data="{ sidebarOpen: false }">
        
        <!-- Sidebar para Mobile -->
        <div x-show="sidebarOpen" class="fixed inset-0 flex z-40 md:hidden" role="dialog" aria-modal="true">
            <div x-show="sidebarOpen" class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false"></div>
            <div class="relative flex-1 flex flex-col max-w-xs w-full pt-5 pb-4 bg-gray-800">
                <div class="flex-shrink-0 flex items-center px-4">
                    <span class="text-white text-2xl font-black tracking-wider">HUB<span class="text-indigo-400">ADMIN</span></span>
                </div>
                <div class="mt-5 flex-1 h-0 overflow-y-auto">
                    <nav class="px-2 space-y-1">
                        <a href="<?= BASE_URL ?>/hub" class="bg-gray-900 text-white group flex items-center px-2 py-2 text-base font-medium rounded-md">Visão Geral</a>
                        <a href="<?= BASE_URL ?>/hub/sites" class="text-gray-300 hover:bg-gray-700 hover:text-white group flex items-center px-2 py-2 text-base font-medium rounded-md">Sites</a>
                        <a href="<?= BASE_URL ?>/hub/partners" class="text-gray-300 hover:bg-gray-700 hover:text-white group flex items-center px-2 py-2 text-base font-medium rounded-md">Parceiros</a>
                        <a href="<?= BASE_URL ?>/hub/settings" class="text-gray-300 hover:bg-gray-700 hover:text-white group flex items-center px-2 py-2 text-base font-medium rounded-md">Configurações</a>
                    </nav>
                </div>
            </div>
            <div class="flex-shrink-0 w-14" aria-hidden="true"></div>
        </div>

        <!-- Sidebar Desktop -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64">
                <div class="flex flex-col h-0 flex-1">
                    <div class="flex items-center h-16 flex-shrink-0 px-4 bg-gray-900 text-white">
                        <span class="text-xl font-black tracking-wider w-full text-center">HUB<span class="text-indigo-400">ADMIN</span></span>
                    </div>
                    <div class="flex-1 flex flex-col overflow-y-auto bg-gray-800">
                        <nav class="flex-1 px-2 py-4 space-y-2">
                            <?php $uri = $_SERVER['REQUEST_URI']; ?>
                            <a href="<?= BASE_URL ?>/hub" class="<?= $uri == BASE_URL . '/hub' ? 'bg-indigo-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                                📊 Visão Geral
                            </a>
                            <a href="<?= BASE_URL ?>/hub/sites" class="<?= strpos($uri, '/hub/sites') !== false ? 'bg-indigo-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                                🌐 Gestão de Sites
                            </a>
                            <a href="<?= BASE_URL ?>/hub/partners" class="<?= strpos($uri, '/hub/partners') !== false ? 'bg-indigo-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                                🤝 Parceiros
                            </a>
                            <a href="<?= BASE_URL ?>/hub/settings" class="<?= strpos($uri, '/hub/settings') !== false ? 'bg-indigo-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                                ⚙️ Ajustes Globais
                            </a>
                        </nav>
                        
                        <!-- User Footer -->
                        <div class="p-4 bg-gray-900 border-t border-gray-700">
                            <div class="flex items-center">
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-white"><?= htmlspecialchars($user['name']) ?></p>
                                    <div class="flex gap-2 text-xs mt-1">
                                        <a href="<?= str_replace('hub.superpage.com.br', 'superpage.com.br', BASE_URL ? BASE_URL : '//'.$_SERVER['HTTP_HOST']) ?>/dashboard" class="text-gray-400 hover:text-white transition">Ir p/ Frente</a>
                                        <span class="text-gray-600">|</span>
                                        <a href="<?= BASE_URL ?>/auth/logout" class="text-red-400 hover:text-red-300 transition">Sair</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            <!-- Topbar Mobile -->
            <div class="md:hidden pl-1 pt-1 sm:pl-3 sm:pt-3 bg-white border-b border-gray-200">
                <button @click="sidebarOpen = true" type="button" class="-ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                    <span class="sr-only">Abrir menu</span>
                    ☰
                </button>
            </div>

            <main class="flex-1 relative z-0 overflow-y-auto focus:outline-none bg-gray-100">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <h1 class="text-2xl font-bold text-gray-900 mb-6"><?= htmlspecialchars($title) ?></h1>
                    </div>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
<?php
}

function render_hub_footer() {
?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
<?php
}
