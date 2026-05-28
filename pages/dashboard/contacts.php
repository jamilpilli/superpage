<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = get_logged_user();
$site_id = filter_input(INPUT_GET, 'site_id', FILTER_VALIDATE_INT);

if (!$site_id) {
    redirect(BASE_URL . '/dashboard');
}

if (!can_access_site($site_id)) {
    redirect(BASE_URL . '/dashboard');
}
$site = db_fetch_one("SELECT * FROM sites WHERE id = :sid", [':sid' => $site_id]);

// Marcar como lido (resolve)
$mark_read = filter_input(INPUT_GET, 'mark_read', FILTER_VALIDATE_INT);
if ($mark_read) {
    db_update('site_contacts', ['status' => 'read'], 'id = :id AND site_id = :sid', [':id' => $mark_read, ':sid' => $site_id]);
    redirect(BASE_URL . '/dashboard/contacts?site_id=' . $site_id);
}

// Deletar contato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    global $pdo;
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM site_contacts WHERE id = :id AND site_id = :sid");
    $stmt->execute([':id' => $delete_id, ':sid' => $site_id]);
    redirect(BASE_URL . '/dashboard/contacts?site_id=' . $site_id);
}

// Filtros
$search        = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all';

// Build WHERE
$where  = 'site_id = :sid';
$params = [':sid' => $site_id];

if ($search !== '') {
    $where .= ' AND (name LIKE :s OR email LIKE :s2 OR message LIKE :s3)';
    $params[':s']  = '%' . $search . '%';
    $params[':s2'] = '%' . $search . '%';
    $params[':s3'] = '%' . $search . '%';
}

if ($status_filter === 'new') {
    $where .= " AND status = 'new'";
} elseif ($status_filter === 'read') {
    $where .= " AND status = 'read'";
}

// Total
$total_result = db_fetch_one("SELECT COUNT(*) as total FROM site_contacts WHERE $where", $params);
$total        = (int)($total_result['total'] ?? 0);

// Export CSV
if (filter_input(INPUT_GET, 'export') === 'csv') {
    $all_contacts = db_fetch_all("SELECT * FROM site_contacts WHERE $where ORDER BY created_at DESC", $params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contacts_' . $site['slug'] . '_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name', 'Email', 'Phone', 'Message', 'Status', 'Received At']);
    foreach ($all_contacts as $c) {
        fputcsv($out, [$c['name'], $c['email'], $c['phone'] ?? '', $c['message'], $c['status'], $c['created_at']]);
    }
    fclose($out);
    exit;
}

// Paginação
$per_page    = 10;
$page        = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1));
$offset      = ($page - 1) * $per_page;
$total_pages = max(1, (int)ceil($total / $per_page));

$contacts = db_fetch_all(
    "SELECT * FROM site_contacts WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset",
    $params
);

// Gera URL preservando filtros
function contacts_url($site_id, $extra = []) {
    $q = array_merge(['site_id' => $site_id], $extra);
    return BASE_URL . '/dashboard/contacts?' . http_build_query($q);
}

// Iniciais do nome
function get_initials($name) {
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    return strtoupper(substr($name, 0, 2));
}

// Cores de avatar (alterna por índice)
$avatar_gradients = [
    'bg-gradient-to-br from-[#685ef7] to-[#914feb]',
    'bg-gradient-to-br from-[#e878b5] to-[#914feb]',
    'bg-gradient-to-br from-[#685ef7] to-[#e878b5]',
];

// Carrega template por último
require_once __DIR__ . '/../../includes/dashboard_template.php';
render_dashboard_header('Contacts – ' . htmlspecialchars($site['domain'] ?: $site['slug']));
?>

