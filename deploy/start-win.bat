@echo off
chcp 936 >nul 2>&1
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
set "BACKGROUND_MODE=0"
if /I "%~2"=="--background" set "BACKGROUND_MODE=1"
set "STOP_MARKER=%INSTALL_DIR%\services-stopped.flag"
if "%BACKGROUND_MODE%"=="1" if exist "%STOP_MARKER%" exit /b 0
if not "%BACKGROUND_MODE%"=="1" if exist "%STOP_MARKER%" del /f /q "%STOP_MARKER%" >nul 2>&1

REM ── 运行时形态判定 ──────────────────────────────────────────────
REM 与 install-win.ps1 / setup.bat 用同一条判据：包内有 Apache 就是 xampp。
REM   xampp   : Apache + mod_php，代码在 xampp\htdocs\dental，无 php-cgi
REM   laragon : Nginx + php-cgi，代码在 laragon\www\dental
REM 此前本脚本把 PROJECT_DIR 写死成 laragon\www\dental，xampp 安装跑到下面
REM 「环境检测」那一步就会因为找不到 artisan 直接退出 —— 也就是说 xampp 包
REM 装完之后 Web 服务器从来没被启动过。
set "XAMPP_DIR=%INSTALL_DIR%\xampp"
set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "RUNTIME_FLAVOR=laragon"
if exist "%XAMPP_DIR%\apache\bin\httpd.exe" set "RUNTIME_FLAVOR=xampp"
set "PROJECT_DIR=%LARAGON_DIR%\www\dental"
if "%RUNTIME_FLAVOR%"=="xampp" set "PROJECT_DIR=%XAMPP_DIR%\htdocs\dental"
set "EXTERNAL_MYSQL_MARKER=%INSTALL_DIR%\existing-mysql.conf"
set "APACHE_SERVICE=DentalClinicApache"

REM ── 自动发现路径（版本无关）────────────────────────────────────
set "PHP_DIR="
set "PHP_EXE="
set "PHP_CGI_EXE="
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
REM MySQL: mysql-5* → mysql-* → mysql* → 任意子目录
for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-5*") do set "MYSQL_DIR=%%D"
if not defined MYSQL_DIR for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do set "MYSQL_DIR=%%D"
if not defined MYSQL_DIR for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql*") do set "MYSQL_DIR=%%D"
if not defined MYSQL_DIR for /d %%D in ("%LARAGON_DIR%\bin\mysql\*") do if exist "%%D\bin\mysql.exe" set "MYSQL_DIR=%%D"
REM Nginx: nginx-* → 任意子目录
for /d %%D in ("%LARAGON_DIR%\bin\nginx\nginx-*") do set "NGINX_DIR=%%D"
if not defined NGINX_DIR for /d %%D in ("%LARAGON_DIR%\bin\nginx\*") do if exist "%%D\nginx.exe" set "NGINX_DIR=%%D"

if defined PHP_DIR (
    set "PHP_EXE=%PHP_DIR%\php.exe"
    set "PHP_CGI_EXE=%PHP_DIR%\php-cgi.exe"
)
if defined MYSQL_DIR (
    set "MYSQL_EXE=%MYSQL_DIR%\bin\mysql.exe"
    set "MYSQLD_EXE=%MYSQL_DIR%\bin\mysqld.exe"
    set "MYSQLADMIN_EXE=%MYSQL_DIR%\bin\mysqladmin.exe"
    set "MYSQL_INI=%LARAGON_DIR%\etc\mysql\my.ini"
)
if defined NGINX_DIR set "NGINX_EXE=%NGINX_DIR%\nginx.exe"

REM xampp 的目录布局是扁平的（没有 php-8.2.x 这种版本号子目录），
REM 上面按 laragon 布局做的探测对它全都不适用，这里直接覆盖成确定路径。
REM MYSQL_INI 必须和 install-win.ps1 里的 $DB_CONFIG_FILE 一致（xampp\mysql\my.ini）。
if "%RUNTIME_FLAVOR%"=="xampp" (
    set "PHP_DIR=%XAMPP_DIR%\php"
    set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
    set "PHP_CGI_EXE="
    set "MYSQL_DIR=%XAMPP_DIR%\mysql"
    set "MYSQL_EXE=%XAMPP_DIR%\mysql\bin\mysql.exe"
    set "MYSQLD_EXE=%XAMPP_DIR%\mysql\bin\mysqld.exe"
    set "MYSQLADMIN_EXE=%XAMPP_DIR%\mysql\bin\mysqladmin.exe"
    set "MYSQL_INI=%XAMPP_DIR%\mysql\my.ini"
    set "NGINX_DIR="
    set "NGINX_EXE="
    set "APACHE_EXE=%XAMPP_DIR%\apache\bin\httpd.exe"
)

