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
        <?php
        $sqlToday = "SELECT COUNT(DISTINCT visitor_ip) as total FROM site_analytics WHERE site_id = :sid AND DATE(created_at) = CURDATE()";
        $sqlMonth = "SELECT COUNT(id) as total FROM site_analytics WHERE site_id = :sid AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        
        $sqlContacts = "SELECT COUNT(id) as total FROM site_contacts WHERE site_id = :sid";
        $sqlNewContacts = "SELECT COUNT(id) as total FROM site_contacts WHERE site_id = :sid AND status = 'new'";
        
        // Evita quebrar a dash caso a migration não tenha rodado
        $visitsToday = 0;
        $visitsMonth = 0;
        $totalContacts = 0;
        $newContacts = 0;
        $devices = ['Mobile' => 0, 'Desktop' => 0];
        $referrers = [];
        $dailyVisits = array_fill(1, 31, 0); // Para o gráfico, dias de 1 a 31
        
        try {
            $visitsToday = db_fetch_one($sqlToday, [':sid' => $currentSite['id']])['total'] ?? 0;
            $visitsMonth = db_fetch_one($sqlMonth, [':sid' => $currentSite['id']])['total'] ?? 0;
            $totalContacts = db_fetch_one($sqlContacts, [':sid' => $currentSite['id']])['total'] ?? 0;
            $newContacts = db_fetch_one($sqlNewContacts, [':sid' => $currentSite['id']])['total'] ?? 0;
            
            // Dispositivos do mês
            $devQuery = db_fetch_all("SELECT device_type, COUNT(*) as qtd FROM site_analytics WHERE site_id = :sid AND MONTH(created_at) = MONTH(CURDATE()) GROUP BY device_type", [':sid' => $currentSite['id']]);
            foreach ($devQuery as $dq) {
                if(isset($devices[$dq['device_type']])) {
                    $devices[$dq['device_type']] = $dq['qtd'];
                }
            }
            
            // Origem (Top 4)
            $refQuery = db_fetch_all("
                SELECT 
                    CASE 
                        WHEN referrer_url LIKE '%google%' THEN 'Google Orgânico'
                        WHEN referrer_url LIKE '%instagram.com%' THEN 'Instagram'
                        WHEN referrer_url LIKE '%facebook.com%' THEN 'Facebook'
                        WHEN referrer_url = '' OR referrer_url IS NULL THEN 'Tráfego Direto'
                        ELSE 'Outros'
                    END as source,
                    COUNT(*) as qtd
                FROM site_analytics 
                WHERE site_id = :sid AND MONTH(created_at) = MONTH(CURDATE())
                GROUP BY source
                ORDER BY qtd DESC
                LIMIT 4
            ", [':sid' => $currentSite['id']]);
            
            foreach ($refQuery as $rq) {
                $referrers[$rq['source']] = $rq['qtd'];
            }
            
            // Chart Data (Dias do Mês atual)
            $chartQuery = db_fetch_all("
                SELECT DAY(created_at) as dia, COUNT(*) as qtd 
                FROM site_analytics 
                WHERE site_id = :sid AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
                GROUP BY DAY(created_at)
            ", [':sid' => $currentSite['id']]);
            
            foreach ($chartQuery as $cq) {
                $dailyVisits[$cq['dia']] = $cq['qtd'];
            }
            
        } catch (\PDOException $e) {
            // Se der erro (ex: tabela não existe), os zeros são mantidos
        }
        
        // O maximo do grafico pro calculo do %. Se vazio, bota 1 pra n quebrar divisão.
        $maxDaily = max($dailyVisits);
        $maxDaily = $maxDaily > 0 ? $maxDaily : 1;
        
        // Percentual para o Grafico de Pizza
        $totalDevices = array_sum($devices);
        if ($totalDevices == 0) $totalDevices = 1; // evitar divisão por 0
        $pctMobile = round(($devices['Mobile'] / $totalDevices) * 100);
        $pctDesktop = round(($devices['Desktop'] / $totalDevices) * 100);
        ?>
        
        <!-- Dashboard do Site Selecionado (Mocks) -->
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6 text-left">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Visão Geral: <?= htmlspecialchars($currentSite['domain'] ?: $currentSite['slug']) ?></h2>
            
            <!-- KPIs Topo -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500">Visitantes Hoje</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($visitsToday, 0, ',', '.') ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500">Acessos no Mês</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($visitsMonth, 0, ',', '.') ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500">Contatos Recebidos</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($totalContacts, 0, ',', '.') ?></p>
                        <?php if ($newContacts > 0): ?>
                            <p class="text-xs text-indigo-600 font-bold bg-indigo-50 px-2 py-1 rounded-full"><?= $newContacts ?> novos</p>
                        <?php else: ?>
                            <p class="text-xs text-gray-400 font-medium">--</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <p class="text-sm font-medium text-gray-500">Tempo Médio</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-gray-900">N/A</p>
                    </div>
                </div>
            </div>

            <!-- Primeira Linha (70 / 30) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Gráfico de Acessos -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-base font-bold text-gray-900">Acessos do Mês Atual</h3>
                        <span class="text-sm text-gray-500 text-right opacity-70">Gráfico Diário</span>
                    </div>
                    <!-- Mock do Gráfico -->
                    <div class="flex-1 flex items-end justify-between gap-[2px] h-48 border-b border-gray-100 pb-2 relative">
                        <!-- Linhas Guia -->
                        <div class="absolute inset-0 flex flex-col justify-between pt-2 pb-0 opacity-50 pointer-events-none z-0">
                            <div class="border-t border-gray-100 w-full h-0"></div>
                            <div class="border-t border-gray-100 w-full h-0"></div>
                            <div class="border-t border-gray-100 w-full h-0"></div>
                            <div class="border-t border-gray-100 w-full h-0"></div>
                        </div>
                        <?php 
                        // Bar char real: os 31 dias possíveis
                        for ($d = 1; $d <= date('t'); $d++): 
                            $v_qtd = $dailyVisits[$d];
                            $h = ($v_qtd / $maxDaily) * 100;
                            // Previne barra muito minúscula visualmente
                            if ($v_qtd > 0 && $h < 5) $h = 5;
                        ?>
                            <div class="w-full bg-indigo-100 hover:bg-indigo-400 transition-colors rounded-t cursor-pointer relative z-10 group" style="height: <?= $h ?>%;">
                                <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs py-1 px-2 rounded pointer-events-none"><?= $v_qtd ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-2 font-medium">
                        <span>Dia 1</span>
                        <span>Dia 15</span>
                        <span>Fim do Mês</span>
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
                            <span class="font-bold text-indigo-800 block mb-1">Estatísticas Reais Ativadas</span>
                            <span class="text-indigo-600 block leading-tight">O painel agora está acompanhando o tráfego do seu site em tempo real!</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segunda Linha (33 / 33 / 33) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Últimos Contatos -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 col-span-1 md:col-span-1 border-opacity-50">
                    <div class="flex justify-between items-center mb-5">
                        <h3 class="text-base font-bold text-gray-900">Últimos Contatos</h3>
                        <a href="<?= BASE_URL ?>/dashboard/contacts?site_id=<?= $currentSite['id'] ?>" class="text-xs text-indigo-600 hover:underline">Ver Tabela</a>
                    </div>
                    <?php 
                    $recentContacts = [];
                    try {
                        $recentContacts = db_fetch_all("SELECT name, status, created_at FROM site_contacts WHERE site_id = :sid ORDER BY created_at DESC LIMIT 5", [':sid' => $currentSite['id']]);
                    } catch(\PDOException $e) {}
                    
                    if (empty($recentContacts)): 
                    ?>
                    <div class="pt-6 pb-6 text-center">
                        <svg class="h-10 w-10 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        <p class="text-sm text-gray-400">Nenhum formulário de contato preenchido ainda.</p>
                    </div>
                    <?php else: ?>
                    <ul class="divide-y divide-gray-100 space-y-3 pt-2">
                        <?php foreach($recentContacts as $lead): 
                            $dateStr = date('d/m H:i', strtotime($lead['created_at']));
                        ?>
                            <li class="flex items-center justify-between pointer-events-none">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 font-bold text-xs uppercase">
                                        <?= substr($lead['name'], 0, 1) ?>
                                    </div>
                                    <div class="ml-3 truncate max-w-[120px]">
                                        <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($lead['name']) ?></p>
                                        <p class="text-xs text-gray-400 truncate"><?= $dateStr ?></p>
                                    </div>
                                </div>
                                <div>
                                    <span class="px-2 py-1 text-[10px] font-bold uppercase rounded-full <?= $lead['status'] === 'new' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' ?>"><?= $lead['status'] === 'new' ? 'Novo' : 'Lido' ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <!-- Origem do Tráfego -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col">
                    <h3 class="text-base font-bold text-gray-900 mb-6">Origem do Tráfego (Mês)</h3>
                    <div class="space-y-5 flex-1 justify-center flex flex-col">
                        <?php if (empty($referrers)): ?>
                            <p class="text-sm text-gray-400 text-center">Dados insuficientes.</p>
                        <?php else: 
                            $colors = ['bg-indigo-600', 'bg-green-500', 'bg-pink-500', 'bg-orange-400'];
                            $i = 0;
                            foreach($referrers as $sourceName => $sourceQtd):
                                $srcPct = round(($sourceQtd / $visitsMonth) * 100);
                                $colorClass = $colors[$i % count($colors)];
                        ?>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700"><?= $sourceName ?></span>
                                    <span class="text-gray-500"><?= $srcPct ?>%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="<?= $colorClass ?> h-2 rounded-full" style="width: <?= $srcPct ?>%"></div>
                                </div>
                            </div>
                        <?php $i++; endforeach; endif; ?>
                    </div>
                </div>

                <!-- Gráfico de Dispositivos -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col items-center text-center">
                    <h3 class="text-base font-bold text-gray-900 mb-6 w-full text-left">Dispositivos (Mês)</h3>
                    
                    <?php if ($visitsMonth > 0): ?>
                        <!-- Grafico de Pizza simples via CSS -->
                        <div class="w-40 h-40 rounded-full relative shadow-inner my-auto" 
                             style="background: conic-gradient(from 0deg, 
                                      #4f46e5 0% <?= $pctMobile ?>%, 
                                      #10b981 <?= $pctMobile ?>% <?= $pctMobile + $pctDesktop ?>%, 
                                      #f3f4f6 <?= $pctMobile + $pctDesktop ?>% 100%);">
                            <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center flex-col shadow-sm">
                                <span class="text-xl font-bold text-gray-900 text-indigo-600"><?= max($pctMobile, $pctDesktop) ?>%</span>
                                <span class="text-[10px] text-gray-500 uppercase tracking-tighter"><?= $pctMobile >= $pctDesktop ? 'Mobile' : 'Desktop' ?></span>
                            </div>
                        </div>
                        
                        <div class="flex justify-center flex-wrap gap-4 w-full mt-6 text-sm">
                            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-indigo-600 mr-2 shadow-sm"></span>Celular (<?= $pctMobile ?>%)</div>
                            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-green-500 mr-2 shadow-sm"></span>Desktop (<?= $pctDesktop ?>%)</div>
                        </div>
                    <?php else: ?>
                        <div class="w-40 h-40 rounded-full bg-gray-50 border-4 border-gray-100 flex items-center justify-center relative my-auto">
                            <span class="text-sm text-gray-400">Sem dados</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php elseif ($totalSites > 0): ?>    <?php elseif ($totalSites > 0): ?>
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
