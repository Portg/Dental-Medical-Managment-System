param(
    [string]$InstallDir,
    [string]$ProjectDir,
    [switch]$Remove
)

# Windows 7 自带 PowerShell 2.0，避免使用较新语法。快捷方式放在公共桌面，
# 所有登录到诊所电脑的账号都能看到；公共桌面不可用时再退回当前用户桌面。
$ErrorActionPreference = 'Stop'
$shortcutName = '牙科诊所管理系统.lnk'
$shell = New-Object -ComObject WScript.Shell
$desktopDir = $null

try {
    $desktopDir = [Environment]::GetFolderPath([Environment+SpecialFolder]::CommonDesktopDirectory)
} catch {}

if ([string]::IsNullOrEmpty($desktopDir)) {
    try { $desktopDir = [string]$shell.SpecialFolders.Item('AllUsersDesktop') } catch {}
}
if ([string]::IsNullOrEmpty($desktopDir)) {
    $desktopDir = [Environment]::GetFolderPath([Environment+SpecialFolder]::DesktopDirectory)
}
if ([string]::IsNullOrEmpty($desktopDir)) {
    throw '无法定位 Windows 桌面目录。'
}

$shortcutPath = Join-Path $desktopDir $shortcutName
if ($Remove) {
    if (Test-Path -LiteralPath $shortcutPath) {
        Remove-Item -LiteralPath $shortcutPath -Force
    }
    Write-Host ("        Desktop shortcut removed: {0}" -f $shortcutPath)
    return
}

if ([string]::IsNullOrEmpty($InstallDir)) {
    throw '创建快捷方式需要安装目录。'
}

$startScript = Join-Path $InstallDir 'start-win.bat'
if (-not (Test-Path -LiteralPath $startScript)) {
    throw ("未找到启动脚本: {0}" -f $startScript)
}

$shortcut = $shell.CreateShortcut($shortcutPath)
$shortcut.TargetPath = $startScript
$shortcut.Arguments = '"' + $InstallDir + '"'
$shortcut.WorkingDirectory = $InstallDir
$shortcut.Description = '打开牙科诊所管理系统'

if (-not [string]::IsNullOrEmpty($ProjectDir)) {
    $iconPath = Join-Path $ProjectDir 'public\favicon.ico'
    if (Test-Path -LiteralPath $iconPath) {
        $shortcut.IconLocation = $iconPath + ',0'
    }
}

$shortcut.Save()
Write-Host ("        Desktop shortcut ........ OK ({0})" -f $shortcutPath)
