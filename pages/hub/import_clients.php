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
