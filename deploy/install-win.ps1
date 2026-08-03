$ErrorActionPreference = "Stop"

# 统一控制台编码为 UTF-8，避免中文乱码。
#
# 但**必须还原**：install-win.bat 是 GBK(936) 体系，它在调用本脚本之后还要往
# prereq.log 里写一行退出码。代码页不还原的话，那一行会以 UTF-8 字节写进一个
# 前面全是 GBK 的文件，同一个日志里出现两种编码，谁都读不全。
# 2026-08-03 那次装机的 prereq.log 就是这样：
#   [2026/08/03 周一 ...] ===== install-win.bat 启动 =====      ← GBK，正常
#   [鍛ㄤ竴 2026/08/03 ...] install-win.ps1 锟剿筹拷锟斤拷: 1    ← 乱码
$script:OriginalCodePage = $null
try {
    $chcpOut = & "$env:SystemRoot\System32\chcp.com"
    if ("$chcpOut" -match '(\d+)\s*$') { $script:OriginalCodePage = $Matches[1] }
} catch {}

try {
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
    [Console]::InputEncoding  = [System.Text.Encoding]::UTF8
    $OutputEncoding           = [System.Text.Encoding]::UTF8
    & "$env:SystemRoot\System32\chcp.com" 65001 | Out-Null
} catch {}

function Restore-ConsoleCodePage {
    if ($script:OriginalCodePage) {
        try { & "$env:SystemRoot\System32\chcp.com" $script:OriginalCodePage | Out-Null } catch {}
        $script:OriginalCodePage = $null
    }
}

function Write-Section {
    param([string]$Message)
    Write-Host ""
    Write-Host ("[{0}/{1}] {2}" -f $script:Step, $script:TotalSteps, $Message)
}

function Fail-Step {
    param([string]$Message)
    throw $Message
}

function Test-CommandExists {
    param([string]$Name)
    return [bool](Get-Command $Name -ErrorAction SilentlyContinue)
}

# Windows PowerShell 2.0 会把原生命令经 2>&1 合并的 stderr 转成 ErrorRecord。
# 全局 ErrorActionPreference=Stop 时，这会在我们读取 $LASTEXITCODE 之前终止脚本。
# 所有允许非零退出码的原生命令都经这些包装器执行，由调用方显式判断退出码。
# 原生命令的输出不能默默丢掉。
#
# 这套脚本曾经三处都在吞诊断信息，结果现场每次只剩一句结论、查不下去：
#   install-win.bat 的 2>nul（PowerShell 探测失败）、
#   Invoke-NativeLogged 的 > 覆盖（OCR 三次 pip 调用互相冲掉）、
#   Invoke-NativeQuiet 的 > $null（schtasks / sc.exe 失败原因全丢）。
# 下面两个封装统一改成：成功时安静，失败时把命令行和完整输出写进安装日志。
# 参数会原样进安装日志，而日志是要发给人排查的。
# 当前代码库刻意不在命令行传密码（见 Invoke-MySql* 用 MYSQL_PWD 环境变量的注释），
# 这里加一道兜底：以后谁加了带密码的调用，不至于把凭据直接写进日志。
# 只作用于展示字符串，不影响真正传给进程的参数。
function Format-SafeArguments {
    param([string[]]$Arguments)

    $safe = @()
    foreach ($a in $Arguments) {
        $s = "$a"
        # password=xxx / --password=xxx / --password xxx
        $s = $s -replace '(?i)(password\s*[= ]\s*)\S+', '$1******'
        # mysql 家族的 -pSecret（密码紧贴在 -p 后面）。
        # 必须排除 -pool 这种「-p 开头的长选项」，否则会误伤：
        # nginx 的 -p 是独立参数（-p <path>），不会命中这条。
        if ($s -cmatch '^-p[^-\s]{1,}$' -and $s -notmatch '^-p[a-z]+$') { $s = '-p******' }
        $safe += $s
    }
    return ($safe -join ' ')
}

function Write-NativeFailure {
    param(
        [string]$FilePath,
        [string[]]$Arguments,
        [int]$ExitCode,
        [string[]]$Output
    )

    Write-Host ("        [诊断] 命令失败（退出码 {0}）: {1} {2}" -f $ExitCode, $FilePath, (Format-SafeArguments $Arguments))
    if ($Output) {
        foreach ($line in $Output) {
            if ("$line".Trim().Length -gt 0) { Write-Host ("        [诊断] {0}" -f $line) }
        }
    }
}

function Invoke-NativeQuiet {
    param(
        [string]$FilePath,
        [string[]]$Arguments = @(),
        # 探测型调用（sc query / sc stop / schtasks /delete 之类）非零是正常结果，
        # 加这个开关抑制诊断输出，否则日志里全是无意义的「失败」，真正的失败反而被淹没。
        [switch]$Probe
    )

    $savedPreference = $ErrorActionPreference
    $exitCode = 1
    $output = @()
    try {
        $ErrorActionPreference = "Continue"
        # 捕获而不是丢弃：失败时这就是唯一的线索
        $output = @(& $FilePath @Arguments 2>&1 | ForEach-Object { "$_" })
        # $LASTEXITCODE 只在原生命令执行后才被设置。万一没被设置（$null），
        # 后面的 `-ne 0` 会判成真、误报一次失败，所以显式兜底。
        $exitCode = if ($null -eq $LASTEXITCODE) { 0 } else { $LASTEXITCODE }
    } catch {
        # 进程根本起不来（CreateProcess 失败，如被安全软件拦截）时会走到这里。
        # 这种情况 $LASTEXITCODE 不会被设置，必须显式记下来，否则调用方看到的
        # 是上一条命令的退出码。
        #
        # 注意：进程起不来即使在 -Probe 下也要报出来——那不是「查到不存在」，
        # 是这台机器上根本执行不了这个命令，性质完全不同。
        $exitCode = -1
        $output = @("无法启动进程: " + $_.Exception.Message)
        Write-NativeFailure -FilePath $FilePath -Arguments $Arguments -ExitCode $exitCode -Output $output
        # 直接 return：finally 仍会执行并还原 $ErrorActionPreference，
        # 这里不必也不该再还原一次。
        return $exitCode
    } finally {
        $ErrorActionPreference = $savedPreference
    }

    if ($exitCode -ne 0 -and -not $Probe) {
        Write-NativeFailure -FilePath $FilePath -Arguments $Arguments -ExitCode $exitCode -Output $output
    }

    return $exitCode
}

function Invoke-NativeLogged {
    param(
        [string]$FilePath,
        [string[]]$Arguments = @(),
        [string]$LogPath,
        [switch]$Append
    )

    $savedPreference = $ErrorActionPreference
    $exitCode = 1
    try {
        $ErrorActionPreference = "Continue"
        $header = @(
            "",
            ("===== {0} {1} =====" -f $FilePath, (Format-SafeArguments $Arguments)),
            ("===== 开始: {0} =====" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"))
        )
        # 日志统一按 GBK(936) 写：安装过程全程在 936 控制台下，
        # 用 Out-File 默认的 UTF-16 会让日志在 cmd 里 type 出来是乱码。
        if ($Append) {
            $header | Out-File -FilePath $LogPath -Encoding Default -Append
        } else {
            $header | Out-File -FilePath $LogPath -Encoding Default
        }
        & $FilePath @Arguments 2>&1 | ForEach-Object { "$_" } |
            Out-File -FilePath $LogPath -Encoding Default -Append
        $exitCode = if ($null -eq $LASTEXITCODE) { 0 } else { $LASTEXITCODE }
    } catch {
        $exitCode = -1
        ("无法启动进程: " + $_.Exception.Message) | Out-File -FilePath $LogPath -Encoding Default -Append
    } finally {
        $ErrorActionPreference = $savedPreference
    }
    return $exitCode
}

function Invoke-NativeCapture {
    param(
        [string]$FilePath,
        [string[]]$Arguments = @()
    )

    $savedPreference = $ErrorActionPreference
    $exitCode = 1
    $output = ""
    try {
        $ErrorActionPreference = "Continue"
        $output = (& $FilePath @Arguments 2>&1 | Out-String).Trim()
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $savedPreference
    }
    return @{
        ExitCode = $exitCode
        Output   = $output
    }
}

function Read-PlainTextPassword {
    param([string]$Prompt)

    $securePassword = Read-Host $Prompt -AsSecureString
    $passwordPtr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPtr)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPtr)
    }
}

# mysql.exe 支持 MYSQL_PWD。用临时环境变量传递密码，避免密码出现在
# 命令行、进程列表和异常日志中；命令结束后立即恢复原环境。
function Invoke-MySqlQuiet {
    param(
        [string]$FilePath,
        [string[]]$Arguments = @(),
        [string]$Password = ""
    )

    $hadOldPassword = Test-Path Env:MYSQL_PWD
    $oldPassword = $env:MYSQL_PWD
    try {
        if ([string]::IsNullOrEmpty($Password)) {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        } else {
            $env:MYSQL_PWD = $Password
        }
        $exitCode = Invoke-NativeQuiet -FilePath $FilePath -Arguments $Arguments
        return $exitCode
    } finally {
        if ($hadOldPassword) {
            $env:MYSQL_PWD = $oldPassword
        } else {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        }
    }
}

function Invoke-MySqlExternal {
    param(
        [string]$FilePath,
        [string[]]$Arguments = @(),
        [string]$Password = ""
    )

    $hadOldPassword = Test-Path Env:MYSQL_PWD
    $oldPassword = $env:MYSQL_PWD
    try {
        if ([string]::IsNullOrEmpty($Password)) {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        } else {
            $env:MYSQL_PWD = $Password
        }
        $exitCode = Invoke-External -FilePath $FilePath -Arguments $Arguments
        return $exitCode
    } finally {
        if ($hadOldPassword) {
            $env:MYSQL_PWD = $oldPassword
        } else {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        }
    }
}

function Invoke-MySqlCmdLine {
    param(
        [string]$CommandLine,
        [string]$Password = ""
    )

    $hadOldPassword = Test-Path Env:MYSQL_PWD
    $oldPassword = $env:MYSQL_PWD
    try {
        if ([string]::IsNullOrEmpty($Password)) {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        } else {
            $env:MYSQL_PWD = $Password
        }
        $exitCode = Invoke-CmdLine -CommandLine $CommandLine
        return $exitCode
    } finally {
        if ($hadOldPassword) {
            $env:MYSQL_PWD = $oldPassword
        } else {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        }
    }
}

function Find-PythonRuntime {
    $result = @{
        Exe     = $null
        Args    = @()
        Display = $null
    }

    if (Test-CommandExists "py") {
        $versionProbe = Invoke-NativeCapture -FilePath "py" -Arguments @("-3", "--version")
        if ($versionProbe.ExitCode -eq 0 -and $versionProbe.Output -match '^Python\s+3\.') {
            $result.Exe = "py"
            $result.Args = @("-3")
            $result.Display = "py -3"
            return $result
        }
    }

    foreach ($candidate in @("python3", "python")) {
        if (-not (Test-CommandExists $candidate)) {
            continue
        }

        $versionProbe = Invoke-NativeCapture -FilePath $candidate -Arguments @("--version")
        if ($versionProbe.ExitCode -eq 0 -and $versionProbe.Output -match '^Python\s+3\.') {
            $result.Exe = $candidate
            $result.Args = @()
            $result.Display = $candidate
            return $result
        }
    }

    return $result
}

