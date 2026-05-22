<?php
// Layout Base do Dashboard do Cliente

function render_dashboard_header($title = "Dashboard") {
    global $currentSite; // Armazenar o site atual
    $user = get_logged_user();

    // Buscar sites acessíveis ao utilizador (admin vê todos, client vê os seus, partner vê os seus + clientes)
    $sites = get_accessible_sites();

    $currentSiteId = isset($_GET['site_id']) ? (int)$_GET['site_id'] : null;
    $currentSite = null;

    if ($currentSiteId) {
        foreach ($sites as $s) {
            if ($s['id'] == $currentSiteId) {
                $currentSite = $s;
                break;
            }
        }
    }

    $unreadContactsCount = 0;
    if ($currentSiteId) {
        try {
            $unreadResult = db_fetch_one("SELECT COUNT(*) as total FROM site_contacts WHERE site_id = :sid AND status = 'new'", [':sid' => $currentSiteId]);
            $unreadContactsCount = (int)($unreadResult['total'] ?? 0);
        } catch (\PDOException $e) {
            // Tabela pode não existir
        }
    }

    // Determinar item ativo na sidebar
    $isContent      = strpos($_SERVER['REQUEST_URI'], '/content') !== false;
    $isSiteSettings = strpos($_SERVER['REQUEST_URI'], '/site_settings') !== false;
    $isContacts     = strpos($_SERVER['REQUEST_URI'], '/contacts') !== false;
    $isHome         = !$isContent && !$isSiteSettings && !$isContacts;

    $navActive   = 'flex items-center gap-3 bg-[#5B4FE9]/20 text-[#a9a4ff] rounded-full px-4 py-2 border-l-4 border-[#5B4FE9] translate-x-1 transition-all duration-200';
    $navInactive = 'flex items-center gap-3 text-slate-400 px-4 py-2 hover:bg-white/5 hover:text-white rounded-full transition-all';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/fav.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "outline": "#757485",
                        "primary-fixed": "#9a94ff",
                        "secondary-fixed-dim": "#d5b6ff",
                        "tertiary-fixed-dim": "#ef7dba",
                        "surface-variant": "#242437",
                        "surface-tint": "#a9a4ff",
                        "secondary-fixed": "#e0c7ff",
                        "secondary-container": "#6107ba",
                        "surface-container-low": "#121220",
                        "error-dim": "#d73357",
                        "tertiary": "#ff98cd",
                        "tertiary-fixed": "#fe8bc8",
                        "tertiary-container": "#f885c2",
                        "on-tertiary-container": "#5a003d",
                        "primary-container": "#9a94ff",
                        "on-background": "#e9e6f9",
                        "surface-container-high": "#1e1e2f",
                        "primary-dim": "#685ef7",
                        "inverse-surface": "#fcf8ff",
                        "surface-bright": "#2a2a3f",
                        "inverse-on-surface": "#545363",
                        "secondary": "#b785ff",
                        "outline-variant": "#474656",
                        "on-primary-container": "#17007d",
                        "primary": "#a9a4ff",
                        "on-error-container": "#ffb2b9",
                        "on-primary-fixed-variant": "#1e0097",
                        "background": "#0d0d1a",
                        "on-surface-variant": "#aba9bb",
                        "secondary-dim": "#914feb",
                        "on-secondary-fixed": "#490090",
                        "on-secondary-container": "#dfc6ff",
                        "on-secondary": "#2f0060",
                        "surface-container-lowest": "#000000",
                        "error-container": "#a70138",
                        "error": "#ff6e84",
                        "on-primary-fixed": "#000000",
                        "surface": "#0d0d1a",
                        "on-surface": "#e9e6f9",
                        "on-primary": "#20009e",
                        "primary-fixed-dim": "#8b84ff",
                        "surface-container-highest": "#242437",
                        "on-tertiary-fixed-variant": "#6d104c",
                        "on-tertiary-fixed": "#360023",
                        "on-error": "#490013",
                        "surface-dim": "#0d0d1a",
                        "on-secondary-fixed-variant": "#6b1fc4",
                        "surface-container": "#181828",
                        "inverse-primary": "#5245e0",
                        "on-tertiary": "#690c49",
                        "tertiary-dim": "#e878b5"
                    },
                    fontFamily: {
                        "headline": ["Plus Jakarta Sans"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    },
                    borderRadius: {"DEFAULT": "1rem", "lg": "2rem", "xl": "3rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body {
            background-color: #0d0d1a;
            color: #e9e6f9;
            font-family: 'Inter', sans-serif;
        }
        .glass-panel {
            background: rgba(36, 36, 55, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="dark overflow-x-hidden">

    <!-- Sidebar fixa — visível apenas em desktop -->
    <nav class="hidden md:flex flex-col h-screen fixed left-0 top-0 pt-6 pb-8 px-4 bg-[#121220] w-64 z-40">
        <div class="mb-8 px-4">
            <a href="<?= BASE_URL ?>/dashboard" class="block">
                <h1 class="text-lg font-bold text-[#a9a4ff] font-headline"><?= APP_NAME ?></h1>
                <p class="text-xs text-slate-400">Client Panel</p>
            </a>
        </div>

        <div class="flex flex-col gap-1.5">
            <?php if ($currentSiteId): ?>
                <!-- Home -->
                <a href="<?= BASE_URL ?>/dashboard?site_id=<?= $currentSiteId ?>" class="<?= $isHome ? $navActive : $navInactive ?>">
                    <span class="material-symbols-outlined text-xl">home</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Home</span>
                </a>
                <!-- Edit Content -->
                <a href="<?= BASE_URL ?>/dashboard/content?site_id=<?= $currentSiteId ?>" class="<?= $isContent ? $navActive : $navInactive ?>">
                    <span class="material-symbols-outlined text-xl">edit_note</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Edit Content</span>
                </a>
                <!-- Edit Design -->
                <button type="button" onclick="openDesignModal()" class="<?= $navInactive ?> w-full text-left">
                    <span class="material-symbols-outlined text-xl">palette</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Edit Design</span>
                </button>
                <!-- Edit Structure -->
                <button type="button" onclick="openStructureModal()" class="<?= $navInactive ?> w-full text-left">
                    <span class="material-symbols-outlined text-xl">account_tree</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Edit Structure</span>
                </button>
                <!-- Settings -->
                <a href="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $currentSiteId ?>" class="<?= $isSiteSettings ? $navActive : $navInactive ?>">
                    <span class="material-symbols-outlined text-xl">settings</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Settings</span>
                </a>
                <!-- Contacts + badge -->
                <a href="<?= BASE_URL ?>/dashboard/contacts?site_id=<?= $currentSiteId ?>" class="<?= $isContacts ? $navActive : $navInactive ?>">
                    <span class="material-symbols-outlined text-xl">contacts</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Contacts</span>
                    <?php if ($unreadContactsCount > 0): ?>
                        <span class="ml-auto flex-shrink-0 min-w-[20px] h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 animate-pulse">
                            <?= $unreadContactsCount > 9 ? '9+' : $unreadContactsCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <!-- Preview Site -->
                <a href="<?= BASE_URL ?>/<?= $currentSite['slug'] ?>?preview=true" target="_blank" class="<?= $navInactive ?> mt-2">
                    <span class="material-symbols-outlined text-xl">open_in_new</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Preview Site</span>
                </a>
            <?php else: ?>
                <!-- Sem site seleccionado: links gerais -->
                <a href="<?= BASE_URL ?>/dashboard" class="<?= $isHome ? $navActive : $navInactive ?>">
                    <span class="material-symbols-outlined text-xl">grid_view</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">My Sites</span>
                </a>
                <a href="<?= BASE_URL ?>/dashboard/create_site" class="<?= $navInactive ?>">
                    <span class="material-symbols-outlined text-xl">add_circle</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Create New Site</span>
                </a>
                <a href="<?= BASE_URL ?>/dashboard/settings" class="<?= $navInactive ?>">
                    <span class="material-symbols-outlined text-xl">account_circle</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">My Account</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Perfil + links de sistema no rodapé da sidebar -->
        <div class="mt-auto px-4 space-y-2">
            <div class="flex items-center gap-3 p-3 bg-[#181828] rounded-xl">
                <div class="w-9 h-9 rounded-full bg-[#5B4FE9]/30 flex items-center justify-center text-[#a9a4ff] font-bold text-sm flex-shrink-0">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($user['name']) ?></p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($user['role']) ?></p>
                </div>
            </div>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= BASE_URL ?>/hub" class="flex items-center gap-3 text-slate-400 px-4 py-2 hover:bg-white/5 hover:text-[#a9a4ff] rounded-full transition-all">
                    <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                    <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Hub Admin</span>
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/auth/logout" class="flex items-center gap-3 text-slate-400 px-4 py-2 hover:bg-white/5 hover:text-white rounded-full transition-all">
                <span class="material-symbols-outlined text-xl">logout</span>
                <span class="font-['Plus_Jakarta_Sans'] text-sm font-medium">Sign Out</span>
            </a>
        </div>
    </nav>

    <!-- Topbar sticky com backdrop-blur -->
    <header class="flex justify-between items-center w-full px-4 sm:px-6 h-16 sticky top-0 z-50 backdrop-blur-xl bg-[#0d0d1a]/80 shadow-[0_4px_30px_rgba(169,164,255,0.06)] border-b border-white/5">
        <div class="flex items-center gap-3">
            <!-- Logo -->
            <a href="<?= BASE_URL ?>/dashboard" class="flex-shrink-0">
                <span class="text-xl font-black text-white tracking-tighter font-headline"><?= APP_NAME ?></span>
            </a>

            <span class="text-slate-600 font-light select-none hidden sm:inline">|</span>

            <!-- Site selector dropdown -->
            <div class="relative">
                <button id="siteDropdownBtn"
                        class="flex items-center gap-2 px-3 py-1.5 bg-[#1e1e2f] hover:bg-[#242437] rounded-full text-sm font-medium transition-all text-slate-300 hover:text-white border border-white/10">
                    <span class="material-symbols-outlined text-base text-[#a9a4ff]" style="font-size:18px">language</span>
                    <span class="max-w-[120px] sm:max-w-[180px] truncate">
                        <?= $currentSite ? htmlspecialchars($currentSite['domain'] ?: $currentSite['slug']) : 'Select your page' ?>
                    </span>
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div id="siteDropdown" style="display: none;"
                     class="origin-top-left absolute left-0 mt-2 w-64 rounded-xl shadow-2xl bg-[#1e1e2f] border border-white/10 py-1 z-[9999]">
                    <a href="<?= BASE_URL ?>/dashboard/create_site"
                       class="flex items-center gap-2 px-4 py-3 text-sm text-[#a9a4ff] font-bold border-b border-white/10 hover:bg-white/5 transition">
                        <span class="material-symbols-outlined text-base" style="font-size:18px">add_circle</span>
                        New Site
                    </a>
                    <?php foreach ($sites as $site): ?>
                        <a href="<?= BASE_URL ?>/dashboard?site_id=<?= $site['id'] ?>"
                           class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-white/5 transition <?= $currentSiteId == $site['id'] ? 'text-[#a9a4ff] font-semibold' : 'text-slate-300' ?>">
                            <?php if ($currentSiteId == $site['id']): ?>
                                <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:16px">check_circle</span>
                            <?php else: ?>
                                <span class="material-symbols-outlined text-slate-600" style="font-size:16px">circle</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($site['domain'] ?: $site['slug'] . '.superpage') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-2">
            <!-- Badge de notificações -->
            <?php if ($currentSiteId): ?>
                <a href="<?= BASE_URL ?>/dashboard/contacts?site_id=<?= $currentSiteId ?>"
                   class="relative p-2 hover:bg-white/10 rounded-full transition-all" title="Contacts">
                    <span class="material-symbols-outlined text-[#a9a4ff]">notifications</span>
                    <?php if ($unreadContactsCount > 0): ?>
                        <span class="absolute top-1 right-1 min-w-[16px] h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center px-0.5 animate-pulse">
                            <?= $unreadContactsCount > 9 ? '9+' : $unreadContactsCount ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Área de conteúdo principal -->
    <main class="md:ml-64 p-6 lg:p-10 min-h-[calc(100vh-4rem)] pb-24 md:pb-10">
<?php
    if (isset($_SESSION['flash_success'])) {
        echo '<div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-xl mb-6 text-sm flex items-center gap-3 max-w-3xl"><span class="material-symbols-outlined text-xl flex-shrink-0">check_circle</span><span>' . $_SESSION['flash_success'] . '</span></div>';
        unset($_SESSION['flash_success']);
    }
    if (isset($_SESSION['flash_error'])) {
        echo '<div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl mb-6 text-sm flex items-center gap-3 max-w-3xl"><span class="material-symbols-outlined text-xl flex-shrink-0">error</span><span>' . $_SESSION['flash_error'] . '</span></div>';
        unset($_SESSION['flash_error']);
    }
}

function render_dashboard_footer() {
    global $currentSite;

    // Re-detectar estado ativo para o bottom nav
    $isContent      = strpos($_SERVER['REQUEST_URI'], '/content') !== false;
    $isSiteSettings = strpos($_SERVER['REQUEST_URI'], '/site_settings') !== false;
    $isContacts     = strpos($_SERVER['REQUEST_URI'], '/contacts') !== false;
    $isHome         = !$isContent && !$isSiteSettings && !$isContacts;

    $bnActive   = 'flex flex-col items-center gap-0.5 text-[#a9a4ff] text-[10px] font-medium px-3 py-1';
    $bnInactive = 'flex flex-col items-center gap-0.5 text-slate-500 text-[10px] hover:text-slate-300 transition-colors px-3 py-1';
?>
    </main>

    <!-- Bottom nav mobile -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 bg-[#121220]/95 backdrop-blur-xl border-t border-white/10 z-50 flex justify-around items-center h-16">
        <?php if ($currentSite): ?>
            <a href="<?= BASE_URL ?>/dashboard?site_id=<?= $currentSite['id'] ?>" class="<?= $isHome ? $bnActive : $bnInactive ?>">
                <span class="material-symbols-outlined text-2xl">home</span>
                <span>Home</span>
            </a>
            <a href="<?= BASE_URL ?>/dashboard/content?site_id=<?= $currentSite['id'] ?>" class="<?= $isContent ? $bnActive : $bnInactive ?>">
                <span class="material-symbols-outlined text-2xl">edit_note</span>
                <span>Content</span>
            </a>
            <button type="button" onclick="openDesignModal()" class="<?= $bnInactive ?>">
                <span class="material-symbols-outlined text-2xl">palette</span>
                <span>Design</span>
            </button>
            <a href="<?= BASE_URL ?>/dashboard/contacts?site_id=<?= $currentSite['id'] ?>" class="<?= $isContacts ? $bnActive : $bnInactive ?>">
                <span class="material-symbols-outlined text-2xl">contacts</span>
                <span>Contacts</span>
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/dashboard" class="<?= $bnActive ?>">
                <span class="material-symbols-outlined text-2xl">grid_view</span>
                <span>My Sites</span>
            </a>
            <a href="<?= BASE_URL ?>/dashboard/create_site" class="<?= $bnInactive ?>">
                <span class="material-symbols-outlined text-2xl">add_circle</span>
                <span>New Site</span>
            </a>
            <a href="<?= BASE_URL ?>/dashboard/settings" class="<?= $bnInactive ?>">
                <span class="material-symbols-outlined text-2xl">account_circle</span>
                <span>Account</span>
            </a>
        <?php endif; ?>
    </nav>

        <script>
        (function() {
            var btn = document.getElementById('siteDropdownBtn');
            var dd  = document.getElementById('siteDropdown');
            if (btn && dd) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
                });
                document.addEventListener('click', function(e) {
                    if (!dd.contains(e.target)) dd.style.display = 'none';
                });
            }
        })();

        window.openDesignModal    = function() { var m=document.getElementById('designModal');    if(m) m.style.display='flex'; };
        window.closeDesignModal   = function() { var m=document.getElementById('designModal');    if(m) m.style.display='none'; };
        window.openStructureModal = function() { var m=document.getElementById('structureModal'); if(m) m.style.display='block'; };
        window.closeStructureModal= function() { var m=document.getElementById('structureModal'); if(m) m.style.display='none'; };
        </script>

        <?php if ($currentSite): ?>
            <?php
                $design = json_decode($currentSite['design'] ?? '{}', true) ?: [];
                $primaryColor = $design['primary_color'] ?? '#4f46e5';
                $titleFont = $design['title_font'] ?? 'Inter';
                $textFont = $design['text_font'] ?? 'Inter';
                $buttonStyle = $design['button_style'] ?? 'rounded';
            ?>
            <!-- Design Modal (Kinetic) -->
            <?php $initBr = $buttonStyle === 'rounded-full' ? '9999px' : ($buttonStyle === 'rounded' ? '8px' : '2px'); ?>
            <div id="designModal" style="display:none;" class="fixed z-[70] inset-0 flex items-center justify-center p-4 bg-[#0d0d1a]/70 backdrop-blur-sm" role="dialog" aria-modal="true">
                <div class="fixed inset-0" onclick="closeDesignModal()"></div>

                <div class="relative w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-white/10 bg-[#F4F5F7]">
                    <form method="POST" action="<?= BASE_URL ?>/dashboard">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="update_design">
                        <input type="hidden" name="site_id" value="<?= $currentSite['id'] ?>">
                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

                        <!-- Modal Header -->
                        <div class="px-8 py-6 bg-white flex justify-between items-center border-b border-gray-100">
                            <div>
                                <h2 class="text-2xl font-extrabold text-[#0d0d1a] font-headline tracking-tight">Site Appearance</h2>
                                <p class="text-sm text-gray-500 mt-0.5">Customise your site's visual identity</p>
                            </div>
                            <button type="button" onclick="closeDesignModal()" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition-colors text-xl leading-none font-bold">✕</button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-8 space-y-8">
                            <!-- Row 1: Color + Button Style -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-[#121220]">Primary Colour</label>
                                    <div class="flex items-center gap-3 bg-white p-3 rounded-xl border border-gray-200">
                                        <div id="colorSwatch" class="w-10 h-10 rounded-full border-2 border-white shadow-sm flex-shrink-0" style="background:<?= htmlspecialchars($primaryColor) ?>"></div>
                                        <input type="color" name="primary_color" id="colorPicker" value="<?= htmlspecialchars($primaryColor) ?>" class="sr-only">
                                        <label for="colorPicker" class="flex-1 font-mono text-sm text-gray-700 cursor-pointer" id="colorHexLabel"><?= htmlspecialchars($primaryColor) ?></label>
                                        <span class="material-symbols-outlined text-gray-400 cursor-pointer" style="font-size:20px" onclick="document.getElementById('colorPicker').click()">colorize</span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-[#121220]">Button Style</label>
                                    <div class="relative">
                                        <select name="button_style" id="btnStyleSelect"
                                                class="w-full appearance-none bg-white px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-800 focus:ring-2 focus:ring-[#5B4FE9]/30 focus:border-[#5B4FE9] outline-none cursor-pointer">
                                            <option value="square" <?= $buttonStyle === 'square' ? 'selected' : '' ?>>Square</option>
                                            <option value="rounded" <?= $buttonStyle === 'rounded' ? 'selected' : '' ?>>Slightly Rounded</option>
                                            <option value="rounded-full" <?= $buttonStyle === 'rounded-full' ? 'selected' : '' ?>>Pill (Fully Rounded)</option>
                                        </select>
                                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400" style="font-size:20px">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Fonts -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-[#121220]">Heading Font</label>
                                    <div class="relative">
                                        <select name="title_font" id="titleFontSelect"
                                                class="w-full appearance-none bg-white px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-800 focus:ring-2 focus:ring-[#5B4FE9]/30 focus:border-[#5B4FE9] outline-none cursor-pointer">
                                            <option value="Plus Jakarta Sans" <?= $titleFont === 'Plus Jakarta Sans' ? 'selected' : '' ?>>Plus Jakarta Sans</option>
                                            <option value="Montserrat" <?= $titleFont === 'Montserrat' ? 'selected' : '' ?>>Montserrat</option>
                                            <option value="Playfair Display" <?= $titleFont === 'Playfair Display' ? 'selected' : '' ?>>Playfair Display</option>
                                            <option value="Inter" <?= $titleFont === 'Inter' ? 'selected' : '' ?>>Inter</option>
                                        </select>
                                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400" style="font-size:20px">expand_more</span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-[#121220]">Body Font</label>
                                    <div class="relative">
                                        <select name="text_font" id="textFontSelect"
                                                class="w-full appearance-none bg-white px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-800 focus:ring-2 focus:ring-[#5B4FE9]/30 focus:border-[#5B4FE9] outline-none cursor-pointer">
                                            <option value="Inter" <?= $textFont === 'Inter' ? 'selected' : '' ?>>Inter</option>
                                            <option value="Roboto" <?= $textFont === 'Roboto' ? 'selected' : '' ?>>Roboto</option>
                                            <option value="Open Sans" <?= $textFont === 'Open Sans' ? 'selected' : '' ?>>Open Sans</option>
                                            <option value="Manrope" <?= $textFont === 'Manrope' ? 'selected' : '' ?>>Manrope</option>
                                        </select>
                                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400" style="font-size:20px">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Preview -->
                            <div class="bg-white/60 backdrop-blur-md p-6 rounded-xl border border-white/70 space-y-3">
                                <span class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Live Preview</span>
                                <h4 id="previewHeading" class="text-xl font-extrabold text-[#121220]" style="font-family:<?= htmlspecialchars($titleFont) ?>">Sample Headline</h4>
                                <p id="previewText" class="text-sm text-gray-500 leading-relaxed" style="font-family:<?= htmlspecialchars($textFont) ?>">This is how your site's typography and colour scheme will look to visitors. Premium and professional.</p>
                                <button type="button" id="previewBtn" class="px-6 py-2 font-bold text-sm text-white shadow-lg transition-transform active:scale-95"
                                        style="background:<?= htmlspecialchars($primaryColor) ?>; border-radius:<?= $initBr ?>; font-family:<?= htmlspecialchars($titleFont) ?>">
                                    Action Button
                                </button>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-8 py-5 bg-white flex justify-end items-center gap-3 border-t border-gray-100">
                            <button type="button" onclick="closeDesignModal()"
                                    class="px-6 py-2.5 rounded-full text-gray-500 font-bold text-sm hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-8 py-2.5 rounded-full bg-[#5B4FE9] text-white font-bold text-sm shadow-lg shadow-[#5B4FE9]/25 hover:bg-[#4a3ecc] transition-all active:scale-95">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Structure Modal -->
            <?php $page = db_fetch_one("SELECT id FROM pages WHERE site_id = :sid AND status = 'published' LIMIT 1", [':sid' => $currentSite['id']]); ?>
            <div id="structureModal" style="display:none;" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-black/60 transition-opacity" onclick="closeStructureModal()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-gray-50 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">

                        <div class="bg-white px-4 py-3 border-b flex justify-between items-center z-10 relative">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Site Structure</h3>
                            <button type="button" onclick="closeStructureModal()" class="text-gray-400 hover:text-gray-500 p-1">✕</button>
                        </div>

                            <div class="bg-gray-50 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[70vh] overflow-y-auto flex flex-col relative">
                                <div class="flex justify-between items-center mb-4">
                                    <p class="text-sm text-gray-500">Toggle sections on or off and drag to reorder them on your site.</p>
                                    <span id="savingIndicator" style="display:none;" class="text-xs px-2 py-1 text-indigo-700 bg-indigo-50 font-bold rounded-full animate-pulse">Saving...</span>
                                </div>
                                <div class="flex-1 w-full" id="blocks-list">
                                    <p class="text-sm text-gray-400 text-center py-8">Loading...</p>
                                </div>
                            </div>

                    </div>
                </div>
            </div>

            <!-- Structure Modal JS (vanilla) -->
            <script>
            (function() {
                var SITE_ID = <?= (int)$currentSite['id'] ?>;
                var PAGE_ID = <?= (int)($page['id'] ?? 0) ?>;
                var BASE    = '<?= BASE_URL ?>';

                var blocks = [];
                var availableTypes = [
                    { type: 'header',       label: 'Header (Navigation)',  desc: 'Top navigation bar with logo.' },
                    { type: 'hero',         label: 'Hero / Main Banner',   desc: 'Full-width banner with headline.' },
                    { type: 'about',        label: 'About Us',             desc: 'Text description with side image.' },
                    { type: 'services',     label: 'Services',             desc: 'Grid of service highlights.' },
                    { type: 'products',     label: 'Products',             desc: 'Product showcase cards.' },
                    { type: 'gallery',      label: 'Photo Gallery',        desc: 'Drag-and-drop photo grid.' },
                    { type: 'videos',       label: 'Videos',               desc: 'Embedded YouTube videos.' },
                    { type: 'testimonials', label: 'Testimonials',         desc: 'Quotes and social proof.' },
                    { type: 'contact',      label: 'Contact',              desc: 'Email form and contact details.' },
                    { type: 'footer',       label: 'Footer',               desc: 'Bottom closing section.' }
                ];
                var sortableInstance = null;
                var initialized = false;

                function setSaving(v) {
                    var el = document.getElementById('savingIndicator');
                    if (el) el.style.display = v ? '' : 'none';
                }

                function isBlockActive(type) {
                    return blocks.some(function(b) {
                        return b.type === type && !(b.config && b.config.is_active === false);
                    });
                }

                function renderBlocks() {
                    var list = document.getElementById('blocks-list');
                    if (!list) return;
                    list.innerHTML = '';
                    availableTypes.forEach(function(item) {
                        var active = isBlockActive(item.type);
                        var div = document.createElement('div');
                        div.className = 'p-4 mb-2 bg-white border border-gray-200 shadow-sm rounded-lg flex items-center justify-between group hover:border-indigo-400 cursor-move';
                        div.setAttribute('data-type', item.type);
                        var editHref = BASE + '/dashboard/content?site_id=' + SITE_ID + '&block_type=' + item.type;
                        div.innerHTML =
                            '<div class="flex items-center flex-1">' +
                                '<span class="cursor-move text-gray-300 mr-4 text-xl group-hover:text-indigo-400 transition select-none" title="Drag to reorder">\u2195</span>' +
                                '<div class="flex-1 cursor-pointer" data-block-label>' +
                                    '<span class="font-bold text-gray-900 block text-sm">' + item.label + '</span>' +
                                    '<span class="text-xs text-gray-500">' + item.desc + '</span>' +
                                '</div>' +
                            '</div>' +
                            '<div class="ml-4 flex items-center gap-3">' +
                                '<a href="' + editHref + '" class="text-xs font-bold text-indigo-700 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded transition"' + (active ? '' : ' style="display:none"') + '>Edit Content</a>' +
                                '<label class="relative inline-flex items-center cursor-pointer shrink-0">' +
                                    '<input type="checkbox" class="sr-only peer"' + (active ? ' checked' : '') + '>' +
                                    '<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>' +
                                '</label>' +
                            '</div>';
                        div.querySelector('[data-block-label]').addEventListener('click', function() { toggleBlock(item.type); });
                        div.querySelector('input[type=checkbox]').addEventListener('change', function() { toggleBlock(item.type); });
                        list.appendChild(div);
                    });
                    initSortable();
                }

                async function fetchBlocks() {
                    if (!PAGE_ID) {
                        document.getElementById('blocks-list').innerHTML = '<p class="text-sm text-gray-400 text-center py-8">No published page found for this site.</p>';
                        return;
                    }
                    try {
                        var res = await fetch(BASE + '/api/blocks?page_id=' + PAGE_ID);
                        if (!res.ok) throw new Error('Failed to load blocks');
                        var json = await res.json();
                        blocks = json.data || [];
                        if (blocks.length > 0) {
                            var orderedTypes = [];
                            blocks.forEach(function(b) {
                                var match = availableTypes.find(function(t) { return t.type === b.type; });
                                if (match) orderedTypes.push(match);
                            });
                            availableTypes.forEach(function(t) {
                                if (!orderedTypes.find(function(o) { return o.type === t.type; })) orderedTypes.push(t);
                            });
                            availableTypes = orderedTypes;
                        }
                        renderBlocks();
                    } catch(e) {
                        alert(e.message);
                    }
                }

                async function toggleBlock(type) {
                    var existing = blocks.find(function(b) { return b.type === type; });
                    setSaving(true);
                    try {
                        if (existing && !(existing.config && existing.config.is_active === false)) {
                            var res = await fetch(BASE + '/api/blocks', {
                                method: 'DELETE',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({block_id: existing.id})
                            });
                            if (res.ok) {
                                existing.config = existing.config || {};
                                existing.config.is_active = false;
                                await evaluateFullReorder();
                                renderBlocks();
                            }
                        } else if (existing && existing.config && existing.config.is_active === false) {
                            existing.config.is_active = true;
                            var res = await fetch(BASE + '/api/blocks', {
                                method: 'PUT',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({block_id: existing.id, config: existing.config})
                            });
                            if (res.ok) {
                                await evaluateFullReorder();
                                renderBlocks();
                            } else {
                                existing.config.is_active = false;
                                alert('Error reactivating block');
                                renderBlocks();
                            }
                        } else {
                            var res = await fetch(BASE + '/api/blocks', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({action: 'add', page_id: PAGE_ID, type: type})
                            });
                            var data = await res.json();
                            if (data.success) { await fetchBlocks(); }
                            else { alert(data.error); }
                        }
                    } catch(e) { console.error(e); }
                    setSaving(false);
                }

                function initSortable() {
                    if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null; }
                    var el = document.getElementById('blocks-list');
                    if (el && typeof Sortable !== 'undefined') {
                        sortableInstance = new Sortable(el, {
                            animation: 150,
                            ghostClass: 'bg-indigo-50',
                            filter: 'input, a, button, label',
                            preventOnFilter: false,
                            onEnd: function() { evaluateFullReorder(); }
                        });
                    }
                }

                async function evaluateFullReorder() {
                    var el = document.getElementById('blocks-list');
                    if (!el) return;
                    var newOrder = [];
                    var idx = 0;
                    Array.from(el.children).forEach(function(item) {
                        var domType = item.getAttribute('data-type');
                        var mb = blocks.find(function(b) { return b.type === domType; });
                        if (mb) { newOrder.push({id: mb.id, sort_order: idx}); idx++; }
                    });
                    if (newOrder.length > 0) {
                        setSaving(true);
                        try {
                            await fetch(BASE + '/api/blocks', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({action: 'reorder', page_id: PAGE_ID, blocks: newOrder})
                            });
                        } catch(e) { console.error('Reorder error', e); }
                        setSaving(false);
                    }
                }

                // Override openStructureModal to load blocks on first open
                var _origOpen = window.openStructureModal;
                window.openStructureModal = function() {
                    var m = document.getElementById('structureModal');
                    if (m) m.style.display = 'block';
                    if (!initialized) { initialized = true; fetchBlocks(); }
                };
            })();
            </script>

            <!-- Design Modal Live Preview JS -->
            <script>
            (function() {
                var colorPicker  = document.getElementById('colorPicker');
                var colorSwatch  = document.getElementById('colorSwatch');
                var colorHexLbl  = document.getElementById('colorHexLabel');
                var btnStyleSel  = document.getElementById('btnStyleSelect');
                var titleFontSel = document.getElementById('titleFontSelect');
                var textFontSel  = document.getElementById('textFontSelect');
                var prevHeading  = document.getElementById('previewHeading');
                var prevText     = document.getElementById('previewText');
                var prevBtn      = document.getElementById('previewBtn');

                function getBorderRadius(s) {
                    if (s === 'rounded-full') return '9999px';
                    if (s === 'rounded') return '8px';
                    return '2px';
                }
                function loadFont(font) {
                    if (!font) return;
                    var id = 'gf-' + font.replace(/\s+/g, '-');
                    if (!document.getElementById(id)) {
                        var link = document.createElement('link');
                        link.id = id; link.rel = 'stylesheet';
                        link.href = 'https://fonts.googleapis.com/css2?family=' + font.replace(/ /g, '+') + ':wght@400;500;600;700;800&display=swap';
                        document.head.appendChild(link);
                    }
                }
                function updatePreview() {
                    var color     = colorPicker  ? colorPicker.value  : '';
                    var btnStyle  = btnStyleSel  ? btnStyleSel.value  : 'rounded';
                    var titleFont = titleFontSel ? titleFontSel.value : 'Inter';
                    var textFont  = textFontSel  ? textFontSel.value  : 'Inter';
                    if (colorSwatch) colorSwatch.style.background = color;
                    if (colorHexLbl) colorHexLbl.textContent = color;
                    if (prevHeading) prevHeading.style.fontFamily = titleFont;
                    if (prevText)    prevText.style.fontFamily = textFont;
                    if (prevBtn) {
                        prevBtn.style.background    = color;
                        prevBtn.style.borderRadius  = getBorderRadius(btnStyle);
                        prevBtn.style.fontFamily    = titleFont;
                    }
                }
                if (colorPicker)  colorPicker.addEventListener('input', updatePreview);
                if (btnStyleSel)  btnStyleSel.addEventListener('change', updatePreview);
                if (titleFontSel) titleFontSel.addEventListener('change', function() { loadFont(this.value); updatePreview(); });
                if (textFontSel)  textFontSel.addEventListener('change',  function() { loadFont(this.value); updatePreview(); });
            })();
            </script>
        <?php endif; ?>
</body>
</html>
<?php
}
