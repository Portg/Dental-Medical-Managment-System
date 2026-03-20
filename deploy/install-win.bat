@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion
title 牙科诊所管理系统 - 离线安装程序

REM ═══════════════════════════════════════════════════════════════════════
REM  牙科诊所管理系统 — Windows 离线安装脚本 (Laragon Portable)
REM
REM  用法:
REM    install-win.bat [INSTALL_DIR] [选项]
REM
REM  参数:
REM    INSTALL_DIR           安装目录 (默认 C:\DentalClinic)
REM
REM  选项:
REM    --db-host <host>      数据库主机       (默认 127.0.0.1)
REM    --db-port <port>      数据库端口       (默认 3306)
REM    --db-name <name>      数据库名         (默认 pristine_dental)
REM    --db-user <user>      数据库用户       (默认 root)
REM    --db-pass <pass>      数据库密码       (默认 空; 设置后自动创建专用用户)
REM    --app-url <url>       应用地址         (默认 http://localhost)
REM    --no-ocr              跳过 OCR 环境安装
REM    --no-service          跳过 Windows 服务注册
REM    --yes                 跳过所有确认提示 (静默模式)
REM ═══════════════════════════════════════════════════════════════════════

REM ── 默认参数 ─────────────────────────────────────────────────────────
set "INSTALL_DIR=C:\DentalClinic"
set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "DB_NAME=pristine_dental"
set "DB_USER=root"
set "DB_PASS="
set "APP_URL=http://localhost"
set "SKIP_OCR=0"
set "SKIP_SERVICE=0"
set "SILENT_MODE=0"
set "TOTAL_STEPS=18"
set "STEP=0"
set "FAILED_STEP="

REM ── 解析命令行参数 ──────────────────────────────────────────────────
REM 第一个非 -- 参数视为 INSTALL_DIR
set "POSITIONAL_PARSED=0"

:parse_args
if "%~1"=="" goto :args_done
if /i "%~1"=="--db-host"    ( set "DB_HOST=%~2"    & shift & shift & goto :parse_args )
if /i "%~1"=="--db-port"    ( set "DB_PORT=%~2"    & shift & shift & goto :parse_args )
if /i "%~1"=="--db-name"    ( set "DB_NAME=%~2"    & shift & shift & goto :parse_args )
if /i "%~1"=="--db-user"    ( set "DB_USER=%~2"    & shift & shift & goto :parse_args )
if /i "%~1"=="--db-pass"    ( set "DB_PASS=%~2"    & shift & shift & goto :parse_args )
if /i "%~1"=="--app-url"    ( set "APP_URL=%~2"    & shift & shift & goto :parse_args )
if /i "%~1"=="--no-ocr"     ( set "SKIP_OCR=1"     & shift & goto :parse_args )
if /i "%~1"=="--no-service" ( set "SKIP_SERVICE=1"  & shift & goto :parse_args )
if /i "%~1"=="--yes"        ( set "SILENT_MODE=1"   & shift & goto :parse_args )
if /i "%~1"=="-y"           ( set "SILENT_MODE=1"   & shift & goto :parse_args )
REM 第一个非选项参数视为 INSTALL_DIR
echo "%~1" | findstr /b /c:"--" >nul 2>&1
if %ERRORLEVEL% neq 0 (
    if "%POSITIONAL_PARSED%"=="0" (
        set "INSTALL_DIR=%~1"
        set "POSITIONAL_PARSED=1"
        shift
        goto :parse_args
    )
)
echo [警告] 未知参数: %~1 (已忽略)
shift
goto :parse_args
:args_done

REM ── 去掉末尾反斜杠 ─────────────────────────────────────────────────
if "%INSTALL_DIR:~-1%"=="\" set "INSTALL_DIR=%INSTALL_DIR:~0,-1%"

REM ── 推导目录结构 ───────────────────────────────────────────────────
set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "PROJECT_DIR=%LARAGON_DIR%\www\dental"
set "NGINX_CONF_DIR=%LARAGON_DIR%\etc\nginx\sites-enabled"

echo.
echo  +=========================================================+
echo  |       牙科诊所管理系统 -- Windows 离线安装程序          |
echo  +=========================================================+
echo  |  安装目录: %INSTALL_DIR%
echo  |  项目目录: %PROJECT_DIR%
echo  +=========================================================+
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 1: 预检 — 管理员权限
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 管理员权限检查"
echo [%STEP%/%TOTAL_STEPS%] 检查管理员权限...

net session >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo.
    echo  [错误] 此脚本需要管理员权限运行。
    echo         请右键点击此文件，选择"以管理员身份运行"。
    echo.
    goto :error
)
echo        管理员权限 ....... OK
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 2: 预检 — 磁盘空间 ^& 已有安装
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 磁盘空间与安装检查"
echo [%STEP%/%TOTAL_STEPS%] 检查磁盘空间和已有安装...

REM 提取目标盘符
set "TARGET_DRIVE=%INSTALL_DIR:~0,2%"

REM 检查磁盘可用空间（需要 >2GB = 2147483648 字节）
set "DISK_OK=0"
for /f "skip=1 tokens=*" %%A in ('wmic logicaldisk where "DeviceID='%TARGET_DRIVE%'" get FreeSpace 2^>nul') do (
    set "FREE_BYTES=%%A"
    REM 去除尾部空格
    for /f "tokens=1" %%B in ("!FREE_BYTES!") do set "FREE_BYTES=%%B"
    if defined FREE_BYTES (
        REM 转换为 MB 避免32位整数溢出: 去掉末6位再比较
        set "FREE_MB_RAW=!FREE_BYTES:~0,-6!"
        if defined FREE_MB_RAW (
            REM FREE_MB_RAW 是 MB 的近似值（实际 = FREE_BYTES / 1000000）
            if !FREE_MB_RAW! geq 2147 (
                set "DISK_OK=1"
            )
        )
    )
)
if "%DISK_OK%"=="0" (
    echo.
    echo  [错误] %TARGET_DRIVE% 磁盘可用空间不足 2GB。
    echo         请清理磁盘或选择其他安装目录。
    echo         用法: install-win.bat D:\DentalClinic
    echo.
    goto :error
)
echo        磁盘空间 ......... OK (>2GB)

