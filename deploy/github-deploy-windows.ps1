#requires -version 2
param(
    [ValidateSet("auto", "install", "update")]
    [string]$Mode = "auto",
    [string]$Repo = "Portg/Dental-Medical-Managment-System",
    [string]$Version = "latest",
    [string]$InstallDir = "C:\DentalClinic",
    [switch]$NoVerify,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

function Test-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Quote-Argument([string]$Value) {
    return '"' + ($Value -replace '"', '\"') + '"'
}

function Get-Sha256([string]$Path) {
    $stream = [System.IO.File]::OpenRead($Path)
    try {
        $sha = New-Object System.Security.Cryptography.SHA256Managed
        try {
            $bytes = $sha.ComputeHash($stream)
            return ([System.BitConverter]::ToString($bytes)).Replace("-", "").ToLowerInvariant()
        } finally {
            $sha.Dispose()
        }
    } finally {
        $stream.Dispose()
    }
}

function New-Downloader {
    try {
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]3072
    } catch {}
    $client = New-Object System.Net.WebClient
    $client.Headers.Add("User-Agent", "dental-clinic-deployer")
    $token = $env:GH_TOKEN
    if (-not $token) { $token = $env:GITHUB_TOKEN }
    if ($token) { $client.Headers.Add("Authorization", "Bearer " + $token) }
    return $client
}

function Expand-Zip([string]$ZipPath, [string]$Destination) {
    New-Item -ItemType Directory -Force -Path $Destination | Out-Null
    $shell = New-Object -ComObject Shell.Application
    $zip = $shell.NameSpace($ZipPath)
    $target = $shell.NameSpace($Destination)
    if (($zip -eq $null) -or ($target -eq $null)) {
        throw "Windows cannot open the ZIP deployment package"
    }
    $target.CopyHere($zip.Items(), 20)

    # Shell.Application extracts asynchronously. Wait until file count and size stabilize.
    #
    # Allow up to 20 minutes (2400 x 500ms). The full Windows package contains
    # Laragon, the Python installer, and OCR wheels. Extracting thousands of files
    # with Shell.CopyHere can legitimately take more than five minutes on Win7 HDDs.
    $lastCount = -1
    $lastBytes = -1
    $stableCount = 0
    for ($i = 0; $i -lt 2400; $i++) {
        Start-Sleep -Milliseconds 500
        $files = @(Get-ChildItem -Path $Destination -Recurse -Force -ErrorAction SilentlyContinue |
            Where-Object { -not $_.PSIsContainer })
        $count = $files.Count
        $bytes = 0
        foreach ($file in $files) { $bytes += $file.Length }
        if (($count -gt 0) -and ($count -eq $lastCount) -and ($bytes -eq $lastBytes)) {
            $stableCount++
            if ($stableCount -ge 20) { return }
        } else {
            $stableCount = 0
            $lastCount = $count
            $lastBytes = $bytes
        }
    }
    throw "Timed out while extracting the ZIP deployment package"
}

function Find-PackageFile([string]$Root, [string]$Name) {
    $match = Get-ChildItem -Path $Root -Recurse -Force -ErrorAction SilentlyContinue |
        Where-Object { (-not $_.PSIsContainer) -and ($_.Name -eq $Name) } |
        Select-Object -First 1
    if ($match) { return $match.FullName }
    return $null
}

if ($Repo -notmatch '^[^/]+/[^/]+$') {
    throw "Invalid GitHub repository: $Repo (expected OWNER/REPO)"
}

$projectExists = (Test-Path (Join-Path $InstallDir "laragon\www\dental\artisan")) -or
                 (Test-Path (Join-Path $InstallDir "xampp\htdocs\dental\artisan")) -or
                 (Test-Path (Join-Path $InstallDir "dental\artisan"))
if ($Mode -eq "auto") {
    if ($projectExists) { $Mode = "update" } else { $Mode = "install" }
}
if (($Mode -eq "install") -and ($InstallDir -ne "C:\DentalClinic")) {
    throw "The full Windows package installs to C:\DentalClinic; use the installer wizard for a custom path"
}

if ($Mode -eq "update") {
    $asset = "dental-clinic-windows-upgrade.zip"
} else {
    $asset = "dental-clinic-windows.zip"
}

