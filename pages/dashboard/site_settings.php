<?php
// Dashboard - Configurações do Site (Painel Individual do Usuário)

require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();
$siteId = $_GET['site_id'] ?? null;

if (!$siteId) {
    redirect('/dashboard');
}

// Verifica ownership do site (Segurança) e se não foi deletado
$site = db_fetch_one("SELECT * FROM sites WHERE id = :id AND user_id = :uid AND status != 'inactive'", [
    ':id' => $siteId,
    ':uid' => $user['id']
]);

if (!$site) {
    http_response_code(404);
    echo "Site não encontrado ou você não tem permissão para acessá-lo.";
    exit;
}

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
render_dashboard_header("Configurações do Site");
?>

<div class="max-w-4xl mx-auto pb-10">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">Configurações Gerais</h2>
            <p class="mt-1 text-sm text-gray-500">Gerenciamento de domínios, visibilidade e integrações base do site.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6"><p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6"><p class="text-sm text-green-700"><?= htmlspecialchars($success) ?></p></div>
    <?php endif; ?>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Endereços da Web (URLs)</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Altere o link gratuito Superpage ou vincule seu próprio domínio (.com.br).</p>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <form method="POST" action="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $site['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="update_general">
                
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    <div class="sm:col-span-4">
                        <label for="slug" class="block text-sm font-medium text-gray-700">Subdomínio Gratuito (Slug)</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                superpage.com.br/
                            </span>
                            <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($site['slug']) ?>" required
                                   class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="sm:col-span-4">
                        <label for="domain" class="block text-sm font-medium text-gray-700">Domínio Customizado Automático</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                https://
                            </span>
                            <input type="text" name="domain" id="domain" value="<?= htmlspecialchars($site['domain'] ?? '') ?>" 
                                   placeholder="www.minhaempresa.com.br"
                                   class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Requer apontamento do Tipo CNAME ou A (via DNS) para nossos servidores.</p>
                    </div>

                </div>
                
                <div class="mt-6 pt-5 border-t border-gray-200 flex justify-start">
                    <button type="submit" class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-8 inline-flex justify-center text-sm font-bold text-white hover:bg-indigo-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Seção Danger Zone futura -->
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Excluir Site</h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500">
                <p>Uma vez deletado o site, as assinaturas vinculadas serão canceladas e o tráfego do domínio interrompido permanentemente. Esta ação não tem volta.</p>
            </div>
            <div class="mt-5">
                <form method="POST" action="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $site['id'] ?>" onsubmit="return confirm('Tem certeza? Isso vai inativar o site e tirá-lo do ar. Você poderá solicitar a restauração via suporte caso necessário.');">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="delete_site">
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Deletar Site
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php render_dashboard_footer(); ?>
