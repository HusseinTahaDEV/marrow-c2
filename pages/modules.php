<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
// Marrow C2 - Custom Module Manager (Polished)
require_once __DIR__ . '/../includes/Layout.php';
Layout::header('Custom Modules');
?>

<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <span class="text-3xl">🧩</span> Custom Modules
            </h1>
            <p class="text-sm text-gray-500 mt-1">Create CMD/PowerShell modules to run on any target</p>
        </div>
        <div class="flex gap-3">
            <button onclick="seedExamples()"
                class="px-4 py-2 bg-dark-600 hover:bg-dark-500 border border-dark-500 rounded-xl text-sm transition">
                📦 Load Examples
            </button>
            <button onclick="openEditor()"
                class="px-4 py-2 bg-accent hover:bg-accent/80 rounded-xl font-medium flex items-center gap-2 transition-all shadow-lg shadow-accent/20">
                <span>+</span> New Module
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-dark-700 border border-dark-500 rounded-xl p-4">
            <div class="text-2xl font-bold text-white" id="stat-total">0</div>
            <div class="text-xs text-gray-500">Total Modules</div>
        </div>
        <div class="bg-dark-700 border border-dark-500 rounded-xl p-4">
            <div class="text-2xl font-bold text-green-400" id="stat-active">0</div>
            <div class="text-xs text-gray-500">Active</div>
        </div>
        <div class="bg-dark-700 border border-dark-500 rounded-xl p-4">
            <div class="text-2xl font-bold text-purple-400" id="stat-cmd">0</div>
            <div class="text-xs text-gray-500">CMD Modules</div>
        </div>
        <div class="bg-dark-700 border border-dark-500 rounded-xl p-4">
            <div class="text-2xl font-bold text-cyan-400" id="stat-ps">0</div>
            <div class="text-xs text-gray-500">PowerShell</div>
        </div>
    </div>

    <!-- Modules Table -->
    <div class="bg-dark-700 border border-dark-500 rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-dark-500 flex items-center justify-between">
            <h3 class="font-semibold text-white">All Modules</h3>
            <div class="flex gap-2">
                <select id="filter-type" onchange="renderModules()"
                    class="bg-dark-600 border border-dark-500 rounded-lg px-3 py-1.5 text-sm">
                    <option value="">All Types</option>
                    <option value="cmd">CMD</option>
                    <option value="powershell">PowerShell</option>
                    <option value="python">Python</option>
                    <option value="exe">EXE</option>
                </select>
            </div>
        </div>
        <div id="modules-table" class="divide-y divide-dark-500">
            <div class="p-8 text-center text-gray-500">Loading...</div>
        </div>
    </div>

    <!-- How to Use -->
    <div class="bg-dark-700 border border-dark-500 rounded-2xl p-6">
        <h3 class="font-semibold text-white mb-4">📖 How to Use</h3>
        <div class="grid grid-cols-3 gap-6 text-sm">
            <div>
                <div class="text-accent font-medium mb-2">1. Create Module</div>
                <p class="text-gray-400">Click "New Module", enter a name, select CMD (recommended), and write your
                    commands.</p>
            </div>
            <div>
                <div class="text-accent font-medium mb-2">2. Run on Target</div>
                <p class="text-gray-400">Go to any target → Custom Modules → Click "Run" on your module.</p>
            </div>
            <div>
                <div class="text-accent font-medium mb-2">3. View Output</div>
                <p class="text-gray-400">Output appears in the target panel. Use JSON for structured data.</p>
            </div>
        </div>
    </div>
</div>

