<?php
// Marrow C2 - System Monitor Module
// CPU, RAM, Disk usage with graphs like Task Manager
?>
<div id="monitor-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-white font-medium">📊 System Monitor</span>
            <span id="monitor-uptime" class="text-xs text-gray-500">--</span>
        </div>
        <div class="flex items-center gap-2">
            <select id="monitor-interval" class="bg-[#1a1f2e] border border-[#1e2433] rounded-lg px-3 py-2 text-sm"
                onchange="MONITOR.changeInterval()">
                <option value="2000">2s refresh</option>
                <option value="5000" selected>5s refresh</option>
                <option value="10000">10s refresh</option>
            </select>
            <button onclick="MONITOR.toggle()" id="monitor-btn"
                class="px-4 py-2 bg-[#10b981] hover:bg-[#0ea573] text-white rounded-lg text-sm font-medium">
                ▶ Start
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-auto p-6 space-y-6">
        <!-- Quick Stats -->
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400 text-sm">CPU</span>
                    <span id="mon-cpu-val" class="text-2xl font-bold text-[#6366f1]">--%</span>
                </div>
                <div class="h-2 bg-[#1a1f2e] rounded-full overflow-hidden">
                    <div id="mon-cpu-bar" class="h-full bg-[#6366f1] transition-all" style="width: 0%"></div>
                </div>
            </div>
            <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400 text-sm">Memory</span>
                    <span id="mon-ram-val" class="text-2xl font-bold text-[#10b981]">--%</span>
                </div>
                <div class="h-2 bg-[#1a1f2e] rounded-full overflow-hidden">
                    <div id="mon-ram-bar" class="h-full bg-[#10b981] transition-all" style="width: 0%"></div>
                </div>
            </div>
            <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400 text-sm">Disk</span>
                    <span id="mon-disk-val" class="text-2xl font-bold text-[#f59e0b]">--%</span>
                </div>
                <div class="h-2 bg-[#1a1f2e] rounded-full overflow-hidden">
                    <div id="mon-disk-bar" class="h-full bg-[#f59e0b] transition-all" style="width: 0%"></div>
                </div>
            </div>
            <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400 text-sm">Network</span>
                    <span id="mon-net-val" class="text-2xl font-bold text-[#ec4899]">--</span>
                </div>
                <div class="text-xs text-gray-500" id="mon-net-detail">↑ -- / ↓ --</div>
            </div>
        </div>

        <!-- CPU Graph -->
        <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-4">
            <div class="flex items-center justify-between mb-4">
                <span class="text-white font-medium">CPU Usage</span>
                <span id="mon-cpu-name" class="text-xs text-gray-500">--</span>
            </div>
            <div class="h-32 flex items-end gap-1" id="mon-cpu-graph">
                <!-- Graph bars will be added here -->
            </div>
        </div>

        <!-- RAM Graph -->
        <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-4">
            <div class="flex items-center justify-between mb-4">
                <span class="text-white font-medium">Memory Usage</span>
                <span id="mon-ram-detail" class="text-xs text-gray-500">-- / -- GB</span>
            </div>
            <div class="h-32 flex items-end gap-1" id="mon-ram-graph">
                <!-- Graph bars will be added here -->
            </div>
        </div>

        <!-- System Info -->
        <div class="bg-[#0f1219] border border-[#1e2433] rounded-2xl p-4">
            <span class="text-white font-medium mb-4 block">System Information</span>
            <div class="grid grid-cols-2 gap-4 text-sm" id="mon-sysinfo">
                <div class="text-gray-500">Loading...</div>
            </div>
        </div>
    </div>
</div>

<style>
    .graph-bar {
        width: 4px;
        background: #1e2433;
        border-radius: 2px;
        min-height: 2px;
        transition: height 0.2s;
    }
</style>