REM 检查已有安装 / 半截安装残留
set "PARTIAL_INSTALL=0"
if exist "%INSTALL_DIR%\laragon" set "PARTIAL_INSTALL=1"
if exist "%INSTALL_DIR%\install-win.bat" set "PARTIAL_INSTALL=1"
if exist "%PROJECT_DIR%" set "PARTIAL_INSTALL=1"

if exist "%PROJECT_DIR%\artisan" (
    echo.
    echo  [警告] 检测到已有安装: %PROJECT_DIR%
    echo.
    if "%SILENT_MODE%"=="1" (
        echo         静默模式：将覆盖已有安装
    ) else (
        set /p "OVERWRITE_CONFIRM=         是否覆盖已有安装？(Y/N): "
        if /i "!OVERWRITE_CONFIRM!" neq "Y" (
            echo  安装已取消。
            goto :done
        )
    )
    echo        已确认覆盖安装
) else (
    if "%PARTIAL_INSTALL%"=="1" (
        echo.
        echo  [警告] 检测到不完整的安装残留: %INSTALL_DIR%
        echo.
        echo         这通常表示上次安装过程中断或失败。
        echo         当前脚本将尝试继续覆盖安装。
        echo         如果后续在“环境检测/复制文件”阶段失败，
        echo         请先关闭 Laragon、MySQL、Nginx 后清理安装目录再重试。
        echo.
    ) else (
        echo        未检测到已有安装
    )
)
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 3: 环境检测 — 自动查找 PHP / MySQL / Nginx / Composer
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 环境检测"
echo [%STEP%/%TOTAL_STEPS%] 检测 Laragon 运行环境...

REM 验证 Laragon 目录存在
if not exist "%LARAGON_DIR%\bin" (
    echo.
    echo  [错误] 未找到 Laragon 目录: %LARAGON_DIR%\bin
    echo         请确认安装包结构正确，或指定正确的安装目录。
    echo.
    goto :error
)

REM ---------- PHP ----------
set "PHP_EXE="
set "PHP_DIR="
REM 优先: php-8* (Laragon 标准命名: php-8.2.25-nts-Win32-vs16-x64)
for /d %%D in ("%LARAGON_DIR%\bin\php\php-8*") do (
    if exist "%%D\php.exe" (
        set "PHP_EXE=%%D\php.exe"
        set "PHP_DIR=%%D"
    )
)
REM 回退1: php8* (某些 Laragon 版本: php8.2.25nts)
if not defined PHP_EXE (
    for /d %%D in ("%LARAGON_DIR%\bin\php\php8*") do (
        if exist "%%D\php.exe" (
            set "PHP_EXE=%%D\php.exe"
            set "PHP_DIR=%%D"
        )
    )
)
REM 回退2: 任意 php* 子目录
if not defined PHP_EXE (
    for /d %%D in ("%LARAGON_DIR%\bin\php\php*") do (
        if exist "%%D\php.exe" (
            set "PHP_EXE=%%D\php.exe"
            set "PHP_DIR=%%D"
        )
    )
)
REM 回退3: php.exe 直接在 bin\php\ 目录下
if not defined PHP_EXE (
    if exist "%LARAGON_DIR%\bin\php\php.exe" (
        set "PHP_EXE=%LARAGON_DIR%\bin\php\php.exe"
        set "PHP_DIR=%LARAGON_DIR%\bin\php"
    )
)
REM 回退4: 任意子目录下的 php.exe
if not defined PHP_EXE (
    for /d %%D in ("%LARAGON_DIR%\bin\php\*") do (
        if exist "%%D\php.exe" (
            set "PHP_EXE=%%D\php.exe"
            set "PHP_DIR=%%D"
        )
    )
)
if not defined PHP_EXE (
    echo.
    echo  [错误] 未在 %LARAGON_DIR%\bin\php\ 下找到 PHP
    echo.
    echo         已扫描以下目录:
    echo           %LARAGON_DIR%\bin\php\php-8*
    echo           %LARAGON_DIR%\bin\php\php8*
    echo           %LARAGON_DIR%\bin\php\php*
    echo           %LARAGON_DIR%\bin\php\php.exe
    echo           %LARAGON_DIR%\bin\php\*\php.exe
    echo.
    if exist "%LARAGON_DIR%\bin\php\" (
        echo         bin\php\ 目录内容:
        dir "%LARAGON_DIR%\bin\php\" /b 2^>nul
        echo.
    ) else (
        echo         bin\php\ 目录不存在！
        echo.
    )
    echo         请确认 Laragon 安装包包含 PHP 8.2+
    echo         或将 PHP 解压到 %LARAGON_DIR%\bin\php\php-8.x\ 目录
    goto :error
)
set "PATH=!PHP_DIR!;!PATH!"
echo        PHP .............. !PHP_EXE!

