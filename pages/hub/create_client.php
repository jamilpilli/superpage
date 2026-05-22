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
