<?php
// HUB - Visão Geral do Sistema (Estatísticas e Logs)

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

// A template já protege verificando is admin, não precisamos de redundância aqui.

// Totais de Dashboard
$stats = [
    'users' => db_fetch_one("SELECT COUNT(id) as total FROM users WHERE role = 'client'")['total'],
    'partners' => db_fetch_one("SELECT COUNT(id) as total FROM users WHERE role = 'partner'")['total'],
    'sites_active' => db_fetch_one("SELECT COUNT(id) as total FROM sites WHERE status = 'active'")['total'],
    'mrr' => db_fetch_one("SELECT SUM(t.price) as total FROM subscriptions s JOIN sites st ON s.site_id = st.id JOIN themes t ON st.theme_id = t.id WHERE s.status = 'active'")['total'] ?? '0.00'
];

$recentLogs = db_fetch_all("
    SELECT h.*, u.name as admin_name 
    FROM hub_audit_logs h 
    JOIN users u ON h.admin_id = u.id 
    ORDER BY h.created_at DESC LIMIT 10
");

render_hub_header("System Overview");
?>

<div class="flex flex-col gap-8 max-w-6xl">

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <div class="relative overflow-hidden bg-[#181828] rounded-xl p-6 border border-white/5 group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <span class="material-symbols-outlined text-6xl">group</span>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Active Clients</p>
            <p class="text-3xl font-bold text-white"><?= number_format($stats['users']) ?></p>
        </div>

        <div class="relative overflow-hidden bg-[#181828] rounded-xl p-6 border border-white/5 group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <span class="material-symbols-outlined text-6xl">language</span>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Published Sites</p>
            <p class="text-3xl font-bold text-white"><?= number_format($stats['sites_active']) ?></p>
        </div>

        <div class="relative overflow-hidden bg-[#181828] rounded-xl p-6 border border-white/5 group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <span class="material-symbols-outlined text-6xl">handshake</span>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">VIP Partners</p>
            <p class="text-3xl font-bold text-white"><?= number_format($stats['partners']) ?></p>
        </div>

        <div class="relative overflow-hidden bg-[#181828] rounded-xl p-6 border border-white/5 group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <span class="material-symbols-outlined text-6xl">payments</span>
            </div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Projected MRR</p>
            <p class="text-3xl font-bold text-white">£<?= number_format((float)$stats['mrr'], 2) ?></p>
        </div>

    </div>

    <!-- Audit Logs -->
    <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:18px">history</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-white font-headline">HUB Audit Log</h3>
                <p class="text-xs text-slate-500">Last 10 actions taken by administrators.</p>
            </div>
        </div>

        <?php if (empty($recentLogs)): ?>
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <span class="material-symbols-outlined text-4xl text-slate-700 mb-3">history</span>
                <p class="text-sm text-slate-500">No events recorded yet.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-white/5">
                <?php foreach ($recentLogs as $log): ?>
                <li class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-white/2 transition-colors">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-[#a9a4ff] truncate"><?= htmlspecialchars($log['action_type']) ?></p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            By <span class="text-slate-300"><?= htmlspecialchars($log['admin_name']) ?></span>
                            — entity #<?= $log['entity_id'] ?> (<?= htmlspecialchars($log['entity_type']) ?>)
                        </p>
                    </div>
                    <span class="text-xs text-slate-600 flex-shrink-0"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>

<?php render_hub_footer(); ?>