REM ---------- MySQL ----------
set "MYSQL_EXE="
set "MYSQLD_EXE="
set "MYSQL_BIN_DIR="
set "MYSQL_BASE_DIR="
for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-8*") do (
    if exist "%%D\bin\mysql.exe" (
        set "MYSQL_EXE=%%D\bin\mysql.exe"
        set "MYSQL_BIN_DIR=%%D\bin"
        set "MYSQL_BASE_DIR=%%D"
    )
    if exist "%%D\bin\mysqld.exe" set "MYSQLD_EXE=%%D\bin\mysqld.exe"
)
REM 回退: 任意 MySQL 版本
if not defined MYSQL_EXE (
    for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do (
        if exist "%%D\bin\mysql.exe" (
            set "MYSQL_EXE=%%D\bin\mysql.exe"
            set "MYSQL_BIN_DIR=%%D\bin"
            set "MYSQL_BASE_DIR=%%D"
        )
        if exist "%%D\bin\mysqld.exe" set "MYSQLD_EXE=%%D\bin\mysqld.exe"
    )
)
if not defined MYSQL_EXE (
    echo  [错误] 未在 %LARAGON_DIR%\bin\mysql\ 下找到 MySQL
    goto :error
)
set "PATH=!MYSQL_BIN_DIR!;!PATH!"
echo        MySQL ............ !MYSQL_EXE!
if defined MYSQLD_EXE echo        mysqld ........... !MYSQLD_EXE!

REM ---------- Nginx ----------
set "NGINX_EXE="
set "NGINX_DIR="
for /d %%D in ("%LARAGON_DIR%\bin\nginx\nginx-*") do (
    if exist "%%D\nginx.exe" (
        set "NGINX_EXE=%%D\nginx.exe"
        set "NGINX_DIR=%%D"
    )
)
if defined NGINX_EXE (
    echo        Nginx ............ !NGINX_EXE!
) else (
    echo        Nginx ............ [未找到，跳过 Nginx 配置]
)

REM ---------- Composer ----------
set "COMPOSER_CMD="
if exist "%LARAGON_DIR%\bin\composer\composer.phar" (
    set "COMPOSER_CMD="!PHP_EXE!" "%LARAGON_DIR%\bin\composer\composer.phar""
)
if not defined COMPOSER_CMD (
    where composer.bat >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        set "COMPOSER_CMD=composer"
    ) else (
        where composer >nul 2>&1
        if !ERRORLEVEL! equ 0 set "COMPOSER_CMD=composer"
    )
)
if defined COMPOSER_CMD (
    echo        Composer ......... !COMPOSER_CMD!
) else (
    echo  [错误] 未找到 Composer (检查: %LARAGON_DIR%\bin\composer\composer.phar)
    goto :error
)

REM ---------- Python (可选) ----------
set "PYTHON_CMD="
if "%SKIP_OCR%"=="1" (
    echo        Python ........... [跳过] --no-ocr
) else (
    where py >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        set "PYTHON_CMD=py -3"
    ) else (
        for %%C in (python3 python) do (
            if not defined PYTHON_CMD (
                where %%C >nul 2>&1
                if !ERRORLEVEL! equ 0 (
                    %%C --version 2>nul | findstr /r "3\.[0-9]" >nul
                    if !ERRORLEVEL! equ 0 set "PYTHON_CMD=%%C"
                )
            )
        )
    )
    if defined PYTHON_CMD (
        echo        Python ........... !PYTHON_CMD!
    ) else (
        echo        Python ........... [未找到] OCR 功能将不可用
    )
)

REM ---------- 版本验证 ----------
echo.
echo        --- 版本验证 ---

REM PHP >= 8.2
for /f "tokens=2 delims= " %%V in ('"!PHP_EXE!" -v 2^>nul ^| findstr /i "^PHP"') do set "PHP_VER=%%V"
echo        PHP 版本 ......... %PHP_VER%
for /f "tokens=1,2 delims=." %%A in ("%PHP_VER%") do (
    set "PHP_MAJOR=%%A"
    set "PHP_MINOR=%%B"
)
if not defined PHP_MAJOR (
    echo  [错误] 无法检测 PHP 版本
    goto :error
)
set /a "PHP_VER_NUM=%PHP_MAJOR% * 100 + %PHP_MINOR%"
if %PHP_VER_NUM% lss 802 (
    echo  [错误] PHP 版本需要 8.2+，当前为 %PHP_VER%
    goto :error
)
echo        PHP >= 8.2 ....... OK

REM MySQL 版本 (informational)
for /f "tokens=*" %%V in ('"!MYSQL_EXE!" --version 2^>nul') do set "MYSQL_VER_LINE=%%V"
echo        MySQL ............ %MYSQL_VER_LINE%

REM 读取项目版本
set "APP_VERSION=未知"
if exist "%PROJECT_DIR%\VERSION" (
    set /p APP_VERSION=<"%PROJECT_DIR%\VERSION"
)

REM 项目 artisan 检查
if not exist "%PROJECT_DIR%\artisan" (
    echo  [错误] 项目文件不完整，未在 %PROJECT_DIR% 找到 artisan
    echo         请确认项目代码已放置在 %PROJECT_DIR%
    goto :error
)
echo        项目文件 ......... OK
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 4: 启动 MySQL
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 启动 MySQL"
echo [%STEP%/%TOTAL_STEPS%] 启动 MySQL 服务...

REM 先用 root（无密码）测试连接 — Laragon MySQL 默认配置
set "ROOT_CONN=-h %DB_HOST% -P %DB_PORT% -u root"
"!MYSQL_EXE!" %ROOT_CONN% -e "SELECT 1" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        MySQL 已在运行
    goto :mysql_started
)