if ($env:DENTAL_RELEASE_BASE_URL) {
    $baseUrl = $env:DENTAL_RELEASE_BASE_URL.TrimEnd('/')
} elseif ($Version -eq "latest") {
    $baseUrl = "https://github.com/$Repo/releases/latest/download"
} else {
    $tag = $Version
    if (-not $tag.StartsWith("v")) { $tag = "v" + $tag }
    $baseUrl = "https://github.com/$Repo/releases/download/$tag"
}
$assetUrl = "$baseUrl/$asset"
$checksumUrl = "$baseUrl/SHA256SUMS"

Write-Host "Platform:      windows"
Write-Host "Action:        $Mode"
Write-Host "Install dir:   $InstallDir"
Write-Host "Package:       $assetUrl"
if ($DryRun) { exit 0 }

$scriptPath = $MyInvocation.MyCommand.Path
if (-not (Test-Administrator)) {
    if (-not $scriptPath) { throw "Save the script locally and run it as Administrator" }
    $arguments = @("-NoProfile", "-ExecutionPolicy", "Bypass", "-File", (Quote-Argument $scriptPath),
                   "-Mode", $Mode, "-Repo", (Quote-Argument $Repo), "-Version", (Quote-Argument $Version),
                   "-InstallDir", (Quote-Argument $InstallDir))
    if ($NoVerify) { $arguments += "-NoVerify" }
    $process = Start-Process -FilePath "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe" `
        -Verb RunAs -ArgumentList ($arguments -join " ") -Wait -PassThru
    exit $process.ExitCode
}

$tempDir = Join-Path ([System.IO.Path]::GetTempPath()) ("dental-github-deploy-" + [Guid]::NewGuid().ToString("N"))
New-Item -ItemType Directory -Path $tempDir | Out-Null
try {
    $zipPath = Join-Path $tempDir $asset
    $checksumPath = Join-Path $tempDir "SHA256SUMS"
    $client = New-Downloader
    try {
        Write-Host "Downloading package..."
        $client.DownloadFile($assetUrl, $zipPath)
        if (-not $NoVerify) {
            Write-Host "Verifying SHA256..."
            $client.DownloadFile($checksumUrl, $checksumPath)
        }
    } finally {
        $client.Dispose()
    }

    if (-not $NoVerify) {
        $expected = $null
        foreach ($line in [System.IO.File]::ReadAllLines($checksumPath)) {
            if ($line -match ('^([0-9a-fA-F]{64})\s+\*?' + [Regex]::Escape($asset) + '$')) {
                $expected = $Matches[1].ToLowerInvariant()
                break
            }
        }
        if (-not $expected) { throw "SHA256SUMS does not contain a valid checksum for $asset" }
        $actual = Get-Sha256 $zipPath
        if ($actual -ne $expected) { throw "Package SHA256 verification failed; deployment stopped" }
        Write-Host "SHA256 verified."
    }

    $packageDir = Join-Path $tempDir "package"
    Expand-Zip $zipPath $packageDir
    if ($Mode -eq "update") {
        $entrypoint = Find-PackageFile $packageDir "upgrade-win.bat"
    } else {
        $entrypoint = Find-PackageFile $packageDir "setup.bat"
    }
    if (-not $entrypoint) { throw "The deployment package does not contain an entrypoint" }

    $workingDir = Split-Path -Parent $entrypoint
    $startInfo = New-Object System.Diagnostics.ProcessStartInfo
    $startInfo.FileName = "$env:SystemRoot\System32\cmd.exe"
    if ($Mode -eq "update") {
        $startInfo.Arguments = '/d /c ""' + $entrypoint + '" "' + $InstallDir + '" --unattended"'
    } else {
        $startInfo.Arguments = '/d /c ""' + $entrypoint + '""'
    }
    $startInfo.WorkingDirectory = $workingDir
    $startInfo.UseShellExecute = $false
    $process = [System.Diagnostics.Process]::Start($startInfo)
    $process.WaitForExit()
    if ($process.ExitCode -ne 0) { throw "Deployment script failed with exit code $($process.ExitCode)" }
    Write-Host "GitHub deployment completed."
} finally {
    if (Test-Path $tempDir) { Remove-Item -Recurse -Force $tempDir -ErrorAction SilentlyContinue }
}