function Install-BundledPython {
    param([string]$InstallerPath)

    if (-not (Test-Path $InstallerPath)) {
        return $false
    }

    Write-Host ("        Installing bundled Python from {0}" -f $InstallerPath)
    $installArgs = @(
        '/quiet',
        'InstallAllUsers=1',
        'PrependPath=1',
        'Include_pip=1',
        'Include_launcher=1',
        'SimpleInstall=1',
        'Shortcuts=0',
        'CompileAll=0',
        'Include_test=0'
    )

    $proc = Start-Process -FilePath $InstallerPath -ArgumentList $installArgs -Wait -PassThru
    return ($proc.ExitCode -eq 0)
}

function Install-LaragonRuntime {
    param(
        [string]$InstallerPath,
        [string]$TargetDir
    )

    if (-not (Test-Path $InstallerPath)) {
        return $false
    }

    $laragonTarget = Join-Path $TargetDir "laragon"
    Write-Host ("        Installing Laragon from {0}" -f $InstallerPath)
    $args = @(
        '/SP-',
        '/VERYSILENT',
        '/SUPPRESSMSGBOXES',
        '/NORESTART',
        ('/DIR=' + $laragonTarget)
    )

    $proc = Start-Process -FilePath $InstallerPath -ArgumentList $args -Wait -PassThru
    if ($proc.ExitCode -ne 0) {
        return $false
    }

    return (Test-Path (Join-Path $laragonTarget 'bin'))
}

function Set-IniValue {
    param(
        [string]$Path,
        [string]$Key,
        [string]$Value
    )

    $lines = @()
    if (Test-Path $Path) {
        $lines = @(Get-Content -Path $Path -ErrorAction SilentlyContinue)
    }

    $updated = New-Object System.Collections.Generic.List[string]
    $pattern = '^\s*' + [Regex]::Escape($Key) + '\s*='
    $replaced = $false

    foreach ($line in $lines) {
        if ($line -match $pattern) {
            if (-not $replaced) {
                $updated.Add($Key + '=' + $Value)
                $replaced = $true
            }
        } else {
            $updated.Add($line)
        }
    }

    if (-not $replaced) {
        $updated.Add($Key + '=' + $Value)
    }

    $utf8NoBom = New-Object -TypeName System.Text.UTF8Encoding -ArgumentList $false
    [System.IO.File]::WriteAllLines($Path, $updated, $utf8NoBom)
}

function Get-FirstDirectoryMatch {
    param(
        [string]$BasePath,
        [string[]]$Patterns,
        [string]$CheckRelativePath
    )

    foreach ($pattern in $Patterns) {
        $matches = Get-ChildItem -Path (Join-Path $BasePath $pattern) -ErrorAction SilentlyContinue |
            Where-Object { $_.PSIsContainer } |
            Sort-Object FullName -Descending
        foreach ($item in $matches) {
            if (Test-Path (Join-Path $item.FullName $CheckRelativePath)) {
                return $item.FullName
            }
        }
    }

    return $null
}

function Invoke-External {
    param(
        [string]$FilePath,
        [string[]]$Arguments = @(),
        [switch]$IgnoreExitCode
    )

    $savedPreference = $ErrorActionPreference
    $exitCode = 1
    try {
        $ErrorActionPreference = "Continue"
        & $FilePath @Arguments
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $savedPreference
    }
    if (-not $IgnoreExitCode -and $exitCode -ne 0) {
        throw "Command failed: $FilePath $($Arguments -join ' ')"
    }
    return $exitCode
}

function Invoke-CmdLine {
    param([string]$CommandLine)
    $savedPreference = $ErrorActionPreference
    $exitCode = 1
    try {
        $ErrorActionPreference = "Continue"
        & cmd.exe /c $CommandLine
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $savedPreference
    }
    return $exitCode
}

function Get-PhpVersionInfo {
    param([string]$PhpExe)

    $versionProbe = Invoke-NativeCapture -FilePath $PhpExe -Arguments @("-v")
    $lines = @($versionProbe.Output -split '\r?\n')
    $exitCode = $versionProbe.ExitCode
    $version = $null

    foreach ($line in $lines) {
        if ($line -match '^PHP\s+([0-9]+\.[0-9]+\.[0-9]+)') {
            $version = $matches[1]
            break
        }
    }

    return @{
        ExitCode = $exitCode
        Version  = $version
        Output   = ($lines -join [Environment]::NewLine)
    }
}

function Ensure-PhpIniForBundledRuntime {
    param(
        [string]$PhpDir
    )

    $phpIni = Join-Path $PhpDir "php.ini"
    $phpIniProduction = Join-Path $PhpDir "php.ini-production"
    if (-not (Test-Path $phpIni) -and (Test-Path $phpIniProduction)) {
        Copy-Item -Path $phpIniProduction -Destination $phpIni -Force
    }
    if (-not (Test-Path $phpIni)) {
        return
    }

    $extensionDir = (Join-Path $PhpDir "ext").Replace('\', '/')
    $lines = @(Get-Content -Path $phpIni -ErrorAction SilentlyContinue)
    $updated = New-Object System.Collections.Generic.List[string]
    $extensionDirSet = $false

    foreach ($line in $lines) {
        if ($line -match '^\s*;?\s*extension_dir\s*=') {
            $updated.Add('extension_dir = "' + $extensionDir + '"')
            $extensionDirSet = $true
        } else {
            $updated.Add($line)
        }
    }

    if (-not $extensionDirSet) {
        $updated.Add('extension_dir = "' + $extensionDir + '"')
    }

    $utf8NoBom = New-Object -TypeName System.Text.UTF8Encoding -ArgumentList $false
    [System.IO.File]::WriteAllLines($phpIni, $updated, $utf8NoBom)
}

function Wait-MySqlReady {
    param(
        [string]$MySqlExe,
        # $args 是 PowerShell 自动变量，不能拿来存连接参数。PowerShell 2
        # 会用“未绑定参数”重填它，结果探测命令丢掉 -h/-P/-u 并误连 3306。
        [string[]]$ConnectionArguments,
        [string]$Password = "",
        [int]$TimeoutSeconds = 60
    )

    $waited = 0
    while ($waited -lt $TimeoutSeconds) {
        Start-Sleep -Seconds 2
        $probeExit = Invoke-MySqlQuiet -FilePath $MySqlExe -Arguments ($ConnectionArguments + @('-e', 'SELECT 1')) -Password $Password
        if ($probeExit -eq 0) {
            return $true
        }
        $waited += 2
    }

    return $false
}

function Test-HttpEndpoint {
    param(
        [string]$Url,
        [int]$TimeoutMs = 2000
    )

    try {
        $request = [System.Net.WebRequest]::Create($Url)
        $request.Timeout = $TimeoutMs
        $response = $request.GetResponse()
        $response.Close()
        return $true
    } catch {
        return $false
    }
}

function Wait-HttpReady {
    param(
        [string]$Url,
        [int]$TimeoutSeconds = 60
    )

    $waited = 0
    while ($waited -lt $TimeoutSeconds) {
        if (Test-HttpEndpoint -Url $Url -TimeoutMs 3000) {
            return $true
        }

        Start-Sleep -Seconds 2
        $waited += 2
    }

    return $false
}

function Get-LastLogLines {
    param(
        [string[]]$Paths,
        [int]$Tail = 20
    )

    $blocks = New-Object System.Collections.Generic.List[string]
    foreach ($path in $Paths) {
        if (-not (Test-Path $path)) {
            continue
        }

        $content = (Get-Content -Path $path -ErrorAction SilentlyContinue | Select-Object -Last $Tail) -join [Environment]::NewLine
        if ($content) {
            $blocks.Add(("--- {0} ---{1}{2}" -f $path, [Environment]::NewLine, $content))
        }
    }

    return ($blocks -join ([Environment]::NewLine + [Environment]::NewLine))
}

function Get-PortOccupancyDetails {
    param([int]$Port)

    $details = New-Object System.Collections.Generic.List[string]

    try {
        $netstatLines = & netstat -ano -p tcp 2>$null | Select-String (":{0}\s" -f $Port)
        foreach ($match in $netstatLines) {
            $line = ($match.ToString() -replace '\s+', ' ').Trim()
            if (-not $line) {
                continue
            }

            $details.Add($line)
            $parts = $line -split ' '
            $pid = $parts[-1]
            if ($pid -match '^\d+$') {
                try {
                    $proc = Get-Process -Id ([int]$pid) -ErrorAction SilentlyContinue
                    if ($proc) {
                        $details.Add(("PID {0}: {1}" -f $pid, $proc.ProcessName))
                    }
                } catch {}
            }
        }
    } catch {}

    return ($details | Select-Object -Unique) -join [Environment]::NewLine
}

function Ensure-Admin {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Fail-Step "Administrator privileges are required. Please run this script as Administrator."
    }
}