REM MySQL 未运行 — 使用 Laragon my.ini 启动
if not defined MYSQLD_EXE (
    echo  [错误] 未找到 mysqld.exe，无法启动 MySQL
    echo         请手动启动 MySQL 服务后重新运行此脚本
    goto :error
)

set "MYSQL_INI=%LARAGON_DIR%\etc\mysql\my.ini"
if exist "!MYSQL_INI!" (
    echo        使用配置: !MYSQL_INI!
    start "" /b "!MYSQLD_EXE!" --defaults-file="!MYSQL_INI!" --console >nul 2>&1
) else (
    echo        [提示] 未找到 my.ini，使用默认配置启动
    start "" /b "!MYSQLD_EXE!" --console >nul 2>&1
)

echo        等待 MySQL 启动 (最多60秒)...
set /a "WAIT_COUNT=0"
:wait_mysql
timeout /t 2 /nobreak >nul
"!MYSQL_EXE!" %ROOT_CONN% -e "SELECT 1" >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        MySQL 启动成功
    goto :mysql_started
)
set /a "WAIT_COUNT+=2"
if !WAIT_COUNT! geq 60 (
    echo.
    echo  [错误] MySQL 启动超时 (60秒)
    echo         建议: 手动运行 Laragon 面板启动 MySQL，然后重新执行此脚本
    goto :error
)
echo        ... 已等待 !WAIT_COUNT! 秒
goto :wait_mysql

:mysql_started
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 5: 创建数据库
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 创建数据库"
echo [%STEP%/%TOTAL_STEPS%] 创建数据库...

"!MYSQL_EXE!" %ROOT_CONN% -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
if %ERRORLEVEL% neq 0 (
    echo  [错误] 创建数据库 %DB_NAME% 失败
    echo         请检查 MySQL 是否有 CREATE DATABASE 权限
    goto :error
)
echo        数据库 %DB_NAME% 已就绪
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 6: 创建专用 MySQL 用户 (可选)
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 创建 MySQL 用户"
echo [%STEP%/%TOTAL_STEPS%] 配置数据库用户...

if "%DB_PASS%"=="" (
    REM 无密码 — 直接使用 root，不创建额外用户
    set "DB_USER=root"
    echo        使用默认 root 用户（无密码）
    set "MYSQL_CONN=%ROOT_CONN%"
) else (
    REM 设置了密码 — 创建专用用户
    echo        创建专用数据库用户: %DB_USER%@localhost
    "!MYSQL_EXE!" %ROOT_CONN% -e "CREATE USER IF NOT EXISTS '%DB_USER%'@'localhost' IDENTIFIED BY '%DB_PASS%';" 2>nul
    if !ERRORLEVEL! neq 0 (
        echo        [警告] 用户创建失败（可能已存在），尝试更新密码...
        "!MYSQL_EXE!" %ROOT_CONN% -e "ALTER USER '%DB_USER%'@'localhost' IDENTIFIED BY '%DB_PASS%';" 2>nul
    )
    "!MYSQL_EXE!" %ROOT_CONN% -e "GRANT ALL PRIVILEGES ON `%DB_NAME%`.* TO '%DB_USER%'@'localhost'; FLUSH PRIVILEGES;" 2>nul
    if !ERRORLEVEL! neq 0 (
        echo        [警告] 权限授予失败，回退使用 root 用户
        set "DB_USER=root"
        set "DB_PASS="
        set "MYSQL_CONN=%ROOT_CONN%"
    ) else (
        echo        用户 %DB_USER% 已创建并授权
        set "MYSQL_CONN=-h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p%DB_PASS%"
    )
)
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 7: 配置 .env
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 配置 .env"
echo [%STEP%/%TOTAL_STEPS%] 生成 .env 配置文件...

set "ENV_TEMPLATE=%PROJECT_DIR%\.env.deploy"
set "ENV_TARGET=%PROJECT_DIR%\.env"

REM 兼容旧打包结构：如果项目根没有 .env.deploy，再回退到 deploy\.env.deploy
if not exist "%ENV_TEMPLATE%" (
    set "ENV_TEMPLATE=%PROJECT_DIR%\deploy\.env.deploy"
)

REM 计算 OCR Python 路径
set "OCR_PYTHON_PATH="
if "%SKIP_OCR%"=="0" (
    if defined PYTHON_CMD (
        set "OCR_PYTHON_PATH=%PROJECT_DIR%\scripts\venv\Scripts\python.exe"
    )
)

