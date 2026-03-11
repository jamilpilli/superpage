<?php
// Página Home Pública

$pageTitle = APP_NAME . " - O Construtor de Sites Institucionais Premium";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .text-gradient {
            background: linear-gradient(to right, #60a5fa, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-bg {
            background-image: url('<?= BASE_URL ?>/assets/img/hero.png');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-[#0f172a] text-white selection:bg-purple-500/30">
    
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 border-b border-white/10 bg-[#0f172a]/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <span class="text-white font-bold text-xl">S</span>
                    </div>
                    <span class="text-2xl font-extrabold tracking-tight text-white"><?= APP_NAME ?></span>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-sm font-medium text-slate-400 hover:text-white transition">Funcionalidades</a>
                    <a href="#how-it-works" class="text-sm font-medium text-slate-400 hover:text-white transition">Como Funciona</a>
                    <a href="#pricing" class="text-sm font-medium text-slate-400 hover:text-white transition">Planos</a>
                </div>

                <div class="flex items-center gap-4">
                    <?php if (is_logged_in()): ?>
                        <a href="<?= BASE_URL ?>/dashboard" class="text-sm font-medium text-slate-400 hover:text-white">Dashboard</a>
                        <a href="<?= BASE_URL ?>/auth/logout" class="px-5 py-2.5 rounded-full bg-red-500/10 text-red-400 text-sm font-bold border border-red-500/20 hover:bg-red-500/20 transition">Sair</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/auth/login" class="text-sm font-medium text-slate-400 hover:text-white transition">Entrar</a>
                        <a href="<?= BASE_URL ?>/auth/register" class="px-6 py-2.5 rounded-full bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold shadow-lg shadow-blue-600/20 transition-all hover:scale-105 active:scale-95">Experimentar Grátis</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_50%_50%,rgba(37,99,235,0.1),transparent_50%)]"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold mb-6 animate-pulse">
                    <span>✨</span> O 1º ano é totalmente grátis para o seu primeiro site!
                </div>
                <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight leading-tight mb-8">
                    Sites profissionais em <span class="text-gradient">velocidade recorde</span>
                </h1>
                <p class="text-lg md:text-xl text-slate-400 mb-12 leading-relaxed max-w-2xl mx-auto">
                    A plataforma definitiva para criar landing pages e sites institucionais OnePage que convertem visitantes em clientes. Sem código, sem complicações.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="<?= BASE_URL ?>/auth/register" class="w-full sm:w-auto px-10 py-4 rounded-2xl bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold text-lg shadow-2xl shadow-blue-600/30 hover:shadow-blue-600/50 transition-all hover:-translate-y-1">
                        Começar Agora — Grátis
                    </a>
                    <a href="#features" class="w-full sm:w-auto px-10 py-4 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-lg hover:bg-white/10 transition">
                        Ver Recursos
                    </a>
                </div>
            </div>

            <!-- Preview/Visual -->
            <div class="mt-20 relative px-4">
                <div class="glass rounded-3xl p-4 shadow-2xl relative overflow-hidden group">
                    <img src="<?= BASE_URL ?>/assets/img/hero.png" alt="Preview" class="rounded-2xl shadow-inner border border-white/5 opacity-80 group-hover:opacity-100 transition-opacity">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] via-transparent to-transparent"></div>
                </div>
                
                <!-- Floating Elements -->
                <div class="absolute -top-10 -right-10 hidden lg:block animate-bounce" style="animation-duration: 4s;">
                    <div class="glass p-4 rounded-2xl border-blue-500/30 shadow-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-300">Performance</div>
                                <div class="text-lg font-black text-white">99/100</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Features Grid -->
    <section id="features" class="py-24 bg-[#0a101f]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20">
                <h2 class="text-3xl md:text-5xl font-bold mb-4">Tudo o que você precisa</h2>
                <div class="w-20 h-1.5 bg-blue-600 mx-auto rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Blocks Feature -->
                <div class="glass p-8 rounded-3xl border-transparent hover:border-blue-500/30 transition-all hover:-translate-y-2 group">
                    <div class="w-14 h-14 bg-blue-600/20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 transition-colors">
                        <svg class="w-8 h-8 text-blue-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Construção por Blocos</h3>
                    <p class="text-slate-400">Monte seu site arrastando e soltando blocos predefinidos: Hero, Sobre, Produtos, Serviços, Galeria e muito mais.</p>
                </div>

                <!-- Marketplace Feature -->
                <div class="glass p-8 rounded-3xl border-transparent hover:border-purple-500/30 transition-all hover:-translate-y-2 group">
                    <div class="w-14 h-14 bg-purple-600/20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 transition-colors">
                        <svg class="w-8 h-8 text-purple-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Marketplace de Temas</h3>
                    <p class="text-slate-400">Adicione funcionalidades extras com plugins ou escolha temas exclusivos para dar um up no visual do seu site.</p>
                </div>

                <!-- Speed Feature -->
                <div class="glass p-8 rounded-3xl border-transparent hover:border-green-500/30 transition-all hover:-translate-y-2 group">
                    <div class="w-14 h-14 bg-green-600/20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-green-600 transition-colors">
                        <svg class="w-8 h-8 text-green-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Performance Extrema</h3>
                    <p class="text-slate-400">Otimização automática de imagens (WebP) e carregamento ultrarrápido para ranquear melhor no Google.</p>
                </div>

                <!-- Domain Feature -->
                <div class="glass p-8 rounded-3xl border-transparent hover:border-orange-500/30 transition-all hover:-translate-y-2 group">
                    <div class="w-14 h-14 bg-orange-600/20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-orange-600 transition-colors">
                        <svg class="w-8 h-8 text-orange-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Domínio Customizado</h3>
                    <p class="text-slate-400">Use seu próprio domínio (ex: www.suaempresa.com) com SSL gratuito e configuração simplificada.</p>
                </div>

                <!-- Partner Feature -->
                <div class="glass p-8 rounded-3xl border-transparent hover:border-cyan-500/30 transition-all hover:-translate-y-2 group">
                    <div class="w-14 h-14 bg-cyan-600/20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-cyan-600 transition-colors">
                        <svg class="w-8 h-8 text-cyan-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Programa de Parceiros</h3>
                    <p class="text-slate-400">Agências e freelancers podem gerenciar múltiplos sites de clientes em uma única conta de forma profissional.</p>
                </div>

                <!-- Multi-Site Feature -->
                <div class="glass p-8 rounded-3xl border-transparent hover:border-pink-500/30 transition-all hover:-translate-y-2 group">
                    <div class="w-14 h-14 bg-pink-600/20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-pink-600 transition-colors">
                        <svg class="w-8 h-8 text-pink-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Múltiplos Sites</h3>
                    <p class="text-slate-400">Crie quantos sites precisar. Gerenciamento centralizado com um painel intuitivo e poderoso.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section id="how-it-works" class="py-24 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <div class="flex-1">
                    <h2 class="text-4xl md:text-5xl font-extrabold mb-8 leading-tight">
                        Seu site no ar em <span class="text-gradient">3 passos simples</span>
                    </h2>
                    
                    <div class="space-y-8">
                        <div class="flex gap-6">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center font-bold text-xl">1</div>
                            <div>
                                <h4 class="text-xl font-bold mb-2">Configure sua conta</h4>
                                <p class="text-slate-400">Crie sua conta em segundos e dê o primeiro passo para sua presença digital.</p>
                            </div>
                        </div>
                        <div class="flex gap-6">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-600 flex items-center justify-center font-bold text-xl">2</div>
                            <div>
                                <h4 class="text-xl font-bold mb-2">Escolha seus blocos</h4>
                                <p class="text-slate-400">Adicione as seções que seu negócio precisa e personalize o conteúdo conforme sua marca.</p>
                            </div>
                        </div>
                        <div class="flex gap-6">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-600 flex items-center justify-center font-bold text-xl">3</div>
                            <div>
                                <h4 class="text-xl font-bold mb-2">Publique e cresça</h4>
                                <p class="text-slate-400">Publique instantaneamente com um clique e comece a receber seus primeiros visitantes.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-12">
                        <a href="<?= BASE_URL ?>/auth/register" class="inline-block px-10 py-4 rounded-2xl bg-blue-600 hover:bg-blue-500 font-bold text-white transition shadow-lg shadow-blue-600/20">
                            Quero criar meu site grátis
                        </a>
                    </div>
                </div>
                
                <div class="flex-1 relative">
                    <div class="absolute inset-0 bg-blue-600/20 blur-[100px] -z-10 animate-pulse"></div>
                    <div class="glass p-4 rounded-[2rem] shadow-2xl rotate-2">
                        <img src="<?= BASE_URL ?>/assets/img/builder.png" alt="Builder Illustration" class="rounded-[1.5rem]">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="pricing" class="py-24 bg-gradient-to-br from-blue-600/20 to-purple-600/20 border-y border-white/5">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-8 leading-tight">
                Prepare-se para transformar seu <span class="text-gradient">negócio hoje mesmo</span>
            </h2>
            <p class="text-xl text-slate-300 mb-12">
                Aproveite nossa oferta de lançamento: O primeiro ano é totalmente por nossa conta para o seu primeiro site!
            </p>
            
            <div class="glass p-1 rounded-2xl max-w-sm mx-auto mb-12 flex">
                <div class="flex-1 py-3 bg-blue-600 rounded-xl font-bold text-white">Pagamento Anual</div>
                <div class="flex-1 py-3 text-slate-400 font-bold">Grátis (1º Ano)</div>
            </div>

            <div class="inline-flex flex-col items-center">
                <a href="<?= BASE_URL ?>/auth/register" class="px-12 py-5 rounded-2xl bg-white text-[#0f172a] font-black text-xl hover:bg-slate-200 transition-all hover:scale-105 shadow-2xl shadow-white/10">
                    Começar Agora sem pagar nada
                </a>
                <span class="mt-4 text-sm text-slate-400 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1.414 1.414L9 10.586 7.707 9.293a1.414 1.414l2 2a1.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    Sem cartão de crédito necessário
                </span>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-20 border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-20">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">S</span>
                        </div>
                        <span class="text-2xl font-bold text-white"><?= APP_NAME ?></span>
                    </div>
                    <p class="text-slate-400 max-w-sm">
                        A plataforma completa para profissionais e empresas que buscam alta performance e conversão em seus sites institucionais.
                    </p>
                </div>
                <div>
                    <h5 class="font-bold text-white mb-6">Produto</h5>
                    <ul class="space-y-4 text-slate-400">
                        <li><a href="#features" class="hover:text-white transition">Funcionalidades</a></li>
                        <li><a href="#pricing" class="hover:text-white transition">Planos</a></li>
                        <li><a href="#" class="hover:text-white transition">Marketplace</a></li>
                        <li><a href="#" class="hover:text-white transition">Temas</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-white mb-6">Suporte</h5>
                    <ul class="space-y-4 text-slate-400">
                        <li><a href="#" class="hover:text-white transition">Central de Ajuda</a></li>
                        <li><a href="#" class="hover:text-white transition">Contato</a></li>
                        <li><a href="#" class="hover:text-white transition">Termos de Uso</a></li>
                        <li><a href="#" class="hover:text-white transition">Privacidade</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="flex flex-col md:flex-row justify-between items-center gap-6 pt-12 border-t border-white/5 text-slate-500 text-sm">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Todos os direitos reservados.</p>
                <div class="flex gap-8">
                    <a href="#" class="hover:text-white transition">Twitter</a>
                    <a href="#" class="hover:text-white transition">Instagram</a>
                    <a href="#" class="hover:text-white transition">LinkedIn</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
