<?php
// Partner Panel — Sites

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/partner_template.php';

$user      = get_logged_user();
$clientFilter = (int)($_GET['client_id'] ?? 0);

$params = [':pid' => $user['id']];
$where  = '';
if ($clientFilter) {
    $where = 'AND pc.client_id = :cid';
    $params[':cid'] = $clientFilter;
}

$sites = db_fetch_all("
    SELECT s.id, s.slug, s.domain, s.status, s.updated_at,
           u.id as client_id, u.name as client_name
    FROM sites s
    JOIN partner_clients pc ON pc.client_id = s.user_id
    JOIN users u ON u.id = s.user_id
    WHERE pc.partner_id = :pid AND s.status != 'inactive'
    $where
    ORDER BY s.updated_at DESC
", $params);

$filterClient = $clientFilter
    ? db_fetch_one("SELECT name FROM users WHERE id = :id", [':id' => $clientFilter])
    : null;

render_partner_header("Sites");
?>

<div class="max-w-5xl flex flex-col gap-6">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white font-headline">
                <?= $filterClient ? 'Sites — ' . htmlspecialchars($filterClient['name']) : 'All Client Sites' ?>
            </h2>
            <p class="text-sm text-on-surface-variant mt-0.5"><?= count($sites) ?> site<?= count($sites) != 1 ? 's' : '' ?></p>
        </div>
        <?php if ($clientFilter): ?>
        <a href="<?= BASE_URL ?>/partner/sites"
           class="px-4 py-2 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white text-sm font-bold rounded-xl transition-colors">
            Show all
        </a>
        <?php endif; ?>
    </div>

    <!-- Sites grid -->
    <?php if (empty($sites)): ?>
    <div class="bg-[#121220] border border-white/5 rounded-2xl py-16 text-center text-on-surface-variant">
        <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">language</span>
        No sites found. <a href="<?= BASE_URL ?>/partner/clients" class="text-[#a9a4ff] hover:underline">Add clients first.</a>
    </div>
    <?php else: ?>
    <div class="bg-[#121220] border border-white/5 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5 text-on-surface-variant text-xs uppercase tracking-widest">
                        <th class="text-left px-6 py-4 font-bold">Site</th>
                        <th class="text-left px-4 py-4 font-bold hidden md:table-cell">Client</th>
                        <th class="text-left px-4 py-4 font-bold">Status</th>
                        <th class="text-left px-4 py-4 font-bold hidden lg:table-cell">Last updated</th>
                        <th class="text-right px-6 py-4 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                <?php foreach ($sites as $s): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-bold text-white"><?= htmlspecialchars($s['domain'] ?: $s['slug']) ?></p>
                        <?php if ($s['domain']): ?>
                        <p class="text-xs text-on-surface-variant">superpage.co.uk/<?= htmlspecialchars($s['slug']) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 hidden md:table-cell text-on-surface-variant">
                        <a href="<?= BASE_URL ?>/partner/sites?client_id=<?= $s['client_id'] ?>"
                           class="hover:text-[#a9a4ff] transition-colors">
                            <?= htmlspecialchars($s['client_name']) ?>
                        </a>
                    </td>
                    <td class="px-4 py-4">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $s['status'] === 'active' ? 'bg-green-500/10 text-green-400' : 'bg-yellow-500/10 text-yellow-400' ?>">
                            <?= ucfirst($s['status']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 hidden lg:table-cell text-on-surface-variant text-xs">
                        <?= date('d M Y', strtotime($s['updated_at'])) ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-3">
                            <a href="<?= BASE_URL ?>/dashboard/content?site_id=<?= $s['id'] ?>"
                               class="px-3 py-1.5 text-xs font-bold bg-[#685ef7]/20 text-[#a9a4ff] hover:bg-[#685ef7]/40 rounded-lg transition-colors">
                                Edit Content
                            </a>
                            <a href="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $s['id'] ?>"
                               class="px-3 py-1.5 text-xs font-bold bg-white/5 text-slate-400 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                                Settings
                            </a>
                            <a href="/<?= htmlspecialchars($s['slug']) ?>?preview=true" target="_blank"
                               class="text-xs text-on-surface-variant hover:text-white transition-colors">
                                <span class="material-symbols-outlined" style="font-size:16px">open_in_new</span>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php render_partner_footer(); ?>
