@echo off
REM build.sh 会把本文件转成 GBK(CP936) + CRLF；必须与 chcp 936 一致。
chcp 936 >nul 2>&1
setlocal EnableExtensions

REM ═══════════════════════════════════════════════════════════════
REM  已弃用：旧版「安装后配置」脚本会无条件 key:generate --force，
REM  并写死 laragon 路径，误跑会毁掉已有 APP_KEY / 数据。
REM  现行安装入口是 setup.bat → install-win.bat → install-win.ps1。
REM ═══════════════════════════════════════════════════════════════

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

echo.
echo  =======================================================
echo    post-install.bat 已弃用
echo  =======================================================
echo.
echo  请改用:
echo    首次安装:  setup.bat  或  install-win.bat
echo    日常启动:  start-win.bat
echo    升级:      upgrade-win.bat
echo.
echo  访问地址: http://localhost
echo.

if exist "%SCRIPT_DIR%\install-win.bat" (
    echo  按任意键打开安装说明提示后退出...
    pause >nul
)

exit /b 1
