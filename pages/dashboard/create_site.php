<?php
// Dashboard - Criar Novo Site

require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Sessão expirada. Tente novamente.";
    } else {
        $siteName = trim($_POST['site_name'] ?? '');
        
        if (empty($siteName)) {
            $error = "O nome do site é obrigatório.";
        } else {
            $slug = generate_slug($siteName);
            
            // Garantir que slug é único
            $counter = 1;
            $originalSlug = $slug;
            while (db_fetch_one("SELECT id FROM sites WHERE slug = :slug", [':slug' => $slug])) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            // Pegar tema padrão
            $defaultTheme = db_fetch_one("SELECT id FROM themes WHERE folder_name = 'default'");
            $themeId = $defaultTheme ? $defaultTheme['id'] : null;

            try {
                $pdo->beginTransaction();

                // 1. Criar o Site
                $siteId = db_insert('sites', [
                    'user_id' => $user['id'],
                    'slug' => $slug,
                    'theme_id' => $themeId,
                    'status' => 'active'
                ]);

                // 2. Criar a Página Principal ONEPAGE
                $pageId = db_insert('pages', [
                    'site_id' => $siteId,
                    'slug' => 'home',
                    'title' => $siteName
                ]);

                // 3. Criar a Assinatura (1 ano grátis para o primeiro site)
                $sitesCount = db_fetch_one("SELECT COUNT(id) as total FROM sites WHERE user_id = :uid", [':uid' => $user['id']])['total'];
                $sitesCount = (int)$sitesCount - 1; // desconta o recém criado

                $expiresAt = ($sitesCount === 0) ? date('Y-m-d H:i:s', strtotime('+1 year')) : null;
                $status = ($sitesCount === 0) ? 'active' : 'expired'; // Se não for o 1º, requer pgto antes de ativar de fato (regra de negócios simplificada)

                db_insert('subscriptions', [
                    'user_id' => $user['id'],
                    'site_id' => $siteId,
                    'plan_type' => 'free_trial',
                    'expires_at' => $expiresAt,
                    'status' => $status
                ]);

                // 4. Inserir Blocos Iniciais default do tema default com config JSON inicial (Todos checkados/criados por padrão)
                $initialBlocks = [
                    ['type' => 'header',       'config' => json_encode(['title' => $siteName])],
                    ['type' => 'hero',         'config' => json_encode(['title' => 'Bem-vindo ao ' . $siteName])],
                    ['type' => 'about',        'config' => json_encode(['title' => 'Sobre Nós', 'description' => 'Um pouco sobre nossa história e valores.'])],
                    ['type' => 'services',     'config' => json_encode(['title' => 'Nossos Serviços', 'description' => 'Conheça o que podemos fazer por você.'])],
                    ['type' => 'products',     'config' => json_encode(['title' => 'Nossos Produtos', 'description' => 'Explore as nossas melhores opções para você.'])],
                    ['type' => 'gallery',      'config' => json_encode(['title' => 'Galeria de Fotos', 'description' => 'Confira nossos melhores momentos e imagens.'])],
                    ['type' => 'videos',       'config' => json_encode(['title' => 'Vídeos', 'description' => 'Assista aos nossos vídeos institucionais.'])],
                    ['type' => 'testimonials', 'config' => json_encode(['title' => 'Depoimentos'])],
                    ['type' => 'contact',      'config' => json_encode(['title' => 'Entre em Contato', 'button_text' => 'Enviar Mensagem'])],
                    ['type' => 'footer',       'config' => json_encode(['title' => $siteName])],
                ];
                foreach ($initialBlocks as $i => $blockData) {
                    db_insert('blocks', [
                        'page_id'    => $pageId,
                        'type'       => $blockData['type'],
                        'sort_order' => $i,
                        'config'     => $blockData['config'],
                    ]);
                }

                $pdo->commit();

                $_SESSION['flash_success'] = "Site '$siteName' criado com sucesso! Este é o seu painel de edição.";
                redirect("/dashboard/content?site_id=$siteId");

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erro ao criar site. Tente novamente mais tarde. (" . $e->getMessage() . ")";
            }
        }
    }
}

$csrf_token = generate_csrf_token();
render_dashboard_header("Create New Site");
?>

<div class="max-w-lg mx-auto flex flex-col gap-6">

    <!-- Page header -->
    <div>
        <div class="flex items-center gap-3 mb-2">
            <span class="px-3 py-1 bg-[#a9a4ff]/10 text-[#a9a4ff] text-xs font-black rounded-full tracking-widest uppercase">New Site</span>
        </div>
        <h1 class="text-3xl font-black font-headline text-white">Create a New Site</h1>
        <p class="text-slate-400 text-sm mt-1">Give your site a name and we'll set everything up for you.</p>
    </div>

    <?php if ($error): ?>
        <div class="flex items-center gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-[#181828] rounded-xl border border-white/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:18px">web</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-white font-headline">Site Details</h3>
                <p class="text-xs text-slate-500">Set the initial details for your new site.</p>
            </div>
        </div>
        <div class="p-6">
            <form action="<?= BASE_URL ?>/dashboard/create_site" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="space-y-1.5">
                    <label for="site_name" class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Site / Business Name</label>
                    <input type="text" name="site_name" id="site_name" required autofocus
                           class="w-full bg-[#121220] border-none rounded-xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all text-sm"
                           placeholder="e.g. My Business">
                    <p class="text-xs text-slate-500">This name will be used to generate your free URL (e.g. <?= BASE_URL ?>/my-business).</p>
                </div>

                <div class="bg-[#121220] rounded-xl p-4 border border-[#a9a4ff]/10 flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#a9a4ff] flex-shrink-0 mt-0.5" style="font-size:18px">info</span>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Your site will be created with all blocks pre-configured. You can customise content, design, and structure immediately after creation.
                    </p>
                </div>

                <div class="flex items-center justify-between gap-4 pt-2">
                    <a href="<?= BASE_URL ?>/dashboard"
                       class="px-5 py-2.5 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-400 hover:text-white font-bold text-sm transition-all">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-8 py-2.5 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm shadow-lg shadow-[#685ef7]/20 hover:brightness-110 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size:18px">add</span>
                        Create Site
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php render_dashboard_footer(); ?>
