<?php
// Resolver de URL de Site
// Captura requisições tipo /meuslugaqui ou /meudominio e desenha o One Page

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// O $requestUri vem do router principal
$slugOuDominio = trim($requestUri, '/');
if (empty($slugOuDominio)) {
    // Evita loop infinito já que a URL / public home.php já foi tratada
    http_response_code(404);
    echo "Página estática não encontrada.";
    exit;
}

// 1. Buscamos o Site
// TODO: Expandir para consulta de campo `domain` primeiro e tratar cache
$site = db_fetch_one("SELECT * FROM sites WHERE slug = :slug AND status = 'active'", [':slug' => $slugOuDominio]);

if (!$site) {
    http_response_code(404);
    // Aqui poderiamos mostrar uma tela de erro amigável do sistema
    echo "<h1>Site não encontrado ou inativo.</h1>";
    exit;
}

// 2. Buscamos a página OnePage Ativa (Geralmente a 'home' do site)
$page = db_fetch_one("SELECT * FROM pages WHERE site_id = :sid AND status = 'published' LIMIT 1", [':sid' => $site['id']]);

if (!$page) {
    http_response_code(404);
    echo "Página do site não gerada ou não publicada.";
    exit;
}

// 3. Processar formulário de Contato, se houver
$contactSuccess = false;
$contactError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $visitorIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rateLimitAction = 'contact_' . $site['id'];

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $contactError = "Invalid session. Please reload the page and try again.";
    } else {
        $limit = db_fetch_one(
            "SELECT attempts, blocked_until FROM rate_limits WHERE ip_address = :ip AND action = :action",
            [':ip' => $visitorIp, ':action' => $rateLimitAction]
        );

        if ($limit && $limit['blocked_until'] && strtotime($limit['blocked_until']) > time()) {
            $contactError = "Too many messages sent. Please try again later.";
        } else {
            $name    = trim($_POST['name'] ?? '');
            $email   = trim($_POST['email'] ?? '');
            $phone   = trim($_POST['phone'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if (empty($name) || empty($message)) {
                $contactError = "Name and message are required.";
            } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contactError = "Invalid email address.";
            } else {
                try {
                    db_insert('site_contacts', [
                        'site_id' => $site['id'],
                        'name'    => $name,
                        'email'   => $email,
                        'phone'   => $phone,
                        'message' => $message,
                    ]);

                    if ($limit) {
                        $attempts = $limit['attempts'] + 1;
                        $blocked  = $attempts >= 5 ? date('Y-m-d H:i:s', strtotime('+1 hour')) : null;
                        db_update('rate_limits', ['attempts' => $attempts, 'blocked_until' => $blocked],
                            'ip_address = :ip AND action = :action', [':ip' => $visitorIp, ':action' => $rateLimitAction]);
                    } else {
                        db_insert('rate_limits', ['ip_address' => $visitorIp, 'action' => $rateLimitAction, 'attempts' => 1]);
                    }

                    $contactSuccess = "Your message has been sent successfully!";
                } catch (\PDOException $e) {
                    $contactError = "An error occurred while sending your message. Please try again.";
                }
            }
        }
    }
}

// 4. Buscamos os Blocos Configurados
$all_blocks = db_fetch_all("SELECT * FROM blocks WHERE page_id = :pid ORDER BY sort_order ASC", [':pid' => $page['id']]);
$blocks = [];
foreach ($all_blocks as $b) {
    $cfg = $b['config'] ? json_decode($b['config'], true) : [];
    if (isset($cfg['is_active']) && $cfg['is_active'] === false) {
        continue;
    }
    $blocks[] = $b;
}

// 4. Buscar design global
$design = json_decode($site['design'] ?? '{}', true) ?: [];

// Validar cor: aceitar apenas formato #rrggbb ou #rgb
$rawColor = $design['primary_color'] ?? '#4f46e5';
$primaryColor = preg_match('/^#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $rawColor) ? $rawColor : '#4f46e5';

// Validar fonte: aceitar apenas letras, números, espaços e hífens
$allowedFontPattern = '/^[a-zA-Z0-9 \-]+$/';
$rawTitleFont = $design['title_font'] ?? 'Inter';
$titleFont = preg_match($allowedFontPattern, $rawTitleFont) ? $rawTitleFont : 'Inter';
$rawTextFont = $design['text_font'] ?? 'Inter';
$textFont = preg_match($allowedFontPattern, $rawTextFont) ? $rawTextFont : 'Inter';

$buttonStyle = $design['button_style'] ?? 'rounded';

// Mapeia o formato do botão pra classes nativas do Tailwind
$btnRadiusMap = [
    'square' => 'rounded-none',
    'rounded' => 'rounded-md',
    'rounded-full' => 'rounded-full'
];
$btnRadiusClass = $btnRadiusMap[$buttonStyle] ?? 'rounded-md';

// Prepara Google Fonts URL
$fontsToLoad = array_unique([$titleFont, $textFont]);
$fontVars = [];
foreach ($fontsToLoad as $f) {
    $fontVars[] = 'family=' . urlencode($f) . ':wght@400;500;600;700;800';
}
$googleFontsUrl = 'https://fonts.googleapis.com/css2?' . implode('&', $fontVars) . '&display=swap';

// 4.5 Registrar Visita (Analytics)
$isPreview = isset($_GET['preview']) && $_GET['preview'] === 'true';

if (!$isPreview) {
    try {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceType = 'Desktop';
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
            $deviceType = 'Mobile';
        }
        
        db_insert('site_analytics', [
            'site_id' => $site['id'],
            'page_id' => $page['id'],
            'visitor_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? ''
        ]);
    } catch (\PDOException $e) {
        // Ignorar se a tabela 'site_analytics' ainda não existir
    }
}

