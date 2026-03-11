<?php
// Dashboard Principal - Tela Inicial
require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();

// Handle POST para Design (O modal global pode enviar para cá de qualquer aba)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_design') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Token inválido ou sessão expirada.";
    } else {
        $siteId = $_POST['site_id'] ?? null;
        $primaryColor = $_POST['primary_color'] ?? '#4f46e5';
        $titleFont = $_POST['title_font'] ?? 'Inter';
        $textFont = $_POST['text_font'] ?? 'Inter';
        $buttonStyle = $_POST['button_style'] ?? 'rounded';
        $redirectTo = $_POST['redirect_to'] ?? '/dashboard';

        $checkSite = db_fetch_one("SELECT id FROM sites WHERE id = :id AND user_id = :uid", [
            ':id' => $siteId, 
            ':uid' => $user['id']
        ]);
        
        if ($checkSite) {
            $designJson = json_encode([
                'primary_color' => $primaryColor,
                'title_font' => $titleFont,
                'text_font' => $textFont,
                'button_style' => $buttonStyle
            ]);
            db_update('sites', ['design' => $designJson], 'id = :id', [':id' => $siteId]);
            $_SESSION['flash_success'] = "Design atualizado com sucesso!";
            
            // Corrige possível duplicação de diretório base
            $cleanRedirect = str_replace(BASE_URL, '', $redirectTo);
            redirect($cleanRedirect ?: '/dashboard');
        }
    }
}

// Para a tela inicial vazia precisaremos saber apenas se o usuário tem algum site ativo
$sitesCount = db_fetch_one("SELECT COUNT(id) as total FROM sites WHERE user_id = :uid AND status != 'inactive'", [':uid' => $user['id']]);
$totalSites = $sitesCount['total'] ?? 0;