REM 如果 Laragon 内没有，尝试系统 PATH
REM 原来靠 goto :found_sys_php 跳出 for 来只取第一条结果，但那个标签在 ( ) 块内 ——
REM cmd 里块内 :label 会让 goto 丢掉块上下文。改成用 if not defined
REM 守卫：defined 在执行时求值，天然只有第一次匹配会赋值，不需要 goto。
if not defined PHP_EXE for /f "tokens=*" %%P in ('where php 2^>nul') do (
    if not defined PHP_EXE set "PHP_EXE=%%P"
)
REM 原来靠 goto :found_sys_mysql 跳出 for 来只取第一条结果，但那个标签在 ( ) 块内 ——
REM cmd 里块内 :label 会让 goto 丢掉块上下文。改成用 if not defined
REM 守卫：defined 在执行时求值，天然只有第一次匹配会赋值，不需要 goto。
if not defined MYSQL_EXE for /f "tokens=*" %%P in ('where mysql 2^>nul') do (
    if not defined MYSQL_EXE set "MYSQL_EXE=%%P"
)
REM ── 状态变量 ────────────────────────────────────────────────────
set "LARAGON_MODE=0"
set "MYSQL_OK=0"
set "WEB_OK=0"
set "WEB_MODE=none"
set "OCR_OK=0"
set "QUEUE_OK=0"
set "APP_PORT=8000"
set "APP_URL=http://localhost"
set "OCR_PORT=5000"
set "MYSQL_WAIT_MAX=30"

REM ── 横幅 ────────────────────────────────────────────────────────
echo.
echo  +=====================================================+
echo  ^|       牙科诊所管理系统 - 启动服务                   ^|
echo  +=====================================================+
echo  ^|  安装目录: %INSTALL_DIR%
echo  +=====================================================+
echo.

REM ── 环境检测 ────────────────────────────────────────────────────
if not exist "%PROJECT_DIR%\artisan" (
    echo  [错误] 项目目录不存在或不完整: %PROJECT_DIR%
    echo         请检查安装目录参数是否正确
    goto :error
)

if not defined PHP_EXE (
    echo  [错误] 未找到 PHP，请确认本安装包内置 PHP 8.2 已正确解压
    goto :error
)

REM Laragon 面板仍可供人工查看，但后台模式由本脚本直接管理明确的
REM PHP/Nginx/OCR 进程，避免 Laragon 同时接管目标机原有的 MySQL 5.6。
set "LARAGON_MODE=0"

REM ══════════════════════════════════════════════════════════════
REM  Step 1/6: 启动 MySQL
REM ══════════════════════════════════════════════════════════════
echo  [1/6] 启动 MySQL...

REM 始终读取应用实际连接信息。隔离安装包写入 DB_PORT=3307，
REM 因此这里不会误把 3306 上的目标机 MySQL 5.6 当成本系统数据库。
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

if not defined MYSQL_EXE (
    echo        [错误] 未找到 MySQL 客户端，无法检查数据库
    goto :error
)

set "MYSQL_PWD=!APP_DB_PASS!"
"%MYSQL_EXE%" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! -e "SELECT 1" >nul 2>&1
set "MYSQL_CHECK_RC=!ERRORLEVEL!"

if "!MYSQL_CHECK_RC!"=="0" (
    echo        MySQL 连接正常: !APP_DB_HOST!:!APP_DB_PORT!          [OK]
    set "MYSQL_OK=1"
    goto :mysql_done
)

REM 现有 MySQL 模式只检查、不管理数据库生命周期。
if exist "%EXTERNAL_MYSQL_MARKER%" (
    echo        [错误] 现有 MySQL 未启动或连接失败: !APP_DB_HOST!:!APP_DB_PORT!
    echo               请先手动启动 MySQL，再重新运行 start-win.bat
    goto :error
)

