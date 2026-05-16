<?php
// Marrow C2 - Installed Apps Module
// Sortable app list with uninstall capability
?>
<div id="apps-container" class="h-full flex flex-col bg-[#030712]">
    <!-- Toolbar -->
    <div class="h-12 bg-[#0a0f1a] border-b border-[#1e2433] flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <span class="text-white font-medium">📦 Installed Apps</span>
            <span id="apps-count" class="text-xs text-gray-500">0 apps</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <input type="text" id="apps-search" placeholder="Search apps..."
                    class="w-56 bg-[#1a1f2e] border border-[#1e2433] rounded-lg pl-8 pr-3 py-2 text-sm focus:outline-none focus:border-[#6366f1]"
                    oninput="APPS.filter()">
                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500">🔍</span>
            </div>
            <button onclick="APPS.refresh()"
                class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm text-gray-300">
                🔄 Refresh
            </button>
            <button onclick="APPS.exportList()"
                class="px-3 py-2 bg-[#1a1f2e] hover:bg-[#252b3d] rounded-lg text-sm text-gray-300">
                💾 Export
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="flex-1 overflow-auto">
        <table class="w-full">
            <thead class="bg-[#0a0f1a] sticky top-0 z-10">
                <tr class="text-left text-xs text-gray-400 uppercase tracking-wider">
                    <th class="px-4 py-3 cursor-pointer hover:text-white" onclick="APPS.sort('name')">
                        Name <span id="apps-sort-name" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3 cursor-pointer hover:text-white" onclick="APPS.sort('publisher')">
                        Publisher <span id="apps-sort-publisher" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3 cursor-pointer hover:text-white" onclick="APPS.sort('version')">
                        Version <span id="apps-sort-version" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3 cursor-pointer hover:text-white" onclick="APPS.sort('date')">
                        Install Date <span id="apps-sort-date" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3 cursor-pointer hover:text-white" onclick="APPS.sort('size')">
                        Size <span id="apps-sort-size" class="text-gray-600">↕</span>
                    </th>
                    <th class="px-4 py-3 w-32"></th>
                </tr>
            </thead>
            <tbody id="apps-body" class="divide-y divide-[#1e2433]">
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-500">
                        <div class="text-4xl mb-4 opacity-30">📦</div>
                        <p>Click "Refresh" to load installed apps</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div
        class="h-10 bg-[#0a0f1a] border-t border-[#1e2433] flex items-center justify-between px-4 text-xs text-gray-500">
        <span id="apps-summary">0 apps</span>
        <span id="apps-updated">Never updated</span>
    </div>
</div>

<style>
    #apps-body tr {
        transition: background 0.15s;
    }

    #apps-body tr:hover {
        background: rgba(99, 102, 241, 0.05);
    }

    .app-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%);
        border-radius: 8px;
        font-size: 16px;
    }
</style>

