<?php
// Partner Panel — Clients

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/partner_template.php';

$user  = get_logged_user();
$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid session. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $email = trim($_POST['email'] ?? '');

            // Verificar limites
            $limits       = db_fetch_one("SELECT partner_max_clients FROM users WHERE id = :id", [':id' => $user['id']]);
            $maxClients   = $limits['partner_max_clients'] ?? null;
            $totalClients = db_fetch_one("SELECT COUNT(id) as t FROM partner_clients WHERE partner_id = :pid", [':pid' => $user['id']])['t'];

            if ($maxClients !== null && $totalClients >= $maxClients) {
                $error = "You have reached your client limit ($maxClients). Contact your admin to increase it.";
            } elseif (empty($email)) {
                $error = "Please enter a valid email address.";
            } else {
                $client = db_fetch_one("SELECT id, name, role FROM users WHERE email = :email", [':email' => $email]);
                if (!$client) {
                    $error = "No account found with that email address.";
                } elseif ($client['role'] !== 'client') {
                    $error = "That account is not a client.";
                } elseif ((int)$client['id'] === (int)$user['id']) {
                    $error = "You cannot add yourself as a client.";
                } else {
                    $existing = db_fetch_one("SELECT id FROM partner_clients WHERE partner_id = :pid AND client_id = :cid", [
                        ':pid' => $user['id'], ':cid' => $client['id']
                    ]);
                    if ($existing) {
                        $error = "This client is already associated with your account.";
                    } else {
                        db_insert('partner_clients', ['partner_id' => $user['id'], 'client_id' => $client['id']]);
                        $msg = "Client "{$client['name']}" added successfully.";
                    }
                }
            }
        } elseif ($action === 'remove') {
            $clientId = (int)($_POST['client_id'] ?? 0);
            db_fetch_one("DELETE FROM partner_clients WHERE partner_id = :pid AND client_id = :cid", [
                ':pid' => $user['id'], ':cid' => $clientId
            ]);
            // Usar PDO directamente para DELETE
            global $pdo;
            $pdo->prepare("DELETE FROM partner_clients WHERE partner_id = :pid AND client_id = :cid")
                ->execute([':pid' => $user['id'], ':cid' => $clientId]);
            $msg = "Client removed.";
        }
    }
}

$clients = db_fetch_all("
    SELECT u.id, u.name, u.email, u.created_at,
           COUNT(s.id) as site_count
    FROM partner_clients pc
    JOIN users u ON u.id = pc.client_id
    LEFT JOIN sites s ON s.user_id = u.id AND s.status != 'inactive'
    WHERE pc.partner_id = :pid
    GROUP BY u.id
    ORDER BY pc.created_at DESC
", [':pid' => $user['id']]);

$limits     = db_fetch_one("SELECT partner_max_clients FROM users WHERE id = :id", [':id' => $user['id']]);
$maxClients = $limits['partner_max_clients'] ?? null;
$csrf       = generate_csrf_token();

render_partner_header("Clients");
?>

<div class="max-w-4xl flex flex-col gap-6">

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

    <!-- Add client -->
    <div class="bg-[#121220] border border-white/5 rounded-2xl p-6">
        <h3 class="font-bold text-white font-headline mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:20px">person_add</span>
            Add Client
        </h3>
        <form method="POST" class="flex gap-3">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="add">
            <input type="email" name="email" required placeholder="client@email.com"
                   class="flex-1 bg-[#0d0d1a] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-[#a9a4ff]/50">
            <button type="submit"
                    class="px-5 py-2.5 bg-[#685ef7] hover:bg-[#685ef7]/80 text-white text-sm font-bold rounded-xl transition-colors">
                Add
            </button>
        </form>
        <p class="text-xs text-on-surface-variant mt-2">The client must already have a SuperPage account.</p>
        <?php if ($maxClients !== null): ?>
        <p class="text-xs text-on-surface-variant mt-1">
            <?= count($clients) ?> / <?= $maxClients ?> clients used.
        </p>
        <?php endif; ?>
    </div>

    <!-- Clients table -->
    <div class="bg-[#121220] border border-white/5 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5">
            <h3 class="font-bold text-white font-headline">Your Clients <span class="text-on-surface-variant font-normal text-sm">(<?= count($clients) ?>)</span></h3>
        </div>

        <?php if (empty($clients)): ?>
        <div class="py-16 text-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">group</span>
            No clients yet. Add one above.
        </div>
        <?php else: ?>
        <div class="divide-y divide-white/5">
            <?php foreach ($clients as $c): ?>
            <div class="flex items-center justify-between px-6 py-4 hover:bg-white/[0.02] transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#685ef7] to-[#914feb] flex items-center justify-center text-white text-xs font-black flex-shrink-0">
                        <?= strtoupper(substr($c['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-bold text-white text-sm"><?= htmlspecialchars($c['name']) ?></p>
                        <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($c['email']) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-xs text-on-surface-variant"><?= $c['site_count'] ?> site<?= $c['site_count'] != 1 ? 's' : '' ?></span>
                    <a href="<?= BASE_URL ?>/partner/sites?client_id=<?= $c['id'] ?>"
                       class="text-xs font-bold text-[#a9a4ff] hover:text-white transition-colors">View sites</a>
                    <form method="POST" onsubmit="return confirm('Remove this client?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="text-xs font-bold text-red-400 hover:text-red-300 transition-colors">Remove</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php render_partner_footer(); ?>