REM 内置模式只启动 DentalClinicMySQL；不调用 net start mysql，也不通过
REM Laragon 启动，避免接管目标机原有的 MySQL 服务。
sc query DentalClinicMySQL >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        启动 DentalClinicMySQL 服务...
    net start DentalClinicMySQL >nul 2>&1
    goto :wait_mysql_managed
)

if not defined MYSQLD_EXE (
    echo        [错误] 未找到安装包内置 mysqld.exe
    goto :error
)
if not exist "%MYSQL_INI%" (
    echo        [错误] 未找到内置 MySQL 配置: %MYSQL_INI%
    goto :error
)

echo        直接启动安装包内置 mysqld...
start "" /b "%MYSQLD_EXE%" --defaults-file="%MYSQL_INI%" --console >nul 2>&1

:wait_mysql_managed
set /a "WAIT=0"
:wait_mysql_direct
timeout /t 2 /nobreak >nul
"%MYSQL_EXE%" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! -e "SELECT 1" >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        内置 MySQL 启动成功: !APP_DB_HOST!:!APP_DB_PORT!     [OK]
    set "MYSQL_OK=1"
    goto :mysql_done
)
set /a "WAIT+=2"
if !WAIT! geq %MYSQL_WAIT_MAX% (
    echo        [错误] 内置 MySQL 启动超时: !APP_DB_HOST!:!APP_DB_PORT!
    goto :error
)
echo        等待中... (!WAIT!/%MYSQL_WAIT_MAX% 秒^)
goto :wait_mysql_direct

:mysql_done
set "MYSQL_PWD="
set "APP_DB_PASS="
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 2/6: 启动 Nginx（或 PHP 内置服务器作为备选）
REM ══════════════════════════════════════════════════════════════
echo  [2/6] 启动 Web 服务器...

REM ─ xampp 形态：Apache + mod_php ─
REM PHP 跑在 Apache 进程内，没有 php-cgi 要维护（这正是换 XAMPP 的目的）。
REM Apache 由 install-win.ps1 注册成 DentalClinicApache 服务（对齐 DentalClinicMySQL
REM 的做法），因此这里只会动本系统这一个实例，绝不碰目标机上其他 Apache。
REM net start 幂等：已在运行时返回非零，不当失败 —— 判据是 80 端口是否在监听。
REM 标签一律放在顶层：cmd 里 ( ) 块内的 :label 行为是坏的。
if not "%RUNTIME_FLAVOR%"=="xampp" goto :web_laragon
sc query %APACHE_SERVICE% >nul 2>&1
if errorlevel 1 goto :apache_no_service
echo        启动 Apache 服务 ^(%APACHE_SERVICE%^)...
net start %APACHE_SERVICE% >nul 2>&1
set /a "WAIT=0"
:wait_apache
timeout /t 2 /nobreak >nul
netstat -an 2>nul | findstr ":80 " | findstr "LISTENING" >nul 2>&1
if !ERRORLEVEL! equ 0 goto :apache_ok
set /a "WAIT+=2"
if !WAIT! geq 20 goto :apache_timeout
goto :wait_apache
:apache_timeout
echo        [错误] Apache 服务已启动但 80 端口始终未监听
echo               见 %XAMPP_DIR%\apache\logs\error.log
goto :error
:apache_no_service
echo        [错误] 未找到 %APACHE_SERVICE% 服务
echo               请重新运行安装程序（install-win.bat）以注册 Apache 服务
goto :error
:apache_ok
echo        Apache 已就绪 ^(%APACHE_SERVICE%^)                [OK]
set "WEB_OK=1"
set "WEB_MODE=apache"
set "APP_URL=http://localhost"
goto :web_done

:web_laragon
REM Nginx 只负责 HTTP，PHP 请求必须先有 php-cgi 监听 9000。
netstat -an 2>nul | findstr ":9000 " | findstr "LISTENING" >nul 2>&1
if !ERRORLEVEL! equ 0 goto :php_cgi_ready
if not exist "%PHP_CGI_EXE%" (
    echo        [错误] 未找到 php-cgi.exe
    goto :error
)
echo        启动 PHP FastCGI (127.0.0.1:9000^)...
start "" /b "%PHP_CGI_EXE%" -b 127.0.0.1:9000 -c "%PHP_DIR%\php.ini" >nul 2>&1
set /a "WAIT=0"
:wait_php_cgi
timeout /t 1 /nobreak >nul
netstat -an 2>nul | findstr ":9000 " | findstr "LISTENING" >nul 2>&1
if !ERRORLEVEL! equ 0 goto :php_cgi_ready
set /a "WAIT+=1"
if !WAIT! geq 15 (
    echo        [错误] PHP FastCGI 启动超时
    goto :error
)
goto :wait_php_cgi
:php_cgi_ready
echo        PHP FastCGI 已就绪                            [OK]

