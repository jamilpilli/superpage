<?php
// Dashboard - Content Editor

require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();
$siteId = $_GET['site_id'] ?? 0;

if (!$siteId) {
    redirect('/dashboard');
}

if (!can_access_site($siteId)) {
    redirect('/dashboard');
}
$site = db_fetch_one("SELECT id, slug, domain FROM sites WHERE id = :id AND status != 'inactive'", [':id' => $siteId]);

render_dashboard_header("Edit Content – " . ($site['domain'] ?: $site['slug']));

$page = db_fetch_one("SELECT id FROM pages WHERE site_id = :sid AND status = 'published' LIMIT 1", [':sid' => $siteId]);
?>

<div class="max-w-6xl" x-data="contentApp(<?= $siteId ?>, <?= $page['id'] ?? 0 ?>)">

    <!-- Toast notification -->
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2.5 px-5 py-3 rounded-2xl shadow-xl text-sm font-bold pointer-events-none"
         :class="toast.type === 'error' ? 'bg-red-500 text-white' : 'bg-emerald-500 text-white'"
         style="display:none;">
        <span class="material-symbols-outlined" style="font-size:18px"
              x-text="toast.type === 'error' ? 'error' : 'check_circle'"></span>
        <span x-text="toast.message"></span>
    </div>

    <!-- Page header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#a9a4ff]/50 mb-1 font-headline">Edit Content</p>
            <h1 class="text-2xl font-black font-headline text-white leading-none">Content Editor</h1>
        </div>
        <div class="flex items-center gap-3">
            <span x-show="isSaving" class="text-xs text-[#a9a4ff] font-bold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-[#a9a4ff] animate-pulse inline-block"></span>
                Saving…
            </span>
            <a href="<?= BASE_URL ?>/<?= $site['slug'] ?>?preview=true" target="_blank"
               class="flex items-center gap-2 px-4 py-2 bg-[#181828] border border-white/8 hover:bg-white/5 rounded-full text-sm font-bold text-slate-400 hover:text-white transition-all">
                <span class="material-symbols-outlined" style="font-size:17px">open_in_new</span>
                Preview
            </a>
        </div>
    </div>

    <!-- Two-panel layout -->
    <div class="flex flex-col lg:flex-row gap-4 items-start">

        <!-- Left: Block navigation -->
        <aside class="w-full lg:w-56 flex-shrink-0 lg:sticky lg:top-24">
            <div class="flex items-center justify-between px-1 mb-3">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-500">Blocks</span>
                <span class="text-[11px] bg-[#685ef7]/15 text-[#a9a4ff] px-2 py-0.5 rounded-full font-bold tabular-nums"
                      x-text="blocks.length"></span>
            </div>

            <nav class="flex flex-col gap-1">
                <template x-for="block in blocks" :key="block.id">
                    <button @click="selectBlock(block)"
                            :class="activeBlock && activeBlock.id === block.id
                                ? 'bg-[#181828] border-l-2 border-[#a9a4ff] pl-3.5 text-[#a9a4ff]'
                                : 'border-l-2 border-transparent pl-3.5 hover:bg-[#181828]/60 text-slate-400 hover:text-white'"
                            class="flex items-center gap-3 pr-3 py-2.5 rounded-r-xl transition-all group text-left w-full">
                        <span class="material-symbols-outlined flex-shrink-0 transition-colors"
                              style="font-size:18px"
                              :class="activeBlock && activeBlock.id === block.id ? 'text-[#a9a4ff]' : 'text-slate-600 group-hover:text-slate-400'"
                              x-text="blockIcons[block.type] || 'widgets'"></span>
                        <span class="text-sm font-bold truncate" x-text="blockLabels[block.type] || block.type"></span>
                    </button>
                </template>

                <div x-show="blocks.length === 0" class="text-center p-6 bg-[#121220] rounded-xl border border-white/5">
                    <span class="material-symbols-outlined text-3xl text-slate-700 mb-2 block">view_module</span>
                    <p class="text-xs text-slate-500 leading-relaxed">No blocks yet.<br>Use <strong class="text-slate-400">Edit Structure</strong> to add some.</p>
                </div>
            </nav>
        </aside>

        <!-- Right: Editor panel -->
        <section class="flex-1 min-w-0">

            <!-- Empty state -->
            <div x-show="!activeBlock"
                 class="flex flex-col items-center justify-center bg-[#121220] rounded-2xl border border-white/5 py-24 text-center">
                <div class="w-16 h-16 rounded-2xl bg-[#181828] flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-3xl text-slate-600">edit_note</span>
                </div>
                <h3 class="text-base font-bold text-white mb-2">Select a block to edit</h3>
                <p class="text-slate-500 text-sm max-w-xs leading-relaxed">Choose a block from the left to start editing its content.</p>
            </div>

            <!-- Edit form -->
            <div x-show="activeBlock" style="display:none;" class="bg-[#121220] rounded-2xl border border-white/5 overflow-hidden">

                <!-- Form header -->
                <div class="flex items-center gap-4 px-6 py-4 border-b border-white/5">
                    <div class="w-9 h-9 rounded-xl bg-[#a9a4ff]/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:18px"
                              x-text="activeBlock ? (blockIcons[activeBlock.type] || 'widgets') : 'widgets'"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-black font-headline text-white uppercase tracking-wide"
                            x-text="activeBlock ? (blockLabels[activeBlock.type] || activeBlock.type) : ''"></h2>
                        <p class="text-xs text-slate-500 truncate"
                           x-text="activeBlock ? (blockDescs[activeBlock.type] || '') : ''"></p>
                    </div>
                </div>

                <!-- Fields -->
                <form @submit.prevent="saveContent" class="p-6 space-y-6">

                    <!-- Main Title (services, products, gallery, videos, testimonials, about, contact) -->
                    <div x-show="activeBlock && ['services', 'products', 'gallery', 'videos', 'testimonials', 'about', 'contact'].includes(activeBlock.type)" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Section Title</label>
                        <input type="text" x-model="formData.title"
                               class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                               placeholder="Enter a clear, compelling title…">
                    </div>

                    <!-- Header block -->
                    <div x-show="activeBlock && activeBlock.type === 'header'" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Business Name</label>
                            <input type="text" x-model="formData.logo_text"
                                   class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                                   placeholder="e.g. Upper Class Services">
                            <p class="text-[11px] text-slate-600">Shown as text if no logo image is uploaded.</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Logo Image</label>
                            <div class="flex items-center gap-4">
                                <template x-if="formData.image">
                                    <div class="relative p-3 bg-[#0d0d1a] rounded-xl border border-white/8 flex-shrink-0 group overflow-hidden">
                                        <img :src="formData.image" class="object-contain h-10 max-w-[120px]">
                                        <button type="button" @click="formData.image = ''"
                                                class="absolute inset-0 bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">
                                            Remove
                                        </button>
                                    </div>
                                </template>
                                <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-[#0d0d1a] border border-dashed border-white/10 hover:border-[#a9a4ff]/30 rounded-xl cursor-pointer transition-all text-sm text-slate-500 hover:text-slate-300">
                                    <span class="material-symbols-outlined" style="font-size:18px">add_photo_alternate</span>
                                    <span>Upload logo</span>
                                    <input type="file" accept="image/*" @change="uploadImage($event, formData, 'image')" class="hidden">
                                </label>
                            </div>
                            <p class="text-[11px] text-slate-600">PNG or WebP with transparent background recommended.</p>
                        </div>
                    </div>

                    <!-- Hero block -->
                    <div x-show="activeBlock && activeBlock.type === 'hero'" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Headline</label>
                            <input type="text" x-model="formData.title"
                                   class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                                   placeholder="e.g. Trusted Builders in London">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Tagline</label>
                            <input type="text" x-model="formData.subtitle"
                                   class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                                   placeholder="e.g. Over 15 years of quality craftsmanship">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Background Image</label>
                            <div class="flex items-center gap-3">
                                <template x-if="formData.image">
                                    <div class="relative w-20 h-14 rounded-xl overflow-hidden border border-white/10 flex-shrink-0 group">
                                        <img :src="formData.image" class="object-cover w-full h-full">
                                        <button type="button" @click="formData.image = ''"
                                                class="absolute inset-0 bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">✕</button>
                                    </div>
                                </template>
                                <label class="flex-1 flex items-center gap-2 px-4 py-3 bg-[#0d0d1a] border border-dashed border-white/10 hover:border-[#a9a4ff]/30 rounded-xl cursor-pointer transition-all text-sm text-slate-500 hover:text-slate-300">
                                    <span class="material-symbols-outlined" style="font-size:18px">add_photo_alternate</span>
                                    Upload background
                                    <input type="file" accept="image/*" @change="uploadImage($event, formData, 'image')" class="hidden">
                                </label>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">CTA Button Text</label>
                                <input type="text" x-model="formData.cta_text"
                                       class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                                       placeholder="e.g. Get a Free Quote">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">CTA Button URL</label>
                                <input type="text" x-model="formData.cta_link"
                                       class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                                       placeholder="#contact">
                            </div>
                        </div>
                    </div>

                    <!-- Description (services, gallery, videos) -->
                    <div x-show="activeBlock && ['services', 'gallery', 'videos'].includes(activeBlock.type)" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Short Description</label>
                        <textarea x-model="formData.description" rows="3"
                                  class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm resize-none"
                                  placeholder="A brief, engaging description…"></textarea>
                    </div>

                    <!-- About block -->
                    <div x-show="activeBlock && activeBlock.type === 'about'" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Full Description</label>
                            <textarea x-model="formData.text" rows="5" maxlength="600"
                                      class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm resize-none"
                                      placeholder="Tell your story (max 600 characters)…"></textarea>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Image</label>
                            <div class="flex items-center gap-3">
                                <template x-if="formData.image">
                                    <div class="relative w-20 h-20 rounded-xl overflow-hidden border border-white/10 flex-shrink-0 group">
                                        <img :src="formData.image" class="object-cover w-full h-full">
                                        <button type="button" @click="formData.image = ''"
                                                class="absolute inset-0 bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">✕</button>
                                    </div>
                                </template>
                                <label class="flex-1 flex items-center gap-2 px-4 py-3 bg-[#0d0d1a] border border-dashed border-white/10 hover:border-[#a9a4ff]/30 rounded-xl cursor-pointer transition-all text-sm text-slate-500 hover:text-slate-300">
                                    <span class="material-symbols-outlined" style="font-size:18px">add_photo_alternate</span>
                                    Upload image
                                    <input type="file" accept="image/*" @change="uploadImage($event, formData, 'image')" class="hidden">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Contact block -->
                    <div x-show="activeBlock && activeBlock.type === 'contact'" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Receiving Email</label>
                            <input type="email" x-model="formData.email"
                                   class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                                   placeholder="your@business.com">
                            <p class="text-[11px] text-slate-600">Not visible publicly — used to receive form submissions.</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Contact Phone</label>
                            <input type="text" x-model="formData.phone"
                                   class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                                   placeholder="+44 7700 900000">
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" x-model="formData.is_whatsapp"
                                   class="w-4 h-4 rounded text-[#685ef7] bg-[#0d0d1a] border-[#474656] focus:ring-[#685ef7]/50">
                            <div>
                                <span class="text-sm font-bold text-slate-300">WhatsApp number</span>
                                <p class="text-xs text-slate-500">Shows a WhatsApp icon next to the phone number.</p>
                            </div>
                        </label>
                        <div class="flex items-start gap-2 p-3 bg-[#0d0d1a] rounded-xl border border-white/5 text-xs text-slate-500">
                            <span class="material-symbols-outlined text-[#a9a4ff] flex-shrink-0 mt-0.5" style="font-size:15px">info</span>
                            The contact form automatically collects Name, Email, Phone and Message.
                        </div>
                    </div>

                    <!-- Button label (about, contact) -->
                    <div x-show="activeBlock && ['about', 'contact'].includes(activeBlock.type)" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Button Label</label>
                        <input type="text" x-model="formData.button_text"
                               class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                               placeholder="e.g. Send Message">
                    </div>

                    <!-- Button link (about) -->
                    <div x-show="activeBlock && activeBlock.type === 'about'" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Button URL</label>
                        <input type="text" x-model="formData.button_link"
                               class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                               placeholder="https://…">
                    </div>

                    <!-- Gallery -->
                    <div x-show="activeBlock && activeBlock.type === 'gallery'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Photos</label>
                            <span class="text-xs text-slate-500 tabular-nums"
                                  x-text="(formData.gallery_images?.length || 0) + ' / 9'"></span>
                        </div>
                        <label class="flex flex-col items-center justify-center gap-2 py-8 bg-[#0d0d1a] border-2 border-dashed border-white/8 hover:border-[#a9a4ff]/30 rounded-xl cursor-pointer transition-all"
                               @dragover.prevent @drop.prevent="uploadMultipleImages($event.dataTransfer.files)">
                            <span class="material-symbols-outlined text-3xl text-slate-600">add_photo_alternate</span>
                            <span class="text-sm text-slate-500">Click or drag to upload photos</span>
                            <span class="text-xs text-slate-600">Max 9 photos</span>
                            <input type="file" multiple accept="image/*" @change="uploadMultipleImages($event.target.files); $event.target.value=''" class="hidden">
                        </label>
                        <div class="grid grid-cols-3 gap-2" x-show="formData.gallery_images && formData.gallery_images.length > 0">
                            <template x-for="(img, index) in formData.gallery_images" :key="index">
                                <div class="relative group aspect-square rounded-xl overflow-hidden border border-white/8 bg-[#0d0d1a]">
                                    <img :src="img" class="object-cover w-full h-full">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                        <button type="button" @click="formData.gallery_images.splice(index, 1)"
                                                class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold">✕</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Videos -->
                    <div x-show="activeBlock && activeBlock.type === 'videos'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">YouTube Videos</label>
                            <button type="button"
                                    @click="if(!formData.videos) formData.videos = []; formData.videos.push({title: '', url: '', is_active: true})"
                                    class="flex items-center gap-1 px-3 py-1.5 bg-[#a9a4ff]/10 hover:bg-[#a9a4ff]/20 text-[#a9a4ff] text-xs font-bold rounded-full transition-all">
                                <span class="material-symbols-outlined" style="font-size:15px">add</span> Add Video
                            </button>
                        </div>
                        <template x-for="(vid, index) in formData.videos" :key="index">
                            <div class="bg-[#0d0d1a] border border-white/5 rounded-xl p-4 relative"
                                 :class="!vid.is_active ? 'opacity-50' : ''">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="vid.is_active"
                                               class="w-4 h-4 rounded text-[#685ef7] bg-[#181828] border-[#474656]">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Active</span>
                                    </label>
                                    <button type="button" @click="formData.videos.splice(index, 1)"
                                            class="w-6 h-6 flex items-center justify-center text-slate-600 hover:text-red-400 hover:bg-red-400/10 rounded-lg transition-all text-xs">✕</button>
                                </div>
                                <div class="space-y-2">
                                    <input type="text" x-model="vid.title" placeholder="Video title"
                                           class="w-full bg-[#181828] border-none rounded-lg px-3 py-2.5 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/40 outline-none">
                                    <input type="url" x-model="vid.url" placeholder="https://www.youtube.com/watch?v=…"
                                           class="w-full bg-[#181828] border-none rounded-lg px-3 py-2.5 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/40 outline-none">
                                </div>
                            </div>
                        </template>
                        <p x-show="!formData.videos || formData.videos.length === 0"
                           class="text-sm text-slate-500 text-center py-6 bg-[#0d0d1a] border border-dashed border-white/5 rounded-xl">No videos added yet.</p>
                    </div>

                    <!-- Footer block -->
                    <div x-show="activeBlock && activeBlock.type === 'footer'" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Copyright Text</label>
                        <input type="text" x-model="formData.text"
                               class="w-full bg-[#0d0d1a] border border-white/8 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/40 focus:border-[#685ef7]/40 outline-none transition-all text-sm"
                               placeholder="e.g. Serving London since 2010">
                        <p class="text-[11px] text-slate-600">Optional tagline shown in the footer. Leave blank to hide.</p>
                    </div>

                    <!-- Repeatable items (services, products, testimonials) -->
                    <div x-show="activeBlock && ['services', 'products', 'testimonials'].includes(activeBlock.type)" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Items</label>
                            <button type="button"
                                    @click="if(!formData.items) formData.items = []; formData.items.push({title: '', description: '', image: '', button_text: '', button_link: ''})"
                                    class="flex items-center gap-1 px-3 py-1.5 bg-[#a9a4ff]/10 hover:bg-[#a9a4ff]/20 text-[#a9a4ff] text-xs font-bold rounded-full transition-all">
                                <span class="material-symbols-outlined" style="font-size:15px">add</span> Add Item
                            </button>
                        </div>
                        <template x-for="(item, index) in formData.items" :key="index">
                            <div class="bg-[#0d0d1a] border border-white/5 rounded-xl overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2.5 border-b border-white/5">
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                                          x-text="'Item ' + (index + 1)"></span>
                                    <button type="button" @click="formData.items.splice(index, 1)"
                                            class="text-xs text-slate-600 hover:text-red-400 transition-colors font-bold">Remove</button>
                                </div>
                                <div class="p-4 space-y-3">
                                    <input type="text" x-model="item.title" placeholder="Title"
                                           class="w-full bg-[#181828] border-none rounded-lg px-3 py-2.5 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/40 outline-none">
                                    <textarea x-model="item.description" maxlength="200" rows="2" placeholder="Description (max 200 chars)"
                                              class="w-full bg-[#181828] border-none rounded-lg px-3 py-2.5 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/40 outline-none resize-none"></textarea>
                                    <div class="flex items-center gap-3">
                                        <template x-if="item.image">
                                            <div class="relative w-12 h-12 rounded-lg overflow-hidden border border-white/10 flex-shrink-0 group">
                                                <img :src="item.image" class="object-cover w-full h-full">
                                                <button type="button" @click="item.image = ''"
                                                        class="absolute inset-0 bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">✕</button>
                                            </div>
                                        </template>
                                        <label class="flex-1 flex items-center gap-2 px-3 py-2 bg-[#181828] border border-dashed border-white/8 hover:border-[#a9a4ff]/30 rounded-lg cursor-pointer transition-all text-xs text-slate-500">
                                            <span class="material-symbols-outlined" style="font-size:15px">add_photo_alternate</span>
                                            Image
                                            <input type="file" accept="image/*" @change="uploadImage($event, item, 'image')" class="hidden">
                                        </label>
                                        <input type="text" x-model="item.button_text" placeholder="Button label"
                                               class="flex-1 bg-[#181828] border-none rounded-lg px-3 py-2 text-white text-xs focus:ring-2 focus:ring-[#685ef7]/40 outline-none">
                                        <input type="text" x-model="item.button_link" placeholder="URL"
                                               class="flex-1 bg-[#181828] border-none rounded-lg px-3 py-2 text-white text-xs focus:ring-2 focus:ring-[#685ef7]/40 outline-none">
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="!formData.items || formData.items.length === 0"
                             class="text-sm text-slate-500 text-center py-6 bg-[#0d0d1a] border border-dashed border-white/5 rounded-xl">No items yet. Add one above.</div>
                    </div>

                    <!-- Save button -->
                    <div class="pt-2 border-t border-white/5">
                        <button type="submit" :disabled="isSaving"
                                class="w-full py-3 rounded-xl bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm hover:brightness-110 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-[#685ef7]/20">
                            <span x-show="!isSaving">Save Content</span>
                            <span x-show="isSaving">Saving…</span>
                        </button>
                    </div>

                </form>
            </div>

        </section>
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
        toast: { show: false, message: '', type: 'success' },

        blockLabels: {
            header: 'Header', hero: 'Hero Banner', about: 'About Us',
            services: 'Services', products: 'Products', gallery: 'Gallery',
            videos: 'Videos', testimonials: 'Testimonials', contact: 'Contact', footer: 'Footer'
        },
        blockDescs: {
            header: 'Top navigation bar with logo.', hero: 'Full-width banner with headline.',
            about: 'Text description with side image.', services: 'Grid of service highlights.',
            products: 'Product showcase cards.', gallery: 'Drag-and-drop photo grid.',
            videos: 'Embedded YouTube videos.', testimonials: 'Quotes and social proof.',
            contact: 'Email form and contact details.', footer: 'Bottom closing section.'
        },
        blockIcons: {
            header: 'top_panel_close', hero: 'view_carousel', about: 'person_outline',
            services: 'category', products: 'inventory_2', gallery: 'photo_library',
            videos: 'smart_display', testimonials: 'format_quote', contact: 'mail_outline', footer: 'bottom_panel_close'
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => this.toast.show = false, 2800);
        },

        init() {
            this.fetchBlocks();
        },

        async fetchBlocks() {
            try {
                const res = await fetch(`<?= BASE_URL ?>/api/blocks?page_id=${this.pageId}`);
                if (!res.ok) throw new Error('Failed to load blocks');
                const json = await res.json();
                this.blocks = json.data || [];

                const urlParams = new URLSearchParams(window.location.search);
                const blockType = urlParams.get('block_type');
                if (blockType) {
                    const found = this.blocks.find(b => b.type === blockType && b.config?.is_active !== false);
                    if (found) this.selectBlock(found);
                }
            } catch (e) {
                this.showToast(e.message, 'error');
            }
        },

        selectBlock(block) {
            this.activeBlock = block;
            this.formData = {
                title:       block.config.title       || '',
                logo_text:   block.config.logo_text   || '',
                description: block.config.description || '',
                text:        block.config.text        || '',
                image:       block.config.image       || '',
                subtitle:    block.config.subtitle    || '',
                cta_text:    block.config.cta_text    || '',
                cta_link:    block.config.cta_link    || '',
                button_text: block.config.button_text || '',
                button_link: block.config.button_link || '',
                email:       block.config.email       || '',
                phone:       block.config.phone       || '',
                is_whatsapp: block.config.is_whatsapp || false,
                items:         JSON.parse(JSON.stringify(block.config.items         || [])),
                gallery_images:JSON.parse(JSON.stringify(block.config.gallery_images|| [])),
                videos:        JSON.parse(JSON.stringify(block.config.videos        || []))
            };
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async saveContent() {
            if (!this.activeBlock) return;
            this.isSaving = true;

            const updatedConfig = { ...this.activeBlock.config, ...this.formData };

            try {
                const res = await fetch(`<?= BASE_URL ?>/api/blocks`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ block_id: this.activeBlock.id, config: updatedConfig })
                });

                if (res.ok) {
                    this.activeBlock.config = updatedConfig;
                    const idx = this.blocks.findIndex(b => b.id === this.activeBlock.id);
                    if (idx > -1) this.blocks[idx].config = updatedConfig;
                    this.showToast('Content saved successfully!');
                } else {
                    this.showToast('Error saving content. Please try again.', 'error');
                }
            } catch (e) {
                this.showToast('Network error. Please try again.', 'error');
            }
            this.isSaving = false;
        },

        async uploadImage(event, targetObj, propName) {
            const file = event.target.files[0];
            if (!file) return;

            this.isSaving = true;
            const fd = new FormData();
            fd.append('image', file);

            try {
                const res = await fetch(`<?= BASE_URL ?>/api/endpoints/upload.php`, { method: 'POST', body: fd });
                const json = await res.json();
                if (res.ok && json.success) {
                    targetObj[propName] = json.url;
                } else {
                    this.showToast(json.error || 'Error uploading image.', 'error');
                }
            } catch (e) {
                this.showToast('Network error during upload.', 'error');
            }

            event.target.value = '';
            this.isSaving = false;
        },

        async uploadMultipleImages(files) {
            if (!files || files.length === 0) return;
            if (!this.formData.gallery_images) this.formData.gallery_images = [];

            const remainingSlots = 9 - this.formData.gallery_images.length;
            const filesToUpload = Array.from(files).slice(0, remainingSlots);

            if (filesToUpload.length === 0) {
                this.showToast('Maximum of 9 photos reached.', 'error');
                return;
            }
            if (files.length > remainingSlots) {
                this.showToast(`Only ${remainingSlots} photo(s) selected to respect the 9-photo limit.`);
            }

            this.isSaving = true;

            for (let file of filesToUpload) {
                if (!file.type.startsWith('image/')) continue;
                const fd = new FormData();
                fd.append('image', file);
                try {
                    const res = await fetch(`<?= BASE_URL ?>/api/endpoints/upload.php`, { method: 'POST', body: fd });
                    const json = await res.json();
                    if (res.ok && json.success) {
                        this.formData.gallery_images.push(json.url);
                    } else {
                        this.showToast(`Error uploading ${file.name}: ${json.error || 'Upload failed'}`, 'error');
                    }
                } catch (e) {
                    console.error('File error', e);
                }
            }

            this.isSaving = false;
        }
    }));
});
</script>

<?php render_dashboard_footer(); ?>