<div class="max-w-6xl mx-auto">

    <!-- Header -->
    <header class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-3">
                <span class="px-3 py-1 bg-[#a9a4ff]/10 sp-primary text-xs font-black rounded-full tracking-widest uppercase">Messages</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black font-headline tracking-tight sp-text mb-2">
                Messages &amp; <span class="sp-primary">Contacts</span>
            </h1>
            <p class="sp-text-muted text-lg font-body">
                You have <span class="sp-text font-bold"><?= $total ?></span>
                <?= $status_filter !== 'all' ? htmlspecialchars($status_filter) . ' ' : '' ?>record<?= $total !== 1 ? 's' : '' ?> in your inbox.
            </p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
            <a href="<?= contacts_url($site_id, array_filter(['search' => $search, 'status' => $status_filter !== 'all' ? $status_filter : null, 'export' => 'csv'])) ?>"
               class="flex items-center gap-2 px-5 py-3 sp-surface hover:sp-surface-hi rounded-full transition-all text-sm font-bold border sp-border-mid sp-text-muted hover:sp-text">
                <span class="material-symbols-outlined text-xl">download</span>
                <span>Export CSV</span>
            </a>
        </div>
    </header>

    <!-- Filters -->
    <section class="mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <form method="GET" action="<?= BASE_URL ?>/dashboard/contacts" class="contents">
            <input type="hidden" name="site_id" value="<?= $site_id ?>">

            <!-- Search -->
            <div class="md:col-span-2 relative">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 sp-text-faint pointer-events-none" style="font-size:20px">search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search by name, email or message..."
                       class="w-full pl-12 pr-4 py-4 rounded-2xl text-sm sp-input"/>
            </div>

            <!-- Status filter -->
            <div class="relative">
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-4 py-4 rounded-2xl appearance-none cursor-pointer text-sm sp-input">
                    <option value="all"  <?= $status_filter === 'all'  ? 'selected' : '' ?>>All Status</option>
                    <option value="new"  <?= $status_filter === 'new'  ? 'selected' : '' ?>>New Messages</option>
                    <option value="read" <?= $status_filter === 'read' ? 'selected' : '' ?>>Resolved</option>
                </select>
                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none sp-text-faint" style="font-size:20px">expand_more</span>
            </div>

            <!-- Submit search -->
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="flex-1 flex items-center justify-center gap-2 px-5 py-4 bg-gradient-to-br from-[#685ef7] to-[#914feb] text-white rounded-2xl font-bold text-sm shadow-lg shadow-[#685ef7]/20 hover:shadow-[#685ef7]/40 transition-all">
                    <span class="material-symbols-outlined text-xl">search</span>
                    Search
                </button>
                <?php if ($search !== '' || $status_filter !== 'all'): ?>
                    <a href="<?= contacts_url($site_id) ?>"
                       class="flex items-center justify-center w-14 h-14 sp-surface-low border sp-border rounded-2xl hover:bg-white/5 transition-all sp-text-muted hover:sp-text" title="Clear filters">
                        <span class="material-symbols-outlined">close</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- Contacts List -->
    <section class="space-y-4">

        <?php if (empty($contacts)): ?>
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-20 h-20 rounded-full sp-surface border sp-border flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-4xl sp-text-faint">mail</span>
                </div>
                <h3 class="text-xl font-bold sp-text mb-2">Inbox is empty</h3>
                <p class="sp-text-muted text-sm max-w-sm">
                    <?php if ($search !== '' || $status_filter !== 'all'): ?>
                        No contacts match your current filters.
                        <a href="<?= contacts_url($site_id) ?>" class="sp-primary font-bold hover:underline">Clear filters</a>
                    <?php else: ?>
                        No contact submissions received through your site's form yet.
                    <?php endif; ?>
                </p>
            </div>

        <?php else: ?>
            <?php foreach ($contacts as $i => $contact):
                $isNew     = $contact['status'] === 'new';
                $initials  = get_initials($contact['name']);
                $avatarBg  = $isNew ? $avatar_gradients[$i % count($avatar_gradients)] : 'bg-[#242437]';
                $avatarTxt = $isNew ? 'text-white' : 'sp-primary';
                $dateStr   = date('d M Y, H:i', strtotime($contact['created_at']));
            ?>
            <div class="group relative overflow-hidden rounded-2xl transition-all duration-200 hover:-translate-y-0.5">
                <!-- Hover glow -->
                <div class="absolute inset-0 bg-gradient-to-r from-[#a9a4ff]/8 to-transparent opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl pointer-events-none"></div>

                <div class="relative p-6 sp-surface backdrop-blur-xl border <?= $isNew ? 'border-[#5B4FE9]/30' : 'sp-border' ?> rounded-2xl flex flex-col lg:flex-row lg:items-center gap-6">

                    <!-- Avatar + Info -->
                    <div class="flex items-center gap-5 lg:w-1/4">
                        <div class="w-14 h-14 rounded-full <?= $avatarBg ?> <?= $avatarTxt ?> flex items-center justify-center text-xl font-black font-headline shadow-lg flex-shrink-0">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div class="min-w-0">
                            <h3 class="sp-text font-bold font-headline text-base flex items-center gap-2 flex-wrap">
                                <?= htmlspecialchars($contact['name']) ?>
                                <?php if ($isNew): ?>
                                    <span class="bg-red-900/40 text-red-400 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest flex-shrink-0">NEW</span>
                                <?php endif; ?>
                            </h3>
                            <p class="sp-text-muted text-xs mt-0.5"><?= $dateStr ?></p>
                        </div>
                    </div>

                    <!-- Contact details + Message preview -->
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <a href="mailto:<?= htmlspecialchars($contact['email']) ?>"
                               class="flex items-center gap-2 sp-primary hover:sp-text font-medium text-sm transition-colors">
                                <span class="material-symbols-outlined text-base flex-shrink-0" style="font-size:18px">mail</span>
                                <span class="truncate"><?= htmlspecialchars($contact['email']) ?></span>
                            </a>
                            <?php if (!empty($contact['phone'])): ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contact['phone']) ?>" target="_blank"
                                   class="flex items-center gap-2 text-slate-400 hover:text-green-400 text-sm transition-colors">
                                    <span class="material-symbols-outlined text-base flex-shrink-0" style="font-size:18px">call</span>
                                    <span><?= htmlspecialchars($contact['phone']) ?></span>
                                </a>
                            <?php else: ?>
                                <span class="flex items-center gap-2 sp-text-faint text-sm">
                                    <span class="material-symbols-outlined text-base flex-shrink-0" style="font-size:18px">call</span>
                                    <span>No phone provided</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] sp-text-faint uppercase font-black tracking-widest mb-1.5">Message Preview</span>
                            <p class="sp-text-muted text-sm line-clamp-2 italic leading-relaxed">
                                "<?= htmlspecialchars(mb_substr($contact['message'], 0, 120)) ?><?= mb_strlen($contact['message']) > 120 ? '…' : '' ?>"
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 justify-end flex-shrink-0">
                        <!-- Reply -->
                        <a href="mailto:<?= htmlspecialchars($contact['email']) ?>?subject=Re: Your message&body=Hi <?= urlencode($contact['name']) ?>,"
                           class="w-11 h-11 flex items-center justify-center rounded-full sp-surface-top hover:bg-[#a9a4ff]/20 sp-text-muted hover:sp-primary transition-all" title="Reply via email">
                            <span class="material-symbols-outlined" style="font-size:20px">reply</span>
                        </a>

                        <!-- Delete -->
                        <form method="POST" action="<?= contacts_url($site_id, array_filter(['search' => $search ?: null, 'status' => $status_filter !== 'all' ? $status_filter : null, 'page' => $page > 1 ? $page : null])) ?>"
                              onsubmit="return confirm('Delete this contact permanently?')">
                            <input type="hidden" name="delete_id" value="<?= $contact['id'] ?>">
                            <button type="submit"
                                    class="w-11 h-11 flex items-center justify-center rounded-full sp-surface-top hover:bg-red-500/20 sp-text-muted hover:text-red-400 transition-all" title="Delete">
                                <span class="material-symbols-outlined" style="font-size:20px">delete</span>
                            </button>
                        </form>

                        <!-- Resolve / Resolved -->
                        <?php if ($isNew): ?>
                            <a href="<?= contacts_url($site_id, array_filter(['search' => $search ?: null, 'status' => $status_filter !== 'all' ? $status_filter : null, 'page' => $page > 1 ? $page : null, 'mark_read' => $contact['id']])) ?>"
                               class="px-5 h-11 flex items-center gap-2 rounded-full bg-[#a9a4ff]/10 hover:bg-[#a9a4ff]/20 sp-primary font-bold text-sm transition-all" title="Mark as resolved">
                                <span class="material-symbols-outlined text-lg" style="font-size:18px">check_circle</span>
                                <span class="hidden sm:inline">Resolve</span>
                            </a>
                        <?php else: ?>
                            <div class="px-5 h-11 flex items-center gap-2 rounded-full bg-[#474656]/20 text-slate-600 font-bold text-sm cursor-default select-none">
                                <span class="material-symbols-outlined text-lg" style="font-size:18px; font-variation-settings: 'FILL' 1;">check_circle</span>
                                <span class="hidden sm:inline">Resolved</span>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </section>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <footer class="mt-12 flex items-center justify-center gap-2 flex-wrap">
            <?php
            $base_query = array_filter(['site_id' => $site_id, 'search' => $search ?: null, 'status' => $status_filter !== 'all' ? $status_filter : null]);
            ?>

            <!-- Prev -->
            <?php if ($page > 1): ?>
                <a href="<?= BASE_URL ?>/dashboard/contacts?<?= http_build_query(array_merge($base_query, ['page' => $page - 1])) ?>"
                   class="w-10 h-10 flex items-center justify-center rounded-full sp-surface hover:sp-surface-hi border sp-border transition-all sp-text-muted hover:sp-text">
                    <span class="material-symbols-outlined">chevron_left</span>
                </a>
            <?php else: ?>
                <span class="w-10 h-10 flex items-center justify-center rounded-full sp-surface-low border sp-border sp-text-faint cursor-not-allowed">
                    <span class="material-symbols-outlined">chevron_left</span>
                </span>
            <?php endif; ?>

            <!-- Pages -->
            <?php
            $start = max(1, $page - 2);
            $end   = min($total_pages, $page + 2);
            if ($start > 1): ?>
                <a href="<?= BASE_URL ?>/dashboard/contacts?<?= http_build_query(array_merge($base_query, ['page' => 1])) ?>"
                   class="w-10 h-10 flex items-center justify-center rounded-full hover:sp-surface transition-all sp-text-muted hover:sp-text text-sm">1</a>
                <?php if ($start > 2): ?><span class="px-1 text-slate-600">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $start; $p <= $end; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="w-10 h-10 flex items-center justify-center rounded-full bg-[#a9a4ff] text-[#20009e] font-black text-sm"><?= $p ?></span><!-- pagination active stays hardcoded (brand color) -->
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/dashboard/contacts?<?= http_build_query(array_merge($base_query, ['page' => $p])) ?>"
                       class="w-10 h-10 flex items-center justify-center rounded-full hover:sp-surface transition-all sp-text-muted hover:sp-text text-sm"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?><span class="px-1 text-slate-600">…</span><?php endif; ?>
                <a href="<?= BASE_URL ?>/dashboard/contacts?<?= http_build_query(array_merge($base_query, ['page' => $total_pages])) ?>"
                   class="w-10 h-10 flex items-center justify-center rounded-full hover:sp-surface transition-all sp-text-muted hover:sp-text text-sm"><?= $total_pages ?></a>
            <?php endif; ?>

            <!-- Next -->
            <?php if ($page < $total_pages): ?>
                <a href="<?= BASE_URL ?>/dashboard/contacts?<?= http_build_query(array_merge($base_query, ['page' => $page + 1])) ?>"
                   class="w-10 h-10 flex items-center justify-center rounded-full sp-surface hover:sp-surface-hi border sp-border transition-all sp-text-muted hover:sp-text">
                    <span class="material-symbols-outlined">chevron_right</span>
                </a>
            <?php else: ?>
                <span class="w-10 h-10 flex items-center justify-center rounded-full sp-surface-low border sp-border sp-text-faint cursor-not-allowed">
                    <span class="material-symbols-outlined">chevron_right</span>
                </span>
            <?php endif; ?>

        </footer>
        <p class="text-center text-xs sp-text-faint mt-4">
            Showing <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> of <?= $total ?> records
        </p>
    <?php endif; ?>

</div>

<?php render_dashboard_footer(); ?>