if exist "%ENV_TEMPLATE%" (
    REM 使用 .env.deploy 模板替换占位符
    "!PHP_EXE!" -r "
        $tpl = file_get_contents('%ENV_TEMPLATE%');
        $replacements = [
            '{{DB_HOST}}'         => '%DB_HOST%',
            '{{DB_PORT}}'         => '%DB_PORT%',
            '{{DB_DATABASE}}'     => '%DB_NAME%',
            '{{DB_USERNAME}}'     => '%DB_USER%',
            '{{DB_PASSWORD}}'     => '%DB_PASS%',
            '{{APP_URL}}'         => '%APP_URL%',
            '{{OCR_PYTHON_PATH}}' => '%OCR_PYTHON_PATH%',
        ];
        $env = str_replace(array_keys($replacements), array_values($replacements), $tpl);
        file_put_contents('%ENV_TARGET%', $env);
    "
    if !ERRORLEVEL! neq 0 (
        echo  [错误] .env 模板替换失败
        goto :error
    )
    echo        已从 .env.deploy 生成 .env
) else (
    REM 回退：从 .env.example 复制然后替换
    if not exist "%ENV_TARGET%" (
        if exist "%PROJECT_DIR%\.env.example" (
            copy "%PROJECT_DIR%\.env.example" "%ENV_TARGET%" >nul
        ) else (
            echo  [错误] 未找到 .env 模板（.env.deploy 和 .env.example 均不存在）
            goto :error
        )
    )
    "!PHP_EXE!" -r "
        $env = file_get_contents('%ENV_TARGET%');
        $env = preg_replace('/^APP_ENV=.*/m',      'APP_ENV=production',    $env);
        $env = preg_replace('/^APP_DEBUG=.*/m',     'APP_DEBUG=false',       $env);
        $env = preg_replace('/^APP_URL=.*/m',       'APP_URL=%APP_URL%',     $env);
        $env = preg_replace('/^DB_HOST=.*/m',       'DB_HOST=%DB_HOST%',     $env);
        $env = preg_replace('/^DB_PORT=.*/m',       'DB_PORT=%DB_PORT%',     $env);
        $env = preg_replace('/^DB_DATABASE=.*/m',   'DB_DATABASE=%DB_NAME%', $env);
        $env = preg_replace('/^DB_USERNAME=.*/m',   'DB_USERNAME=%DB_USER%', $env);
        $env = preg_replace('/^DB_PASSWORD=.*/m',   'DB_PASSWORD=%DB_PASS%', $env);
        file_put_contents('%ENV_TARGET%', $env);
    "
    echo        已从 .env.example 生成 .env
)
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 8: 生成 APP_KEY
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 生成 APP_KEY"
echo [%STEP%/%TOTAL_STEPS%] 生成 APP_KEY...

cd /d "%PROJECT_DIR%"

REM 避免覆盖已有密钥（可能导致加密数据解密失败）
findstr /r "^APP_KEY=base64:" "%ENV_TARGET%" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        APP_KEY 已存在，跳过生成
) else (
    "!PHP_EXE!" artisan key:generate --force --no-interaction
    if !ERRORLEVEL! neq 0 (
        echo  [错误] 生成 APP_KEY 失败
        echo         请检查 PHP 扩展是否完整 (openssl, mbstring, etc.)
        goto :error
    )
    echo        APP_KEY 已生成
)
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 9: 数据库初始化 (schema.sql 或 migrate)
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 数据库初始化"
echo [%STEP%/%TOTAL_STEPS%] 初始化数据库结构...

cd /d "%PROJECT_DIR%"

set "SCHEMA_SQL=%PROJECT_DIR%\database\schema.sql"
if not exist "%SCHEMA_SQL%" (
    set "SCHEMA_SQL=%PROJECT_DIR%\database\schema\mysql-schema.sql"
)
if exist "%SCHEMA_SQL%" (
    echo        检测到 schema.sql，使用快速导入模式...
    if "%DB_PASS%"=="" (
        "!MYSQL_EXE!" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% "%DB_NAME%" < "%SCHEMA_SQL%" 2>nul
    ) else (
        "!MYSQL_EXE!" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p"%DB_PASS%" "%DB_NAME%" < "%SCHEMA_SQL%" 2>nul
    )
    if !ERRORLEVEL! neq 0 (
        echo        [警告] schema.sql 导入失败，回退到 artisan migrate...
        "!PHP_EXE!" artisan migrate --force --no-interaction
        if !ERRORLEVEL! neq 0 (
            echo  [错误] 数据库迁移也失败
            echo         请检查数据库连接和 .env 配置
            goto :error
        )
    ) else (
        echo        schema.sql 导入成功
    )
) else (
    echo        使用 artisan migrate 创建数据库表...
    "!PHP_EXE!" artisan migrate --force --no-interaction
    if !ERRORLEVEL! neq 0 (
        echo  [错误] 数据库迁移失败
        echo         请检查数据库连接: %DB_HOST%:%DB_PORT%, 用户: %DB_USER%, 数据库: %DB_NAME%
        goto :error
    )
    echo        数据库迁移完成
)
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 10: 数据库填充
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 数据库填充"
echo [%STEP%/%TOTAL_STEPS%] 初始化系统数据...

cd /d "%PROJECT_DIR%"

REM 检测是否为全新安装（users 表是否有数据）
set "IS_FRESH=1"
"!PHP_EXE!" artisan tinker --execute="echo \App\User::count()>0?'has_data':'empty';" 2>nul | findstr "has_data" >nul && set "IS_FRESH=0"

if "%IS_FRESH%"=="1" (
    echo        正在初始化系统数据（首次安装）...
    "!PHP_EXE!" artisan db:seed --force --no-interaction
    if !ERRORLEVEL! neq 0 (
        echo  [错误] 数据填充失败
        echo         请查看 storage/logs/laravel.log 获取详细错误信息
        goto :error
    )
    echo        系统数据初始化完成
) else (
    echo        检测到已有数据，跳过数据填充
)
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 11: Storage 软链接
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] Storage 链接"
echo [%STEP%/%TOTAL_STEPS%] 创建 Storage 软链接...

cd /d "%PROJECT_DIR%"
"!PHP_EXE!" artisan storage:link --force --no-interaction 2>nul
if %ERRORLEVEL% equ 0 (
    echo        Storage 链接已创建
) else (
    echo        [警告] Storage 链接创建失败
    echo        尝试使用 mklink 手动创建...
    if not exist "%PROJECT_DIR%\public\storage" (
        mklink /D "%PROJECT_DIR%\public\storage" "%PROJECT_DIR%\storage\app\public" >nul 2>&1
        if !ERRORLEVEL! equ 0 (
            echo        mklink 创建成功
        ) else (
            echo        [警告] mklink 也失败了，文件上传功能可能受限
        )
    )
)
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 12: 缓存优化
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 缓存优化"
echo [%STEP%/%TOTAL_STEPS%] 缓存优化...

