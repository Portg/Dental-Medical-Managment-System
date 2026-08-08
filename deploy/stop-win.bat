@echo off
chcp 936 >nul 2>&1
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
set "DB_SERVICE=DentalClinicMySQL"
if "%RUNTIME_FLAVOR%"=="xampp" set "DB_SERVICE=DentalClinicMariaDB"
set "APACHE_STOPPED=0"

REM 阻止每分钟运行的健康检查在停止过程中把组件重新拉起。
>"%INSTALL_DIR%\services-stopped.flag" echo stopped
schtasks /end /tn "DentalClinic-ServiceWatchdog" >nul 2>&1
schtasks /end /tn "DentalClinic-AutoStart" >nul 2>&1
schtasks /end /tn "DentalClinic-QueueWorker" >nul 2>&1
REM DentalClinic-Scheduler（每分钟 artisan schedule:run）此前既没 /end 也没禁用，
REM 服务停着它照跑：2026-08-08 那次装机 laravel.log 里 67 条 2002 + 7 条 1045
REM 全是它打的，snooze:send 在 scheduler.log 里连续 FAIL 35 次也是同一件事。
REM 而且 /end 只结束当前这一次，下一分钟还会再触发 —— 必须 /disable。
REM 恢复点在 start-win.bat 开头（手动启动）与 install-win.ps1 第 18 步（重装）。
schtasks /end /tn "DentalClinic-Scheduler" >nul 2>&1
schtasks /change /tn "DentalClinic-Scheduler" /disable >nul 2>&1
schtasks /change /tn "DentalClinic-ServiceWatchdog" /disable >nul 2>&1

echo.
echo  +=====================================================+
echo  ^|       牙科诊所管理系统 - 停止服务                   ^|
echo  +=====================================================+
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 1/5: 停止队列工作进程 (php artisan queue:work)
REM ══════════════════════════════════════════════════════════════
echo  [1/5] 停止队列工作进程...

set "QUEUE_FOUND=0"
for /f "tokens=1" %%P in ('wmic process where "commandline like '%%queue:work%%'" get processid 2^>nul ^| findstr /R "^[0-9][0-9]*"') do (
    set "QUEUE_FOUND=1"
    echo        发现队列进程 PID=%%P，尝试优雅关闭...
    taskkill /PID %%P >nul 2>&1
)

REM 等待循环与标签必须放在**顶层**：cmd 里 ( ) 块内的 :label 会让
REM goto 丢掉块上下文，跳过去之后就按文件顺序线性往下跑，块结构失效。
REM 本文件此前从没跑通过（第 43 行横幅的裸管道会中止批处理），
REM 所以这几段等待逻辑从未真正执行，问题一直没暴露。
if not "!QUEUE_FOUND!"=="1" goto :queue_not_running
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
    for /f "tokens=1" %%P in ('wmic process where "commandline like '%%queue:work%%'" get processid 2^>nul ^| findstr /R "^[0-9][0-9]*"') do (
        taskkill /PID %%P /F >nul 2>&1
    )
    echo        队列工作进程已强制停止                        [OK]
    set "QUEUE_STOPPED=1"
    goto :queue_done
)
goto :wait_queue_stop
:queue_not_running
echo        队列工作进程未运行                              [跳过]

:queue_done
REM 也终止通过 start /min 标题创建的窗口
taskkill /FI "WINDOWTITLE eq dental-queue-worker" /F >nul 2>&1
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 2/5: 停止 OCR 服务 (Python on port 5000)
REM ══════════════════════════════════════════════════════════════
echo  [2/5] 停止 OCR 服务...

set "OCR_FOUND=0"
for /f "tokens=1" %%P in ('wmic process where "commandline like '%%ocr_server%%'" get processid 2^>nul ^| findstr /R "^[0-9][0-9]*"') do (
    set "OCR_FOUND=1"
    echo        发现 OCR 进程 PID=%%P，尝试优雅关闭...
    taskkill /PID %%P >nul 2>&1
)

REM 等待循环与标签必须放在**顶层**：cmd 里 ( ) 块内的 :label 会让
REM goto 丢掉块上下文，跳过去之后就按文件顺序线性往下跑，块结构失效。
REM 本文件此前从没跑通过（第 43 行横幅的裸管道会中止批处理），
REM 所以这几段等待逻辑从未真正执行，问题一直没暴露。
if not "!OCR_FOUND!"=="1" goto :ocr_not_running
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
    for /f "tokens=1" %%P in ('wmic process where "commandline like '%%ocr_server%%'" get processid 2^>nul ^| findstr /R "^[0-9][0-9]*"') do (
        taskkill /PID %%P /F >nul 2>&1
    )
    echo        OCR 服务已强制停止                            [OK]
    set "OCR_STOPPED=1"
    goto :ocr_done
)
goto :wait_ocr_stop
:ocr_not_running
echo        OCR 服务未运行                                  [跳过]

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
REM 同上：等待循环与标签摊到顶层，块内 :label 在 cmd 里行为是坏的。
REM 注释必须放在探测命令**之前**：命令与 errorlevel 判断之间不留任何东西，
REM 免得依赖「REM 到底会不会重置 errorlevel」这种记忆性结论。
tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | findstr /I "nginx.exe" >nul
if !ERRORLEVEL! neq 0 goto :nginx_not_running
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
    echo        优雅关闭超时，强制终止本安装目录下的 Nginx...
    call :kill_by_cmdline nginx.exe "%INSTALL_DIR%"
    echo        Nginx 已强制停止                              [OK]
    set "NGINX_STOPPED=1"
    goto :nginx_done
)
goto :wait_nginx_stop
:nginx_not_running
echo        Nginx 未运行                                    [跳过]

