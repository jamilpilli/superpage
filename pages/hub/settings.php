<?php
// HUB - Configurações Globais (Settings) e Log de Auditoria

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/hub_template.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF Inválido.";
    } else {
        $updates = 0;
        foreach ($_POST['settings'] ?? [] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET key_value = :val WHERE key_name = :key");
            $res = $stmt->execute([':val' => trim($value), ':key' => $key]);
            if ($res && $stmt->rowCount() > 0) {
                $updates++;
            }
        }
        
        if ($updates > 0) {
            db_insert('hub_audit_logs', [
                'admin_id' => get_logged_user()['id'],
                'action_type' => "settings_update",
                'entity_type' => 'settings',
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            $_SESSION['hub_msg'] = "$updates configuração(ões) salva(s) com sucesso.";
        }
        redirect('/hub/settings');
    }
}

$allSettings = db_fetch_all("SELECT * FROM settings ORDER BY group_name ASC, key_name ASC");

// Agrupar por categoria
$groupedSettings = [];
foreach ($allSettings as $s) {
    // Tratamento visual para o nome do grupo
    $gname = match($s['group_name']) {
        'general' => 'Geral do Sistema',
        'billing' => 'Faturamento e Planos',
        'email'   => 'Servidor de E-mail (SMTP)',
        default   => ucfirst($s['group_name'])
    };
    $groupedSettings[$gname][] = $s;
}

$csrf_token = generate_csrf_token();
render_hub_header("Configurações Globais");
?>

<?php if (isset($_SESSION['hub_msg'])): ?>
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 shadow-sm">
        <p class="text-sm text-green-700 font-bold"><?= $_SESSION['hub_msg'] ?></p>
        <?php unset($_SESSION['hub_msg']); ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/hub/settings">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    
    <div class="space-y-6">
        <?php foreach ($groupedSettings as $groupLabel => $items): ?>
        <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6 mb-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-3 mb-4 text-indigo-700"><?= $groupLabel ?></h3>
            
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <?php foreach ($items as $item): ?>
                    <div class="sm:col-span-6">
                        <label for="set_<?= $item['id'] ?>" class="block text-sm font-medium text-gray-700">
                            <?= ucwords(str_replace('_', ' ', $item['key_name'])) ?>
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm w-full md:w-1/2">
                            <?php if ($item['type'] === 'boolean'): ?>
                                <select name="settings[<?= $item['key_name'] ?>]" id="set_<?= $item['id'] ?>" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="true" <?= $item['key_value'] === 'true' ? 'selected' : '' ?>>Sim / Ativo</option>
                                    <option value="false" <?= $item['key_value'] === 'false' ? 'selected' : '' ?>>Não / Inativo</option>
                                </select>
                            <?php else: ?>
                                <input type="<?= $item['type'] === 'string' ? 'text' : 'number' ?>" 
                                       step="<?= $item['type'] === 'integer' ? '1' : '0.01' ?>"
                                       name="settings[<?= $item['key_name'] ?>]" 
                                       id="set_<?= $item['id'] ?>" 
                                       value="<?= htmlspecialchars($item['key_value']) ?>"
                                       class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <?php endif; ?>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Chave base: <code class="bg-gray-100 px-1 rounded"><?= $item['key_name'] ?></code></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="flex justify-end mt-4 mb-20 md:mb-10">
        <button type="submit" class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-6 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Salvar Configurações
        </button>
    </div>
</form>

<?php render_hub_footer(); ?>