// 5. Extrair dados SEO dos blocos
$seoDescription = '';
$seoKeywords    = [];
$seoImage       = '';

foreach ($blocks as $b) {
    $cfg  = json_decode($b['config'] ?? '{}', true) ?: [];
    $type = $b['type'];

    // Descrição: primeiro bloco hero ou about
    if (empty($seoDescription) && in_array($type, ['hero', 'about'])) {
        $candidate = strip_tags($cfg['description'] ?? '');
        if (!empty($candidate)) $seoDescription = $candidate;
    }

    // Keywords: títulos dos itens de serviços e produtos
    if (in_array($type, ['services', 'products'])) {
        foreach (($cfg['items'] ?? []) as $item) {
            if (!empty($item['title'])) $seoKeywords[] = $item['title'];
        }
    }

    // OG Image: primeira imagem encontrada nos blocos
    if (empty($seoImage)) {
        if (!empty($cfg['image']))                 $seoImage = $cfg['image'];
        elseif (!empty($cfg['items'][0]['image'])) $seoImage = $cfg['items'][0]['image'];
    }
}

$siteName = $page['title'] ?? $site['slug'];

if (empty($seoDescription)) $seoDescription = $siteName;
$seoDescription = mb_strimwidth($seoDescription, 0, 160, '...');

$protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$siteBaseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

// Canonical aponta para o domínio custom quando disponível
if (!empty($site['domain'])) {
    $canonicalUrl = 'https://' . $site['domain'];
} else {
    $canonicalUrl = $siteBaseUrl . '/' . $site['slug'];
}

if (!empty($seoImage) && !preg_match('/^https?:\/\//', $seoImage)) {
    $seoImage = $siteBaseUrl . '/' . ltrim($seoImage, '/');
}

$seoTitle       = htmlspecialchars($siteName);
$seoKeywordsStr = implode(', ', array_unique(array_slice($seoKeywords, 0, 10)));

// 6. Renderização HTML Final
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/fav.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary SEO -->
    <title><?= $seoTitle ?></title>
    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <?php if ($seoKeywordsStr): ?>
    <meta name="keywords"    content="<?= htmlspecialchars($seoKeywordsStr) ?>">
    <?php endif; ?>
    <link rel="canonical"    href="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta name="robots"      content="index, follow">

    <!-- Open Graph (WhatsApp, Facebook, LinkedIn) -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:title"       content="<?= htmlspecialchars($siteName) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <meta property="og:site_name"   content="<?= htmlspecialchars($siteName) ?>">
    <meta property="og:locale"      content="en_GB">
    <?php if ($seoImage): ?>
    <meta property="og:image"        content="<?= htmlspecialchars($seoImage) ?>">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= htmlspecialchars($siteName) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($seoDescription) ?>">
    <?php if ($seoImage): ?>
    <meta name="twitter:image"       content="<?= htmlspecialchars($seoImage) ?>">
    <?php endif; ?>

    <!-- JSON-LD: Organization -->
    <?php
    $jsonld = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => $siteName,
        'url'      => $canonicalUrl,
        'description' => $seoDescription,
    ];
    if (!empty($seoImage))  $jsonld['image'] = $seoImage;
    // Telefone do bloco de contato
    foreach ($blocks as $_b) {
        if ($_b['type'] === 'contact') {
            $_cfg = json_decode($_b['config'] ?? '{}', true) ?: [];
            if (!empty($_cfg['phone'])) {
                $rawPhone = preg_replace('/\D/', '', $_cfg['phone']);
                $jsonld['telephone'] = (str_starts_with(trim($_cfg['phone']), '+') ? '+' : '') . $rawPhone;
            }
            break;
        }
    }
    echo '<script type="application/ld+json">' . json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?= $googleFontsUrl?>" rel="stylesheet">

    <!-- CSS Nativo + Tailwind base pro template default -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: <?= $primaryColor ?>;
            --font-title: '<?= $titleFont ?>', sans-serif;
            --font-text: '<?= $textFont ?>', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-text);
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .font-title {
            font-family: var(--font-title);
        }

        .nav-link:hover {
            color: var(--color-primary);
        }
    </style>