:nginx_done

REM ─ 停止 PHP-CGI（仅本安装目录下的进程，避免误伤同机其他栈）─
set "PHPCGI_STOPPED=0"
call :kill_by_cmdline php-cgi.exe "%INSTALL_DIR%"
if "!KILL_HIT!"=="1" (
    set "PHPCGI_STOPPED=1"
    echo        PHP-CGI 已停止                                [OK]
)

REM ─ 停止 PHP 内置服务器 ─
set "PHPSVR_FOUND=0"
for /f "tokens=1" %%P in ('wmic process where "commandline like '%%-S localhost%%'" get processid 2^>nul ^| findstr /R "^[0-9][0-9]*"') do (
    set "PHPSVR_FOUND=1"
    echo        发现 PHP 内置服务器 PID=%%P，终止...
    taskkill /PID %%P >nul 2>&1
)
if "!PHPSVR_FOUND!"=="1" (
    timeout /t 2 /nobreak >nul
    REM 强制终止残留
    for /f "tokens=1" %%P in ('wmic process where "commandline like '%%-S localhost%%'" get processid 2^>nul ^| findstr /R "^[0-9][0-9]*"') do (
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

    echo        优雅关闭 !DB_SERVICE! ^(!APP_DB_HOST!:!APP_DB_PORT!^)...
    "!MYSQLADMIN_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! shutdown >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        set /a "WAIT=0"
        goto :wait_mysql_shutdown
    )
)

REM mysqladmin 失败时只停止本系统注册的服务，不操作其他 MySQL 服务。
echo        通过 !DB_SERVICE! 服务停止...
net stop !DB_SERVICE! >nul 2>&1
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
echo  ^|              服务停止状态汇总                        ^|
echo  +=====================================================+
echo  ^|                                                     ^|

if "%QUEUE_STOPPED%"=="1" (
    echo  ^|  队列工作进程 ........... 已停止                   ^|
) else (
    echo  ^|  队列工作进程 ........... 未运行                   ^|
)

if "%OCR_STOPPED%"=="1" (
    echo  ^|  OCR 服务 ............... 已停止                   ^|
) else (
    echo  ^|  OCR 服务 ............... 未运行                   ^|
)

if "%NGINX_STOPPED%"=="1" (
    echo  ^|  Nginx .................. 已停止                   ^|
) else (
    echo  ^|  Nginx .................. 未运行                   ^|
)

if "%PHPCGI_STOPPED%"=="1" (
    echo  ^|  PHP 服务 ............... 已停止                   ^|
) else (
    echo  ^|  PHP 服务 ............... 未运行                   ^|
)

if "%MYSQL_STOPPED%"=="1" (
    echo  ^|  MySQL .................. 已停止                   ^|
) else (
    echo  ^|  MySQL .................. 未运行                   ^|
)

echo  ^|                                                     ^|
echo  ^|  所有服务已处理完毕                                 ^|
echo  +=====================================================+
echo.

if not "%BACKGROUND_MODE%"=="1" pause
endlocal
exit /b 0

REM ── 仅终止 CommandLine 含指定安装目录的进程（WMI LIKE 需转义反斜杠）──
:kill_by_cmdline
set "KILL_HIT=0"
set "KILL_IMG=%~1"
set "KILL_DIR=%~2"
if "%KILL_DIR%"=="" goto :eof
set "KILL_LIKE=%KILL_DIR:\=\\%"
REM 不用 /value + delims== ：那样 tokens=2 会把行尾的 CR 一起吃进去
REM （wmic 的输出带 CR），taskkill /PID 拿到 "1234<CR>" 会失败。
REM 表格格式下 PID 后面跟空格，tokens=* 配 findstr /R "^[0-9]" 取到的是干净数字。
for /f "usebackq tokens=1" %%P in (`wmic process where "name='%KILL_IMG%' and CommandLine like '%%%KILL_LIKE%%%'" get ProcessId 2^>nul ^| findstr /R "^[0-9][0-9]*"`) do (
    if not "%%P"=="" (
        taskkill /PID %%P /F >nul 2>&1
        set "KILL_HIT=1"
    )
)
goto :eof
