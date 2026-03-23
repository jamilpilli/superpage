<?php
// HUB - Configurações Globais (Settings) e Log de Auditoria

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF Inválido.";
    } else {
        $updates = 0;
        foreach ($_POST['settings'] ?? [] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET key_value = :val WHERE key_name = :key");
            $res = $stmt->execute([':val' => trim($value), ':key' => $key]);
            if ($res && $stmt->rowCount() > 0) {
                $updates++;
            }
        }
        
        if ($updates > 0) {
            db_insert('hub_audit_logs', [
                'admin_id' => get_logged_user()['id'],
                'action_type' => "settings_update",
                'entity_type' => 'settings',
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            $_SESSION['hub_msg'] = "$updates configuração(ões) salva(s) com sucesso.";
        }
        redirect('/hub/settings');
    }
}

$allSettings = db_fetch_all("SELECT * FROM settings ORDER BY group_name ASC, key_name ASC");

// Agrupar por categoria
$groupedSettings = [];
foreach ($allSettings as $s) {
    // Tratamento visual para o nome do grupo
    $gname = match($s['group_name']) {
        'general' => 'Geral do Sistema',
        'billing' => 'Faturamento e Planos',
        'email'   => 'Servidor de E-mail (SMTP)',
        default   => ucfirst($s['group_name'])
    };
    $groupedSettings[$gname][] = $s;
}

$csrf_token = generate_csrf_token();
render_hub_header("Global Settings");
?>

<div class="max-w-3xl flex flex-col gap-6">

    <?php if (isset($_SESSION['hub_msg'])): ?>
        <div class="flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">check_circle</span>
            <?= htmlspecialchars($_SESSION['hub_msg']) ?>
            <?php unset($_SESSION['hub_msg']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/hub/settings" class="flex flex-col gap-6">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <?php foreach ($groupedSettings as $groupLabel => $items): ?>
        <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden">
            <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:18px">tune</span>
                </div>
                <h3 class="text-base font-bold text-white font-headline"><?= htmlspecialchars($groupLabel) ?></h3>
            </div>
            <div class="p-6 space-y-5">
                <?php foreach ($items as $item): ?>
                <div class="space-y-1.5">
                    <label for="set_<?= $item['id'] ?>" class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">
                        <?= ucwords(str_replace('_', ' ', $item['key_name'])) ?>
                    </label>
                    <?php if ($item['type'] === 'boolean'): ?>
                        <select name="settings[<?= $item['key_name'] ?>]" id="set_<?= $item['id'] ?>"
                                class="w-full bg-[#121220] border-none rounded-xl p-4 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/50 transition-all appearance-none">
                            <option value="true"  <?= $item['key_value'] === 'true'  ? 'selected' : '' ?>>Yes / Enabled</option>
                            <option value="false" <?= $item['key_value'] === 'false' ? 'selected' : '' ?>>No / Disabled</option>
                        </select>
                    <?php else: ?>
                        <input type="<?= $item['type'] === 'string' ? 'text' : 'number' ?>"
                               step="<?= $item['type'] === 'integer' ? '1' : '0.01' ?>"
                               name="settings[<?= $item['key_name'] ?>]"
                               id="set_<?= $item['id'] ?>"
                               value="<?= htmlspecialchars($item['key_value']) ?>"
                               class="w-full bg-[#121220] border-none rounded-xl p-4 text-white text-sm placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all">
                    <?php endif; ?>
                    <p class="text-xs text-slate-600">Key: <code class="bg-white/5 px-1.5 py-0.5 rounded text-slate-400"><?= htmlspecialchars($item['key_name']) ?></code></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="flex justify-end pb-8">
            <button type="submit"
                    class="px-8 py-2.5 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm shadow-lg shadow-[#685ef7]/20 hover:brightness-110 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined" style="font-size:18px">save</span>
                Save Settings
            </button>
        </div>
    </form>

</div>

<?php render_hub_footer(); ?>
