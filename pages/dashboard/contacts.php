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

// Verifica permissão do site
$site = db_fetch_one("SELECT * FROM sites WHERE id = :sid AND user_id = :uid", [':sid' => $site_id, ':uid' => $user['id']]);
if (!$site) {
    redirect(BASE_URL . '/dashboard');
}

// Marcar como lido
$mark_read = filter_input(INPUT_GET, 'mark_read', FILTER_VALIDATE_INT);
if ($mark_read) {
    db_update('site_contacts', ['status' => 'read'], 'id = :id AND site_id = :sid', [':id' => $mark_read, ':sid' => $site_id]);
    redirect(BASE_URL . '/dashboard/contacts?site_id=' . $site_id);
}

// Busca contatos
$contacts = db_fetch_all("SELECT * FROM site_contacts WHERE site_id = :sid ORDER BY created_at DESC", [':sid' => $site_id]);

// Carrega template global por último para já ter modificado os headers de redirect se precisar
require_once __DIR__ . '/../../includes/dashboard_template.php';
render_dashboard_header("Contatos Recebidos - " . htmlspecialchars($site['domain'] ?: $site['slug']));
?>

<div class="max-w-5xl mx-auto py-8">
    <div class="flex justify-between items-center mb-6 px-4 sm:px-0">
        <h2 class="text-2xl font-bold text-gray-900">Mensagens e Contatos</h2>
        <span class="text-sm text-gray-500 font-medium"><?= count($contacts) ?> registros encontrados</span>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md border border-gray-200">
        <?php if (empty($contacts)): ?>
            <div class="p-10 text-center text-gray-500">
                <svg class="h-12 w-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <p class="font-bold text-gray-600">A caixa de entrada está vazia.</p>
                <p class="text-sm mt-1">Nenhum contato recebido através do formulário do seu site ainda.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($contacts as $contact): ?>
                    <li class="<?= $contact['status'] === 'new' ? 'bg-indigo-50 border-l-4 border-indigo-600' : 'bg-white hover:bg-gray-50' ?> transition">
                        <div class="px-4 py-6 sm:px-6">
                            
                            <!-- Header do Box de Email -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold text-sm uppercase shadow-sm">
                                        <?= substr(htmlspecialchars($contact['name']), 0, 1) ?>
                                    </div>
                                    <div class="flex flex-col">
                                        <p class="text-base font-bold text-gray-900 truncate">
                                            <?= htmlspecialchars($contact['name']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500"><?= date('d/m/Y \à\s H:i', strtotime($contact['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="ml-2 flex-shrink-0 flex items-center gap-2">
                                    <p class="px-2 py-1 inline-flex text-[10px] uppercase font-bold rounded-full <?= $contact['status'] === 'new' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500 border border-gray-200' ?>">
                                        <?= $contact['status'] === 'new' ? 'Nova' : 'Lida' ?>
                                    </p>
                                    <?php if ($contact['status'] === 'new'): ?>
                                        <a href="<?= BASE_URL ?>/dashboard/contacts?site_id=<?= $site_id ?>&mark_read=<?= $contact['id'] ?>" class="text-gray-400 hover:text-indigo-600 transition" title="Marcar como Lido">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Informações de Contato Rápidas -->
                            <div class="mt-2 mb-4 sm:flex gap-6 border-b border-gray-100 pb-4">
                                <p class="flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600">
                                    <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a>
                                </p>
                                <?php if (!empty($contact['phone'])): ?>
                                <p class="mt-2 flex items-center text-sm font-medium text-gray-700 hover:text-green-600 sm:mt-0">
                                    <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contact['phone']) ?>" target="_blank" title="Abrir no WhatsApp"><?= htmlspecialchars($contact['phone']) ?></a>
                                </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Mensagem Principal -->
                            <div class="mt-2 text-sm text-gray-700 bg-white p-4 rounded-md border border-gray-100 sm:p-5 shadow-inner">
                                <?= nl2br(htmlspecialchars($contact['message'])) ?>
                            </div>
                            
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php render_dashboard_footer(); ?>
