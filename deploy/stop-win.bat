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
set "BACKGROUND_MODE=0"
if /I "%~1"=="--background" set "BACKGROUND_MODE=1"
if /I "%~2"=="--background" set "BACKGROUND_MODE=1"
set "INSTALL_DIR=%~dp0"
if "%INSTALL_DIR:~-1%"=="\" set "INSTALL_DIR=%INSTALL_DIR:~0,-1%"
REM 运行时形态判定，与 install-win.ps1 / setup.bat / start-win.bat 同一条判据。
set "XAMPP_DIR=%INSTALL_DIR%\xampp"
set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "RUNTIME_FLAVOR=laragon"
if exist "%XAMPP_DIR%\apache\bin\httpd.exe" set "RUNTIME_FLAVOR=xampp"
set "PROJECT_DIR=%LARAGON_DIR%\www\dental"
if "%RUNTIME_FLAVOR%"=="xampp" set "PROJECT_DIR=%XAMPP_DIR%\htdocs\dental"
set "EXTERNAL_MYSQL_MARKER=%INSTALL_DIR%\existing-mysql.conf"
set "APACHE_SERVICE=DentalClinicApache"
set "APACHE_STOPPED=0"

REM 阻止每分钟运行的健康检查在停止过程中把组件重新拉起。
>"%INSTALL_DIR%\services-stopped.flag" echo stopped
schtasks /end /tn "DentalClinic-ServiceWatchdog" >nul 2>&1
schtasks /end /tn "DentalClinic-AutoStart" >nul 2>&1
schtasks /end /tn "DentalClinic-QueueWorker" >nul 2>&1

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

REM ─ 停止 Apache（xampp 形态）─
REM 只停本系统注册的那一个服务实例，绝不按 httpd.exe 进程名批量终止 ——
REM 目标机上可能还有别人的 Apache，这跟本文件对 mysqld 的原则一致。
REM net stop 是同步的，返回即已停止；服务不存在/已停止时返回非零，属正常。
REM 这一步必须在 setup.bat 覆盖文件之前生效，否则 httpd.exe 会占着
REM php8ts.dll / apache\logs\* 让 xcopy 失败。
if not "%RUNTIME_FLAVOR%"=="xampp" goto :apache_skip
sc query %APACHE_SERVICE% >nul 2>&1
if errorlevel 1 goto :apache_no_svc
echo        停止 Apache 服务 ^(%APACHE_SERVICE%^)...
net stop %APACHE_SERVICE% >nul 2>&1
echo        Apache 已停止                                   [OK]
set "APACHE_STOPPED=1"
goto :apache_skip
:apache_no_svc
echo        未注册 %APACHE_SERVICE% 服务，跳过               [跳过]
:apache_skip

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

REM 现有 MySQL 由目标机管理；绝不调用 mysqladmin、net stop 或 taskkill。
if exist "%EXTERNAL_MYSQL_MARKER%" (
    echo        现有 MySQL 不属于本系统、保持运行             [跳过]
    goto :mysql_done
)

REM 从 .env 读取本系统端口。隔离包为 3307，所有关闭和探测都带明确端口，
REM 绝不按 mysqld.exe 进程名批量终止，避免影响 3306 上的原有 MySQL。
set "APP_DB_HOST=127.0.0.1"
set "APP_DB_PORT=3306"
set "APP_DB_USER=root"
set "APP_DB_PASS="
if exist "%PROJECT_DIR%\.env" (
    for /f "usebackq tokens=1,* delims==" %%A in ("%PROJECT_DIR%\.env") do (
        if /i "%%A"=="DB_HOST" set "APP_DB_HOST=%%B"
        if /i "%%A"=="DB_PORT" set "APP_DB_PORT=%%B"
        if /i "%%A"=="DB_USERNAME" set "APP_DB_USER=%%B"
        if /i "%%A"=="DB_PASSWORD" set "APP_DB_PASS=%%B"
    )
)

set "MYSQLADMIN_EXE="
for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do (
    if exist "%%D\bin\mysqladmin.exe" set "MYSQLADMIN_EXE=%%D\bin\mysqladmin.exe"
)
if not defined MYSQLADMIN_EXE for /d %%D in ("%LARAGON_DIR%\bin\mysql\*") do (
    if exist "%%D\bin\mysqladmin.exe" set "MYSQLADMIN_EXE=%%D\bin\mysqladmin.exe"
)

set "MYSQL_PWD=!APP_DB_PASS!"
if defined MYSQLADMIN_EXE (
    "!MYSQLADMIN_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! ping >nul 2>&1
    if !ERRORLEVEL! neq 0 (
        echo        内置 MySQL 未运行                              [跳过]
        goto :mysql_done
    )

    echo        优雅关闭 DentalClinicMySQL ^(!APP_DB_HOST!:!APP_DB_PORT!^)...
    "!MYSQLADMIN_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! shutdown >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        set /a "WAIT=0"
        goto :wait_mysql_shutdown
    )
)

REM mysqladmin 失败时只停止本系统注册的服务，不操作其他 MySQL 服务。
echo        通过 DentalClinicMySQL 服务停止...
net stop DentalClinicMySQL >nul 2>&1
if defined MYSQLADMIN_EXE (
    set /a "WAIT=0"
    goto :wait_mysql_shutdown
)
echo        已发送服务停止请求                              [OK]
set "MYSQL_STOPPED=1"
goto :mysql_done

:wait_mysql_shutdown
timeout /t 2 /nobreak >nul
"!MYSQLADMIN_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! ping >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo        内置 MySQL 已停止                              [OK]
    set "MYSQL_STOPPED=1"
    goto :mysql_done
)
set /a "WAIT+=2"
if !WAIT! geq %GRACEFUL_TIMEOUT% (
    echo        [警告] !APP_DB_HOST!:!APP_DB_PORT! 仍在监听；为保护其他 MySQL，未强制终止进程
    goto :mysql_done
)
goto :wait_mysql_shutdown

:mysql_done
set "MYSQL_PWD="
set "APP_DB_PASS="
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

if not "%BACKGROUND_MODE%"=="1" pause
endlocal
