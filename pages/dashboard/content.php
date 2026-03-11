<?php
// Dashboard - Editor de Conteúdo (Textos e Imagens dos Blocos)

require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();
$siteId = $_GET['site_id'] ?? 0;

if (!$siteId) {
    redirect('/dashboard');
}

// Verifica ownership e se o site não foi deletado
$site = db_fetch_one("SELECT id, slug, domain FROM sites WHERE id = :id AND user_id = :uid AND status != 'inactive'", [':id' => $siteId, ':uid' => $user['id']]);
if (!$site) {
    redirect('/dashboard');
}

render_dashboard_header("Editar Conteúdo - " . $site['slug']);

$page = db_fetch_one("SELECT id FROM pages WHERE site_id = :sid AND status = 'published' LIMIT 1", [':sid' => $siteId]);
?>

<div class="flex flex-col md:flex-row gap-6 min-h-[calc(100vh-12rem)]" x-data="contentApp(<?= $siteId ?>, <?= $page['id'] ?>)">
    
    <!-- Painel Lateral de Seleção de Blocos -->
    <div class="w-full md:w-80 flex-shrink-0 bg-white shadow rounded-lg p-0 flex flex-col overflow-hidden border border-gray-200">
        <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Selecione o Bloco</h3>
            <span x-show="isSaving" class="text-xs text-indigo-600 animate-pulse font-medium">Salvando...</span>
        </div>
        
        <div class="flex-1 overflow-y-auto p-2">
            <template x-for="block in blocks" :key="block.id">
                <button @click="selectBlock(block)" 
                        :class="{'bg-indigo-50 border-indigo-500': activeBlock && activeBlock.id === block.id, 'bg-white border-transparent hover:border-gray-300': !activeBlock || activeBlock.id !== block.id}"
                        class="w-full text-left p-3 mb-2 border rounded shadow-sm flex items-center justify-between group transition">
                    <div>
                        <span class="text-sm font-bold text-gray-700 block uppercase tracking-wider text-xs" x-text="block.type"></span>
                        <span class="text-xs text-gray-500 truncate block w-48" x-text="block.config.title || 'Sem título'"></span>
                    </div>
                    <svg class="h-5 w-5 text-gray-400 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </template>
            
            <div x-show="blocks.length === 0" class="text-center p-6 text-gray-400 text-sm">
                Nenhum bloco encontrado. Vá em "Editar Estrutura" para adicionar blocos.
            </div>
        </div>
    </div>

    <!-- Área de Edição do Bloco Ativo -->
    <div class="flex-1 bg-white shadow rounded-lg p-6 border border-gray-200">
        <div x-show="!activeBlock" class="h-full flex flex-col items-center justify-center text-gray-400">
            <svg class="h-16 w-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <p>Selecione um bloco na lista lateral para editar seu conteúdo.</p>
        </div>

        <div x-show="activeBlock" style="display: none;">
            <div class="border-b border-gray-200 pb-4 mb-6 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800 uppercase" x-text="'Editar Bloco: ' + activeBlock.type"></h3>
                <span class="text-sm text-gray-500">ID: <span x-text="activeBlock.id"></span></span>
            </div>

            <form @submit.prevent="saveContent" class="space-y-6">
                <!-- Campos Genéricos Baseados no Tipo -->
                
                <div x-show="activeBlock.type !== 'hero'">
                    <label class="block text-sm font-medium text-gray-700">Título Principal</label>
                    <input type="text" x-model="formData.title" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" placeholder="Título atraente...">
                </div>

                <div x-show="['header'].includes(activeBlock.type)">
                    <label class="block text-sm font-medium text-gray-700 mt-4">Logotipo</label>
                    <div class="mt-1 flex items-center">
                        <template x-if="formData.image">
                            <div class="relative max-h-20 mr-4 p-2 rounded border overflow-hidden flex-shrink-0 shadow-sm group bg-gray-50">
                                <img :src="formData.image" class="object-contain h-16">
                                <button type="button" @click="formData.image = ''" class="absolute inset-0 bg-black bg-opacity-60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-sm font-bold" title="Remover Logo">✕</button>
                            </div>
                        </template>
                        <input type="file" accept="image/*" @change="uploadImage($event, formData, 'image')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-gray-200 p-2 border-dashed bg-gray-50 transition">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Recomendado: PNG ou WebP com fundo transparente.</p>
                </div>

                <div x-show="['services', 'gallery', 'videos'].includes(activeBlock.type)">
                    <label class="block text-sm font-medium text-gray-700">Subtítulo / Descrição Curta</label>
                    <textarea x-model="formData.description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" placeholder="Um breve texto chamativo..."></textarea>
                </div>

                <div x-show="['about'].includes(activeBlock.type)">
                    <label class="block text-sm font-medium text-gray-700">Texto Completo (Sobre)</label>
                    <textarea x-model="formData.text" rows="5" maxlength="600" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" placeholder="Conte a sua história (max 600 caracteres)..."></textarea>
                    
                    <label class="block text-sm font-medium text-gray-700 mt-4">Imagem</label>
                    <div class="mt-1 flex items-center">
                        <template x-if="formData.image">
                            <div class="relative w-20 h-20 mr-4 rounded border overflow-hidden flex-shrink-0 shadow-sm group">
                                <img :src="formData.image" class="object-cover w-full h-full">
                                <button type="button" @click="formData.image = ''" class="absolute inset-0 bg-black bg-opacity-60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-sm font-bold" title="Remover Imagem">✕</button>
                            </div>
                        </template>
                        <input type="file" accept="image/*" @change="uploadImage($event, formData, 'image')" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-gray-200 p-2 border-dashed bg-gray-50 transition">
                    </div>
                </div>

                <div x-show="['contact'].includes(activeBlock.type)" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail de Recebimento</label>
                        <input type="email" x-model="formData.email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" placeholder="exemplo@suaempresa.com">
                        <p class="text-xs text-gray-400 mt-1">Este e-mail não será exibido no site. Ele será usado apenas para você receber as mensagens enviadas pelos visitantes no formulário.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Telefone de Contato</label>
                        <input type="text" x-model="formData.phone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" placeholder="(11) 99999-9999">
                        <p class="text-xs text-gray-400 mt-1">Este telefone será exibido no topo do site e no rodapé para facilitar o contato direto do cliente.</p>
                    </div>

                    <div class="flex items-start mt-2">
                        <div class="flex items-center h-5">
                            <input id="is_whatsapp" type="checkbox" x-model="formData.is_whatsapp" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_whatsapp" class="font-medium text-gray-700">É um número de WhatsApp?</label>
                            <p class="text-gray-500 text-xs">Se marcado, exibiremos o ícone do WhatsApp ao lado do número no site.</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 rounded-md border border-gray-200 mt-4">
                        <p class="text-xs text-gray-500 font-medium">✨ O formulário de contato do site já coletará automaticamente: Nome, E-mail, Telefone e Mensagem do visitante.</p>
                    </div>
                </div>

                <div x-show="['about', 'contact'].includes(activeBlock.type)">
                    <label class="block text-sm font-medium text-gray-700">Texto do Botão Base</label>
                    <input type="text" x-model="formData.button_text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" placeholder="Ex: Enviar Mensagem">
                </div>

                <div x-show="['about'].includes(activeBlock.type)">
                    <label class="block text-sm font-medium text-gray-700 mt-4">Link do Botão Base</label>
                    <input type="text" x-model="formData.button_link" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border" placeholder="Ex: https://google.com">
                </div>

                <!-- Gallery Section -->
                <div x-show="['gallery'].includes(activeBlock.type)">
                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-md font-bold text-gray-800">Fotos da Galeria (Máx. 9)</h4>
                            <span class="text-xs text-gray-500" x-text="(formData.gallery_images?.length || 0) + ' / 9 fotos'"></span>
                        </div>
                        
                        <div class="mb-4">
                            <label class="relative flex justify-center w-full h-32 px-4 transition bg-white border-2 border-gray-300 border-dashed rounded-md appearance-none cursor-pointer hover:border-indigo-500 hover:bg-indigo-50"
                                @dragover.prevent="$el.classList.add('border-indigo-500', 'bg-indigo-50')"
                                @dragleave.prevent="$el.classList.remove('border-indigo-500', 'bg-indigo-50')"
                                @drop.prevent="$el.classList.remove('border-indigo-500', 'bg-indigo-50'); uploadMultipleImages($event.dataTransfer.files)">
                                <span class="flex flex-col items-center justify-center space-y-2 pointer-events-none">
                                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <span class="font-medium text-gray-600">Arraste e solte até 9 fotos ou clique aqui</span>
                                </span>
                                <input type="file" multiple accept="image/*" @change="uploadMultipleImages($event.target.files); $event.target.value=''" class="hidden">
                            </label>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4" x-show="formData.gallery_images && formData.gallery_images.length > 0">
                            <template x-for="(img, index) in formData.gallery_images" :key="index">
                                <div class="relative group aspect-square rounded overflow-hidden border border-gray-200 bg-gray-50 shadow-sm">
                                    <img :src="img" class="object-cover w-full h-full">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                        <button type="button" @click="formData.gallery_images.splice(index, 1)" class="bg-red-500 hover:bg-red-600 text-white rounded-full p-2 leading-none" title="Remover Foto">
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Videos Section -->
                <div x-show="['videos'].includes(activeBlock.type)">
                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-md font-bold text-gray-800">Vídeos do YouTube</h4>
                            <button type="button" @click="if(!formData.videos) formData.videos = []; formData.videos.push({title: '', url: '', is_active: true})" class="bg-gray-100 text-gray-700 px-3 py-1 rounded shadow-sm text-sm font-medium hover:bg-gray-200 border border-gray-300 cursor-pointer">+ Adicionar Vídeo</button>
                        </div>
                        
                        <div class="space-y-4">
                            <template x-for="(vid, index) in formData.videos" :key="index">
                                <div class="bg-gray-50 border border-gray-200 rounded p-4 relative shadow-sm transition" :class="!vid.is_active ? 'opacity-60 grayscale' : ''">
                                    <button type="button" @click="formData.videos.splice(index, 1)" class="absolute top-2 right-2 flex items-center justify-center h-6 w-6 text-red-500 hover:text-red-700 hover:bg-red-50 rounded cursor-pointer" title="Remover Vídeo">✕</button>
                                    
                                    <div class="space-y-3 pt-2">
                                        <div class="flex items-center mb-2">
                                            <input type="checkbox" x-model="vid.is_active" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded mr-2 cursor-pointer">
                                            <label class="text-sm font-medium text-gray-700 cursor-pointer" @click="vid.is_active = !vid.is_active">Vídeo Ativo</label>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Título do Vídeo</label>
                                            <input type="text" x-model="vid.title" class="mt-1 block w-full border-gray-300 rounded-md p-2 border sm:text-sm bg-white" placeholder="Ex: Apresentação Institucional">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Link do YouTube</label>
                                            <input type="url" x-model="vid.url" class="mt-1 block w-full border-gray-300 rounded-md p-2 border sm:text-sm bg-white" placeholder="Ex: https://www.youtube.com/watch?v=XXXXXXX">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p x-show="!formData.videos || formData.videos.length === 0" class="text-sm text-gray-500 italic text-center py-4 bg-gray-50 border border-dashed border-gray-300 rounded">Nenhum vídeo adicionado.</p>
                    </div>
                </div>

                <!-- Itens Repetíveis para Blocos Específicos -->
                <div x-show="['hero', 'services', 'products', 'testimonials'].includes(activeBlock.type)">
                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-md font-bold text-gray-800">Itens deste Bloco (Cards/Slides)</h4>
                            <button type="button" @click="if(!formData.items) formData.items = []; formData.items.push({title: '', description: '', image: '', button_text: '', button_link: ''})" class="bg-gray-100 text-gray-700 px-3 py-1 rounded shadow-sm text-sm font-medium hover:bg-gray-200 border border-gray-300 cursor-pointer">+ Adicionar Item</button>
                        </div>
                        
                        <div class="space-y-4">
                            <template x-for="(item, index) in formData.items" :key="index">
                                <div class="bg-gray-50 border border-gray-200 rounded p-4 relative shadow-sm">
                                    <button type="button" @click="formData.items.splice(index, 1)" class="absolute top-2 right-2 flex items-center justify-center h-6 w-6 text-red-500 hover:text-red-700 hover:bg-red-50 rounded cursor-pointer" title="Remover Item">✕</button>
                                    
                                    <div class="space-y-3 pt-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Título</label>
                                            <input type="text" x-model="item.title" class="mt-1 block w-full border-gray-300 rounded-md p-2 border sm:text-sm bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Descrição (máx 200)</label>
                                            <textarea x-model="item.description" maxlength="200" rows="2" class="mt-1 block w-full border-gray-300 rounded-md p-2 border sm:text-sm bg-white"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Imagem</label>
                                            <div class="mt-1 flex items-center bg-white p-2 rounded border border-gray-200">
                                                <template x-if="item.image">
                                                    <div class="relative w-12 h-12 mr-3 rounded border overflow-hidden flex-shrink-0 group">
                                                        <img :src="item.image" class="object-cover w-full h-full">
                                                        <button type="button" @click="item.image = ''" class="absolute inset-0 bg-black bg-opacity-60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold" title="Remover Imagem">✕</button>
                                                    </div>
                                                </template>
                                                <input type="file" accept="image/*" @change="uploadImage($event, item, 'image')" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">Botão (Texto)</label>
                                                <input type="text" x-model="item.button_text" class="mt-1 block w-full border-gray-300 rounded-md p-2 border sm:text-sm bg-white">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">Botão (Link)</label>
                                                <input type="text" x-model="item.button_link" class="mt-1 block w-full border-gray-300 rounded-md p-2 border sm:text-sm bg-white">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p x-show="!formData.items || formData.items.length === 0" class="text-sm text-gray-500 italic text-center py-4 bg-gray-50 border border-dashed border-gray-300 rounded">Nenhum item adicionado.</p>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-200 mt-6 flex justify-end">
                    <button type="submit" :disabled="isSaving" class="bg-indigo-600 text-white px-6 py-2 rounded shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-bold disabled:opacity-50">
                        <span x-show="!isSaving">Salvar Conteúdo</span>
                        <span x-show="isSaving">Salvando...</span>
                    </button>
                    
                    <a href="<?= BASE_URL ?>/<?= $site['slug'] ?>?preview=true" target="_blank" class="ml-3 bg-white text-gray-700 border border-gray-300 px-6 py-2 rounded shadow-sm hover:bg-gray-50 focus:outline-none font-medium">
                        Ver Preview ↗
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('contentApp', (siteId, pageId) => ({
        siteId: siteId,
        pageId: pageId,
        blocks: [],
        activeBlock: null,
        formData: {},
        isSaving: false,

        init() {
            this.fetchBlocks();
        },

        async fetchBlocks() {
            try {
                const res = await fetch(`<?= BASE_URL ?>/api/blocks?page_id=${this.pageId}`);
                if (!res.ok) throw new Error('Falha ao carregar blocos');
                const json = await res.json();
                this.blocks = json.data || [];
                
                // Se o usuário veio pelo botão Editar Conteúdo no modal de Estrutura
                const urlParams = new URLSearchParams(window.location.search);
                const blockType = urlParams.get('block_type');
                if (blockType) {
                    const found = this.blocks.find(b => b.type === blockType && b.config?.is_active !== false);
                    if (found) {
                        this.selectBlock(found);
                    }
                }
            } catch (e) {
                alert(e.message);
            }
        },

        selectBlock(block) {
            this.activeBlock = block;
            // Clona as configurações para o formData para não alterar o state original antes de salvar
            this.formData = {
                title: block.config.title || '',
                description: block.config.description || '',
                text: block.config.text || '',
                image: block.config.image || '',
                button_text: block.config.button_text || '',
                button_link: block.config.button_link || '',
                items: JSON.parse(JSON.stringify(block.config.items || [])),
                gallery_images: JSON.parse(JSON.stringify(block.config.gallery_images || [])),
                videos: JSON.parse(JSON.stringify(block.config.videos || []))
            };
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async saveContent() {
            if (!this.activeBlock) return;
            this.isSaving = true;
            
            // Mantém outras props existentes no config original e mescla com formData
            const updatedConfig = { 
                ...this.activeBlock.config, 
                ...this.formData 
            };

            try {
                const res = await fetch(`<?= BASE_URL ?>/api/blocks`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        block_id: this.activeBlock.id, 
                        config: updatedConfig 
                    })
                });
                
                if (res.ok) {
                    // Atualiza localmente
                    this.activeBlock.config = updatedConfig;
                    const idx = this.blocks.findIndex(b => b.id === this.activeBlock.id);
                    if (idx > -1) this.blocks[idx].config = updatedConfig;
                    
                    // Mostra um toast ou algo
                    alert("Conteúdo salvo com sucesso!");
                } else {
                    alert("Erro ao salvar.");
                }
            } catch (e) {
                console.error("Erro ao salvar", e);
                alert("Falha na comunicação.");
            }
            this.isSaving = false;
        },

        async uploadImage(event, targetObj, propName) {
            const file = event.target.files[0];
            if (!file) return;
            
            this.isSaving = true; // Mostra aviso "Salvando..."
            const fd = new FormData();
            fd.append('image', file);
            
            try {
                const res = await fetch(`<?= BASE_URL ?>/api/endpoints/upload.php`, {
                    method: 'POST',
                    body: fd
                });
                
                const json = await res.json();
                if (res.ok && json.success) {
                    targetObj[propName] = json.url;
                } else {
                    alert(json.error || "Erro ao fazer upload da imagem.");
                }
            } catch (e) {
                console.error(e);
                alert("Falha de rede ao tentar fazer upload.");
            }
            
            // Limpa o input file caso o usuário queria enviar o mesmo arquivo logo após remover
            event.target.value = '';
            this.isSaving = false;
        },

        async uploadMultipleImages(files) {
            if (!files || files.length === 0) return;
            if (!this.formData.gallery_images) this.formData.gallery_images = [];
            
            const remainingSlots = 9 - this.formData.gallery_images.length;
            const filesToUpload = Array.from(files).slice(0, remainingSlots);
            
            if (filesToUpload.length === 0) {
                alert('Limite máximo de 9 fotos atingido.');
                return;
            }
            if (files.length > remainingSlots) {
                alert(`Apenas ${remainingSlots} foto(s) foram selecionadas para respeitar o limite de 9.`);
            }

            this.isSaving = true;
            
            for (let file of filesToUpload) {
                if (!file.type.startsWith('image/')) continue;
                
                const fd = new FormData();
                fd.append('image', file);
                try {
                    const res = await fetch(`<?= BASE_URL ?>/api/endpoints/upload.php`, {
                        method: 'POST',
                        body: fd
                    });
                    const json = await res.json();
                    if (res.ok && json.success) {
                        this.formData.gallery_images.push(json.url);
                    } else {
                        alert(`Erro foto ${file.name}: ${json.error || 'Falha no upload'}`);
                    }
                } catch (e) {
                    console.error('Falha ao processar arquivo', e);
                }
            }
            
            this.isSaving = false;
        }
    }));
});
</script>

<?php render_dashboard_footer(); ?>