render_dashboard_header("Início");
?>

    <?php if ($currentSite): ?>
        
        <!-- Dashboard do Site Selecionado (Mocks) -->
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6 text-left">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Visão Geral: <?= htmlspecialchars($currentSite['domain'] ?: $currentSite['slug']) ?></h2>
            
            <!-- KPIs Topo -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500">Visitantes Hoje</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900">142</p>
                        <p class="text-sm font-medium text-green-600 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg> 12%</p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500">Acessos no Mês</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900">4.891</p>
                        <p class="text-sm font-medium text-green-600 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg> 5.4%</p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500">Contatos Recebidos</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900">23</p>
                        <p class="text-sm font-medium text-red-600 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg> 2%</p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500">Tempo Médio</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900">1m 45s</p>
                    </div>
                </div>
            </div>

            <!-- Primeira Linha (70 / 30) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Gráfico de Acessos -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-base font-bold text-gray-900">Acessos do Mês</h3>
                        <select class="text-sm border-gray-300 rounded-md shadow-sm opacity-70"><option>Últimos 30 dias</option></select>
                    </div>
                    <!-- Mock do Gráfico -->
                    <div class="flex-1 flex items-end justify-between gap-2 h-48 border-b border-gray-100 pb-2 relative">
                        <!-- Linhas Guia -->
                        <div class="absolute inset-0 flex flex-col justify-between pt-2 pb-0">
                            <div class="border-t border-gray-100 w-full h-0"></div>
                            <div class="border-t border-gray-100 w-full h-0"></div>
                            <div class="border-t border-gray-100 w-full h-0"></div>
                        </div>
                        <?php 
                        // Bar char mock
                        $heights = [30, 45, 25, 60, 80, 50, 40, 75, 90, 65, 55, 30, 20, 40, 85];
                        foreach ($heights as $idx => $h): ?>
                            <div class="w-full bg-indigo-100 hover:bg-indigo-300 transition-colors rounded-t cursor-pointer relative z-10 group" style="height: <?= $h ?>%;">
                                <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs py-1 px-2 rounded pointer-events-none"><?= $h * 15 ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-2">
                        <span>01 Mar</span>
                        <span>15 Mar</span>
                        <span>30 Mar</span>
                    </div>
                </div>

                <!-- Notificações -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-5 h-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        Avisos e Dicas
                    </h3>
                    <div class="space-y-4">
                        <div class="bg-indigo-50 rounded-lg p-3 text-sm border border-indigo-100">
                            <span class="font-bold text-indigo-800 block mb-1">Dica de SEO</span>
                            <span class="text-indigo-600 block leading-tight">Melhore seu "Sobre Nós" adicionando 2 imagens novas.</span>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-3 text-sm border border-orange-100">
                            <span class="font-bold text-orange-800 block mb-1">Configuração Incompleta</span>
                            <span class="text-orange-600 block leading-tight">Seu botão de WhatsApp no rodapé está sem número vinculado.</span>
                        </div>
                        <div class="flex items-center text-sm text-gray-500 py-2">
                            <span class="w-2 h-2 bg-gray-300 rounded-full mr-3"></span>
                            Visita de bot do Google identificada ontem às 14h.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segunda Linha (33 / 33 / 33) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Últimos Contatos -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 col-span-1 md:col-span-1">
                    <div class="flex justify-between items-center mb-5">
                        <h3 class="text-base font-bold text-gray-900">Últimos Contatos</h3>
                        <a href="#" class="text-xs text-indigo-600 hover:underline">Ver Tabela Completa</a>
                    </div>
                    <ul class="divide-y divide-gray-100 space-y-3 pt-2">
                        <?php 
                        $mockLeads = [
                            ['nome' => 'Mariana Silva', 'status' => 'Novo', 'tempo' => '2 horas atrás'],
                            ['nome' => 'João Nogueira', 'status' => 'Lido', 'tempo' => 'ontem'],
                            ['nome' => 'Pedro Álvares', 'status' => 'Lido', 'tempo' => 'ontem'],
                            ['nome' => 'Tech Solutions', 'status' => 'Novo', 'tempo' => 'há 2 dias'],
                            ['nome' => 'Carlos B.', 'status' => 'Lido', 'tempo' => 'há 3 dias']
                        ];
                        foreach($mockLeads as $lead): ?>
                            <li class="flex items-center justify-between pointer-events-none">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 font-bold text-xs uppercase">
                                        <?= substr($lead['nome'], 0, 1) ?>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900"><?= $lead['nome'] ?></p>
                                        <p class="text-xs text-gray-400"><?= $lead['tempo'] ?></p>
                                    </div>
                                </div>
                                <div>
                                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded-full <?= $lead['status'] == 'Novo' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' ?>"><?= $lead['status'] ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Origem do Tráfego -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col">
                    <h3 class="text-base font-bold text-gray-900 mb-6">Origem do Tráfego</h3>
                    <div class="space-y-5 flex-1 justify-center flex flex-col">
                        
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">Google Orgânico</span>
                                <span class="text-gray-500">65%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: 65%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">Tráfego Direto (Link)</span>
                                <span class="text-gray-500">20%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: 20%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">Redes Sociais (Insta)</span>
                                <span class="text-gray-500">12%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-pink-500 h-2 rounded-full" style="width: 12%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">Outros Referenciadores</span>
                                <span class="text-gray-500">3%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-gray-400 h-2 rounded-full" style="width: 3%"></div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Gráfico de Dispositivos -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col items-center text-center">
                    <h3 class="text-base font-bold text-gray-900 mb-6 w-full text-left">Dispositivos</h3>
                    
                    <!-- Mock de Grafico de Pizza simples via CSS -->
                    <div class="w-40 h-40 rounded-full relative shadow-inner my-auto" 
                         style="background: conic-gradient(from 0deg, 
                                  #4f46e5 0% 75%, 
                                  #10b981 75% 95%, 
                                  #f3f4f6 95% 100%);">
                        <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center flex-col">
                            <span class="text-xl font-bold text-gray-900">75%</span>
                            <span class="text-xs text-gray-500">Mobile</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-center flex-wrap gap-4 w-full mt-6 text-sm">
                        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-indigo-600 mr-2"></span>Celular (75%)</div>
                        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>Desktop (20%)</div>
                        <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-gray-100 mr-2"></span>Tablet (5%)</div>
                    </div>
                </div>

            </div>
        </div>

    <?php elseif ($totalSites > 0): ?>
        <div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-6">
            <svg class="h-20 w-20 text-indigo-200 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Bem-vindo ao <?= APP_NAME ?> painel</h2>
            <p class="text-lg text-gray-500 max-w-xl mx-auto">
                Por favor, selecione um dos seus sites no menu superior <strong>"Meus Sites"</strong> para visualizar suas métricas ou acessar as ferramentas de edição.
            </p>
        </div>
    <?php else: ?>
        <svg class="h-20 w-20 text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
        </svg>
        <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Nenhum site encontrado</h2>
        <p class="text-lg text-gray-500 max-w-xl mx-auto mb-8">
            Você ainda não criou nenhum site. Comece agora a construir a sua presença digital de forma rápida e fácil!
        </p>
        <a href="<?= BASE_URL ?>/dashboard/create_site" class="inline-flex items-center px-8 py-3 border border-transparent shadow text-base font-bold rounded-full text-white bg-indigo-600 hover:bg-indigo-700 transition">
            Criar Primeiro Site
        </a>
    <?php endif; ?>
</div>

<?php render_dashboard_footer(); ?>
