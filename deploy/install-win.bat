@echo off
chcp 65001 >nul 2>&1
setlocal

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "PS_EXE=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
set "PS_SCRIPT=%SCRIPT_DIR%\install-win.ps1"
set "PS_TEMP=%TEMP%\dental-install-%RANDOM%%RANDOM%.ps1"

if not exist "%PS_EXE%" (
    echo [ERROR] PowerShell not found: %PS_EXE%
    exit /b 1
)

if not exist "%PS_SCRIPT%" (
    echo [ERROR] PowerShell installer script not found: %PS_SCRIPT%
    exit /b 1
)

REM Windows PowerShell 5.1 reads UTF-8 scripts reliably only with BOM.
"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -Command ^
  "$src = $args[0]; $dst = $args[1]; $bytes = [System.IO.File]::ReadAllBytes($src); if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) { [System.IO.File]::WriteAllBytes($dst, $bytes) } else { $text = [System.Text.Encoding]::UTF8.GetString($bytes); $text = $text -replace \"`r?`n\", \"`r`n\"; $enc = New-Object System.Text.UTF8Encoding($true); [System.IO.File]::WriteAllText($dst, $text, $enc) }" ^
  "%PS_SCRIPT%" "%PS_TEMP%"
if errorlevel 1 (
    echo [ERROR] Failed to prepare PowerShell installer script.
    endlocal & exit /b 1
)

"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -File "%PS_TEMP%" %*
set "EXIT_CODE=%ERRORLEVEL%"
if exist "%PS_TEMP%" del /f /q "%PS_TEMP%" >nul 2>&1

endlocal & exit /b %EXIT_CODE%
