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

        // Criar novo admin
        if ($action === 'create_admin') {
            $newName  = trim($_POST['new_name'] ?? '');
            $newEmail = trim($_POST['new_email'] ?? '');
            $newPass  = trim($_POST['new_password'] ?? '');
            if (empty($newName) || empty($newEmail) || empty($newPass)) {
                $error = "All fields are required.";
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email address.";
            } elseif (strlen($newPass) < 6) {
                $error = "Password must be at least 6 characters.";
            } elseif (db_fetch_one("SELECT id FROM users WHERE email = :e", [':e' => $newEmail])) {
                $error = "Email already in use.";
            } else {
                $newId = db_insert('users', [
                    'name'          => $newName,
                    'email'         => $newEmail,
                    'password_hash' => password_hash($newPass, PASSWORD_DEFAULT, ['cost' => HASH_COST]),
                    'role'          => 'admin',
                ]);
                db_insert('hub_audit_logs', ['admin_id' => $currentAdmin['id'], 'action_type' => 'user_create_admin', 'entity_type' => 'users', 'entity_id' => $newId, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '']);
                $msg = "Admin account created for {$newEmail}.";
            }
        }

        // Acções que requerem um targetId específico
        if ($action === 'create_admin') {
            // já tratado acima — não cair no bloco seguinte
        } elseif ($targetId === (int)$currentAdmin['id']) {
            $error = "You cannot modify your own account here.";
        } elseif ($action === 'delete') {
            $siteCount = (int)(db_fetch_one("SELECT COUNT(id) as t FROM sites WHERE user_id = :id", [':id' => $targetId])['t'] ?? 0);
            if ($siteCount > 0) {
                $error = "Cannot delete user with active sites. Remove their sites first.";
            } else {
                db_insert('hub_audit_logs', [
                    'admin_id'    => $currentAdmin['id'],
                    'action_type' => 'user_delete',
                    'entity_type' => 'users',
                    'entity_id'   => $targetId,
                    'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
                global $pdo;
                $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $targetId]);
                $msg = "User deleted.";
            }
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

// Contagens por role para os stats cards
$allCounts = db_fetch_all("SELECT role, COUNT(*) as cnt FROM users GROUP BY role", []);
$roleCounts = ['admin' => 0, 'partner' => 0, 'client' => 0];
foreach ($allCounts as $rc) { $roleCounts[$rc['role']] = (int)$rc['cnt']; }
$totalUsers = array_sum($roleCounts);

$csrf = generate_csrf_token();
render_hub_header("Users");
?>

<div class="max-w-6xl flex flex-col gap-6">

    <!-- Flash messages -->
    <?php if ($msg): ?>
    <div class="flex items-center gap-3 bg-green-500/10 border border-green-500/20 text-green-400 px-4 py-3 rounded-xl text-sm">
        <span class="material-symbols-outlined flex-shrink-0" style="font-size:18px;font-variation-settings:'FILL' 1">check_circle</span>
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl text-sm">
        <span class="material-symbols-outlined flex-shrink-0" style="font-size:18px;font-variation-settings:'FILL' 1">error</span>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-white font-headline">Users</h2>
            <p class="text-sm text-on-surface-variant mt-0.5"><?= $totalUsers ?> total accounts</p>
        </div>
        <button onclick="document.getElementById('newAdminModal').style.display='flex'"
                class="flex items-center gap-2 px-5 py-2.5 bg-[#685ef7] hover:bg-[#685ef7]/80 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-[#685ef7]/20 flex-shrink-0">
            <span class="material-symbols-outlined" style="font-size:18px">add</span>
            New Admin
        </button>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php
        $statsCards = [
            ['label' => 'Total Users',  'value' => $totalUsers,              'icon' => 'group',              'color' => '#a9a4ff', 'bg' => '#a9a4ff1a', 'href' => BASE_URL . '/hub/users'],
            ['label' => 'Admins',       'value' => $roleCounts['admin'],     'icon' => 'admin_panel_settings','color' => '#685ef7', 'bg' => '#685ef71a', 'href' => BASE_URL . '/hub/users?role=admin'],
            ['label' => 'Partners',     'value' => $roleCounts['partner'],   'icon' => 'handshake',          'color' => '#914feb', 'bg' => '#914feb1a', 'href' => BASE_URL . '/hub/users?role=partner'],
            ['label' => 'Clients',      'value' => $roleCounts['client'],    'icon' => 'person',             'color' => '#aba9bb', 'bg' => '#aba9bb1a', 'href' => BASE_URL . '/hub/users?role=client'],
        ];
        foreach ($statsCards as $card): ?>
        <a href="<?= $card['href'] ?>"
           class="bg-[#121220] border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-all group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?= $card['bg'] ?>">
                    <span class="material-symbols-outlined" style="color:<?= $card['color'] ?>;font-size:18px"><?= $card['icon'] ?></span>
                </div>
                <span class="material-symbols-outlined text-slate-700 group-hover:text-slate-500 transition-colors" style="font-size:16px">arrow_forward</span>
            </div>
            <p class="text-3xl font-black text-white font-headline"><?= $card['value'] ?></p>
            <p class="text-xs text-on-surface-variant font-bold uppercase tracking-widest mt-1"><?= $card['label'] ?></p>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search + role filter -->
    <div class="flex flex-col sm:flex-row gap-3">
        <form method="GET" action="" class="flex items-center gap-2 flex-1">
            <div class="relative flex-1 max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" style="font-size:18px">search</span>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search name or email…"
                       class="w-full bg-[#121220] border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-[#a9a4ff]/50">
                <?php if ($roleFilter): ?>
                <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>">
                <?php endif; ?>
            </div>
            <button type="submit"
                    class="px-4 py-2.5 bg-[#685ef7] hover:bg-[#685ef7]/80 text-white text-sm font-bold rounded-xl transition-colors flex-shrink-0">
                Search
            </button>
            <?php if ($search || $roleFilter): ?>
            <a href="<?= BASE_URL ?>/hub/users"
               class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white text-sm font-bold rounded-xl transition-colors flex-shrink-0">
                Clear
            </a>
            <?php endif; ?>
        </form>

        <!-- Role pill tabs -->
        <div class="flex items-center gap-1.5 bg-[#121220] border border-white/5 rounded-xl p-1 flex-shrink-0">
            <?php
            $roleTabs = [
                '' => 'All',
                'admin' => 'Admin',
                'partner' => 'Partner',
                'client' => 'Client',
            ];
            foreach ($roleTabs as $val => $label):
                $isActive = $roleFilter === $val;
                $qs = $val ? '?role=' . $val . ($search ? '&q=' . urlencode($search) : '') : ($search ? '?q=' . urlencode($search) : '');
            ?>
            <a href="<?= BASE_URL ?>/hub/users<?= $qs ?>"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $isActive ? 'bg-[#685ef7] text-white shadow-lg' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
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

                    $avatarGrad = match($u['role']) {
                        'admin'   => 'from-[#685ef7] to-[#a9a4ff]',
                        'partner' => 'from-[#914feb] to-[#c084fc]',
                        default   => 'from-slate-600 to-slate-500',
                    };
                    $roleBadge = match($u['role']) {
                        'admin'   => 'bg-[#a9a4ff]/15 text-[#a9a4ff] border border-[#a9a4ff]/20',
                        'partner' => 'bg-[#914feb]/15 text-[#c084fc] border border-[#914feb]/20',
                        default   => 'bg-white/5 text-slate-400 border border-white/10',
                    };
                    $roleIcon = match($u['role']) {
                        'admin'   => 'admin_panel_settings',
                        'partner' => 'handshake',
                        default   => 'person',
                    };
                ?>
                <tr class="hover:bg-white/[0.02] transition-colors <?= $isSuspended ? 'opacity-40' : '' ?>">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br <?= $avatarGrad ?> flex items-center justify-center text-white text-sm font-black flex-shrink-0">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <p class="font-bold text-white truncate"><?= htmlspecialchars($u['name']) ?></p>
                                    <?php if ($isSelf): ?>
                                    <span class="text-[10px] bg-[#a9a4ff]/10 text-[#a9a4ff] font-black px-1.5 py-0.5 rounded-full">YOU</span>
                                    <?php endif; ?>
                                    <?php if ($isSuspended): ?>
                                    <span class="text-[10px] bg-red-500/10 text-red-400 font-black px-1.5 py-0.5 rounded-full">SUSPENDED</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-on-surface-variant truncate"><?= htmlspecialchars($u['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold <?= $roleBadge ?>">
                            <span class="material-symbols-outlined" style="font-size:12px;font-variation-settings:'FILL' 1"><?= $roleIcon ?></span>
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 hidden md:table-cell">
                        <?php if ((int)$u['site_count'] > 0): ?>
                        <span class="inline-flex items-center gap-1 text-sm font-bold text-white">
                            <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:14px">language</span>
                            <?= (int)$u['site_count'] ?>
                        </span>
                        <?php else: ?>
                        <span class="text-on-surface-variant">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 hidden lg:table-cell text-on-surface-variant text-xs">
                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if (!$isSelf): ?>
                        <div class="relative flex justify-end">
                            <button onclick="hubToggleMenu('um<?= $u['id'] ?>')"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-400 hover:text-white bg-white/5 hover:bg-white/10 transition-all">
                                <span class="material-symbols-outlined" style="font-size:16px">more_horiz</span>
                            </button>

                            <div id="um<?= $u['id'] ?>" style="display:none"
                                 class="absolute right-0 top-9 z-20 w-52 bg-[#1e1e2f] border border-white/10 rounded-xl shadow-2xl overflow-hidden origin-top-right">

                                <!-- Change role -->
                                <div class="px-4 pt-3 pb-1">
                                    <p class="text-[10px] text-on-surface-variant uppercase tracking-widest font-bold">Change Role</p>
                                </div>
                                <?php foreach (['client', 'partner', 'admin'] as $role):
                                    if ($role === $u['role']) continue;
                                    $rIcon = match($role) { 'admin' => 'admin_panel_settings', 'partner' => 'handshake', default => 'person' };
                                    $rColor = match($role) { 'admin' => 'text-[#a9a4ff]', 'partner' => 'text-[#c084fc]', default => 'text-slate-400' };
                                ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id"   value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action"    value="set_role">
                                    <input type="hidden" name="role"      value="<?= $role ?>">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-white/5 transition-colors flex items-center gap-2.5">
                                        <span class="material-symbols-outlined <?= $rColor ?>" style="font-size:16px"><?= $rIcon ?></span>
                                        Set as <?= ucfirst($role) ?>
                                    </button>
                                </form>
                                <?php endforeach; ?>

                                <div class="border-t border-white/5 my-1"></div>

                                <!-- Suspend / Activate -->
                                <?php if ($isSuspended): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id"   value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action"    value="activate">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2.5 text-sm text-green-400 hover:text-green-300 hover:bg-green-500/10 transition-colors flex items-center gap-2.5">
                                        <span class="material-symbols-outlined" style="font-size:16px">check_circle</span>
                                        Activate Account
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id"   value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action"    value="suspend">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2.5 text-sm text-orange-400 hover:text-orange-300 hover:bg-orange-500/10 transition-colors flex items-center gap-2.5">
                                        <span class="material-symbols-outlined" style="font-size:16px">block</span>
                                        Suspend Account
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php if ((int)$u['site_count'] === 0): ?>
                                <div class="border-t border-white/5 my-1"></div>
                                <form method="POST" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?> permanently? This cannot be undone.')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id"   value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action"    value="delete">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors flex items-center gap-2.5">
                                        <span class="material-symbols-outlined" style="font-size:16px">delete</span>
                                        Delete User
                                    </button>
                                </form>
                                <?php endif; ?>
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
                    <td colspan="5" class="px-6 py-20 text-center">
                        <span class="material-symbols-outlined text-5xl block mb-3 text-slate-700">person_search</span>
                        <p class="text-white font-bold mb-1">No users found</p>
                        <p class="text-sm text-on-surface-variant">Try adjusting your search or filter.</p>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.max-w-6xl -->

<!-- Create Admin Modal -->
<div id="newAdminModal" style="display:none"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
     onclick="if(event.target===this) this.style.display='none'">

    <div class="w-full max-w-md bg-[#121220] border border-white/10 rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-5 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#685ef7]/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:18px">admin_panel_settings</span>
                </div>
                <div>
                    <h3 class="font-bold text-white font-headline">New Admin Account</h3>
                    <p class="text-xs text-on-surface-variant">Full SuperAdmin access</p>
                </div>
            </div>
            <button onclick="document.getElementById('newAdminModal').style.display='none'" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined" style="font-size:18px">close</span>
            </button>
        </div>

        <form method="POST" class="px-6 py-5 flex flex-col gap-4">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="create_admin">

            <div>
                <label class="block text-xs text-on-surface-variant font-bold uppercase tracking-widest mb-2">Full Name</label>
                <input type="text" name="new_name" required placeholder="e.g. John Smith"
                       class="w-full bg-[#0d0d1a] border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-[#a9a4ff]/50 transition-colors">
            </div>
            <div>
                <label class="block text-xs text-on-surface-variant font-bold uppercase tracking-widest mb-2">Email Address</label>
                <input type="email" name="new_email" required placeholder="admin@email.com"
                       class="w-full bg-[#0d0d1a] border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-[#a9a4ff]/50 transition-colors">
            </div>
            <div>
                <label class="block text-xs text-on-surface-variant font-bold uppercase tracking-widest mb-2">Password</label>
                <input type="password" name="new_password" required placeholder="Min. 6 characters"
                       class="w-full bg-[#0d0d1a] border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-[#a9a4ff]/50 transition-colors">
            </div>

            <div class="flex gap-3 pt-1">
                <button type="button" onclick="document.getElementById('newAdminModal').style.display='none'"
                        class="flex-1 py-2.5 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white text-sm font-bold rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 bg-[#685ef7] hover:bg-[#685ef7]/80 text-white text-sm font-bold rounded-xl transition-colors flex items-center justify-center gap-2 shadow-lg shadow-[#685ef7]/20">
                    <span class="material-symbols-outlined" style="font-size:16px">add</span>
                    Create Admin
                </button>
            </div>
        </form>
    </div>
</div>


<script>
function hubToggleMenu(id) {
    const el = document.getElementById(id);
    const isOpen = el.style.display !== 'none';
    document.querySelectorAll('[id^="um"]').forEach(m => m.style.display = 'none');
    if (!isOpen) el.style.display = 'block';
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="um"]') && !e.target.closest('[onclick*="hubToggleMenu"]')) {
        document.querySelectorAll('[id^="um"]').forEach(m => m.style.display = 'none');
    }
});
</script>

<?php render_hub_footer(); ?>
