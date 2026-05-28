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
            <span class="px-3 py-1 bg-[#a9a4ff]/10 sp-primary text-xs font-black rounded-full tracking-widest uppercase">Site Settings</span>
        </div>
        <h1 class="text-3xl font-black font-headline sp-text">Site Settings</h1>
        <p class="sp-text-muted text-sm mt-1">Manage your domain, URL and site configuration.</p>
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
    <div class="sp-surface rounded-xl border sp-border overflow-hidden">
        <div class="px-6 py-4 border-b sp-border flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined sp-primary" style="font-size:18px">language</span>
            </div>
            <div>
                <h3 class="text-base font-bold sp-text font-headline">Web Addresses</h3>
                <p class="text-xs sp-text-faint">Change your free SuperPage link or connect a custom domain.</p>
            </div>
        </div>
        <div class="p-6">
            <form method="POST" action="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $site['id'] ?>" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="update_general">

                <div class="space-y-1.5">
                    <label for="slug" class="text-xs font-bold uppercase tracking-widest sp-primary">Free Subdomain (Slug)</label>
                    <div class="flex rounded-xl overflow-hidden">
                        <span class="inline-flex items-center px-4 sp-bg sp-text-faint text-sm font-medium border-r sp-border flex-shrink-0">
                            superpage.com/
                        </span>
                        <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($site['slug']) ?>" required
                               class="flex-1 px-4 py-4 text-sm sp-input" style="border-radius:0">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="domain" class="text-xs font-bold uppercase tracking-widest sp-primary">Custom Domain</label>
                    <div class="flex rounded-xl overflow-hidden">
                        <span class="inline-flex items-center px-4 sp-bg sp-text-faint text-sm font-medium border-r sp-border flex-shrink-0">
                            https://
                        </span>
                        <input type="text" name="domain" id="domain" value="<?= htmlspecialchars($site['domain'] ?? '') ?>"
                               placeholder="www.yourbusiness.co.uk"
                               class="flex-1 px-4 py-4 text-sm sp-input" style="border-radius:0">
                    </div>
                    <p class="text-xs sp-text-faint">Requires a CNAME or A record pointing to our servers via your DNS provider.</p>
                </div>

                <!-- How to connect a domain -->
                <div class="sp-bg rounded-xl border sp-border p-5 space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined sp-primary" style="font-size:18px">help</span>
                        <span class="text-sm font-bold sp-text">How to connect your domain</span>
                    </div>

                    <div class="space-y-3 text-sm sp-text-muted">
                        <p>Log in to your domain registrar (e.g. GoDaddy, Namecheap, Cloudflare) and go to the <strong class="sp-text-muted">DNS settings</strong> for your domain. Add one of the following records:</p>

                        <!-- Option A: CNAME -->
                        <div class="sp-surface-low rounded-lg p-4 space-y-2">
                            <p class="text-xs font-black uppercase tracking-widest sp-primary">Option A — CNAME (recommended for www)</p>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="sp-text-faint border-b sp-border">
                                        <th class="text-left pb-2 font-semibold">Type</th>
                                        <th class="text-left pb-2 font-semibold">Name / Host</th>
                                        <th class="text-left pb-2 font-semibold">Value / Points to</th>
                                        <th class="text-left pb-2 font-semibold">TTL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="sp-text-muted">
                                        <td class="pt-2 font-mono">CNAME</td>
                                        <td class="pt-2 font-mono">www</td>
                                        <td class="pt-2 font-mono">superpage.co.uk</td>
                                        <td class="pt-2 font-mono">Auto</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="sp-text-faint text-xs">Then enter <code class="bg-white/5 px-1 rounded">www.yourdomain.com</code> in the field above.</p>
                        </div>

                        <!-- Option B: A record -->
                        <div class="sp-surface-low rounded-lg p-4 space-y-2">
                            <p class="text-xs font-black uppercase tracking-widest text-[#914feb]">Option B — A Record (for root domain)</p>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="sp-text-faint border-b sp-border">
                                        <th class="text-left pb-2 font-semibold">Type</th>
                                        <th class="text-left pb-2 font-semibold">Name / Host</th>
                                        <th class="text-left pb-2 font-semibold">Value / IP Address</th>
                                        <th class="text-left pb-2 font-semibold">TTL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="sp-text-muted">
                                        <td class="pt-2 font-mono">A</td>
                                        <td class="pt-2 font-mono">@ (or blank)</td>
                                        <td class="pt-2 font-mono">72.61.145.172</td>
                                        <td class="pt-2 font-mono">Auto</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="sp-text-faint text-xs">Then enter <code class="bg-white/5 px-1 rounded">yourdomain.com</code> in the field above.</p>
                        </div>

                        <div class="flex items-start gap-2 text-xs sp-text-faint pt-1">
                            <span class="material-symbols-outlined flex-shrink-0" style="font-size:15px">schedule</span>
                            <span>DNS changes can take up to <strong class="sp-text-muted">24–48 hours</strong> to propagate worldwide, although most providers update within a few minutes.</span>
                        </div>
                    </div>
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
    <div class="sp-surface rounded-xl border border-red-500/20 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-500/10 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-red-500/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-red-400" style="font-size:18px">warning</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-red-400 font-headline">Danger Zone</h3>
                <p class="text-xs sp-text-faint">Irreversible and destructive actions.</p>
            </div>
        </div>
        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-sm font-bold sp-text mb-1">Delete this site</p>
                <p class="text-sm sp-text-muted max-w-sm">Once deleted, linked subscriptions will be cancelled and domain traffic stopped permanently. This cannot be undone.</p>
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
