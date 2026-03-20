@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion
title 牙科诊所管理系统 - 停止服务

REM ═══════════════════════════════════════════════════════════════
REM  牙科诊所管理系统 - Windows 服务停止脚本
REM  用途: 按反向顺序停止 队列→OCR→Nginx→MySQL
REM  策略: 先优雅关闭 (SIGTERM)，超时后强制终止 (SIGKILL)
REM  安全: 仅停止本系统相关进程，不影响其他服务
REM ═══════════════════════════════════════════════════════════════

set "QUEUE_STOPPED=0"
set "OCR_STOPPED=0"
set "NGINX_STOPPED=0"
set "PHPCGI_STOPPED=0"
set "MYSQL_STOPPED=0"
set "GRACEFUL_TIMEOUT=10"

echo.
echo  +=====================================================+
echo  |       牙科诊所管理系统 - 停止服务                   |
echo  +=====================================================+
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 1/5: 停止队列工作进程 (php artisan queue:work)
REM ══════════════════════════════════════════════════════════════
echo  [1/5] 停止队列工作进程...

set "QUEUE_FOUND=0"
for /f "tokens=2" %%P in ('wmic process where "commandline like '%%queue:work%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
    set "QUEUE_FOUND=1"
    echo        发现队列进程 PID=%%P，尝试优雅关闭...
    taskkill /PID %%P >nul 2>&1
)

if "!QUEUE_FOUND!"=="1" (
    REM 等待优雅关闭
    set /a "WAIT=0"
    :wait_queue_stop
    timeout /t 2 /nobreak >nul
    wmic process where "commandline like '%%queue:work%%'" get processid 2>nul | findstr /R "[0-9]" >nul 2>&1
    if !ERRORLEVEL! neq 0 (
        echo        队列工作进程已停止                            [OK]
        set "QUEUE_STOPPED=1"
        goto :queue_done
    )
    set /a "WAIT+=2"
    if !WAIT! geq %GRACEFUL_TIMEOUT% (
        echo        优雅关闭超时，强制终止...
        for /f "tokens=2" %%P in ('wmic process where "commandline like '%%queue:work%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
            taskkill /PID %%P /F >nul 2>&1
        )
        echo        队列工作进程已强制停止                        [OK]
        set "QUEUE_STOPPED=1"
        goto :queue_done
    )
    goto :wait_queue_stop
) else (
    echo        队列工作进程未运行                              [跳过]
)

:queue_done
REM 也终止通过 start /min 标题创建的窗口
taskkill /FI "WINDOWTITLE eq dental-queue-worker" /F >nul 2>&1
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 2/5: 停止 OCR 服务 (Python on port 5000)
REM ══════════════════════════════════════════════════════════════
echo  [2/5] 停止 OCR 服务...

set "OCR_FOUND=0"
for /f "tokens=2" %%P in ('wmic process where "commandline like '%%ocr_server%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
    set "OCR_FOUND=1"
    echo        发现 OCR 进程 PID=%%P，尝试优雅关闭...
    taskkill /PID %%P >nul 2>&1
)

if "!OCR_FOUND!"=="1" (
    REM 等待优雅关闭
    set /a "WAIT=0"
    :wait_ocr_stop
    timeout /t 2 /nobreak >nul
    wmic process where "commandline like '%%ocr_server%%'" get processid 2>nul | findstr /R "[0-9]" >nul 2>&1
    if !ERRORLEVEL! neq 0 (
        echo        OCR 服务已停止                                [OK]
        set "OCR_STOPPED=1"
        goto :ocr_done
    )
    set /a "WAIT+=2"
    if !WAIT! geq %GRACEFUL_TIMEOUT% (
        echo        优雅关闭超时，强制终止...
        for /f "tokens=2" %%P in ('wmic process where "commandline like '%%ocr_server%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
            taskkill /PID %%P /F >nul 2>&1
        )
        echo        OCR 服务已强制停止                            [OK]
        set "OCR_STOPPED=1"
        goto :ocr_done
    )
    goto :wait_ocr_stop
) else (
    echo        OCR 服务未运行                                  [跳过]
)

