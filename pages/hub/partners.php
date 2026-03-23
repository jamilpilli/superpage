<?php
// HUB - Gestão de Parceiros (Agências que revendem a plataforma)

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

if (isset($_POST['action']) && isset($_POST['user_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Token CSRF inválido.";
    } else {
        $uId = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        $newRole = '';
        if ($action === 'make_partner') $newRole = 'partner';
        if ($action === 'revoke_partner') $newRole = 'client';
        
        if ($newRole && $uId !== get_logged_user()['id']) {
            db_update('users', ['role' => $newRole], 'id = :id', [':id' => $uId]);
            db_insert('hub_audit_logs', [
                'admin_id' => get_logged_user()['id'],
                'action_type' => "role_$action",
                'entity_type' => 'users',
                'entity_id' => $uId,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            $_SESSION['hub_msg'] = "Permissões de parceiro atualizadas.";
        }
        redirect('/hub/partners');
    }
}

// Filtro simples de listagem: todos ou só partners
$onlyPartners = isset($_GET['full']) ? false : true;

$sql = "SELECT u.id, u.name, u.email, u.role, u.created_at, 
        (SELECT COUNT(s.id) FROM sites s WHERE s.user_id = u.id) as sites_count 
        FROM users u WHERE role != 'admin'";

if ($onlyPartners) {
    $sql .= " AND role = 'partner'";
}
$sql .= " ORDER BY u.created_at DESC";

$users = db_fetch_all($sql);
$csrf_token = generate_csrf_token();

render_hub_header("Partners Management");
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

    <?php if (isset($_SESSION['hub_msg'])): ?>
        <div class="flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">check_circle</span>
            <?= htmlspecialchars($_SESSION['hub_msg']) ?>
            <?php unset($_SESSION['hub_msg']); ?>
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
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Sites</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-white/2 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-white"><?= htmlspecialchars($u['name']) ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($u['email']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400">
                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-white">
                                <?= $u['sites_count'] ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($u['role'] === 'partner'): ?>
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-[#a9a4ff]/10 text-[#a9a4ff]">VIP Partner</span>
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
                                        <button type="submit"
                                                onclick="return confirm('Remove partner status?')"
                                                class="px-4 py-1.5 text-xs font-bold rounded-full bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition-all">
                                            Revoke Partner
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="make_partner">
                                        <button type="submit"
                                                class="px-4 py-1.5 text-xs font-bold rounded-full bg-[#a9a4ff]/10 border border-[#a9a4ff]/20 text-[#a9a4ff] hover:bg-[#a9a4ff]/20 transition-all">
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
