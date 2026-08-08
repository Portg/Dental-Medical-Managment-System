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
# 与 Invoke-MySqlQuiet 同样用 MYSQL_PWD 传密码，但需要**读回输出**。
# 用于枚举 root 账号的 host —— 只有查得到实际有哪些账号，才能保证
# 「收紧 root」不漏 host（见 Configure database user 那一步的注释）。
function Invoke-MySqlCapture {
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
        return (Invoke-NativeCapture -FilePath $FilePath -Arguments $Arguments)
    } finally {
        if ($hadOldPassword) {
            $env:MYSQL_PWD = $oldPassword
        } else {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        }
    }
}

function Invoke-MySqlQuiet {
    param(
        [string]$FilePath,
        [string[]]$Arguments = @(),
        [string]$Password = "",
        # 探测型调用（「MySQL 起来了没」这类）非零是**预期结果**，不该打诊断。
        # 不转发这个开关的话，每次装机日志里都会出现
        #   [诊断] 命令失败（退出码 1）: mysql.exe ... -e SELECT 1
        #   [诊断] ERROR 2002 (HY000): Can't connect ... (10061)
        # 而下一行就是「MariaDB started OK」—— 看日志的人只会以为出事了，
        # 真正的失败反而被这种噪声淹没。
        [switch]$Probe
    )

    $hadOldPassword = Test-Path Env:MYSQL_PWD
    $oldPassword = $env:MYSQL_PWD
    try {
        if ([string]::IsNullOrEmpty($Password)) {
            Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
        } else {
            $env:MYSQL_PWD = $Password
        }
        if ($Probe) {
            $exitCode = Invoke-NativeQuiet -FilePath $FilePath -Arguments $Arguments -Probe
        } else {
            $exitCode = Invoke-NativeQuiet -FilePath $FilePath -Arguments $Arguments
        }
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

    # 带 Encoding 的多行写入 API是 .NET 4+；纯净 Win7 只有 CLR 2 / .NET 3.5。
    # File.WriteAllText 带 Encoding 自 .NET 2.0 起可用。
    Write-Utf8NoBomFile -Path $Path -Lines $updated
}

function Write-Utf8NoBomFile {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        $Lines
    )
    $utf8NoBom = New-Object -TypeName System.Text.UTF8Encoding -ArgumentList $false
    $content = (@($Lines) -join [Environment]::NewLine) + [Environment]::NewLine
    [System.IO.File]::WriteAllText($Path, $content, $utf8NoBom)
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

# 必须捕获输出，不能裸执行。
#
# Windows PowerShell **2.0 的 Start-Transcript 不记录原生程序的输出**，
# 所以 `& php.exe artisan ...` 打的东西一个字都不会进安装日志。
# 2026-08-06 那次装机就是这样：日志里 [9/19] 下面直接跳到失败横幅，
# 只有一句 "Command failed: ...artisan key:generate"，artisan 到底说了什么
# 完全看不到，只能让现场再跑一遍——这是不该发生的来回。
#
# 现在与 Invoke-NativeQuiet 一致：合并 stdout/stderr 捕获下来，
# 成功时原样打出（进 transcript），失败时既走 [诊断] 又塞进异常消息，
# 让失败横幅上的 Error 行自带原因。
function Invoke-External {
    param(
        [string]$FilePath,
        [string[]]$Arguments = @(),
        [switch]$IgnoreExitCode
    )

    $savedPreference = $ErrorActionPreference
    $exitCode = 1
    $output = @()
    try {
        $ErrorActionPreference = "Continue"
        $output = @(& $FilePath @Arguments 2>&1 | ForEach-Object { "$_" })
        # $LASTEXITCODE 只在原生命令执行后才被设置；没被设置时按成功处理，
        # 否则会拿上一条命令的退出码误报（同 Invoke-NativeQuiet 的处理）。
        $exitCode = if ($null -eq $LASTEXITCODE) { 0 } else { $LASTEXITCODE }
    } catch {
        # 进程根本起不来（CreateProcess 失败，例如被安全软件拦截）。
        $exitCode = -1
        $output = @("无法启动进程: " + $_.Exception.Message)
    } finally {
        $ErrorActionPreference = $savedPreference
    }

    foreach ($line in $output) {
        if ("$line".Trim().Length -gt 0) { Write-Host ("        | {0}" -f $line) }
    }

    if (-not $IgnoreExitCode -and $exitCode -ne 0) {
        Write-NativeFailure -FilePath $FilePath -Arguments $Arguments -ExitCode $exitCode -Output $output
        $detail = ""
        foreach ($line in $output) {
            if ("$line".Trim().Length -gt 0) { $detail += [Environment]::NewLine + "    " + $line }
        }
        if (-not $detail) { $detail = [Environment]::NewLine + "    （命令没有任何输出）" }
        throw ("Command failed (exit {0}): {1} {2}{3}" -f $exitCode, $FilePath, (Format-SafeArguments $Arguments), $detail)
    }
    return $exitCode
}

# 与 Invoke-External 一样必须捕获输出。
# 之前是裸执行：cmd 里跑的 mysql / sc / schtasks 说了什么，一个字都进不了
# 安装日志（PS2 的 Start-Transcript 不记原生程序输出）。
# 2026-08-07 那次装机 [7/19] 报「设置 MySQL root 密码失败。mysql 的输出见
# 安装日志」，而日志里恰恰没有 mysql 的输出 —— 承诺了一个不存在的诊断。
function Invoke-CmdLine {
    param(
        [string]$CommandLine,
        # 探测型调用非零是正常结果，不打诊断
        [switch]$Probe
    )
    $savedPreference = $ErrorActionPreference
    $exitCode = 1
    $output = @()
    try {
        $ErrorActionPreference = "Continue"
        # cmd /S /C 遇到「命令本身以带引号的 exe 开头」时，会把第一对引号
        # 当成 /C 的外层引号剥掉。mysql.exe ... < schema.sql 正是这种形态，
        # 不补最外层这一对就会报“文件名、目录名或卷标语法不正确”。
        $cmdPayload = '"' + $CommandLine + '"'
        $output = @(& cmd.exe /D /S /C $cmdPayload 2>&1 | ForEach-Object { "$_" })
        $exitCode = if ($null -eq $LASTEXITCODE) { 0 } else { $LASTEXITCODE }
    } catch {
        $exitCode = -1
        $output = @("无法启动 cmd.exe: " + $_.Exception.Message)
    } finally {
        $ErrorActionPreference = $savedPreference
    }
    $script:LastCmdLineOutput = $output
    if ($exitCode -ne 0 -and -not $Probe) {
        # 命令行本身可能含密码（sc 的 binPath 等），走同一套掩码
        Write-Host ("        [诊断] cmd 命令失败（退出码 {0}）: {1}" -f $exitCode, (Format-SafeArguments @($CommandLine)))
        foreach ($line in $output) {
            if ("$line".Trim().Length -gt 0) { Write-Host ("        [诊断] {0}" -f $line) }
        }
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

    Write-Utf8NoBomFile -Path $phpIni -Lines $updated
}

# ── PHP 扩展 ─────────────────────────────────────────────────────────
#
# XAMPP 和 windows.php.net 的 php.ini 里，共享扩展默认大多是注释掉的。
# 这个应用有两个**硬性**依赖恰好在默认关闭的那批里：
#
#   zip  vendor/spatie/laravel-backup/config/backup.php:133 是
#        'compression_method' => ZipArchive::CM_DEFAULT —— 在**配置加载阶段**
#        求值，所以缺了它每一条 artisan 命令都会以
#        "Class \"ZipArchive\" not found" 失败。2026-08-06 22:39 那次装机
#        就死在 [9/19] key:generate 上，而根因跟 key:generate 本身无关。
#   gd   phpoffice/phpspreadsheet 的 composer.json 里 ext-gd 是 require
#        （Excel 导出走它）。
#
# bcmath 也被大量用于金额计算（Invoice / Prescription / InventoryCheck），
# 但它在 Windows 官方构建里是**编译进去的**：ext\ 下没有 php_bcmath.dll，
# php.ini 里也没有对应的注释行。所以它不需要开，只需要在下面断言里确认存在。
# intl 全仓库没有用到（没有 IntlDateFormatter / NumberFormatter），不开——
# 少开一个扩展少一份加载失败的风险。
$script:PhpExtensionsToEnable = @('zip', 'gd')
# 断言清单：装不上就早失败，别等到某个业务页面才报错。
$script:PhpExtensionsRequired = @('zip', 'gd', 'bcmath', 'mbstring', 'openssl', 'curl', 'fileinfo', 'pdo_mysql', 'tokenizer')
$script:PhpExtensionsWhy = @{
    'zip'       = 'spatie/laravel-backup 的配置在加载期就用 ZipArchive'
    'gd'        = 'phpspreadsheet（Excel 导出）的硬性依赖'
    'bcmath'    = '账单/处方/库存的金额计算'
    'mbstring'  = '中文字符串处理'
    'openssl'   = 'APP_KEY 加密与 HTTPS'
    'curl'      = '外部 HTTP 调用'
    'fileinfo'  = '上传文件类型识别'
    'pdo_mysql' = '数据库连接'
    'tokenizer' = 'Laravel 框架依赖'
}

function Ensure-PhpExtensions {
    param(
        [string]$PhpDir,
        [string[]]$Required
    )

    $phpIni = Join-Path $PhpDir "php.ini"
    if (-not (Test-Path $phpIni)) {
        return @{ Enabled = @(); MissingDll = @() }
    }
    $extDir = Join-Path $PhpDir "ext"

    $lines = @(Get-Content -Path $phpIni -ErrorAction SilentlyContinue)
    $enabled = New-Object System.Collections.Generic.List[string]
    $missingDll = New-Object System.Collections.Generic.List[string]
    $appended = New-Object System.Collections.Generic.List[string]

    foreach ($ext in $Required) {
        # 没有 DLL 就不写 extension= 行 —— 那只会让 php.exe 每次启动都报
        # "Unable to load dynamic library"，比不开更糟。
        if (-not (Test-Path (Join-Path $extDir ("php_" + $ext + ".dll")))) {
            $missingDll.Add($ext)
            continue
        }

        # 已启用？两种写法都算：extension=zip 和 extension=php_zip.dll
        $alreadyOn = $false
        foreach ($line in $lines) {
            if ($line -match ('^\s*extension\s*=\s*"?(php_)?' + [regex]::Escape($ext) + '(\.dll)?"?\s*($|;)')) {
                $alreadyOn = $true
                break
            }
        }
        if ($alreadyOn) { continue }

        # 优先把已有的注释行取消注释（保持在原来的 [extensions] 区块里），
        # 找不到才追加到文件末尾。
        $uncommented = $false
        for ($i = 0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match ('^\s*;\s*extension\s*=\s*"?(php_)?' + [regex]::Escape($ext) + '(\.dll)?"?\s*$')) {
                $lines[$i] = 'extension=' + $ext
                $uncommented = $true
                break
            }
        }
        if (-not $uncommented) { $appended.Add('extension=' + $ext) }
        $enabled.Add($ext)
    }

    if ($enabled.Count -gt 0) {
        $out = New-Object System.Collections.Generic.List[string]
        foreach ($line in $lines) { $out.Add($line) }
        if ($appended.Count -gt 0) {
            $out.Add('')
            $out.Add('; 由 install-win.ps1 追加（php.ini 里没有对应的注释行可取消注释）')
            foreach ($a in $appended) { $out.Add($a) }
        }
        Write-Utf8NoBomFile -Path $phpIni -Lines $out
    }

    return @{ Enabled = $enabled.ToArray(); MissingDll = $missingDll.ToArray() }
}

# 改完 php.ini 之后用 php -m 实测一遍。
# 早失败一步的价值很大：否则缺扩展只会在某条 artisan 命令或某个业务页面上
# 冒出一句 "Class ... not found"，跟真正的原因隔着好几层。
function Assert-PhpExtensions {
    param(
        [string]$PhpExe,
        [string[]]$Required,
        [hashtable]$Why
    )

    $probe = Invoke-NativeCapture -FilePath $PhpExe -Arguments @('-m')
    $loaded = @()
    foreach ($line in ($probe.Output -split '\r?\n')) {
        $name = "$line".Trim().ToLower()
        if ($name -and -not $name.StartsWith('[')) { $loaded += $name }
    }

    $missing = @()
    foreach ($ext in $Required) {
        if ($loaded -notcontains $ext.ToLower()) { $missing += $ext }
    }
    if ($missing.Count -eq 0) { return }

    $detail = ""
    foreach ($ext in $missing) {
        $reason = if ($Why.ContainsKey($ext)) { $Why[$ext] } else { "应用依赖" }
        $detail += [Environment]::NewLine + ("    {0} —— {1}" -f $ext, $reason)
    }
    Fail-Step ("PHP 缺少必需扩展，装下去会在后续步骤以「Class ... not found」这类信息失败:" +
               $detail + [Environment]::NewLine +
               "php.ini: " + (Join-Path (Split-Path -Parent $PhpExe) 'php.ini'))
}

# ── 在目标机静默安装 XAMPP（--runtime xampp-installer）────────────────
#
# 官方安装器是 BitRock/InstallBuilder（二进制里含 BitRock Installer 标识），
# 该家族支持：
#   --mode unattended --unattendedmodeui none --prefix <dir> --launchapps 0
# 注意这些选项字符串在安装器里搜不到（Tcl 载荷是压缩的）——「它是 BitRock」
# 是构建期实测的，「这个二进制确实接受这些参数」只能在目标机验证。
# 因此这里不假设它一定成功：装完必须**按文件是否出现**判定，而不是看退出码。
function Install-XamppRuntime {
    param(
        [string]$InstallerPath,
        [string]$Prefix
    )

    $httpd = Join-Path $Prefix "apache\bin\httpd.exe"
    $php   = Join-Path $Prefix "php\php.exe"

    if ((Test-Path $httpd) -and (Test-Path $php)) {
        # 已有 XAMPP。复用而不是覆盖：重复安装会把用户既有的数据库和
        # 配置一起动掉，比拒绝安装更糟。版本不符时明确拒绝，别装一半。
        $verProbe = Invoke-NativeCapture -FilePath $php -Arguments @('-r', 'echo PHP_VERSION;')
        $existing = "$($verProbe.Output)".Trim()
        Write-Host ("        已有 XAMPP ............... {0}（PHP {1}）" -f $Prefix, $existing)
        if ($existing -notmatch '^8\.2\.') {
            Fail-Step ("{0} 已存在，但其 PHP 是 {1}，本系统要求 8.2.x。" -f $Prefix, $existing)
        }
        Write-Host "        XAMPP 安装 ............... 复用已有安装"
        return
    }

    if (Test-Path $Prefix) {
        # 目录在但没有 httpd/php —— 上次装了一半，或者是别的东西占了这个名字。
        # 直接往上装容易得到一个半死不活的环境，不如让人先处理。
        Fail-Step ("{0} 已存在但不是完整的 XAMPP（缺 apache\bin\httpd.exe 或 php\php.exe）。" -f $Prefix +
                   [Environment]::NewLine + "请先手工删除或重命名该目录，再重新运行安装。")
    }

    if (-not (Test-Path $InstallerPath)) {
        Fail-Step ("找不到 XAMPP 安装器: {0}" -f $InstallerPath)
    }

    Write-Host ("        安装 XAMPP 到 {0} ......（约需数分钟，无界面）" -f $Prefix)
    $args = @('--mode', 'unattended', '--unattendedmodeui', 'none', '--prefix', $Prefix, '--launchapps', '0')
    $installExit = Invoke-NativeQuiet -FilePath $InstallerPath -Arguments $args

    # 判定以文件为准。BitRock 静默安装的退出码在部分版本上并不可靠，
    # 而「装出来没有」是可以直接看的。
    $waited = 0
    while ($waited -lt 60 -and -not ((Test-Path $httpd) -and (Test-Path $php))) {
        Start-Sleep -Seconds 5
        $waited += 5
    }

    if (-not ((Test-Path $httpd) -and (Test-Path $php))) {
        Fail-Step ("XAMPP 静默安装失败（安装器退出码 {0}）。" -f $installExit + [Environment]::NewLine +
                   ("期望出现: {0}" -f $httpd) + [Environment]::NewLine +
                   "若安装器不接受 --mode unattended，请改用 portable 版安装包（build.sh --runtime xampp）。")
    }
    Write-Host ("        XAMPP 安装 ............... OK（安装器退出码 {0}）" -f $installExit)
}

# ── XAMPP 内置绝对路径重写 ────────────────────────────────────────────
#
# XAMPP portable 包里的配置写死了「从盘符根开始」的 \xampp\... 路径：
#   php.ini      browscap / session.save_path / upload_tmp_dir / curl.cainfo / error_log
#   httpd.conf   ServerRoot / DocumentRoot / ScriptAlias
#   httpd-xampp.conf  LoadModule php_module / PHPINIDir / SetEnv PHPRC
#   httpd-ssl.conf    ErrorLog / TransferLog / SSLSessionCache
# 官方玩法是把包解到 C:\xampp；我们装到 {安装目录}\xampp，这些路径就全部
# 指向不存在的 C:\xampp\...。其中 browscap 是**致命**的，不是警告：
#     Warning: Cannot open "\xampp\php\extras\browscap.ini" for reading
#     Fatal error: Unable to start standard module in Unknown on line 0
# php.exe 直接起不来，连 php -v 都失败——2026-08-04 那次装机就卡在
# [4/19] 探测运行时，错误信息还被误导成「缺 VC++ 运行库」。
#
# XAMPP 自带的 setup_xampp.bat 本该干这件事，但它靠相对路径找解释器
# （set PHP_BIN=php\php.exe），必须以 xampp 目录为当前目录运行；找不到时
# 只 echo 一句再 pause，退出码仍是 0，调用方永远收到「OK」。
# 所以这里自己重写：路径确定、可幂等、失败会 Fail-Step。
#
# 必须在**任何 php.exe 调用之前**执行。
function Repair-XamppHardcodedPaths {
    param([string]$XamppDir)

    $rootBackslash = $XamppDir.TrimEnd('\')

    # 幂等性**只看文件内容**，不留标记文件。
    #
    # 第一版在 xampp 根目录写了个 .dental-xampp-root，目录没变就跳过重写。
    # 那是错的，而且只在「安装目录已存在」时才暴露：setup.bat 用
    # xcopy /E /I /H /Y 覆盖式铺运行时，php.ini 会被换回包里那份带
    # \xampp\... 的原始版本，而 xcopy 从不删除目标里多出来的文件 ——
    # 标记于是存活下来，重写被跳过，php.exe 又死在 browscap 上。
    # 记录「改过了」的标记跟不上文件在它底下被替换。
    #
    # 改成每次都扫一遍：替换规则锚定在路径起点，改完的
    # C:\DentalClinic\xampp\... 不会再次命中，所以重复执行是零改动、
    # 逐字节不变。装机、重装、升级走同一条路径，还能自愈。
    $targets = New-Object System.Collections.Generic.List[string]
    foreach ($rel in @('php\php.ini', 'mysql\bin\my.ini', 'apache\conf\httpd.conf')) {
        $candidate = Join-Path $rootBackslash $rel
        if (Test-Path $candidate) { $targets.Add($candidate) }
    }
    $extraConfDir = Join-Path $rootBackslash 'apache\conf\extra'
    if (Test-Path $extraConfDir) {
        foreach ($conf in @(Get-ChildItem -Path $extraConfDir -Filter '*.conf' -ErrorAction SilentlyContinue)) {
            $targets.Add($conf.FullName)
        }
    }
    if ($targets.Count -eq 0) {
        Fail-Step ("XAMPP 配置文件一个都没找到，安装包可能不完整: " + $rootBackslash)
    }

    # Latin-1（28591）字节与字符一一对应，读写无损。
    # 用它而不是 UTF-8/ANSI：替换之外的内容原样保留，
    # 不会把 XAMPP 配置里的德文注释之类的非 ASCII 字节转坏。
    #
    # 代价是写入的路径必须能用 Latin-1 表示，否则会被静默换成 '?'。
    # 所以先挡住非 ASCII 安装路径 —— 那种路径本来就装不成：
    # Apache 的 ServerRoot / PHP 的 extension_dir 在 Win7 上都处理不了非 ASCII。
    if ($rootBackslash -match '[^\x20-\x7E]') {
        Fail-Step ("安装路径含非 ASCII 字符，Apache / PHP 无法使用: " + $rootBackslash + [Environment]::NewLine +
                   "请改用纯英文路径重新安装，例如 C:\DentalClinic。")
    }
    $latin1 = [System.Text.Encoding]::GetEncoding(28591)

    # 只替换「路径起点」的 xampp：前一个字符必须是引号、等号或空白。
    # 这样 "/xampp/htdocs/xampp" 里第二个 xampp 不会被误伤；
    # 重写后的 "C:/DentalClinic/xampp/..." 前面是字母，也不会再次命中。
    $anchor = "(?<=[`"'=\s])"
    # Regex.Replace 的替换串里 $ 有特殊含义，安装路径可能带 $。
    $rootBs  = $rootBackslash.Replace('$', '$$')
    $rootBs2 = $rootBackslash.Replace('\', '\\').Replace('$', '$$')
    $rootFs  = $rootBackslash.Replace('\', '/').Replace('$', '$$')

    $changed = 0
    foreach ($file in $targets) {
        $text = [System.IO.File]::ReadAllText($file, $latin1)
        $before = $text
        # 顺序：先双反斜杠（httpd-xampp.conf 的 SetEnv "\\xampp\\php"），
        # 再单反斜杠（php.ini），最后正斜杠（Apache / MySQL 配置）。
        $text = [regex]::Replace($text, ($anchor + '\\{2}xampp(?=\\{2})'), $rootBs2)
        $text = [regex]::Replace($text, ($anchor + '\\xampp(?=\\)'), $rootBs)
        $text = [regex]::Replace($text, ($anchor + '/xampp(?=/)'), $rootFs)
        # httpd-ssl.conf 的 SSLSessionCache 是 "shmcb:/xampp/..." 这种带前缀
        # 的写法，路径前面不是引号，上面三条锚定规则匹配不到。
        $text = $text.Replace('shmcb:/xampp/', ('shmcb:' + $rootBackslash.Replace('\', '/') + '/'))
        if ($text -ne $before) {
            [System.IO.File]::WriteAllText($file, $text, $latin1)
            $changed++
        }
    }

    # php.ini 是唯一「改漏了就直接崩」的文件，单独复核一遍。
    $phpIniPath = Join-Path $rootBackslash 'php\php.ini'
    if (Test-Path $phpIniPath) {
        # 前导字符集刻意不含冒号：装到盘符根（C:\xampp）时 "C:\xampp\ 是正确结果，
        # 那个冒号来自盘符而不是路径分隔，含进来会误报。
        $leftover = @(Select-String -Path $phpIniPath -Pattern '["''=\s][\\/]{1,2}xampp[\\/]' -ErrorAction SilentlyContinue)
        if ($leftover.Count -gt 0) {
            Fail-Step ("php.ini 里仍残留 XAMPP 的盘符根路径（\xampp\...），php.exe 会启动失败: " + $phpIniPath +
                       [Environment]::NewLine + "首个残留行: " + $leftover[0].Line)
        }
    }

    # 配置指向的目录不一定存在（php\logs 就不在包里）。缺了 error_log
    # 写不进去、session/upload 落不了盘，都是装完才发现的问题。
    foreach ($rel in @('tmp', 'php\logs', 'apache\logs', 'apache\conf\extra')) {
        $dir = Join-Path $rootBackslash $rel
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }
    }

    # 清掉第一版留下的标记文件。新代码不读它，但装过旧版的机器上会一直躺着
    # 一个看不出用途的 .dental-xampp-root，日后排查时容易被当成还在生效。
    $staleMarker = Join-Path $rootBackslash ".dental-xampp-root"
    if (Test-Path $staleMarker) {
        Remove-Item $staleMarker -Force -ErrorAction SilentlyContinue
    }

    return @{ Changed = $changed; Total = $targets.Count }
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
        # 轮询等待：每 2 秒一次，未就绪前失败是常态
        $probeExit = Invoke-MySqlQuiet -FilePath $MySqlExe -Arguments ($ConnectionArguments + @('-e', 'SELECT 1')) -Password $Password -Probe
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
        Fail-Step "需要管理员权限。请右键「以管理员身份运行」setup.bat 或 install-win.bat 后重试。"
    }
}

function New-RandomDbPassword {
    # 避免易混淆字符与 mysql -e / .env 解析敏感字符
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'
    $rng = New-Object System.Security.Cryptography.RNGCryptoServiceProvider
    $sb = New-Object System.Text.StringBuilder 20
    # 取模会有偏置（256 % 57 != 0，前 28 个字符出现概率偏高），改用拒绝采样：
    # 只接受落在 [0, limit) 的字节，limit 是 256 对字符表长度取整后的最大整数倍。
    $limit = 256 - (256 % $chars.Length)
    $one = New-Object byte[] 1
    while ($sb.Length -lt 20) {
        $rng.GetBytes($one)
        if ([int]$one[0] -ge $limit) { continue }
        [void]$sb.Append($chars[[int]($one[0] % $chars.Length)])
    }
    return $sb.ToString()
}

function Escape-MySqlString {
    param([string]$Value)
    if ($null -eq $Value) { return '' }
    return (($Value -replace '\\', '\\') -replace "'", "''")
}

function Invoke-MySqlSqlText {
    param(
        [string]$FilePath,
        [string[]]$ConnArgs,
        [string]$Password,
        [string]$SqlText
    )
    $tmp = Join-Path $env:TEMP ('dental-mysql-' + [Guid]::NewGuid().ToString('N') + '.sql')
    try {
        $utf8NoBom = New-Object -TypeName System.Text.UTF8Encoding -ArgumentList $false
        [System.IO.File]::WriteAllText($tmp, $SqlText, $utf8NoBom)
        $argLine = ''
        foreach ($a in $ConnArgs) {
            if ($argLine.Length -gt 0) { $argLine += ' ' }
            if ($a -match '[\s"]') {
                $argLine += '"' + ($a -replace '"', '\"') + '"'
            } else {
                $argLine += $a
            }
        }
        $cmd = '"' + $FilePath + '" ' + $argLine + ' < "' + $tmp + '"'
        return (Invoke-MySqlCmdLine -CommandLine $cmd -Password $Password)
    } finally {
        Remove-Item -Path $tmp -Force -ErrorAction SilentlyContinue
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
        # 在线回退时用的 pip 索引源。默认清华镜像：这套包是发给国内诊所的，
        # 走 pypi.org 基本等于卡死（无超时无重试上限时会干等十几分钟）。
        # 传空字符串可退回 pypi.org 官方源。
        PIP_INDEX_URL = "https://pypi.tuna.tsinghua.edu.cn/simple"
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
            '^--pip-index-url$' { $i++; $config.PIP_INDEX_URL = [string]$RawArgs[$i]; continue }
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
$script:ScriptRev = "20260808-service-name-diag"
# 失败收尾要用：本次安装是否亲手拉起过裸 mysqld（交接给 Windows 服务后清零）。
$script:InstallerStartedMysqld = $false
# WMI/NSSM 注册成功不等于服务已经能由 SCM 托管。分别记录“本次注册过”和
# “已完成 net start + SCM Running + SQL 探测”，失败收尾只删除前者中的半成品。
$script:InstallerRegisteredBundledDbService = $false
$script:InstallerBundledDbServiceReady = $false
# Apache 在数据库服务注册前启动。若后续失败，必须只回滚本次注册的 Web 服务，
# 否则会留下“80 端口正常、数据库已停”的半安装状态。
$script:InstallerRegisteredApache = $false
# 覆盖安装会暂停上一版的 Scheduler / Watchdog。若本轮失败，必须恢复本次确实
# 找到并暂停的任务，否则一次失败安装会把原有系统的自愈能力永久关掉。
$script:InstallerPausedTasks = @()
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
$PIP_INDEX_URL = $cfg.PIP_INDEX_URL
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

# ── xampp-installer 形态 ─────────────────────────────────────────────
# 包内只有 xampp-installer.exe，没有运行时文件树。XAMPP 由本脚本在目标机
# 静默安装到 **C:\xampp**（它的默认位置）—— 落点对了，XAMPP 配置里那 67 处
# 写死的 \xampp\... 路径天生就是正确的，Repair-XamppHardcodedPaths 会
# 报「0 处需改写」，退化成一张安全网。
#
# 应用**不放** htdocs：setup.bat 复制应用时 C:\xampp 尚不存在。
# 应用固定在 {安装目录}\dental，由 Apache vhost 的 DocumentRoot 指过去；
# write_apache_vhost.php 接受任意路径并生成对应的 <Directory> 授权块。
# 好处是应用与运行时彻底分开：卸载应用不动 XAMPP，反之亦然。
$XAMPP_INSTALLER = Join-Path $INSTALL_DIR "xampp-installer.exe"
$XAMPP_INSTALLER_MODE = Test-Path $XAMPP_INSTALLER
if ($XAMPP_INSTALLER_MODE) {
    # 安装前缀写死 C:\xampp：换成别的目录就等于回到「要重写 67 处路径」，
    # 那正是这条路线要消掉的东西。
    $XAMPP_DIR = "C:\xampp"
}

if ($XAMPP_INSTALLER_MODE) {
    $RUNTIME_FLAVOR = "xampp"
    $RUNTIME_ROOT   = $XAMPP_DIR
    $PROJECT_DIR    = Join-Path $INSTALL_DIR "dental"
} elseif (Test-Path (Join-Path $XAMPP_DIR "apache\bin\httpd.exe")) {
    $RUNTIME_FLAVOR = "xampp"
    $RUNTIME_ROOT   = $XAMPP_DIR
    $PROJECT_DIR    = Join-Path $XAMPP_DIR "htdocs\dental"
} else {
    $RUNTIME_FLAVOR = "laragon"
    $RUNTIME_ROOT   = $LARAGON_DIR
    $PROJECT_DIR    = Join-Path $LARAGON_DIR "www\dental"
}
# artisan 一律走绝对路径。裸写 'artisan' 要靠当前目录，而 Set-Location
# 在 PowerShell 2.0 下不保证同步到进程工作目录（详见 Generate APP_KEY 那一步的注释）。
$ARTISAN = Join-Path $PROJECT_DIR "artisan"

# 数据库名称与状态目录都按运行时形态走。
#
# xampp 形态用的是 XAMPP 自带的 **MariaDB**，不额外装 MySQL —— 但此前
# 横幅写「MySQL: bundled runtime」、步骤叫「Start MySQL」，my.ini 和
# 控制台日志还都落在 $LARAGON_DIR 下，于是一个 xampp 安装会在
# C:\DentalClinic\ 里凭空长出一个 laragon\ 目录，失败提示也指向
# C:\DentalClinic\laragon\data\mysql-console.log。看起来就像装了两套数据库。
# 名字和路径都改成跟着形态走，laragon 分支保持原样不动。
if ($RUNTIME_FLAVOR -eq "xampp") {
    $DB_ENGINE_NAME  = "MariaDB"
    $DB_SERVICE_NAME = "DentalClinicMariaDB"
    $DB_STATE_DIR    = Join-Path $XAMPP_DIR "mysql"
    $DB_CONFIG_FILE  = Join-Path $XAMPP_DIR "mysql\my.ini"
    $DB_RUNTIME_LOG_DIR = Join-Path $XAMPP_DIR "mysql\data"
} else {
    $DB_ENGINE_NAME  = "MySQL"
    $DB_SERVICE_NAME = "DentalClinicMySQL"
    $DB_STATE_DIR    = Join-Path $LARAGON_DIR "data"
    $DB_CONFIG_FILE  = Join-Path $LARAGON_DIR "etc\mysql\my.ini"
    $DB_RUNTIME_LOG_DIR = Join-Path $LARAGON_DIR "data"
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
    Write-Host ("| {0}:{1}existing {2}:{3}" -f $DB_ENGINE_NAME, (" " * (13 - $DB_ENGINE_NAME.Length)), $DB_HOST, $DB_PORT)
} else {
    Write-Host ("| {0}:{1}bundled runtime" -f $DB_ENGINE_NAME, (" " * (13 - $DB_ENGINE_NAME.Length)))
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
            # 刻意只看 laragon 的数据目录：xampp 包自带**已初始化好**的
            # mysql\data，第 5 步遇到空数据目录会直接 Fail-Step（MariaDB 10.4
            # 不支持 --initialize-insecure）。把这段清理扩到 xampp 等于先删空
            # 再自己撞死。xampp 重装时数据目录由 setup.bat 的 xcopy 覆盖。
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

    # 安装期间暂停每分钟触发的计划任务。
    #
    # 不停它们的话，整个装机过程一直有另一个进程在连库 —— 而这段时间里数据库
    # 正被停掉、root 密码正被换、配置缓存正被重建。结果就是 2026-08-08 那次装机
    # 的日志：laravel.log 被灌进 67 条 2002（库停着）+ 7 条 1045（密码刚换），
    # scheduler.log 里 snooze:send 连续 FAIL 35 次。真正该看的失败埋在里面。
    #
    # 光 /end 不够 —— 那只结束当前这一次，下一分钟照样再触发，必须 /disable。
    # 任务不存在（全新安装）时 schtasks 返回非零，属正常，用 -Probe 静音。
    # 只记录本次确实存在、确实由安装器暂停的任务，catch 才能对称恢复。
    foreach ($pausedTask in @('DentalClinic-Scheduler', 'DentalClinic-ServiceWatchdog')) {
        $taskQueryExit = Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/query', '/tn', $pausedTask) -Probe
        if ($taskQueryExit -eq 0) {
            $script:InstallerPausedTasks += $pausedTask
            Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/end', '/tn', $pausedTask) -Probe | Out-Null
            Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/change', '/tn', $pausedTask, '/disable') -Probe | Out-Null
        }
    }
    Write-Host "        计划任务 ................ 安装期间已暂停"

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
    # installer 形态：先把 XAMPP 装出来，再往下探测
    if ($XAMPP_INSTALLER_MODE) {
        Install-XamppRuntime -InstallerPath $XAMPP_INSTALLER -Prefix $XAMPP_DIR
    }
    # ── XAMPP 布局：扁平，没有版本号子目录，不需要 Get-FirstDirectoryMatch ──
    $PHP_DIR    = Join-Path $XAMPP_DIR "php"
    $PHP_EXE    = Join-Path $PHP_DIR "php.exe"
    if (-not (Test-Path $PHP_EXE)) { Fail-Step "PHP not found: $PHP_EXE" }
    # 必须排在 Ensure-PhpIniForBundledRuntime 和后面所有 php.exe 调用之前：
    # XAMPP 原始 php.ini 里的 browscap = "\xampp\php\extras\browscap.ini"
    # 会让 php.exe 以 "Unable to start standard module" 直接退出。
    $xamppPathFix = Repair-XamppHardcodedPaths -XamppDir $XAMPP_DIR
    if ($xamppPathFix.Changed -eq 0) {
        Write-Host ("        XAMPP 路径重写 .......... 无需改写（{0} 个配置文件已是绝对路径）" -f $xamppPathFix.Total)
    } else {
        Write-Host ("        XAMPP 路径重写 .......... OK（改写 {0} / {1} 个配置文件）" -f $xamppPathFix.Changed, $xamppPathFix.Total)
    }
    Ensure-PhpIniForBundledRuntime -PhpDir $PHP_DIR
    $extResult = Ensure-PhpExtensions -PhpDir $PHP_DIR -Required $script:PhpExtensionsToEnable
    if ($extResult.Enabled.Count -gt 0) {
        Write-Host ("        PHP 扩展启用 ............ {0}" -f ($extResult.Enabled -join ', '))
    } else {
        Write-Host "        PHP 扩展 ................ 已就绪，无需改动"
    }
    if ($extResult.MissingDll.Count -gt 0) {
        Write-Host ("        [警告] ext\ 下缺少 DLL: {0}" -f ($extResult.MissingDll -join ', ')) -ForegroundColor Yellow
    }
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
    # 自组装的 PHP 是从 php.ini-production 起步的，共享扩展同样默认全关，
    # 缺 zip 一样会让每条 artisan 命令挂掉，所以两种形态都要处理。
    $extResult = Ensure-PhpExtensions -PhpDir $phpDir -Required $script:PhpExtensionsToEnable
    if ($extResult.Enabled.Count -gt 0) {
        Write-Host ("        PHP 扩展启用 ............ {0}" -f ($extResult.Enabled -join ', '))
    }
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
        # 按输出特征区分两类失败，别让所有情况都指向 VC++ ——
        # 2026-08-04 那次真实原因是 php.ini 的 \xampp\... 路径没重写，
        # 报错却写着「请先运行 vc_redist.x64.exe」，排查方向被带偏了一整轮。
        if ($phpVersionInfo.Output -match 'Unable to start standard module|Cannot open .*\.ini') {
            # $PHP_DIR 只在 xampp 分支定义，两个分支都会设 $env:PHPRC，用它。
            $runtimeHint += " 这是 php.ini 里的路径指向了不存在的文件（典型是 browscap / extension_dir）。" +
                            " 请确认 " + (Join-Path "$env:PHPRC" 'php.ini') + " 里的路径都是完整绝对路径（形如 " + $RUNTIME_ROOT + "\...），" +
                            "不是 XAMPP 原始包里的 \xampp\... 写法。"
        } else {
            $runtimeHint += " 此安装包内置 PHP 8.2（VS16 x64）。php.exe 无法启动最常见的原因是缺少 Visual C++ 2015-2022 (x64) 运行库；安装包根目录已附带 vc_redist.x64.exe，请先运行它。"
        }
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
    Assert-PhpExtensions -PhpExe $PHP_EXE -Required $script:PhpExtensionsRequired -Why $script:PhpExtensionsWhy
    Write-Host ("        PHP 扩展校验 ............ OK（{0}）" -f ($script:PhpExtensionsRequired -join ', '))

    if (-not (Test-Path (Join-Path $PROJECT_DIR "artisan"))) {
        Fail-Step "Project is incomplete. artisan not found in $PROJECT_DIR"
    }
    if (-not (Test-Path $HELPER_DIR)) {
        Fail-Step "batch-helpers directory is missing: $HELPER_DIR"
    }
    Write-Host "        Project files ........... OK"

    $script:Step++
    Write-Section ("Start {0}" -f $DB_ENGINE_NAME)
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

        # 「这个库是否已存在」——不存在是正常分支
        $existingDbExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-D', $DB_NAME, '-e', 'SELECT 1')) -Password $DB_ADMIN_PASS -Probe
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
            ('service=' + $DB_SERVICE_NAME)
        ) | Set-Content -Path $BUNDLED_MYSQL_MARKER -Encoding ASCII
        # 「内置 MariaDB 是否已经在跑」。没跑是**首次安装的正常状态**，
        # 下面紧接着就会把它启动起来，所以这里不能报成故障。
        $mysqlProbeExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', 'SELECT 1')) -Password "" -Probe
        # 措辞照着探测**实际能证明的事**写：它用的是空密码，非零只说明
        # 「空密码连不上」。覆盖安装时上一轮已把 root 密码加固过，即使 MariaDB
        # 正在运行也会探测失败 —— 那种情况由下面的「停服务 + 端口占用检查」兜住，
        # 但这里不能替它下「未在运行」的结论。
        if ($mysqlProbeExit -eq 0) {
            # 探测通了**不等于**通的是我们自己的实例。
            #
            # 目标机上常驻着别人的 MySQL（现场那台是 5.7，就在 3306）。它若恰好
            # 允许空密码 root，上面这次探测会连到**它**身上，而下面的代码会把它
            # 当成「内置 MariaDB 已在运行」直接复用 —— 接着就是在别人的实例里建库、
            # 把别人的 root 密码改成随机值（三个 host 全覆盖）、往里导 schema。
            # 那是把人家的数据库当场打死，比装不上严重得多。
            #
            # 所以复用之前必须验明正身：@@basedir 必须落在本安装目录内。
            # 这里刻意「不确定就停」——验不了身份的复用一律拒绝，让人显式选择
            # --db-port（内置库换端口共存）或 --use-existing-mysql（明确复用）。
            $baseDirProbe = Invoke-MySqlCapture -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-N', '-B', '-e', 'SELECT @@basedir')) -Password ""
            $probeBaseDir = ''
            if ($baseDirProbe.ExitCode -eq 0) { $probeBaseDir = ($baseDirProbe.Output -split "`r?`n")[0].Trim() }
            # 比对时两边都补上结尾的 '/'：否则安装到 C:\Dental 时，
            # 一个 basedir 在 C:\DentalClinic 的外部实例会因为前缀相同被误判成自己人。
            $normalizedProbeBase = $probeBaseDir.Replace('\', '/').TrimEnd('/').ToLowerInvariant() + '/'
            $normalizedOurBase   = $INSTALL_DIR.Replace('\', '/').TrimEnd('/').ToLowerInvariant() + '/'
            if ($probeBaseDir -and $normalizedProbeBase.StartsWith($normalizedOurBase)) {
                Write-Host "        MariaDB 探测 ............ 已在运行，复用（basedir 已验明）"
            } else {
                $foreignDetail = if ($probeBaseDir) { $probeBaseDir } else { "（无法读取 @@basedir）" }
                Fail-Step (
                    ("端口 {0} 上有另一个数据库在应答，它不属于本系统。" -f $DB_PORT) + [Environment]::NewLine +
                    ("  它的 basedir: {0}" -f $foreignDetail) + [Environment]::NewLine +
                    ("  本系统目录:   {0}" -f $INSTALL_DIR) + [Environment]::NewLine +
                    "已停止安装，未对该数据库做任何修改。请二选一后重试：" + [Environment]::NewLine +
                    "  1) 内置数据库换端口与它共存：安装时加 --db-port 3307" + [Environment]::NewLine +
                    "     （或用 build.sh --bundled-mysql-port 3307 构建的安装包）" + [Environment]::NewLine +
                    "  2) 明确复用目标机现有数据库：安装时加 --use-existing-mysql"
                )
            }
        } else {
            Write-Host "        MariaDB 探测 ............ 未响应，准备启动"
        }
        if ($mysqlProbeExit -ne 0) {
        if (-not (Test-Path $MYSQLD_EXE)) { Fail-Step "mysqld.exe not found." }
        $MYSQL_CONSOLE_LOG = Join-Path $DB_RUNTIME_LOG_DIR "mysql-console.log"
        $MYSQL_STDERR_LOG  = Join-Path $DB_RUNTIME_LOG_DIR "mysql-stderr.log"
        $MYSQL_DATA_ROOT = $DB_STATE_DIR

        # 覆盖安装时先停止本系统注册的服务（它可能仍使用旧端口或旧配置）。
        # 绝不能按进程名批量终止 mysqld.exe，否则会误停目标机原有的
        # MySQL（例如 3306 上的 5.6）。
        # 旧版 XAMPP 包曾误用 DentalClinicMySQL，升级时顺手停掉并在注册阶段删除
        # 这个旧别名；不会触碰目标机真正名为 MySQL 的服务。
        $managedServiceNames = @($DB_SERVICE_NAME)
        if ($RUNTIME_FLAVOR -eq 'xampp') { $managedServiceNames += 'DentalClinicMySQL' }
        foreach ($managedServiceName in ($managedServiceNames | Select-Object -Unique)) {
            $legacyServiceExit = Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('query', $managedServiceName) -Probe
            if ($legacyServiceExit -eq 0) {
                Write-Host ("        Stopping previous {0} service..." -f $managedServiceName)
                Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('stop', $managedServiceName) -Probe | Out-Null
                Start-Sleep -Seconds 2
            }
        }
        $managedServiceExit = Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('query', $DB_SERVICE_NAME) -Probe
        if ($managedServiceExit -eq 0) {
            Write-Host ("        Stopping previous {0} service..." -f $DB_SERVICE_NAME)
            Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('stop', $DB_SERVICE_NAME) -Probe | Out-Null
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
            $portMessage = "Port {0} is occupied after stopping {1}. Nothing else was stopped. Please free this port or choose another port, then retry." -f $DB_PORT, $DB_SERVICE_NAME
            if ($portDetails) {
                Fail-Step ($portMessage + [Environment]::NewLine + $portDetails)
            }
            Fail-Step $portMessage
        }

        $mysqlIni = $DB_CONFIG_FILE
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
        # 标记「这个 mysqld 是本次安装拉起的裸进程」。第 17 步会把它交接给
        # Windows 服务；但**安装中途失败时没人关它** —— 2026-08-08 11:26 那次
        # 死在第 11 步，退出后它还活着占着端口和数据目录，所以 11:27~11:29 应用
        # 报的是 1045（服务器在应答、密码不对）而不是 2002。收尾清理靠这个标记。
        $script:InstallerStartedMysqld = $true
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
        Write-Host ("        {0} started{1}OK" -f $DB_ENGINE_NAME, ("." * (17 - $DB_ENGINE_NAME.Length) + " "))
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
    } elseif (-not $USE_EXISTING_MYSQL -and [string]::IsNullOrEmpty($DB_PASS)) {
        # 内置库默认生成随机 root 密码写入 .env，避免本机任意进程空密直连
        # ── 收紧内置库的 root：给**所有**本机 root 账号设同一个随机密码 ──
        #
        # 只改 'root'@'localhost' 是不够的。XAMPP 随包的权限表里 root 有四个
        # host，全部全权限、全部空密码（从 mysql\data\mysql\global_priv 里
        # 实测得到）：localhost / 127.0.0.1 / ::1 / <XAMPP 打包机的主机名>。
        # 而两个 my.ini 都没有 skip-name-resolve，127.0.0.1 的 TCP 连接会做
        # 反向解析，既可能命中 root@localhost 也可能命中 root@127.0.0.1 ——
        # 两者都是「最具体」host，优先级由实现决定。于是只改 localhost 时：
        #   命中 127.0.0.1（仍空密码）→ 客户端送了密码就是 ERROR 1045，
        #                              装机死在后面导 schema 那一步；
        #   命中 localhost（已设密码）→ 装机能过，但其余 host 仍可空密直连，
        #                              加固形同虚设。
        # 所以这里不猜 host，而是先查出实际有哪些 root 账号再逐个处理，
        # 最后用新密码**实际回连一次**验证 —— 有这道验证就不会漏 host。
        $DB_PASS = New-RandomDbPassword
        $DB_USER = 'root'
        $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root')
        $escapedPass = Escape-MySqlString $DB_PASS

        $hostProbe = Invoke-MySqlCapture -FilePath $MYSQL_EXE `
                        -Arguments ($rootConnArgs + @('-N', '-B', '-e', "SELECT Host FROM mysql.user WHERE User='root';")) `
                        -Password $DB_ADMIN_PASS
        $rootHosts = @()
        if ($hostProbe.ExitCode -eq 0) {
            foreach ($line in ($hostProbe.Output -split '\r?\n')) {
                $h = "$line".Trim()
                if ($h) { $rootHosts += $h }
            }
        }
        # 查不到就退回三个标准本机 host（比只改 localhost 仍然安全得多）
        if ($rootHosts.Count -eq 0) {
            Write-Host "        [警告] 无法枚举 root 账号，按标准本机 host 处理" -ForegroundColor Yellow
            $rootHosts = @('localhost', '127.0.0.1', '::1')
        }

        # 本机 host 设密码；其余（打包机主机名残留）直接删掉 —— 那种账号
        # 在诊所机器上永远解析不到，留着只是个全权限空密的死账号。
        $localHosts = @('localhost', '127.0.0.1', '::1')
        $stmts = @()
        $droppedHosts = @()
        foreach ($h in $rootHosts) {
            $eh = Escape-MySqlString $h
            if ($localHosts -contains $h.ToLower()) {
                # IF EXISTS：MariaDB 10.1.3+ / MySQL 5.7.6+ 都支持，
                # 账号不存在时不会让整批 SQL 失败。
                $stmts += ("ALTER USER IF EXISTS 'root'@'" + $eh + "' IDENTIFIED BY '" + $escapedPass + "';")
            } else {
                $stmts += ("DROP USER IF EXISTS 'root'@'" + $eh + "';")
                $droppedHosts += $h
            }
        }
        $stmts += 'FLUSH PRIVILEGES;'
        $setPassExit = Invoke-MySqlSqlText -FilePath $MYSQL_EXE -ConnArgs $rootConnArgs -Password $DB_ADMIN_PASS -SqlText ($stmts -join [Environment]::NewLine)

        if ($setPassExit -ne 0) {
            # 老版本回退：没有 ALTER USER IF EXISTS 时用 SET PASSWORD FOR。
            # 它不支持 IF EXISTS，所以逐条执行、各自容错。
            $anySet = $false
            foreach ($h in $rootHosts) {
                if (-not ($localHosts -contains $h.ToLower())) { continue }
                $eh = Escape-MySqlString $h
                $legacySql = "SET PASSWORD FOR 'root'@'" + $eh + "' = PASSWORD('" + $escapedPass + "');"
                if ((Invoke-MySqlSqlText -FilePath $MYSQL_EXE -ConnArgs $rootConnArgs -Password $DB_ADMIN_PASS -SqlText $legacySql) -eq 0) {
                    $anySet = $true
                }
            }
            if ($anySet) {
                Invoke-MySqlSqlText -FilePath $MYSQL_EXE -ConnArgs $rootConnArgs -Password $DB_ADMIN_PASS -SqlText 'FLUSH PRIVILEGES;' | Out-Null
                $setPassExit = 0
            }
        }
        # 设密码 + 回连验证；任一环节不成就**退回空密码**继续装。
        #
        # 收紧 root 是可选的加固，不是装机的必要条件。空密码 root 是这个包
        # 一直以来的既有状态 —— 退回去不构成安全回归，而为了一个可选步骤
        # 让整个安装失败是不划算的。2026-08-07 23:23 那次就是这样：
        # [7/19] 直接 Fail-Step，装机中止，而当时连 mysql 说了什么都看不到。
        $hardened = $false
        if ($setPassExit -eq 0) {
            # 用新密码实际回连一次。不做这步就无法区分「密码设好了」和
            # 「设在了一个连接命中不到的 host 上」——后者会一路装到导 schema 才炸。
            $verifyExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', 'SELECT 1')) -Password $DB_PASS
            if ($verifyExit -eq 0) {
                $hardened = $true
            } else {
                Write-Host "        [警告] 密码已设置但用它连不上，可能还有别的 root@host 未覆盖" -ForegroundColor Yellow
            }
        }

        if ($hardened) {
            $DB_ADMIN_PASS = $DB_PASS
            Write-Host ("        Root password ........... generated for {0} (saved to .env)" -f ($rootHosts -join ', '))
            if ($droppedHosts.Count -gt 0) {
                Write-Host ("        Stale root accounts ..... dropped ({0})" -f ($droppedHosts -join ', '))
            }
        } else {
            # 退回既有行为：root 空密码。必须把 $DB_PASS 也清掉，
            # 否则 .env 会写上一个连不上的密码，应用直接起不来。
            $DB_PASS = ""
            $DB_ADMIN_PASS = ""
            Write-Host "        Root password ........... 加固失败，沿用空密码（不影响安装）" -ForegroundColor Yellow
            Write-Host "                                  mysql 的输出见上方 [诊断] 行"
        }
    } elseif ([string]::IsNullOrEmpty($DB_PASS)) {
        $DB_USER = "root"
        $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root')
        Write-Host "        Using root user without password"
    } else {
        $escapedPass = Escape-MySqlString $DB_PASS
        $escapedUser = Escape-MySqlString $DB_USER
        $createSql = "CREATE USER IF NOT EXISTS '$escapedUser'@'localhost' IDENTIFIED BY '$escapedPass';"
        $userExit = Invoke-MySqlSqlText -FilePath $MYSQL_EXE -ConnArgs $rootConnArgs -Password $DB_ADMIN_PASS -SqlText $createSql
        if ($userExit -ne 0) {
            $alterSql = "ALTER USER '$escapedUser'@'localhost' IDENTIFIED BY '$escapedPass';"
            $userExit = Invoke-MySqlSqlText -FilePath $MYSQL_EXE -ConnArgs $rootConnArgs -Password $DB_ADMIN_PASS -SqlText $alterSql
        }
        $grantSql = "GRANT ALL PRIVILEGES ON ``$DB_NAME``.* TO '$escapedUser'@'localhost'; FLUSH PRIVILEGES;"
        $grantExit = Invoke-MySqlSqlText -FilePath $MYSQL_EXE -ConnArgs $rootConnArgs -Password $DB_ADMIN_PASS -SqlText $grantSql
        if ($userExit -ne 0 -or $grantExit -ne 0) {
            if ($USE_EXISTING_MYSQL) {
                $DB_USER = $DB_ADMIN_USER
                $DB_PASS = $DB_ADMIN_PASS
                $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_ADMIN_USER)
                Write-Host "        Grant failed; falling back to the existing MySQL account"
            } else {
                Fail-Step "Failed to create dedicated MySQL user. Refusing to fall back to empty-password root."
            }
        } else {
            Write-Host ("        Dedicated user created .. {0}" -f $DB_USER)
        }
    }
    # 注册服务时要把当前安装器直接启动的 mysqld 优雅交接给 Windows 服务。
    # 先保留管理员密码供 mysqladmin shutdown 使用；交接完成后立即清空。
    $BUNDLED_SERVICE_ADMIN_PASS = ""
    if (-not $USE_EXISTING_MYSQL) {
        $BUNDLED_SERVICE_ADMIN_PASS = $DB_ADMIN_PASS
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

        # 已有 .env（尤其含 APP_KEY）时只合并连接参数，禁止整文件覆盖导致换 key。
        $existingEnvHasKey = $false
        if (Test-Path $ENV_TARGET) {
            $existingEnvHasKey = [bool](Select-String -Path $ENV_TARGET -Pattern '^APP_KEY=base64:' -Quiet -ErrorAction SilentlyContinue)
        }

        if ((Test-Path $ENV_TARGET) -and ($existingEnvHasKey -or $SKIP_SCHEMA_IMPORT)) {
            Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'install_update_env.php'), $ENV_TARGET, $APP_URL, $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $passwordSentinel) | Out-Null
            if ((Test-Path $ENV_TEMPLATE) -and (Test-Path (Join-Path $HELPER_DIR 'merge_missing_env.php'))) {
                # 模板含 {{占位符}}，不能直接 merge；仅在无模板占位的 .env.example 场景才有意义。
                # 这里只保证 OCR_PYTHON_PATH 等已有键被 set_env_value 更新。
            }
            if ($OCR_PYTHON_PATH -and (Test-Path (Join-Path $HELPER_DIR 'set_env_value.php'))) {
                Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_PYTHON_PATH', $OCR_PYTHON_PATH) | Out-Null
            }
            Write-Host "        .env updated (APP_KEY preserved)"
        } elseif (Test-Path $ENV_TEMPLATE) {
            Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'install_render_env.php'), $ENV_TEMPLATE, $ENV_TARGET, $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $passwordSentinel, $APP_URL, $OCR_PYTHON_PATH) | Out-Null
            Write-Host "        .env created from .env.deploy"
        } else {
            if (-not (Test-Path $ENV_TARGET)) {
                Copy-Item (Join-Path $PROJECT_DIR ".env.example") $ENV_TARGET -Force
            }
            Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'install_update_env.php'), $ENV_TARGET, $APP_URL, $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $passwordSentinel) | Out-Null
            Write-Host "        .env created from .env.example"
        }
    } finally {
        if ($hadOldDbPassword) {
            $env:DENTAL_DB_PASSWORD = $oldDbPassword
        } else {
            Remove-Item Env:DENTAL_DB_PASSWORD -ErrorAction SilentlyContinue
        }
    }

    # .env 一改完就必须清掉配置缓存，且必须在任何连库的 artisan 命令之前。
    #
    # Laravel 只要 bootstrap\cache\config.php 存在就**完全不读 .env**。
    # 覆盖安装时那个文件是上一次安装 config:cache 留下的，里面是旧的
    # 数据库密码。于是会出现这种极具误导性的组合：
    #   [7/19] root 密码已换成随机值并写进 .env（PowerShell 侧全部正确）
    #   [10/19] 导 schema 用 $DB_PASS 直连 mysql.exe —— 成功
    #   [11/19] db:seed 走 artisan，读到缓存里的旧空密码 —— 
    #           SQLSTATE[HY000] [1045] ... (using password: NO)
    # 2026-08-08 11:26 那次装机就是这样失败的。
    #
    # 整个脚本此前只在第 13 步「Optimize caches」里、且仅作为 config:cache
    # 失败时的兜底才会 config:clear —— 对第 10/11 步来说太晚了。
    # 这里用 Probe：全新安装时本来就没有缓存，clear 返回非零属正常。
    Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'config:clear', '--no-interaction') -Probe | Out-Null
    Write-Host "        配置缓存 ................ 已清除（.env 变更后必须）"

    $script:Step++
    Write-Section "Generate APP_KEY"
    # Set-Location 只改 PowerShell 的 location，**不改进程真正的工作目录**
    # （[Environment]::CurrentDirectory）。原生子进程继承的是后者，PowerShell 2.0
    # 下两者不保证同步。此前这里往下 13 处 artisan 调用全都写成相对的 'artisan'，
    # 一旦不同步就是 "Could not open input file: artisan" —— 退出码 1、错误在
    # stderr、而 PS2 的 transcript 不记原生输出，现场只能看到一句 Command failed。
    # 现在 artisan 一律用绝对路径（$ARTISAN），两个当前目录也都摆正，
    # 双保险：不依赖 cwd，即使依赖也是对的。
    Set-Location $PROJECT_DIR
    try { [Environment]::CurrentDirectory = $PROJECT_DIR } catch {}
    if (-not (Select-String -Path $ENV_TARGET -Pattern '^APP_KEY=base64:' -Quiet -ErrorAction SilentlyContinue)) {
        Invoke-External -FilePath $PHP_EXE -Arguments @($ARTISAN, 'key:generate', '--force', '--no-interaction') | Out-Null
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
            Invoke-External -FilePath $PHP_EXE -Arguments @($ARTISAN, 'migrate', '--force', '--no-interaction') | Out-Null
        }
    } else {
        Invoke-External -FilePath $PHP_EXE -Arguments @($ARTISAN, 'migrate', '--force', '--no-interaction') | Out-Null
    }
    Write-Host "        Database schema ......... OK"

    $script:Step++
    Write-Section "Seed database"
    $DEFAULT_ADMIN_SEEDED = $false
    $LOCAL_DB_SNAPSHOT = Test-Path (Join-Path $PROJECT_DIR 'database\.init-db-from-local')
    if ($LOCAL_DB_SNAPSHOT) {
        # 快照包里的数据（含账号、角色、权限、菜单和业务数据）是交付内容。
        # 任何 Seeder 都可能覆盖/补写快照，且权限不完整时会直接导致安装失败，
        # 因此本模式严格跳过全部 Seeder。
        Write-Host "        Package database snapshot . preserved"
        Write-Host "        All seeders ................ skipped"
    } else {
        $seedArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER, '-D', $DB_NAME, '-N', '-e', 'SELECT 1 FROM users LIMIT 1')
        $seedProbeExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments $seedArgs -Password $DB_PASS
        if ($seedProbeExit -ne 0) {
            Invoke-External -FilePath $PHP_EXE -Arguments @($ARTISAN, 'db:seed', '--force', '--no-interaction') | Out-Null
            $DEFAULT_ADMIN_SEEDED = $true
            Write-Host "        Seed data initialized .... OK"
        } else {
            Write-Host "        Existing data found ...... skipped"
        }

        # 普通 Schema 包没有业务快照，菜单需要由专用 Seeder 补齐。
        Invoke-External -FilePath $PHP_EXE -Arguments @($ARTISAN, 'db:seed', '--class=MenuItemsSeeder', '--force', '--no-interaction') | Out-Null
        Write-Host "        Sidebar menu synced ...... OK"
    }

    $script:Step++
    Write-Section "Create storage link"
    $storageLinkExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'storage:link', '--force', '--no-interaction')
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

    $configCacheExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'config:cache', '--no-interaction')
    if ($configCacheExit -ne 0) {
        Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'config:clear', '--no-interaction') | Out-Null
        $degraded += 'config'
    }

    $routeCacheExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'route:cache', '--no-interaction')
    if ($routeCacheExit -ne 0) {
        Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'route:clear', '--no-interaction') | Out-Null
        $degraded += 'route'
    }

    $viewCacheExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'view:cache', '--no-interaction')
    if ($viewCacheExit -ne 0) { $degraded += 'view' }

    if ($degraded.Count -eq 0) {
        Write-Host "        Cache optimization ...... OK (config/route/view)"
    } else {
        Write-Host ("        Cache optimization ...... warning（{0} 缓存未生成，已回退为不缓存）" -f ($degraded -join '/'))
        Write-Host ("        应用仍可运行，但每个请求都要重新解析，性能下降。原因见 {0}" -f (Join-Path $PROJECT_DIR 'storage\logs'))
    }

    $script:Step++
    Write-Section "Configure log cleanup task"
    # /tr 的值里本来套着三层引号（forfiles 的 /p 一层、/c 一层），经 PowerShell
    # 的原生参数数组传出去内层引号会被吞，schtasks 于是把 /c 当成自己的选项，
    # 报 ERROR: Invalid argument/option - '/c'。原字符串里还混了 2>nul —— 那是
    # shell 重定向，塞进任务命令里也不会生效。
    # 改成把清理逻辑写进一个 .bat，任务只指向该文件，引号层级降到一层。
    $logCleanupBat = Join-Path $INSTALL_DIR 'clean-logs.bat'
    @(
        '@echo off',
        'REM 由 install-win.ps1 生成：清理 30 天前的应用日志。',
        'REM 单独成文件是为了避开 schtasks /tr 的多层引号转义。',
        ('forfiles /p "' + (Join-Path $PROJECT_DIR 'storage\logs') + '" /s /m *.log /d -30 /c "cmd /c del @path" >nul 2>&1'),
        'exit /b 0'
    ) | Set-Content -Path $logCleanupBat -Encoding Ascii
    $logTaskExit = Invoke-CmdLine -CommandLine ('schtasks.exe /create /tn "DentalClinic-LogCleanup" /tr "\"' + $logCleanupBat + '\"" /sc weekly /d MON /st 03:00 /ru SYSTEM /f')
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
            Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_ENABLED', 'false') | Out-Null
        }
    } else {
        $OCR_VENV = Join-Path $PROJECT_DIR "scripts\venv"

        # 优先用锁文件：离线 wheels 是构建时按 requirements-lock.txt 以 --no-deps
        # 下载的约 80 个精确版本。若在这里用只有 5 个顶层包的 requirements.txt，
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
                Write-Host "        Win7 may need 10-30 minutes for about 80 packages (about 342 MB)."
                Write-Host ("        Progress log: {0}" -f $OCR_INSTALL_LOG)
                # 每次 pip 调用都追加，不能覆盖：这里最多跑三次（离线 → 升级 pip → 在线），
                # 用覆盖的话前面的输出会被后面冲掉，离线为什么失败就永远查不到了 ——
                # 2026-08-03 那次装机就是这样：日志里只剩最后一次在线安装的网络超时，
                # 而真正该看的是第一次离线安装的报错。
                # 在线回退的参数：镜像 + 有界超时。
                #
                # 离线路径（--no-index --find-links）根本不碰网络，不会卡。
                # 真正会卡的是**离线失败后的这条回退**：原先既没有镜像也没有
                # 超时上限，在国内网络上就是干等 —— 现场看到的「一直卡」就是它。
                # 所以三件事一起做：走国内镜像、限制单次连接超时、限制重试次数，
                # 让它要么很快成功、要么很快失败并降级为手工录入，而不是挂住。
                $pipOnlineArgs = @('install', '-r', $OCR_REQUIREMENTS, '-q',
                                   '--timeout', '20', '--retries', '2')
                if ($PIP_INDEX_URL) {
                    # trusted-host 必须跟着 index-url 走：目标机是 Win7，
                    # 其根证书库常年不更新，握手失败会被报成一个看不懂的 SSL 错误。
                    $pipHost = ''
                    try { $pipHost = ([System.Uri]$PIP_INDEX_URL).Host } catch {}
                    $pipOnlineArgs += @('--index-url', $PIP_INDEX_URL)
                    if ($pipHost) { $pipOnlineArgs += @('--trusted-host', $pipHost) }
                }

                if (Test-Path $OCR_WHEELS_DIR) {
                    $pipExit = Invoke-NativeLogged -FilePath $pipExe -Arguments @('install', '--no-index', ("--find-links=" + $OCR_WHEELS_DIR), '-r', $OCR_REQUIREMENTS, '-q') -LogPath $OCR_INSTALL_LOG
                    if ($pipExit -ne 0) {
                        Write-Host "        [警告] 离线安装失败，回退到在线安装。"
                        if ($PIP_INDEX_URL) {
                            Write-Host ("        使用镜像 {0}（超时 20 秒 / 重试 2 次）" -f $PIP_INDEX_URL)
                        } else {
                            Write-Host "        使用 pypi.org 官方源（超时 20 秒 / 重试 2 次）"
                        }
                        Write-Host ("        离线失败的详细原因见 {0}" -f $OCR_INSTALL_LOG)
                        $pipExit = Invoke-NativeLogged -FilePath $pipExe -Arguments $pipOnlineArgs -LogPath $OCR_INSTALL_LOG -Append
                    }
                } else {
                    Write-Host ("        [警告] 未找到离线 wheels 目录 {0}，改为在线安装。" -f $OCR_WHEELS_DIR)
                    $pipExit = Invoke-NativeLogged -FilePath $pipExe -Arguments $pipOnlineArgs -LogPath $OCR_INSTALL_LOG
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
                Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_ENABLED', 'false') | Out-Null
            }
        }

        if ($ocrReady -and (Test-Path $ENV_TARGET)) {
            if (Select-String -Path $ENV_TARGET -Pattern '^OCR_PYTHON_PATH=' -Quiet -ErrorAction SilentlyContinue) {
                Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'update_ocr_env_path.php'), $ENV_TARGET, $ocrPythonExe) | Out-Null
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
                Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_ENABLED', 'false') | Out-Null
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
                    Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'set_env_value.php'), $ENV_TARGET, 'OCR_ENABLED', 'false') | Out-Null
                }
            }
        }

        # 健康检查只临时启动 OCR，不把安装器子进程当成最终常驻实例。
        # 安装完成后由 DentalClinic-AutoStart / ServiceWatchdog 统一拉起，
        # 这样 stop/start、重启和失败回滚都只管理同一套进程。
        if ($ocrProc) {
            try {
                if (-not $ocrProc.HasExited) {
                    Stop-Process -Id $ocrProc.Id -Force -ErrorAction SilentlyContinue
                    $ocrProc.WaitForExit(5000) | Out-Null
                }
            } catch {}
            $ocrProc = $null
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
        # 顺序很重要：先把 XAMPP 写死的 /xampp/... 绝对路径改到实际安装目录，
        # 再写 vhost —— 否则 vhost 会被覆盖或与 ServerRoot 错配。
        # 这一步已经在「Detect runtime」里做过（php.exe 启动就依赖它，见
        # Repair-XamppHardcodedPaths 的注释）；这里只是幂等地再确认一次，
        # 有标记文件就直接跳过。
        #
        # 不用 XAMPP 自带的 setup_xampp.bat：它用相对路径找 php.exe，
        # 必须 cd 到 xampp 目录才生效，找不到时只 echo + pause，退出码仍是 0，
        # 调用方拿到的永远是「OK」。
        Repair-XamppHardcodedPaths -XamppDir $XAMPP_DIR | Out-Null

        $apacheLogDir = Join-Path $APACHE_DIR 'logs'
        if (-not (Test-Path $apacheLogDir)) { New-Item -ItemType Directory -Path $apacheLogDir -Force | Out-Null }

        # vhost 落在 apache/conf/extra/，再由 httpd.conf 的 Include 引入。
        # 不写 Listen（httpd.conf 自带 Listen 80，重复声明会让 Apache 拒绝启动）——
        # 这条约束在 write_apache_vhost.php 里实现，用真 Apache 校验过。
        $apacheRoot   = (Join-Path $PROJECT_DIR 'public').Replace('\', '/')
        $vhostFile    = Join-Path $APACHE_DIR 'conf\extra\dental-vhost.conf'
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'write_apache_vhost.php'), $vhostFile, $apacheRoot, '80') | Out-Null

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

        # ── 把 Apache 注册成 Windows 服务 ────────────────────────────
        #
        # 在这之前 $APACHE_EXE 全文只用于 httpd -t，**Apache 从来没被启动过**：
        # start-win.bat 的 WEB_MODE 只有 laragon / nginx / php-builtin 三种，
        # xampp 装完会退到 `php.exe -S`（PHP 内置开发服务器，单线程），
        # 等于换了 XAMPP 却没用上 Apache + mod_php。
        #
        # 用具名服务而不是裸进程，理由和 DentalClinicMySQL 一致：
        # 可以精确停这一个实例，绝不牵连目标机上其他 Apache；
        # 而 httpd 的 -k stop 在 Windows 上本来就是按服务名工作的。
        $apacheService = 'DentalClinicApache'
        # 先卸旧的：服务的 binPath 里带着 httpd.exe 与 conf 的绝对路径，
        # 换安装目录或重装后必须刷新，否则服务指向的还是上一次的路径。
        $apacheSvcExists = Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('query', $apacheService) -Probe
        if ($apacheSvcExists -eq 0) {
            Invoke-NativeQuiet -FilePath 'net.exe' -Arguments @('stop', $apacheService) -Probe | Out-Null
            Invoke-NativeQuiet -FilePath $APACHE_EXE -Arguments @('-k', 'uninstall', '-n', $apacheService) -Probe | Out-Null
        }
        $apacheInstallExit = Invoke-NativeQuiet -FilePath $APACHE_EXE -Arguments @('-k', 'install', '-n', $apacheService, '-f', $httpdConf)
        if ($apacheInstallExit -eq 0) {
            $script:InstallerRegisteredApache = $true
            Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('config', $apacheService, 'start=', 'auto') -Probe | Out-Null
            Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('description', $apacheService, 'DentalClinic Apache (mod_php) web server') -Probe | Out-Null
            Write-Host ("        Apache service .......... OK ({0})" -f $apacheService)

            $apacheStartExit = Invoke-NativeQuiet -FilePath 'net.exe' -Arguments @('start', $apacheService) -Probe
            # net start 对「已在运行」返回非零，所以真正的判据是端口是否响应。
            $apacheUp = $false
            for ($i = 0; $i -lt 10; $i++) {
                if (Test-HttpEndpoint -Url 'http://127.0.0.1/' -TimeoutMs 2000) { $apacheUp = $true; break }
                Start-Sleep -Seconds 2
            }
            if ($apacheUp) {
                Write-Host "        Apache started .......... OK (http://127.0.0.1/)"
            } else {
                $apacheErrorLog = Join-Path $APACHE_DIR 'logs\error.log'
                Write-Host ("        Apache started .......... warning（80 端口无响应，net start 返回 {0}）" -f $apacheStartExit) -ForegroundColor Yellow
                $apacheTail = Get-LastLogLines -Paths @($apacheErrorLog)
                if ($apacheTail) { Write-Host $apacheTail }
            }
        } else {
            Write-Host "        Apache service .......... warning（注册失败，详见上方诊断）" -ForegroundColor Yellow
        }
    } elseif ($NGINX_DIR) {
        if (-not (Test-Path $NGINX_CONF_DIR)) { New-Item -ItemType Directory -Path $NGINX_CONF_DIR -Force | Out-Null }
        $nginxRuntimeLogDir = Join-Path $PROJECT_DIR 'storage\logs'
        if (-not (Test-Path $nginxRuntimeLogDir)) { New-Item -ItemType Directory -Path $nginxRuntimeLogDir -Force | Out-Null }
        $nginxRoot = (Join-Path $PROJECT_DIR 'public').Replace('\', '/')
        $nginxConfFile = Join-Path $NGINX_CONF_DIR "auto.dental.conf"
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'write_nginx_conf.php'), $nginxConfFile, $nginxRoot, $NGINX_DIR) | Out-Null
        $nginxMainConf = Join-Path $LARAGON_DIR 'etc\nginx\nginx.conf'
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'write_nginx_main_conf.php'), $nginxMainConf, $NGINX_DIR, $NGINX_CONF_DIR, $nginxRuntimeLogDir) | Out-Null
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
        $svcName = $DB_SERVICE_NAME
        $serviceDisplayName = if ($RUNTIME_FLAVOR -eq 'xampp') { 'DentalClinic MariaDB' } else { 'DentalClinic MySQL' }
        $mysqlIni = $DB_CONFIG_FILE
        if ($RUNTIME_FLAVOR -eq 'xampp') {
            $legacySvc = 'DentalClinicMySQL'
            if ($legacySvc -ne $svcName -and (Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('query', $legacySvc) -Probe) -eq 0) {
                Invoke-NativeQuiet -FilePath 'net.exe' -Arguments @('stop', $legacySvc) -Probe | Out-Null
                Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('delete', $legacySvc) -Probe | Out-Null
                Write-Host "        Legacy MySQL service ..... removed for refresh"
            }
        }
        $serviceQueryExit = Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('query', $svcName) -Probe
        if ($serviceQueryExit -eq 0) {
            # 与 Apache 对称：已存在则停掉并删除，按当前 my.ini / basedir 重建，
            # 避免重装换端口或安装目录后仍指向旧 binPath。
            Invoke-NativeQuiet -FilePath 'net.exe' -Arguments @('stop', $svcName) -Probe | Out-Null
            Start-Sleep -Seconds 2
            $nssmExeExisting = $null
            if (Test-Path (Join-Path $INSTALL_DIR 'nssm.exe')) { $nssmExeExisting = Join-Path $INSTALL_DIR 'nssm.exe' }
            elseif (Test-Path (Join-Path $LARAGON_DIR 'bin\nssm\nssm.exe')) { $nssmExeExisting = Join-Path $LARAGON_DIR 'bin\nssm\nssm.exe' }
            elseif (Test-CommandExists 'nssm') { $nssmExeExisting = 'nssm' }
            if ($nssmExeExisting) {
                Invoke-NativeQuiet -FilePath $nssmExeExisting -Arguments @('remove', $svcName, 'confirm') -Probe | Out-Null
            }
            Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('delete', $svcName) -Probe | Out-Null
            # DeleteService 可能先标记“待删除”再返回。服务对象仍存在时立刻 Create
            # 会得到 ReturnValue=23（Service Exists）或 16（Marked For Deletion），
            # 所以等 WMI 确认它消失再重建。
            # 注：Win32_Service.Create 的返回码是 WMI 自己那套，不是 Win32 错误码 ——
            # 14 是 Service Disabled，别拿 Win32 的语义去对。
            $oldServiceGone = $false
            for ($deleteWait = 0; $deleteWait -lt 20; $deleteWait++) {
                $oldServiceObject = Get-WmiObject -Class Win32_Service -Filter ("Name='{0}'" -f $svcName) -ErrorAction SilentlyContinue
                if (-not $oldServiceObject) { $oldServiceGone = $true; break }
                Start-Sleep -Milliseconds 500
            }
            if (-not $oldServiceGone) {
                Fail-Step ("{0} service is still pending deletion; close service-management tools and retry." -f $svcName)
            }
            Write-Host "        Old MySQL service ....... removed for refresh"
        }

        $nssmExe = $null
        if (Test-Path (Join-Path $INSTALL_DIR 'nssm.exe')) { $nssmExe = Join-Path $INSTALL_DIR 'nssm.exe' }
        elseif (Test-Path (Join-Path $LARAGON_DIR 'bin\nssm\nssm.exe')) { $nssmExe = Join-Path $LARAGON_DIR 'bin\nssm\nssm.exe' }
        elseif (Test-CommandExists 'nssm') { $nssmExe = 'nssm' }

        $serviceCreateExit = -1
        $serviceReadBackFailed = $false
        if ($nssmExe) {
            $serviceCreateExit = Invoke-NativeQuiet -FilePath $nssmExe -Arguments @('install', $svcName, $MYSQLD_EXE, "--defaults-file=$mysqlIni")
            if ($serviceCreateExit -eq 0) {
                Invoke-NativeQuiet -FilePath $nssmExe -Arguments @('set', $svcName, 'DisplayName', $serviceDisplayName) | Out-Null
                Invoke-NativeQuiet -FilePath $nssmExe -Arguments @('set', $svcName, 'Start', 'SERVICE_AUTO_START') | Out-Null
                Write-Host "        Service registration .... OK (NSSM)"
            } else {
                Write-Host "        Service registration .... warning"
            }
        } else {
            # 不再让 PowerShell 2.0 代传 sc.exe create 的嵌套引号。
            # 目标 Win7 已实测两种写法都不可靠：
            #   - 原生参数数组：binPath 内层引号被重排，sc.exe 返回 1639；
            #   - cmd.exe /c + \"：服务能创建，但反斜杠被原样写进 ImagePath，
            #     net start 返回 2。
            # Win32_Service.Create 直接接收 PathName 字符串并写服务数据库，没有
            # shell/native 参数的二次解析，正好绕开这整类转义问题。
            $binPathValue = '"' + $MYSQLD_EXE + '"'
            if (Test-Path $mysqlIni) {
                $binPathValue += ' --defaults-file="' + $mysqlIni + '"'
            }
            # mysqld 不是普通控制台程序。自定义 Windows 服务名必须作为最后一个
            # 启动参数传回 mysqld；否则它按默认的 MySQL 身份接入 SCM。结果会是
            # 3307 已监听、错误日志显示 ready for connections，但
            # net start DentalClinicMariaDB 仍返回 2。
            $binPathValue += ' ' + $svcName

            # 与 $serviceCreateExit 分开：一个装 WMI 的返回码，一个装我们自己
            # 读回校验的结论。两者混用会让日志里出现不存在的「WMI 返回码」。
            try {
                $serviceClass = [wmiclass]'Win32_Service'
                $createResult = $serviceClass.Create(
                    $svcName,
                    $serviceDisplayName,
                    $binPathValue,
                    16,          # SERVICE_WIN32_OWN_PROCESS
                    1,           # SERVICE_ERROR_NORMAL
                    'Automatic',
                    $false,
                    'LocalSystem',
                    $null,
                    $null,
                    $null,
                    $null
                )
                $serviceCreateExit = [int]$createResult.ReturnValue
            } catch {
                Write-Host ("        [诊断] Win32_Service.Create 异常: {0}" -f $_.Exception.Message)
            }

            if ($serviceCreateExit -eq 0) {
                # 不能只信 Create 的返回码。立即从服务数据库读回三项关键属性，
                # 把“注册成功但 ImagePath 写错，net start 才报 2”挡在安装现场。
                $createdService = Get-WmiObject -Class Win32_Service -Filter ("Name='{0}'" -f $svcName) -ErrorAction SilentlyContinue
                $servicePathMatches = ($createdService -and ("$($createdService.PathName)" -eq $binPathValue))
                $serviceModeMatches = ($createdService -and ("$($createdService.StartMode)" -eq 'Auto'))
                if (-not $servicePathMatches -or -not $serviceModeMatches) {
                    # 这里的失败是**我们自己判定**的，不是 WMI 返回的。
                    # 不能把它塞回 $serviceCreateExit —— 那个变量装的是
                    # Win32_Service.Create 的返回码，混进一个自造的数字之后，
                    # 日志里会打出「Win32_Service.Create 返回码: 87」，
                    # 让下一个查问题的人去 WMI 文档里找一个根本不存在的 87。
                    Write-Host ("        [诊断] 服务读回校验失败（Create 返回 0，但写进服务数据库的值不对）")
                    Write-Host ("        [诊断]   期望 PathName={0}" -f $binPathValue)
                    Write-Host ("        [诊断]   实际 PathName={0}; StartMode={1}" -f $createdService.PathName, $createdService.StartMode)
                    Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('delete', $svcName) -Probe | Out-Null
                    $serviceReadBackFailed = $true
                } else {
                    try {
                        $serviceRegistryPath = 'HKLM:\SYSTEM\CurrentControlSet\Services\' + $svcName
                        Set-ItemProperty -Path $serviceRegistryPath -Name 'Description' -Value ($serviceDisplayName + ' database service') -ErrorAction Stop
                    } catch {
                        Write-Host ("        [诊断] 服务描述写入失败（不影响启动）: {0}" -f $_.Exception.Message)
                    }
                    Write-Host "        Service registration .... OK (Win32_Service.Create + read-back)"
                }
            } else {
                Write-Host ("        [诊断] Win32_Service.Create 返回码: {0}" -f $serviceCreateExit)
                Write-Host "        Service registration .... warning"
            }
        }

        # 两种失败都必须拦下：Create 本身没成功，或者 Create 说成功了但读回的
        # PathName / StartMode 不对（后者正是 2026-08-08 16:24 那次的形态 ——
        # 注册当场看着 OK，直到 net start 才报 2）。
        if ($serviceCreateExit -ne 0 -or $serviceReadBackFailed) {
            Fail-Step ("{0} service registration failed. The database would not survive installer exit or reboot." -f $svcName)
        }
        $script:InstallerRegisteredBundledDbService = $true

        # mysqld 在前面的数据库初始化阶段是安装器直接拉起的。服务注册成功并不
        # 会接管这个现有进程；安装窗口退出后它可能随父进程消失，而每分钟一次的
        # watchdog 要到下一轮才补启。2026-08-08 的现场日志正好在这个空窗里登录，
        # 10:30 被拒绝、10:31 又恢复。这里显式做一次“裸进程 -> Windows 服务”交接。
        $MYSQLADMIN_EXE = Join-Path $mysqlDir 'bin\mysqladmin.exe'
        if (-not (Test-Path $MYSQLADMIN_EXE)) {
            Fail-Step ("mysqladmin.exe not found; cannot safely hand the bundled database over to its Windows service ({0})." -f $svcName)
        }

        $shutdownExit = Invoke-MySqlQuiet -FilePath $MYSQLADMIN_EXE `
                            -Arguments @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_ADMIN_USER, 'shutdown') `
                            -Password $BUNDLED_SERVICE_ADMIN_PASS
        if ($shutdownExit -ne 0) {
            Fail-Step ("Could not gracefully stop the installer-managed {0} process before starting its Windows service." -f $DB_ENGINE_NAME)
        }

        $databaseStopped = $false
        for ($i = 0; $i -lt 15; $i++) {
            # 服务起来没 —— 轮询探测
            $probeExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', 'SELECT 1')) -Password $BUNDLED_SERVICE_ADMIN_PASS -Probe
            if ($probeExit -ne 0) { $databaseStopped = $true; break }
            Start-Sleep -Seconds 1
        }
        if (-not $databaseStopped) {
            Fail-Step "The installer-managed MySQL process did not stop; refusing to start a second instance on the same data directory."
        }

        # net start 的输出必须留下来。这一步失败过三轮（16:24 / 17:24 / 18:43），
        # 每次日志里都只有一个「exit 2」，而 net.exe 自己那句话（「系统找不到指定的
        # 文件」还是「服务没有响应控制功能」）指向完全不同的原因 —— 偏偏它被
        # -Probe 静音掉了，于是每轮都只能靠猜。用 Capture 拿回来：成功时不打印，
        # 失败时连同 SCM 的系统日志一起进失败消息。
        $serviceStartResult = Invoke-NativeCapture -FilePath 'net.exe' -Arguments @('start', $svcName)
        $serviceStartExit = $serviceStartResult.ExitCode
        $startedService = Get-WmiObject -Class Win32_Service -Filter ("Name='{0}'" -f $svcName) -ErrorAction SilentlyContinue
        $serviceState = $(if ($startedService) { "$($startedService.State)" } else { '<missing>' })
        if ($serviceStartExit -ne 0 -or -not $startedService -or $serviceState -ne 'Running') {
            $serviceLog = Get-LastLogLines -Paths @($MYSQL_ERROR_LOG)
            # SCM 在服务起不来时会往系统日志写 7000/7009/7024 这类事件，里面带的是
            # 真正的 Win32 错误码 —— 比 net.exe 的退出码具体得多。
            $scmDetail = ''
            try {
                $scmEvents = @(Get-EventLog -LogName System -Source 'Service Control Manager' -Newest 30 -ErrorAction SilentlyContinue |
                               Where-Object { $_.Message -like ('*' + $svcName + '*') })
                if ($scmEvents.Count -gt 0) {
                    $scmLines = @($scmEvents | Select-Object -First 3 | ForEach-Object {
                        ("[{0}] {1}" -f $_.TimeGenerated, (($_.Message -replace '[\r\n]+', ' ').Trim()))
                    })
                    $scmDetail = [Environment]::NewLine + "--- 系统日志（服务控制管理器）---" +
                                 [Environment]::NewLine + ($scmLines -join [Environment]::NewLine)
                }
            } catch {}
            Fail-Step (("{0} did not reach SCM Running state (net start exit {1}; State={2})." -f $svcName, $serviceStartExit, $serviceState) +
                       $(if ($serviceStartResult.Output) { [Environment]::NewLine + "--- net start 输出 ---" + [Environment]::NewLine + $serviceStartResult.Output } else { "" }) +
                       $scmDetail +
                       $(if ($serviceLog) { [Environment]::NewLine + $serviceLog } else { "" }))
        }
        if (-not (Wait-MySqlReady -MySqlExe $MYSQL_EXE -ConnectionArguments $mysqlConnArgs -Password $DB_PASS -TimeoutSeconds 60)) {
            $serviceLog = Get-LastLogLines -Paths @($MYSQL_ERROR_LOG)
            Fail-Step (("{0} reached SCM Running but SQL readiness failed." -f $svcName) +
                       $(if ($serviceLog) { [Environment]::NewLine + $serviceLog } else { "" }))
        }
        $script:InstallerBundledDbServiceReady = $true
        # 裸进程已交接给 Windows 服务，之后再失败也不该去关数据库了：
        # 那时候关掉的是服务，等于把装好的系统留在停机状态。
        $script:InstallerStartedMysqld = $false
        $BUNDLED_SERVICE_ADMIN_PASS = ""
        Write-Host "        Managed MySQL service ... started and verified"
    }

    $script:Step++
    Write-Section "Create scheduled tasks"
    # 第 2 步把 Scheduler / Watchdog 禁用了（不让它们在装机中途连库）。
    # 这里是对称的恢复点，且必须无条件执行：
    #   - 正常路径：下面 /create /f 重建时本来就会恢复启用，但万一 /create 失败
    #     （只打了 warning 不中止），任务会停在禁用态 —— 等于装完了调度永不触发。
    #   - --no-service 路径：不重建任何任务，不显式启用的话，上一版装好的调度
    #     会被这次安装顺手关掉，而日志里没有任何一行提到过这件事。
    foreach ($resumedTask in $script:InstallerPausedTasks) {
        Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/change', '/tn', $resumedTask, '/enable') -Probe | Out-Null
    }
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
    Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'config:cache', '--no-interaction') | Out-Null
    Invoke-External -FilePath $PHP_EXE -Arguments @($ARTISAN, '--version') | Out-Null

    $validationArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER, '-D', $DB_NAME, '-e', 'SELECT 1 FROM users LIMIT 1')
    $databaseCheckExit = Invoke-MySqlQuiet -FilePath $MYSQL_EXE -Arguments $validationArgs -Password $DB_PASS
    if ($databaseCheckExit -eq 0) { Write-Host "        Database check .......... OK" } else { Write-Host "        Database check .......... warning" }

    # Laravel 11 的 route:list 没有 --compact（它在 8.x 存在过），传了会抛
    # 「The "--compact" option does not exist.」并往 laravel.log 写一条 ERROR。
    # 这里只是想确认路由能枚举出来，不需要任何格式选项。
    $routeCheckExit = Invoke-NativeQuiet -FilePath $PHP_EXE -Arguments @($ARTISAN, 'route:list', '--no-interaction')
    if ($routeCheckExit -eq 0) { Write-Host "        Route check ............. OK" } else { Write-Host "        Route check ............. warning" }

    Write-Host ""
    Write-Host "+=========================================================+"
    Write-Host "| Installation completed                                  |"
    Write-Host "+=========================================================+"
    Write-Host ("| Version:      {0}" -f ((Get-Content (Join-Path $PROJECT_DIR 'VERSION') -ErrorAction SilentlyContinue | Select-Object -First 1)))
    Write-Host ("| Install Dir:  {0}" -f $INSTALL_DIR)
    Write-Host ("| App URL:      {0}" -f $APP_URL)
    if ($DEFAULT_ADMIN_SEEDED) {
        Write-Host "| Admin User:   admin / admin@example.com"
        Write-Host "| Admin Pass:   password"
    } else {
        Write-Host "| Login:        use an account from the packaged/existing database"
        Write-Host "|               (default password is not reset during reinstall)"
    }
    Write-Host ("| Install Log:  {0}" -f $INSTALL_LOG)
    Write-Host "+=========================================================+"
    Stop-InstallTranscript
    exit 0
}
catch {
    # ── 收尾：关掉本次安装亲手拉起的进程 ────────────────────────────
    #
    # 以前失败就直接 exit 1，第 5 步拉起的裸 mysqld 没人管，退出后继续占着
    # 端口和数据目录。2026-08-08 11:26 那次死在第 11 步，之后 11:27~11:29
    # 应用报的是 1045 而不是 2002 —— 服务器还在应答，就是这个被丢下的进程。
    #
    # 只关本次自己起的：InstallerStartedMysqld 标记为真、且尚未交接给
    # Windows 服务时才动手；一律走 mysqladmin shutdown，绝不按进程名杀
    # mysqld.exe（目标机上还有别人的 MySQL 5.7）。
    try {
        if ($script:InstallerStartedMysqld -and $MYSQL_EXE) {
            $mysqladminCleanup = Join-Path (Split-Path -Parent $MYSQL_EXE) 'mysqladmin.exe'
            if (Test-Path $mysqladminCleanup) {
                # 失败可能发生在加固密码前后，口令不确定，按可能性依次试。
                foreach ($cleanupPass in @($BUNDLED_SERVICE_ADMIN_PASS, $DB_PASS, $DB_ADMIN_PASS, "")) {
                    if ($null -eq $cleanupPass) { continue }
                    $cleanupExit = Invoke-MySqlQuiet -FilePath $mysqladminCleanup `
                                       -Arguments @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root', 'shutdown') `
                                       -Password $cleanupPass -Probe
                    if ($cleanupExit -eq 0) {
                        Write-Host "        已关闭本次安装启动的数据库进程（避免占用端口与数据目录）"
                        break
                    }
                }
            }
        }
    } catch {}
    try {
        # 服务已注册但没有通过 net start + SCM Running + SQL 三重验收时，它只是
        # 一个开机必失败的半成品。先尝试停止，再删除服务项；已完成交接的服务
        # 不在这里动，避免后续非数据库步骤失败时反而把可用数据库拆掉。
        if ($script:InstallerRegisteredBundledDbService -and
            -not $script:InstallerBundledDbServiceReady -and $DB_SERVICE_NAME) {
            Invoke-NativeQuiet -FilePath 'net.exe' -Arguments @('stop', $DB_SERVICE_NAME) -Probe | Out-Null
            Invoke-NativeQuiet -FilePath 'sc.exe' -Arguments @('delete', $DB_SERVICE_NAME) -Probe | Out-Null
            for ($cleanupWait = 0; $cleanupWait -lt 20; $cleanupWait++) {
                $leftoverService = Get-WmiObject -Class Win32_Service -Filter ("Name='{0}'" -f $DB_SERVICE_NAME) -ErrorAction SilentlyContinue
                if (-not $leftoverService) { break }
                Start-Sleep -Milliseconds 500
            }
            if ($leftoverService) {
                Write-Host ("        [警告] 半成品数据库服务仍在等待删除: {0}" -f $DB_SERVICE_NAME) -ForegroundColor Yellow
            } else {
                Write-Host ("        已删除未通过启动验收的数据库服务: {0}" -f $DB_SERVICE_NAME)
            }
            $script:InstallerRegisteredBundledDbService = $false
        }
    } catch {}
    try {
        if ($ocrProc -and -not $ocrProc.HasExited) {
            Stop-Process -Id $ocrProc.Id -Force -ErrorAction SilentlyContinue
            Write-Host "        已关闭本次安装启动的 OCR 进程"
        }
    } catch {}
    try {
        if ($script:InstallerRegisteredApache) {
            Invoke-NativeQuiet -FilePath 'net.exe' -Arguments @('stop', 'DentalClinicApache') -Probe | Out-Null
            Write-Host "        已停止本次安装注册的 Apache 服务（避免留下半安装站点）"
        }
    } catch {}
    try {
        # 放在所有服务/进程回滚之后恢复，避免 Watchdog 恰好触发并把刚清理的
        # Apache 或半成品数据库重新拉起来。
        foreach ($pausedTask in $script:InstallerPausedTasks) {
            Invoke-NativeQuiet -FilePath 'schtasks.exe' -Arguments @('/change', '/tn', $pausedTask, '/enable') -Probe | Out-Null
        }
        if ($script:InstallerPausedTasks.Count -gt 0) {
            Write-Host "        已恢复安装前暂停的计划任务"
        }
    } catch {}

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
    # 数据库日志目录跟着运行时形态走。写死 $LARAGON_DIR 的话，xampp 安装
    # 失败时会指着一个根本不存在的 C:\DentalClinic\laragon\data\... 让人去看。
    foreach ($extra in @(
        (Join-Path $DB_RUNTIME_LOG_DIR "mysql-error.log"),
        (Join-Path $DB_RUNTIME_LOG_DIR "mysql-console.log"),
        (Join-Path $DB_RUNTIME_LOG_DIR "mysql-stderr.log"),
        (Join-Path $PROJECT_DIR "storage\logs\ocr-install.log"),
        (Join-Path $PROJECT_DIR "storage\logs\laravel.log")
    )) {
        if (Test-Path $extra) { Write-Host ("  {0}" -f $extra) }
    }
    Write-Host ""
    Stop-InstallTranscript
    exit 1
}
