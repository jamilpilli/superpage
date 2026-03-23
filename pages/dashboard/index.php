<?php
// Dashboard Principal - Tela Inicial
require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();

// Handle POST para Design (O modal global pode enviar para cá de qualquer aba)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_design') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid token or session expired.";
    } else {
        $siteId = $_POST['site_id'] ?? null;
        $primaryColor = $_POST['primary_color'] ?? '#4f46e5';
        $titleFont = $_POST['title_font'] ?? 'Inter';
        $textFont = $_POST['text_font'] ?? 'Inter';
        $buttonStyle = $_POST['button_style'] ?? 'rounded';
        $redirectTo = $_POST['redirect_to'] ?? '/dashboard';

        $checkSite = db_fetch_one("SELECT id FROM sites WHERE id = :id AND user_id = :uid", [
            ':id' => $siteId,
            ':uid' => $user['id']
        ]);

        if ($checkSite) {
            $designJson = json_encode([
                'primary_color' => $primaryColor,
                'title_font'    => $titleFont,
                'text_font'     => $textFont,
                'button_style'  => $buttonStyle
            ]);
            db_update('sites', ['design' => $designJson], 'id = :id', [':id' => $siteId]);
            $_SESSION['flash_success'] = "Design updated successfully!";

            $cleanRedirect = str_replace(BASE_URL, '', $redirectTo);
            redirect($cleanRedirect ?: '/dashboard');
        }
    }
}

render_dashboard_header("Dashboard");
?>