</head>

<body class="text-gray-800 antialiased bg-white">

    <?php if ($isPreview && !is_logged_in()): ?>
    <!-- Aviso de preview de segurança (somente do iframe do dashboard passaria) -->
    <?php
endif; ?>

    <?php if (empty($blocks)): ?>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="text-center p-8 bg-white shadow-xl rounded-lg border border-gray-100">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Bem-vindo ao
                <?= htmlspecialchars($page['title'])?>!
            </h1>
            <p class="text-gray-500">Este site ainda não possui nenhum conteúdo.</p>
            <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL?>/dashboard/content?site_id=<?= $site['id']?>"
                class="mt-4 inline-block bg-indigo-600 text-white px-6 py-2 rounded font-medium hover:bg-indigo-700">Adicionar
                Blocos</a>
            <?php
    endif; ?>
        </div>
    </div>
    <?php
else: ?>

    <?php
    // Extração global: Busca telefone do bloco Contato para exibir no Header/Footer
    $globalContactPhone = '';
    $globalIsWhatsapp = false;
    foreach ($blocks as $b) {
        if ($b['type'] === 'contact') {
            $cCfg = json_decode($b['config'], true) ?? [];
            if (!empty($cCfg['phone'])) {
                $globalContactPhone = $cCfg['phone'];
                $globalIsWhatsapp = !empty($cCfg['is_whatsapp']);
            }
            break;
        }
    }

    // Utilitário para montar HTML de Telefone
    $renderPhoneLink = function ($phone, $isWa, $classes) {
        if (!$phone)
            return '';
        $onlyNumbers = preg_replace('/\D/', '', $phone);
        $hasPlus = str_starts_with(trim($phone), '+');
        $link = $isWa ? "https://wa.me/{$onlyNumbers}" : "tel:" . ($hasPlus ? '+' : '') . $onlyNumbers;
        $icon = $isWa
            ? '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 22a9.962 9.962 0 01-5.1-1.4L2 22l4.63-1.854A9.957 9.957 0 0112 2A10 10 0 0122 12c0 5.514-4.486 10-10 10zm0-18C7.589 4 4 7.589 4 12c0 1.543.441 3.013 1.25 4.254l-.842 3.366 3.444-.813A7.957 7.957 0 0012 20c4.411 0 8-3.589 8-8s-3.589-8-8-8z"/></svg>'
            : '<svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
        return "<a href='{$link}' class='{$classes}' target='_blank'>{$icon}<span>" . htmlspecialchars($phone) . "</span></a>";
    };

    // Renderiza cada bloco consumindo as confs
    foreach ($blocks as $block) {
        $type = $block['type'];
        // A configuração de cada bloco mora num varchar JSON gravado na tabela
        $cfg = json_decode($block['config'], true) ?? [];
        $blockTitle = isset($cfg['title']) && !empty(trim($cfg['title'])) ? htmlspecialchars($cfg['title']) : ucfirst($type);
        $blockSlug = generate_slug(isset($cfg['title']) && !empty(trim($cfg['title'])) ? $cfg['title'] : ucfirst($type));

        // Lendo campos customizados comuns
        $desc = isset($cfg['description']) && !empty(trim($cfg['description'])) ? htmlspecialchars($cfg['description']) : "";
        $btnText = isset($cfg['button_text']) && !empty(trim($cfg['button_text'])) ? htmlspecialchars($cfg['button_text']) : "Get In Touch";
        $btnLink = isset($cfg['button_link']) && !empty(trim($cfg['button_link'])) ? htmlspecialchars($cfg['button_link']) : "#contact";
        $text = isset($cfg['text']) && !empty(trim($cfg['text'])) ? htmlspecialchars($cfg['text']) : "";
        $img = isset($cfg['image']) && !empty(trim($cfg['image'])) ? htmlspecialchars($cfg['image']) : "https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80";

        $items = $cfg['items'] ?? [];

        switch ($type) {
            case 'header':
                $navLinks = "<a href='#inicio' class='text-gray-600 transition nav-link'>Home</a>";
                foreach ($blocks as $navBlock) {
                    $ntype = $navBlock['type'];
                    if (in_array($ntype, ['header', 'hero', 'footer']))
                        continue;
                    $ncfg = json_decode($navBlock['config'], true) ?? [];
                    $ntitle = (!empty($ncfg['title']) && trim($ncfg['title']) !== '') ? $ncfg['title'] : ucfirst($ntype);
                    $nslug = generate_slug($ntitle);
                    $navLinks .= "<a href='#{$nslug}' class='text-gray-600 transition nav-link'>" . htmlspecialchars($ntitle) . "</a>";
                }

                $headerLogo = !empty($cfg['image'])
                    ? "<img src='" . htmlspecialchars($cfg['image']) . "' alt='{$blockTitle}' title='{$blockTitle}' class='h-10 object-contain max-w-[200px]'>"
                    : "<h1 class='font-bold text-xl' style='color: var(--color-primary);'>{$blockTitle}</h1>";

                $topPhoneHtml       = $renderPhoneLink($globalContactPhone, $globalIsWhatsapp, "hidden md:flex items-center text-gray-700 font-bold nav-link ml-6 pl-6 border-l border-gray-200 transition");
                $mobilePhoneHtml    = $renderPhoneLink($globalContactPhone, $globalIsWhatsapp, "flex items-center text-gray-700 font-bold px-4 py-3 rounded-xl hover:bg-gray-50 transition");

                // Mobile nav links (same links, different style)
                $mobileNavLinks = "<a href='#inicio' class='block px-4 py-3 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition'>Home</a>";
                foreach ($blocks as $navBlock) {
                    $ntype = $navBlock['type'];
                    if (in_array($ntype, ['header', 'hero', 'footer'])) continue;
                    $ncfg  = json_decode($navBlock['config'], true) ?? [];
                    $ntitle = (!empty($ncfg['title']) && trim($ncfg['title']) !== '') ? $ncfg['title'] : ucfirst($ntype);
                    $nslug  = generate_slug($ntitle);
                    $mobileNavLinks .= "<a href='#{$nslug}' class='mobile-site-link block px-4 py-3 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition'>" . htmlspecialchars($ntitle) . "</a>";
                }

                echo "
                <header id='inicio' class='bg-white shadow sticky top-0 z-50'>
                    <div class='max-w-7xl mx-auto px-4 py-4 flex justify-between items-center'>
                        {$headerLogo}
                        <div class='flex items-center'>
                            <nav class='hidden md:flex space-x-6 text-sm font-medium items-center'>{$navLinks}</nav>
                            {$topPhoneHtml}
                        </div>
                        <!-- Hamburger -->
                        <button id='site-nav-toggle' aria-label='Open menu' class='md:hidden flex flex-col items-center justify-center gap-1.5 w-9 h-9 rounded-lg hover:bg-gray-100 transition-colors'>
                            <span class='site-ham-line block w-5 h-0.5 bg-gray-700 rounded-full transition-all duration-300'></span>
                            <span class='site-ham-line block w-5 h-0.5 bg-gray-700 rounded-full transition-all duration-300'></span>
                            <span class='site-ham-line block w-5 h-0.5 bg-gray-700 rounded-full transition-all duration-300'></span>
                        </button>
                    </div>
                    <!-- Mobile drawer -->
                    <div id='site-nav-mobile' class='md:hidden overflow-hidden max-h-0 transition-all duration-300 ease-in-out border-t border-gray-100'>
                        <div class='px-4 py-4 space-y-1 bg-white'>
                            {$mobileNavLinks}
                            " . ($mobilePhoneHtml ? "<div class='pt-3 border-t border-gray-100 mt-3'>{$mobilePhoneHtml}</div>" : "") . "
                        </div>
                    </div>
                </header>
                <script>
                (function(){
                    var btn    = document.getElementById('site-nav-toggle');
                    var drawer = document.getElementById('site-nav-mobile');
                    var lines  = btn.querySelectorAll('.site-ham-line');
                    var open   = false;
                    function toggle(force){
                        open = force !== undefined ? force : !open;
                        drawer.style.maxHeight = open ? drawer.scrollHeight + 'px' : '0';
                        lines[0].style.transform = open ? 'translateY(8px) rotate(45deg)' : '';
                        lines[1].style.opacity   = open ? '0' : '';
                        lines[2].style.transform = open ? 'translateY(-8px) rotate(-45deg)' : '';
                    }
                    btn.addEventListener('click', function(){ toggle(); });
                    document.querySelectorAll('.mobile-site-link').forEach(function(l){
                        l.addEventListener('click', function(){ toggle(false); });
                    });
                })();
                </script>";
                break;
            case 'hero':
                $heroId = 'slider-' . $block['id'];
                echo "<section id='{$blockSlug}' class='relative w-full h-screen min-h-[600px] flex items-center justify-center overflow-hidden bg-gray-900'>";

                if (!empty($items)) {
                    echo "<div id='{$heroId}' class='absolute inset-0 w-full h-full'>";
                    foreach ($items as $index => $item) {
                        $iT = htmlspecialchars($item['title'] ?? '');
                        $iD = htmlspecialchars($item['description'] ?? '');
                        $iI = htmlspecialchars($item['image'] ?? '');
                        if (empty($iI))
                            $iI = 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1920&q=80';
                        $iBT = htmlspecialchars($item['button_text'] ?? '');
                        $iBL = htmlspecialchars($item['button_link'] ?? '#');

                        // Apenas o primeiro começa visível
                        $opacityClass = $index === 0 ? 'opacity-100 relative z-10' : 'opacity-0 absolute z-0';
                        $visibilityStyle = $index === 0 ? 'transition: opacity 1s ease-in-out;' : 'transition: opacity 1s ease-in-out; visibility: hidden;';

                        echo "<div class='hero-slide absolute inset-0 w-full h-full {$opacityClass}' data-index='{$index}' style='{$visibilityStyle}'>";
                        echo "<div class='absolute inset-0 bg-cover bg-center' style='background-image: url(\"{$iI}\");'></div>";
                        echo "<div class='absolute inset-0 bg-black bg-opacity-60'></div>"; // Overlay escuro
                        echo "<div class='relative h-full flex flex-col items-center justify-center text-center px-4 max-w-4xl mx-auto z-20'>";

                        // Só exibe título se o usuário preencheu
                        if (!empty($iT)) {
                            echo "<h2 class='text-4xl md:text-6xl font-extrabold text-white font-title mb-6'>" . $iT . "</h2>";
                        }
                        if (!empty($iD))
                            echo "<p class='text-xl md:text-2xl text-gray-200 mb-10 leading-relaxed'>" . $iD . "</p>";

                        if (!empty($iBT) && !empty($iBL) && $iBL !== '#') {
                            echo "<a href='{$iBL}' class='inline-block px-10 py-4 {$btnRadiusClass} text-white font-bold transition hover:opacity-90 shadow-lg text-lg' style='background-color: var(--color-primary);'>" . $iBT . "</a>";
                        }
                        echo "</div></div>";
                    }
                    echo "</div>";

                    if (count($items) > 1) {
                        echo "
                            <button id='prev-{$heroId}' class='absolute left-4 top-1/2 -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-60 text-white rounded-full p-3 transition z-30 focus:outline-none'>
                                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='w-8 h-8'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 19.5L8.25 12l7.5-7.5'/></svg>
                            </button>
                            <button id='next-{$heroId}' class='absolute right-4 top-1/2 -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-60 text-white rounded-full p-3 transition z-30 focus:outline-none'>
                                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' class='w-8 h-8'><path stroke-linecap='round' stroke-linejoin='round' d='M8.25 4.5l7.5 7.5-7.5 7.5'/></svg>
                            </button>
                            ";

                        echo "<script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    let currentIndex = 0;
                                    const slider = document.getElementById('{$heroId}');
                                    if (!slider) return;
                                    const slides = Array.from(slider.querySelectorAll('.hero-slide'));
                                    const total = slides.length;
                                    let slideInterval;

                                    if (total > 1) {
                                        const showSlide = (index) => {
                                            const currentSlide = slides[currentIndex];
                                            currentSlide.classList.remove('opacity-100', 'z-10');
                                            currentSlide.classList.add('opacity-0', 'z-0');
                                            setTimeout(() => { currentSlide.style.visibility = 'hidden'; }, 1000);

                                            currentIndex = index;
                                            if (currentIndex < 0) currentIndex = total - 1;
                                            if (currentIndex >= total) currentIndex = 0;

                                            const nextSlide = slides[currentIndex];
                                            nextSlide.style.visibility = 'visible';
                                            nextSlide.classList.remove('opacity-0', 'z-0');
                                            nextSlide.classList.add('opacity-100', 'z-10');
                                        };

                                        const nextSlide = () => showSlide(currentIndex + 1);
                                        const prevSlide = () => showSlide(currentIndex - 1);

                                        const startInterval = () => {
                                            slideInterval = setInterval(nextSlide, 5000);
                                        };
                                        const resetInterval = () => {
                                            clearInterval(slideInterval);
                                            startInterval();
                                        };

                                        document.getElementById('next-{$heroId}').addEventListener('click', () => {
                                            nextSlide();
                                            resetInterval();
                                        });

                                        document.getElementById('prev-{$heroId}').addEventListener('click', () => {
                                            prevSlide();
                                            resetInterval();
                                        });

                                        startInterval();
                                    }
                                });
                            </script>";
                    }
                }
                else {
                    // Fallback sem itens
                    $bgImg = $img ?: 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=1920&q=80';
                    echo "<div class='absolute inset-0 bg-cover bg-center' style='background-image: url(\"{$bgImg}\");'></div>";
                    echo "<div class='absolute inset-0 bg-black bg-opacity-60 z-0'></div>";
                    echo "<div class='relative z-10 flex flex-col items-center justify-center text-center px-4 max-w-4xl mx-auto h-full'>";
                    echo "<h2 class='text-4xl md:text-6xl font-extrabold text-white font-title mb-6'>" . $blockTitle . "</h2>";
                    echo "<p class='text-xl md:text-2xl text-gray-200 mb-10'>" . $desc . "</p>";
                    if (!empty($btnText) && !empty($btnLink) && $btnLink !== '#') {
                        echo "<a href='" . $btnLink . "' class='inline-block px-10 py-4 {$btnRadiusClass} text-white font-bold transition hover:opacity-90 shadow-lg text-lg' style='background-color: var(--color-primary);'>" . $btnText . "</a>";
                    }
                    echo "</div>";
                }
                echo "</section>";
                break;
            case 'about':
                $aboutBtn = (!empty($btnText) && !empty($btnLink) && $btnLink !== '#') ? "<a href='{$btnLink}' class='text-white px-6 py-3 {$btnRadiusClass} shadow-sm inline-block font-medium hover:opacity-90' style='background-color: var(--color-primary);'>{$btnText}</a>" : "";
                echo "<section id='{$blockSlug}' class='py-20 px-4 bg-white'><div class='max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center'><div><h2 class='text-3xl font-bold font-title mb-6' style='color: var(--color-primary);'>" . $blockTitle . "</h2><p class='text-gray-600 text-lg leading-relaxed mb-6'>" . nl2br($text) . "</p>{$aboutBtn}</div><div><img src='" . $img . "' alt='" . $blockTitle . "' class='rounded-lg shadow-xl w-full h-auto object-cover max-h-96'></div></div></section>";
                break;
            case 'services':
                echo "<section id='{$blockSlug}' class='py-20 px-4 bg-gray-50'><div class='max-w-7xl mx-auto'><div class='text-center max-w-3xl mx-auto mb-16'><h2 class='text-3xl font-bold font-title mb-4' style='color: var(--color-primary);'>" . $blockTitle . "</h2><p class='text-gray-500 text-lg'>" . $desc . "</p></div>";
                if (!empty($items)) {
                    echo "<div class='grid grid-cols-1 md:grid-cols-3 gap-8'>";
                    foreach ($items as $item) {
                        $iT = htmlspecialchars($item['title'] ?? '');
                        $iD = htmlspecialchars($item['description'] ?? '');
                        $iI = htmlspecialchars($item['image'] ?? '');
                        $iBT = htmlspecialchars($item['button_text'] ?? '');
                        $iBL = htmlspecialchars($item['button_link'] ?? '#');
                        $btnHtml = (!empty($iBT) && !empty($iBL) && $iBL !== '#')
                            ? "<div class='mt-auto flex pt-4'><a href='{$iBL}' class='inline-block px-6 py-2 {$btnRadiusClass} text-white text-sm font-medium hover:opacity-90 transition' style='background-color: var(--color-primary);'>{$iBT}</a></div>"
                            : '';
                        $imgHtml = $iI ? "<img src='{$iI}' class='w-full h-48 object-cover rounded-t-xl'" . ($iT ? " alt='{$iT}'" : '') . ">" : '';
                        $cardPadding = $iI ? '' : 'p-8';
                        echo "<div class='bg-white rounded-xl shadow-md border border-gray-100 hover:shadow-lg transition flex flex-col overflow-hidden'>{$imgHtml}<div class='p-6 flex flex-col flex-1'><h3 class='text-xl font-bold mb-3 font-title text-gray-900'>{$iT}</h3><p class='text-gray-500 flex-1'>{$iD}</p>{$btnHtml}</div></div>";
                    }
                    echo "</div>";
                }
                else {
                    echo "<p class='text-center text-gray-400'>Adicione serviços através do painel de controle.</p>";
                }
                echo "</div></section>";
                break;
            case 'products':
                echo "<section id='{$blockSlug}' class='py-20 px-4 bg-white'><div class='max-w-7xl mx-auto'><div class='text-center max-w-3xl mx-auto mb-16'><h2 class='text-3xl font-bold font-title mb-4' style='color: var(--color-primary);'>" . $blockTitle . "</h2></div>";
                if (!empty($items)) {
                    echo "<div class='grid grid-cols-1 md:grid-cols-4 gap-6'>";
                    foreach ($items as $item) {
                        $iT = htmlspecialchars($item['title'] ?? '');
                        $iD = htmlspecialchars($item['description'] ?? '');
                        $iI = htmlspecialchars($item['image'] ?? ''); // Sem placeholder genérico
                        $iBT = htmlspecialchars(trim($item['button_text'] ?? '')); // Sem fallback 'Comprar'
                        $iBL = htmlspecialchars(trim($item['button_link'] ?? ''));
                        if (empty($iBL))
                            $iBL = '#';

                        $imgHtml = $iI ? "<img src='{$iI}' class='w-full h-48 object-cover bg-gray-100'>" : '';
                        // Mostra o botão unicamente se o texto do botão (título do botão) foi definido pelo user
                        $btnHtml = (!empty($iBT)) ? "<div class='pt-4 mt-auto flex'><a href='{$iBL}' class='text-center inline-block px-6 py-2 {$btnRadiusClass} text-white font-medium hover:opacity-90 transition' style='background-color: var(--color-primary);'>{$iBT}</a></div>" : "";

                        echo "<div class='bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col hover:shadow-md transition'>{$imgHtml}<div class='p-5 flex-1 flex flex-col'><h3 class='text-lg font-bold mb-2 font-title text-gray-900'>{$iT}</h3><p class='text-gray-500 text-sm mb-4 flex-1'>{$iD}</p>{$btnHtml}</div></div>";
                    }
                    echo "</div>";
                }
                else {
                    echo "<p class='text-center text-gray-400'>Adicione produtos através do painel de controle.</p>";
                }
                echo "</div></section>";
                break;
            case 'testimonials':
                echo "<section id='{$blockSlug}' class='py-20 px-4 bg-indigo-900 text-white' style='background-color: var(--color-primary);'><div class='max-w-7xl mx-auto text-center'><h2 class='text-3xl font-bold font-title mb-16'>" . $blockTitle . "</h2>";
                if (!empty($items)) {
                    echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8'>";
                    foreach ($items as $item) {
                        $iT = htmlspecialchars($item['title'] ?? ''); // Author Name
                        $iD = htmlspecialchars($item['description'] ?? ''); // Quote
                        $iI = htmlspecialchars($item['image'] ?? ''); // Avatar
                        echo "<div class='bg-white bg-opacity-10 rounded-xl p-8 text-left relative'><div class='text-4xl text-white opacity-20 absolute top-4 left-4'>&ldquo;</div><p class='text-indigo-50 text-lg relative z-10 italic mb-6'>\"{$iD}\"</p><div class='flex items-center mt-auto'>" . ($iI ? "<img src='{$iI}' class='w-12 h-12 rounded-full mr-4 object-cover border-2 border-white opacity-80'>" : "<div class='w-12 h-12 rounded-full mr-4 bg-white bg-opacity-20 flex items-center justify-center font-bold text-xl'>" . substr($iT, 0, 1) . "</div>") . "<div><h4 class='font-bold text-white'>{$iT}</h4><span class='text-sm text-indigo-200'>Cliente Verificado</span></div></div></div>";
                    }
                    echo "</div>";
                }
                else {
                    echo "<p class='text-indigo-200'>Nenhum depoimento adicionado.</p>";
                }
                echo "</div></section>";
                break;
            case 'contact':
                echo "<section id='{$blockSlug}' class='py-20 px-4 bg-gray-50'><div class='max-w-3xl mx-auto'><div class='text-center mb-10'><h2 class='text-3xl font-bold font-title' style='color: var(--color-primary);'>" . $blockTitle . "</h2></div><div class='bg-white rounded-lg shadow-xl p-8 border border-gray-100'>";
                
                if ($contactSuccess) {
                    echo "<div class='bg-green-50 justify-between text-green-700 p-4 rounded mb-6 text-sm flex border border-green-100 shadow-sm'><span>{$contactSuccess}</span></div>";
                }
                if ($contactError) {
                    echo "<div class='bg-red-50 justify-between text-red-700 p-4 rounded mb-6 text-sm flex border border-red-100 shadow-sm'><span>{$contactError}</span></div>";
                }
                
                echo "<form action='#{$blockSlug}' method='POST' class='space-y-6'>
                <input type='hidden' name='contact_submit' value='1'>
                <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                <div><label class='block text-sm font-medium text-gray-700'>Full Name</label><input type='text' name='name' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm p-3 border focus:ring-indigo-500 focus:border-indigo-500'></div>
                <div class='grid grid-cols-1 md:grid-cols-2 gap-6'><div><label class='block text-sm font-medium text-gray-700'>Email</label><input type='email' name='email' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm p-3 border focus:ring-indigo-500 focus:border-indigo-500'></div><div><label class='block text-sm font-medium text-gray-700'>Phone</label><input type='tel' name='phone' placeholder='+44 7700 900000' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm p-3 border focus:ring-indigo-500 focus:border-indigo-500'></div></div>
                <div><label class='block text-sm font-medium text-gray-700'>Message</label><textarea required name='message' rows='4' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm p-3 border focus:ring-indigo-500 focus:border-indigo-500'></textarea></div>
                <div class='text-center md:text-left'><button type='submit' class='inline-block w-auto text-white font-bold py-3 px-10 {$btnRadiusClass} shadow focus:outline-none transition hover:opacity-90' style='background-color: var(--color-primary);'>" . $btnText . "</button></div></form></div></div></section>";
                break;
            case 'footer':
                $footerPhoneHtml = $renderPhoneLink($globalContactPhone, $globalIsWhatsapp, "flex items-center justify-center text-white opacity-90 font-medium hover:opacity-100 transition mt-4");
                echo "<footer id='{$blockSlug}' class='text-white py-12 text-center mt-auto' style='background-color: var(--color-primary);'><div class='max-w-7xl mx-auto px-4'><h3 class='text-2xl font-bold mb-4 font-title'>{$blockTitle}</h3><p class='text-white opacity-80'>&copy; " . date('Y') . " All rights reserved.</p>{$footerPhoneHtml}</div></footer>";
                break;
            case 'gallery':
                echo "<section id='{$blockSlug}' class='py-20 px-4 bg-white'><div class='max-w-7xl mx-auto'><div class='text-center mb-16'><h2 class='text-3xl font-bold font-title mb-4' style='color: var(--color-primary);'>" . $blockTitle . "</h2><p class='text-gray-500 text-lg'>" . $desc . "</p></div>";
                $images = $cfg['gallery_images'] ?? [];
                if (!empty($images)) {
                    echo "<div class='grid grid-cols-1 md:grid-cols-3 gap-6'>";
                    foreach ($images as $imgUrl) {
                        $safeUrl = htmlspecialchars($imgUrl);
                        echo "<div class='overflow-hidden rounded-lg shadow-sm hover:shadow-md transition aspect-square'><img src='{$safeUrl}' class='w-full h-full object-cover transition duration-300 hover:scale-105'></div>";
                    }
                    echo "</div>";
                }
                else {
                    echo "<p class='text-center text-gray-400'>Nenhuma foto adicionada à galeria.</p>";
                }
                echo "</div></section>";
                break;
            case 'videos':
                echo "<section id='{$blockSlug}' class='py-20 px-4 bg-gray-50'><div class='max-w-7xl mx-auto'><div class='text-center mb-16'><h2 class='text-3xl font-bold font-title mb-4' style='color: var(--color-primary);'>" . $blockTitle . "</h2><p class='text-gray-500 text-lg'>" . $desc . "</p></div>";
                $videos = $cfg['videos'] ?? [];
                $activeVideos = array_filter($videos, function ($v) {
                    return isset($v['is_active']) ? $v['is_active'] : true; });
                if (!empty($activeVideos)) {
                    echo "<div class='grid grid-cols-1 md:grid-cols-3 gap-8'>";
                    foreach ($activeVideos as $vid) {
                        $vT = htmlspecialchars($vid['title'] ?? '');
                        $vU = htmlspecialchars($vid['url'] ?? '');
                        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $vU, $match);
                        $ytId = $match[1] ?? '';
                        if ($ytId) {
                            echo "<div class='bg-white rounded-xl shadow-md overflow-hidden flex flex-col hover:shadow-lg transition'>";
                            echo "<div class='relative w-full aspect-video'><iframe class='absolute top-0 left-0 w-full h-full' src='https://www.youtube.com/embed/{$ytId}' title='{$vT}' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe></div>";
                            if ($vT)
                                echo "<div class='p-4'><h3 class='text-lg font-bold font-title text-gray-900'>{$vT}</h3></div>";
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                }
                else {
                    echo "<p class='text-center text-gray-400'>Nenhum vídeo adicionado ou ativo.</p>";
                }
                echo "</div></section>";
                break;
            default:
                $defaultDesc = isset($cfg['description']) && !empty(trim($cfg['description'])) ? htmlspecialchars($cfg['description']) : "Conteúdo do bloco $type";
                echo "<section id='{$blockSlug}' class='py-16 px-4 border-b border-gray-200 bg-white'><div class='max-w-7xl mx-auto text-center'><h3 class='text-2xl font-bold mb-4 font-title' style='color: var(--color-primary);'>" . $blockTitle . "</h3><p class='text-gray-500'>" . $defaultDesc . "</p></div></section>";
        }
    }
?>

    <?php
endif; ?>

    <script>
        // Correção de Smooth Scroll Nativa (Fase 4.2)
        document.addEventListener('DOMContentLoaded', () => {
            // Corrige comportamento se estiver rodando dentro de Iframe ou local
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#inicio') {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        const targetEl = document.querySelector(targetId);
                        if (targetEl) {
                            targetEl.scrollIntoView({
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>