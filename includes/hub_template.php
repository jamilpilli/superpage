<?php
// Layout Base do HUB do SuperAdmin

function render_hub_header($title = "HUB Admin") {
    $user = get_logged_user();

    // Proteção dupla de rota (Middleware de View)
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        die("Restricted access — SuperAdmin only.");
    }
    $uri = $_SERVER['REQUEST_URI'];
    $navItems = [
        ['href' => BASE_URL . '/hub',          'label' => 'Overview',        'icon' => 'dashboard',        'match' => fn($u) => rtrim($u, '/') === rtrim(BASE_URL . '/hub', '/')],
        ['href' => BASE_URL . '/hub/sites',    'label' => 'Sites',           'icon' => 'language',         'match' => fn($u) => strpos($u, '/hub/sites') !== false],
        ['href' => BASE_URL . '/hub/partners', 'label' => 'Partners',        'icon' => 'handshake',        'match' => fn($u) => strpos($u, '/hub/partners') !== false],
        ['href' => BASE_URL . '/hub/settings', 'label' => 'Global Settings', 'icon' => 'settings',         'match' => fn($u) => strpos($u, '/hub/settings') !== false],
    ];
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/fav.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> – HUB <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'primary': '#a9a4ff',
                        'primary-dim': '#685ef7',
                        'secondary-dim': '#914feb',
                        'surface-container': '#181828',
                        'surface-container-high': '#1e1e2f',
                        'surface-container-low': '#121220',
                        'on-surface-variant': '#aba9bb',
                        'outline-variant': '#474656',
                    },
                    fontFamily: {
                        'headline': ['Plus Jakarta Sans'],
                        'body': ['Inter'],
                    },
                }
            }
        }
    </script>
    <style>
        body { background-color: #0d0d1a; color: #e9e6f9; font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="antialiased" x-data="{ sidebarOpen: false }">

    <!-- Mobile overlay -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm md:hidden"
         @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-[#0d0d1a] border-r border-white/5 flex flex-col
                  transform transition-transform duration-200 ease-in-out
                  -translate-x-full md:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">

        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-white/5 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#685ef7] to-[#914feb] flex items-center justify-center">
                    <span class="material-symbols-outlined text-white" style="font-size:14px">admin_panel_settings</span>
                </div>
                <span class="text-white font-black font-headline tracking-tight">HUB<span class="text-[#a9a4ff]">ADMIN</span></span>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <?php foreach ($navItems as $item):
                $active = $item['match']($uri);
            ?>
            <a href="<?= $item['href'] ?>"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all text-sm font-bold
                      <?= $active
                          ? 'bg-[#685ef7]/20 text-[#a9a4ff]'
                          : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px"><?= $item['icon'] ?></span>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- User footer -->
        <div class="p-4 border-t border-white/5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#685ef7] to-[#914feb] flex items-center justify-center text-white text-xs font-black flex-shrink-0">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($user['name']) ?></p>
                    <p class="text-xs text-[#a9a4ff] font-bold uppercase tracking-widest">SuperAdmin</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="<?= BASE_URL ?>/dashboard"
                   class="flex-1 text-center py-1.5 text-xs font-bold text-slate-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg transition-all">
                    Dashboard
                </a>
                <a href="<?= BASE_URL ?>/auth/logout"
                   class="flex-1 text-center py-1.5 text-xs font-bold text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition-all">
                    Sign Out
                </a>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="md:ml-64 min-h-screen flex flex-col">

        <!-- Topbar -->
        <header class="h-16 border-b border-white/5 bg-[#0d0d1a]/80 backdrop-blur-xl flex items-center px-4 md:px-8 gap-4 sticky top-0 z-30">
            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/5 transition-colors text-slate-400 hover:text-white">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="text-base font-bold text-white font-headline"><?= htmlspecialchars($title) ?></h1>
            <div class="ml-auto flex items-center gap-3">
                <span class="hidden sm:flex items-center gap-1.5 px-3 py-1 bg-[#685ef7]/20 text-[#a9a4ff] rounded-full text-xs font-black uppercase tracking-widest">
                    <span class="material-symbols-outlined" style="font-size:14px">admin_panel_settings</span>
                    SuperAdmin
                </span>
            </div>
        </header>

        <main class="flex-1 p-6 md:p-8">
<?php
}

function render_hub_footer() {
?>
        </main>
    </div>
</body>
</html>
<?php
}
