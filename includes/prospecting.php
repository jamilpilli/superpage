<?php
// includes/prospecting.php
// Lógica partilhada de criação de sites de prospecção

require_once __DIR__ . '/prospect_templates.php';

/**
 * Cria utilizador + site + página + blocos + subscrição 1 ano grátis.
 * Não dispara webhook — isso é feito depois pelo prospect_send endpoint.
 *
 * @param array $data {
 *   name, slug, email, phone, hero, about,
 *   service1, service2, service3, service4,
 *   color, category, status ('active'|'draft')
 * }
 * @param int $adminId
 * @return array { site_id, user_id, password }
 */
function create_prospect_site(array $data, int $adminId): array {
    global $pdo;

    $category = $data['category'] ?? 'other';
    $tpl      = get_prospect_template($category);
    $year     = date('Y');
    $password = 'Welcome' . $year . '!';

    // --- 1. Utilizador ---
    $email = !empty($data['email'])
        ? $data['email']
        : $data['slug'] . '@superpage.co.uk';

    // Se o email já existe, reutiliza o utilizador
    $existingUser = db_fetch_one("SELECT id FROM users WHERE email = :e", [':e' => $email]);
    if ($existingUser) {
        $userId = (int)$existingUser['id'];
    } else {
        $userId = db_insert('users', [
            'name'          => $data['name'],
            'email'         => $email,
            'phone'         => $data['phone'] ?? null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]),
            'role'          => 'client',
            'status'        => 'active',
        ]);
    }

    // --- 2. Site ---
    $color  = !empty($data['color']) ? $data['color'] : $tpl['color'];
    $design = json_encode([
        'primary_color' => $color,
        'title_font'    => $tpl['title_font'],
        'text_font'     => $tpl['text_font'],
        'button_style'  => $tpl['button_style'],
    ]);

    $siteId = db_insert('sites', [
        'user_id' => $userId,
        'slug'    => $data['slug'],
        'status'  => $data['status'] ?? 'active',
        'design'  => $design,
    ]);

    // --- 3. Página ---
    $pageId = db_insert('pages', [
        'site_id' => $siteId,
        'slug'    => 'home',
        'title'   => $data['name'],
        'status'  => 'published',
    ]);

    // --- 4. Blocos ---
    $heroText  = !empty($data['hero'])  ? $data['hero']  : $data['name'] . ' — ' . $tpl['hero'];
    $aboutText = !empty($data['about']) ? $data['about'] : $tpl['about'];

    // Serviços: usar os fornecidos, completar com genéricos
    $serviceNames = [];
    for ($i = 1; $i <= 4; $i++) {
        $key = 'service' . $i;
        $serviceNames[] = !empty($data[$key]) ? $data[$key] : ($tpl['services'][$i - 1] ?? null);
    }
    $serviceNames = array_filter($serviceNames);

    $serviceItems = [];
    foreach (array_values($serviceNames) as $idx => $svcName) {
        $serviceItems[] = [
            'title'       => $svcName,
            'description' => 'Get in touch to learn more about this service.',
            'image'       => $tpl['service_images'][$idx] ?? '',
        ];
    }

    $blocks = [
        ['type' => 'header',   'sort' => 0, 'config' => ['logo_text' => $data['name'], 'menu_items' => []]],
        ['type' => 'hero',     'sort' => 1, 'config' => ['title' => $data['name'], 'subtitle' => $heroText, 'image' => $tpl['hero_img'], 'cta_text' => 'Get In Touch', 'cta_link' => '#contact']],
        ['type' => 'about',    'sort' => 2, 'config' => ['title' => 'About Us', 'text' => $aboutText, 'image' => $tpl['about_img'] ?? '']],
        ['type' => 'services', 'sort' => 3, 'config' => ['title' => 'Our Services', 'items' => $serviceItems]],
        ['type' => 'gallery',  'sort' => 4, 'config' => ['title' => 'Our Work', 'description' => '', 'gallery_images' => $tpl['gallery_images']]],
        ['type' => 'contact',  'sort' => 5, 'config' => ['title' => 'Contact Us', 'email' => $data['email'] ?? '', 'phone' => $data['phone'] ?? '', 'text' => 'We would love to hear from you.']],
        ['type' => 'footer',   'sort' => 6, 'config' => ['text' => '© ' . $year . ' ' . $data['name'] . '. All rights reserved.']],
    ];

    foreach ($blocks as $block) {
        db_insert('blocks', [
            'page_id'    => $pageId,
            'type'       => $block['type'],
            'sort_order' => $block['sort'],
            'config'     => json_encode($block['config']),
        ]);
    }

    // --- 5. Subscrição 1 ano grátis ---
    db_insert('subscriptions', [
        'user_id'    => $userId,
        'site_id'    => $siteId,
        'plan_type'  => 'free_trial',
        'started_at' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+365 days')),
        'status'     => 'active',
    ]);

    // --- 6. Audit log ---
    db_insert('hub_audit_logs', [
        'admin_id'    => $adminId,
        'action_type' => 'prospect_site_created',
        'entity_type' => 'sites',
        'entity_id'   => $siteId,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    ]);

    return [
        'site_id'  => $siteId,
        'user_id'  => $userId,
        'password' => $password,
    ];
}

/**
 * Dispara webhook n8n com os dados do cliente.
 * Regista em prospect_log e activa o site draft.
 */
function fire_prospect_webhook(int $siteId, int $adminId, array $payload): bool {
    // Activar site draft sempre — independente do webhook
    db_update('sites', ['status' => 'active'], 'id = :id', [':id' => $siteId]);

    $webhookUrl = defined('N8N_PROSPECT_WEBHOOK_URL') ? N8N_PROSPECT_WEBHOOK_URL : '';
    if (empty($webhookUrl)) {
        return false;
    }

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $via = 'email';
    if (!empty($payload['has_phone']) && !empty($payload['has_email'])) $via = 'both';
    elseif (!empty($payload['has_phone'])) $via = 'whatsapp';

    db_insert('prospect_log', [
        'site_id'      => $siteId,
        'admin_id'     => $adminId,
        'notified_via' => $via,
    ]);

    return $httpCode >= 200 && $httpCode < 300;
}
