<?php
// HUB - Gestão de Utilizadores

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

$currentAdmin = get_logged_user();
$msg   = '';
$error = '';

// Action handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session. Please try again.";
    } else {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $action   = $_POST['action'] ?? '';

        // Impede que o admin se altere a si próprio
        if ($targetId === (int)$currentAdmin['id']) {
            $error = "You cannot modify your own account here.";
        } elseif ($action === 'set_role' && in_array($_POST['role'] ?? '', ['client', 'partner', 'admin'])) {
            $newRole = $_POST['role'];
            db_update('users', ['role' => $newRole], 'id = :id', [':id' => $targetId]);
            db_insert('hub_audit_logs', [
                'admin_id'    => $currentAdmin['id'],
                'action_type' => 'user_role_change',
                'entity_type' => 'users',
                'entity_id'   => $targetId,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            $msg = "Role updated successfully.";
        } elseif ($action === 'suspend') {
            db_update('users', ['status' => 'suspended'], 'id = :id', [':id' => $targetId]);
            db_insert('hub_audit_logs', [
                'admin_id'    => $currentAdmin['id'],
                'action_type' => 'user_suspend',
                'entity_type' => 'users',
                'entity_id'   => $targetId,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            $msg = "User suspended.";
        } elseif ($action === 'activate') {
            db_update('users', ['status' => 'active'], 'id = :id', [':id' => $targetId]);
            db_insert('hub_audit_logs', [
                'admin_id'    => $currentAdmin['id'],
                'action_type' => 'user_activate',
                'entity_type' => 'users',
                'entity_id'   => $targetId,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            $msg = "User activated.";
        } else {
            $error = "Invalid action.";
        }
    }
}

// Search / filter
$search     = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$params     = [];
$where      = 'WHERE 1=1';

if ($search !== '') {
    $where .= ' AND (u.name LIKE :q OR u.email LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}
if (in_array($roleFilter, ['client', 'partner', 'admin'])) {
    $where .= ' AND u.role = :role';
    $params[':role'] = $roleFilter;
}

$users = db_fetch_all(
    "SELECT u.*, COUNT(s.id) as site_count
     FROM users u
     LEFT JOIN sites s ON s.user_id = u.id
     $where
     GROUP BY u.id
     ORDER BY u.role = 'admin' DESC, u.created_at DESC",
    $params
);

$csrf = generate_csrf_token();
render_hub_header("Users");
?>

<div class="max-w-6xl flex flex-col gap-6">

    <?php if ($msg): ?>
    <div class="flex items-center gap-3 bg-green-500/10 border border-green-500/20 text-green-400 px-4 py-3 rounded-xl text-sm">
        <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1">check_circle</span>
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl text-sm">
        <span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:'FILL' 1">error</span>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Header + filters -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white font-headline">All Users</h2>
            <p class="text-sm text-on-surface-variant mt-0.5"><?= count($users) ?> users found</p>
        </div>

        <form method="GET" action="" class="flex items-center gap-2 flex-wrap">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search name or email…"
                   class="bg-[#121220] border border-white/10 rounded-xl px-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-[#a9a4ff]/50 w-56">
            <select name="role"
                    class="bg-[#121220] border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-[#a9a4ff]/50 appearance-none">
                <option value="">All roles</option>
                <option value="admin"   <?= $roleFilter === 'admin'   ? 'selected' : '' ?>>Admin</option>
                <option value="partner" <?= $roleFilter === 'partner' ? 'selected' : '' ?>>Partner</option>
                <option value="client"  <?= $roleFilter === 'client'  ? 'selected' : '' ?>>Client</option>
            </select>
            <button type="submit"
                    class="px-4 py-2 bg-[#685ef7] hover:bg-[#685ef7]/80 text-white text-sm font-bold rounded-xl transition-colors">
                Filter
            </button>
            <?php if ($search || $roleFilter): ?>
            <a href="<?= BASE_URL ?>/hub/users"
               class="px-4 py-2 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white text-sm font-bold rounded-xl transition-colors">
                Clear
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-[#121220] rounded-2xl border border-white/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5 text-on-surface-variant text-xs uppercase tracking-widest">
                        <th class="text-left px-6 py-4 font-bold">User</th>
                        <th class="text-left px-4 py-4 font-bold">Role</th>
                        <th class="text-left px-4 py-4 font-bold hidden md:table-cell">Sites</th>
                        <th class="text-left px-4 py-4 font-bold hidden lg:table-cell">Joined</th>
                        <th class="text-right px-6 py-4 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                <?php foreach ($users as $u):
                    $isSelf      = (int)$u['id'] === (int)$currentAdmin['id'];
                    $isSuspended = ($u['status'] ?? 'active') === 'suspended';
                    $roleColors  = [
                        'admin'   => 'bg-[#a9a4ff]/15 text-[#a9a4ff]',
                        'partner' => 'bg-[#914feb]/15 text-[#c084fc]',
                        'client'  => 'bg-white/5 text-slate-400',
                    ];
                    $roleColor = $roleColors[$u['role']] ?? $roleColors['client'];
                ?>
                <tr class="hover:bg-white/[0.02] transition-colors <?= $isSuspended ? 'opacity-50' : '' ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#685ef7] to-[#914feb] flex items-center justify-center text-white text-xs font-black flex-shrink-0">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-white truncate">
                                    <?= htmlspecialchars($u['name']) ?>
                                    <?php if ($isSelf): ?>
                                    <span class="text-[10px] text-[#a9a4ff] font-bold ml-1">(you)</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-on-surface-variant truncate"><?= htmlspecialchars($u['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $roleColor ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                        <?php if ($isSuspended): ?>
                        <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-500/10 text-red-400">Suspended</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 hidden md:table-cell text-on-surface-variant">
                        <?= (int)$u['site_count'] ?>
                    </td>
                    <td class="px-4 py-4 hidden lg:table-cell text-on-surface-variant">
                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if (!$isSelf): ?>
                        <div x-data="{ open: false }" class="relative flex justify-end">
                            <button @click="open = !open" @click.outside="open = false"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-400 hover:text-white bg-white/5 hover:bg-white/10 transition-all">
                                Actions
                                <span class="material-symbols-outlined" style="font-size:14px">expand_more</span>
                            </button>

                            <div x-show="open" x-transition
                                 class="absolute right-0 top-8 z-10 w-48 bg-[#1e1e2f] border border-white/10 rounded-xl shadow-2xl overflow-hidden">

                                <!-- Change role -->
                                <p class="px-4 pt-3 pb-1 text-[10px] text-on-surface-variant uppercase tracking-widest font-bold">Change Role</p>
                                <?php foreach (['client', 'partner', 'admin'] as $role):
                                    if ($role === $u['role']) continue; ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id"   value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action"    value="set_role">
                                    <input type="hidden" name="role"      value="<?= $role ?>">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-white/5 transition-colors flex items-center gap-2">
                                        <span class="material-symbols-outlined text-on-surface-variant" style="font-size:16px">
                                            <?= $role === 'admin' ? 'admin_panel_settings' : ($role === 'partner' ? 'handshake' : 'person') ?>
                                        </span>
                                        Set as <?= ucfirst($role) ?>
                                    </button>
                                </form>
                                <?php endforeach; ?>

                                <div class="border-t border-white/5 mt-1">
                                <?php if ($isSuspended): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id"   value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action"    value="activate">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2.5 text-sm text-green-400 hover:text-green-300 hover:bg-green-500/10 transition-colors flex items-center gap-2">
                                        <span class="material-symbols-outlined" style="font-size:16px">check_circle</span>
                                        Activate
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id"   value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action"    value="suspend">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors flex items-center gap-2">
                                        <span class="material-symbols-outlined" style="font-size:16px">block</span>
                                        Suspend
                                    </button>
                                </form>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <span class="text-xs text-on-surface-variant">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">person_search</span>
                        No users found.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php render_hub_footer(); ?>
