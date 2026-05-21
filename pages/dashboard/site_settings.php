<?php
// Dashboard - Configurações do Site (Painel Individual do Usuário)

require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();
$siteId = $_GET['site_id'] ?? null;

if (!$siteId) {
    redirect('/dashboard');
}

if (!can_access_site($siteId)) {
    http_response_code(404);
    echo "Site not found or access denied.";
    exit;
}
$site = db_fetch_one("SELECT * FROM sites WHERE id = :id AND status != 'inactive'", [':id' => $siteId]);

$error = '';
$success = '';

// Fluxo de Atualização (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF inválido.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_general') {
            $newSlug = trim($_POST['slug']);
            $newDomain = trim($_POST['domain']);
            
            // Valida Slug único (excluindo o atual)
            $checkSlug = db_fetch_one("SELECT id FROM sites WHERE slug = :slug AND id != :id", [
                ':slug' => $newSlug,
                ':id' => $site['id']
            ]);
            
            // Valida Domínio único (excluindo o atual)
            $checkDomain = null;
            if (!empty($newDomain)) {
                $checkDomain = db_fetch_one("SELECT id FROM sites WHERE domain = :domain AND id != :id", [
                    ':domain' => $newDomain,
                    ':id' => $site['id']
                ]);
            }

            if ($checkSlug) {
                $error = "Este slug já está em uso por outro site.";
            } elseif ($checkDomain) {
                $error = "Este domínio já está registrado em outro site na plataforma.";
            } else {
                $updateData = [
                    'slug' => $newSlug,
                    'domain' => empty($newDomain) ? null : $newDomain
                ];
                
                db_update('sites', $updateData, 'id = :id', [':id' => $site['id']]);
                
                // Se o slug mudou, a gente criaria um redirect 301 aqui (Fase 8)
                if ($site['slug'] !== $newSlug) {
                    try {
                        db_insert('redirects', [
                            'old_url' => '/' . $site['slug'],
                            'new_url' => '/' . $newSlug,
                            'site_id' => $site['id']
                        ]);
                    } catch (Exception $e) {
                        // Tabela redirects pode não existir se fase 8 não rodou
                    }
                }
                
                $success = "Configurações atualizadas com sucesso.";
                $site['slug'] = $newSlug;
                $site['domain'] = $newDomain;
            }
        } elseif ($action === 'delete_site') {
            db_update('sites', ['status' => 'inactive'], 'id = :id', [':id' => $site['id']]);
            $_SESSION['flash_success'] = "O site foi movido para a lixeira/inativado. As assinaturas vinculadas serão processadas em breve.";
            redirect('/dashboard');
        }
    }
}

$csrf_token = generate_csrf_token();
$siteName = htmlspecialchars($site['domain'] ?: $site['slug']);
render_dashboard_header("Site Settings – $siteName");
?>

<div class="max-w-2xl mx-auto flex flex-col gap-6">

    <!-- Page header -->
    <div>
        <div class="flex items-center gap-3 mb-2">
            <span class="px-3 py-1 bg-[#a9a4ff]/10 text-[#a9a4ff] text-xs font-black rounded-full tracking-widest uppercase">Site Settings</span>
        </div>
        <h1 class="text-3xl font-black font-headline text-white">Site Settings</h1>
        <p class="text-slate-400 text-sm mt-1">Manage your domain, URL and site configuration.</p>
    </div>

    <?php if ($error): ?>
        <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="flex items-center gap-3 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">check_circle</span>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Web Addresses -->
    <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:18px">language</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-white font-headline">Web Addresses</h3>
                <p class="text-xs text-slate-500">Change your free SuperPage link or connect a custom domain.</p>
            </div>
        </div>
        <div class="p-6">
            <form method="POST" action="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $site['id'] ?>" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="update_general">

                <div class="space-y-1.5">
                    <label for="slug" class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Free Subdomain (Slug)</label>
                    <div class="flex rounded-xl overflow-hidden">
                        <span class="inline-flex items-center px-4 bg-[#0d0d1a] text-slate-500 text-sm font-medium border-r border-white/5 flex-shrink-0">
                            superpage.com/
                        </span>
                        <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($site['slug']) ?>" required
                               class="flex-1 bg-[#121220] px-4 py-4 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="domain" class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Custom Domain</label>
                    <div class="flex rounded-xl overflow-hidden">
                        <span class="inline-flex items-center px-4 bg-[#0d0d1a] text-slate-500 text-sm font-medium border-r border-white/5 flex-shrink-0">
                            https://
                        </span>
                        <input type="text" name="domain" id="domain" value="<?= htmlspecialchars($site['domain'] ?? '') ?>"
                               placeholder="www.yourbusiness.co.uk"
                               class="flex-1 bg-[#121220] px-4 py-4 text-white text-sm placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-[#685ef7]/50">
                    </div>
                    <p class="text-xs text-slate-500">Requires a CNAME or A record pointing to our servers via your DNS provider.</p>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm shadow-lg shadow-[#685ef7]/20 hover:brightness-110 transition-all">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-[#181828] rounded-xl border border-red-500/20 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-500/10 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-red-500/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-red-400" style="font-size:18px">warning</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-red-400 font-headline">Danger Zone</h3>
                <p class="text-xs text-slate-500">Irreversible and destructive actions.</p>
            </div>
        </div>
        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-sm font-bold text-white mb-1">Delete this site</p>
                <p class="text-sm text-slate-400 max-w-sm">Once deleted, linked subscriptions will be cancelled and domain traffic stopped permanently. This cannot be undone.</p>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $site['id'] ?>"
                  onsubmit="return confirm('Are you sure? This will deactivate the site and take it offline. You can request restoration via support if needed.');"
                  class="flex-shrink-0">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="delete_site">
                <button type="submit"
                        class="px-5 py-2.5 rounded-full bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 font-bold text-sm transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size:18px">delete</span>
                    Delete Site
                </button>
            </form>
        </div>
    </div>

</div>

<?php render_dashboard_footer(); ?>
