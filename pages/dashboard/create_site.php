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
render_dashboard_header("Criar Novo Site");
?>

<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">Novo Site</h2>
            <p class="mt-1 text-sm text-gray-500">Configure os dados iniciais do seu projeto.</p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="<?= BASE_URL ?>/dashboard" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                Voltar
            </a>
        </div>
    </div>

    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/dashboard/create_site" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div>
                <label for="site_name" class="block text-sm font-medium text-gray-700">Nome do Site / Negócio</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="text" name="site_name" id="site_name" required
                           class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           placeholder="Ex: Minha Empresa">
                </div>
                <p class="mt-2 text-sm text-gray-500">Este nome será usado para gerar sua URL gratuita (ex: <?= BASE_URL ?>/minha-empresa).</p>
            </div>

            <div class="bg-gray-50 px-4 py-3 text-right sm:px-6 -mx-6 -mb-6 mt-6 rounded-b-lg border-t border-gray-200">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Gerar Site Agora
                </button>
            </div>
        </form>
    </div>
</div>

<?php render_dashboard_footer(); ?>
