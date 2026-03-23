<?php
// Public Landing Page
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> | No-Code Website Builder for UK Small Business</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#a9a4ff",
                        "primary-dim": "#685ef7",
                        "secondary-dim": "#914feb",
                        "surface-container": "#181828",
                        "surface-container-high": "#1e1e2f",
                        "surface-container-low": "#121220",
                        "on-surface-variant": "#aba9bb",
                        "outline-variant": "#474656",
                    },
                    fontFamily: {
                        "headline": ["Plus Jakarta Sans"],
                        "body": ["Inter"],
                    },
                }
            }
        }
    </script>
    <style>
        body { background-color: #0d0d1a; color: #e9e6f9; font-family: 'Inter', sans-serif; }
        .signature-glow { background: linear-gradient(135deg, #685ef7 0%, #914feb 100%); }
        .glass-card { background: rgba(36,36,55,0.6); backdrop-filter: blur(20px); }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="antialiased selection:bg-[#a9a4ff]/30">

    <!-- Nav -->
    <nav class="fixed top-0 w-full z-50 bg-[#0d0d1a]/80 backdrop-blur-xl border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 md:px-8 flex justify-between items-center h-16">
            <div class="text-2xl font-black text-white tracking-tighter font-headline"><?= APP_NAME ?></div>

            <div class="hidden md:flex items-center gap-8 font-headline font-bold tracking-tight text-sm">
                <a href="#features" class="text-slate-400 hover:text-white transition-colors">Features</a>
                <a href="#how-it-works" class="text-slate-400 hover:text-white transition-colors">How It Works</a>
                <a href="#pricing" class="text-slate-400 hover:text-white transition-colors">Pricing</a>
            </div>

            <div class="flex items-center gap-3">
                <?php if (is_logged_in()): ?>
                    <a href="<?= BASE_URL ?>/dashboard" class="text-sm font-bold text-slate-400 hover:text-white transition">Dashboard</a>
                    <a href="<?= BASE_URL ?>/auth/logout" class="px-5 py-2 rounded-full text-sm font-bold text-slate-400 hover:text-white border border-white/10 hover:bg-white/5 transition">Sign Out</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login" class="text-sm font-bold text-slate-400 hover:text-white transition">Log In</a>
                    <a href="<?= BASE_URL ?>/auth/register" class="signature-glow px-6 py-2.5 rounded-full text-sm font-bold text-white hover:opacity-90 transition-all shadow-lg shadow-[#685ef7]/25">Try for Free</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="relative pt-32 pb-24 md:pt-40 md:pb-32 overflow-hidden">
        <!-- Background glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[500px] rounded-full bg-[#685ef7]/20 blur-[120px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 md:px-8 flex flex-col lg:flex-row items-center gap-16 relative">
            <!-- Text -->
            <div class="flex-1 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#181828] border border-[#a9a4ff]/20 mb-6">
                    <span class="text-sm font-bold text-[#a9a4ff]">🚀 1st year free for your first site!</span>
                </div>
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-headline font-extrabold text-white tracking-tighter leading-[1.05] mb-5">
                    Professional websites at<br>
                    <span class="text-transparent bg-clip-text signature-glow">record speed</span>
                </h1>
                <h2 class="text-2xl font-headline font-bold text-[#9a94ff] mb-5">No code. No hassle.</h2>
                <p class="text-lg text-on-surface-variant max-w-xl mx-auto lg:mx-0 mb-10 leading-relaxed">
                    The complete platform for small businesses and freelancers who need a fast, beautiful online presence — without the big agency price tag.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="<?= BASE_URL ?>/auth/register"
                       class="w-full sm:w-auto signature-glow px-8 py-4 rounded-full font-bold text-lg text-white hover:opacity-90 transition-all shadow-xl shadow-[#685ef7]/30">
                        Get Started — Free
                    </a>
                    <a href="#how-it-works"
                       class="w-full sm:w-auto px-8 py-4 rounded-full font-bold text-lg text-white border border-white/15 hover:bg-white/5 transition-all">
                        See How It Works
                    </a>
                </div>
            </div>

            <!-- Visual -->
            <div class="flex-1 relative flex justify-center lg:justify-end w-full max-w-md lg:max-w-none">
                <div class="w-full glass-card rounded-2xl p-4 border border-white/5 shadow-2xl">
                    <div class="bg-[#121220] rounded-xl aspect-video flex flex-col items-center justify-center gap-4 p-8">
                        <div class="flex gap-3">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                        <div class="w-full space-y-2">
                            <div class="h-4 bg-[#685ef7]/30 rounded-full w-3/4 mx-auto"></div>
                            <div class="h-3 bg-white/5 rounded-full w-full"></div>
                            <div class="h-3 bg-white/5 rounded-full w-5/6 mx-auto"></div>
                        </div>
                        <div class="flex gap-3 mt-2">
                            <div class="h-8 w-28 signature-glow rounded-full opacity-80"></div>
                            <div class="h-8 w-24 bg-white/10 rounded-full border border-white/10"></div>
                        </div>
                    </div>
                </div>
                <!-- Floating badge -->
                <div class="absolute -top-4 -right-4 glass-card border border-white/10 p-3 rounded-xl shadow-xl hidden lg:block">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center">
                            <span class="material-symbols-outlined text-green-400" style="font-size:18px;font-variation-settings:'FILL' 1;">check_circle</span>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Performance</div>
                            <div class="text-base font-black text-white">99/100</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-24 bg-[#121220]">
        <div class="max-w-7xl mx-auto px-6 md:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-headline font-bold text-white mb-4">Everything you need to grow</h2>
                <p class="text-on-surface-variant max-w-2xl mx-auto text-lg">Built specifically for the UK small business market with local integrations and dedicated support.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $features = [
                    ['grid_view',    'primary',       '#a9a4ff', 'Block Builder',      'Drag, drop, and customise. No technical skills required to build professional layouts.'],
                    ['storefront',   'secondary-dim', '#914feb', 'Theme Marketplace',  'Choose from hundreds of industry-specific templates crafted by expert designers.'],
                    ['bolt',         'tertiary',      '#ff98cd', 'Extreme Performance','Lightning-fast load times that keep your customers happy and improve SEO rankings.'],
                    ['language',     'primary',       '#a9a4ff', 'Custom Domain',      'Connect your own .co.uk or .com domain instantly with automated SSL security.'],
                    ['handshake',    'secondary-dim', '#914feb', 'Partner Programme',  'Earn rewards by referring other businesses or manage multiple client sites effortlessly.'],
                    ['layers',       'tertiary',      '#ff98cd', 'Multiple Sites',     'Scale your empire. Launch new pages or separate brands from a single dashboard.'],
                ];
                foreach ($features as [$icon, $colorKey, $hex, $title, $desc]):
                ?>
                <div class="bg-[#181828] p-8 rounded-2xl border border-white/5 hover:-translate-y-1 transition-all duration-200 group">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center mb-6" style="background:<?= $hex ?>1a">
                        <span class="material-symbols-outlined text-3xl" style="color:<?= $hex ?>"><?= $icon ?></span>
                    </div>
                    <h3 class="text-xl font-headline font-bold text-white mb-3"><?= $title ?></h3>
                    <p class="text-on-surface-variant leading-relaxed text-sm"><?= $desc ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section id="how-it-works" class="py-24 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full bg-[#685ef7]/10 blur-[100px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 md:px-8 flex flex-col lg:flex-row items-center gap-20 relative">
            <div class="flex-1 space-y-10">
                <h2 class="text-4xl md:text-5xl font-headline font-bold text-white">How it works</h2>

                <?php $steps = [
                    ['01', 'Create your account',  'Sign up in seconds. No credit card required to start building your dream site today.'],
                    ['02', 'Choose your blocks',   'Pick from pre-designed components and stack them to create your perfect layout.'],
                    ['03', 'Publish and grow',      'Hit publish and watch your site go live on our global edge network. Scale with ease.'],
                ]; foreach ($steps as [$num, $title, $desc]): ?>
                <div class="relative pl-14">
                    <div class="absolute left-0 top-0 text-5xl font-headline font-black text-[#9a94ff]/20 select-none leading-none"><?= $num ?></div>
                    <h3 class="text-xl font-headline font-bold text-white mb-2"><?= $title ?></h3>
                    <p class="text-on-surface-variant leading-relaxed"><?= $desc ?></p>
                </div>
                <?php endforeach; ?>

                <a href="<?= BASE_URL ?>/auth/register"
                   class="inline-flex items-center gap-2 signature-glow px-8 py-4 rounded-full font-bold text-white hover:opacity-90 transition-all shadow-lg shadow-[#685ef7]/25">
                    Get Started — Free
                    <span class="material-symbols-outlined">arrow_forward</span>
                </a>
            </div>

            <!-- Editor mockup -->
            <div class="flex-1 w-full max-w-lg lg:max-w-none">
                <div class="bg-[#181828] rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                    <!-- Fake topbar -->
                    <div class="flex items-center gap-2 px-4 py-3 bg-[#121220] border-b border-white/5">
                        <div class="w-3 h-3 rounded-full bg-red-400/60"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400/60"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400/60"></div>
                        <div class="flex-1 mx-4 h-5 bg-white/5 rounded-full"></div>
                    </div>
                    <!-- Fake sidebar + content -->
                    <div class="flex">
                        <div class="w-16 bg-[#121220] py-4 px-2 space-y-3 flex flex-col items-center border-r border-white/5">
                            <?php foreach(['home','edit_note','palette','contacts'] as $ic): ?>
                            <div class="w-9 h-9 rounded-full flex items-center justify-center bg-white/5 hover:bg-[#685ef7]/20 transition-colors">
                                <span class="material-symbols-outlined text-slate-500" style="font-size:18px"><?= $ic ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex-1 p-6 space-y-4">
                            <div class="h-5 bg-[#a9a4ff]/20 rounded-full w-2/3"></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="h-20 bg-white/5 rounded-xl"></div>
                                <div class="h-20 bg-white/5 rounded-xl"></div>
                            </div>
                            <div class="h-3 bg-white/5 rounded-full w-full"></div>
                            <div class="h-3 bg-white/5 rounded-full w-4/5"></div>
                            <div class="h-8 signature-glow rounded-full w-28 mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-24 bg-gradient-to-b from-[#0d0d1a] via-[#121220] to-[#0d0d1a]">
        <div class="max-w-7xl mx-auto px-6 md:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-headline font-extrabold text-white mb-4">Get ready to transform your business</h2>
            <p class="text-on-surface-variant mb-12 text-lg">Launch offer: the first year is completely free for your first site!</p>

            <div class="max-w-lg mx-auto bg-[#1e1e2f] p-12 rounded-2xl border border-[#474656]/40 shadow-2xl relative">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 signature-glow text-white px-6 py-1 rounded-full text-xs font-black uppercase tracking-widest shadow-lg">
                    Limited Launch Offer
                </div>
                <div class="text-6xl font-headline font-extrabold text-white mb-2">£0<span class="text-xl font-medium text-on-surface-variant">/year</span></div>
                <p class="text-[#9a94ff] font-bold mb-8">For your first 12 months</p>

                <ul class="text-left space-y-4 mb-10">
                    <?php $perks = ['Unlimited Blocks & Pages', 'Custom .co.uk Domain Included', 'Priority UK-Based Support', 'No Transaction Fees']; ?>
                    <?php foreach ($perks as $perk): ?>
                    <li class="flex items-center gap-3 text-on-surface-variant">
                        <span class="material-symbols-outlined text-[#a9a4ff]" style="font-variation-settings:'FILL' 1;">check_circle</span>
                        <?= $perk ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <a href="<?= BASE_URL ?>/auth/register"
                   class="block w-full signature-glow text-white py-4 rounded-full font-bold text-xl hover:opacity-90 transition-all mb-4">
                    Start Now — Pay Nothing
                </a>
                <p class="text-sm text-on-surface-variant/60 flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-sm">verified_user</span>
                    No credit card required.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#080812] border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 md:px-8 py-16 grid grid-cols-2 md:grid-cols-4 gap-12">
            <div class="col-span-2 md:col-span-1">
                <div class="text-xl font-black text-white font-headline mb-5"><?= APP_NAME ?></div>
                <p class="text-slate-500 text-sm leading-relaxed mb-6">Empowering UK small businesses with high-performance web solutions.</p>
            </div>
            <div>
                <h4 class="text-white font-bold mb-5 text-sm uppercase tracking-widest">Product</h4>
                <ul class="space-y-3 text-slate-500 text-sm">
                    <li><a href="#features" class="hover:text-white transition">Features</a></li>
                    <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
                    <li><a href="#" class="hover:text-white transition">Themes</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-5 text-sm uppercase tracking-widest">Support</h4>
                <ul class="space-y-3 text-slate-500 text-sm">
                    <li><a href="#" class="hover:text-white transition">Help Centre</a></li>
                    <li><a href="#" class="hover:text-white transition">Contact</a></li>
                    <li><a href="#" class="hover:text-white transition">Partner Programme</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-5 text-sm uppercase tracking-widest">Legal</h4>
                <ul class="space-y-3 text-slate-500 text-sm">
                    <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-6 md:px-8 py-6 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-4 text-slate-500 text-sm">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> UK. Built for small business.</p>
            <div class="flex gap-6">
                <a href="#" class="hover:text-white transition">Twitter</a>
                <a href="#" class="hover:text-white transition">Instagram</a>
                <a href="#" class="hover:text-white transition">LinkedIn</a>
            </div>
        </div>
    </footer>

</body>
</html>
