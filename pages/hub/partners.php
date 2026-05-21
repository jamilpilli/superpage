<?php
// HUB - Gestão de Parceiros (Agências que revendem a plataforma)

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

$admin = get_logged_user();
$hubMsg = '';
$hubError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $hubError = "Invalid session. Please try again.";
    } else {
        $uId   = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($uId && $uId !== (int)$admin['id']) {
            if ($action === 'make_partner') {
                db_update('users', ['role' => 'partner'], 'id = :id', [':id' => $uId]);
                db_insert('hub_audit_logs', ['admin_id' => $admin['id'], 'action_type' => 'role_make_partner', 'entity_type' => 'users', 'entity_id' => $uId, 'ip_address' => $_SERVER['REMOTE_ADDR']]);
                $hubMsg = "Partner status granted.";
            } elseif ($action === 'revoke_partner') {
                db_update('users', ['role' => 'client'], 'id = :id', [':id' => $uId]);
                db_insert('hub_audit_logs', ['admin_id' => $admin['id'], 'action_type' => 'role_revoke_partner', 'entity_type' => 'users', 'entity_id' => $uId, 'ip_address' => $_SERVER['REMOTE_ADDR']]);
                $hubMsg = "Partner status revoked.";
            } elseif ($action === 'set_limits') {
                $maxClients = $_POST['max_clients'] !== '' ? (int)$_POST['max_clients'] : null;
                $maxSites   = $_POST['max_sites']   !== '' ? (int)$_POST['max_sites']   : null;
                db_update('users', ['partner_max_clients' => $maxClients, 'partner_max_sites' => $maxSites], 'id = :id', [':id' => $uId]);
                db_insert('hub_audit_logs', ['admin_id' => $admin['id'], 'action_type' => 'partner_limits_update', 'entity_type' => 'users', 'entity_id' => $uId, 'ip_address' => $_SERVER['REMOTE_ADDR']]);
                $hubMsg = "Limits updated.";
            }
        }
    }
}

// Filtro simples de listagem: todos ou só partners
$onlyPartners = isset($_GET['full']) ? false : true;

$sql = "SELECT u.id, u.name, u.email, u.role, u.created_at, u.partner_max_clients, u.partner_max_sites,
        (SELECT COUNT(s.id) FROM sites s WHERE s.user_id = u.id) as sites_count,
        (SELECT COUNT(pc.id) FROM partner_clients pc WHERE pc.partner_id = u.id) as clients_count
        FROM users u WHERE role != 'admin'";

if ($onlyPartners) {
    $sql .= " AND role = 'partner'";
}
$sql .= " ORDER BY u.created_at DESC";

$users = db_fetch_all($sql);
$csrf_token = generate_csrf_token();

render_hub_header("Partners Management");

// Flash messages
if (isset($_SESSION['hub_msg'])) { $hubMsg = $_SESSION['hub_msg']; unset($_SESSION['hub_msg']); }
?>

<div class="max-w-6xl flex flex-col gap-6">

    <!-- Header actions -->
    <div class="flex items-center justify-between gap-4">
        <p class="text-sm text-slate-400">Partners receive discounts on multi-site renewals.</p>
        <?php if ($onlyPartners): ?>
            <a href="?full=1"
               class="flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-full bg-[#181828] border border-white/10 text-slate-300 hover:text-white hover:bg-white/5 transition-all">
                <span class="material-symbols-outlined" style="font-size:16px">group</span>
                Show All Clients
            </a>
        <?php else: ?>
            <a href="?"
               class="flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-full bg-[#a9a4ff]/10 border border-[#a9a4ff]/20 text-[#a9a4ff] hover:bg-[#a9a4ff]/20 transition-all">
                <span class="material-symbols-outlined" style="font-size:16px">handshake</span>
                Show Partners Only
            </a>
        <?php endif; ?>
    </div>

    <?php if ($hubMsg): ?>
    <div class="flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
        <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">check_circle</span>
        <?= htmlspecialchars($hubMsg) ?>
    </div>
    <?php endif; ?>
    <?php if ($hubError): ?>
    <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
        <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">error</span>
        <?= htmlspecialchars($hubError) ?>
    </div>
    <?php endif; ?>

    <!-- Partners table -->
    <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:18px">handshake</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-white font-headline"><?= $onlyPartners ? 'VIP Partners' : 'All Users' ?></h3>
                <p class="text-xs text-slate-500"><?= count($users) ?> record<?= count($users) !== 1 ? 's' : '' ?> found.</p>
            </div>
        </div>

        <?php if (empty($users)): ?>
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <span class="material-symbols-outlined text-4xl text-slate-700 mb-3">handshake</span>
                <p class="text-sm text-slate-500">No partners found.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Clients</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Sites</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Limits</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-white/[0.02] transition-colors" x-data="{ limitsOpen: false }">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-white"><?= htmlspecialchars($u['name']) ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($u['email']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400">
                                <?= (int)$u['clients_count'] ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-white">
                                <?= (int)$u['sites_count'] ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400">
                                <?php if ($u['role'] === 'partner'): ?>
                                <button @click="limitsOpen = !limitsOpen" class="flex items-center gap-1 text-[#a9a4ff] hover:text-white transition-colors font-bold">
                                    <?= $u['partner_max_clients'] !== null ? $u['partner_max_clients'] . ' clients' : '∞' ?>
                                    / <?= $u['partner_max_sites'] !== null ? $u['partner_max_sites'] . ' sites' : '∞' ?>
                                    <span class="material-symbols-outlined" style="font-size:14px">edit</span>
                                </button>
                                <!-- Limits form -->
                                <div x-show="limitsOpen" x-transition class="mt-2">
                                    <form method="POST" class="flex items-center gap-2 flex-wrap">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="user_id"   value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action"    value="set_limits">
                                        <input type="number" name="max_clients" min="1" placeholder="∞ clients"
                                               value="<?= $u['partner_max_clients'] ?? '' ?>"
                                               class="w-24 bg-[#0d0d1a] border border-white/10 rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:border-[#a9a4ff]/50">
                                        <input type="number" name="max_sites" min="1" placeholder="∞ sites"
                                               value="<?= $u['partner_max_sites'] ?? '' ?>"
                                               class="w-24 bg-[#0d0d1a] border border-white/10 rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:border-[#a9a4ff]/50">
                                        <button type="submit" class="px-2 py-1 bg-[#685ef7] text-white text-xs font-bold rounded-lg hover:bg-[#685ef7]/80 transition-colors">Save</button>
                                    </form>
                                    <p class="text-[10px] text-on-surface-variant mt-1">Leave blank for no limit.</p>
                                </div>
                                <?php else: ?>
                                —
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($u['role'] === 'partner'): ?>
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-[#a9a4ff]/10 text-[#a9a4ff]">Partner</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-white/5 text-slate-400">Client</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="<?= BASE_URL ?>/hub/partners" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($u['role'] === 'partner'): ?>
                                        <input type="hidden" name="action" value="revoke_partner">
                                        <button type="submit" onclick="return confirm('Remove partner status?')"
                                                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-all">
                                            Revoke
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="make_partner">
                                        <button type="submit"
                                                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-[#a9a4ff]/10 text-[#a9a4ff] hover:bg-[#a9a4ff]/20 transition-all">
                                            Make Partner
                                        </button>
                                    <?php endif; ?>
                                </form>
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
