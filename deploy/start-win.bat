@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion
title 牙科诊所管理系统 - 启动服务

REM ═══════════════════════════════════════════════════════════════
REM  牙科诊所管理系统 - Windows 服务启动脚本
REM  用途: 依次启动 MySQL、Web 服务器、OCR 服务、队列工作进程
REM  用法: start-win.bat [安装目录]
REM  默认安装目录: C:\DentalClinic
REM ═══════════════════════════════════════════════════════════════

REM ── 参数处理 ────────────────────────────────────────────────────
set "INSTALL_DIR=%~1"
if "%INSTALL_DIR%"=="" set "INSTALL_DIR=C:\DentalClinic"
if "%INSTALL_DIR:~-1%"=="\" set "INSTALL_DIR=%INSTALL_DIR:~0,-1%"

set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "PROJECT_DIR=%LARAGON_DIR%\www\dental"

REM ── 自动发现路径（版本无关）────────────────────────────────────
set "PHP_DIR="
set "PHP_EXE="
set "MYSQL_DIR="
set "MYSQL_EXE="
set "MYSQLD_EXE="
set "MYSQLADMIN_EXE="
set "MYSQL_INI="
set "NGINX_DIR="
set "NGINX_EXE="
set "LARAGON_EXE=%LARAGON_DIR%\laragon.exe"
set "OCR_VENV=%PROJECT_DIR%\scripts\venv\Scripts\python.exe"
set "OCR_SCRIPT=%PROJECT_DIR%\scripts\ocr_server.py"

REM PHP: php-8* → php8* → php* → 任意子目录
for /d %%D in ("%LARAGON_DIR%\bin\php\php-8*") do set "PHP_DIR=%%D"
if not defined PHP_DIR for /d %%D in ("%LARAGON_DIR%\bin\php\php8*") do set "PHP_DIR=%%D"
if not defined PHP_DIR for /d %%D in ("%LARAGON_DIR%\bin\php\php*") do set "PHP_DIR=%%D"
if not defined PHP_DIR if exist "%LARAGON_DIR%\bin\php\php.exe" set "PHP_DIR=%LARAGON_DIR%\bin\php"
if not defined PHP_DIR for /d %%D in ("%LARAGON_DIR%\bin\php\*") do if exist "%%D\php.exe" set "PHP_DIR=%%D"
REM MySQL: mysql-8* → mysql-* → mysql* → 任意子目录
for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-8*") do set "MYSQL_DIR=%%D"
if not defined MYSQL_DIR for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do set "MYSQL_DIR=%%D"
if not defined MYSQL_DIR for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql*") do set "MYSQL_DIR=%%D"
if not defined MYSQL_DIR for /d %%D in ("%LARAGON_DIR%\bin\mysql\*") do if exist "%%D\bin\mysql.exe" set "MYSQL_DIR=%%D"
REM Nginx: nginx-* → 任意子目录
for /d %%D in ("%LARAGON_DIR%\bin\nginx\nginx-*") do set "NGINX_DIR=%%D"
if not defined NGINX_DIR for /d %%D in ("%LARAGON_DIR%\bin\nginx\*") do if exist "%%D\nginx.exe" set "NGINX_DIR=%%D"

if defined PHP_DIR set "PHP_EXE=%PHP_DIR%\php.exe"
if defined MYSQL_DIR (
    set "MYSQL_EXE=%MYSQL_DIR%\bin\mysql.exe"
    set "MYSQLD_EXE=%MYSQL_DIR%\bin\mysqld.exe"
    set "MYSQLADMIN_EXE=%MYSQL_DIR%\bin\mysqladmin.exe"
    set "MYSQL_INI=%LARAGON_DIR%\etc\mysql\my.ini"
)
if defined NGINX_DIR set "NGINX_EXE=%NGINX_DIR%\nginx.exe"