<?php if ($currentSite): ?>
    <?php
    // ─── Dados do site específico ────────────────────────────────────────────
    $visitsToday  = 0;
    $visitsMonth  = 0;
    $totalContacts = 0;
    $newContacts   = 0;
    $devices       = ['Mobile' => 0, 'Desktop' => 0];
    $referrers     = [];
    $dailyVisits   = array_fill(1, 31, 0);

    try {
        $visitsToday  = db_fetch_one("SELECT COUNT(DISTINCT visitor_ip) as total FROM site_analytics WHERE site_id = :sid AND DATE(created_at) = CURDATE()", [':sid' => $currentSite['id']])['total'] ?? 0;
        $visitsMonth  = db_fetch_one("SELECT COUNT(id) as total FROM site_analytics WHERE site_id = :sid AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())", [':sid' => $currentSite['id']])['total'] ?? 0;
        $totalContacts = db_fetch_one("SELECT COUNT(id) as total FROM site_contacts WHERE site_id = :sid", [':sid' => $currentSite['id']])['total'] ?? 0;
        $newContacts   = db_fetch_one("SELECT COUNT(id) as total FROM site_contacts WHERE site_id = :sid AND status = 'new'", [':sid' => $currentSite['id']])['total'] ?? 0;

        // Dispositivos do mês
        $devQuery = db_fetch_all("SELECT device_type, COUNT(*) as qtd FROM site_analytics WHERE site_id = :sid AND MONTH(created_at) = MONTH(CURDATE()) GROUP BY device_type", [':sid' => $currentSite['id']]);
        foreach ($devQuery as $dq) {
            if (isset($devices[$dq['device_type']])) {
                $devices[$dq['device_type']] = $dq['qtd'];
            }
        }

        // Origem do tráfego (Top 4)
        $refQuery = db_fetch_all("
            SELECT
                CASE
                    WHEN referrer_url LIKE '%google%'       THEN 'Google Organic'
                    WHEN referrer_url LIKE '%instagram.com%' THEN 'Instagram'
                    WHEN referrer_url LIKE '%facebook.com%'  THEN 'Facebook'
                    WHEN referrer_url = '' OR referrer_url IS NULL THEN 'Direct Traffic'
                    ELSE 'Other'
                END as source,
                COUNT(*) as qtd
            FROM site_analytics
            WHERE site_id = :sid AND MONTH(created_at) = MONTH(CURDATE())
            GROUP BY source
            ORDER BY qtd DESC
            LIMIT 4
        ", [':sid' => $currentSite['id']]);

        foreach ($refQuery as $rq) {
            $referrers[$rq['source']] = $rq['qtd'];
        }

        // Gráfico diário
        $chartQuery = db_fetch_all("
            SELECT DAY(created_at) as dia, COUNT(*) as qtd
            FROM site_analytics
            WHERE site_id = :sid AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY DAY(created_at)
        ", [':sid' => $currentSite['id']]);

        foreach ($chartQuery as $cq) {
            $dailyVisits[$cq['dia']] = $cq['qtd'];
        }
    } catch (\PDOException $e) {
        // Tabelas podem não existir — mantém zeros
    }

    $maxDaily     = max($dailyVisits) ?: 1;
    $totalDevices = array_sum($devices) ?: 1;
    $pctMobile    = round(($devices['Mobile'] / $totalDevices) * 100);
    $pctDesktop   = round(($devices['Desktop'] / $totalDevices) * 100);

    // Últimos contatos
    $recentContacts = [];
    try {
        $recentContacts = db_fetch_all("SELECT name, status, created_at FROM site_contacts WHERE site_id = :sid ORDER BY created_at DESC LIMIT 5", [':sid' => $currentSite['id']]);
    } catch (\PDOException $e) {}

    $siteName = htmlspecialchars($currentSite['domain'] ?: $currentSite['slug']);
    ?>

    <!-- ─── PAINEL DO SITE ESPECÍFICO ───────────────────────────────────────── -->
    <div class="flex flex-col gap-8">

        <!-- Título -->
        <div>
            <h2 class="text-3xl lg:text-4xl font-extrabold font-headline tracking-tight text-white mb-1">
                Overview: <span class="text-[#a9a4ff]"><?= $siteName ?></span>
            </h2>
            <p class="text-sm text-slate-400">Here's what's happening with your site.</p>
        </div>

        <!-- 4 KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

            <!-- Visitors Today -->
            <div class="relative overflow-hidden bg-[#181828] rounded-xl p-6 border border-white/5 group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="material-symbols-outlined text-6xl">visibility</span>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Visitors Today</p>
                <p class="text-3xl font-bold text-white"><?= number_format($visitsToday, 0, '.', ',') ?></p>
            </div>

            <!-- Monthly Traffic -->
            <div class="relative overflow-hidden bg-[#181828] rounded-xl p-6 border border-white/5 group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="material-symbols-outlined text-6xl">analytics</span>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Monthly Traffic</p>
                <p class="text-3xl font-bold text-white"><?= number_format($visitsMonth, 0, '.', ',') ?></p>
            </div>

            <!-- Contacts Received -->
            <div class="relative overflow-hidden bg-[#181828] rounded-xl p-6 border border-white/5 group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="material-symbols-outlined text-6xl">mail</span>
                </div>
                <div class="flex items-center gap-2 mb-2">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Contacts Received</p>
                    <?php if ($newContacts > 0): ?>
                        <span class="bg-[#a9a4ff] text-[#20009e] text-[9px] px-1.5 py-0.5 rounded-full font-black">NEW</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl font-bold text-white"><?= number_format($totalContacts, 0, '.', ',') ?></p>
                    <?php if ($newContacts > 0): ?>
                        <span class="text-xs font-semibold text-[#a9a4ff]">+<?= $newContacts ?> unread</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Avg. Time on Page -->
            <div class="relative overflow-hidden bg-[#181828] rounded-xl p-6 border border-white/5 group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="material-symbols-outlined text-6xl">timer</span>
                </div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Avg. Time on Page</p>
                <p class="text-3xl font-bold text-white">N/A</p>
            </div>

        </div>

        <!-- Linha 2: Gráfico + Tips -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Gráfico de barras diário -->
            <div class="lg:col-span-2 bg-[#181828] rounded-xl p-6 lg:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-lg font-bold font-headline text-white">Monthly Traffic</h4>
                    <span class="text-xs text-slate-500 font-medium">Daily breakdown</span>
                </div>
                <div class="flex items-end justify-between gap-[2px] h-48 pb-2 relative">
                    <!-- Linhas guia -->
                    <div class="absolute inset-0 flex flex-col justify-between pb-2 opacity-20 pointer-events-none">
                        <div class="border-t border-white/10 w-full"></div>
                        <div class="border-t border-white/10 w-full"></div>
                        <div class="border-t border-white/10 w-full"></div>
                        <div class="border-t border-white/10 w-full"></div>
                    </div>
                    <?php for ($d = 1; $d <= date('t'); $d++):
                        $qty = $dailyVisits[$d];
                        $h   = $qty > 0 ? max(4, round(($qty / $maxDaily) * 100)) : 0;
                        $isToday = ($d == date('j'));
                    ?>
                        <div class="w-full <?= $isToday ? 'bg-[#a9a4ff]' : 'bg-[#1e1e2f] hover:bg-[#685ef7]' ?> transition-colors rounded-t cursor-pointer relative z-10 group/bar"
                             style="height: <?= max($h, 2) ?>%;">
                            <?php if ($qty > 0): ?>
                                <div class="opacity-0 group-hover/bar:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-[#242437] text-white text-xs py-1 px-2 rounded pointer-events-none whitespace-nowrap z-20">
                                    <?= $qty ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="flex justify-between text-xs text-slate-600 mt-2 font-medium">
                    <span>Day 1</span>
                    <span>Day 15</span>
                    <span>Day <?= date('t') ?></span>
                </div>
            </div>

            <!-- Tips & Notices -->
            <div class="bg-[#1e1e2f] rounded-xl p-6 lg:p-8 border border-[#a9a4ff]/10 flex flex-col justify-between">
                <div>
                    <div class="w-11 h-11 bg-[#a9a4ff]/20 rounded-xl flex items-center justify-center mb-5">
                        <span class="material-symbols-outlined text-[#a9a4ff]">lightbulb</span>
                    </div>
                    <h4 class="text-lg font-bold font-headline text-white mb-4">Tips & Notices</h4>
                    <div class="space-y-3">
                        <div class="flex gap-3 p-3 rounded-xl hover:bg-white/5 transition-colors">
                            <div class="w-2 h-2 rounded-full bg-[#a9a4ff] mt-1.5 shrink-0"></div>
                            <p class="text-sm text-slate-400 leading-relaxed">Your analytics are being tracked in real time. Visit your site to generate data.</p>
                        </div>
                        <div class="flex gap-3 p-3 rounded-xl hover:bg-white/5 transition-colors">
                            <div class="w-2 h-2 rounded-full bg-[#b785ff] mt-1.5 shrink-0"></div>
                            <p class="text-sm text-slate-400 leading-relaxed">Use <strong class="text-white">Edit Structure</strong> to add or remove sections from your page.</p>
                        </div>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/dashboard/content?site_id=<?= $currentSite['id'] ?>"
                   class="mt-6 w-full py-2.5 bg-white/5 hover:bg-white/10 text-[#a9a4ff] font-bold text-sm rounded-full transition-all border border-[#a9a4ff]/20 text-center block">
                    Edit Your Content
                </a>
            </div>

        </div>

        <!-- Linha 3: Recent Contacts + Traffic Source + Devices -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Recent Contacts -->
            <div class="bg-[#181828] rounded-xl p-6 flex flex-col">
                <div class="flex justify-between items-center mb-5">
                    <h4 class="text-lg font-bold font-headline text-white">Recent Contacts</h4>
                    <a href="<?= BASE_URL ?>/dashboard/contacts?site_id=<?= $currentSite['id'] ?>" class="text-xs text-[#a9a4ff] hover:underline">View All</a>
                </div>

                <?php if (empty($recentContacts)): ?>
                    <div class="flex-1 flex flex-col items-center justify-center py-8 text-center">
                        <span class="material-symbols-outlined text-4xl text-slate-700 mb-3">inbox</span>
                        <p class="text-sm text-slate-500">No contact form submissions yet.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2 flex-1">
                        <?php foreach ($recentContacts as $lead):
                            $dateStr = date('d M, H:i', strtotime($lead['created_at']));
                            $isNew   = $lead['status'] === 'new';
                        ?>
                            <div class="flex items-center gap-3 p-3 bg-[#121220] rounded-xl hover:bg-[#1e1e2f] transition-all">
                                <div class="w-9 h-9 rounded-full bg-[#5B4FE9]/20 flex items-center justify-center text-[#a9a4ff] font-bold text-sm flex-shrink-0">
                                    <?= strtoupper(substr($lead['name'], 0, 1)) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($lead['name']) ?></p>
                                        <?php if ($isNew): ?>
                                            <span class="bg-[#a9a4ff] text-[#20009e] text-[8px] px-1.5 py-0.5 rounded-full font-black flex-shrink-0">NEW</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-500"><?= $dateStr ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Traffic Source -->
            <div class="bg-[#181828] rounded-xl p-6">
                <h4 class="text-lg font-bold font-headline text-white mb-6">Traffic Source</h4>
                <?php if (empty($referrers)): ?>
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <span class="material-symbols-outlined text-4xl text-slate-700 mb-3">bar_chart</span>
                        <p class="text-sm text-slate-500">Not enough data yet.</p>
                    </div>
                <?php else:
                    $srcColors = ['bg-[#a9a4ff]', 'bg-[#b785ff]', 'bg-[#ff98cd]', 'bg-[#474656]'];
                    $i = 0;
                    foreach ($referrers as $sourceName => $sourceQtd):
                        $pct = $visitsMonth > 0 ? round(($sourceQtd / $visitsMonth) * 100) : 0;
                ?>
                    <div class="mb-5">
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-slate-300 font-medium"><?= htmlspecialchars($sourceName) ?></span>
                            <span class="text-slate-500"><?= $pct ?>%</span>
                        </div>
                        <div class="w-full bg-[#1e1e2f] rounded-full h-2">
                            <div class="<?= $srcColors[$i % 4] ?> h-2 rounded-full transition-all" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php $i++; endforeach; endif; ?>
            </div>

            <!-- Devices Donut -->
            <div class="bg-[#181828] rounded-xl p-6 flex flex-col items-center">
                <h4 class="text-lg font-bold font-headline text-white mb-6 self-start">Devices</h4>

                <?php if ($visitsMonth > 0): ?>
                    <div class="relative w-36 h-36 flex items-center justify-center mb-6">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="32" fill="transparent" stroke="#1e1e2f" stroke-width="10"/>
                            <circle cx="40" cy="40" r="32" fill="transparent" stroke="#a9a4ff"
                                    stroke-width="10"
                                    stroke-dasharray="<?= round(201 * $pctMobile / 100) ?> 201"
                                    stroke-dashoffset="0"/>
                            <circle cx="40" cy="40" r="32" fill="transparent" stroke="#b785ff"
                                    stroke-width="10"
                                    stroke-dasharray="<?= round(201 * $pctDesktop / 100) ?> 201"
                                    stroke-dashoffset="-<?= round(201 * $pctMobile / 100) ?>"/>
                        </svg>
                        <div class="absolute flex flex-col items-center">
                            <span class="text-xl font-black text-white"><?= max($pctMobile, $pctDesktop) ?>%</span>
                            <span class="text-[9px] uppercase tracking-widest text-slate-500 font-bold"><?= $pctMobile >= $pctDesktop ? 'Mobile' : 'Desktop' ?></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 w-full">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-[#a9a4ff] flex-shrink-0"></div>
                            <span class="text-xs text-slate-300">Mobile (<?= $pctMobile ?>%)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-[#b785ff] flex-shrink-0"></div>
                            <span class="text-xs text-slate-300">Desktop (<?= $pctDesktop ?>%)</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flex-1 flex flex-col items-center justify-center py-8 text-center">
                        <div class="w-32 h-32 rounded-full bg-[#1e1e2f] flex items-center justify-center mb-4">
                            <span class="text-sm text-slate-500">No data</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

<?php else: ?>
    <?php
    // ─── Dados para o painel geral ────────────────────────────────────────────
    $allSites = db_fetch_all(
        "SELECT id, slug, domain, status FROM sites WHERE user_id = :uid AND status != 'inactive' ORDER BY created_at DESC",
        [':uid' => $user['id']]
    );

    $sitesWithStats = [];
    foreach ($allSites as $s) {
        try {
            $v = db_fetch_one("SELECT COUNT(DISTINCT visitor_ip) as t FROM site_analytics WHERE site_id = :id AND DATE(created_at) = CURDATE()", [':id' => $s['id']]);
            $c = db_fetch_one("SELECT COUNT(id) as t FROM site_contacts WHERE site_id = :id AND status = 'new'", [':id' => $s['id']]);
        } catch (\PDOException $e) {
            $v = ['t' => 0];
            $c = ['t' => 0];
        }
        $s['visits_today'] = (int)($v['t'] ?? 0);
        $s['new_contacts'] = (int)($c['t'] ?? 0);
        $sitesWithStats[] = $s;
    }
    ?>

    <!-- ─── PAINEL GERAL (sem site seleccionado) ─────────────────────────────── -->
    <div class="flex flex-col gap-8">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-3xl lg:text-4xl font-extrabold font-headline tracking-tight text-white mb-1">
                    My Sites <span class="text-[#a9a4ff]">(<?= count($sitesWithStats) ?>)</span>
                </h2>
                <p class="text-sm text-slate-400">Select a site to manage it, or create a new one.</p>
            </div>

        </div>

        <?php if (empty($sitesWithStats)): ?>
            <!-- Estado vazio -->
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-20 h-20 bg-[#1e1e2f] rounded-2xl flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-4xl text-slate-600">web</span>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3">No sites yet</h3>
                <p class="text-slate-400 max-w-sm mx-auto mb-8">
                    You haven't created any sites yet. Get started and build your digital presence in minutes.
                </p>
                <a href="<?= BASE_URL ?>/dashboard/create_site"
                   class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold rounded-full shadow-[0_8px_24px_rgba(104,94,247,0.35)] hover:opacity-90 transition-all">
                    <span class="material-symbols-outlined">add</span>
                    Create Your First Site
                </a>
            </div>

        <?php else: ?>
            <!-- Grid de cards dos sites -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($sitesWithStats as $s):
                    $displayName = htmlspecialchars($s['domain'] ?: $s['slug']);
                    $previewUrl  = $s['domain'] ? 'https://' . $s['domain'] : BASE_URL . '/' . $s['slug'];
                ?>
                    <div class="bg-[#181828] rounded-xl p-6 border border-white/5 hover:border-[#a9a4ff]/20 transition-all group flex flex-col gap-5">

                        <!-- Card header -->
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-white truncate text-base"><?= $displayName ?></h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    <?= $s['domain'] ? htmlspecialchars($s['domain']) : $s['slug'] . '.superpage.com' ?>
                                </p>
                            </div>
                            <span class="flex-shrink-0 ml-3 px-2.5 py-1 text-[10px] font-bold rounded-full uppercase
                                <?= $s['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-400' ?>">
                                <?= $s['status'] === 'active' ? 'Active' : ucfirst($s['status']) ?>
                            </span>
                        </div>

                        <!-- Métricas rápidas -->
                        <div class="flex gap-6">
                            <div>
                                <p class="text-2xl font-bold text-white"><?= number_format($s['visits_today'], 0, '.', ',') ?></p>
                                <p class="text-xs text-slate-500 mt-0.5">visitors today</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold <?= $s['new_contacts'] > 0 ? 'text-[#a9a4ff]' : 'text-white' ?>">
                                    <?= $s['new_contacts'] ?>
                                </p>
                                <p class="text-xs text-slate-500 mt-0.5">new contacts</p>
                            </div>
                        </div>

                        <!-- Acções -->
                        <div class="flex gap-2 mt-auto">
                            <a href="<?= BASE_URL ?>/dashboard?site_id=<?= $s['id'] ?>"
                               class="flex-1 text-center py-2 bg-[#5B4FE9]/20 hover:bg-[#5B4FE9]/30 text-[#a9a4ff] font-bold text-sm rounded-full transition-all border border-[#5B4FE9]/20">
                                Manage
                            </a>
                            <a href="<?= $previewUrl ?>?preview=true" target="_blank"
                               class="px-4 py-2 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white font-bold text-sm rounded-full transition-all"
                               title="Preview site">
                                <span class="material-symbols-outlined text-base" style="font-size:18px">open_in_new</span>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
<?php endif; ?>

<?php render_dashboard_footer(); ?>
