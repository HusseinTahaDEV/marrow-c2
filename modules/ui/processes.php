<?php
// Marrow C2 - Processes Module
// Sortable, filterable process list with multi-select kill
?>
<div id="proc-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <span class="text-white font-medium">⚙️ Processes</span>
            <span id="proc-count" class="text-xs text-gray-500">0 running</span>
            <span id="proc-selected" class="text-xs text-[#6366f1] hidden">0 selected</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <input type="text" id="proc-filter" placeholder="Search..."
                    class="w-48 bg-[#1a1f2e] border border-[#1e2433] rounded-lg pl-8 pr-3 py-2 text-sm focus:outline-none focus:border-[#6366f1]"
                    oninput="PROC.filter()">
                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500">🔍</span>
            </div>
            <button onclick="PROC.refresh()" class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm">
                🔄 Refresh
            </button>
            <button id="proc-kill-btn" onclick="PROC.killSelected()"
                class="px-3 py-2 bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-lg text-sm hidden">
                💀 Kill Selected (<span id="proc-kill-count">0</span>)
            </button>
            <button onclick="PROC.runNew()"
                class="px-3 py-2 bg-[#10b981] hover:bg-[#0ea573] text-white rounded-lg text-sm">
                ▶ Run New
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="flex-1 overflow-auto">
        <table class="w-full">
            <thead class="bg-[#0a0f1a] sticky top-0 z-10">
                <tr class="text-left text-xs text-gray-400 uppercase tracking-wider">
                    <th class="px-3 py-3 w-10">
                        <input type="checkbox" id="proc-select-all" onchange="PROC.toggleAll(this.checked)"
                            class="w-4 h-4 rounded bg-[#1a1f2e] border-[#1e2433] cursor-pointer accent-[#6366f1]">
                    </th>
                    <th class="px-4 py-3 cursor-pointer hover:text-white" onclick="PROC.sort('id')">
                        PID <span id="sort-id" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3 cursor-pointer hover:text-white" onclick="PROC.sort('name')">
                        Name <span id="sort-name" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3 cursor-pointer hover:text-white text-right" onclick="PROC.sort('cpu')">
                        CPU <span id="sort-cpu" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3 cursor-pointer hover:text-white text-right" onclick="PROC.sort('mem')">
                        Memory <span id="sort-mem" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3">Path</th>
                    <th class="px-4 py-3 w-20"></th>
                </tr>
            </thead>
            <tbody id="proc-body" class="divide-y divide-[#1e2433]">
            </tbody>
        </table>
    </div>

    <!-- Summary Bar -->
    <div
        class="h-10 bg-[#0a0f1a] border-t border-[#1e2433] flex items-center justify-between px-4 text-xs text-gray-500">
        <span id="proc-summary">0 processes • 0% CPU • 0 MB Memory</span>
        <span id="proc-updated">Never updated</span>
    </div>
</div>

<style>
    #proc-body tr {
        transition: background 0.15s;
    }

    #proc-body tr:hover {
        background: rgba(99, 102, 241, 0.05);
    }

    #proc-body tr.selected {
        background: rgba(99, 102, 241, 0.15);
    }

    .proc-cpu-bar {
        width: 60px;
        height: 6px;
        background: #1e2433;
        border-radius: 3px;
        overflow: hidden;
    }

    .proc-cpu-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s;
    }
</style>

