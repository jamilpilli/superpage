<?php
// Layout Base do Dashboard do Cliente

function render_dashboard_header($title = "Dashboard") {
    global $currentSite; // Armazenar o site atual
    $user = get_logged_user();
    
    // Buscar sites ativos do usuário para popular o dropdown
    $sites = db_fetch_all("SELECT id, slug, domain, design FROM sites WHERE user_id = :uid AND status != 'inactive' ORDER BY created_at DESC", [':uid' => $user['id']]);
    
    $currentSiteId = isset($_GET['site_id']) ? (int)$_GET['site_id'] : null;
    $currentSite = null;
    
    if ($currentSiteId) {
        foreach ($sites as $s) {
            if ($s['id'] == $currentSiteId) {
                $currentSite = $s;
                break;
            }
        }
    }

    $unreadContactsCount = 0;
    if ($currentSiteId) {
        try {
            $unreadResult = db_fetch_one("SELECT COUNT(*) as total FROM site_contacts WHERE site_id = :sid AND status = 'new'", [':sid' => $currentSiteId]);
            $unreadContactsCount = (int)($unreadResult['total'] ?? 0);
        } catch (\PDOException $e) {
            // Tabela pode não existir
        }
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex flex-col" x-data="{ siteMenuOpen: false }">
        
        <!-- Header Principal -->
        <nav class="bg-indigo-600 shadow border-b border-indigo-700 relative z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-6">
                        <a href="<?= BASE_URL ?>/dashboard" class="flex-shrink-0 flex items-center">
                            <span class="text-white font-bold text-xl"><?= APP_NAME ?> painel</span>
                        </a>
                        
                        <!-- Dropdown Meus Sites -->
                        <div class="relative">
                            <button @click="siteMenuOpen = !siteMenuOpen" @click.away="siteMenuOpen = false" class="text-indigo-100 hover:text-white flex items-center space-x-1 focus:outline-none font-medium text-sm transition">
                                <span class="max-w-xs truncate"><?= $currentSite ? htmlspecialchars($currentSite['domain'] ?: $currentSite['slug']) : 'Meus Sites' ?></span>
                                <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': siteMenuOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            
                            <div x-show="siteMenuOpen" style="display: none;" class="origin-top-left absolute left-0 mt-2 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none py-1 z-50">
                                <a href="<?= BASE_URL ?>/dashboard/create_site" class="block px-4 py-3 text-sm text-indigo-700 font-bold border-b border-gray-100 hover:bg-indigo-50 transition">
                                    + Novo Site
                                </a>
                                <?php foreach ($sites as $site): ?>
                                    <a href="<?= BASE_URL ?>/dashboard?site_id=<?= $site['id'] ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-700 transition <?= $currentSiteId == $site['id'] ? 'bg-gray-50 font-bold border-l-2 border-indigo-600 text-indigo-700' : '' ?>">
                                        <?= htmlspecialchars($site['domain'] ?: $site['slug'] . ' .superpage') ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-indigo-100 text-sm hidden md:inline-block border-r border-indigo-500 pr-4 mr-1 lg:pr-6 lg:mr-2">Olá, <?= htmlspecialchars($user['name']) ?></span>
                        <a href="<?= BASE_URL ?>/dashboard/settings" class="text-white hover:text-indigo-200 text-sm font-medium transition">Minha Conta</a>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="<?= BASE_URL ?>/hub" class="text-indigo-200 hover:text-white text-sm font-bold transition whitespace-nowrap ml-4">HUB</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/auth/logout" class="text-white hover:text-indigo-200 text-sm font-medium border border-transparent hover:border-indigo-400 px-3 py-1 rounded transition ml-2">Sair</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Submenu Funcional de Edição -->
        <div class="bg-white shadow relative z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex space-x-8 h-12 items-center text-sm font-medium text-gray-700 relative">
                    <?php if ($currentSiteId): ?>
                        <a href="<?= BASE_URL ?>/dashboard?site_id=<?= $currentSiteId ?>" class="hover:text-indigo-600 <?= (strpos($_SERVER['REQUEST_URI'], '/content') === false && strpos($_SERVER['REQUEST_URI'], '/site_settings') === false && strpos($_SERVER['REQUEST_URI'], '/contacts') === false) ? 'text-indigo-600 border-b-2 border-indigo-600' : 'border-b-2 border-transparent' ?> h-full flex items-center transition">
                            Início
                        </a>
                        <a href="<?= BASE_URL ?>/dashboard/content?site_id=<?= $currentSiteId ?>" class="hover:text-indigo-600 <?= strpos($_SERVER['REQUEST_URI'], '/content') !== false ? 'text-indigo-600 border-b-2 border-indigo-600' : 'border-b-2 border-transparent' ?> h-full flex items-center transition">
                            Editar Conteúdo
                        </a>
                        <button type="button" @click="$dispatch('open-design-modal')" class="hover:text-indigo-600 border-b-2 border-transparent h-full flex items-center transition">
                            Editar Design
                        </button>
                        <button type="button" @click="$dispatch('open-structure-modal')" class="hover:text-indigo-600 border-b-2 border-transparent h-full flex items-center transition">
                            Editar Estrutura
                        </button>
                        <a href="<?= BASE_URL ?>/dashboard/site_settings?site_id=<?= $currentSiteId ?>" class="hover:text-indigo-600 <?= strpos($_SERVER['REQUEST_URI'], '/site_settings') !== false ? 'text-indigo-600 border-b-2 border-indigo-600' : 'border-b-2 border-transparent' ?> h-full flex items-center transition">
                            Configurações
                        </a>
                        <a href="<?= BASE_URL ?>/dashboard/contacts?site_id=<?= $currentSiteId ?>" class="hover:text-indigo-600 <?= strpos($_SERVER['REQUEST_URI'], '/contacts') !== false ? 'text-indigo-600 border-b-2 border-indigo-600' : 'border-b-2 border-transparent' ?> h-full flex items-center transition gap-2">
                            Contatos
                            <?php if ($unreadContactsCount > 0): ?>
                                <span class="flex-shrink-0 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center animate-pulse">
                                    <?= $unreadContactsCount > 9 ? '9+' : $unreadContactsCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="flex-1 flex justify-end">
                            <a href="<?= BASE_URL ?>/<?= $currentSite['slug'] ?>?preview=true" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold ml-6 h-full flex items-center text-xs uppercase tracking-wide">
                                Visualizar ↗
                            </a>
                        </div>
                    <?php else: ?>
                        <span class="text-gray-400 italic">Selecione um site no painel superior para ver o menu de edição.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
<?php
    if (isset($_SESSION['flash_success'])) {
        echo '<div class="bg-green-50 justify-between text-green-700 p-4 rounded mb-6 text-sm flex border border-green-100 shadow-sm max-w-3xl mx-auto"><span>' . $_SESSION['flash_success'] . '</span></div>';
        unset($_SESSION['flash_success']);
    }
    if (isset($_SESSION['flash_error'])) {
        echo '<div class="bg-red-50 justify-between text-red-700 p-4 rounded mb-6 text-sm flex border border-red-100 shadow-sm max-w-3xl mx-auto"><span>' . $_SESSION['flash_error'] . '</span></div>';
        unset($_SESSION['flash_error']);
    }
}

function render_dashboard_footer() {
    global $currentSite;
?>
        </main>
        
        <!-- Footer do Dashboard -->
        <footer class="bg-white border-t border-gray-200 py-4 mt-auto relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500">
                &copy; <?= date('Y') ?> <?= APP_NAME ?>. Todos os direitos reservados.
            </div>
        </footer>

        <?php if ($currentSite): ?>
            <?php 
                $design = json_decode($currentSite['design'] ?? '{}', true) ?: [];
                $primaryColor = $design['primary_color'] ?? '#4f46e5';
                $titleFont = $design['title_font'] ?? 'Inter';
                $textFont = $design['text_font'] ?? 'Inter';
                $buttonStyle = $design['button_style'] ?? 'rounded';
            ?>
            <!-- Modal Editar Design Global -->
            <div x-data="{ isModalOpen: false, primaryColor: '<?= $primaryColor ?>', titleFont: '<?= $titleFont ?>', textFont: '<?= $textFont ?>', buttonStyle: '<?= $buttonStyle ?>' }" 
                 @open-design-modal.window="isModalOpen = true">
                <div x-show="isModalOpen" style="display: none;" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="isModalOpen" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isModalOpen = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div x-show="isModalOpen" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form method="POST" action="<?= BASE_URL ?>/dashboard">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="update_design">
                                <input type="hidden" name="site_id" value="<?= $currentSite['id'] ?>">
                                <!-- Usamos redirect URL no POST para voltar onde estávamos -->
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Aparência do Site</h3>
                                            <div class="mt-4 space-y-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Cor Principal (Botões e Destaques)</label>
                                                    <div class="mt-1 flex items-center">
                                                        <input type="color" name="primary_color" x-model="primaryColor" class="h-8 w-8 rounded border border-gray-300 cursor-pointer p-0">
                                                        <span class="ml-3 text-sm text-gray-500" x-text="primaryColor"></span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Fonte dos Títulos</label>
                                                    <select name="title_font" x-model="titleFont" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border text-gray-900 bg-white">
                                                        <option value="Inter">Inter</option>
                                                        <option value="Roboto">Roboto</option>
                                                        <option value="Playfair Display">Playfair Display</option>
                                                        <option value="Montserrat">Montserrat</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Fonte do Texto Geral</label>
                                                    <select name="text_font" x-model="textFont" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border text-gray-900 bg-white">
                                                        <option value="Inter">Inter</option>
                                                        <option value="Roboto">Roboto</option>
                                                        <option value="Open Sans">Open Sans</option>
                                                        <option value="Lato">Lato</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Formato dos Botões</label>
                                                    <select name="button_style" x-model="buttonStyle" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border text-gray-900 bg-white">
                                                        <option value="square">Quadrado</option>
                                                        <option value="rounded">Levemente Arredondado</option>
                                                        <option value="rounded-full">Totalmente Arredondado (Pílula)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition">Salvar Alterações</button>
                                    <button type="button" @click="isModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Editar Estrutura Global -->
            <?php $page = db_fetch_one("SELECT id FROM pages WHERE site_id = :sid AND status = 'published' LIMIT 1", [':sid' => $currentSite['id']]); ?>
            <div x-data="{ isStructOpen: false }" 
                 @open-structure-modal.window="isStructOpen = true">
                <div x-show="isStructOpen" style="display: none;" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <!-- Overlay background -->
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="isStructOpen" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isStructOpen = false"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        
                        <!-- Panel Modal Ocupando boa parte da tela -->
                        <div x-show="isStructOpen" class="inline-block align-bottom bg-gray-50 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                            
                            <div class="bg-white px-4 py-3 border-b flex justify-between items-center z-10 relative">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Estrutura do Site</h3>
                                <button type="button" @click="isStructOpen = false" class="text-gray-400 hover:text-gray-500 p-1">✕</button>
                            </div>
                            
                            <!-- App Alpine de Gestão de Blocos (Portado) -->
                            <div x-data="editorApp(<?= $currentSite['id'] ?>, <?= $page['id'] ?? 0 ?>)" class="bg-gray-50 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[70vh] overflow-y-auto flex flex-col relative">
                                
                                <div class="flex justify-between items-center mb-4">
                                    <p class="text-sm text-gray-500">Selecione as seções desejadas e arraste para alterar a ordem no site.</p>
                                    <span x-show="isSaving" class="text-xs px-2 py-1 text-indigo-700 bg-indigo-50 font-bold rounded-full animate-pulse">Salvando...</span>
                                </div>
                                
                                <div class="flex-1 w-full" id="blocks-list">
                                    <template x-for="(item, index) in availableTypes" :key="item.type">
                                        <div class="p-4 mb-2 bg-white border border-gray-200 shadow-sm rounded-lg flex items-center justify-between group hover:border-indigo-400 cursor-move" :data-type="item.type">
                                            
                                            <div class="flex items-center flex-1">
                                                <span class="text-gray-300 mr-4 text-xl group-hover:text-indigo-400 transition" title="Arrastar">↕</span>
                                                <div class="flex-1 cursor-pointer" @click="toggleBlock(item.type)">
                                                    <span class="font-bold text-gray-900 block text-sm" x-text="item.label"></span>
                                                    <span class="text-xs text-gray-500" x-text="item.desc"></span>
                                                </div>
                                            </div>
                                            
                                            <div class="ml-4 flex items-center gap-3">
                                                <!-- Botão de Edição (Só exibe se estiver ativo) -->
                                                <a x-show="isBlockActive(item.type)" :href="'<?= BASE_URL ?>/dashboard/content?site_id=<?= $currentSite['id'] ?? 0 ?>&block_type=' + item.type" class="text-xs font-bold text-indigo-700 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded transition">Editar Conteúdo</a>
                                                
                                                <!-- Custom Toggler -->
                                                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                                    <input type="checkbox" class="sr-only peer" :checked="isBlockActive(item.type)" @change="toggleBlock(item.type)">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                                </label>
                                            </div>
                                            
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <!-- Fim do Alpine App Estrutura -->

                        </div>
                    </div>
                </div>
            </div>

            <!-- Scripts Alpine.js do Modal de Blocos -->
            <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('editorApp', (siteId, pageId) => ({
                    siteId: siteId,
                    pageId: pageId,
                    blocks: [], // blocos vindos do banco
                    availableTypes: [
                        { type: 'header', label: 'Cabeçalho (Menu)', desc: 'Menu de navegação no topo com logo.' },
                        { type: 'hero', label: 'Capa / Banner Principal', desc: 'Slider com título principal de impacto.' },
                        { type: 'about', label: 'Sobre Nós', desc: 'Descrição em texto com imagem lateral.' },
                        { type: 'services', label: 'Serviços', desc: 'Grid de tópicos de serviços.' },
                        { type: 'products', label: 'Produtos', desc: 'Vitrine de itens/produtos em cards.' },
                        { type: 'gallery', label: 'Galeria de Fotos', desc: 'Grade de fotos arrastar e soltar.' },
                        { type: 'videos', label: 'Vídeos', desc: 'Vídeos do YouTube.' },
                        { type: 'testimonials', label: 'Depoimentos', desc: 'Citações e provas sociais.' },
                        { type: 'contact', label: 'Contato', desc: 'Formulário de e-mail e dados.' },
                        { type: 'footer', label: 'Rodapé', desc: 'Faixa inferior de encerramento.' }
                    ],
                    isSaving: false,
                    sortableInstance: null,

                    init() {
                        if (this.pageId) this.fetchBlocks();
                    },

                    async fetchBlocks() {
                        try {
                            const res = await fetch(`<?= BASE_URL ?>/api/blocks?page_id=${this.pageId}`);
                            if (!res.ok) throw new Error('Falha ao carregar blocos');
                            const json = await res.json();
                            this.blocks = json.data || [];
                            
                            // Reordenar visually o `availableTypes` based no sort_order vindos do banco
                            if (this.blocks.length > 0) {
                                const orderedTypes = [];
                                // Primeiro os que já estão no banco, na ordem do banco
                                this.blocks.forEach(b => {
                                    const match = this.availableTypes.find(t => t.type === b.type);
                                    if(match) orderedTypes.push(match);
                                });
                                // Depois os que sobram da lista disponível (desligados), ficam pro final
                                this.availableTypes.forEach(t => {
                                    if(!orderedTypes.find(o => o.type === t.type)) {
                                        orderedTypes.push(t);
                                    }
                                });
                                this.availableTypes = orderedTypes;
                            }
                            
                            this.$nextTick(() => {
                                this.initSortable();
                            });
                        } catch (e) {
                            alert(e.message);
                        }
                    },

                    isBlockActive(type) {
                        return this.blocks.some(b => b.type === type && b.config?.is_active !== false);
                    },

                    async toggleBlock(type) {
                        const existingBlock = this.blocks.find(b => b.type === type);
                        this.isSaving = true;

                        try {
                            if (existingBlock && existingBlock.config?.is_active !== false) {
                                // Desativar (Soft Delete)
                                const res = await fetch(`<?= BASE_URL ?>/api/blocks`, {
                                    method: 'DELETE',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ block_id: existingBlock.id })
                                });
                                if (res.ok) {
                                    existingBlock.config = existingBlock.config || {};
                                    existingBlock.config.is_active = false;
                                    this.evaluateFullReorder();
                                }
                            } else if (existingBlock && existingBlock.config?.is_active === false) {
                                // Reativar
                                existingBlock.config.is_active = true;
                                const res = await fetch(`<?= BASE_URL ?>/api/blocks`, {
                                    method: 'PUT',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ block_id: existingBlock.id, config: existingBlock.config })
                                });
                                if (res.ok) {
                                    this.evaluateFullReorder();
                                } else {
                                    existingBlock.config.is_active = false; // rollback
                                    alert('Erro ao reativar bloco');
                                }
                            } else {
                                // Adicionar novo cenário se não existir nada nem inativo
                                const res = await fetch(`<?= BASE_URL ?>/api/blocks`, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ action: 'add', page_id: this.pageId, type: type })
                                });
                                const json = await res.json();
                                if (json.success) {
                                    await this.fetchBlocks(); 
                                } else {
                                    alert(json.error);
                                }
                            }
                        } catch (e) {
                            console.error(e);
                        }
                        this.isSaving = false;
                    },

                    initSortable() {
                        if (this.sortableInstance) {
                            this.sortableInstance.destroy();
                        }
                        
                        const el = document.getElementById('blocks-list');
                        if (el) {
                            this.sortableInstance = new Sortable(el, {
                                animation: 150,
                                ghostClass: 'bg-indigo-50',
                                handle: '.cursor-move',
                                onEnd: (evt) => {
                                    // Lê a nova ordem do DOM html e grava no banco somente os que estão ATIVOS (em blocks)
                                    this.evaluateFullReorder();
                                }
                            });
                        }
                    },
                    
                    async evaluateFullReorder() {
                        const el = document.getElementById('blocks-list');
                        if(!el) return;
                        
                        const listItems = Array.from(el.children);
                        const newOrder = [];
                        let virtualIndex = 0;
                        
                        listItems.forEach((item) => {
                            const domType = item.getAttribute('data-type');
                            // Procura na memoria see type está ativo
                            const memBlock = this.blocks.find(b => b.type === domType);
                            if (memBlock) {
                                newOrder.push({ id: memBlock.id, sort_order: virtualIndex });
                                virtualIndex++;
                            }
                        });
                        
                        if(newOrder.length > 0) {
                            await this.saveReorder(newOrder);
                        }
                    },

                    async saveReorder(newOrderArray) {
                        this.isSaving = true;
                        try {
                            const res = await fetch(`<?= BASE_URL ?>/api/blocks`, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'reorder', page_id: this.pageId, blocks: newOrderArray })
                            });
                            if (res.ok) {
                                // success stealth
                            }
                        } catch (e) {
                            console.error("Erro ao reordenar", e);
                        }
                        this.isSaving = false;
                    }
                }));
            });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
}