REM 如果 Laragon 内没有，尝试系统 PATH
if not defined PHP_EXE (
    where php >nul 2>&1 && for /f "tokens=*" %%P in ('where php 2^>nul') do (
        set "PHP_EXE=%%P"
        goto :found_sys_php
    )
    :found_sys_php
)
if not defined MYSQL_EXE (
    where mysql >nul 2>&1 && for /f "tokens=*" %%P in ('where mysql 2^>nul') do (
        set "MYSQL_EXE=%%P"
        goto :found_sys_mysql
    )
    :found_sys_mysql
)
if not defined MYSQLD_EXE (
    where mysqld >nul 2>&1 && for /f "tokens=*" %%P in ('where mysqld 2^>nul') do (
        set "MYSQLD_EXE=%%P"
    )
)
if not defined MYSQLADMIN_EXE (
    where mysqladmin >nul 2>&1 && for /f "tokens=*" %%P in ('where mysqladmin 2^>nul') do (
        set "MYSQLADMIN_EXE=%%P"
    )
)

REM ── 状态变量 ────────────────────────────────────────────────────
set "LARAGON_MODE=0"
set "MYSQL_OK=0"
set "WEB_OK=0"
set "WEB_MODE=none"
set "OCR_OK=0"
set "QUEUE_OK=0"
set "APP_PORT=8000"
set "APP_URL=http://localhost/dental"
set "OCR_PORT=5000"
set "MYSQL_WAIT_MAX=30"

REM ── 横幅 ────────────────────────────────────────────────────────
echo.
echo  +=====================================================+
echo  |       牙科诊所管理系统 - 启动服务                   |
echo  +=====================================================+
echo  |  安装目录: %INSTALL_DIR%
echo  +=====================================================+
echo.

REM ── 环境检测 ────────────────────────────────────────────────────
if not exist "%PROJECT_DIR%\artisan" (
    echo  [错误] 项目目录不存在或不完整: %PROJECT_DIR%
    echo         请检查安装目录参数是否正确
    goto :error
)

if not defined PHP_EXE (
    echo  [错误] 未找到 PHP，请安装 PHP 8.2+ 或 Laragon
    goto :error
)

REM 检测是否有 Laragon
if exist "%LARAGON_EXE%" set "LARAGON_MODE=1"

REM ══════════════════════════════════════════════════════════════
REM  Step 1/6: 启动 MySQL
REM ══════════════════════════════════════════════════════════════
echo  [1/6] 启动 MySQL...

REM 检查 MySQL 是否已在运行
if defined MYSQL_EXE (
    "%MYSQL_EXE%" -u root -e "SELECT 1" >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        echo        MySQL 已在运行                              [跳过]
        set "MYSQL_OK=1"
        goto :mysql_done
    )
)

REM 方式1: 通过 Laragon 启动（它会管理 MySQL + Nginx）
if "%LARAGON_MODE%"=="1" (
    tasklist /FI "IMAGENAME eq laragon.exe" 2>nul | findstr /I "laragon.exe" >nul
    if !ERRORLEVEL! neq 0 (
        echo        通过 Laragon 启动...
        start "" "%LARAGON_EXE%"
    ) else (
        echo        Laragon 已在运行
    )

    REM 等待 MySQL 就绪
    if defined MYSQL_EXE (
        set /a "WAIT=0"
        :wait_mysql_laragon
        timeout /t 2 /nobreak >nul
        "%MYSQL_EXE%" -u root -e "SELECT 1" >nul 2>&1
        if !ERRORLEVEL! equ 0 (
            echo        MySQL 启动成功 (Laragon^)                   [OK]
            set "MYSQL_OK=1"
            goto :mysql_done
        )
        set /a "WAIT+=2"
        if !WAIT! geq %MYSQL_WAIT_MAX% (
            echo        [警告] MySQL 启动超时 (%MYSQL_WAIT_MAX% 秒^)
            goto :mysql_done
        )
        goto :wait_mysql_laragon
    )
    goto :mysql_done
)

REM 方式2: 直接启动 mysqld
if defined MYSQLD_EXE (
    echo        直接启动 mysqld...
    if exist "%MYSQL_INI%" (
        start "" /b "%MYSQLD_EXE%" --defaults-file="%MYSQL_INI%" --console >nul 2>&1
    ) else (
        start "" /b "%MYSQLD_EXE%" --console >nul 2>&1
    )
) else (
    REM 方式3: Windows 服务
    net start mysql >nul 2>&1
    if !ERRORLEVEL! neq 0 (
        echo  [错误] 无法启动 MySQL，请手动启动数据库
        goto :error
    )
)

