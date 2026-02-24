@echo off
chcp 65001 >nul 2>&1
title 牙科诊所管理系统

REM ═══════════════════════════════════════════════════════════════
REM  牙科诊所管理系统 - 启动脚本
REM  桌面快捷方式指向此文件，双击即可启动系统
REM ═══════════════════════════════════════════════════════════════

set "INSTALL_DIR=%~dp0"
if "%INSTALL_DIR:~-1%"=="\" set "INSTALL_DIR=%INSTALL_DIR:~0,-1%"
set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "LARAGON_EXE=%LARAGON_DIR%\laragon.exe"

echo.
echo  启动牙科诊所管理系统...
echo.

REM ── 方式1: 使用 Laragon 主程序（推荐）─────────────────────────
if exist "%LARAGON_EXE%" (
    start "" "%LARAGON_EXE%"
    echo  Laragon 正在启动，请稍候...
    echo.

    REM 等待 MySQL 就绪
    set "MYSQL_DIR="
    for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do set "MYSQL_DIR=%%D"

    if defined MYSQL_DIR (
        set /a "WAIT=0"
        :wait_loop
        timeout /t 2 /nobreak >nul
        "%MYSQL_DIR%\bin\mysql.exe" -u root -e "SELECT 1" >nul 2>&1
        if %ERRORLEVEL% equ 0 goto :ready
        set /a "WAIT+=2"
        if %WAIT% geq 30 (
            echo  [提示] MySQL 启动较慢，请等 Laragon 面板显示绿色后手动打开浏览器
            echo         访问地址: http://localhost/dental
            pause
            exit /b
        )
        goto :wait_loop
    )

    :ready
    REM 等待 Nginx 也就绪
    timeout /t 3 /nobreak >nul
    goto :open_browser
)

REM ── 方式2: 无 Laragon 主程序时手动启动服务 ────────────────────
echo  [提示] 未找到 laragon.exe，尝试手动启动服务...

set "PHP_DIR="
set "MYSQL_DIR="
set "NGINX_DIR="
for /d %%D in ("%LARAGON_DIR%\bin\php\php-8*") do set "PHP_DIR=%%D"
for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-8*") do set "MYSQL_DIR=%%D"
for /d %%D in ("%LARAGON_DIR%\bin\nginx\nginx-*") do set "NGINX_DIR=%%D"

if not defined MYSQL_DIR (
    echo  [错误] 未找到 MySQL
    pause
    exit /b 1
)

REM 启动 MySQL
"%MYSQL_DIR%\bin\mysql.exe" -u root -e "SELECT 1" >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo  启动 MySQL...
    start "" /b "%MYSQL_DIR%\bin\mysqld.exe" --defaults-file="%LARAGON_DIR%\etc\mysql\my.ini" --console
    timeout /t 5 /nobreak >nul
)

REM 启动 Nginx（如果有配置的话用 PHP 内置服务器作为备选）
if defined NGINX_DIR (
    echo  启动 Nginx...
    cd /d "%NGINX_DIR%"
    start "" /b nginx.exe -p "%LARAGON_DIR%\etc\nginx" 2>nul
) else if defined PHP_DIR (
    echo  启动 PHP 内置服务器...
    cd /d "%LARAGON_DIR%\www\dental"
    start "" /b "%PHP_DIR%\php.exe" -S localhost:80 -t public
)

timeout /t 3 /nobreak >nul

:open_browser
echo.
echo  正在打开浏览器...
start "" "http://localhost/dental"
echo.
echo  ╔══════════════════════════════════════════════╗
echo  ║  系统已启动！                                ║
echo  ║  浏览器地址: http://localhost/dental          ║
echo  ║                                              ║
echo  ║  关闭此窗口不会停止服务                      ║
echo  ║  如需停止，请在 Laragon 面板中点击 Stop      ║
echo  ╚══════════════════════════════════════════════╝
echo.
timeout /t 5 >nul
