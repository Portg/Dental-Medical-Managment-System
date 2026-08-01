$ErrorActionPreference = "Stop"

# 统一控制台编码为 UTF-8，避免中文乱码
try {
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
    [Console]::InputEncoding  = [System.Text.Encoding]::UTF8
    $OutputEncoding           = [System.Text.Encoding]::UTF8
    & "$env:SystemRoot\System32\chcp.com" 65001 | Out-Null
} catch {}

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

function Find-PythonRuntime {
    $result = @{
        Exe     = $null
        Args    = @()
        Display = $null
    }

    if (Test-CommandExists "py") {
        $versionOutput = (& py -3 --version 2>&1 | Out-String).Trim()
        if ($LASTEXITCODE -eq 0 -and $versionOutput -match '^Python\s+3\.') {
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

        $versionOutput = (& $candidate --version 2>&1 | Out-String).Trim()
        if ($LASTEXITCODE -eq 0 -and $versionOutput -match '^Python\s+3\.') {
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

    [System.IO.File]::WriteAllLines($Path, $updated, [System.Text.UTF8Encoding]::new($false))
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

    & $FilePath @Arguments
    $exitCode = $LASTEXITCODE
    if (-not $IgnoreExitCode -and $exitCode -ne 0) {
        throw "Command failed: $FilePath $($Arguments -join ' ')"
    }
    return $exitCode
}

function Invoke-CmdLine {
    param([string]$CommandLine)
    & cmd.exe /c $CommandLine
    return $LASTEXITCODE
}

function Get-PhpVersionInfo {
    param([string]$PhpExe)

    $lines = @()
    & $PhpExe -v 2>&1 | ForEach-Object { $lines += $_.ToString() }
    $exitCode = $LASTEXITCODE
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

    [System.IO.File]::WriteAllLines($phpIni, $updated, [System.Text.UTF8Encoding]::new($false))
}

function Wait-MySqlReady {
    param(
        [string]$MySqlExe,
        [string[]]$Args,
        [int]$TimeoutSeconds = 60
    )

    $waited = 0
    while ($waited -lt $TimeoutSeconds) {
        Start-Sleep -Seconds 2
        & $MySqlExe @Args -e "SELECT 1" > $null 2>&1
        if ($LASTEXITCODE -eq 0) {
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
$script:ScriptRev = "20260322-ps1"
$cfg = Parse-Arguments $args

$INSTALL_DIR = $cfg.INSTALL_DIR
$DB_HOST = $cfg.DB_HOST
$DB_PORT = $cfg.DB_PORT
$DB_NAME = $cfg.DB_NAME
$DB_USER = $cfg.DB_USER
$DB_PASS = $cfg.DB_PASS
$APP_URL = $cfg.APP_URL
$SKIP_OCR = $cfg.SKIP_OCR
$SKIP_SERVICE = $cfg.SKIP_SERVICE
$SILENT_MODE = $cfg.SILENT_MODE

$LARAGON_DIR = Join-Path $INSTALL_DIR "laragon"
$PROJECT_DIR = Join-Path $LARAGON_DIR "www\dental"
$NGINX_CONF_DIR = Join-Path $LARAGON_DIR "etc\nginx\sites-enabled"
$HELPER_DIR = Join-Path $INSTALL_DIR "batch-helpers"
$LARAGON_INSTALLER = Join-Path $INSTALL_DIR "laragon-wamp.exe"

Write-Host ""
Write-Host "+=========================================================+"
Write-Host "| Dental Clinic Management System - Windows Installer     |"
Write-Host "+=========================================================+"
Write-Host ("| Script Revision: {0}" -f $script:ScriptRev)
Write-Host ("| Install Dir: {0}" -f $INSTALL_DIR)
Write-Host ("| Project Dir: {0}" -f $PROJECT_DIR)
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
                exit 0
            }
        }
        Write-Host "        Existing installation will be overwritten"
        # 清理 MySQL data 目录，确保覆盖安装时重新初始化数据库
        $oldMysqlData = Join-Path $LARAGON_DIR "data\mysql"
        if (Test-Path $oldMysqlData) {
            Remove-Item -Path $oldMysqlData -Recurse -Force -ErrorAction SilentlyContinue
            Write-Host "        Old MySQL data cleared"
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
    Write-Section "Detect Laragon runtime"
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
    $rootConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root')
    & $MYSQL_EXE @rootConnArgs -e "SELECT 1" > $null 2>&1
    if ($LASTEXITCODE -ne 0) {
        if (-not (Test-Path $MYSQLD_EXE)) { Fail-Step "mysqld.exe not found." }
        $MYSQL_CONSOLE_LOG = Join-Path $LARAGON_DIR "data\mysql-console.log"
        $MYSQL_STDERR_LOG  = Join-Path $LARAGON_DIR "data\mysql-stderr.log"
        $MYSQL_DATA_ROOT = Join-Path $LARAGON_DIR "data"

        # 检测并终止占用目标端口的残留 mysqld 进程（覆盖安装时常见）
        $portInUse = $false
        try {
            $tcpConn = [System.Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() |
                Where-Object { $_.Port -eq [int]$DB_PORT }
            if ($tcpConn) { $portInUse = $true }
        } catch {}

        if ($portInUse) {
            Write-Host ("        Port {0} in use — stopping existing mysqld..." -f $DB_PORT)
            Get-Process -Name "mysqld" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 3
            # 若端口仍被占用则报错
            try {
                $stillInUse = [System.Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() |
                    Where-Object { $_.Port -eq [int]$DB_PORT }
                if ($stillInUse) {
                    $portDetails = Get-PortOccupancyDetails -Port ([int]$DB_PORT)
                    if ($portDetails) {
                        Fail-Step (("Port {0} is still occupied after stopping mysqld. Please stop the conflicting service manually and retry." -f $DB_PORT) + [Environment]::NewLine + $portDetails)
                    }
                    Fail-Step ("Port {0} is still occupied after stopping mysqld. Please stop the conflicting service manually and retry." -f $DB_PORT)
                }
            } catch {}
            Write-Host "        Previous mysqld stopped"
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

        if (Test-Path $mysqlIni) {
            Set-IniValue -Path $mysqlIni -Key 'basedir' -Value $mysqlBaseDirNormalized
            Set-IniValue -Path $mysqlIni -Key 'datadir' -Value $mysqlDataDirNormalized
            Set-IniValue -Path $mysqlIni -Key 'log-error' -Value $mysqlErrorLogNormalized
            Set-IniValue -Path $mysqlIni -Key 'port' -Value $dbPortString
        }

        $dataFiles = @(Get-ChildItem -Path $MYSQL_DATA_DIR -Force -ErrorAction SilentlyContinue)
        if ($dataFiles.Count -eq 0) {
            Write-Host "        Initializing MySQL data directory..."
            & $MYSQLD_EXE "--defaults-file=$mysqlIni" "--basedir=$mysqlDir" "--datadir=$MYSQL_DATA_DIR" --initialize-insecure > $null 2>&1
            if ($LASTEXITCODE -ne 0) {
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
        $mysqldProc = Start-Process -FilePath $MYSQLD_EXE -ArgumentList $mysqlArgs -RedirectStandardOutput $MYSQL_CONSOLE_LOG -RedirectStandardError $MYSQL_STDERR_LOG -WindowStyle Hidden -PassThru
        if (-not (Wait-MySqlReady -MySqlExe $MYSQL_EXE -Args $rootConnArgs -TimeoutSeconds 60)) {
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
    Write-Host "        MySQL started ........... OK"

    $script:Step++
    Write-Section "Create database"
    Invoke-External -FilePath $MYSQL_EXE -Arguments ($rootConnArgs + @('-e', "CREATE DATABASE IF NOT EXISTS ``$DB_NAME`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"))
    Write-Host ("        Database ready .......... {0}" -f $DB_NAME)

    $script:Step++
    Write-Section "Configure database user"
    $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER)
    if ([string]::IsNullOrEmpty($DB_PASS)) {
        $DB_USER = "root"
        $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root')
        Write-Host "        Using root user without password"
    } else {
        & $MYSQL_EXE @rootConnArgs -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';" > $null 2>&1
        if ($LASTEXITCODE -ne 0) {
            & $MYSQL_EXE @rootConnArgs -e "ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';" > $null 2>&1
        }
        & $MYSQL_EXE @rootConnArgs -e "GRANT ALL PRIVILEGES ON ``$DB_NAME``.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;" > $null 2>&1
        if ($LASTEXITCODE -ne 0) {
            $DB_USER = "root"
            $DB_PASS = ""
            $mysqlConnArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', 'root')
            Write-Host "        Grant failed; falling back to root user"
        } else {
            $mysqlConnArgs += ('-p' + $DB_PASS)
            Write-Host ("        Dedicated user created .. {0}" -f $DB_USER)
        }
    }

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

    if (Test-Path $ENV_TEMPLATE) {
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'install_render_env.php'), $ENV_TEMPLATE, $ENV_TARGET, $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS, $APP_URL, $OCR_PYTHON_PATH)
        Write-Host "        .env created from .env.deploy"
    } else {
        if (-not (Test-Path $ENV_TARGET)) {
            Copy-Item (Join-Path $PROJECT_DIR ".env.example") $ENV_TARGET -Force
        }
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'install_update_env.php'), $ENV_TARGET, $APP_URL, $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS)
        Write-Host "        .env created from .env.example"
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
    if (Test-Path $schemaSql) {
        $mysqlImport = '"' + $MYSQL_EXE + '" -h "' + $DB_HOST + '" -P "' + $DB_PORT + '" -u "' + $DB_USER + '"'
        if (-not [string]::IsNullOrEmpty($DB_PASS)) {
            $mysqlImport += ' -p"' + $DB_PASS + '"'
        }
        $mysqlImport += ' "' + $DB_NAME + '" < "' + $schemaSql + '"'
        $schemaExit = Invoke-CmdLine $mysqlImport
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
    if (-not [string]::IsNullOrEmpty($DB_PASS)) {
        $seedArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER, ('-p' + $DB_PASS), '-D', $DB_NAME, '-N', '-e', 'SELECT 1 FROM users LIMIT 1')
    }
    & $MYSQL_EXE @seedArgs > $null 2>&1
    if ($LASTEXITCODE -ne 0) {
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
    & $PHP_EXE artisan storage:link --force --no-interaction > $null 2>&1
    if ($LASTEXITCODE -ne 0 -and -not (Test-Path (Join-Path $PROJECT_DIR 'public\storage'))) {
        Invoke-CmdLine ('mklink /D "' + (Join-Path $PROJECT_DIR 'public\storage') + '" "' + (Join-Path $PROJECT_DIR 'storage\app\public') + '"') | Out-Null
    }
    Write-Host "        Storage link ............ OK"

    $script:Step++
    Write-Section "Optimize caches"
    # 注意：这里缓存的是本步骤之前的 .env。后面的「Configure OCR environment」
    # 步骤可能把 OCR_ENABLED 改写为 false，那次改动要靠「Final validation」
    # 步骤重跑一次 config:cache 才会生效 —— 删除那次重跑会让 OCR 降级开关静默失效。
    & $PHP_EXE artisan config:cache --no-interaction > $null 2>&1
    if ($LASTEXITCODE -ne 0) { & $PHP_EXE artisan config:clear --no-interaction > $null 2>&1 }
    & $PHP_EXE artisan route:cache --no-interaction > $null 2>&1
    if ($LASTEXITCODE -ne 0) { & $PHP_EXE artisan route:clear --no-interaction > $null 2>&1 }
    & $PHP_EXE artisan view:cache --no-interaction > $null 2>&1
    Write-Host "        Cache optimization ...... OK"

    $script:Step++
    Write-Section "Configure log cleanup task"
    $logTask = 'forfiles /p "' + (Join-Path $PROJECT_DIR 'storage\logs') + '" /s /m *.log /d -30 /c "cmd /c del @path" 2>nul'
    & schtasks /create /tn "DentalClinic-LogCleanup" /tr $logTask /sc weekly /d MON /st 03:00 /ru SYSTEM /f > $null 2>&1
    if ($LASTEXITCODE -eq 0) {
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
        $OCR_REQUIREMENTS = Join-Path $PROJECT_DIR "scripts\requirements.txt"
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
            & $PYTHON_EXE @PYTHON_ARGS -m venv $OCR_VENV
            if ($LASTEXITCODE -ne 0) {
                $ocrReady = $false
                $ocrDegradeReason = "OCR 虚拟环境创建失败。"
            }
        }

        if ($ocrReady) {
            if (Test-Path $OCR_REQUIREMENTS) {
                $pipExe = Join-Path $OCR_VENV "Scripts\pip.exe"
                if (Test-Path $OCR_WHEELS_DIR) {
                    & $pipExe install --no-index --find-links=$OCR_WHEELS_DIR -r $OCR_REQUIREMENTS -q *> $OCR_INSTALL_LOG
                    if ($LASTEXITCODE -ne 0) {
                        & $pipExe install --upgrade pip -q *> $OCR_INSTALL_LOG
                        & $pipExe install -r $OCR_REQUIREMENTS -q *> $OCR_INSTALL_LOG
                    }
                } else {
                    & $pipExe install --upgrade pip -q *> $OCR_INSTALL_LOG
                    & $pipExe install -r $OCR_REQUIREMENTS -q *> $OCR_INSTALL_LOG
                }

                if ($LASTEXITCODE -ne 0) {
                    $ocrReady = $false
                    $ocrDegradeReason = "OCR 依赖安装失败，详见 $OCR_INSTALL_LOG。"
                }
            } else {
                $ocrReady = $false
                $ocrDegradeReason = "缺少 scripts\requirements.txt。"
            }
        }

        $ocrPythonExe = Join-Path $OCR_VENV "Scripts\python.exe"
        if ($ocrReady) {
            # 这一步同时充当 AVX 探测：无 AVX 的 CPU 上 import paddle 会异常退出。
            & $ocrPythonExe -c "import paddleocr, flask, PIL; print('OCR_IMPORTS_OK')" *> $OCR_VERIFY_LOG
            if ($LASTEXITCODE -ne 0) {
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
                -WindowStyle Hidden `
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
    Write-Section "Configure Nginx"
    if ($NGINX_DIR) {
        if (-not (Test-Path $NGINX_CONF_DIR)) { New-Item -ItemType Directory -Path $NGINX_CONF_DIR -Force | Out-Null }
        $nginxRoot = (Join-Path $PROJECT_DIR 'public').Replace('\', '/')
        $nginxConfFile = Join-Path $NGINX_CONF_DIR "auto.dental.conf"
        Invoke-External -FilePath $PHP_EXE -Arguments @((Join-Path $HELPER_DIR 'write_nginx_conf.php'), $nginxConfFile, $nginxRoot)
        & $NGINX_EXE -t -c (Join-Path $LARAGON_DIR 'etc\nginx\nginx.conf') > $null 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "        Nginx config ............ OK"
        } else {
            Write-Host "        Nginx config ............ warning"
        }
    } else {
        Write-Host "        Nginx ................... skipped"
    }

    $script:Step++
    Write-Section "Register Windows service"
    if ($SKIP_SERVICE) {
        Write-Host "        Windows service ......... skipped (--no-service)"
    } elseif (-not (Test-Path $MYSQLD_EXE)) {
        Write-Host "        Windows service ......... skipped (mysqld not found)"
    } else {
        $svcName = "DentalClinicMySQL"
        $mysqlIni = Join-Path $LARAGON_DIR "etc\mysql\my.ini"
        & sc.exe query $svcName > $null 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "        Service already exists ... skipped"
        } else {
            $nssmExe = $null
            if (Test-Path (Join-Path $INSTALL_DIR 'nssm.exe')) { $nssmExe = Join-Path $INSTALL_DIR 'nssm.exe' }
            elseif (Test-Path (Join-Path $LARAGON_DIR 'bin\nssm\nssm.exe')) { $nssmExe = Join-Path $LARAGON_DIR 'bin\nssm\nssm.exe' }
            elseif (Test-CommandExists 'nssm') { $nssmExe = 'nssm' }

            if ($nssmExe) {
                & $nssmExe install $svcName $MYSQLD_EXE "--defaults-file=$mysqlIni" > $null 2>&1
                if ($LASTEXITCODE -eq 0) {
                    & $nssmExe set $svcName DisplayName "DentalClinic MySQL" > $null 2>&1
                    & $nssmExe set $svcName Start SERVICE_AUTO_START > $null 2>&1
                    Write-Host "        Service registration .... OK (NSSM)"
                } else {
                    Write-Host "        Service registration .... warning"
                }
            } else {
                if (Test-Path $mysqlIni) {
                    & sc.exe create $svcName ('binPath= "' + $MYSQLD_EXE + '" --defaults-file="' + $mysqlIni + '"') 'DisplayName= DentalClinic MySQL' 'start= auto' > $null 2>&1
                } else {
                    & sc.exe create $svcName ('binPath= "' + $MYSQLD_EXE + '"') 'DisplayName= DentalClinic MySQL' 'start= auto' > $null 2>&1
                }
                if ($LASTEXITCODE -eq 0) {
                    & sc.exe description $svcName "DentalClinic MySQL database service" > $null 2>&1
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
        $schedulerCommand = '"' + $PHP_EXE + '" "' + (Join-Path $PROJECT_DIR 'artisan') + '" schedule:run >> "' + (Join-Path $PROJECT_DIR 'storage\logs\scheduler.log') + '" 2>&1'
        & schtasks /create /tn "DentalClinic-Scheduler" /tr $schedulerCommand /sc minute /mo 1 /ru SYSTEM /f > $null 2>&1
        if ($LASTEXITCODE -eq 0) { Write-Host "        Scheduler task .......... OK" } else { Write-Host "        Scheduler task .......... warning" }

        $queueCommand = '"' + $PHP_EXE + '" "' + (Join-Path $PROJECT_DIR 'artisan') + '" queue:work --sleep=3 --tries=3 --max-time=3600'
        & schtasks /create /tn "DentalClinic-QueueWorker" /tr $queueCommand /sc onstart /ru SYSTEM /f > $null 2>&1
        if ($LASTEXITCODE -eq 0) { Write-Host "        Queue worker task ....... OK" } else { Write-Host "        Queue worker task ....... warning" }
    }

    $script:Step++
    Write-Section "Final validation"
    # 必须重跑：OCR 步骤若把 OCR_ENABLED 改为 false，只有这次 config:cache
    # 能让它进入配置缓存。请勿删除（详见「Optimize caches」步骤的说明）。
    & $PHP_EXE artisan config:cache --no-interaction > $null 2>&1
    Invoke-External -FilePath $PHP_EXE -Arguments @('artisan', '--version')

    $validationArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER, '-D', $DB_NAME, '-e', 'SELECT 1 FROM users LIMIT 1')
    if (-not [string]::IsNullOrEmpty($DB_PASS)) {
        $validationArgs = @('-h', $DB_HOST, '-P', $DB_PORT, '-u', $DB_USER, ('-p' + $DB_PASS), '-D', $DB_NAME, '-e', 'SELECT 1 FROM users LIMIT 1')
    }
    & $MYSQL_EXE @validationArgs > $null 2>&1
    if ($LASTEXITCODE -eq 0) { Write-Host "        Database check .......... OK" } else { Write-Host "        Database check .......... warning" }

    & $PHP_EXE artisan route:list --compact --no-interaction > $null 2>&1
    if ($LASTEXITCODE -eq 0) { Write-Host "        Route check ............. OK" } else { Write-Host "        Route check ............. warning" }

    Write-Host ""
    Write-Host "+=========================================================+"
    Write-Host "| Installation completed                                  |"
    Write-Host "+=========================================================+"
    Write-Host ("| Version:      {0}" -f ((Get-Content (Join-Path $PROJECT_DIR 'VERSION') -ErrorAction SilentlyContinue | Select-Object -First 1)))
    Write-Host ("| Install Dir:  {0}" -f $INSTALL_DIR)
    Write-Host ("| App URL:      {0}" -f $APP_URL)
    Write-Host "| Admin User:   admin@example.com"
    Write-Host "| Admin Pass:   password"
    Write-Host "+=========================================================+"
    exit 0
}
catch {
    Write-Host ""
    Write-Host "+=========================================================+"
    Write-Host "| Installation failed                                      |"
    Write-Host "+=========================================================+"
    Write-Host ("| Error: {0}" -f $_.Exception.Message)
    Write-Host "+=========================================================+"
    exit 1
}