REM 等待 MySQL 就绪
if defined MYSQL_EXE (
    set /a "WAIT=0"
    :wait_mysql_direct
    timeout /t 2 /nobreak >nul
    "%MYSQL_EXE%" -u root -e "SELECT 1" >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        echo        MySQL 启动成功                              [OK]
        set "MYSQL_OK=1"
        goto :mysql_done
    )
    set /a "WAIT+=2"
    if !WAIT! geq %MYSQL_WAIT_MAX% (
        echo  [错误] MySQL 启动超时 (%MYSQL_WAIT_MAX% 秒^)
        goto :error
    )
    echo        等待中... (!WAIT!/%MYSQL_WAIT_MAX% 秒^)
    goto :wait_mysql_direct
)

:mysql_done
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 2/6: 启动 Nginx（或 PHP 内置服务器作为备选）
REM ══════════════════════════════════════════════════════════════
echo  [2/6] 启动 Web 服务器...

REM Laragon 模式: Nginx 由 Laragon 管理
if "%LARAGON_MODE%"=="1" (
    REM 等 Laragon 拉起 Nginx
    timeout /t 3 /nobreak >nul
    tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | findstr /I "nginx.exe" >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        echo        Nginx 由 Laragon 管理                       [OK]
        set "WEB_OK=1"
        set "WEB_MODE=laragon"
        set "APP_URL=http://localhost/dental"
        goto :web_done
    )
    echo        [警告] Laragon 的 Nginx 未就绪，尝试其他方式
)

REM 检查 Nginx 是否已在运行
tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | findstr /I "nginx.exe" >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        Nginx 已在运行                                [跳过]
    set "WEB_OK=1"
    set "WEB_MODE=nginx"
    set "APP_URL=http://localhost/dental"
    goto :web_done
)

REM 检查端口 80 是否已被占用
netstat -an 2>nul | findstr ":80 " | findstr "LISTENING" >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        端口 80 已被占用，使用 PHP 内置服务器          [INFO]
    goto :try_php_builtin
)

REM 方式1: 直接启动 Nginx
if defined NGINX_EXE (
    if exist "%NGINX_EXE%" (
        echo        启动 Nginx...
        pushd "%NGINX_DIR%"
        if exist "%LARAGON_DIR%\etc\nginx" (
            start "" /b "%NGINX_EXE%" -p "%LARAGON_DIR%\etc\nginx" 2>nul
        ) else (
            start "" /b "%NGINX_EXE%" 2>nul
        )
        popd
        timeout /t 2 /nobreak >nul
        tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | findstr /I "nginx.exe" >nul 2>&1
        if !ERRORLEVEL! equ 0 (
            echo        Nginx 启动成功                              [OK]
            set "WEB_OK=1"
            set "WEB_MODE=nginx"
            set "APP_URL=http://localhost/dental"
            goto :web_done
        )
        echo        [警告] Nginx 启动失败，使用 PHP 内置服务器
    )
)

:try_php_builtin
REM 方式2: PHP 内置服务器（备选）
if defined PHP_EXE (
    echo        启动 PHP 内置服务器 (localhost:%APP_PORT%)...
    pushd "%PROJECT_DIR%"
    start "" /b "%PHP_EXE%" -S localhost:%APP_PORT% -t public >nul 2>&1
    popd
    timeout /t 2 /nobreak >nul
    set "WEB_OK=1"
    set "WEB_MODE=php-builtin"
    set "APP_URL=http://localhost:%APP_PORT%"
    echo        PHP 内置服务器启动成功                        [OK]
    goto :web_done
)

echo        [错误] 无可用的 Web 服务器
goto :error

:web_done
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 3/6: 启动 OCR 服务（可选，需要 Python venv）
REM ══════════════════════════════════════════════════════════════
echo  [3/6] 启动 OCR 识别服务（可选）...

