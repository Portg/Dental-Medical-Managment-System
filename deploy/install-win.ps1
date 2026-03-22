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

$script:TotalSteps = 18
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
    Write-Section "Detect Laragon runtime"
    if (-not (Test-Path (Join-Path $LARAGON_DIR "bin"))) {
        Fail-Step "Laragon directory not found: $LARAGON_DIR\bin"
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
    $mysqlDir = Get-FirstDirectoryMatch -BasePath $mysqlBase -Patterns @('mysql-8*', 'mysql-*', 'mysql*', '*') -CheckRelativePath 'bin\mysql.exe'
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
                if (-not (Install-BundledPython -InstallerPath $bundledPythonInstaller)) {
                    Fail-Step "Bundled Python installation failed. OCR cannot be initialized."
                }
                $pythonRuntime = Find-PythonRuntime
            }
        }

        if ($pythonRuntime.Exe) {
            $PYTHON_EXE = $pythonRuntime.Exe
            $PYTHON_ARGS = $pythonRuntime.Args
            Write-Host ("        Python .................. {0}" -f $pythonRuntime.Display)
        } else {
            Fail-Step "Python 3 runtime is required for OCR, but no Python installation was found and no bundled installer is available. Rebuild the package with OCR dependencies or use --no-ocr."
        }
    }

    $phpVersionInfo = Get-PhpVersionInfo -PhpExe $PHP_EXE
    $script:PhpVer = $phpVersionInfo.Version
    if (-not $script:PhpVer) {
        $osVersion = [Environment]::OSVersion.Version
        $runtimeHint = "Unable to determine PHP version."
        if ($phpVersionInfo.Output) {
            $runtimeHint += " PHP startup output: " + $phpVersionInfo.Output
        }
        if ($osVersion.Major -lt 6 -or ($osVersion.Major -eq 6 -and $osVersion.Minor -le 1)) {
            $runtimeHint += " Windows 7 / Server 2008 R2 is not a supported target for the bundled PHP 8.2 runtime. Use Windows 10+ / Server 2016+, or rebuild the package with an older supported PHP stack."
        } else {
            $runtimeHint += " The bundled PHP runtime may be missing a compatible Visual C++ redistributable."
        }
        Fail-Step $runtimeHint
    }
    $phpVersion = [Version]$script:PhpVer
    if ($phpVersion -lt [Version]"8.2.0") { Fail-Step "PHP 8.2+ is required. Current: $($script:PhpVer)" }
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
                if ($stillInUse) { Fail-Step ("Port {0} is still occupied after stopping mysqld. Please stop the conflicting service manually and retry." -f $DB_PORT) }
            } catch {}
            Write-Host "        Previous mysqld stopped"
        }

        $mysqlIni = Join-Path $LARAGON_DIR "etc\mysql\my.ini"
        if (-not (Test-Path $MYSQL_DATA_DIR)) {
            New-Item -ItemType Directory -Path $MYSQL_DATA_DIR -Force | Out-Null
        }
        if (Test-Path $mysqlIni) {
            $mysqlIniLines = @(Get-Content $mysqlIni -ErrorAction SilentlyContinue)
            if (-not ($mysqlIniLines | Select-String '^\s*basedir\s*=' -Quiet)) {
                Add-Content -Path $mysqlIni -Value ("basedir=" + $mysqlDir.Replace('\', '/'))
            }
            if (-not ($mysqlIniLines | Select-String '^\s*datadir\s*=' -Quiet)) {
                Add-Content -Path $mysqlIni -Value ("datadir=" + $MYSQL_DATA_DIR.Replace('\', '/'))
            }
            if (-not ($mysqlIniLines | Select-String '^\s*log-error\s*=' -Quiet)) {
                Add-Content -Path $mysqlIni -Value ("log-error=" + $MYSQL_ERROR_LOG.Replace('\', '/'))
            }
        }

        $dataFiles = @(Get-ChildItem -Path $MYSQL_DATA_DIR -Force -ErrorAction SilentlyContinue)
        if ($dataFiles.Count -eq 0) {
            Write-Host "        Initializing MySQL data directory..."
            & $MYSQLD_EXE "--defaults-file=$mysqlIni" "--basedir=$mysqlDir" "--datadir=$MYSQL_DATA_DIR" --initialize-insecure > $null 2>&1
            if ($LASTEXITCODE -ne 0) {
                $initLog = $null
                if (Test-Path $MYSQL_ERROR_LOG) {
                    $initLog = (Get-Content $MYSQL_ERROR_LOG | Select-Object -Last 20) -join [Environment]::NewLine
                }
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
        Start-Process -FilePath $MYSQLD_EXE -ArgumentList $mysqlArgs -WindowStyle Hidden | Out-Null
        if (-not (Wait-MySqlReady -MySqlExe $MYSQL_EXE -Args $rootConnArgs -TimeoutSeconds 60)) {
            $startupLog = $null
            if (Test-Path $MYSQL_ERROR_LOG) {
                $startupLog = (Get-Content $MYSQL_ERROR_LOG | Select-Object -Last 20) -join [Environment]::NewLine
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

    $script:Step++
    Write-Section "Create storage link"
    & $PHP_EXE artisan storage:link --force --no-interaction > $null 2>&1
    if ($LASTEXITCODE -ne 0 -and -not (Test-Path (Join-Path $PROJECT_DIR 'public\storage'))) {
        Invoke-CmdLine ('mklink /D "' + (Join-Path $PROJECT_DIR 'public\storage') + '" "' + (Join-Path $PROJECT_DIR 'storage\app\public') + '"') | Out-Null
    }
    Write-Host "        Storage link ............ OK"

    $script:Step++
    Write-Section "Optimize caches"
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
        Write-Host "        OCR ..................... skipped (--no-ocr)"
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

        $ocrReady = $true
        if (-not (Test-Path (Join-Path $OCR_VENV "Scripts\python.exe"))) {
            & $PYTHON_EXE @PYTHON_ARGS -m venv $OCR_VENV
            if ($LASTEXITCODE -ne 0) {
                Fail-Step "OCR virtual environment creation failed."
            }
        }

        if ($ocrReady -and (Test-Path $OCR_REQUIREMENTS)) {
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
                $ocrInstallLog = Get-LastLogLines -Paths @($OCR_INSTALL_LOG)
                if ($ocrInstallLog) {
                    Fail-Step ("OCR dependency installation failed." + [Environment]::NewLine + $ocrInstallLog)
                }
                Fail-Step "OCR dependency installation failed."
            }
        } else {
            Fail-Step "OCR requirements.txt is missing."
        }

        $ocrPythonExe = Join-Path $OCR_VENV "Scripts\python.exe"
        & $ocrPythonExe -c "import paddleocr, flask, PIL; print('OCR_IMPORTS_OK')" *> $OCR_VERIFY_LOG
        if ($LASTEXITCODE -ne 0) {
            $ocrVerifyLog = Get-LastLogLines -Paths @($OCR_VERIFY_LOG)
            if ($ocrVerifyLog) {
                Fail-Step ("OCR dependency verification failed." + [Environment]::NewLine + $ocrVerifyLog)
            }
            Fail-Step "OCR dependency verification failed."
        }

        if (Test-Path $ENV_TARGET) {
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

        if (-not (Test-Path $OCR_SCRIPT)) {
            Fail-Step "OCR server script is missing."
        }

        if (-not (Test-HttpEndpoint -Url $OCR_HEALTH_URL -TimeoutMs 3000)) {
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

                $ocrServerLog = Get-LastLogLines -Paths @($OCR_SERVER_OUT_LOG, $OCR_SERVER_ERR_LOG)
                if ($ocrServerLog) {
                    Fail-Step ("OCR server health check failed." + [Environment]::NewLine + $ocrServerLog)
                }
                Fail-Step "OCR server health check failed."
            }
        }

        Write-Host "        OCR setup ............... OK"
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
