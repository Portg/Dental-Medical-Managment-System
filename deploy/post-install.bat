@echo off
chcp 65001 >nul 2>&1
title 牙科诊所管理系统 - 安装配置中...

REM ═══════════════════════════════════════════════════════════════
REM  牙科诊所管理系统 - 安装后自动配置脚本
REM  此脚本由安装程序自动调用，也可手动重新运行
REM ═══════════════════════════════════════════════════════════════

REM ── 定位路径 ──────────────────────────────────────────────────
set "INSTALL_DIR=%~dp0"
REM 去掉末尾反斜杠
if "%INSTALL_DIR:~-1%"=="\" set "INSTALL_DIR=%INSTALL_DIR:~0,-1%"

set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "PROJECT_DIR=%LARAGON_DIR%\www\dental"
set "PHP_DIR=%LARAGON_DIR%\bin\php\php-8.2"
set "MYSQL_DIR=%LARAGON_DIR%\bin\mysql\mysql-8.0"
set "NODE_DIR=%LARAGON_DIR%\bin\nodejs\node-18"
set "COMPOSER=%LARAGON_DIR%\bin\composer\composer.phar"

REM 自动查找实际的 PHP 目录（版本号可能不同）
if not exist "%PHP_DIR%" (
    for /d %%D in ("%LARAGON_DIR%\bin\php\php-8*") do set "PHP_DIR=%%D"
)
if not exist "%MYSQL_DIR%" (
    for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-8*") do set "MYSQL_DIR=%%D"
)
if not exist "%NODE_DIR%" (
    for /d %%D in ("%LARAGON_DIR%\bin\nodejs\node-*") do set "NODE_DIR=%%D"
)

set "PHP=%PHP_DIR%\php.exe"
set "MYSQL=%MYSQL_DIR%\bin\mysql.exe"
set "MYSQLD=%MYSQL_DIR%\bin\mysqld.exe"
set "NPM=%NODE_DIR%\npm.cmd"
set "PATH=%PHP_DIR%;%MYSQL_DIR%\bin;%NODE_DIR%;%LARAGON_DIR%\bin\composer;%PATH%"

echo.
echo  ╔══════════════════════════════════════════════╗
echo  ║     牙科诊所管理系统 - 正在安装配置...      ║
echo  ╚══════════════════════════════════════════════╝
echo.

REM ── Step 1: 环境检测 ─────────────────────────────────────────
echo [1/8] 检测运行环境...

if not exist "%PHP%" (
    echo [错误] 未找到 PHP，请确认 Laragon 已正确安装
    echo        期望路径: %PHP%
    goto :error
)
"%PHP%" -v 2>nul | findstr /i "PHP 8" >nul
if %ERRORLEVEL% neq 0 (
    echo [错误] PHP 版本需要 8.2+
    goto :error
)
echo        PHP .............. OK

if not exist "%MYSQL%" (
    echo [错误] 未找到 MySQL，请确认 Laragon 已正确安装
    goto :error
)
echo        MySQL ............ OK

if not exist "%NPM%" (
    echo [警告] 未找到 Node.js，跳过前端资源编译（不影响核心功能）
    set "SKIP_NPM=1"
) else (
    echo        Node.js .......... OK
)

if not exist "%COMPOSER%" (
    echo [错误] 未找到 Composer
    goto :error
)
echo        Composer ......... OK

if not exist "%PROJECT_DIR%\artisan" (
    echo [错误] 项目文件不完整，未找到 artisan
    goto :error
)
echo        项目文件 ......... OK
echo.

REM ── Step 2: 启动 MySQL ───────────────────────────────────────
echo [2/8] 启动 MySQL 服务...

REM 检查 MySQL 是否已运行
"%MYSQL%" -u root -e "SELECT 1" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo        MySQL 已在运行
) else (
    REM 使用 Laragon 的 MySQL 数据目录启动
    set "MYSQL_DATA=%MYSQL_DIR%\data"
    if not exist "%MYSQL_DATA%" set "MYSQL_DATA=%LARAGON_DIR%\data\mysql"

    start "" /b "%MYSQLD%" --defaults-file="%LARAGON_DIR%\etc\mysql\my.ini" --console >nul 2>&1
    echo        等待 MySQL 启动...

    REM 等待最多30秒
    set /a "WAIT=0"
    :wait_mysql
    timeout /t 2 /nobreak >nul
    "%MYSQL%" -u root -e "SELECT 1" >nul 2>&1
    if %ERRORLEVEL% equ 0 goto :mysql_ready
    set /a "WAIT+=2"
    if %WAIT% geq 30 (
        echo [错误] MySQL 启动超时，请手动启动 Laragon 后重新运行此脚本
        goto :error
    )
    goto :wait_mysql

    :mysql_ready
    echo        MySQL 启动成功
)
echo.