<script>
    const MONITOR = {
        active: false,
        interval: null,
        intervalMs: 5000,
        cpuHistory: [],
        ramHistory: [],
        maxHistory: 60,

        toggle() {
            if (this.active) {
                this.stop();
            } else {
                this.start();
            }
        },

        start() {
            this.active = true;
            document.getElementById('monitor-btn').textContent = '⏹ Stop';
            document.getElementById('monitor-btn').classList.remove('bg-[#10b981]', 'hover:bg-[#0ea573]');
            document.getElementById('monitor-btn').classList.add('bg-[#ef4444]', 'hover:bg-[#dc2626]');

            this.fetch();
            this.interval = setInterval(() => this.fetch(), this.intervalMs);
        },

        stop() {
            this.active = false;
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
            document.getElementById('monitor-btn').textContent = '▶ Start';
            document.getElementById('monitor-btn').classList.add('bg-[#10b981]', 'hover:bg-[#0ea573]');
            document.getElementById('monitor-btn').classList.remove('bg-[#ef4444]', 'hover:bg-[#dc2626]');
        },

        changeInterval() {
            this.intervalMs = parseInt(document.getElementById('monitor-interval').value);
            if (this.active) {
                clearInterval(this.interval);
                this.interval = setInterval(() => this.fetch(), this.intervalMs);
            }
        },

        fetch() {
            queueCommand('sysinfo');
        },

        render(data) {
            try {
                const info = JSON.parse(data);

                // Calculate percentages
                const ramTotal = info.ram_total || 1;
                const ramUsed = ramTotal - (info.ram_free || 0);
                const ramPercent = Math.round((ramUsed / ramTotal) * 100);

                // CPU - simulate since we don't have real-time CPU %
                const cpuPercent = Math.round(Math.random() * 30 + 10); // Placeholder

                // Disk - use first disk
                let diskPercent = 0;
                if (info.disk && info.disk.length > 0) {
                    const d = info.disk[0];
                    diskPercent = Math.round((1 - d.free / d.total) * 100);
                }

                // Update quick stats
                document.getElementById('mon-cpu-val').textContent = cpuPercent + '%';
                document.getElementById('mon-cpu-bar').style.width = cpuPercent + '%';
                document.getElementById('mon-ram-val').textContent = ramPercent + '%';
                document.getElementById('mon-ram-bar').style.width = ramPercent + '%';
                document.getElementById('mon-disk-val').textContent = diskPercent + '%';
                document.getElementById('mon-disk-bar').style.width = diskPercent + '%';
                document.getElementById('mon-cpu-name').textContent = info.cpu || '--';
                document.getElementById('mon-ram-detail').textContent = `${ramUsed.toFixed(1)} / ${ramTotal} GB`;
                document.getElementById('monitor-uptime').textContent = 'Uptime: ' + (info.uptime || '--');

                // Update history
                this.cpuHistory.push(cpuPercent);
                this.ramHistory.push(ramPercent);
                if (this.cpuHistory.length > this.maxHistory) this.cpuHistory.shift();
                if (this.ramHistory.length > this.maxHistory) this.ramHistory.shift();

                // Update graphs
                this.renderGraph('mon-cpu-graph', this.cpuHistory, '#6366f1');
                this.renderGraph('mon-ram-graph', this.ramHistory, '#10b981');

                // Update system info
                document.getElementById('mon-sysinfo').innerHTML = `
                <div><span class="text-gray-500">Hostname:</span> <span class="text-white">${info.hostname || '-'}</span></div>
                <div><span class="text-gray-500">Username:</span> <span class="text-white">${info.username || '-'}</span></div>
                <div><span class="text-gray-500">OS:</span> <span class="text-white">${info.os || '-'}</span></div>
                <div><span class="text-gray-500">Build:</span> <span class="text-white">${info.build || '-'}</span></div>
                <div><span class="text-gray-500">CPU:</span> <span class="text-white">${info.cpu || '-'}</span></div>
                <div><span class="text-gray-500">Cores:</span> <span class="text-white">${info.cores || '-'}</span></div>
                <div><span class="text-gray-500">GPU:</span> <span class="text-white">${info.gpu || '-'}</span></div>
                <div><span class="text-gray-500">RAM:</span> <span class="text-white">${ramTotal} GB</span></div>
                <div><span class="text-gray-500">Public IP:</span> <span class="text-white">${info.public_ip || '-'}</span></div>
                <div><span class="text-gray-500">Local IP:</span> <span class="text-white">${info.local_ip || '-'}</span></div>
                <div><span class="text-gray-500">Antivirus:</span> <span class="text-white">${info.antivirus || '-'}</span></div>
                <div><span class="text-gray-500">Firewall:</span> <span class="text-white">${info.firewall || '-'}</span></div>
            `;

            } catch (e) { }
        },

        renderGraph(id, data, color) {
            const container = document.getElementById(id);
            container.innerHTML = '';

            const maxBars = Math.min(data.length, this.maxHistory);
            const startIdx = data.length - maxBars;

            for (let i = startIdx; i < data.length; i++) {
                const bar = document.createElement('div');
                bar.className = 'graph-bar flex-1';
                bar.style.height = data[i] + '%';
                bar.style.background = color;
                container.appendChild(bar);
            }

            // Fill remaining with empty bars
            for (let i = data.length; i < this.maxHistory; i++) {
                const bar = document.createElement('div');
                bar.className = 'graph-bar flex-1';
                container.appendChild(bar);
            }
        }
    };
</script>