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
        $handle  = fopen($_FILES['csv']['tmp_name'], 'r');
        $headers = array_map('trim', fgetcsv($handle));
        $rows    = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) continue;
            $data     = array_combine(array_slice($headers, 0, count($row)), $row);
            $rowError = '';
            if (empty($data['name']))                                          $rowError = 'Missing name';
            elseif (empty($data['slug']))                                      $rowError = 'Missing slug';
            elseif (empty($data['email']) && empty($data['phone']))            $rowError = 'Need email or phone';
            elseif (db_fetch_one("SELECT id FROM sites WHERE slug = :s", [':s' => trim($data['slug'])])) $rowError = 'Slug already taken';
            $rows[] = ['data' => $data, 'error' => $rowError];
        }
        fclose($handle);
        $parsed = $rows;
    }
}

// Gerar sites activos a partir de rows válidos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_drafts'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session token.";
    } else {
        $rows    = json_decode($_POST['rows_json'] ?? '[]', true);
        $created = 0;
        foreach ($rows as $row) {
            if (!empty($row['error'])) continue;
            $row['data']['status'] = 'active';
            create_prospect_site($row['data'], (int)$admin['id']);
            $created++;
        }
        $msg = "{$created} site(s) created and live. Notify clients below.";
    }
}

$csrf_token = generate_csrf_token();

// Carregar fila de prospecção — todos os sites criados via hub (audit log),
// com estado de notificação via prospect_log
$prospects = db_fetch_all(
    "SELECT s.id, s.slug, s.status, s.created_at, u.name, u.email, u.phone,
            pl.notified_via, pl.notified_at
     FROM hub_audit_logs hal
     JOIN sites s ON s.id = hal.entity_id
     JOIN users u ON u.id = s.user_id
     LEFT JOIN prospect_log pl ON pl.site_id = s.id
     WHERE hal.action_type = 'prospect_site_created'
       AND hal.entity_type = 'sites'
     GROUP BY s.id
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
          ⚡ Create <?= $validCount ?> Site(s)
        </button>
      </form>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Prospect Queue -->
  <?php if (!empty($prospects)): ?>
  <?php
    $unnotifiedIds = array_column(array_filter($prospects, fn($p) => empty($p['notified_at'])), 'id');
  ?>
  <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden" x-data="{ selected: [] }">
    <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
      <h3 class="text-base font-bold text-white font-headline">
        Prospect Queue — <?= count($prospects) ?> site(s)
        <span class="text-slate-500 font-normal text-sm ml-2"><?= count($unnotifiedIds) ?> not yet notified</span>
      </h3>
      <button
        class="px-5 py-2 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm disabled:opacity-40"
        :disabled="selected.length === 0"
        @click="
          fetch('<?= BASE_URL ?>/api/prospect_send', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ site_ids: selected, csrf_token: '<?= $csrf_token ?>' })
          }).then(r => r.json()).then(d => { if(d.success) window.location.reload(); else alert(d.error || 'Error'); })
        ">
        🚀 Notify Selected (<span x-text="selected.length"></span>)
      </button>
    </div>
    <table class="w-full text-sm">
      <thead><tr class="border-b border-white/5 text-xs text-slate-500">
        <th class="px-4 py-3 w-10"><input type="checkbox" @change="e => selected = e.target.checked ? <?= json_encode($unnotifiedIds) ?> : []" class="accent-[#685ef7]"></th>
        <th class="px-4 py-3 text-left">Business</th>
        <th class="px-4 py-3 text-left">URL</th>
        <th class="px-4 py-3 text-left">Contact</th>
        <th class="px-4 py-3 text-left">Notification</th>
        <th class="px-4 py-3 text-left">Created</th>
      </tr></thead>
      <tbody>
        <?php foreach ($prospects as $p): ?>
        <tr class="border-b border-white/5">
          <td class="px-4 py-3">
            <?php if (empty($p['notified_at'])): ?>
              <input type="checkbox" :value="<?= $p['id'] ?>" x-model="selected" class="accent-[#685ef7]">
            <?php else: ?>
              <span class="material-symbols-outlined text-emerald-500/40" style="font-size:18px">check</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-white font-medium"><?= htmlspecialchars($p['name']) ?></td>
          <td class="px-4 py-3"><a href="https://superpage.co.uk/<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="text-[#a9a4ff] hover:underline text-xs"><?= htmlspecialchars($p['slug']) ?></a></td>
          <td class="px-4 py-3 text-slate-400 text-xs"><?= htmlspecialchars(trim($p['email'] . ' ' . $p['phone'])) ?></td>
          <td class="px-4 py-3">
            <?php if (!empty($p['notified_at'])): ?>
              <span class="bg-emerald-500/15 text-emerald-400 px-2 py-0.5 rounded-full text-xs">
                ✓ Notified · <?= htmlspecialchars(ucfirst($p['notified_via'])) ?>
              </span>
            <?php else: ?>
              <span class="bg-amber-500/15 text-amber-400 px-2 py-0.5 rounded-full text-xs">
                Not notified
              </span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-slate-500 text-xs"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div>

<?php render_hub_footer(); ?>