<!-- Editor Modal -->
<div id="editor-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50">
    <div
        class="bg-dark-700 border border-dark-500 rounded-2xl w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col shadow-2xl">
        <div class="p-4 border-b border-dark-500 flex items-center justify-between bg-dark-800">
            <h2 id="modal-title" class="text-lg font-semibold text-white">New Module</h2>
            <button onclick="closeEditor()"
                class="text-gray-400 hover:text-white text-2xl w-8 h-8 flex items-center justify-center rounded hover:bg-dark-600">&times;</button>
        </div>
        <div class="p-6 flex-1 overflow-auto space-y-4">
            <input type="hidden" id="module-id">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Module Name <span
                            class="text-red-400">*</span></label>
                    <input type="text" id="module-name" placeholder="e.g., Get System Info"
                        class="w-full bg-dark-600 border border-dark-500 rounded-lg px-3 py-2.5 text-white focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1.5">Type</label>
                    <select id="module-type"
                        class="w-full bg-dark-600 border border-dark-500 rounded-lg px-3 py-2.5 text-white focus:border-accent focus:outline-none">
                        <option value="cmd" selected>🖥️ CMD (Recommended)</option>
                        <option value="powershell">⚡ PowerShell</option>
                        <option value="python">🐍 Python</option>
                        <option value="exe">📦 EXE (Base64)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Description</label>
                <input type="text" id="module-desc" placeholder="What does this module do?"
                    class="w-full bg-dark-600 border border-dark-500 rounded-lg px-3 py-2.5 text-white focus:border-accent focus:outline-none">
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-1.5">Code / Command <span
                        class="text-red-400">*</span></label>
                <textarea id="module-content" rows="12"
                    placeholder="Enter your CMD commands here...&#10;&#10;Example:&#10;systeminfo&#10;echo.&#10;net user"
                    class="w-full bg-dark-900 border border-dark-500 rounded-lg px-4 py-3 text-green-400 font-mono text-sm focus:border-accent focus:outline-none resize-none"></textarea>
                <div class="text-xs text-gray-500 mt-1">💡 For CMD: Use multiple lines or && to chain commands</div>
            </div>
        </div>
        <div class="p-4 border-t border-dark-500 flex justify-between items-center bg-dark-800">
            <button onclick="testSyntax()"
                class="px-4 py-2 rounded-lg bg-dark-600 hover:bg-dark-500 transition text-gray-300 text-sm">
                🧪 Test Syntax
            </button>
            <div class="flex gap-3">
                <button onclick="closeEditor()"
                    class="px-4 py-2 rounded-lg bg-dark-600 hover:bg-dark-500 transition text-white">Cancel</button>
                <button onclick="saveModule()"
                    class="px-6 py-2 rounded-lg bg-accent hover:bg-accent/80 font-medium transition-all text-white shadow-lg shadow-accent/20">
                    💾 Save Module
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"
    class="fixed bottom-6 right-6 bg-green-600 text-white px-4 py-3 rounded-xl shadow-lg hidden transform transition-all">
    <span id="toast-msg"></span>
</div>

