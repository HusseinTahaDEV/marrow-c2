<?php
// Marrow C2 - Shared Layout Engine
class Layout
{
    public static function header($title = 'Dashboard', $bodyClass = '')
    {
        $nav = [
            ['/', 'Dashboard', '📊'],
            ['/pages/targets.php', 'Targets', '🎯'],
            ['/pages/modules.php', 'Modules', '📦'],
            ['/pages/builder.php', 'Builder', '🔧'],
        ];
        $current = $_SERVER['REQUEST_URI'];
        ?>
        <!DOCTYPE html>
        <html lang="en" class="dark">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>MARROW C2 - <?= htmlspecialchars($title) ?></title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <link
                href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Inter:wght@400;500;600;700&display=swap"
                rel="stylesheet">
            <script>
                tailwind.config = {
                    darkMode: 'class',
                    theme: {
                        extend: {
                            colors: {
                                primary: '#6366f1',
                                accent: '#10b981',
                                danger: '#ef4444',
                                warning: '#f59e0b',
                                info: '#3b82f6',
                                dark: { 900: '#030712', 800: '#0a0a0f', 700: '#111118', 600: '#1a1a24', 500: '#252530' }
                            },
                            fontFamily: { sans: ['Inter', 'system-ui'], mono: ['JetBrains Mono', 'monospace'] }
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
                    background: #444;
                    border-radius: 3px;
                }

                .glass {
                    background: rgba(10, 10, 15, 0.8);
                    backdrop-filter: blur(20px);
                }

                .glow-green {
                    box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
                }

                .glow-primary {
                    box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
                }
            </style>
        </head>

        <body class="bg-dark-900 text-gray-200 font-sans antialiased <?= $bodyClass ?>">
            <div class="flex h-screen overflow-hidden">
                <!-- Sidebar -->
                <nav class="w-64 bg-dark-800 border-r border-dark-500 flex flex-col">
                    <div class="p-5 border-b border-dark-500">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-accent flex items-center justify-center">
                                <span class="text-white font-bold text-lg">M</span>
                            </div>
                            <div>
                                <div class="text-white font-bold">MARROW</div>
                                <div class="text-[10px] text-gray-500 tracking-widest">C2 FRAMEWORK</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 p-4 space-y-1">
                        <?php foreach ($nav as $item):
                            $isActive = $current === $item[0] || ($item[0] !== '/' && strpos($current, $item[0]) === 0);
                            ?>
                            <a href="<?= $item[0] ?>"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $isActive ? 'bg-primary/10 text-primary border border-primary/20' : 'text-gray-400 hover:bg-dark-600 hover:text-white' ?>">
                                <span><?= $item[2] ?></span>
                                <span class="font-medium"><?= $item[1] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-4 border-t border-dark-500">
                        <div class="flex items-center gap-2 text-sm">
                            <div id="global-status" class="w-2 h-2 rounded-full bg-accent animate-pulse"></div>
                            <span class="text-gray-400">Online: <span id="global-online">0</span></span>
                        </div>
                    </div>
                </nav>

                <!-- Main -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <header
                        class="h-14 bg-dark-800 border-b border-dark-500 flex items-center justify-between px-6 flex-shrink-0">
                        <h1 class="text-lg font-semibold text-white"><?= htmlspecialchars($title) ?></h1>
                        <div class="flex items-center gap-4">
                            <span class="text-xs text-gray-500" id="current-time"></span>
                        </div>
                    </header>
                    <main class="flex-1 overflow-auto bg-dark-900 p-6">
                        <?php
    }

    public static function footer()
    {
        ?>
                    </main>
                </div>
            </div>
            <script>
                // Global polling
                async function globalPoll() {
                    try {
                        const s = await fetch('/api/dashboard.php?action=stats').then(r => r.json());
                        document.getElementById('global-online').textContent = s.online;
                    } catch (e) { }
                }
                setInterval(globalPoll, 2000);
                globalPoll();

                // Time
                setInterval(() => {
                    document.getElementById('current-time').textContent = new Date().toLocaleTimeString();
                }, 1000);
            </script>
        </body>

        </html>
        <?php
    }
}
