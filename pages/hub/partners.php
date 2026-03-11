<?php
// HUB - Gestão de Parceiros (Agências que revendem a plataforma)

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

if (isset($_POST['action']) && isset($_POST['user_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Token CSRF inválido.";
    } else {
        $uId = (int)$_POST['user_id'];
        $action = $_POST['action'];
        
        $newRole = '';
        if ($action === 'make_partner') $newRole = 'partner';
        if ($action === 'revoke_partner') $newRole = 'client';
        
        if ($newRole && $uId !== get_logged_user()['id']) {
            db_update('users', ['role' => $newRole], 'id = :id', [':id' => $uId]);
            db_insert('hub_audit_logs', [
                'admin_id' => get_logged_user()['id'],
                'action_type' => "role_$action",
                'entity_type' => 'users',
                'entity_id' => $uId,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            $_SESSION['hub_msg'] = "Permissões de parceiro atualizadas.";
        }
        redirect('/hub/partners');
    }
}

// Filtro simples de listagem: todos ou só partners
$onlyPartners = isset($_GET['full']) ? false : true;

$sql = "SELECT u.id, u.name, u.email, u.role, u.created_at, 
        (SELECT COUNT(s.id) FROM sites s WHERE s.user_id = u.id) as sites_count 
        FROM users u WHERE role != 'admin'";

if ($onlyPartners) {
    $sql .= " AND role = 'partner'";
}
$sql .= " ORDER BY u.created_at DESC";

$users = db_fetch_all($sql);
$csrf_token = generate_csrf_token();

render_hub_header("Gestão de Parceiros Comerciais");
?>

<div class="mb-4 flex justify-between items-center">
    <p class="text-sm text-gray-600">
        Parceiros têm acesso a descontos na renovação de múltiplos sites.
    </p>
    <div>
        <?php if ($onlyPartners): ?>
            <a href="?full=1" class="text-sm bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded shadow-sm hover:bg-gray-50">Mostrar Todos Clientes p/ Elevar</a>
        <?php else: ?>
            <a href="?" class="text-sm bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded shadow-sm hover:bg-gray-50">Mostrar Somente Parceiros</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['hub_msg'])): ?>
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
        <p class="text-sm text-green-700"><?= $_SESSION['hub_msg'] ?></p>
        <?php unset($_SESSION['hub_msg']); ?>
    </div>
<?php endif; ?>

<div class="bg-white shadow overflow-hidden sm:rounded-lg border-b border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cadastro</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume (Sites)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hierarquia</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($users as $u): ?>
            <tr class="<?= $u['role'] === 'partner' ? 'bg-yellow-50 bg-opacity-30' : '' ?>">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($u['name']) ?></div>
                    <div class="text-sm text-gray-500"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold">
                    <?= $u['sites_count'] ?> site(s)
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <?php if ($u['role'] === 'partner'): ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">💎 Partner VIP</span>
                    <?php else: ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Cliente Comum</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <form method="POST" action="<?= BASE_URL ?>/hub/partners" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        
                        <?php if ($u['role'] === 'partner'): ?>
                            <input type="hidden" name="action" value="revoke_partner">
                            <button type="submit" class="text-red-600 hover:text-red-900 text-xs" onClick="return confirm('Remover selo de parceria?');">Revogar Partner</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="make_partner">
                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 border border-indigo-200 px-2 py-1 rounded text-xs">Tornar Partner</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php render_hub_footer(); ?>