<script>
    const PROC = {
        processes: [],
        selected: new Set(),
        sortField: 'cpu',
        sortDir: 'desc',

        refresh() {
            this.selected.clear();
            this.updateSelectionUI();
            document.getElementById('proc-body').innerHTML = `
            <tr><td colspan="7" class="text-center py-12 text-gray-500">
                <div class="animate-spin text-2xl mb-2">⏳</div>Loading...
            </td></tr>
        `;
            queueCommand('processes');
        },

        render(data) {
            try {
                this.processes = JSON.parse(data);
                this.updateTable();
                document.getElementById('proc-updated').textContent = 'Updated: ' + new Date().toLocaleTimeString();
            } catch (e) {
                document.getElementById('proc-body').innerHTML = `
                <tr><td colspan="7" class="p-4 text-red-400">${data}</td></tr>
            `;
            }
        },

        updateTable() {
            const filter = (document.getElementById('proc-filter')?.value || '').toLowerCase();
            let filtered = this.processes.filter(p =>
                (p.name || '').toLowerCase().includes(filter) ||
                String(p.id).includes(filter)
            );

            // Sort
            filtered.sort((a, b) => {
                let va = a[this.sortField];
                let vb = b[this.sortField];
                if (typeof va === 'string') va = va.toLowerCase();
                if (typeof vb === 'string') vb = vb.toLowerCase();
                if (va < vb) return this.sortDir === 'asc' ? -1 : 1;
                if (va > vb) return this.sortDir === 'asc' ? 1 : -1;
                return 0;
            });

            document.getElementById('proc-count').textContent = `${filtered.length} running`;

            // Calculate totals
            const totalCpu = this.processes.reduce((s, p) => s + (p.cpu || 0), 0);
            const totalMem = this.processes.reduce((s, p) => s + (p.mem || 0), 0);
            document.getElementById('proc-summary').textContent =
                `${this.processes.length} processes • ${totalCpu.toFixed(1)}% CPU • ${totalMem.toFixed(0)} MB Memory`;

            // Render table
            const body = document.getElementById('proc-body');
            body.innerHTML = filtered.map(p => {
                const cpuColor = p.cpu > 50 ? '#ef4444' : p.cpu > 20 ? '#f59e0b' : '#10b981';
                const memColor = p.mem > 500 ? '#ef4444' : p.mem > 200 ? '#f59e0b' : '#3b82f6';
                const isSelected = this.selected.has(p.id);

                return `
                <tr class="${isSelected ? 'selected' : ''}" data-pid="${p.id}">
                    <td class="px-3 py-3">
                        <input type="checkbox" ${isSelected ? 'checked' : ''} 
                            onchange="PROC.toggleSelect(${p.id}, this.checked)"
                            class="w-4 h-4 rounded bg-[#1a1f2e] border-[#1e2433] cursor-pointer accent-[#6366f1]">
                    </td>
                    <td class="px-4 py-3 font-mono text-sm text-gray-400">${p.id}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">${this.getIcon(p.name)}</span>
                            <span class="text-white font-medium">${this.esc(p.name)}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <div class="proc-cpu-bar">
                                <div class="proc-cpu-bar-fill" style="width: ${Math.min(p.cpu || 0, 100)}%; background: ${cpuColor}"></div>
                            </div>
                            <span class="text-sm" style="color: ${cpuColor}">${(p.cpu || 0).toFixed(1)}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-sm" style="color: ${memColor}">${(p.mem || 0).toFixed(0)} MB</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 truncate max-w-xs" title="${this.esc(p.path)}">${this.esc(p.path) || '-'}</td>
                    <td class="px-4 py-3">
                        <button onclick="PROC.kill(${p.id}, '${this.esc(p.name).replace(/'/g, "\\'")}')" 
                                class="px-2 py-1 bg-[#ef4444]/10 hover:bg-[#ef4444]/20 text-[#ef4444] rounded text-xs">
                            Kill
                        </button>
                    </td>
                </tr>
            `;
            }).join('');
        },

        toggleSelect(pid, checked) {
            if (checked) {
                this.selected.add(pid);
            } else {
                this.selected.delete(pid);
            }
            this.updateSelectionUI();
            // Update row highlighting
            const row = document.querySelector(`tr[data-pid="${pid}"]`);
            if (row) row.classList.toggle('selected', checked);
        },

        toggleAll(checked) {
            const checkboxes = document.querySelectorAll('#proc-body input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = checked;
                const pid = parseInt(cb.closest('tr').dataset.pid);
                if (checked) this.selected.add(pid);
                else this.selected.delete(pid);
                cb.closest('tr').classList.toggle('selected', checked);
            });
            this.updateSelectionUI();
        },

        updateSelectionUI() {
            const count = this.selected.size;
            const selSpan = document.getElementById('proc-selected');
            const killBtn = document.getElementById('proc-kill-btn');
            const killCount = document.getElementById('proc-kill-count');

            if (count > 0) {
                selSpan.textContent = `${count} selected`;
                selSpan.classList.remove('hidden');
                killBtn.classList.remove('hidden');
                killCount.textContent = count;
            } else {
                selSpan.classList.add('hidden');
                killBtn.classList.add('hidden');
            }

            // Update select-all checkbox
            const allCb = document.getElementById('proc-select-all');
            const visibleCount = document.querySelectorAll('#proc-body tr').length;
            if (allCb) allCb.checked = count > 0 && count === visibleCount;
        },

        killSelected() {
            if (this.selected.size === 0) return;
            const pids = Array.from(this.selected);
            if (!confirm(`Kill ${pids.length} selected process(es)?`)) return;

            // Kill each process
            pids.forEach(pid => {
                queueCommand('kill_process', String(pid));
            });

            this.selected.clear();
            this.updateSelectionUI();
            setTimeout(() => this.refresh(), 1500);
        },

        sort(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = 'desc';
            }

            ['id', 'name', 'cpu', 'mem'].forEach(f => {
                const el = document.getElementById(`sort-${f}`);
                if (el) el.textContent = f === field ? (this.sortDir === 'asc' ? '↑' : '↓') : '↕';
            });

            this.updateTable();
        },

        filter() {
            this.updateTable();
        },

        kill(pid, name) {
            if (!confirm(`Kill process "${name}" (PID: ${pid})?`)) return;
            queueCommand('kill_process', String(pid));
            setTimeout(() => this.refresh(), 1000);
        },

        runNew() {
            const cmd = prompt('Enter program path or command to run:');
            if (!cmd) return;
            queueCommand('run', cmd);
            setTimeout(() => this.refresh(), 1000);
        },

        getIcon(name) {
            const n = (name || '').toLowerCase();
            if (n.includes('chrome')) return '🌐';
            if (n.includes('firefox')) return '🦊';
            if (n.includes('edge')) return '🌊';
            if (n.includes('code') || n.includes('vscode')) return '💻';
            if (n.includes('explorer')) return '📂';
            if (n.includes('discord')) return '💬';
            if (n.includes('spotify')) return '🎵';
            if (n.includes('steam')) return '🎮';
            if (n.includes('powershell') || n.includes('pwsh')) return '📜';
            if (n.includes('cmd')) return '⬛';
            if (n.includes('svc') || n.includes('service')) return '⚙️';
            if (n.includes('system')) return '🔧';
            return '📦';
        },

        esc(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }
    };
</script>