REM 检查 OCR 是否已在运行
netstat -an 2>nul | findstr ":%OCR_PORT% " | findstr "LISTENING" >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        OCR 服务已在运行 (端口 %OCR_PORT%^)             [跳过]
    set "OCR_OK=1"
    goto :ocr_done
)

if not exist "%OCR_VENV%" (
    echo        Python venv 不存在，跳过 OCR                  [跳过]
    goto :ocr_done
)
if not exist "%OCR_SCRIPT%" (
    echo        OCR 脚本不存在，跳过                          [跳过]
    goto :ocr_done
)

echo        启动 OCR 服务 (PaddleOCR, 端口 %OCR_PORT%)...
pushd "%PROJECT_DIR%"
start "" /b "%OCR_VENV%" "%OCR_SCRIPT%" --port %OCR_PORT%
popd

REM 等待 OCR 服务就绪（模型加载约 4 秒）
set /a "WAIT=0"
:wait_ocr
timeout /t 3 /nobreak >nul
netstat -an 2>nul | findstr ":%OCR_PORT% " | findstr "LISTENING" >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        OCR 服务启动成功                              [OK]
    set "OCR_OK=1"
    goto :ocr_done
)
set /a "WAIT+=3"
if !WAIT! geq 30 (
    echo        [警告] OCR 服务启动超时，系统仍可正常运行     [WARN]
    goto :ocr_done
)
goto :wait_ocr

:ocr_done
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 4/6: 启动 Laravel 队列工作进程
REM ══════════════════════════════════════════════════════════════
echo  [4/6] 启动 Laravel 队列工作进程...

REM 检查是否已有 queue:work 进程运行
wmic process where "commandline like '%%queue:work%%'" get processid 2>nul | findstr /R "[0-9]" >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        队列工作进程已在运行                          [跳过]
    set "QUEUE_OK=1"
    goto :queue_done
)

if not defined PHP_EXE (
    echo        [警告] 未找到 PHP，无法启动队列               [WARN]
    goto :queue_done
)

pushd "%PROJECT_DIR%"
start "dental-queue-worker" /min "%PHP_EXE%" artisan queue:work --sleep=3 --tries=3 --max-time=3600
popd
set "QUEUE_OK=1"
echo        队列工作进程启动成功                            [OK]

:queue_done
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 5/6: 打开浏览器
REM ══════════════════════════════════════════════════════════════
echo  [5/6] 打开浏览器...
timeout /t 2 /nobreak >nul
start "" "%APP_URL%"
echo        已打开 %APP_URL%                               [OK]
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 6/6: 状态汇总
REM ══════════════════════════════════════════════════════════════
echo  [6/6] 服务状态汇总
echo.
echo  +=====================================================+
echo  |              服务状态汇总                           |
echo  +=====================================================+
echo  |                                                     |

if "%MYSQL_OK%"=="1" (
    echo  |  MySQL .................. 运行中                   |
) else (
    echo  |  MySQL .................. 未启动                   |
)

if "%WEB_MODE%"=="laragon" (
    echo  |  Web 服务器 ............. Laragon Nginx            |
) else if "%WEB_MODE%"=="nginx" (
    echo  |  Web 服务器 ............. Nginx                    |
) else if "%WEB_MODE%"=="php-builtin" (
    echo  |  Web 服务器 ............. PHP 内置 (:%APP_PORT%^)       |
) else (
    echo  |  Web 服务器 ............. 未启动                   |
)

if "%OCR_OK%"=="1" (
    echo  |  OCR 服务 ............... 运行中 (:%OCR_PORT%^)        |
) else (
    echo  |  OCR 服务 ............... 未启动                   |
)

if "%QUEUE_OK%"=="1" (
    echo  |  队列工作进程 ........... 运行中                   |
) else (
    echo  |  队列工作进程 ........... 未启动                   |
)

echo  |                                                     |
echo  |  访问地址: %APP_URL%
echo  |  停止服务: 运行 stop-win.bat                        |
echo  +=====================================================+
echo.
goto :done

:error
echo.
echo  +=====================================================+
echo  |  启动失败！请检查以上错误信息                       |
echo  |  修复问题后可重新运行此脚本                         |
echo  +=====================================================+
echo.

:done
endlocal
pause