REM Laragon 模式: Nginx 由 Laragon 管理
if "%LARAGON_MODE%"=="1" (
    REM 等 Laragon 拉起 Nginx
    timeout /t 3 /nobreak >nul
    tasklist /FI "IMAGENAME eq nginx.exe" 2>nul | findstr /I "nginx.exe" >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        echo        Nginx 由 Laragon 管理                       [OK]
        set "WEB_OK=1"
        set "WEB_MODE=laragon"
        set "APP_URL=http://localhost"
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
    set "APP_URL=http://localhost"
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
            start "" /b "%NGINX_EXE%" -p "%NGINX_DIR%\" -c "%LARAGON_DIR%\etc\nginx\nginx.conf" >nul 2>&1
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
            set "APP_URL=http://localhost"
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

REM 安装时若检测到本机无法运行 PaddleOCR（常见于不支持 AVX 的老 CPU），
REM 会把 .env 的 OCR_ENABLED 置为 false。此时 venv 仍然存在（pip 装成功了，
REM 只是 import paddleocr 崩溃），必须按开关跳过，否则每次启动都会白等 30 秒。
findstr /I /R /C:"^OCR_ENABLED=false" "%PROJECT_DIR%\.env" >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        OCR 已在安装时关闭（手工录入）              [跳过]
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
start "" /b "%PHP_EXE%" artisan queue:work --sleep=3 --tries=3 --max-time=3600 >nul 2>&1
popd
set "QUEUE_OK=1"
echo        队列工作进程启动成功                            [OK]

:queue_done
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 5/6: 打开浏览器
REM ══════════════════════════════════════════════════════════════
echo  [5/6] 打开浏览器...
if "%BACKGROUND_MODE%"=="1" (
    echo        后台健康检查模式，不打开浏览器                  [跳过]
    goto :browser_done
)
timeout /t 2 /nobreak >nul
start "" "%APP_URL%"
echo        已打开 %APP_URL%                               [OK]
:browser_done
echo.

REM ══════════════════════════════════════════════════════════════
REM  Step 6/6: 状态汇总
REM ══════════════════════════════════════════════════════════════
echo  [6/6] 服务状态汇总
echo.
echo  +=====================================================+
echo  ^|              服务状态汇总                           ^|
echo  +=====================================================+
echo  ^|                                                     ^|

if "%MYSQL_OK%"=="1" (
    echo  ^|  MySQL .................. 运行中                   ^|
) else (
    echo  ^|  MySQL .................. 未启动                   ^|
)

if "%WEB_MODE%"=="apache" (
    echo  ^|  Web 服务器 ............. Apache + mod_php         ^|
) else if "%WEB_MODE%"=="laragon" (
    echo  ^|  Web 服务器 ............. Laragon Nginx            ^|
) else if "%WEB_MODE%"=="nginx" (
    echo  ^|  Web 服务器 ............. Nginx                    ^|
) else if "%WEB_MODE%"=="php-builtin" (
    echo  ^|  Web 服务器 ............. PHP 内置 (:%APP_PORT%^)       ^|
) else (
    echo  ^|  Web 服务器 ............. 未启动                   ^|
)

if "%OCR_OK%"=="1" (
    echo  ^|  OCR 服务 ............... 运行中 (:%OCR_PORT%^)        ^|
) else (
    echo  ^|  OCR 服务 ............... 未启动                   ^|
)

if "%QUEUE_OK%"=="1" (
    echo  ^|  队列工作进程 ........... 运行中                   ^|
) else (
    echo  ^|  队列工作进程 ........... 未启动                   ^|
)

echo  ^|                                                     ^|
echo  ^|  访问地址: %APP_URL%
echo  ^|  停止服务: 运行 stop-win.bat                        ^|
echo  +=====================================================+
echo.
goto :done

:error
echo.
echo  +=====================================================+
echo  ^|  启动失败！请检查以上错误信息                       ^|
echo  ^|  修复问题后可重新运行此脚本                         ^|
echo  +=====================================================+
echo.

:done
if not "%BACKGROUND_MODE%"=="1" pause
endlocal