<script>
    const APPS = {
        apps: [],
        sortField: 'name',
        sortDir: 'asc',

        refresh() {
            document.getElementById('apps-body').innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-12 text-gray-500">
                    <div class="animate-spin text-3xl mb-4">⏳</div>
                    <p>Loading installed apps...</p>
                </td>
            </tr>
        `;
            queueCommand('installed');
        },

        render(data) {
            try {
                this.apps = JSON.parse(data);
                this.updateTable();
                document.getElementById('apps-updated').textContent = 'Updated: ' + new Date().toLocaleTimeString();
            } catch (e) {
                document.getElementById('apps-body').innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-12 text-red-400">
                        Failed to parse apps data
                    </td>
                </tr>
            `;
            }
        },

        updateTable() {
            const search = (document.getElementById('apps-search')?.value || '').toLowerCase();
            let filtered = this.apps.filter(app =>
                (app.name || '').toLowerCase().includes(search) ||
                (app.publisher || '').toLowerCase().includes(search)
            );

            // Sort
            filtered.sort((a, b) => {
                let va = a[this.sortField] || '';
                let vb = b[this.sortField] || '';
                if (typeof va === 'string') va = va.toLowerCase();
                if (typeof vb === 'string') vb = vb.toLowerCase();
                if (va < vb) return this.sortDir === 'asc' ? -1 : 1;
                if (va > vb) return this.sortDir === 'asc' ? 1 : -1;
                return 0;
            });

            document.getElementById('apps-count').textContent = `${filtered.length} apps`;
            document.getElementById('apps-summary').textContent = `${this.apps.length} total • ${filtered.length} shown`;

            const body = document.getElementById('apps-body');

            if (filtered.length === 0) {
                body.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-500">
                        No apps match your search
                    </td>
                </tr>
            `;
                return;
            }

            body.innerHTML = filtered.map(app => `
            <tr>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="app-icon">${this.getIcon(app.name)}</div>
                        <span class="text-white font-medium">${this.esc(app.name)}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-400 text-sm">${this.esc(app.publisher) || '-'}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-500">${app.version || '-'}</td>
                <td class="px-4 py-3 text-xs text-gray-500">${this.formatDate(app.date)}</td>
                <td class="px-4 py-3 text-xs text-gray-500">${this.formatSize(app.size)}</td>
                <td class="px-4 py-3">
                    <button onclick="APPS.uninstall('${this.esc(app.name).replace(/'/g, "\\'")}')" 
                            class="px-3 py-1.5 bg-[#ef4444]/10 hover:bg-[#ef4444]/20 text-[#ef4444] rounded-lg text-xs font-medium">
                        Uninstall
                    </button>
                </td>
            </tr>
        `).join('');
        },

        sort(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = 'asc';
            }

            // Update indicators
            ['name', 'publisher', 'version', 'date', 'size'].forEach(f => {
                const el = document.getElementById(`apps-sort-${f}`);
                if (el) el.textContent = f === field ? (this.sortDir === 'asc' ? '↑' : '↓') : '↕';
            });

            this.updateTable();
        },

        filter() {
            this.updateTable();
        },

        uninstall(name) {
            if (!confirm(`Uninstall "${name}"?\n\nThis may require admin privileges and user interaction on the target machine.`)) return;
            queueCommand('uninstall', name);
        },

        exportList() {
            let text = 'Installed Applications\n';
            text += '='.repeat(50) + '\n\n';

            this.apps.forEach(app => {
                text += `Name: ${app.name}\n`;
                text += `Publisher: ${app.publisher || '-'}\n`;
                text += `Version: ${app.version || '-'}\n`;
                text += `Date: ${app.date || '-'}\n`;
                text += `Size: ${app.size ? app.size + ' MB' : '-'}\n`;
                text += '-'.repeat(30) + '\n';
            });

            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `installed_apps_${Date.now()}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        },

        getIcon(name) {
            const n = (name || '').toLowerCase();
            if (n.includes('chrome')) return '🌐';
            if (n.includes('firefox')) return '🦊';
            if (n.includes('edge')) return '🌊';
            if (n.includes('visual studio') || n.includes('vscode')) return '💻';
            if (n.includes('microsoft office') || n.includes('word') || n.includes('excel')) return '📊';
            if (n.includes('adobe')) return '🎨';
            if (n.includes('steam')) return '🎮';
            if (n.includes('discord')) return '💬';
            if (n.includes('spotify')) return '🎵';
            if (n.includes('zoom')) return '📹';
            if (n.includes('slack')) return '💼';
            if (n.includes('git')) return '🔀';
            if (n.includes('python')) return '🐍';
            if (n.includes('node')) return '💚';
            if (n.includes('java')) return '☕';
            if (n.includes('nvidia') || n.includes('amd') || n.includes('intel')) return '🖥️';
            if (n.includes('driver')) return '⚙️';
            if (n.includes('update')) return '🔄';
            if (n.includes('security') || n.includes('antivirus') || n.includes('defender')) return '🛡️';
            return '📦';
        },

        formatDate(date) {
            if (!date) return '-';
            // Try to parse YYYYMMDD format
            if (/^\d{8}$/.test(date)) {
                return `${date.slice(0, 4)}-${date.slice(4, 6)}-${date.slice(6, 8)}`;
            }
            return date;
        },

        formatSize(size) {
            if (!size || size === 0) return '-';
            if (size >= 1024) {
                return (size / 1024).toFixed(1) + ' GB';
            }
            return size.toFixed(1) + ' MB';
        },

        esc(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }
    };
</script>