<?php
// Partner Panel — Overview

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/partner_template.php';

$user = get_logged_user();

$totalClients = db_fetch_one("SELECT COUNT(id) as t FROM partner_clients WHERE partner_id = :pid", [':pid' => $user['id']])['t'] ?? 0;

$totalSites = db_fetch_one("
    SELECT COUNT(s.id) as t FROM sites s
    JOIN partner_clients pc ON pc.client_id = s.user_id
    WHERE pc.partner_id = :pid AND s.status = 'active'
", [':pid' => $user['id']])['t'] ?? 0;

$limits = db_fetch_one("SELECT partner_max_clients, partner_max_sites FROM users WHERE id = :id", [':id' => $user['id']]);
$maxClients = $limits['partner_max_clients'] ?? null;
$maxSites   = $limits['partner_max_sites'] ?? null;

$recentSites = db_fetch_all("
    SELECT s.slug, s.domain, s.status, s.updated_at, u.name as client_name
    FROM sites s
    JOIN partner_clients pc ON pc.client_id = s.user_id
    JOIN users u ON u.id = s.user_id
    WHERE pc.partner_id = :pid
    ORDER BY s.updated_at DESC LIMIT 5
", [':pid' => $user['id']]);

render_partner_header("Overview");
?>

<div class="max-w-5xl flex flex-col gap-8">

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php
        $stats = [
            ['Clients',      $totalClients, $maxClients !== null ? "/ $maxClients limit" : 'No limit', 'group',    '#a9a4ff'],
            ['Active Sites', $totalSites,   $maxSites   !== null ? "/ $maxSites limit"   : 'No limit', 'language', '#914feb'],
        ];
        foreach ($stats as [$label, $value, $sub, $icon, $color]):
        ?>
        <div class="bg-[#121220] border border-white/5 rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:<?= $color ?>1a">
                    <span class="material-symbols-outlined" style="color:<?= $color ?>;font-size:18px"><?= $icon ?></span>
                </div>
                <span class="text-xs text-on-surface-variant font-bold uppercase tracking-widest"><?= $label ?></span>
            </div>
            <p class="text-3xl font-black text-white font-headline"><?= $value ?></p>
            <p class="text-xs text-on-surface-variant mt-1"><?= $sub ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent sites -->
    <div class="bg-[#121220] border border-white/5 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
            <h3 class="font-bold text-white font-headline">Recent Sites</h3>
            <a href="<?= BASE_URL ?>/partner/sites" class="text-xs text-[#a9a4ff] hover:text-white font-bold transition-colors">View all →</a>
        </div>

        <?php if (empty($recentSites)): ?>
        <div class="py-16 text-center text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl block mb-2 opacity-30">language</span>
            No sites yet. <a href="<?= BASE_URL ?>/partner/clients" class="text-[#a9a4ff] hover:underline">Add a client first.</a>
        </div>
        <?php else: ?>
        <div class="divide-y divide-white/5">
            <?php foreach ($recentSites as $s): ?>
            <div class="flex items-center justify-between px-6 py-4 hover:bg-white/[0.02] transition-colors">
                <div>
                    <p class="font-bold text-white text-sm"><?= htmlspecialchars($s['domain'] ?: $s['slug']) ?></p>
                    <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($s['client_name']) ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $s['status'] === 'active' ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400' ?>">
                        <?= ucfirst($s['status']) ?>
                    </span>
                    <a href="<?= BASE_URL ?>/dashboard/content?site_id=<?= $s['slug'] ?>"
                       class="text-xs font-bold text-[#a9a4ff] hover:text-white transition-colors">Edit →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php render_partner_footer(); ?>
