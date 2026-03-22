@echo off
chcp 65001 >nul 2>&1
setlocal

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "PS_EXE=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
set "PS_SCRIPT=%SCRIPT_DIR%\install-win.ps1"

if not exist "%PS_EXE%" (
    echo [ERROR] PowerShell not found: %PS_EXE%
    exit /b 1
)

if not exist "%PS_SCRIPT%" (
    echo [ERROR] PowerShell installer script not found: %PS_SCRIPT%
    exit /b 1
)

"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -File "%PS_SCRIPT%" %*
set "EXIT_CODE=%ERRORLEVEL%"

endlocal & exit /b %EXIT_CODE%