REM ── Step 3: 创建数据库 ───────────────────────────────────────
echo [3/8] 创建数据库...

"%MYSQL%" -u root -e "CREATE DATABASE IF NOT EXISTS pristine_dental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
if %ERRORLEVEL% neq 0 (
    echo [错误] 创建数据库失败
    goto :error
)
echo        数据库 pristine_dental 已就绪
echo.

REM ── Step 4: 配置 .env ────────────────────────────────────────
echo [4/8] 生成配置文件...

if not exist "%PROJECT_DIR%\.env" (
    copy "%PROJECT_DIR%\.env.example" "%PROJECT_DIR%\.env" >nul
    echo        .env 已生成
) else (
    echo        .env 已存在，跳过
)

REM 确保关键配置正确
"%PHP%" -r "
    $env = file_get_contents('%PROJECT_DIR%\.env');
    $env = preg_replace('/^APP_URL=.*/m', 'APP_URL=http://localhost/dental', $env);
    $env = preg_replace('/^APP_NAME=.*/m', 'APP_NAME=牙科诊所管理系统', $env);
    $env = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=pristine_dental', $env);
    $env = preg_replace('/^DB_USERNAME=.*/m', 'DB_USERNAME=root', $env);
    $env = preg_replace('/^DB_PASSWORD=.*/m', 'DB_PASSWORD=', $env);
    file_put_contents('%PROJECT_DIR%\.env', $env);
"
echo        配置已更新
echo.

REM ── Step 5: Composer 依赖 ────────────────────────────────────
echo [5/8] 安装 PHP 依赖（首次较慢，请耐心等待）...

cd /d "%PROJECT_DIR%"
"%PHP%" "%COMPOSER%" install --optimize-autoloader --no-dev --no-interaction 2>&1
if %ERRORLEVEL% neq 0 (
    echo [错误] Composer 安装失败
    goto :error
)
echo        PHP 依赖安装完成
echo.

REM ── Step 6: Laravel 初始化 ───────────────────────────────────
echo [6/8] 初始化 Laravel...

REM 生成 APP_KEY
"%PHP%" artisan key:generate --force --no-interaction
echo        APP_KEY 已生成

REM 创建 storage 软链接
"%PHP%" artisan storage:link --force --no-interaction 2>nul
echo        Storage 链接已创建

REM 数据库迁移
echo        正在创建数据库表...
"%PHP%" artisan migrate --force --no-interaction
if %ERRORLEVEL% neq 0 (
    echo [错误] 数据库迁移失败
    goto :error
)
echo        数据库表创建完成

REM 数据库填充
echo        正在初始化系统数据...
"%PHP%" artisan db:seed --force --no-interaction
if %ERRORLEVEL% neq 0 (
    echo [错误] 数据填充失败
    goto :error
)
echo        系统数据初始化完成
echo.

REM ── Step 7: 前端资源 ─────────────────────────────────────────
echo [7/8] 编译前端资源...

if defined SKIP_NPM (
    echo        [跳过] Node.js 未安装，使用已有的前端资源
) else (
    cd /d "%PROJECT_DIR%"
    call "%NPM%" install --no-audit --no-fund 2>&1
    if %ERRORLEVEL% neq 0 (
        echo [警告] npm install 出错，尝试继续...
    )
    call "%NPM%" run production 2>&1
    if %ERRORLEVEL% neq 0 (
        echo [警告] 前端编译出错，系统仍可使用但样式可能不完整
    ) else (
        echo        前端资源编译完成
    )
)
echo.

REM ── Step 8: 缓存优化 ─────────────────────────────────────────
echo [8/8] 优化缓存...

cd /d "%PROJECT_DIR%"
"%PHP%" artisan config:cache --no-interaction
"%PHP%" artisan route:cache --no-interaction
"%PHP%" artisan view:cache --no-interaction
echo        缓存优化完成
echo.

REM ── 完成 ──────────────────────────────────────────────────────
echo.
echo  ╔══════════════════════════════════════════════╗
echo  ║           安装配置完成！                     ║
echo  ╠══════════════════════════════════════════════╣
echo  ║                                              ║
echo  ║  访问地址: http://localhost/dental            ║
echo  ║                                              ║
echo  ║  管理员账号: admin@example.com               ║
echo  ║  管理员密码: password                        ║
echo  ║                                              ║
echo  ║  请使用桌面快捷方式启动系统                  ║
echo  ║  首次登录后请立即修改密码！                  ║
echo  ║                                              ║
echo  ╚══════════════════════════════════════════════╝
echo.
goto :done

:error
echo.
echo  ╔══════════════════════════════════════════════╗
echo  ║  安装出错！请检查以上错误信息               ║
echo  ║  修复问题后可重新运行此脚本                 ║
echo  ╚══════════════════════════════════════════════╝
echo.

:done
pause
