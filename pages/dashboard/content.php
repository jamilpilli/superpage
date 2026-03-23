<?php
// Dashboard - Content Editor

require_once __DIR__ . '/../../includes/dashboard_template.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = get_logged_user();
$siteId = $_GET['site_id'] ?? 0;

if (!$siteId) {
    redirect('/dashboard');
}

$site = db_fetch_one("SELECT id, slug, domain FROM sites WHERE id = :id AND user_id = :uid AND status != 'inactive'", [':id' => $siteId, ':uid' => $user['id']]);
if (!$site) {
    redirect('/dashboard');
}

render_dashboard_header("Edit Content – " . ($site['domain'] ?: $site['slug']));

$page = db_fetch_one("SELECT id FROM pages WHERE site_id = :sid AND status = 'published' LIMIT 1", [':sid' => $siteId]);
?>

<div class="max-w-7xl mx-auto" x-data="contentApp(<?= $siteId ?>, <?= $page['id'] ?? 0 ?>)">

    <!-- Page header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="px-3 py-1 bg-[#a9a4ff]/10 text-[#a9a4ff] text-xs font-black rounded-full tracking-widest uppercase">Edit Content</span>
                <span x-show="isSaving" class="text-xs text-[#a9a4ff] font-bold animate-pulse">Saving…</span>
            </div>
            <h1 class="text-3xl font-black font-headline text-white">Content Editor</h1>
            <p class="text-slate-400 text-sm mt-1">Select a block on the left to start editing its content.</p>
        </div>
        <a href="<?= BASE_URL ?>/<?= $site['slug'] ?>?preview=true" target="_blank"
           class="flex items-center gap-2 px-5 py-2.5 bg-[#181828] border border-white/10 hover:bg-white/5 rounded-full text-sm font-bold text-slate-300 hover:text-white transition-all flex-shrink-0 self-start sm:self-auto">
            <span class="material-symbols-outlined text-xl">open_in_new</span>
            Preview Site
        </a>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">

        <!-- Left: Block List -->
        <aside class="w-full lg:w-72 flex-shrink-0">
            <div class="flex items-center justify-between px-1 mb-4">
                <h3 class="text-base font-bold text-white font-headline">Select Block</h3>
                <span class="text-xs bg-[#685ef7]/20 text-[#a9a4ff] px-2 py-1 rounded-full font-bold" x-text="blocks.length + ' block' + (blocks.length !== 1 ? 's' : '')"></span>
            </div>

            <div class="flex flex-col gap-2">
                <template x-for="block in blocks" :key="block.id">
                    <button @click="selectBlock(block)"
                            :class="activeBlock && activeBlock.id === block.id
                                ? 'bg-[#181828] border border-[#a9a4ff]/25 shadow-lg shadow-[#a9a4ff]/5'
                                : 'bg-[#121220] border border-transparent hover:bg-[#181828]'"
                            class="flex items-center justify-between p-4 rounded-xl transition-all group text-left w-full">
                        <div class="min-w-0">
                            <span class="block font-bold text-sm transition-colors"
                                  :class="activeBlock && activeBlock.id === block.id ? 'text-[#a9a4ff]' : 'text-white group-hover:text-[#a9a4ff]'"
                                  x-text="blockLabels[block.type] || block.type"></span>
                            <span class="text-xs text-slate-500" x-text="blockDescs[block.type] || ''"></span>
                        </div>
                        <span class="material-symbols-outlined flex-shrink-0 ml-2 transition-colors"
                              :class="activeBlock && activeBlock.id === block.id ? 'text-[#a9a4ff]' : 'text-slate-600 group-hover:text-[#a9a4ff]'">chevron_right</span>
                    </button>
                </template>

                <div x-show="blocks.length === 0" class="text-center p-8 bg-[#121220] rounded-xl border border-white/5">
                    <span class="material-symbols-outlined text-4xl text-slate-600 mb-3 block">view_module</span>
                    <p class="text-sm text-slate-500">No blocks found.<br>Use <strong class="text-slate-400">Edit Structure</strong> to add blocks.</p>
                </div>
            </div>
        </aside>

        <!-- Right: Edit Panel -->
        <section class="flex-1 min-w-0">

            <!-- Empty state -->
            <div x-show="!activeBlock" class="flex flex-col items-center justify-center bg-[#121220] rounded-2xl border border-white/5 py-32 text-center">
                <div class="w-20 h-20 rounded-full bg-[#181828] border border-white/5 flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-4xl text-slate-600">edit_note</span>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">No Block Selected</h3>
                <p class="text-slate-500 text-sm max-w-xs">Select a block from the left panel to edit its content.</p>
            </div>

            <!-- Edit area -->
            <div x-show="activeBlock" style="display: none;" class="flex flex-col gap-6">

                <!-- Block header bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-[#121220] p-6 rounded-2xl border-l-4 border-[#685ef7] gap-4">
                    <div class="min-w-0">
                        <h2 class="text-xl font-black font-headline tracking-tight text-white uppercase"
                            x-text="'Edit Block: ' + (activeBlock ? (blockLabels[activeBlock.type] || activeBlock.type) : '')"></h2>
                        <p class="text-slate-400 text-sm mt-1"
                           x-text="activeBlock ? (blockDescs[activeBlock.type] || 'Customise this block\'s content.') : ''"></p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <a :href="'<?= BASE_URL ?>/<?= $site['slug'] ?>?preview=true'" target="_blank"
                           class="px-5 py-2 rounded-full border border-[#474656] hover:bg-white/5 text-slate-300 hover:text-white transition-all text-sm font-bold flex items-center gap-2">
                            Preview <span class="material-symbols-outlined text-base">open_in_new</span>
                        </a>
                        <button @click="saveContent" :disabled="isSaving"
                                class="px-7 py-2 rounded-full bg-gradient-to-r from-[#685ef7] to-[#914feb] hover:brightness-110 text-white font-bold transition-all text-sm shadow-lg shadow-[#685ef7]/20 disabled:opacity-50">
                            <span x-show="!isSaving">Save Content</span>
                            <span x-show="isSaving">Saving…</span>
                        </button>
                    </div>
                </div>

                <!-- 2-column: form + tip -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                    <!-- Form -->
                    <form @submit.prevent="saveContent" class="xl:col-span-2 space-y-5 bg-[#121220] rounded-2xl p-6 border border-white/5">

                        <!-- Title (all except hero) -->
                        <div x-show="activeBlock && activeBlock.type !== 'hero'" class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Main Title</label>
                            <input type="text" x-model="formData.title"
                                   class="w-full bg-[#181828] border-none rounded-xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all text-sm"
                                   placeholder="Enter a compelling title…">
                        </div>

                        <!-- Logo (header) -->
                        <div x-show="activeBlock && activeBlock.type === 'header'" class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Logo Image</label>
                            <div class="flex items-center gap-4">
                                <template x-if="formData.image">
                                    <div class="relative p-2 bg-[#181828] rounded-xl border border-white/10 flex-shrink-0 group overflow-hidden">
                                        <img :src="formData.image" class="object-contain h-12">
                                        <button type="button" @click="formData.image = ''" class="absolute inset-0 bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-sm font-bold">✕</button>
                                    </div>
                                </template>
                                <label class="flex-1 flex items-center justify-center gap-2 p-4 bg-[#181828] border border-dashed border-white/10 hover:border-[#a9a4ff]/40 rounded-xl cursor-pointer transition-all text-sm text-slate-500 hover:text-slate-300">
                                    <span class="material-symbols-outlined">add_photo_alternate</span>
                                    <span>Upload logo</span>
                                    <input type="file" accept="image/*" @change="uploadImage($event, formData, 'image')" class="hidden">
                                </label>
                            </div>
                            <p class="text-xs text-slate-600">Recommended: PNG or WebP with transparent background.</p>
                        </div>

                        <!-- Description (services, gallery, videos) -->
                        <div x-show="activeBlock && ['services', 'gallery', 'videos'].includes(activeBlock.type)" class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Subtitle / Short Description</label>
                            <textarea x-model="formData.description" rows="3"
                                      class="w-full bg-[#181828] border-none rounded-xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all text-sm resize-none"
                                      placeholder="A brief, engaging description…"></textarea>
                        </div>

                        <!-- Full text + image (about) -->
                        <div x-show="activeBlock && activeBlock.type === 'about'" class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Full Description</label>
                                <textarea x-model="formData.text" rows="5" maxlength="600"
                                          class="w-full bg-[#181828] border-none rounded-xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all text-sm resize-none"
                                          placeholder="Tell your story (max 600 characters)…"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Image</label>
                                <div class="flex items-center gap-4">
                                    <template x-if="formData.image">
                                        <div class="relative w-20 h-20 rounded-xl overflow-hidden border border-white/10 flex-shrink-0 group">
                                            <img :src="formData.image" class="object-cover w-full h-full">
                                            <button type="button" @click="formData.image = ''" class="absolute inset-0 bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">✕</button>
                                        </div>
                                    </template>
                                    <label class="flex-1 flex items-center justify-center gap-2 p-4 bg-[#181828] border border-dashed border-white/10 hover:border-[#a9a4ff]/40 rounded-xl cursor-pointer transition-all text-sm text-slate-500 hover:text-slate-300">
                                        <span class="material-symbols-outlined">add_photo_alternate</span>
                                        Upload image
                                        <input type="file" accept="image/*" @change="uploadImage($event, formData, 'image')" class="hidden">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Contact block fields -->
                        <div x-show="activeBlock && activeBlock.type === 'contact'" class="space-y-5">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Receiving Email</label>
                                <input type="email" x-model="formData.email"
                                       class="w-full bg-[#181828] border-none rounded-xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all text-sm"
                                       placeholder="your@business.com">
                                <p class="text-xs text-slate-600">Not shown publicly — used to receive form submissions.</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Contact Phone Number</label>
                                <input type="text" x-model="formData.phone"
                                       class="w-full bg-[#181828] border-none rounded-xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all text-sm"
                                       placeholder="+44 7700 900000">
                                <p class="text-xs text-slate-600">Displayed in the header and footer for direct client contact.</p>
                            </div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input id="is_whatsapp" type="checkbox" x-model="formData.is_whatsapp"
                                       class="w-5 h-5 rounded text-[#685ef7] bg-[#181828] border-[#474656] focus:ring-[#685ef7]/50">
                                <div>
                                    <span class="text-sm font-bold text-slate-300">WhatsApp number</span>
                                    <p class="text-xs text-slate-500">Show WhatsApp icon next to the phone number on the site.</p>
                                </div>
                            </label>
                            <div class="bg-[#181828] p-4 rounded-xl border border-[#a9a4ff]/10 text-xs text-slate-400 flex items-start gap-2">
                                <span class="material-symbols-outlined text-[#a9a4ff] text-base flex-shrink-0 mt-0.5">info</span>
                                The contact form automatically collects: Name, Email, Phone and Message.
                            </div>
                        </div>

                        <!-- Button text (about, contact) -->
                        <div x-show="activeBlock && ['about', 'contact'].includes(activeBlock.type)" class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Button Label</label>
                            <input type="text" x-model="formData.button_text"
                                   class="w-full bg-[#181828] border-none rounded-xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all text-sm"
                                   placeholder="e.g. Send Message">
                        </div>

                        <!-- Button link (about) -->
                        <div x-show="activeBlock && activeBlock.type === 'about'" class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Button Link</label>
                            <input type="text" x-model="formData.button_link"
                                   class="w-full bg-[#181828] border-none rounded-xl p-4 text-white placeholder-slate-600 focus:ring-2 focus:ring-[#685ef7]/50 transition-all text-sm"
                                   placeholder="https://…">
                        </div>

                        <!-- Gallery -->
                        <div x-show="activeBlock && activeBlock.type === 'gallery'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Gallery Photos (Max 9)</label>
                                <span class="text-xs text-slate-500 font-medium" x-text="(formData.gallery_images?.length || 0) + ' / 9'"></span>
                            </div>
                            <label class="flex flex-col items-center justify-center gap-2 p-6 bg-[#181828] border-2 border-dashed border-white/10 hover:border-[#a9a4ff]/40 rounded-xl cursor-pointer transition-all"
                                   @dragover.prevent="$el.classList.add('!border-[#a9a4ff]/40')"
                                   @dragleave.prevent="$el.classList.remove('!border-[#a9a4ff]/40')"
                                   @drop.prevent="$el.classList.remove('!border-[#a9a4ff]/40'); uploadMultipleImages($event.dataTransfer.files)">
                                <span class="material-symbols-outlined text-3xl text-slate-600">add_photo_alternate</span>
                                <span class="text-sm text-slate-500">Click or drag to upload photos</span>
                                <input type="file" multiple accept="image/*" @change="uploadMultipleImages($event.target.files); $event.target.value=''" class="hidden">
                            </label>
                            <div class="grid grid-cols-3 gap-3" x-show="formData.gallery_images && formData.gallery_images.length > 0">
                                <template x-for="(img, index) in formData.gallery_images" :key="index">
                                    <div class="relative group aspect-square rounded-xl overflow-hidden border border-white/10 bg-[#181828]">
                                        <img :src="img" class="object-cover w-full h-full">
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all">
                                            <button type="button" @click="formData.gallery_images.splice(index, 1)"
                                                    class="w-9 h-9 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center font-bold">✕</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Videos -->
                        <div x-show="activeBlock && activeBlock.type === 'videos'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">YouTube Videos</label>
                                <button type="button" @click="if(!formData.videos) formData.videos = []; formData.videos.push({title: '', url: '', is_active: true})"
                                        class="flex items-center gap-1 px-3 py-1.5 bg-[#a9a4ff]/10 hover:bg-[#a9a4ff]/20 text-[#a9a4ff] text-xs font-bold rounded-full transition-all">
                                    <span class="material-symbols-outlined" style="font-size:16px">add</span> Add Video
                                </button>
                            </div>
                            <template x-for="(vid, index) in formData.videos" :key="index">
                                <div class="bg-[#181828] border border-white/5 rounded-xl p-4 relative" :class="!vid.is_active ? 'opacity-50' : ''">
                                    <button type="button" @click="formData.videos.splice(index, 1)"
                                            class="absolute top-3 right-3 w-7 h-7 flex items-center justify-center text-slate-500 hover:text-red-400 hover:bg-red-400/10 rounded-full transition-all">✕</button>
                                    <div class="space-y-3">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="vid.is_active" class="w-4 h-4 rounded text-[#685ef7] bg-[#242437] border-[#474656]">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Active</span>
                                        </label>
                                        <div>
                                            <label class="text-xs text-slate-500 mb-1 block">Video Title</label>
                                            <input type="text" x-model="vid.title" class="w-full bg-[#242437] border-none rounded-lg p-3 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/50" placeholder="e.g. Company Introduction">
                                        </div>
                                        <div>
                                            <label class="text-xs text-slate-500 mb-1 block">YouTube URL</label>
                                            <input type="url" x-model="vid.url" class="w-full bg-[#242437] border-none rounded-lg p-3 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/50" placeholder="https://www.youtube.com/watch?v=…">
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <p x-show="!formData.videos || formData.videos.length === 0"
                               class="text-sm text-slate-500 italic text-center py-4 bg-[#181828] border border-dashed border-white/5 rounded-xl">No videos added yet.</p>
                        </div>

                        <!-- Repeatable items (hero, services, products, testimonials) -->
                        <div x-show="activeBlock && ['hero', 'services', 'products', 'testimonials'].includes(activeBlock.type)" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold uppercase tracking-widest text-[#9a94ff]">Block Items</label>
                                <button type="button" @click="if(!formData.items) formData.items = []; formData.items.push({title: '', description: '', image: '', button_text: '', button_link: ''})"
                                        class="flex items-center gap-1 px-3 py-1.5 bg-[#a9a4ff]/10 hover:bg-[#a9a4ff]/20 text-[#a9a4ff] text-xs font-bold rounded-full transition-all">
                                    <span class="material-symbols-outlined" style="font-size:16px">add</span> Add Item
                                </button>
                            </div>
                            <template x-for="(item, index) in formData.items" :key="index">
                                <div class="bg-[#181828] border border-white/5 rounded-xl p-4 relative">
                                    <button type="button" @click="formData.items.splice(index, 1)"
                                            class="absolute top-3 right-3 w-7 h-7 flex items-center justify-center text-slate-500 hover:text-red-400 hover:bg-red-400/10 rounded-full transition-all">✕</button>
                                    <div class="space-y-3 pt-1">
                                        <div>
                                            <label class="text-xs text-slate-500 mb-1 block">Title</label>
                                            <input type="text" x-model="item.title" class="w-full bg-[#242437] border-none rounded-lg p-3 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/50">
                                        </div>
                                        <div>
                                            <label class="text-xs text-slate-500 mb-1 block">Description (max 200)</label>
                                            <textarea x-model="item.description" maxlength="200" rows="2" class="w-full bg-[#242437] border-none rounded-lg p-3 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/50 resize-none"></textarea>
                                        </div>
                                        <div>
                                            <label class="text-xs text-slate-500 mb-1 block">Image</label>
                                            <div class="flex items-center gap-3">
                                                <template x-if="item.image">
                                                    <div class="relative w-14 h-14 rounded-lg overflow-hidden border border-white/10 flex-shrink-0 group">
                                                        <img :src="item.image" class="object-cover w-full h-full">
                                                        <button type="button" @click="item.image = ''" class="absolute inset-0 bg-black/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">✕</button>
                                                    </div>
                                                </template>
                                                <label class="flex-1 flex items-center gap-2 p-2.5 bg-[#242437] border border-dashed border-white/10 hover:border-[#a9a4ff]/30 rounded-lg cursor-pointer transition-all text-xs text-slate-500">
                                                    <span class="material-symbols-outlined" style="font-size:16px">add_photo_alternate</span>
                                                    Upload image
                                                    <input type="file" accept="image/*" @change="uploadImage($event, item, 'image')" class="hidden">
                                                </label>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="text-xs text-slate-500 mb-1 block">Button Label</label>
                                                <input type="text" x-model="item.button_text" class="w-full bg-[#242437] border-none rounded-lg p-3 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/50">
                                            </div>
                                            <div>
                                                <label class="text-xs text-slate-500 mb-1 block">Button URL</label>
                                                <input type="text" x-model="item.button_link" class="w-full bg-[#242437] border-none rounded-lg p-3 text-white text-sm focus:ring-2 focus:ring-[#685ef7]/50">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <p x-show="!formData.items || formData.items.length === 0"
                               class="text-sm text-slate-500 italic text-center py-4 bg-[#181828] border border-dashed border-white/5 rounded-xl">No items added yet.</p>
                        </div>

                        <!-- Save (bottom) -->
                        <div class="pt-2">
                            <button type="submit" :disabled="isSaving"
                                    class="w-full py-3 rounded-xl bg-gradient-to-r from-[#685ef7] to-[#914feb] text-white font-bold text-sm shadow-lg shadow-[#685ef7]/20 hover:brightness-110 transition-all disabled:opacity-50">
                                <span x-show="!isSaving">Save Content</span>
                                <span x-show="isSaving">Saving…</span>
                            </button>
                        </div>

                    </form>

                    <!-- Right: tip card -->
                    <div class="hidden xl:flex flex-col gap-4">
                        <div class="bg-[#121220] rounded-2xl p-6 border border-[#a9a4ff]/10 relative overflow-hidden flex-1">
                            <div class="relative z-10">
                                <div class="w-10 h-10 rounded-full bg-[#a9a4ff]/10 flex items-center justify-center mb-4">
                                    <span class="material-symbols-outlined text-[#a9a4ff]">lightbulb</span>
                                </div>
                                <h4 class="text-xs font-bold uppercase tracking-widest text-[#9a94ff] mb-3">Block Tip</h4>
                                <p class="text-sm text-slate-400 leading-relaxed">
                                    Keep your headline under 8 words for maximum impact on mobile devices. Short, punchy text converts better.
                                </p>
                            </div>
                            <div class="absolute -right-4 -bottom-6 opacity-5 pointer-events-none">
                                <span class="material-symbols-outlined text-[#a9a4ff]" style="font-size:9rem">lightbulb</span>
                            </div>
                        </div>
                        <div class="bg-[#121220] rounded-2xl p-6 border border-white/5 relative overflow-hidden">
                            <h4 class="text-xs font-bold uppercase tracking-widest text-[#9a94ff] mb-3">Preview Status</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400">Desktop</span>
                                    <span class="text-[#a9a4ff] font-bold">Optimised</span>
                                </div>
                                <div class="w-full bg-[#181828] rounded-full h-1.5">
                                    <div class="bg-[#a9a4ff] h-1.5 rounded-full w-[95%] shadow-[0_0_8px_rgba(169,164,255,0.4)]"></div>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400">Mobile</span>
                                    <span class="text-[#ff98cd] font-bold">Good</span>
                                </div>
                                <div class="w-full bg-[#181828] rounded-full h-1.5">
                                    <div class="bg-[#ff98cd] h-1.5 rounded-full w-[78%] shadow-[0_0_8px_rgba(255,152,205,0.4)]"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
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

        blockLabels: {
            header: 'Header (Navigation)', hero: 'Hero / Main Banner', about: 'About Us',
            services: 'Services', products: 'Products', gallery: 'Photo Gallery',
            videos: 'Videos', testimonials: 'Testimonials', contact: 'Contact', footer: 'Footer'
        },
        blockDescs: {
            header: 'Top navigation bar with logo.', hero: 'Full-width banner with headline.',
            about: 'Text description with side image.', services: 'Grid of service highlights.',
            products: 'Product showcase cards.', gallery: 'Drag-and-drop photo grid.',
            videos: 'Embedded YouTube videos.', testimonials: 'Quotes and social proof.',
            contact: 'Email form and contact details.', footer: 'Bottom closing section.'
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
                alert(e.message);
            }
        },

        selectBlock(block) {
            this.activeBlock = block;
            this.formData = {
                title: block.config.title || '',
                description: block.config.description || '',
                text: block.config.text || '',
                image: block.config.image || '',
                button_text: block.config.button_text || '',
                button_link: block.config.button_link || '',
                email: block.config.email || '',
                phone: block.config.phone || '',
                is_whatsapp: block.config.is_whatsapp || false,
                items: JSON.parse(JSON.stringify(block.config.items || [])),
                gallery_images: JSON.parse(JSON.stringify(block.config.gallery_images || [])),
                videos: JSON.parse(JSON.stringify(block.config.videos || []))
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
                    alert('Content saved successfully!');
                } else {
                    alert('Error saving content. Please try again.');
                }
            } catch (e) {
                console.error('Save error', e);
                alert('Network error. Please try again.');
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
                    alert(json.error || 'Error uploading image.');
                }
            } catch (e) {
                console.error(e);
                alert('Network error during upload.');
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
                alert('Maximum of 9 photos reached.');
                return;
            }
            if (files.length > remainingSlots) {
                alert(`Only ${remainingSlots} photo(s) selected to respect the 9-photo limit.`);
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
                        alert(`Error uploading ${file.name}: ${json.error || 'Upload failed'}`);
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