cd /d "%PROJECT_DIR%"

"!PHP_EXE!" artisan config:cache --no-interaction >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        config:cache ...... OK
) else (
    echo        [警告] config:cache 失败，清除缓存作为回退
    "!PHP_EXE!" artisan config:clear --no-interaction >nul 2>&1
)

"!PHP_EXE!" artisan route:cache --no-interaction >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        route:cache ....... OK
) else (
    echo        [警告] route:cache 失败（可能存在闭包路由），已跳过
    "!PHP_EXE!" artisan route:clear --no-interaction >nul 2>&1
)

"!PHP_EXE!" artisan view:cache --no-interaction >nul 2>&1
echo        view:cache ........ OK
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 13: 配置日志管理
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 配置日志管理"
echo [%STEP%/%TOTAL_STEPS%] 配置日志管理...

REM Create a scheduled task to rotate logs weekly
REM Laravel daily log channel handles rotation itself, but we clean old logs
schtasks /create /tn "DentalClinic-LogCleanup" /tr "forfiles /p \"%PROJECT_DIR%\storage\logs\" /s /m *.log /d -30 /c \"cmd /c del @path\" 2>nul" /sc weekly /d MON /st 03:00 /ru SYSTEM /f >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        日志清理任务已创建（每周清理30天前的日志）
) else (
    echo [警告] 日志清理任务创建失败
)
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 14: OCR 环境安装 (可选)
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] OCR 环境"
echo [%STEP%/%TOTAL_STEPS%] OCR 环境配置...

if "%SKIP_OCR%"=="1" (
    echo        [跳过] --no-ocr 已指定
    goto :ocr_done
)
if not defined PYTHON_CMD (
    echo        [跳过] 未找到 Python 3，OCR 功能不可用
    echo        如需 OCR，请安装 Python 3.8+ 后重新运行
    goto :ocr_done
)

set "OCR_VENV=%PROJECT_DIR%\scripts\venv"
set "OCR_REQUIREMENTS=%PROJECT_DIR%\scripts\requirements.txt"
set "OCR_WHEELS_DIR=%INSTALL_DIR%\ocr-wheels"

REM 回退: 兼容历史打包结构
if not exist "%OCR_WHEELS_DIR%" (
    set "OCR_WHEELS_DIR=%PROJECT_DIR%\ocr-wheels"
)
if not exist "%OCR_WHEELS_DIR%" (
    set "OCR_WHEELS_DIR=%PROJECT_DIR%\scripts\wheels"
)

REM 创建虚拟环境
if not exist "%OCR_VENV%\Scripts\python.exe" (
    echo        创建 Python 虚拟环境...
    %PYTHON_CMD% -m venv "%OCR_VENV%"
    if !ERRORLEVEL! neq 0 (
        echo        [警告] venv 创建失败，跳过 OCR
        goto :ocr_done
    )
    echo        虚拟环境已创建
)

REM 安装依赖
if not exist "%OCR_REQUIREMENTS%" (
    echo        [警告] 未找到 scripts/requirements.txt，跳过 OCR 依赖安装
    goto :ocr_env_update
)

echo        安装 OCR 依赖包...
if exist "%OCR_WHEELS_DIR%" (
    echo        模式: 离线安装 (从 %OCR_WHEELS_DIR%)
    "%OCR_VENV%\Scripts\pip.exe" install --no-index --find-links="%OCR_WHEELS_DIR%" -r "%OCR_REQUIREMENTS%" -q 2>&1
    if !ERRORLEVEL! neq 0 (
        echo        [警告] 离线安装失败，尝试在线安装...
        "%OCR_VENV%\Scripts\pip.exe" install --upgrade pip -q 2>nul
        "%OCR_VENV%\Scripts\pip.exe" install -r "%OCR_REQUIREMENTS%" -q 2>&1
    )
) else (
    echo        模式: 在线安装
    "%OCR_VENV%\Scripts\pip.exe" install --upgrade pip -q 2>nul
    "%OCR_VENV%\Scripts\pip.exe" install -r "%OCR_REQUIREMENTS%" -q 2>&1
)

if %ERRORLEVEL% equ 0 (
    echo        OCR 依赖安装完成
) else (
    echo        [警告] OCR 依赖安装可能不完整
)

:ocr_env_update
REM 更新 .env 中 OCR_PYTHON_PATH
findstr /b "OCR_PYTHON_PATH=" "%ENV_TARGET%" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    "!PHP_EXE!" -r "
        $env = file_get_contents('%ENV_TARGET%');
        $env = preg_replace('/^OCR_PYTHON_PATH=.*/m', 'OCR_PYTHON_PATH=%OCR_VENV%\Scripts\python.exe', $env);
        file_put_contents('%ENV_TARGET%', $env);
    "
) else (
    echo.>> "%ENV_TARGET%"
    echo # OCR Service>> "%ENV_TARGET%"
    echo OCR_PYTHON_PATH=%OCR_VENV%\Scripts\python.exe>> "%ENV_TARGET%"
    echo OCR_TIMEOUT=300>> "%ENV_TARGET%"
    echo OCR_SERVER_URL=http://127.0.0.1:5000>> "%ENV_TARGET%"
)
echo        .env OCR 路径已更新

:ocr_done
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 15: 配置 Nginx
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 配置 Nginx"
echo [%STEP%/%TOTAL_STEPS%] 配置 Nginx...

if not defined NGINX_EXE (
    echo        [跳过] 未检测到 Nginx
    goto :nginx_done
)

