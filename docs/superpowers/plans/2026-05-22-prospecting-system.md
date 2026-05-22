# Prospecting System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow SuperAdmin to create client sites (single wizard or bulk CSV), auto-generate content by category, and notify clients via n8n webhook (WhatsApp + email) with 1-year free subscription.

**Architecture:** Shared creation logic in `includes/prospecting.php`. Single wizard at `pages/hub/create_client.php`. Bulk flow at `pages/hub/import_clients.php` with a draft queue and AJAX send endpoint. Category templates hardcoded in `includes/prospect_templates.php`.

**Tech Stack:** PHP vanilla, MySQL/PDO, Alpine.js, Tailwind CDN, n8n webhook (HTTP POST JSON)

---

## File Map

| File | Action | Purpose |
|---|---|---|
| `migrations/014_prospecting.sql` | Create | Add `draft` to sites.status; create `prospect_log` table |
| `includes/prospect_templates.php` | Create | Category stock content (services, about, hero image URLs) |
| `includes/prospecting.php` | Create | `create_prospect_site()` + `fire_prospect_webhook()` |
| `includes/hub_template.php` | Modify | Add nav links for Create Client + Import Clients |
| `pages/hub/create_client.php` | Create | Single wizard (3-step Alpine.js form) |
| `pages/hub/import_clients.php` | Create | CSV upload + draft queue + send selected |
| `api/endpoints/prospect_send.php` | Create | AJAX: fire webhook + activate draft site |
| `.env.example` | Modify | Add `N8N_PROSPECT_WEBHOOK_URL` |
| `config/app.php` | Modify | Read `N8N_PROSPECT_WEBHOOK_URL` from `.env` |

---

## Task 1: Migration

**Files:**
- Create: `migrations/014_prospecting.sql`

- [ ] **Step 1: Write migration file**

```sql
-- 014_prospecting.sql

-- Add 'draft' status to sites
ALTER TABLE sites MODIFY COLUMN status ENUM('active','inactive','suspended','draft') DEFAULT 'active';

-- Prospect log: tracks when/how client was notified
CREATE TABLE prospect_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    site_id     INT NOT NULL,
    admin_id    INT NOT NULL,
    notified_via ENUM('whatsapp','email','both') NOT NULL,
    notified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id)  REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Run migration**

```powershell
"C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" migrate.php
```

Expected output: `[OK] 014_prospecting.sql`

- [ ] **Step 3: Verify in MySQL**

```sql
SHOW COLUMNS FROM sites LIKE 'status';
-- should show: enum('active','inactive','suspended','draft')

DESCRIBE prospect_log;
-- should show: id, site_id, admin_id, notified_via, notified_at
```

- [ ] **Step 4: Commit**

```bash
git add migrations/014_prospecting.sql
git commit -m "feat: migration — adicionar status draft em sites e tabela prospect_log"
```

---

## Task 2: Category Templates

**Files:**
- Create: `includes/prospect_templates.php`

- [ ] **Step 1: Create file**

```php
<?php
// includes/prospect_templates.php
// Stock content por categoria — usado na criação de sites de prospecção

