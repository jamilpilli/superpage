<?php
// HUB - Gestão de Sites

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

// Action Handler (Bloquear / Ativar sites)
if (isset($_POST['action']) && isset($_POST['site_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Token CSRF inválido.";
    } else {
        $siteId = (int)$_POST['site_id'];
        $action = $_POST['action'];
        
        $newStatus = ($action === 'block') ? 'suspended' : 'active';
        db_update('sites', ['status' => $newStatus], 'id = :id', [':id' => $siteId]);
        
        // Registrar Auditoria
        db_insert('hub_audit_logs', [
            'admin_id' => get_logged_user()['id'],
            'action_type' => "site_{$action}",
            'entity_type' => 'sites',
            'entity_id' => $siteId,
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        $_SESSION['hub_msg'] = "Site alterado para $newStatus com sucesso.";
        redirect('/hub/sites');
    }
}

// Busca todos os sites
$sites = db_fetch_all("
    SELECT s.*, u.name as owner_name, u.email as owner_email, t.name as theme_name
    FROM sites s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN themes t ON s.theme_id = t.id
    ORDER BY s.created_at DESC
");

$csrf_token = generate_csrf_token();
render_hub_header("Gestão de Sites Publicados");
?>

<?php if (isset($_SESSION['hub_msg'])): ?>
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
        <p class="text-sm text-green-700"><?= $_SESSION['hub_msg'] ?></p>
        <?php unset($_SESSION['hub_msg']); ?>
    </div>
<?php endif; ?>

<div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site / Domínio</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proprietário</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tema Atual</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sites as $s): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-indigo-600 block">
                                    <a href="<?= BASE_URL ?>/<?= $s['slug'] ?>" target="_blank" class="hover:underline">
                                        <?= BASE_URL ?><?= BASE_URL?'/':'' ?><?= htmlspecialchars($s['slug']) ?>
                                    </a>
                                </div>
                                <?php if ($s['domain']): ?>
                                    <div class="text-xs text-gray-500 mt-1">Domínio: <?= htmlspecialchars($s['domain']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($s['owner_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($s['owner_email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($s['status'] === 'active'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Suspenso</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $s['theme_name'] ? htmlspecialchars($s['theme_name']) : 'Nenhum' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form method="POST" action="<?= BASE_URL ?>/hub/sites" class="inline" onSubmit="return confirm('Tem certeza que deseja mudar a situação deste site?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="site_id" value="<?= $s['id'] ?>">
                                    
                                    <?php if ($s['status'] === 'active'): ?>
                                        <input type="hidden" name="action" value="block">
                                        <button type="submit" class="text-red-600 hover:text-red-900 border border-red-200 hover:bg-red-50 px-3 py-1 rounded">Bloquear</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="unblock">
                                        <button type="submit" class="text-green-600 hover:text-green-900 border border-green-200 hover:bg-green-50 px-3 py-1 rounded">Reativar</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($sites)): ?>
                            <tr><td colspan="5" class="py-10 text-center text-sm text-gray-500">Nenhum site publicado na plataforma ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php render_hub_footer(); ?>
