<?php
// HUB - Gestão de Sites

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

// Action Handler (Bloquear / Ativar sites)
if (isset($_POST['action']) && isset($_POST['site_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Token CSRF inválido.";
    } else {
        $siteId = (int)$_POST['site_id'];
        $action = $_POST['action'];
        
        $newStatus = ($action === 'block') ? 'suspended' : 'active';
        db_update('sites', ['status' => $newStatus], 'id = :id', [':id' => $siteId]);
        
        // Registrar Auditoria
        db_insert('hub_audit_logs', [
            'admin_id' => get_logged_user()['id'],
            'action_type' => "site_{$action}",
            'entity_type' => 'sites',
            'entity_id' => $siteId,
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        $_SESSION['hub_msg'] = "Site alterado para $newStatus com sucesso.";
        redirect('/hub/sites');
    }
}

// Busca todos os sites
$sites = db_fetch_all("
    SELECT s.*, u.name as owner_name, u.email as owner_email, t.name as theme_name
    FROM sites s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN themes t ON s.theme_id = t.id
    ORDER BY s.created_at DESC
");

$csrf_token = generate_csrf_token();
render_hub_header("Sites Management");
?>

<div class="max-w-6xl flex flex-col gap-6">

    <?php if (isset($_SESSION['hub_msg'])): ?>
        <div class="flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">check_circle</span>
            <?= htmlspecialchars($_SESSION['hub_msg']) ?>
            <?php unset($_SESSION['hub_msg']); ?>
        </div>
    <?php endif; ?>

    <!-- Sites table -->
    <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:18px">language</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-white font-headline">All Sites</h3>
                <p class="text-xs text-slate-500"><?= count($sites) ?> site<?= count($sites) !== 1 ? 's' : '' ?> on the platform.</p>
            </div>
        </div>

        <?php if (empty($sites)): ?>
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <span class="material-symbols-outlined text-4xl text-slate-700 mb-3">language</span>
                <p class="text-sm text-slate-500">No sites published on the platform yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Site / Domain</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Theme</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($sites as $s): ?>
                        <tr class="hover:bg-white/2 transition-colors">
                            <td class="px-6 py-4">
                                <a href="<?= BASE_URL ?>/<?= $s['slug'] ?>" target="_blank"
                                   class="text-sm font-bold text-[#a9a4ff] hover:text-white transition-colors flex items-center gap-1.5">
                                    <?= htmlspecialchars($s['slug']) ?>
                                    <span class="material-symbols-outlined" style="font-size:14px">open_in_new</span>
                                </a>
                                <?php if ($s['domain']): ?>
                                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($s['domain']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-white"><?= htmlspecialchars($s['owner_name']) ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($s['owner_email']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($s['status'] === 'active'): ?>
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-500/10 text-emerald-400">Active</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-500/10 text-red-400"><?= ucfirst($s['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400">
                                <?= $s['theme_name'] ? htmlspecialchars($s['theme_name']) : '—' ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= BASE_URL ?>/dashboard/content?site_id=<?= $s['id'] ?>"
                                       class="px-3 py-1.5 text-xs font-bold rounded-lg bg-[#685ef7]/20 text-[#a9a4ff] hover:bg-[#685ef7]/40 transition-all">
                                        Edit Content
                                    </a>
                                    <a href="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $s['id'] ?>"
                                       class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white/5 text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                                        Settings
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>/hub/sites" class="inline"
                                          onsubmit="return confirm('Are you sure you want to change this site\'s status?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="site_id" value="<?= $s['id'] ?>">
                                        <?php if ($s['status'] === 'active'): ?>
                                            <input type="hidden" name="action" value="block">
                                            <button type="submit"
                                                    class="px-3 py-1.5 text-xs font-bold rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-all">
                                                Suspend
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="unblock">
                                            <button type="submit"
                                                    class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-all">
                                                Reactivate
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php render_hub_footer(); ?>