REM 确保 sites-enabled 目录存在
if not exist "%NGINX_CONF_DIR%" (
    mkdir "%NGINX_CONF_DIR%" 2>nul
)

REM 将 Windows 路径转换为 Nginx 使用的正斜杠路径
set "NGINX_ROOT=%PROJECT_DIR%\public"
set "NGINX_ROOT_SLASH=%NGINX_ROOT:\=/%"

set "NGINX_CONF_FILE=%NGINX_CONF_DIR%\auto.dental.conf"

echo        生成 %NGINX_CONF_FILE%

REM 使用 PHP 写入 Nginx 配置（避免 batch 转义问题）
"!PHP_EXE!" -r "
    $root = '%NGINX_ROOT_SLASH%';
    $conf = 'server {' . PHP_EOL;
    $conf .= '    listen 80;' . PHP_EOL;
    $conf .= '    server_name localhost;' . PHP_EOL;
    $conf .= '    root \"' . $root . '\";' . PHP_EOL;
    $conf .= '' . PHP_EOL;
    $conf .= '    index index.php index.html;' . PHP_EOL;
    $conf .= '' . PHP_EOL;
    $conf .= '    location / {' . PHP_EOL;
    $conf .= '        try_files \$uri \$uri/ /index.php?\$query_string;' . PHP_EOL;
    $conf .= '    }' . PHP_EOL;
    $conf .= '' . PHP_EOL;
    $conf .= '    location ~ \.php\$ {' . PHP_EOL;
    $conf .= '        fastcgi_pass 127.0.0.1:9000;' . PHP_EOL;
    $conf .= '        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;' . PHP_EOL;
    $conf .= '        include fastcgi_params;' . PHP_EOL;
    $conf .= '    }' . PHP_EOL;
    $conf .= '' . PHP_EOL;
    $conf .= '    location ~ /\.ht {' . PHP_EOL;
    $conf .= '        deny all;' . PHP_EOL;
    $conf .= '    }' . PHP_EOL;
    $conf .= '' . PHP_EOL;
    $conf .= '    client_max_body_size 100M;' . PHP_EOL;
    $conf .= '}' . PHP_EOL;
    file_put_contents('%NGINX_CONF_FILE%', $conf);
"
if %ERRORLEVEL% equ 0 (
    echo        Nginx 配置已生成
    echo        Root: %NGINX_ROOT_SLASH%
) else (
    echo        [警告] Nginx 配置生成失败
)

REM Validate nginx config
"%NGINX_DIR%\nginx.exe" -t -c "%LARAGON_DIR%\etc\nginx\nginx.conf" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        Nginx 配置验证通过
) else (
    echo [警告] Nginx 配置验证失败，请手动检查
)

:nginx_done
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 16: 注册 Windows 服务 (可选)
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] Windows 服务注册"
echo [%STEP%/%TOTAL_STEPS%] 注册 Windows 服务 (MySQL 自启动)...

if "%SKIP_SERVICE%"=="1" (
    echo        [跳过] --no-service 已指定
    goto :service_done
)

if not defined MYSQLD_EXE (
    echo        [跳过] 未找到 mysqld.exe
    goto :service_done
)

set "SVC_NAME=DentalClinicMySQL"
set "MYSQL_INI=%LARAGON_DIR%\etc\mysql\my.ini"

REM 检查服务是否已存在
sc query "%SVC_NAME%" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        服务 %SVC_NAME% 已存在，跳过注册
    goto :service_done
)

REM 方式1: 尝试使用 NSSM（如果存在）
set "NSSM_EXE="
if exist "%INSTALL_DIR%\nssm.exe" set "NSSM_EXE=%INSTALL_DIR%\nssm.exe"
if exist "%LARAGON_DIR%\bin\nssm\nssm.exe" set "NSSM_EXE=%LARAGON_DIR%\bin\nssm\nssm.exe"
where nssm >nul 2>&1
if %ERRORLEVEL% equ 0 if not defined NSSM_EXE (
    for /f "tokens=*" %%P in ('where nssm 2^>nul') do set "NSSM_EXE=%%P"
)

if defined NSSM_EXE (
    echo        使用 NSSM 注册服务: %SVC_NAME%
    "!NSSM_EXE!" install "%SVC_NAME%" "!MYSQLD_EXE!" --defaults-file="!MYSQL_INI!" >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        "!NSSM_EXE!" set "%SVC_NAME%" DisplayName "DentalClinic MySQL" >nul 2>&1
        "!NSSM_EXE!" set "%SVC_NAME%" Description "牙科诊所管理系统 - MySQL 数据库服务" >nul 2>&1
        "!NSSM_EXE!" set "%SVC_NAME%" Start SERVICE_AUTO_START >nul 2>&1
        echo        服务 %SVC_NAME% 注册成功 (NSSM, 自动启动)
    ) else (
        echo        [警告] NSSM 注册服务失败
    )
    goto :service_done
)

REM 方式2: 使用 sc.exe
echo        使用 sc.exe 注册服务: %SVC_NAME%
if exist "!MYSQL_INI!" (
    sc create "%SVC_NAME%" binPath= "\"!MYSQLD_EXE!\" --defaults-file=\"!MYSQL_INI!\"" DisplayName= "DentalClinic MySQL" start= auto >nul 2>&1
) else (
    sc create "%SVC_NAME%" binPath= "\"!MYSQLD_EXE!\"" DisplayName= "DentalClinic MySQL" start= auto >nul 2>&1
)
if %ERRORLEVEL% equ 0 (
    sc description "%SVC_NAME%" "牙科诊所管理系统 - MySQL 数据库服务" >nul 2>&1
    echo        服务 %SVC_NAME% 注册成功 (sc.exe, 自动启动)
) else (
    echo        [警告] 服务注册失败 — MySQL 需要手动启动
    echo        可稍后手动运行: sc create %SVC_NAME% binPath= "!MYSQLD_EXE!"
)

