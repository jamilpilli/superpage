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

render_hub_header("Visão Geral do Sistema");
?>

<!-- Cards KPI -->
<div class="mt-4 pb-8">
    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative bg-white pt-5 px-4 pb-6 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden border-t-4 border-indigo-500">
            <dt>
                <div class="absolute bg-indigo-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <p class="ml-16 text-sm font-medium text-gray-500 truncate">Clientes Ativos</p>
            </dt>
            <dd class="ml-16 pb-2 flex items-baseline sm:pb-3">
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['users']) ?></p>
            </dd>
        </div>

        <div class="relative bg-white pt-5 px-4 pb-6 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden border-t-4 border-green-500">
            <dt>
                <div class="absolute bg-green-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                </div>
                <p class="ml-16 text-sm font-medium text-gray-500 truncate">Sites Publicados</p>
            </dt>
            <dd class="ml-16 pb-2 flex items-baseline sm:pb-3">
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['sites_active']) ?></p>
            </dd>
        </div>

        <div class="relative bg-white pt-5 px-4 pb-6 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden border-t-4 border-yellow-500">
            <dt>
                <div class="absolute bg-yellow-500 rounded-md p-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                </div>
                <p class="ml-16 text-sm font-medium text-gray-500 truncate">Parceiros Vips</p>
            </dt>
            <dd class="ml-16 pb-2 flex items-baseline sm:pb-3">
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['partners']) ?></p>
            </dd>
        </div>

        <div class="relative bg-white pt-5 px-4 pb-6 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden border-t-4 border-purple-500">
            <dt>
                <div class="absolute bg-purple-500 rounded-md p-3">
                    <span class="text-white font-black text-xl leading-none inline-block pb-1 pl-1">$</span>
                </div>
                <p class="ml-16 text-sm font-medium text-gray-500 truncate">MRR Previsto (Mes)</p>
            </dt>
            <dd class="ml-16 pb-2 flex items-baseline sm:pb-3">
                <p class="text-2xl font-bold text-gray-900">R$ <?= number_format((float)$stats['mrr'], 2, ',', '.') ?></p>
            </dd>
        </div>
    </dl>
</div>

<!-- Logs Recentes -->
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Logs de Auditoria do HUB</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Últimas 10 ações tomadas por administradores.</p>
    </div>
    <ul class="divide-y divide-gray-200">
        <?php if (empty($recentLogs)): ?>
            <li class="px-4 py-8 text-center text-sm text-gray-500">Nenhum evento registrado.</li>
        <?php endif; ?>
        
        <?php foreach ($recentLogs as $log): ?>
        <li class="px-4 py-4 sm:px-6 flex justify-between items-center hover:bg-gray-50">
            <div>
                <p class="text-sm font-medium text-indigo-600 truncate"><?= htmlspecialchars($log['action_type']) ?></p>
                <p class="mt-1 flex items-center text-sm text-gray-500 border-l-[3px] border-gray-300 pl-2">
                    <span class="truncate">Efetuado por <?= htmlspecialchars($log['admin_name']) ?> no objeto #<?= $log['entity_id'] ?> (<?= htmlspecialchars($log['entity_type']) ?>)</span>
                </p>
            </div>
            <div class="ml-2 flex-shrink-0 flex text-xs text-gray-400">
                <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php render_hub_footer(); ?>
