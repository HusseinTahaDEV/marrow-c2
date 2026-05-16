<?php
// Custom Modules UI for Target Panel - Execute modules on this target
?>
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <span>🧩</span> Custom Modules
        </h2>
        <a href="/pages/modules.php" target="_blank"
            class="text-xs text-accent hover:underline flex items-center gap-1">
            Manage Modules <span>→</span>
        </a>
    </div>

    <div id="cm-list" class="space-y-2">
        <div class="text-center text-gray-500 py-8">
            <div class="animate-pulse">Loading modules...</div>
        </div>
    </div>

    <!-- Output Panel -->
    <div id="cm-output" class="hidden">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold text-gray-400">Output</h3>
            <button onclick="document.getElementById('cm-output').classList.add('hidden')"
                class="text-gray-500 hover:text-white text-xs">✕ Close</button>
        </div>
        <pre id="cm-output-content"
            class="bg-dark-900 rounded-xl p-4 text-sm text-green-400 font-mono max-h-80 overflow-auto whitespace-pre-wrap border border-dark-500"></pre>
    </div>
</div>

<script>
    const CM = {
        modules: [],

        async load() {
            try {
                const res = await fetch('/api/assets.php?action=list');
                const data = await res.json();
                if (data.success) {
                    this.modules = (data.modules || []).filter(m => m.is_active == 1);
                    this.render();
                } else {
                    document.getElementById('cm-list').innerHTML = '<div class="text-red-400 text-center py-4">' + (data.error || 'Failed') + '</div>';
                }
            } catch (e) {
                document.getElementById('cm-list').innerHTML = '<div class="text-red-400 text-center py-4">Connection error</div>';
            }
        },

        render() {
            const c = document.getElementById('cm-list');

            if (!this.modules.length) {
                c.innerHTML = `
                <div class="text-gray-500 text-center py-8">
                    <div class="text-2xl mb-2">📦</div>
                    <div>No active modules</div>
                    <a href="/pages/modules.php" target="_blank" class="text-accent hover:underline text-sm">Create one →</a>
                </div>
            `;
                return;
            }

            const typeStyle = {
                cmd: 'bg-purple-500/20 text-purple-400',
                powershell: 'bg-cyan-500/20 text-cyan-400',
                python: 'bg-blue-500/20 text-blue-400',
                exe: 'bg-red-500/20 text-red-400'
            };

            c.innerHTML = this.modules.map(m => `
            <div class="flex items-center justify-between p-3 bg-dark-600 rounded-xl border border-dark-500 hover:border-accent/50 transition group">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase ${typeStyle[m.type] || 'bg-gray-500/20'}">${m.type}</span>
                    <div class="min-w-0">
                        <div class="text-white font-medium text-sm truncate">${this.esc(m.name)}</div>
                        <div class="text-xs text-gray-500 truncate">${this.esc(m.description || 'No description')}</div>
                    </div>
                </div>
                <button onclick="CM.execute(${m.id}, '${this.esc(m.name)}')" 
                    class="px-4 py-1.5 bg-accent hover:bg-accent/80 rounded-lg text-sm font-medium transition flex items-center gap-1.5 opacity-80 group-hover:opacity-100">
                    <span>▶️</span> Run
                </button>
            </div>
        `).join('');
        },

        execute(moduleId, moduleName) {
            // Show loading state
            document.getElementById('cm-output').classList.remove('hidden');
            document.getElementById('cm-output-content').innerHTML =
                '<span class="text-yellow-400">⏳ Executing "' + moduleName + '" on target...</span>\n\nWaiting for response...';

            // Queue the command
            if (typeof queueCommand === 'function') {
                queueCommand('execute_custom', moduleId.toString());
            } else {
                this.showOutput('Error: queueCommand not available');
            }
        },

        handleResult(data) {
            document.getElementById('cm-output').classList.remove('hidden');
            const output = document.getElementById('cm-output-content');

            try {
                const parsed = typeof data === 'string' ? JSON.parse(data) : data;

                if (parsed.error) {
                    output.innerHTML = '<span class="text-red-400">❌ Error:</span>\n' + parsed.error;
                } else if (parsed.output) {
                    output.innerHTML = '<span class="text-green-400">✅ Success:</span>\n\n' + parsed.output;
                } else {
                    output.textContent = JSON.stringify(parsed, null, 2);
                }
            } catch {
                output.textContent = data;
            }
        },

        showOutput(text) {
            document.getElementById('cm-output').classList.remove('hidden');
            document.getElementById('cm-output-content').textContent = text;
        },

        esc(s) {
            return s ? String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])) : '';
        }
    };

    // Load on init
    CM.load();
</script>