function get_prospect_template(string $category): array {
    $templates = [
        'marketing' => [
            'hero'     => 'award-winning marketing agency helping brands grow online.',
            'about'    => 'We are a results-driven marketing agency specialising in digital growth. From social media to paid advertising, our team delivers strategies that generate real results for our clients.',
            'services' => ['Social Media Management', 'Brand Design', 'Paid Ads', 'SEO'],
            'hero_img' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=1920&q=80',
        ],
        'restaurant' => [
            'hero'     => 'delicious food, warm atmosphere, unforgettable experience.',
            'about'    => 'We are passionate about bringing people together over great food. Using fresh, locally sourced ingredients, every dish is crafted with care and served with a smile.',
            'services' => ['Dine In', 'Takeaway', 'Catering', 'Private Events'],
            'hero_img' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1920&q=80',
        ],
        'health' => [
            'hero'     => 'your health and wellbeing are our top priority.',
            'about'    => 'We provide compassionate, professional healthcare services tailored to your individual needs. Our experienced team is dedicated to helping you feel your best.',
            'services' => ['Consultations', 'Treatments', 'Wellness Plans', 'Nutrition Advice'],
            'hero_img' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1920&q=80',
        ],
        'construction' => [
            'hero'     => 'quality craftsmanship you can trust, built to last.',
            'about'    => 'With years of experience in the construction industry, we deliver high-quality builds and renovations on time and within budget. Your vision, our expertise.',
            'services' => ['Renovation', 'New Build', 'Repairs & Maintenance', 'Surveying'],
            'hero_img' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=1920&q=80',
        ],
        'retail' => [
            'hero'     => 'discover products you will love, every single day.',
            'about'    => 'We curate a carefully selected range of products to suit every taste and budget. Whether you shop in-store or online, we are committed to making your experience effortless and enjoyable.',
            'services' => ['In-Store Shopping', 'Online Orders', 'Gift Cards', 'Easy Returns'],
            'hero_img' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1920&q=80',
        ],
        'professional' => [
            'hero'     => 'expert advice and professional services you can rely on.',
            'about'    => 'We are a team of experienced professionals committed to delivering practical solutions for our clients. We combine deep expertise with a personal approach to achieve outstanding outcomes.',
            'services' => ['Consultation', 'Advisory Services', 'Ongoing Support', 'Training'],
            'hero_img' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=1920&q=80',
        ],
        'other' => [
            'hero'     => 'welcome — we are here to help you succeed.',
            'about'    => 'We are a dedicated team passionate about what we do. Our goal is to provide outstanding service and create lasting value for every client we work with.',
            'services' => ['Our Services', 'What We Offer', 'How We Work', 'Get In Touch'],
            'hero_img' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1920&q=80',
        ],
    ];

    return $templates[$category] ?? $templates['other'];
}

function get_prospect_categories(): array {
    return [
        'marketing'    => 'Marketing & Agency',
        'restaurant'   => 'Restaurant & Food',
        'health'       => 'Health & Beauty',
        'construction' => 'Construction & Trades',
        'retail'       => 'Retail & Shop',
        'professional' => 'Professional Services',
        'other'        => 'Other',
    ];
}
```

- [ ] **Step 2: Commit**

```bash
git add includes/prospect_templates.php
git commit -m "feat: templates de categoria para prospecção (hero, about, serviços, imagens)"
```

---

## Task 3: Prospecting Service

**Files:**
- Create: `includes/prospecting.php`
- Modify: `.env.example`, `config/app.php`

- [ ] **Step 1: Add webhook URL to .env.example**

Open `.env.example` and add at the end:
```
N8N_PROSPECT_WEBHOOK_URL=
```

- [ ] **Step 2: Read webhook URL in config/app.php**

In `config/app.php`, find where other `.env` variables are read and add:
```php
define('N8N_PROSPECT_WEBHOOK_URL', $_ENV['N8N_PROSPECT_WEBHOOK_URL'] ?? '');
```

- [ ] **Step 3: Create includes/prospecting.php**

```php
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
    $color  = !empty($data['color']) ? $data['color'] : '#685ef7';
    $design = json_encode([
        'primary_color' => $color,
        'title_font'    => 'Plus Jakarta Sans',
        'text_font'     => 'Inter',
        'button_style'  => 'rounded',
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
            'image'       => '',
        ];
    }

    $blocks = [
        ['type' => 'header',       'sort' => 0, 'config' => ['logo_text' => $data['name'], 'menu_items' => []]],
        ['type' => 'hero',         'sort' => 1, 'config' => ['title' => $data['name'], 'subtitle' => $heroText, 'image' => $tpl['hero_img'], 'cta_text' => 'Get In Touch', 'cta_link' => '#contact']],
        ['type' => 'about',        'sort' => 2, 'config' => ['title' => 'About Us', 'text' => $aboutText, 'image' => '']],
        ['type' => 'services',     'sort' => 3, 'config' => ['title' => 'Our Services', 'items' => $serviceItems]],
        ['type' => 'contact',      'sort' => 4, 'config' => ['title' => 'Contact Us', 'email' => $data['email'] ?? '', 'phone' => $data['phone'] ?? '', 'text' => 'We would love to hear from you.']],
        ['type' => 'footer',       'sort' => 5, 'config' => ['text' => '© ' . $year . ' ' . $data['name'] . '. All rights reserved.']],
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
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    return [
        'site_id'  => $siteId,
        'user_id'  => $userId,
        'password' => $password,
    ];
}