:service_done
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 17: 设置 Windows 定时任务
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 设置 Windows 定时任务"
echo [%STEP%/%TOTAL_STEPS%] 设置 Laravel 定时任务...

if "%SKIP_SERVICE%"=="1" (
    echo        [跳过] --no-service 已指定
    goto :scheduler_done
)

REM Create scheduled task for artisan schedule:run (every minute)
schtasks /create /tn "DentalClinic-Scheduler" /tr "\"%PHP_EXE%\" \"%PROJECT_DIR%\artisan\" schedule:run >> \"%PROJECT_DIR%\storage\logs\scheduler.log\" 2>&1" /sc minute /mo 1 /ru SYSTEM /f >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        Laravel 定时任务已创建
) else (
    echo [警告] 定时任务创建失败，需要手动配置
)

REM Create scheduled task for queue worker (run at startup, restart on failure)
schtasks /create /tn "DentalClinic-QueueWorker" /tr "\"%PHP_EXE%\" \"%PROJECT_DIR%\artisan\" queue:work --sleep=3 --tries=3 --max-time=3600" /sc onstart /ru SYSTEM /f >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        队列处理器定时任务已创建
) else (
    echo [警告] 队列处理器任务创建失败
)

:scheduler_done
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  Step 18: 最终验证 ^& 重新缓存
REM ═══════════════════════════════════════════════════════════════════════
set /a STEP+=1
set "FAILED_STEP=[%STEP%/%TOTAL_STEPS%] 最终验证"
echo [%STEP%/%TOTAL_STEPS%] 最终验证...

cd /d "%PROJECT_DIR%"

REM OCR 步骤可能修改了 .env，重新缓存
"!PHP_EXE!" artisan config:cache --no-interaction >nul 2>&1

REM 验证 artisan 可以运行
"!PHP_EXE!" artisan --version >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        artisan 命令 ...... OK
) else (
    echo  [错误] artisan 命令执行失败，系统可能存在配置问题
    goto :error
)

REM 验证数据库连接
if "%DB_PASS%"=="" (
    "!MYSQL_EXE!" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -e "SELECT COUNT(*) FROM %DB_NAME%.users LIMIT 1" >nul 2>&1
) else (
    "!MYSQL_EXE!" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p"%DB_PASS%" -e "SELECT COUNT(*) FROM %DB_NAME%.users LIMIT 1" >nul 2>&1
)
if %ERRORLEVEL% equ 0 (
    echo        数据库连接 ........ OK
) else (
    echo        [警告] 数据库验证查询失败，请安装后手动检查
)

REM 验证路由加载
"!PHP_EXE!" artisan route:list --compact --no-interaction >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        路由加载 .......... OK
) else (
    echo        [警告] route:list 失败，部分功能可能不正常
)

echo        安装验证完成
echo.

REM ═══════════════════════════════════════════════════════════════════════
REM  安装成功
REM ═══════════════════════════════════════════════════════════════════════

echo.
echo  +=========================================================+
echo  |                                                          |
echo  |           安装完成！                                     |
echo  |                                                          |
echo  +=========================================================+
echo  |                                                          |
echo  |  系统版本:   v%APP_VERSION%
echo  |  安装目录:   %INSTALL_DIR%
echo  |  访问地址:   %APP_URL%
echo  |                                                          |
echo  |  ------------------------------------------------        |
echo  |                                                          |
echo  |  管理员账号: admin@example.com                           |
echo  |  管理员密码: password                                    |
echo  |                                                          |
echo  |  ** 首次登录后请立即修改密码 **                          |
echo  |                                                          |
echo  +=========================================================+
echo  |                                                          |
echo  |  启动方式:                                               |
echo  |    1. 双击桌面快捷方式 (如有)                            |
echo  |    2. 运行 %INSTALL_DIR%\laragon-startup.bat
echo  |    3. 打开 Laragon 面板 → Start All                      |
echo  |                                                          |
echo  +=========================================================+
echo.
if defined PYTHON_CMD if "%SKIP_OCR%"=="0" (
    echo  OCR 服务启动:
    echo    %PROJECT_DIR%\scripts\venv\Scripts\python.exe %PROJECT_DIR%\scripts\ocr_server.py
    echo.
)
echo  数据库连接信息:
echo    主机: %DB_HOST%:%DB_PORT%  数据库: %DB_NAME%  用户: %DB_USER%
echo.
goto :done

REM ═══════════════════════════════════════════════════════════════════════
REM  错误处理
REM ═══════════════════════════════════════════════════════════════════════
:error
echo.
echo  +=========================================================+
echo  |  安装出错！                                             |
echo  +=========================================================+
echo  |                                                          |
echo  |  失败步骤: %FAILED_STEP%
echo  |                                                          |
echo  |  排查建议:                                               |
echo  |    1. 检查以上错误提示信息                               |
echo  |    2. 确认 Laragon 目录结构完整                          |
echo  |    3. 若上次安装中断，请关闭 Laragon/MySQL/Nginx         |
echo  |       并清理 %INSTALL_DIR%\laragon 后重试                |
echo  |    4. 以管理员身份重新运行此脚本                         |
echo  |    5. 查看日志: %PROJECT_DIR%\storage\logs\
echo  |                                                          |
echo  |  修复问题后可重新运行此脚本（安装过程幂等安全）         |
echo  |                                                          |
echo  +=========================================================+
echo.

:done
endlocal
pause