:ocr_done
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 3/5: 停止 Nginx / PHP 内置服务器
REM ══════════════════════════════════════════════════════════════
echo  [3/5] 停止 Web 服务器...

REM ─ 停止 Nginx ─
tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | findstr /I "nginx.exe" >nul
if !ERRORLEVEL! equ 0 (
    echo        发现 Nginx，发送 quit 信号...
    REM Nginx 优雅停止: nginx -s quit
    set "NGINX_QUIT=0"
    REM 搜索 Laragon 常见路径（含安装目录和系统路径）
    for %%L in (
        "%~dp0laragon"
        "%~dp0..\laragon"
        "C:\DentalClinic\laragon"
        "C:\laragon"
    ) do (
        for /d %%D in ("%%~L\bin\nginx\nginx-*") do (
            if exist "%%D\nginx.exe" (
                "%%D\nginx.exe" -s quit >nul 2>&1
                set "NGINX_QUIT=1"
            )
        )
        if "!NGINX_QUIT!"=="0" for /d %%D in ("%%~L\bin\nginx\*") do (
            if exist "%%D\nginx.exe" (
                "%%D\nginx.exe" -s quit >nul 2>&1
                set "NGINX_QUIT=1"
            )
        )
    )
    if "!NGINX_QUIT!"=="0" (
        where nginx >nul 2>&1 && nginx -s quit >nul 2>&1
    )

    REM 等待 Nginx 停止
    set /a "WAIT=0"
    :wait_nginx_stop
    timeout /t 2 /nobreak >nul
    tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | findstr /I "nginx.exe" >nul
    if !ERRORLEVEL! neq 0 (
        echo        Nginx 已停止                                  [OK]
        set "NGINX_STOPPED=1"
        goto :nginx_done
    )
    set /a "WAIT+=2"
    if !WAIT! geq %GRACEFUL_TIMEOUT% (
        echo        优雅关闭超时，强制终止 Nginx...
        taskkill /IM nginx.exe /F >nul 2>&1
        echo        Nginx 已强制停止                              [OK]
        set "NGINX_STOPPED=1"
        goto :nginx_done
    )
    goto :wait_nginx_stop
) else (
    echo        Nginx 未运行                                    [跳过]
)

:nginx_done

REM ─ 停止 PHP-CGI（FastCGI 模式）─
tasklist /FI "IMAGENAME eq php-cgi.exe" 2>nul | findstr /I "php-cgi.exe" >nul
if !ERRORLEVEL! equ 0 (
    echo        终止 PHP-CGI 进程...
    taskkill /IM php-cgi.exe /F >nul 2>&1
    set "PHPCGI_STOPPED=1"
    echo        PHP-CGI 已停止                                [OK]
)

REM ─ 停止 PHP 内置服务器 ─
set "PHPSVR_FOUND=0"
for /f "tokens=2" %%P in ('wmic process where "commandline like '%%-S localhost%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
    set "PHPSVR_FOUND=1"
    echo        发现 PHP 内置服务器 PID=%%P，终止...
    taskkill /PID %%P >nul 2>&1
)
if "!PHPSVR_FOUND!"=="1" (
    timeout /t 2 /nobreak >nul
    REM 强制终止残留
    for /f "tokens=2" %%P in ('wmic process where "commandline like '%%-S localhost%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
        taskkill /PID %%P /F >nul 2>&1
    )
    set "PHPCGI_STOPPED=1"
    echo        PHP 内置服务器已停止                            [OK]
)

if "!NGINX_STOPPED!"=="0" if "!PHPCGI_STOPPED!"=="0" (
    echo        Web 服务器相关进程未运行                        [跳过]
)
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 4/5: 停止 MySQL
REM ══════════════════════════════════════════════════════════════
echo  [4/5] 停止 MySQL...