/**
 * Dispara webhook n8n com os dados do cliente.
 * Regista em prospect_log.
 */
function fire_prospect_webhook(int $siteId, int $adminId, array $payload): bool {
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

    // Activar site draft após envio
    db_update('sites', ['status' => 'active'], 'id = :id', [':id' => $siteId]);

    return $httpCode >= 200 && $httpCode < 300;
}
```

- [ ] **Step 4: Commit**

```bash
git add includes/prospecting.php .env.example config/app.php
git commit -m "feat: serviço de prospecção — create_prospect_site e fire_prospect_webhook"
```

---

## Task 4: Hub Navigation

**Files:**
- Modify: `includes/hub_template.php`

- [ ] **Step 1: Add nav items**

In `includes/hub_template.php`, find the `$navItems` array and add two items after `'Sites'`:

```php
['href' => BASE_URL . '/hub/create_client', 'label' => 'Create Client',   'icon' => 'add_business',    'match' => fn($u) => strpos($u, '/hub/create_client') !== false],
['href' => BASE_URL . '/hub/import_clients','label' => 'Import Clients',  'icon' => 'upload_file',     'match' => fn($u) => strpos($u, '/hub/import_clients') !== false],
```

- [ ] **Step 2: Commit**

```bash
git add includes/hub_template.php
git commit -m "feat: adicionar links Create Client e Import Clients na nav do Hub"
```

---

## Task 5: Single Wizard

**Files:**
- Create: `pages/hub/create_client.php`

- [ ] **Step 1: Create the wizard page**

```php
<?php
// pages/hub/create_client.php — Wizard de criação de site de prospecção (1 cliente)

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';
require_once __DIR__ . '/../../includes/prospecting.php';