function Parse-Arguments {
    param([string[]]$RawArgs)

    $config = @{
        INSTALL_DIR  = "C:\DentalClinic"
        DB_HOST      = "127.0.0.1"
        DB_PORT      = "3306"
        DB_NAME      = "pristine_dental"
        DB_USER      = "root"
        DB_PASS      = ""
        DB_ADMIN_USER = "root"
        USE_EXISTING_MYSQL = $false
        NON_INTERACTIVE = $false
        APP_URL      = "http://localhost"
        SKIP_OCR     = $false
        SKIP_SERVICE = $false
        SILENT_MODE  = $false
    }

    $positionParsed = $false
    for ($i = 0; $i -lt $RawArgs.Count; $i++) {
        $arg = [string]$RawArgs[$i]
        switch -Regex ($arg) {
            '^--db-host$'     { $i++; $config.DB_HOST = [string]$RawArgs[$i]; continue }
            '^--db-port$'     { $i++; $config.DB_PORT = [string]$RawArgs[$i]; continue }
            '^--db-name$'     { $i++; $config.DB_NAME = [string]$RawArgs[$i]; continue }
            '^--db-user$'     { $i++; $config.DB_USER = [string]$RawArgs[$i]; continue }
            '^--db-pass$'     { $i++; $config.DB_PASS = [string]$RawArgs[$i]; continue }
            '^--db-admin-user$' { $i++; $config.DB_ADMIN_USER = [string]$RawArgs[$i]; continue }
            '^--use-existing-mysql$' { $config.USE_EXISTING_MYSQL = $true; continue }
            '^--non-interactive$' { $config.NON_INTERACTIVE = $true; continue }
            '^--app-url$'     { $i++; $config.APP_URL = [string]$RawArgs[$i]; continue }
            '^--no-ocr$'      { $config.SKIP_OCR = $true; continue }
            '^--no-service$'  { $config.SKIP_SERVICE = $true; continue }
            '^(--yes|-y)$'    { $config.SILENT_MODE = $true; continue }
            default {
                if (-not $arg.StartsWith("--") -and -not $positionParsed) {
                    $config.INSTALL_DIR = $arg
                    $positionParsed = $true
                } else {
                    Write-Host "[WARN] Unknown argument ignored: $arg"
                }
            }
        }
    }

    $config.INSTALL_DIR = $config.INSTALL_DIR.TrimEnd('\')
    return $config
}

$script:TotalSteps = 19
$script:Step = 0
$script:ScriptRev = "20260803-win7-autostart"
$cfg = Parse-Arguments $args

$INSTALL_DIR = $cfg.INSTALL_DIR
$DB_HOST = $cfg.DB_HOST
$DB_PORT = $cfg.DB_PORT
$DB_NAME = $cfg.DB_NAME
$DB_USER = $cfg.DB_USER
$DB_PASS = $cfg.DB_PASS
$DB_ADMIN_USER = $cfg.DB_ADMIN_USER
$DB_ADMIN_PASS = ""
$USE_EXISTING_MYSQL = $cfg.USE_EXISTING_MYSQL
$NON_INTERACTIVE = $cfg.NON_INTERACTIVE
$SKIP_SCHEMA_IMPORT = $false
$APP_URL = $cfg.APP_URL
$SKIP_OCR = $cfg.SKIP_OCR
$SKIP_SERVICE = $cfg.SKIP_SERVICE
$SILENT_MODE = $cfg.SILENT_MODE

# ── 运行时形态识别 ────────────────────────────────────────────────────
#
# 两种包结构：
#   laragon（默认）— {安装目录}\laragon\bin\{php,mysql,nginx}，带版本号子目录，
#                    Web 层是 Nginx + php-cgi，代码放 laragon\www\dental
#   xampp          — {安装目录}\xampp\{php,apache,mysql}，扁平无版本号子目录，
#                    Web 层是 Apache + mod_php，代码放 xampp\htdocs\dental
#
# 按目录存在性判定而不是靠参数：安装包里带的是哪套就是哪套，
# 省得 setup.bat / Inno / 手工执行三条路径各自传参、漏一个就配错。
$XAMPP_DIR = Join-Path $INSTALL_DIR "xampp"
$LARAGON_DIR = Join-Path $INSTALL_DIR "laragon"

if (Test-Path (Join-Path $XAMPP_DIR "apache\bin\httpd.exe")) {
    $RUNTIME_FLAVOR = "xampp"
    $RUNTIME_ROOT   = $XAMPP_DIR
    $PROJECT_DIR    = Join-Path $XAMPP_DIR "htdocs\dental"
} else {
    $RUNTIME_FLAVOR = "laragon"
    $RUNTIME_ROOT   = $LARAGON_DIR
    $PROJECT_DIR    = Join-Path $LARAGON_DIR "www\dental"
}
$NGINX_CONF_DIR = Join-Path $LARAGON_DIR "etc\nginx\sites-enabled"
$HELPER_DIR = Join-Path $INSTALL_DIR "batch-helpers"
$LARAGON_INSTALLER = Join-Path $INSTALL_DIR "laragon-wamp.exe"
$EXTERNAL_MYSQL_MARKER = Join-Path $INSTALL_DIR "existing-mysql.conf"
$BUNDLED_MYSQL_MARKER = Join-Path $INSTALL_DIR "bundled-mysql.conf"

# ── 安装日志 ────────────────────────────────────────────────────────
# 此前安装过程只打在控制台上。Inno 的安装包以 runhidden 调用本脚本，
# 根本不存在控制台，一旦失败什么都不剩，只能让用户手动重跑一次才看得到错误。
# ZIP 方式虽有窗口，但装完即关、日志同样不留。
# 这里把完整过程落盘到 {安装目录}\logs\，成功和失败都在末尾打印路径。
# 放在安装目录而非项目目录下：升级时项目目录会被整体替换，日志需要留存。
$LOG_DIR = Join-Path $INSTALL_DIR "logs"
$INSTALL_LOG = Join-Path $LOG_DIR ("install-{0}.log" -f (Get-Date -Format "yyyyMMdd-HHmmss"))
$script:TranscriptStarted = $false

# 脚本的统一收尾：所有退出路径都要经过这里。
# 除了停 transcript，还要把控制台代码页还给调用方（install-win.bat 之后还要写日志）。
function Stop-InstallTranscript {
    if ($script:TranscriptStarted) {
        try { Stop-Transcript | Out-Null } catch {}
        $script:TranscriptStarted = $false
    }
    Restore-ConsoleCodePage
}

# 记日志失败绝不能阻断安装，整体包 try/catch
try {
    if (-not (Test-Path $LOG_DIR)) {
        New-Item -ItemType Directory -Path $LOG_DIR -Force | Out-Null
    }
    Start-Transcript -Path $INSTALL_LOG -Force | Out-Null
    $script:TranscriptStarted = $true
} catch {
    $INSTALL_LOG = "(日志不可用: $($_.Exception.Message))"
}

Write-Host ""
Write-Host "+=========================================================+"
Write-Host "| Dental Clinic Management System - Windows Installer     |"
Write-Host "+=========================================================+"
Write-Host ("| Script Revision: {0}" -f $script:ScriptRev)
Write-Host ("| Install Dir: {0}" -f $INSTALL_DIR)
Write-Host ("| Project Dir: {0}" -f $PROJECT_DIR)
Write-Host ("| Install Log: {0}" -f $INSTALL_LOG)
if ($USE_EXISTING_MYSQL) {
    Write-Host ("| MySQL:       existing {0}:{1}" -f $DB_HOST, $DB_PORT)
} else {
    Write-Host "| MySQL:       bundled runtime"
}
Write-Host "+=========================================================+"

try {
    $script:Step++
    Write-Section "Check administrator privileges"
    Ensure-Admin
    Write-Host "        Administrator privileges ... OK"

    $script:Step++
    Write-Section "Check disk space and existing install"
    $targetDrive = $INSTALL_DIR.Substring(0, 2)
    $disk = Get-WmiObject Win32_LogicalDisk -Filter ("DeviceID='{0}'" -f $targetDrive) -ErrorAction SilentlyContinue
    if (-not $disk -or [int64]$disk.FreeSpace -lt 2147483648) {
        Fail-Step "Not enough free disk space on $targetDrive. At least 2GB is required."
    }
    Write-Host "        Disk space .............. OK (>2GB)"

    $partialInstall = (Test-Path (Join-Path $INSTALL_DIR "laragon")) -or (Test-Path (Join-Path $INSTALL_DIR "install-win.bat")) -or (Test-Path $PROJECT_DIR)
    if (Test-Path (Join-Path $PROJECT_DIR "artisan")) {
        if (-not $SILENT_MODE) {
            $confirm = Read-Host "Existing installation detected. Overwrite? (Y/N)"
            if ($confirm -ne "Y" -and $confirm -ne "y") {
                Write-Host "Installation cancelled."
                # 这条退出路径也要走收尾，否则控制台代码页留在 65001，
                # install-win.bat 后面写的日志就是乱码
                Stop-InstallTranscript
                exit 0
            }
        }
        Write-Host "        Existing installation will be overwritten"
        # 只有内置 MySQL 模式才清理安装目录内的数据目录。
        # 外部 MySQL 模式不得对目标机数据库进程或 datadir 做任何生命周期操作。
        if (-not $USE_EXISTING_MYSQL) {
            $oldMysqlData = Join-Path $LARAGON_DIR "data\mysql"
            if (Test-Path $oldMysqlData) {
                # 上一次可能在 MySQL 已启动后失败。仅当存在本系统生成的内置
                # MySQL 标记时，才对目标端口执行优雅关闭；绝不按进程名结束
                # mysqld，也绝不操作目标机 3306 上的原有实例。
                if (Test-Path $BUNDLED_MYSQL_MARKER) {
                    Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('stop', 'DentalClinicMySQL') -Probe | Out-Null
                    $oldMysqlBase = Join-Path $LARAGON_DIR 'bin\mysql'
                    $oldMysqlDir = Get-FirstDirectoryMatch -BasePath $oldMysqlBase -Patterns @('mysql-5*', 'mysql-*', 'mysql*', '*') -CheckRelativePath 'bin\mysqladmin.exe'
                    if ($oldMysqlDir) {
                        $oldMysqlAdmin = Join-Path $oldMysqlDir 'bin\mysqladmin.exe'
                        Invoke-MySqlQuiet -FilePath $oldMysqlAdmin -Arguments @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root', 'shutdown') -Password '' | Out-Null
                    }
                    Start-Sleep -Seconds 3

                    $oldPortStillInUse = $false
                    try {
                        $oldListeners = [System.Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() |
                            Where-Object { $_.Port -eq [int]$DB_PORT }
                        if ($oldListeners) { $oldPortStillInUse = $true }
                    } catch {}
                    if ($oldPortStillInUse) {
                        Fail-Step ("Bundled MySQL is still listening on port {0}. Refusing to clear a live MySQL data directory." -f $DB_PORT)
                    }
                }
                Remove-Item -Path $oldMysqlData -Recurse -Force -ErrorAction SilentlyContinue
                Write-Host "        Old MySQL data cleared"
            }
        }
    } elseif ($partialInstall) {
        Write-Host "        Partial installation residue detected; continuing"
    } else {
        Write-Host "        No prior installation found"
    }

    $script:Step++
    Write-Section "Ensure Visual C++ runtime"
    # PHP 的 VS16 构建依赖 VC++ 2015-2022 运行库。干净的 Win7 上通常没有，
    # 缺失时 php.exe 会以「缺少 VCRUNTIME140.dll」直接失败，报错信息对现场
    # 人员毫无指向性。这里无条件静默安装一次（已装则由安装器自行跳过）。
    $vcRedist = Join-Path $INSTALL_DIR "vc_redist.x64.exe"
    if (Test-Path $vcRedist) {
        $vcProc = Start-Process -FilePath $vcRedist -ArgumentList @('/install', '/quiet', '/norestart') -Wait -PassThru
        switch ($vcProc.ExitCode) {
            0       { Write-Host "        VC++ runtime ............ 已安装" }
            1638    { Write-Host "        VC++ runtime ............ 已是更新版本，跳过" }
            3010    { Write-Host "        VC++ runtime ............ 已安装（重启后完全生效）" }
            default { Write-Host ("        VC++ runtime ............ 警告：安装器返回 {0}" -f $vcProc.ExitCode) -ForegroundColor Yellow }
        }
    } else {
        Write-Host "        VC++ runtime ............ 安装包内未附带，跳过" -ForegroundColor Yellow
    }

    $script:Step++
    Write-Section ("Detect runtime ({0})" -f $RUNTIME_FLAVOR)

if ($RUNTIME_FLAVOR -eq "xampp") {
    # ── XAMPP 布局：扁平，没有版本号子目录，不需要 Get-FirstDirectoryMatch ──
    $PHP_DIR    = Join-Path $XAMPP_DIR "php"
    $PHP_EXE    = Join-Path $PHP_DIR "php.exe"
    if (-not (Test-Path $PHP_EXE)) { Fail-Step "PHP not found: $PHP_EXE" }
    Ensure-PhpIniForBundledRuntime -PhpDir $PHP_DIR
    $env:PHPRC = $PHP_DIR
    $env:PHP_INI_SCAN_DIR = ""
    $env:PATH = "$PHP_DIR;$env:PATH"
    Write-Host ("        PHP ..................... {0}" -f $PHP_EXE)

    $mysqlDir       = Join-Path $XAMPP_DIR "mysql"
    $MYSQL_EXE      = Join-Path $mysqlDir "bin\mysql.exe"
    $MYSQLD_EXE     = Join-Path $mysqlDir "bin\mysqld.exe"
    if (-not (Test-Path $MYSQLD_EXE)) { Fail-Step "MariaDB not found: $MYSQLD_EXE" }
    # XAMPP 的 portable 包**自带初始化好的 data 目录**（ibdata1/ib_logfile*/aria_log* 等），
    # 所以不需要 --initialize-insecure —— 而且 MariaDB 10.4 根本不支持那个参数
    # （它用 mysql_install_db）。数据目录就用包里自带的位置。
    $MYSQL_DATA_DIR  = Join-Path $mysqlDir "data"
    $MYSQL_ERROR_LOG = Join-Path $mysqlDir "data\mysql-error.log"
    $env:PATH = "$(Join-Path $mysqlDir 'bin');$env:PATH"
    Write-Host ("        MariaDB ................. {0}" -f $MYSQL_EXE)

    $APACHE_DIR = Join-Path $XAMPP_DIR "apache"
    $APACHE_EXE = Join-Path $APACHE_DIR "bin\httpd.exe"
    if (-not (Test-Path $APACHE_EXE)) { Fail-Step "Apache not found: $APACHE_EXE" }
    Write-Host ("        Apache .................. {0}" -f $APACHE_EXE)

    # Nginx 相关变量置空：下游按 $NGINX_EXE 是否有值决定走哪条 Web 层配置
    $NGINX_DIR = $null
    $NGINX_EXE = $null

    # XAMPP 不带 Composer。装机流程只做存在性检查、从不真正执行它
    # （$COMPOSER_PHAR 全文只出现在探测与打印处），所以这里降级为提示，不阻断。
    $COMPOSER_PHAR = Join-Path $INSTALL_DIR "composer.phar"
    if (Test-Path $COMPOSER_PHAR) {
        Write-Host ("        Composer ................ {0}" -f $COMPOSER_PHAR)
    } else {
        $COMPOSER_PHAR = $null
        Write-Host "        Composer ................ 未随包（安装流程不需要）"
    }
} else {
    if (-not (Test-Path (Join-Path $LARAGON_DIR "bin"))) {
        if (Test-Path $LARAGON_INSTALLER) {
            if (-not (Install-LaragonRuntime -InstallerPath $LARAGON_INSTALLER -TargetDir $INSTALL_DIR)) {
                Fail-Step "Laragon installation failed. Please verify laragon-wamp.exe can be installed silently on the target machine."
            }
            Write-Host ("        Laragon installed ....... {0}" -f $LARAGON_DIR)
        } else {
            Fail-Step "Laragon directory not found: $LARAGON_DIR\bin"
        }
    }

    $phpBase = Join-Path $LARAGON_DIR "bin\php"
    $phpDir = Get-FirstDirectoryMatch -BasePath $phpBase -Patterns @('php-8*', 'php8*', 'php*', '*') -CheckRelativePath 'php.exe'
    if (-not $phpDir -and (Test-Path (Join-Path $phpBase 'php.exe'))) {
        $phpDir = $phpBase
    }
    if (-not $phpDir) { Fail-Step "PHP not found under $phpBase" }
    Ensure-PhpIniForBundledRuntime -PhpDir $phpDir
    $PHP_EXE = Join-Path $phpDir "php.exe"
    $env:PHPRC = $phpDir
    $env:PHP_INI_SCAN_DIR = ""
    $env:PATH = "$phpDir;$env:PATH"
    Write-Host ("        PHP ..................... {0}" -f $PHP_EXE)

    $mysqlBase = Join-Path $LARAGON_DIR "bin\mysql"
    $mysqlDir = Get-FirstDirectoryMatch -BasePath $mysqlBase -Patterns @('mysql-5*', 'mysql-*', 'mysql*', '*') -CheckRelativePath 'bin\mysql.exe'
    if (-not $mysqlDir) { Fail-Step "MySQL not found under $mysqlBase" }
    $MYSQL_EXE = Join-Path $mysqlDir "bin\mysql.exe"
    $MYSQLD_EXE = Join-Path $mysqlDir "bin\mysqld.exe"
    $MYSQL_DATA_DIR = Join-Path $LARAGON_DIR "data\mysql"
    $MYSQL_ERROR_LOG = Join-Path $LARAGON_DIR "data\mysql-error.log"
    $env:PATH = "$(Join-Path $mysqlDir 'bin');$env:PATH"
    Write-Host ("        MySQL ................... {0}" -f $MYSQL_EXE)

    $nginxBase = Join-Path $LARAGON_DIR "bin\nginx"
    $NGINX_DIR = Get-FirstDirectoryMatch -BasePath $nginxBase -Patterns @('nginx-*', '*') -CheckRelativePath 'nginx.exe'
    if ($NGINX_DIR) {
        $NGINX_EXE = Join-Path $NGINX_DIR "nginx.exe"
        Write-Host ("        Nginx ................... {0}" -f $NGINX_EXE)
    }

    $COMPOSER_PHAR = Join-Path $LARAGON_DIR "bin\composer\composer.phar"
    if (-not (Test-Path $COMPOSER_PHAR) -and -not (Test-CommandExists "composer")) {
        Fail-Step "Composer not found."
    }
    if (Test-Path $COMPOSER_PHAR) {
        Write-Host ("        Composer ................ {0}" -f $COMPOSER_PHAR)
    } else {
        Write-Host "        Composer ................ composer"
    }
}
# ── 以下为两种运行时共用 ──────────────────────────────────────────────

    $PYTHON_EXE = $null
    $PYTHON_ARGS = @()
    if ($SKIP_OCR) {
        Write-Host "        Python .................. skipped (--no-ocr)"
    } else {
        $pythonRuntime = Find-PythonRuntime
        if (-not $pythonRuntime.Exe) {
            $bundledPythonInstaller = $null
            foreach ($candidate in @(
                (Join-Path $INSTALL_DIR 'python-installer.exe'),
                (Join-Path $INSTALL_DIR 'python\python-installer.exe')
            )) {
                if (Test-Path $candidate) {
                    $bundledPythonInstaller = $candidate
                    break
                }
            }

            if ($bundledPythonInstaller) {
                # Win7 上 Python 3.8.10 安装失败不应中止整个部署 —— OCR 是增强功能。
                if (Install-BundledPython -InstallerPath $bundledPythonInstaller) {
                    $pythonRuntime = Find-PythonRuntime
                } else {
                    Write-Host "        [警告] 内置 Python 安装失败，OCR 将被关闭。" -ForegroundColor Yellow
                }
            }
        }

        if ($pythonRuntime.Exe) {
            $PYTHON_EXE = $pythonRuntime.Exe
            $PYTHON_ARGS = $pythonRuntime.Args
            Write-Host ("        Python .................. {0}" -f $pythonRuntime.Display)
        } else {
            Write-Host "        [警告] 未找到 Python 3 运行时，OCR 已关闭，工作日志改为手工录入。" -ForegroundColor Yellow
            Write-Host "                （Win7 目标机需要 Python 3.8.x；3.9+ 无法在 Win7 上安装）" -ForegroundColor Yellow
            $SKIP_OCR = $true
        }
    }

    $phpVersionInfo = Get-PhpVersionInfo -PhpExe $PHP_EXE
    $script:PhpVer = $phpVersionInfo.Version
    if (-not $script:PhpVer) {
        $runtimeHint = "无法获取 PHP 版本。"
        if ($phpVersionInfo.Output) {
            $runtimeHint += " PHP 启动输出: " + $phpVersionInfo.Output
        }
        $runtimeHint += " 此安装包内置 PHP 8.2（VS16 x64）。php.exe 无法启动最常见的原因是缺少 Visual C++ 2015-2022 (x64) 运行库；安装包根目录已附带 vc_redist.x64.exe，请先运行它。"
        Fail-Step $runtimeHint
    }
    $phpVersion = [Version]$script:PhpVer
    # Win7 版锁定 PHP 8.2.x：
    #   下限 8.2 —— Laravel 11 的最低要求；
    #   上限 8.3 —— PHP 8.3 起最低要求 Windows 8/Server 2012，装在 Win7 上跑不起来。
    if ($phpVersion -lt [Version]"8.2.0") {
        Fail-Step "需要 PHP 8.2，当前为 $($script:PhpVer)（Laravel 11 最低要求 8.2）。"
    }
    if ($phpVersion -ge [Version]"8.3.0") {
        Fail-Step "检测到 PHP $($script:PhpVer)。此为 Windows 7 专用安装包，运行时必须是 PHP 8.2.x —— PHP 8.3 起最低要求 Windows 8/Server 2012。请使用配套的 Win7 构建产物重新安装。"
    }
    Write-Host ("        PHP version ............. {0}" -f $script:PhpVer)

    if (-not (Test-Path (Join-Path $PROJECT_DIR "artisan"))) {
        Fail-Step "Project is incomplete. artisan not found in $PROJECT_DIR"
    }
    if (-not (Test-Path $HELPER_DIR)) {
        Fail-Step "batch-helpers directory is missing: $HELPER_DIR"
    }
    Write-Host "        Project files ........... OK"

    $script:Step++
    Write-Section "Start MySQL"
    $rootConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_ADMIN_USER)
    if ($USE_EXISTING_MYSQL) {
        Remove-Item $BUNDLED_MYSQL_MARKER -Force -ErrorAction SilentlyContinue
        if (-not [string]::IsNullOrEmpty($env:DENTAL_MYSQL_ADMIN_PASSWORD)) {
            $DB_ADMIN_PASS = [string]$env:DENTAL_MYSQL_ADMIN_PASSWORD
        } elseif ($NON_INTERACTIVE) {
            Fail-Step "Existing MySQL mode requires DENTAL_MYSQL_ADMIN_PASSWORD in non-interactive installs."
        } else {
            $DB_ADMIN_PASS = Read-PlainTextPassword ("MySQL password for {0}@{1}:{2}" -f $DB_ADMIN_USER, $DB_HOST, $DB_PORT)
        }

        $mysqlProbeExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', 'SELECT 1')) -Password $DB_ADMIN_PASS
        if ($mysqlProbeExit -ne 0) {
            Fail-Step ("Cannot connect to existing MySQL at {0}:{1} as {2}. No MySQL process was stopped or changed." -f $DB_HOST, $DB_PORT, $DB_ADMIN_USER)
        }

        $existingDbExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-D', $DB_NAME, '-e', 'SELECT 1')) -Password $DB_ADMIN_PASS
        if ($existingDbExit -eq 0) {
            if ($NON_INTERACTIVE) {
                Fail-Step ("Database $DB_NAME already exists. Refusing to overwrite it in non-interactive mode.")
            }
            $replaceDatabase = Read-Host ("Database {0} already exists. Replace same-name tables with package data? (Y/N)" -f $DB_NAME)
            if ($replaceDatabase -ne 'Y' -and $replaceDatabase -ne 'y') {
                $SKIP_SCHEMA_IMPORT = $true
                Write-Host "        Existing database data .. preserved"
            }
        }

        if ($DB_USER -eq $DB_ADMIN_USER -and [string]::IsNullOrEmpty($DB_PASS)) {
            $DB_PASS = $DB_ADMIN_PASS
        }
        @(
            'mode=existing',
            ('host=' + $DB_HOST),
            ('port=' + $DB_PORT)
        ) | Set-Content -Path $EXTERNAL_MYSQL_MARKER -Encoding ASCII
        Write-Host ("        Existing MySQL .......... connected ({0}:{1})" -f $DB_HOST, $DB_PORT)
    } else {
        Remove-Item $EXTERNAL_MYSQL_MARKER -Force -ErrorAction SilentlyContinue
        @(
            'mode=bundled',
            ('host=' + $DB_HOST),
            ('port=' + $DB_PORT),
            'service=DentalClinicMySQL'
        ) | Set-Content -Path $BUNDLED_MYSQL_MARKER -Encoding ASCII
        $mysqlProbeExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', 'SELECT 1')) -Password ""
        if ($mysqlProbeExit -ne 0) {
        if (-not (Test-Path $MYSQLD_EXE)) { Fail-Step "mysqld.exe not found." }
        $MYSQL_CONSOLE_LOG = Join-Path $LARAGON_DIR "data\mysql-console.log"
        $MYSQL_STDERR_LOG  = Join-Path $LARAGON_DIR "data\mysql-stderr.log"
        $MYSQL_DATA_ROOT = Join-Path $LARAGON_DIR "data"

        # 覆盖安装时先停止本系统注册的服务（它可能仍使用旧端口或旧配置）。
        # 绝不能按进程名批量终止 mysqld.exe，否则会误停目标机原有的
        # MySQL（例如 3306 上的 5.6）。
        $managedServiceExit = Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('query', 'DentalClinicMySQL') -Probe
        if ($managedServiceExit -eq 0) {
            Write-Host "        Stopping previous DentalClinicMySQL service..."
            Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('stop', 'DentalClinicMySQL') -Probe | Out-Null
            Start-Sleep -Seconds 5
        }

        $portInUse = $false
        try {
            $tcpConn = [System.Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() |
                Where-Object { $_.Port -eq [int]$DB_PORT }
            if ($tcpConn) { $portInUse = $true }
        } catch {}

        if ($portInUse) {
            $portDetails = Get-PortOccupancyDetails -Port ([int]$DB_PORT)
            $portMessage = "Port {0} is occupied after stopping DentalClinicMySQL. Nothing else was stopped. Please free this port or choose another port, then retry." -f $DB_PORT
            if ($portDetails) {
                Fail-Step ($portMessage + [Environment]::NewLine + $portDetails)
            }
            Fail-Step $portMessage
        }

        $mysqlIni = Join-Path $LARAGON_DIR "etc\mysql\my.ini"
        if (-not (Test-Path $MYSQL_DATA_ROOT)) {
            New-Item -ItemType Directory -Path $MYSQL_DATA_ROOT -Force | Out-Null
        }
        if (-not (Test-Path $MYSQL_DATA_DIR)) {
            New-Item -ItemType Directory -Path $MYSQL_DATA_DIR -Force | Out-Null
        }

        $mysqlBaseDirNormalized = $mysqlDir.Replace('\', '/')
        $mysqlDataDirNormalized = $MYSQL_DATA_DIR.Replace('\', '/')
        $mysqlErrorLogNormalized = $MYSQL_ERROR_LOG.Replace('\', '/')
        $dbPortString = [string]$DB_PORT

        # 自组装的 Win7 运行环境不包含 my.ini。这里必须始终生成一份只属于
        # DentalClinicMySQL 的配置，否则 --defaults-file 会指向不存在的文件，
        # 且 mysqld 会回退到 3306，与目标机原有 MySQL 冲突。
        $mysqlIniDir = Split-Path -Parent $mysqlIni
        if (-not (Test-Path $mysqlIniDir)) {
            New-Item -ItemType Directory -Path $mysqlIniDir -Force | Out-Null
        }
        @(
            '[mysqld]',
            ('basedir=' + $mysqlBaseDirNormalized),
            ('datadir=' + $mysqlDataDirNormalized),
            ('port=' + $dbPortString),
            'bind-address=127.0.0.1',
            ('log-error=' + $mysqlErrorLogNormalized),
            'character-set-server=utf8mb4',
            'collation-server=utf8mb4_unicode_ci',
            'sql-mode=NO_ENGINE_SUBSTITUTION',
            'max_allowed_packet=64M',
            '',
            '[client]',
            'host=127.0.0.1',
            ('port=' + $dbPortString),
            'default-character-set=utf8mb4'
        ) | Set-Content -Path $mysqlIni -Encoding ASCII

        $dataFiles = @(Get-ChildItem -Path $MYSQL_DATA_DIR -Force -ErrorAction SilentlyContinue)
        if ($dataFiles.Count -eq 0 -and $RUNTIME_FLAVOR -eq "xampp") {
            # XAMPP 包自带初始化好的 mysql\data（ibdata1 / ib_logfile* / aria_log* 等 142 个文件），
            # 正常情况下走不到这里。真走到了说明数据目录被删或复制不全 ——
            # 此时**不能**退回 --initialize-insecure：那是 MySQL 5.7 的参数，
            # MariaDB 10.4 不支持（它用 mysql_install_db），跑下去只会得到一句难懂的报错。
            Fail-Step ("MariaDB 数据目录为空: $MYSQL_DATA_DIR" + [Environment]::NewLine +
                       "XAMPP 包本应自带初始化好的数据目录，请确认安装包完整、复制未被中断。")
        }
        if ($dataFiles.Count -eq 0) {
            Write-Host "        Initializing MySQL data directory..."
            $mysqlInitExit = Invoke-NativeQuiet -FilePath $MYSQLD_EXE -Arguments @("--defaults-file=$mysqlIni", "--basedir=$mysqlDir", "--datadir=$MYSQL_DATA_DIR", '--initialize-insecure')
            if ($mysqlInitExit -ne 0) {
                $initLog = Get-LastLogLines -Paths @($MYSQL_ERROR_LOG, $MYSQL_CONSOLE_LOG)
                if ($initLog) {
                    Fail-Step ("MySQL data directory initialization failed." + [Environment]::NewLine + $initLog)
                }
                Fail-Step "MySQL data directory initialization failed."
            }
        }

        $mysqlArgs = @()
        if (Test-Path $mysqlIni) {
            $mysqlArgs += "--defaults-file=$mysqlIni"
        }
        $mysqlArgs += "--basedir=$mysqlDir"
        $mysqlArgs += "--datadir=$MYSQL_DATA_DIR"
        $mysqlArgs += "--console"
        if (Test-Path $MYSQL_CONSOLE_LOG) {
            Remove-Item $MYSQL_CONSOLE_LOG -Force -ErrorAction SilentlyContinue
        }
        if (Test-Path $MYSQL_STDERR_LOG) {
            Remove-Item $MYSQL_STDERR_LOG -Force -ErrorAction SilentlyContinue
        }
        # Windows PowerShell 2.0 无法解析“标准流重定向 + WindowStyle”的
        # Start-Process 参数组合。安装器本身已在隐藏窗口中运行，去掉
        # WindowStyle 仍不会弹出额外窗口，同时保留启动日志和进程句柄。
        $mysqldProc = Start-Process -FilePath $MYSQLD_EXE -ArgumentList $mysqlArgs -RedirectStandardOutput $MYSQL_CONSOLE_LOG -RedirectStandardError $MYSQL_STDERR_LOG -PassThru
        if (-not (Wait-MySqlReady -MySqlExe $MYSQL_EXE -ConnectionArguments $rootConnArgs -Password "" -TimeoutSeconds 60)) {
            $startupLog = Get-LastLogLines -Paths @($MYSQL_ERROR_LOG, $MYSQL_CONSOLE_LOG, $MYSQL_STDERR_LOG)
            $portDetails = Get-PortOccupancyDetails -Port ([int]$DB_PORT)
            if ($mysqldProc) {
                try {
                    $mysqldProc.Refresh()
                } catch {}
            }

            if ($mysqldProc -and $mysqldProc.HasExited) {
                $processMessage = "MySQL process exited immediately."
                try {
                    $processMessage += (" ExitCode=" + $mysqldProc.ExitCode)
                } catch {}
                if ($startupLog) {
                    Fail-Step ($processMessage + [Environment]::NewLine + $startupLog)
                }
                Fail-Step $processMessage
            }

            if ($portDetails) {
                $startupLog = ($startupLog, $portDetails | Where-Object { $_ }) -join [Environment]::NewLine
            }
            if ($startupLog) {
                Fail-Step ("MySQL startup timed out." + [Environment]::NewLine + $startupLog)
            }
            Fail-Step "MySQL startup timed out."
        }
        }
    }
    if ($USE_EXISTING_MYSQL) {
        Write-Host "        MySQL lifecycle ......... external (not managed)"
    } else {
        Write-Host "        MySQL started ........... OK"
    }

    $script:Step++
    Write-Section "Create database"
    Invoke-MySqlExternal -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', "CREATE DATABASE IF NOT EXISTS ``$DB_NAME`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")) -Password $DB_ADMIN_PASS | Out-Null
    Write-Host ("        Database ready .......... {0}" -f $DB_NAME)

    $script:Step++
    Write-Section "Configure database user"
    $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER)
    if ($USE_EXISTING_MYSQL -and $DB_USER -eq $DB_ADMIN_USER) {
        Write-Host ("        Using existing user ...... {0}" -f $DB_USER)
    } elseif ([string]::IsNullOrEmpty($DB_PASS)) {
        $DB_USER = "root"
        $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root')
        Write-Host "        Using root user without password"
    } else {
        $userExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';")) -Password $DB_ADMIN_PASS
        if ($userExit -ne 0) {
            $userExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', "ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';")) -Password $DB_ADMIN_PASS
        }
        $grantExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', "GRANT ALL PRIVILEGES ON ``$DB_NAME``.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;")) -Password $DB_ADMIN_PASS
        if ($userExit -ne 0 -or $grantExit -ne 0) {
            if ($USE_EXISTING_MYSQL) {
                $DB_USER = $DB_ADMIN_USER
                $DB_PASS = $DB_ADMIN_PASS
                $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_ADMIN_USER)
                Write-Host "        Grant failed; falling back to the existing MySQL account"
            } else {
                $DB_USER = "root"
                $DB_PASS = ""
                $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root')
                Write-Host "        Grant failed; falling back to root user"
            }
        } else {
            Write-Host ("        Dedicated user created .. {0}" -f $DB_USER)
        }
    }
    $DB_ADMIN_PASS = ""
    Remove-Item Env:DENTAL_MYSQL_ADMIN_PASSWORD -ErrorAction SilentlyContinue

    $script:Step++
    Write-Section "Generate .env"
    $ENV_TEMPLATE = Join-Path $PROJECT_DIR ".env.deploy"
    $ENV_TARGET = Join-Path $PROJECT_DIR ".env"
    if (-not (Test-Path $ENV_TEMPLATE)) {
        $ENV_TEMPLATE = Join-Path $PROJECT_DIR "deploy\.env.deploy"
    }
    $OCR_PYTHON_PATH = ""
    if (-not $SKIP_OCR -and $PYTHON_EXE) {
        $OCR_PYTHON_PATH = Join-Path $PROJECT_DIR "scripts\venv\Scripts\python.exe"
    }

    $hadOldDbPassword = Test-Path Env:DENTAL_DB_PASSWORD
    $oldDbPassword = $env:DENTAL_DB_PASSWORD
    try {
        # Windows PowerShell 2 / .NET 会把空字符串环境变量视为“不存在”。
        # 内置 MySQL 的 root 初始密码正好为空，因此不能统一走 getenv；
        # 空密码使用专用哨兵，非空密码仍经临时环境变量传递，避免出现在命令行。
        if ([string]::IsNullOrEmpty($DB_PASS)) {
            Remove-Item Env:DENTAL_DB_PASSWORD -ErrorAction SilentlyContinue
            $passwordSentinel = '__DENTAL_DB_PASSWORD_EMPTY__'
        } else {
            $env:DENTAL_DB_PASSWORD = $DB_PASS
            $passwordSentinel = '__DENTAL_DB_PASSWORD_FROM_ENV__'
        }
        if (Test-Path $ENV_TEMPLATE) {
            Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'install_render_env.php'), $ENV_TEMPLATE, $ENV_TARGET, $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $passwordSentinel, $APP_URL, $OCR_PYTHON_PATH)
            Write-Host "        .env created from .env.deploy"
        } else {
            if (-not (Test-Path $ENV_TARGET)) {
                Copy-Item (Join-Path $PROJECT_DIR ".env.example") $ENV_TARGET -Force
            }
            Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'install_update_env.php'), $ENV_TARGET, $APP_URL, $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $passwordSentinel)
            Write-Host "        .env created from .env.example"
        }
    } finally {
        if ($hadOldDbPassword) {
            $env:DENTAL_DB_PASSWORD = $oldDbPassword
        } else {
            Remove-Item Env:DENTAL_DB_PASSWORD -ErrorAction SilentlyContinue
        }
    }

    $script:Step++
    Write-Section "Generate APP_KEY"
    Set-Location $PROJECT_DIR
    if (-not (Select-String -Path $ENV_TARGET -Pattern '^APP_KEY=base64:' -Quiet -ErrorAction SilentlyContinue)) {
        Invoke-External -FilePath $PHP_EXE -Arguments @('artisan', 'key:generate', '--force', '--no-interaction')
    }
    Write-Host "        APP_KEY ................. OK"

    $script:Step++
    Write-Section "Initialize database"
    $schemaSql = Join-Path $PROJECT_DIR "database\schema.sql"
    if (-not (Test-Path $schemaSql)) {
        $schemaSql = Join-Path $PROJECT_DIR "database\schema\mysql-schema.sql"
    }
    if ($SKIP_SCHEMA_IMPORT) {
        Write-Host "        Package database import . skipped (existing data preserved)"
    } elseif (Test-Path $schemaSql) {
        $mysqlImport = '"' + $MYSQL_EXE + '" -h "' + $DB_HOST + '" -P "' + $DB_PORT + '" -u "' + $DB_USER + '"'
        $mysqlImport += ' "' + $DB_NAME + '" < "' + $schemaSql + '"'
        $schemaExit = Invoke-MySqlCmdLine -CommandLine $mysqlImport -Password $DB_PASS
        if ($schemaExit -ne 0) {
            Invoke-External -FilePath $PHP_EXE -Arguments @('artisan', 'migrate', '--force', '--no-interaction')
        }
    } else {
        Invoke-External -FilePath $PHP_EXE -Arguments @('artisan', 'migrate', '--force', '--no-interaction')
    }
    Write-Host "        Database schema ......... OK"

    $script:Step++
    Write-Section "Seed database"
    $seedArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER, '-D', $DB_NAME, '-N', '-e', 'SELECT 1 FROM users LIMIT 1')
    $seedProbeExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments $seedArgs -Password $DB_PASS
    if ($seedProbeExit -ne 0) {
        Invoke-External -FilePath $PHP_EXE -Arguments @('artisan', 'db:seed', '--force', '--no-interaction')
        Write-Host "        Seed data initialized .... OK"
    } else {
        Write-Host "        Existing data found ...... skipped"
    }

    # 菜单同步无条件执行（不受上面的「已有数据」判断影响）：
    # MenuItemsSeeder 不在 DatabaseSeeder 中，且它是侧边栏菜单的唯一定义。
    # 按 title_key 幂等 upsert —— 既有项就地更新、未定义项只报告不删除，
    # 因此对随包导入了菜单数据的库同样安全。
    Invoke-External -FilePath $PHP_EXE -Arguments @('artisan', 'db:seed', '--class=MenuItemsSeeder', '--force', '--no-interaction')
    Write-Host "        Sidebar menu synced ...... OK"

    $script:Step++
    Write-Section "Create storage link"
    $storageLinkExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @('artisan', 'storage:link', '--force', '--no-interaction')
    if ($storageLinkExit -ne 0 -and -not (Test-Path (Join-Path $PROJECT_DIR 'public\storage'))) {
        Invoke-CmdLine ('mklink /D "' + (Join-Path $PROJECT_DIR 'public\storage') + '" "' + (Join-Path $PROJECT_DIR 'storage\app\public') + '"') | Out-Null
    }
    Write-Host "        Storage link ............ OK"

    $script:Step++
    Write-Section "Optimize caches"
    # 注意：这里缓存的是本步骤之前的 .env。后面的「Configure OCR environment」
    # 步骤可能把 OCR_ENABLED 改写为 false，那次改动要靠「Final validation」
    # 步骤重跑一次 config:cache 才会生效 —— 删除那次重跑会让 OCR 降级开关静默失效。
    # 三个 cache 各自独立报告成败。
    #
    # 原先是无论结果如何都打一句 "Cache optimization ...... OK"，于是
    # route:cache 失败、回退到 route:clear 这种降级完全看不出来 ——
    # 2026-08-03 那次装机就是：laravel.log 里有 route:cache 的 LogicException
    # （68 个路由名冲突），装机日志却显示 OK，问题因此潜伏了很久。
    # 回退本身是对的（宁可没有缓存也不要坏缓存），但必须说出来。
    $degraded = @()

    $configCacheExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @('artisan', 'config:cache', '--no-interaction')
    if ($configCacheExit -ne 0) {
        Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @('artisan', 'config:clear', '--no-interaction') | Out-Null
        $degraded += 'config'
    }

    $routeCacheExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @('artisan', 'route:cache', '--no-interaction')
    if ($routeCacheExit -ne 0) {
        Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @('artisan', 'route:clear', '--no-interaction') | Out-Null
        $degraded += 'route'
    }

    $viewCacheExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @('artisan', 'view:cache', '--no-interaction')
    if ($viewCacheExit -ne 0) { $degraded += 'view' }

    if ($degraded.Count -eq 0) {
        Write-Host "        Cache optimization ...... OK (config/route/view)"
    } else {
        Write-Host ("        Cache optimization ...... warning（{0} 缓存未生成，已回退为不缓存）" -f ($degraded -join '/'))
        Write-Host ("        应用仍可运行，但每个请求都要重新解析，性能下降。原因见 {0}" -f (Join-Path $PROJECT_DIR 'storage\logs'))
    }

    $script:Step++
    Write-Section "Configure log cleanup task"
    $logTask = 'forfiles /p "' + (Join-Path $PROJECT_DIR 'storage\logs') + '" /s /m *.log /d -30 /c "cmd /c del @path" 2>nul'
    $logTaskExit = Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/create', '/tn', 'DentalClinic-LogCleanup', '/tr', $logTask, '/sc', 'weekly', '/d', 'MON', '/st', '03:00', '/ru', 'SYSTEM', '/f')
    if ($logTaskExit -eq 0) {
        Write-Host "        Log cleanup task ........ OK"
    } else {
        Write-Host "        Log cleanup task ........ warning"
    }

    $script:Step++
    Write-Section "Configure OCR environment"
    if ($SKIP_OCR) {
        Write-Host "        OCR ..................... skipped（工作日志改为手工录入）"
        if (Test-Path $ENV_TARGET) {
            Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_ENABLED', 'false')
        }
    } else {
        $OCR_VENV = Join-Path $PROJECT_DIR "scripts\venv"

        # 优先用锁文件：离线 wheels 是构建时按 requirements-lock.txt 以 --no-deps
        # 下载的那 81 个精确版本。若在这里用只有 5 个顶层包的 requirements.txt，
        # pip 得自己在 wheels 目录里重新解析依赖树，解出来的版本一旦和已下载的
        # 对不上就整体失败，然后回退在线安装 —— 而目标机通常没有外网，最终表现为
        # 一次漫长的超时，真正的原因（离线解析失败）被埋在日志前半段。
        $OCR_REQUIREMENTS = Join-Path $PROJECT_DIR "scripts\requirements-lock.txt"
        if (-not (Test-Path $OCR_REQUIREMENTS)) {
            $OCR_REQUIREMENTS = Join-Path $PROJECT_DIR "scripts\requirements.txt"
        }
        $OCR_WHEELS_DIR = Join-Path $INSTALL_DIR "ocr-wheels"
        if (-not (Test-Path $OCR_WHEELS_DIR)) { $OCR_WHEELS_DIR = Join-Path $PROJECT_DIR "ocr-wheels" }
        if (-not (Test-Path $OCR_WHEELS_DIR)) { $OCR_WHEELS_DIR = Join-Path $PROJECT_DIR "scripts\wheels" }
        $OCR_SCRIPT = Join-Path $PROJECT_DIR "scripts\ocr_server.py"
        $OCR_HEALTH_URL = "http://127.0.0.1:5000/health"
        $OCR_LOG_DIR = Join-Path $PROJECT_DIR "storage\logs"
        $OCR_INSTALL_LOG = Join-Path $OCR_LOG_DIR "ocr-install.log"
        $OCR_VERIFY_LOG = Join-Path $OCR_LOG_DIR "ocr-verify.log"
        $OCR_SERVER_OUT_LOG = Join-Path $OCR_LOG_DIR "ocr-server.out.log"
        $OCR_SERVER_ERR_LOG = Join-Path $OCR_LOG_DIR "ocr-server.err.log"
        if (-not (Test-Path $OCR_LOG_DIR)) {
            New-Item -ItemType Directory -Path $OCR_LOG_DIR -Force | Out-Null
        }

        # ── Win7 版策略：OCR 属于增强功能，任何一步失败都不中止安装，
        #    而是关闭 OCR、让工作日志回落到手工录入。
        #    最常见的失败原因是 CPU 不支持 AVX 指令集（2011 年前的机器），
        #    paddlepaddle 会在 import 阶段直接以非法指令退出。
        $ocrReady = $true
        $ocrDegradeReason = ""

        if (-not (Test-Path (Join-Path $OCR_VENV "Scripts\python.exe"))) {
            Write-Host "        Creating Python virtual environment..."
            $venvExit = Invoke-NativeQuiet -FilePath $PYTHON_EXE -Arguments ($PYTHON_ARGS + @('-m', 'venv', $OCR_VENV))
            if ($venvExit -ne 0) {
                $ocrReady = $false
                $ocrDegradeReason = "OCR 虚拟环境创建失败。"
            }
        }

        if ($ocrReady) {
            if (Test-Path $OCR_REQUIREMENTS) {
                $pipExe = Join-Path $OCR_VENV "Scripts\pip.exe"
                Write-Host "        Installing OCR dependencies offline..."
                Write-Host "        Win7 may need 10-30 minutes for 81 packages (about 342 MB)."
                Write-Host ("        Progress log: {0}" -f $OCR_INSTALL_LOG)
                # 每次 pip 调用都追加，不能覆盖：这里最多跑三次（离线 → 升级 pip → 在线），
                # 用覆盖的话前面的输出会被后面冲掉，离线为什么失败就永远查不到了 ——
                # 2026-08-03 那次装机就是这样：日志里只剩最后一次在线安装的网络超时，
                # 而真正该看的是第一次离线安装的报错。
                if (Test-Path $OCR_WHEELS_DIR) {
                    $pipExit = Invoke-NativeLogged -FilePath $pipExe -Arguments @('install', '--no-index', ("--find-links=" + $OCR_WHEELS_DIR), '-r', $OCR_REQUIREMENTS, '-q') -LogPath $OCR_INSTALL_LOG
                    if ($pipExit -ne 0) {
                        Write-Host "        [警告] 离线安装失败，回退到在线安装（目标机无网络时会超时）。"
                        Write-Host ("        离线失败的详细原因见 {0}" -f $OCR_INSTALL_LOG)
                        Invoke-NativeLogged -FilePath $pipExe -Arguments @('install', '--upgrade', 'pip', '-q') -LogPath $OCR_INSTALL_LOG -Append | Out-Null
                        $pipExit = Invoke-NativeLogged -FilePath $pipExe -Arguments @('install', '-r', $OCR_REQUIREMENTS, '-q') -LogPath $OCR_INSTALL_LOG -Append
                    }
                } else {
                    Write-Host ("        [警告] 未找到离线 wheels 目录 {0}，改为在线安装。" -f $OCR_WHEELS_DIR)
                    Invoke-NativeLogged -FilePath $pipExe -Arguments @('install', '--upgrade', 'pip', '-q') -LogPath $OCR_INSTALL_LOG | Out-Null
                    $pipExit = Invoke-NativeLogged -FilePath $pipExe -Arguments @('install', '-r', $OCR_REQUIREMENTS, '-q') -LogPath $OCR_INSTALL_LOG -Append
                }

                if ($pipExit -ne 0) {
                    $ocrReady = $false
                    $ocrDegradeReason = "OCR 依赖安装失败，详见 $OCR_INSTALL_LOG。"
                } else {
                    Write-Host "        OCR dependencies ........ OK"
                }
            } else {
                $ocrReady = $false
                $ocrDegradeReason = "缺少 scripts\requirements.txt。"
            }
        }

        $ocrPythonExe = Join-Path $OCR_VENV "Scripts\python.exe"
        if ($ocrReady) {
            # 这一步同时充当 AVX 探测：无 AVX 的 CPU 上 import paddle 会异常退出。
            $ocrVerifyExit = Invoke-NativeLogged -FilePath $ocrPythonExe -Arguments @('-c', "import paddleocr, flask, PIL; print('OCR_IMPORTS_OK')") -LogPath $OCR_VERIFY_LOG
            if ($ocrVerifyExit -ne 0) {
                $ocrReady = $false
                $ocrDegradeReason = "OCR 依赖自检失败（常见原因：CPU 不支持 AVX 指令集，paddlepaddle 无法加载），详见 $OCR_VERIFY_LOG。"
            }
        }

        if (-not $ocrReady) {
            Write-Host ""
            Write-Host "        [警告] OCR 不可用，已自动关闭该功能，安装继续。" -ForegroundColor Yellow
            Write-Host ("        原因: {0}" -f $ocrDegradeReason) -ForegroundColor Yellow
            Write-Host "        影响: 工作日志的图片识别不可用，需手工录入；其余功能不受影响。" -ForegroundColor Yellow
            Write-Host ""
            if (Test-Path $ENV_TARGET) {
                # 必须就地替换：.env 模板里已有 OCR_ENABLED=true，
                # 而 Laravel 的 Env 取首次出现的值，追加无效。
                Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_ENABLED', 'false')
            }
        }

        if ($ocrReady -and (Test-Path $ENV_TARGET)) {
            if (Select-String -Path $ENV_TARGET -Pattern '^OCR_PYTHON_PATH=' -Quiet -ErrorAction SilentlyContinue) {
                Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'update_ocr_env_path.php'), $ENV_TARGET, $ocrPythonExe)
            } else {
                Add-Content -Path $ENV_TARGET -Value ""
                Add-Content -Path $ENV_TARGET -Value "# OCR Service"
                Add-Content -Path $ENV_TARGET -Value ("OCR_PYTHON_PATH=" + $ocrPythonExe)
                Add-Content -Path $ENV_TARGET -Value "OCR_TIMEOUT=300"
                Add-Content -Path $ENV_TARGET -Value "OCR_SERVER_URL=http://127.0.0.1:5000"
            }
        }

        if ($ocrReady -and -not (Test-Path $OCR_SCRIPT)) {
            $ocrReady = $false
            Write-Host "        [警告] 缺少 scripts\ocr_server.py，OCR 已关闭。" -ForegroundColor Yellow
            # 只改 $ocrReady 不够：上面那个 -not $ocrReady 的分支已经跑过了，
            # 这里再降级就没人写 .env，配置里仍是模板里的 OCR_ENABLED=true，
            # 结果是系统以为 OCR 可用、实际每次调用都失败。
            if (Test-Path $ENV_TARGET) {
                Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_ENABLED', 'false')
            }
        }

        if ($ocrReady -and -not (Test-HttpEndpoint -Url $OCR_HEALTH_URL -TimeoutMs 3000)) {
            if (Test-Path $OCR_SERVER_OUT_LOG) { Remove-Item $OCR_SERVER_OUT_LOG -Force -ErrorAction SilentlyContinue }
            if (Test-Path $OCR_SERVER_ERR_LOG) { Remove-Item $OCR_SERVER_ERR_LOG -Force -ErrorAction SilentlyContinue }

            $ocrProc = Start-Process `
                -FilePath $ocrPythonExe `
                -ArgumentList @($OCR_SCRIPT, '--host', '127.0.0.1', '--port', '5000') `
                -WorkingDirectory $PROJECT_DIR `
                -RedirectStandardOutput $OCR_SERVER_OUT_LOG `
                -RedirectStandardError $OCR_SERVER_ERR_LOG `
                -PassThru

            if (-not (Wait-HttpReady -Url $OCR_HEALTH_URL -TimeoutSeconds 120)) {
                if ($ocrProc -and -not $ocrProc.HasExited) {
                    Stop-Process -Id $ocrProc.Id -Force -ErrorAction SilentlyContinue
                }

                $ocrReady = $false
                $ocrServerLog = Get-LastLogLines -Paths @($OCR_SERVER_OUT_LOG, $OCR_SERVER_ERR_LOG)
                Write-Host "        [警告] OCR 服务健康检查未通过，已关闭 OCR，安装继续。" -ForegroundColor Yellow
                if ($ocrServerLog) { Write-Host $ocrServerLog -ForegroundColor DarkYellow }
                if (Test-Path $ENV_TARGET) {
                    Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_ENABLED', 'false')
                }
            }
        }

        if ($ocrReady) {
            Write-Host "        OCR setup ............... OK"
        } else {
            Write-Host "        OCR setup ............... 已跳过（功能降级为手工录入）"
        }
    }

    $script:Step++
    Write-Section ("Configure web server ({0})" -f $(if ($RUNTIME_FLAVOR -eq "xampp") { "Apache" } else { "Nginx" }))
    if ($RUNTIME_FLAVOR -eq "xampp") {
        # ── Apache（XAMPP）──────────────────────────────────────────────
        #
        # 顺序很重要：先跑 setup_xampp.bat，再写 vhost。
        # XAMPP 的配置里写死了 /xampp/... 这种从盘符根开始的绝对路径，
        # setup_xampp.bat 负责把它们重写到实际安装目录；先写 vhost 会被它覆盖或错配。
        $setupXampp = Join-Path $XAMPP_DIR "setup_xampp.bat"
        if (Test-Path $setupXampp) {
            # 该脚本交互式会问「是否继续」，管道喂一个换行让它走默认分支
            $setupExit = Invoke-NativeQuiet -FilePath "cmd.exe" -Arguments @('/c', "echo. | `"$setupXampp`"")
            if ($setupExit -eq 0) {
                Write-Host "        XAMPP 路径重写 .......... OK"
            } else {
                Write-Host "        XAMPP 路径重写 .......... warning（Apache 可能因内置绝对路径而起不来）"
            }
        } else {
            Write-Host "        XAMPP 路径重写 .......... 跳过（未找到 setup_xampp.bat）"
        }

        $apacheLogDir = Join-Path $APACHE_DIR 'logs'
        if (-not (Test-Path $apacheLogDir)) { New-Item -ItemType Directory -Path $apacheLogDir -Force | Out-Null }

        # vhost 落在 apache/conf/extra/，再由 httpd.conf 的 Include 引入。
        # 不写 Listen（httpd.conf 自带 Listen 80，重复声明会让 Apache 拒绝启动）——
        # 这条约束在 write_apache_vhost.php 里实现，用真 Apache 校验过。
        $apacheRoot   = (Join-Path $PROJECT_DIR 'public').Replace('\', '/')
        $vhostFile    = Join-Path $APACHE_DIR 'conf\extra\dental-vhost.conf'
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'write_apache_vhost.php'), $vhostFile, $apacheRoot, '80')

        # 幂等地把 Include 追加进 httpd.conf
        $httpdConf = Join-Path $APACHE_DIR 'conf\httpd.conf'
        $includeLine = 'Include conf/extra/dental-vhost.conf'
        if (Test-Path $httpdConf) {
            if (-not (Select-String -Path $httpdConf -Pattern 'dental-vhost\.conf' -Quiet -ErrorAction SilentlyContinue)) {
                Add-Content -Path $httpdConf -Value "" -Encoding Ascii
                Add-Content -Path $httpdConf -Value "# 牙科诊所管理系统（由 install-win.ps1 追加）" -Encoding Ascii
                Add-Content -Path $httpdConf -Value $includeLine -Encoding Ascii
            }
        } else {
            Fail-Step "Apache 主配置不存在: $httpdConf"
        }

        $apacheCheckExit = Invoke-NativeQuiet -FilePath $APACHE_EXE -Arguments @('-t')
        if ($apacheCheckExit -eq 0) {
            Write-Host "        Apache config ........... OK"
        } else {
            Write-Host "        Apache config ........... warning（httpd -t 未通过，详见上方诊断）"
        }
    } elseif ($NGINX_DIR) {
        if (-not (Test-Path $NGINX_CONF_DIR)) { New-Item -ItemType Directory -Path $NGINX_CONF_DIR -Force | Out-Null }
        $nginxRuntimeLogDir = Join-Path $PROJECT_DIR 'storage\logs'
        if (-not (Test-Path $nginxRuntimeLogDir)) { New-Item -ItemType Directory -Path $nginxRuntimeLogDir -Force | Out-Null }
        $nginxRoot = (Join-Path $PROJECT_DIR 'public').Replace('\', '/')
        $nginxConfFile = Join-Path $NGINX_CONF_DIR "auto.dental.conf"
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'write_nginx_conf.php'), $nginxConfFile, $nginxRoot, $NGINX_DIR)
        $nginxMainConf = Join-Path $LARAGON_DIR 'etc\nginx\nginx.conf'
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'write_nginx_main_conf.php'), $nginxMainConf, $NGINX_DIR, $NGINX_CONF_DIR, $nginxRuntimeLogDir)
        $nginxCheckExit = Invoke-NativeQuiet -FilePath $NGINX_EXE -Arguments @('-t', '-p', ($NGINX_DIR + '\'), '-c', $nginxMainConf)
        if ($nginxCheckExit -eq 0) {
            Write-Host "        Nginx config ............ OK"
        } else {
            Write-Host "        Nginx config ............ warning"
        }
    } else {
        Write-Host "        Nginx ................... skipped"
    }

    $script:Step++
    Write-Section "Register Windows service"
    if ($USE_EXISTING_MYSQL) {
        Write-Host "        MySQL service ........... existing (not managed)"
    } elseif ($SKIP_SERVICE) {
        Write-Host "        Windows service ......... skipped (--no-service)"
    } elseif (-not (Test-Path $MYSQLD_EXE)) {
        Write-Host "        Windows service ......... skipped (mysqld not found)"
    } else {
        $svcName = "DentalClinicMySQL"
        $mysqlIni = Join-Path $LARAGON_DIR "etc\mysql\my.ini"
        $serviceQueryExit = Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('query', $svcName) -Probe
        if ($serviceQueryExit -eq 0) {
            Write-Host "        Service already exists ... skipped"
        } else {
            $nssmExe = $null
            if (Test-Path (Join-Path $INSTALL_DIR 'nssm.exe')) { $nssmExe = Join-Path $INSTALL_DIR 'nssm.exe' }
            elseif (Test-Path (Join-Path $LARAGON_DIR 'bin\nssm\nssm.exe')) { $nssmExe = Join-Path $LARAGON_DIR 'bin\nssm\nssm.exe' }
            elseif (Test-CommandExists 'nssm') { $nssmExe = 'nssm' }

            if ($nssmExe) {
                $serviceCreateExit = Invoke-NativeQuiet -FilePath $nssmExe -Arguments @('install', $svcName, $MYSQLD_EXE, "--defaults-file=$mysqlIni")
                if ($serviceCreateExit -eq 0) {
                    Invoke-NativeQuiet -FilePath $nssmExe -Arguments @('set', $svcName, 'DisplayName', 'DentalClinic MySQL') | Out-Null
                    Invoke-NativeQuiet -FilePath $nssmExe -Arguments @('set', $svcName, 'Start', 'SERVICE_AUTO_START') | Out-Null
                    Write-Host "        Service registration .... OK (NSSM)"
                } else {
                    Write-Host "        Service registration .... warning"
                }
            } else {
                if (Test-Path $mysqlIni) {
                    $serviceCreateExit = Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('create', $svcName, ('binPath= "' + $MYSQLD_EXE + '" --defaults-file="' + $mysqlIni + '"'), 'DisplayName= DentalClinic MySQL', 'start= auto')
                } else {
                    $serviceCreateExit = Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('create', $svcName, ('binPath= "' + $MYSQLD_EXE + '"'), 'DisplayName= DentalClinic MySQL', 'start= auto')
                }
                if ($serviceCreateExit -eq 0) {
                    Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('description', $svcName, 'DentalClinic MySQL database service') | Out-Null
                    Write-Host "        Service registration .... OK (sc.exe)"
                } else {
                    Write-Host "        Service registration .... warning"
                }
            }
        }
    }

    $script:Step++
    Write-Section "Create scheduled tasks"
    if ($SKIP_SERVICE) {
        Write-Host "        Scheduler ............... skipped (--no-service)"
    } else {
        Remove-Item (Join-Path $INSTALL_DIR 'services-stopped.flag') -Force -ErrorAction SilentlyContinue
        $schedulerCommand = 'cmd.exe /c ""' + $PHP_EXE + '" "' + (Join-Path $PROJECT_DIR 'artisan') + '" schedule:run >> "' + (Join-Path $PROJECT_DIR 'storage\logs\scheduler.log') + '" 2>&1"'
        $schedulerExit = Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/create', '/tn', 'DentalClinic-Scheduler', '/tr', $schedulerCommand, '/sc', 'minute', '/mo', '1', '/ru', 'SYSTEM', '/f')
        if ($schedulerExit -eq 0) { Write-Host "        Scheduler task .......... OK" } else { Write-Host "        Scheduler task .......... warning" }

        # 旧版本单独创建的队列启动任务会绕过“已手动停止”标记，也会与统一
        # 启动器竞争。新版由 AutoStart + Watchdog 一处托管。
        Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/delete', '/tn', 'DentalClinic-QueueWorker', '/f') -Probe | Out-Null
        Write-Host "        Queue worker ............ managed by watchdog"

        # Windows 7 内置任务计划程序统一托管 Web/OCR/队列：开机启动一次，
        # 并每分钟健康检查。start-win.bat 是幂等的，只会补启缺失进程。
        $startScript = Join-Path $INSTALL_DIR 'start-win.bat'
        $autoStartCommand = 'cmd.exe /c ""' + $startScript + '" "' + $INSTALL_DIR + '" --background"'
        $autoStartExit = Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/create', '/tn', 'DentalClinic-AutoStart', '/tr', $autoStartCommand, '/sc', 'onstart', '/ru', 'SYSTEM', '/f')
        if ($autoStartExit -eq 0) { Write-Host "        Auto-start task ......... OK" } else { Write-Host "        Auto-start task ......... warning" }

        $watchdogExit = Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/create', '/tn', 'DentalClinic-ServiceWatchdog', '/tr', $autoStartCommand, '/sc', 'minute', '/mo', '1', '/ru', 'SYSTEM', '/f')
        if ($watchdogExit -eq 0) { Write-Host "        Service watchdog ........ OK" } else { Write-Host "        Service watchdog ........ warning" }

        if ($autoStartExit -eq 0) {
            Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/run', '/tn', 'DentalClinic-AutoStart') | Out-Null
            Write-Host "        Background services ..... starting"
        }
    }

    $script:Step++
    Write-Section "Final validation"
    # 必须重跑：OCR 步骤若把 OCR_ENABLED 改为 false，只有这次 config:cache
    # 能让它进入配置缓存。请勿删除（详见「Optimize caches」步骤的说明）。
    Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @('artisan', 'config:cache', '--no-interaction') | Out-Null
    Invoke-External -FilePath $PHP_EXE -Arguments @('artisan', '--version')

    $validationArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER, '-D', $DB_NAME, '-e', 'SELECT 1 FROM users LIMIT 1')
    $databaseCheckExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments $validationArgs -Password $DB_PASS
    if ($databaseCheckExit -eq 0) { Write-Host "        Database check .......... OK" } else { Write-Host "        Database check .......... warning" }

    $routeCheckExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @('artisan', 'route:list', '--compact', '--no-interaction')
    if ($routeCheckExit -eq 0) { Write-Host "        Route check ............. OK" } else { Write-Host "        Route check ............. warning" }

    Write-Host ""
    Write-Host "+=========================================================+"
    Write-Host "| Installation completed                                  |"
    Write-Host "+=========================================================+"
    Write-Host ("| Version:      {0}" -f ((Get-Content (Join-Path $PROJECT_DIR 'VERSION') -ErrorAction SilentlyContinue | Select-Object -First 1)))
    Write-Host ("| Install Dir:  {0}" -f $INSTALL_DIR)
    Write-Host ("| App URL:      {0}" -f $APP_URL)
    Write-Host "| Admin User:   admin@example.com"
    Write-Host "| Admin Pass:   password"
    Write-Host ("| Install Log:  {0}" -f $INSTALL_LOG)
    Write-Host "+=========================================================+"
    Stop-InstallTranscript
    exit 0
}
catch {
    Write-Host ""
    Write-Host "+=========================================================+"
    Write-Host "| Installation failed                                      |"
    Write-Host "+=========================================================+"
    Write-Host ("| Error: {0}" -f $_.Exception.Message)
    if ($_.InvocationInfo -and $_.InvocationInfo.PositionMessage) {
        Write-Host ("| Position: {0}" -f (($_.InvocationInfo.PositionMessage -replace '[\r\n]+', ' ').Trim()))
    }
    Write-Host "+=========================================================+"
    # 失败时把排查要用的日志一次列全 —— 装机现场通常没人会去翻目录
    Write-Host ""
    Write-Host "排查用日志:"
    Write-Host ("  安装全过程: {0}" -f $INSTALL_LOG)
    foreach ($extra in @(
        (Join-Path $LARAGON_DIR "data\mysql-error.log"),
        (Join-Path $LARAGON_DIR "data\mysql-console.log"),
        (Join-Path $LARAGON_DIR "data\mysql-stderr.log"),
        (Join-Path $PROJECT_DIR "storage\logs\ocr-install.log"),
        (Join-Path $PROJECT_DIR "storage\logs\laravel.log")
    )) {
        if (Test-Path $extra) { Write-Host ("  {0}" -f $extra) }
    }
    Write-Host ""
    Stop-InstallTranscript
    exit 1
}