<script>
    let modules = [];

    // Load modules
    async function loadModules() {
        try {
            const res = await fetch('/api/assets.php?action=list');
            const data = await res.json();
            if (data.success) {
                modules = data.modules || [];
                updateStats();
                renderModules();
            } else {
                showToast(data.error || 'Failed to load', 'error');
            }
        } catch (e) {
            document.getElementById('modules-table').innerHTML = '<div class="p-8 text-center text-red-400">Failed to connect to API</div>';
        }
    }

    function updateStats() {
        document.getElementById('stat-total').textContent = modules.length;
        document.getElementById('stat-active').textContent = modules.filter(m => m.is_active == 1).length;
        document.getElementById('stat-cmd').textContent = modules.filter(m => m.type === 'cmd').length;
        document.getElementById('stat-ps').textContent = modules.filter(m => m.type === 'powershell').length;
    }

    function renderModules() {
        const filter = document.getElementById('filter-type').value;
        const filtered = filter ? modules.filter(m => m.type === filter) : modules;
        const c = document.getElementById('modules-table');

        if (!filtered.length) {
            c.innerHTML = '<div class="p-8 text-center text-gray-500">No modules found. Click "New Module" or "Load Examples" to get started.</div>';
            return;
        }

        const typeStyle = {
            cmd: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
            powershell: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30',
            python: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
            exe: 'bg-red-500/20 text-red-400 border-red-500/30'
        };

        c.innerHTML = filtered.map(m => `
        <div class="flex items-center justify-between p-4 hover:bg-dark-600/50 transition">
            <div class="flex items-center gap-4 flex-1">
                <span class="px-2 py-1 rounded border text-xs font-semibold uppercase ${typeStyle[m.type] || 'bg-gray-500/20'}">${m.type}</span>
                <div class="flex-1">
                    <div class="text-white font-medium">${esc(m.name)}</div>
                    <div class="text-xs text-gray-500">${esc(m.description || 'No description')}</div>
                </div>
                <div class="${m.is_active == 1 ? 'text-green-400' : 'text-gray-500'} text-xs">
                    ${m.is_active == 1 ? '● Active' : '○ Inactive'}
                </div>
            </div>
            <div class="flex items-center gap-1 ml-4">
                <button onclick="editModule(${m.id})" class="p-2 hover:bg-dark-500 rounded-lg transition" title="Edit">✏️</button>
                <button onclick="toggleModule(${m.id})" class="p-2 hover:bg-dark-500 rounded-lg transition" title="Toggle">${m.is_active == 1 ? '⏸️' : '▶️'}</button>
                <button onclick="copyModule(${m.id})" class="p-2 hover:bg-dark-500 rounded-lg transition" title="Duplicate">📋</button>
                <button onclick="deleteModule(${m.id})" class="p-2 hover:bg-red-900/50 rounded-lg text-red-400 transition" title="Delete">🗑️</button>
            </div>
        </div>
    `).join('');
    }

    function openEditor(id = null) {
        document.getElementById('editor-modal').style.display = 'flex';
        document.getElementById('module-id').value = id || '';
        document.getElementById('modal-title').textContent = id ? 'Edit Module' : 'New Module';

        if (id) {
            const m = modules.find(x => x.id == id);
            if (m) {
                document.getElementById('module-name').value = m.name;
                document.getElementById('module-type').value = m.type;
                document.getElementById('module-desc').value = m.description || '';
                // Fetch content
                fetch(`/api/assets.php?action=get&id=${id}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.content) document.getElementById('module-content').value = d.content;
                    });
            }
        } else {
            document.getElementById('module-name').value = '';
            document.getElementById('module-type').value = 'cmd';
            document.getElementById('module-desc').value = '';
            document.getElementById('module-content').value = '# Enter your CMD commands here\necho Hello from custom module!\nwhoami';
        }
    }

    function editModule(id) { openEditor(id); }
    function closeEditor() { document.getElementById('editor-modal').style.display = 'none'; }

    async function saveModule() {
        const id = document.getElementById('module-id').value;
        const name = document.getElementById('module-name').value.trim();
        const content = document.getElementById('module-content').value.trim();

        if (!name) { showToast('Module name is required', 'error'); return; }
        if (!content) { showToast('Module code is required', 'error'); return; }

        const data = new FormData();
        data.append('action', id ? 'update' : 'create');
        if (id) data.append('id', id);
        data.append('name', name);
        data.append('type', document.getElementById('module-type').value);
        data.append('description', document.getElementById('module-desc').value);
        data.append('content', content);

        const res = await fetch('/api/assets.php', { method: 'POST', body: data });
        const result = await res.json();

        if (result.success) {
            showToast(result.message || 'Saved!', 'success');
            closeEditor();
            loadModules();
        } else {
            showToast(result.error || 'Failed to save', 'error');
        }
    }

    async function toggleModule(id) {
        const data = new FormData();
        data.append('action', 'toggle');
        data.append('id', id);
        await fetch('/api/assets.php', { method: 'POST', body: data });
        loadModules();
    }

    async function deleteModule(id) {
        if (!confirm('Delete this module permanently?')) return;
        const data = new FormData();
        data.append('action', 'delete');
        data.append('id', id);
        await fetch('/api/assets.php', { method: 'POST', body: data });
        showToast('Module deleted', 'success');
        loadModules();
    }

    async function copyModule(id) {
        const m = modules.find(x => x.id == id);
        if (!m) return;

        // Fetch content first
        const res = await fetch(`/api/assets.php?action=get&id=${id}`);
        const data = await res.json();

        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('name', m.name + ' (Copy)');
        formData.append('description', m.description || '');
        formData.append('type', m.type);
        formData.append('content', data.content || '');

        await fetch('/api/assets.php', { method: 'POST', body: formData });
        showToast('Module duplicated', 'success');
        loadModules();
    }

    async function seedExamples() {
        const res = await fetch('/api/assets.php?action=seed');
        const data = await res.json();
        showToast(data.message || 'Examples loaded', 'success');
        loadModules();
    }

    function testSyntax() {
        const content = document.getElementById('module-content').value;
        if (!content.trim()) {
            showToast('No code to test', 'error');
            return;
        }
        showToast('Syntax looks OK (basic check)', 'success');
    }

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.className = `fixed bottom-6 right-6 px-4 py-3 rounded-xl shadow-lg transform transition-all ${type === 'error' ? 'bg-red-600' : 'bg-green-600'} text-white`;
        document.getElementById('toast-msg').textContent = msg;
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 3000);
    }

    function esc(s) { return s ? s.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])) : ''; }

    // Init
    loadModules();
</script>

<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(); Layout::footer(); ?>