$admin  = get_logged_user();
$error  = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token.";
    } else {
        $name  = trim($_POST['name'] ?? '');
        $slug  = trim($_POST['slug'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($slug)) {
            $error = "Business name and slug are required.";
        } elseif (empty($email) && empty($phone)) {
            $error = "Provide at least an email or a phone number.";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif (db_fetch_one("SELECT id FROM sites WHERE slug = :s", [':s' => $slug])) {
            $error = "This slug is already taken.";
        } elseif (!empty($email) && db_fetch_one("SELECT id FROM users WHERE email = :e", [':e' => $email])) {
            $error = "This email is already registered.";
        } else {
            $data = [
                'name'     => $name,
                'slug'     => $slug,
                'email'    => $email,
                'phone'    => $phone,
                'hero'     => trim($_POST['hero'] ?? ''),
                'about'    => trim($_POST['about'] ?? ''),
                'service1' => trim($_POST['service1'] ?? ''),
                'service2' => trim($_POST['service2'] ?? ''),
                'service3' => trim($_POST['service3'] ?? ''),
                'service4' => trim($_POST['service4'] ?? ''),
                'color'    => trim($_POST['color'] ?? '#685ef7'),
                'category' => trim($_POST['category'] ?? 'other'),
                'status'   => 'active',
            ];

            $created = create_prospect_site($data, (int)$admin['id']);

            $siteUrl = 'https://superpage.co.uk/' . $slug;
            $payload = [
                'event'         => 'prospect_site_created',
                'business_name' => $name,
                'site_url'      => $siteUrl,
                'email'         => $email,
                'phone'         => $phone,
                'password'      => $created['password'],
                'has_email'     => !empty($email),
                'has_phone'     => !empty($phone),
            ];
            fire_prospect_webhook($created['site_id'], (int)$admin['id'], $payload);

            $result = array_merge($created, ['slug' => $slug, 'name' => $name, 'site_url' => $siteUrl, 'email' => $email, 'phone' => $phone]);
        }
    }
}

$csrf_token = generate_csrf_token();
$categories = get_prospect_categories();
$colors = ['#685ef7','#914feb','#3b82f6','#10b981','#f59e0b','#ef4444','#ec4899','#0ea5e9'];

render_hub_header("Create Client Site");
?>

<div class="max-w-2xl mx-auto flex flex-col gap-6" x-data="{ step: 1 }">

  <div>
    <span class="px-3 py-1 bg-[#a9a4ff]/10 text-[#a9a4ff] text-xs font-black rounded-full tracking-widest uppercase">Prospecting</span>
    <h1 class="text-3xl font-black font-headline text-white mt-2">Create Client Site</h1>
    <p class="text-slate-400 text-sm mt-1">Create a site for a prospect — 1 year free hosting, notify via WhatsApp + email.</p>
  </div>

  <?php if ($error): ?>
    <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
      <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">error</span>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($result): ?>
    <!-- Success -->
    <div class="bg-[#181828] rounded-xl border border-emerald-500/20 p-6 flex flex-col gap-4">
      <div class="flex items-center gap-3 text-emerald-400">
        <span class="material-symbols-outlined" style="font-size:28px">check_circle</span>
        <h2 class="text-xl font-bold font-headline">Site created and client notified!</h2>
      </div>
      <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="bg-[#0d0d1a] rounded-lg p-3"><span class="text-slate-500 block text-xs mb-1">Site URL</span><a href="<?= htmlspecialchars($result['site_url']) ?>" target="_blank" class="text-[#a9a4ff] hover:underline"><?= htmlspecialchars($result['site_url']) ?></a></div>
        <div class="bg-[#0d0d1a] rounded-lg p-3"><span class="text-slate-500 block text-xs mb-1">Password</span><span class="text-white font-mono font-bold"><?= htmlspecialchars($result['password']) ?></span></div>
        <?php if ($result['email']): ?><div class="bg-[#0d0d1a] rounded-lg p-3"><span class="text-slate-500 block text-xs mb-1">Email</span><span class="text-white"><?= htmlspecialchars($result['email']) ?></span></div><?php endif; ?>
        <?php if ($result['phone']): ?><div class="bg-[#0d0d1a] rounded-lg p-3"><span class="text-slate-500 block text-xs mb-1">WhatsApp</span><span class="text-white"><?= htmlspecialchars($result['phone']) ?></span></div><?php endif; ?>
      </div>
      <div class="flex gap-3">
        <a href="<?= BASE_URL ?>/hub/create_client" class="px-5 py-2.5 rounded-full bg-[#a9a4ff]/10 text-[#a9a4ff] font-bold text-sm hover:bg-[#a9a4ff]/20 transition-all">+ Create Another</a>
        <a href="<?= BASE_URL ?>/hub/sites" class="px-5 py-2.5 rounded-full bg-white/5 text-slate-300 font-bold text-sm hover:bg-white/10 transition-all">View All Sites</a>
      </div>
    </div>
  <?php else: ?>

  <!-- Step indicator -->
  <div class="flex gap-2">
    <?php foreach ([1 => 'Client Info', 2 => 'Site Setup', 3 => 'Review'] as $n => $label): ?>
      <div class="flex items-center gap-2 flex-1">
        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
             :class="step >= <?= $n ?> ? 'bg-[#685ef7] text-white' : 'bg-white/5 text-slate-500'">
          <?= $n ?>
        </div>
        <span class="text-xs font-bold" :class="step >= <?= $n ?> ? 'text-white' : 'text-slate-600'"><?= $label ?></span>
        <?php if ($n < 3): ?><div class="flex-1 h-px bg-white/5 mx-1"></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="POST" action="<?= BASE_URL ?>/hub/create_client">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

    <!-- Step 1 -->
    <div x-show="step === 1" class="bg-[#181828] rounded-xl border border-white/5 p-6 flex flex-col gap-4">
      <h3 class="text-base font-bold text-white font-headline">Client Info</h3>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Business Name *</label>
          <input type="text" name="name" required placeholder="Lemon Blue Marketing"
                 class="w-full bg-[#121220] rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">URL Slug *</label>
          <div class="flex rounded-xl overflow-hidden">
            <span class="inline-flex items-center px-3 bg-[#0d0d1a] text-slate-500 text-xs border-r border-white/5 flex-shrink-0">superpage.co.uk/</span>
            <input type="text" name="slug" required placeholder="lemonblue"
                   class="flex-1 bg-[#121220] px-3 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Client Email</label>
          <input type="email" name="email" placeholder="client@business.com"
                 class="w-full bg-[#121220] rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">WhatsApp Number</label>
          <input type="text" name="phone" placeholder="+44 7700 900000"
                 class="w-full bg-[#121220] rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
        </div>
      </div>
      <p class="text-xs text-slate-500">* At least one of email or phone is required.</p>
      <div class="flex justify-end">
        <button type="button" @click="step = 2"
                class="px-6 py-2.5 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm">
          Next →
        </button>
      </div>
    </div>

    <!-- Step 2 -->
    <div x-show="step === 2" class="bg-[#181828] rounded-xl border border-white/5 p-6 flex flex-col gap-4">
      <h3 class="text-base font-bold text-white font-headline">Site Setup <span class="text-slate-500 font-normal text-sm">— all optional, auto-generated if empty</span></h3>

      <div class="space-y-1.5">
        <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Hero — tagline / intro</label>
        <input type="text" name="hero" placeholder="Award-winning agency in London..."
               class="w-full bg-[#121220] rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
      </div>

      <div class="space-y-1.5">
        <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">About</label>
        <textarea name="about" rows="3" placeholder="Tell the story of the business..."
                  class="w-full bg-[#121220] rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50 resize-none"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <?php foreach ([1,2,3,4] as $i): ?>
        <div class="space-y-1.5">
          <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Service / Product <?= $i ?></label>
          <input type="text" name="service<?= $i ?>" placeholder="<?= $i <= 2 ? 'e.g. Social Media' : 'optional' ?>"
                 class="w-full bg-[#121220] rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
        </div>
        <?php endforeach; ?>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Primary Colour</label>
          <div class="flex gap-2 flex-wrap mt-1" x-data="{ picked: '#685ef7' }">
            <?php foreach ($colors as $c): ?>
            <label class="cursor-pointer">
              <input type="radio" name="color" value="<?= $c ?>" class="sr-only" <?= $c === '#685ef7' ? 'checked' : '' ?>>
              <div class="w-8 h-8 rounded-lg border-2 transition-all"
                   style="background:<?= $c ?>"
                   :class="'<?= $c ?>' === picked ? 'border-white' : 'border-transparent'"
                   @click="picked = '<?= $c ?>'"></div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Category</label>
          <select name="category" class="w-full bg-[#121220] rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
            <?php foreach ($categories as $val => $label): ?>
            <option value="<?= $val ?>"><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="flex justify-between">
        <button type="button" @click="step = 1" class="px-6 py-2.5 rounded-full bg-white/5 text-slate-300 font-bold text-sm">← Back</button>
        <button type="button" @click="step = 3" class="px-6 py-2.5 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm">Review →</button>
      </div>
    </div>

    <!-- Step 3 -->
    <div x-show="step === 3" class="bg-[#181828] rounded-xl border border-white/5 p-6 flex flex-col gap-4">
      <h3 class="text-base font-bold text-white font-headline">Review & Send</h3>
      <div class="bg-[#0d0d1a] rounded-xl p-4 text-sm text-slate-400 space-y-2">
        <p>✅ User account created with default password <strong class="text-white font-mono">Welcome<?= date('Y') ?>!</strong></p>
        <p>✅ Site created with Header, Hero, About, Services (up to 4), Contact, Footer</p>
        <p>✅ 1 year free subscription (expires <?= date('d M Y', strtotime('+365 days')) ?>)</p>
        <p>📱 WhatsApp + 📧 Email notification sent via n8n</p>
      </div>
      <div class="flex justify-between">
        <button type="button" @click="step = 2" class="px-6 py-2.5 rounded-full bg-white/5 text-slate-300 font-bold text-sm">← Back</button>
        <button type="submit" class="px-6 py-2.5 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm shadow-lg shadow-[#685ef7]/20 hover:brightness-110 transition-all flex items-center gap-2">
          <span class="material-symbols-outlined" style="font-size:18px">rocket_launch</span>
          Create Site & Notify Client
        </button>
      </div>
    </div>

  </form>
  <?php endif; ?>

</div>

<?php render_hub_footer(); ?>
```

- [ ] **Step 2: Commit**

```bash
git add pages/hub/create_client.php
git commit -m "feat: wizard Create Client Site no Hub (3 passos — Alpine.js)"
```

- [ ] **Step 3: Test manually**
  - Acede a `http://localhost/hub/create_client`
  - Preenche nome + slug + email
  - Clica Next → Next → Create Site & Notify Client
  - Verifica no MySQL: `SELECT * FROM sites WHERE slug = 'test-slug';`
  - Verifica blocos: `SELECT type, sort_order FROM blocks WHERE page_id = (SELECT id FROM pages WHERE site_id = LAST_SITE_ID);`

---

## Task 6: Bulk Import + Draft Queue

**Files:**
- Create: `pages/hub/import_clients.php`

- [ ] **Step 1: Create the import page**

```php
<?php
// pages/hub/import_clients.php — Importação bulk via CSV + fila de prospecção

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';
require_once __DIR__ . '/../../includes/prospecting.php';

$admin  = get_logged_user();
$error  = '';
$msg    = '';
$parsed = [];

// Processar CSV submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token.";
    } elseif ($_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $error = "Failed to upload file.";
    } else {
        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        $headers = array_map('trim', fgetcsv($handle));
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) continue;
            $data = array_combine(array_slice($headers, 0, count($row)), $row);
            // Validar campos obrigatórios
            $rowError = '';
            if (empty($data['name'])) $rowError = 'Missing name';
            elseif (empty($data['slug'])) $rowError = 'Missing slug';
            elseif (empty($data['email']) && empty($data['phone'])) $rowError = 'Need email or phone';
            elseif (db_fetch_one("SELECT id FROM sites WHERE slug = :s", [':s' => trim($data['slug'])])) $rowError = 'Slug already taken';

            $rows[] = ['data' => $data, 'error' => $rowError];
        }
        fclose($handle);
        $parsed = $rows;
    }
}

// Gerar drafts a partir de rows válidos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_drafts'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token.";
    } else {
        $rows = json_decode($_POST['rows_json'] ?? '[]', true);
        $created = 0;
        foreach ($rows as $row) {
            if (!empty($row['error'])) continue;
            $row['data']['status'] = 'draft';
            create_prospect_site($row['data'], (int)$admin['id']);
            $created++;
        }
        $msg = "{$created} draft site(s) created. Select them below to send.";
    }
}

$csrf_token = generate_csrf_token();

// Carregar fila de drafts
$drafts = db_fetch_all(
    "SELECT s.id, s.slug, s.created_at, u.name, u.email, u.phone
     FROM sites s
     JOIN users u ON u.id = s.user_id
     WHERE s.status = 'draft'
     ORDER BY s.created_at DESC"
);

render_hub_header("Import Clients");
?>

<div class="max-w-4xl mx-auto flex flex-col gap-6">

  <div>
    <span class="px-3 py-1 bg-[#a9a4ff]/10 text-[#a9a4ff] text-xs font-black rounded-full tracking-widest uppercase">Prospecting</span>
    <h1 class="text-3xl font-black font-headline text-white mt-2">Import Clients</h1>
    <p class="text-slate-400 text-sm mt-1">Upload a CSV to create multiple client sites at once.</p>
  </div>

  <?php if ($error): ?>
    <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
      <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">error</span>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>
  <?php if ($msg): ?>
    <div class="flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
      <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">check_circle</span>
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <!-- CSV Upload -->
  <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
      <h3 class="text-base font-bold text-white font-headline">Upload CSV</h3>
      <p class="text-xs text-slate-500 mt-1">Columns: <code class="bg-white/5 px-1 rounded text-[#a9a4ff]">name, slug, email, phone, hero, about, service1, service2, service3, service4, color, category</code></p>
    </div>
    <div class="p-6">
      <form method="POST" enctype="multipart/form-data" class="flex items-center gap-4">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="file" name="csv" accept=".csv" required class="flex-1 bg-[#121220] rounded-xl px-4 py-3 text-white text-sm file:mr-4 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#685ef7]/20 file:text-[#a9a4ff]">
        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#685ef7]/20 text-[#a9a4ff] font-bold text-sm hover:bg-[#685ef7]/30 transition-all flex-shrink-0">
          Parse CSV
        </button>
      </form>
    </div>
  </div>

  <!-- Parsed preview -->
  <?php if (!empty($parsed)): ?>
  <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
      <h3 class="text-base font-bold text-white font-headline"><?= count($parsed) ?> rows detected</h3>
      <?php $validCount = count(array_filter($parsed, fn($r) => empty($r['error']))); ?>
      <span class="text-xs text-slate-400"><?= $validCount ?> valid · <?= count($parsed) - $validCount ?> with errors</span>
    </div>
    <table class="w-full text-sm">
      <thead><tr class="border-b border-white/5 text-xs text-slate-500">
        <th class="px-4 py-3 text-left">Business</th>
        <th class="px-4 py-3 text-left">Slug</th>
        <th class="px-4 py-3 text-left">Email / Phone</th>
        <th class="px-4 py-3 text-left">Status</th>
      </tr></thead>
      <tbody>
        <?php foreach ($parsed as $row): ?>
        <tr class="border-b border-white/5">
          <td class="px-4 py-3 text-white font-medium"><?= htmlspecialchars($row['data']['name'] ?? '') ?></td>
          <td class="px-4 py-3 text-[#a9a4ff]"><?= htmlspecialchars($row['data']['slug'] ?? '') ?></td>
          <td class="px-4 py-3 text-slate-400 text-xs"><?= htmlspecialchars(($row['data']['email'] ?? '') . ' ' . ($row['data']['phone'] ?? '')) ?></td>
          <td class="px-4 py-3">
            <?php if ($row['error']): ?>
              <span class="bg-red-500/15 text-red-400 px-2 py-0.5 rounded-full text-xs">⚠ <?= htmlspecialchars($row['error']) ?></span>
            <?php else: ?>
              <span class="bg-emerald-500/15 text-emerald-400 px-2 py-0.5 rounded-full text-xs">✓ Ready</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($validCount > 0): ?>
    <div class="p-4 flex justify-end">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="generate_drafts" value="1">
        <input type="hidden" name="rows_json" value="<?= htmlspecialchars(json_encode($parsed)) ?>">
        <button type="submit" class="px-6 py-2.5 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm">
          ⚡ Generate <?= $validCount ?> Draft(s)
        </button>
      </form>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Draft Queue -->
  <?php if (!empty($drafts)): ?>
  <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden" x-data="{ selected: [] }">
    <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
      <h3 class="text-base font-bold text-white font-headline">Prospect Queue — <?= count($drafts) ?> draft(s)</h3>
      <button
        class="px-5 py-2 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm disabled:opacity-40"
        :disabled="selected.length === 0"
        @click="
          fetch('<?= BASE_URL ?>/api/endpoints/prospect_send', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ site_ids: selected, csrf_token: '<?= $csrf_token ?>' })
          }).then(r => r.json()).then(d => { if(d.success) window.location.reload(); else alert(d.error || 'Error'); })
        ">
        🚀 Send Selected (<span x-text="selected.length"></span>)
      </button>
    </div>
    <table class="w-full text-sm">
      <thead><tr class="border-b border-white/5 text-xs text-slate-500">
        <th class="px-4 py-3 w-10"><input type="checkbox" @change="e => selected = e.target.checked ? <?= json_encode(array_column($drafts, 'id')) ?> : []" class="accent-[#685ef7]"></th>
        <th class="px-4 py-3 text-left">Business</th>
        <th class="px-4 py-3 text-left">URL</th>
        <th class="px-4 py-3 text-left">Contact</th>
        <th class="px-4 py-3 text-left">Created</th>
      </tr></thead>
      <tbody>
        <?php foreach ($drafts as $d): ?>
        <tr class="border-b border-white/5">
          <td class="px-4 py-3"><input type="checkbox" :value="<?= $d['id'] ?>" x-model="selected" class="accent-[#685ef7]"></td>
          <td class="px-4 py-3 text-white font-medium"><?= htmlspecialchars($d['name']) ?></td>
          <td class="px-4 py-3"><a href="https://superpage.co.uk/<?= htmlspecialchars($d['slug']) ?>" target="_blank" class="text-[#a9a4ff] hover:underline text-xs"><?= htmlspecialchars($d['slug']) ?></a></td>
          <td class="px-4 py-3 text-slate-400 text-xs"><?= htmlspecialchars($d['email'] . ' ' . $d['phone']) ?></td>
          <td class="px-4 py-3 text-slate-500 text-xs"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div>

<?php render_hub_footer(); ?>
```

- [ ] **Step 2: Commit**

```bash
git add pages/hub/import_clients.php
git commit -m "feat: Import Clients — CSV upload + draft queue + send selected"
```

---

## Task 7: Send Drafts API Endpoint

**Files:**
- Create: `api/endpoints/prospect_send.php`

- [ ] **Step 1: Create endpoint**

```php
<?php
// api/endpoints/prospect_send.php
// AJAX: recebe array de site_ids, dispara webhook e activa os drafts

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/prospecting.php';

header('Content-Type: application/json');

if (!is_logged_in() || get_logged_user()['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$siteIds  = array_map('intval', $input['site_ids'] ?? []);
$csrf     = $input['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

if (empty($siteIds)) {
    echo json_encode(['error' => 'No sites selected']);
    exit;
}

$admin   = get_logged_user();
$sent    = 0;
$errors  = [];

foreach ($siteIds as $siteId) {
    $site = db_fetch_one("SELECT s.*, u.name, u.email, u.phone FROM sites s JOIN users u ON u.id = s.user_id WHERE s.id = :id AND s.status = 'draft'", [':id' => $siteId]);
    if (!$site) {
        $errors[] = "Site {$siteId} not found or not a draft";
        continue;
    }

    $password    = 'Welcome' . date('Y') . '!';
    $siteUrl     = 'https://superpage.co.uk/' . $site['slug'];

    $payload = [
        'event'         => 'prospect_site_created',
        'business_name' => $site['name'],
        'site_url'      => $siteUrl,
        'email'         => $site['email'] ?? '',
        'phone'         => $site['phone'] ?? '',
        'password'      => $password,
        'has_email'     => !empty($site['email']),
        'has_phone'     => !empty($site['phone']),
    ];

    fire_prospect_webhook($siteId, (int)$admin['id'], $payload);
    $sent++;
}

echo json_encode(['success' => true, 'sent' => $sent, 'errors' => $errors]);
```

- [ ] **Step 2: Commit**

```bash
git add api/endpoints/prospect_send.php
git commit -m "feat: endpoint AJAX prospect_send — dispara webhook e activa drafts"
```

---

## Task 8: Router + Final Push

**Files:**
- Modify: `router.php`

- [ ] **Step 1: Verify hub routes exist**

Open `router.php` and confirm hub routes are handled. If there's a catch-all for `/hub/*` that includes PHP files automatically, no change is needed. Otherwise, add:

```php
'/hub/create_client'  => 'pages/hub/create_client.php',
'/hub/import_clients' => 'pages/hub/import_clients.php',
```

- [ ] **Step 2: Add webhook URL to .env on production**

On the VPS, in the `.env` file inside the container, add:
```
N8N_PROSPECT_WEBHOOK_URL=http://n8n_app:5678/webhook/prospect-site
```
(Replace with the actual n8n webhook URL after creating the workflow)

- [ ] **Step 3: Final push**

```bash
git push origin main
```

- [ ] **Step 4: Test end-to-end**
  - Log in as admin on `https://superpage.co.uk/hub/create_client`
  - Create a test client with your own WhatsApp/email
  - Confirm site appears at `superpage.co.uk/{slug}`
  - Confirm WhatsApp/email received (after n8n workflow is configured)
  - Test bulk: upload a 2-row CSV, generate drafts, send selected
