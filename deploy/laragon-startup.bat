@echo off
REM build.sh 会把本文件转成 GBK(CP936) + CRLF；必须与 chcp 936 一致。
chcp 936 >nul 2>&1
setlocal EnableExtensions

REM ═══════════════════════════════════════════════════════════════
REM  兼容入口：旧桌面快捷方式曾指向本文件。
REM  现行托管模型走 start-win.bat（DentalClinicMySQL + Nginx/php-cgi），
REM  不再启动 laragon.exe，也不再打开 /dental（站点 root 已是 public）。
REM ═══════════════════════════════════════════════════════════════

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "START_BAT=%SCRIPT_DIR%\start-win.bat"

if not exist "%START_BAT%" (
    echo  [错误] 未找到 start-win.bat: %START_BAT%
    pause
    exit /b 1
)

call "%START_BAT%" "%SCRIPT_DIR%"
exit /b %ERRORLEVEL%