REM 检查 MySQL 是否在运行
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | findstr /I "mysqld.exe" >nul
if !ERRORLEVEL! neq 0 (
    echo        MySQL 未运行                                    [跳过]
    goto :mysql_done
)

REM 优先使用 mysqladmin 优雅关闭
set "MYSQLADMIN_EXE="
where mysqladmin >nul 2>&1 && for /f "tokens=*" %%P in ('where mysqladmin 2^>nul') do (
    set "MYSQLADMIN_EXE=%%P"
    goto :found_mysqladmin
)

REM 搜索 Laragon 目录（兼容不同 MySQL 目录命名）
for %%L in (
    "%~dp0laragon"
    "%~dp0..\laragon"
    "C:\DentalClinic\laragon"
    "C:\laragon"
) do (
    for /d %%D in ("%%~L\bin\mysql\mysql-*") do (
        if exist "%%D\bin\mysqladmin.exe" (
            set "MYSQLADMIN_EXE=%%D\bin\mysqladmin.exe"
            goto :found_mysqladmin
        )
    )
    for /d %%D in ("%%~L\bin\mysql\*") do (
        if exist "%%D\bin\mysqladmin.exe" (
            set "MYSQLADMIN_EXE=%%D\bin\mysqladmin.exe"
            goto :found_mysqladmin
        )
    )
)

:found_mysqladmin
if defined MYSQLADMIN_EXE (
    echo        使用 mysqladmin 优雅关闭 MySQL...
    "%MYSQLADMIN_EXE%" -u root shutdown >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        REM 等待 mysqld 进程退出
        set /a "WAIT=0"
        :wait_mysql_shutdown
        timeout /t 2 /nobreak >nul
        tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | findstr /I "mysqld.exe" >nul
        if !ERRORLEVEL! neq 0 (
            echo        MySQL 已优雅关闭                              [OK]
            set "MYSQL_STOPPED=1"
            goto :mysql_done
        )
        set /a "WAIT+=2"
        if !WAIT! geq %GRACEFUL_TIMEOUT% (
            echo        优雅关闭超时，强制终止...
            goto :mysql_force_kill
        )
        goto :wait_mysql_shutdown
    )
    echo        mysqladmin shutdown 失败，使用 taskkill...
)

:mysql_force_kill
echo        强制终止 MySQL 进程...
taskkill /IM mysqld.exe /F >nul 2>&1
set "MYSQL_STOPPED=1"
echo        MySQL 已强制停止                                [OK]

:mysql_done
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 5/5: 状态确认
REM ══════════════════════════════════════════════════════════════
echo  [5/5] 确认状态
echo.
echo  +=====================================================+
echo  |              服务停止状态汇总                        |
echo  +=====================================================+
echo  |                                                     |

if "%QUEUE_STOPPED%"=="1" (
    echo  |  队列工作进程 ........... 已停止                   |
) else (
    echo  |  队列工作进程 ........... 未运行                   |
)

if "%OCR_STOPPED%"=="1" (
    echo  |  OCR 服务 ............... 已停止                   |
) else (
    echo  |  OCR 服务 ............... 未运行                   |
)

if "%NGINX_STOPPED%"=="1" (
    echo  |  Nginx .................. 已停止                   |
) else (
    echo  |  Nginx .................. 未运行                   |
)

if "%PHPCGI_STOPPED%"=="1" (
    echo  |  PHP 服务 ............... 已停止                   |
) else (
    echo  |  PHP 服务 ............... 未运行                   |
)

if "%MYSQL_STOPPED%"=="1" (
    echo  |  MySQL .................. 已停止                   |
) else (
    echo  |  MySQL .................. 未运行                   |
)

echo  |                                                     |
echo  |  所有服务已处理完毕                                 |
echo  +=====================================================+
echo.

endlocal
pause
