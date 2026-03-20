@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion
title 牙科诊所管理系统 - 升级工具

REM ═══════════════════════════════════════════════════════════════
REM  牙科诊所管理系统 - Windows 升级脚本
REM
REM  用法: upgrade-win.bat [安装目录]
REM        默认安装目录: C:\DentalClinic
REM
REM  升级包结构（本脚本所在目录即为升级包根目录）:
REM    deploy/upgrade-win.bat   ← 本脚本
REM    VERSION                  ← 新版本号
REM    app/                     ← 新代码
REM    config/                  ← 新配置
REM    database/                ← 新迁移文件
REM    ...
REM    deploy/env.patch         ← (可选) 新增环境变量
REM
REM  执行流程:
REM    1. 版本检查（拒绝降级，同版本警告）
REM    2. 自动备份（.env / 数据库 / 应用目录）
REM    3. 代码更新（保留 .env 和 storage/app/）
REM    4. 环境变量合并（env.patch → .env）
REM    5. 数据库迁移
REM    6. 缓存清理与重建
REM    7. 健康检查
REM    8. 失败自动回滚
REM ═══════════════════════════════════════════════════════════════

REM ── 参数解析 ────────────────────────────────────────────────────
set "INSTALL_DIR=%~1"
if "%INSTALL_DIR%"=="" set "INSTALL_DIR=C:\DentalClinic"
if "%INSTALL_DIR:~-1%"=="\" set "INSTALL_DIR=%INSTALL_DIR:~0,-1%"

REM 升级包所在目录（构建产物中脚本位于升级包根目录）
set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "UPGRADE_PKG_DIR=%SCRIPT_DIR%"

REM 关键路径
set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "PROJECT_DIR=%LARAGON_DIR%\www\dental"
set "BACKUP_BASE=%INSTALL_DIR%\backups"
set "ENV_PATCH=%UPGRADE_PKG_DIR%\env.patch"

REM 自动查找 PHP / MySQL（兼容不同 Laragon 版本的目录命名）
set "PHP_DIR="
set "MYSQL_DIR="
REM PHP: php-8* → php8* → php* → 直接 php.exe → 任意子目录
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

if defined PHP_DIR (
    set "PHP=%PHP_DIR%\php.exe"
) else (
    where php >nul 2>&1 && set "PHP=php"
)
if defined MYSQL_DIR (
    set "MYSQLDUMP=%MYSQL_DIR%\bin\mysqldump.exe"
    set "MYSQL=%MYSQL_DIR%\bin\mysql.exe"
) else (
    where mysqldump >nul 2>&1 && set "MYSQLDUMP=mysqldump"
    where mysql >nul 2>&1 && set "MYSQL=mysql"
)

set "COMPOSER=%LARAGON_DIR%\bin\composer\composer.phar"
if not exist "%COMPOSER%" (
    where composer >nul 2>&1 && set "COMPOSER=composer"
)

REM 添加到 PATH
if defined PHP_DIR set "PATH=%PHP_DIR%;%PATH%"
if defined MYSQL_DIR set "PATH=%MYSQL_DIR%\bin;%PATH%"

REM 时间戳（使用 PowerShell，避免 wmic 在 Windows 11 22H2+ 被弃用）
for /f %%T in ('powershell -NoProfile -Command "Get-Date -Format ''yyyyMMdd_HHmmss''" 2^>nul') do set "TIMESTAMP=%%T"
REM 兜底: 若 PowerShell 不可用则用 date/time 命令拼接
if not defined TIMESTAMP (
    for /f "tokens=1-3 delims=/-. " %%A in ("%date%") do set "DATEPART=%%A%%B%%C"
    for /f "tokens=1-3 delims=:., " %%A in ("%time%") do set "TIMEPART=%%A%%B%%C"
    set "TIMESTAMP=!DATEPART!_!TIMEPART!"
)

REM 回滚追踪
set "ROLLBACK_NEEDED=0"
set "BACKUP_DIR="
set "ENV_BACKUP_FILE="
set "DB_BACKUP_FILE="
set "FILES_BACKUP_DIR="
set "MAINTENANCE_MODE=0"
set "CURRENT_VERSION="
set "NEW_VERSION="

REM 数据库连接默认值
set "DB_NAME=pristine_dental"
set "DB_USER=root"
set "DB_PASS="
set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"

echo.
echo  +=========================================================+
echo  |         牙科诊所管理系统 - 升级工具                     |
echo  +=========================================================+
echo  |  安装目录:  %INSTALL_DIR%
echo  |  升级包:    %UPGRADE_PKG_DIR%
echo  +=========================================================+
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 1: 环境检测
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 1] 检测运行环境                                   |
echo  +----------------------------------------------------------+

if not exist "%INSTALL_DIR%" (
    echo  [错误] 安装目录不存在: %INSTALL_DIR%
    goto :abort_no_rollback
)
if not exist "%PROJECT_DIR%\artisan" (
    echo  [错误] 项目文件不完整，未找到 artisan
    echo         期望路径: %PROJECT_DIR%\artisan
    echo         如果这是首次安装，请运行 post-install.bat
    goto :abort_no_rollback
)
if not defined PHP (
    echo  [错误] 未找到 PHP，请确认 Laragon 已安装或 PHP 已加入 PATH
    goto :abort_no_rollback
)
if defined PHP_DIR (
    if not exist "!PHP!" (
        echo  [错误] PHP 不存在: !PHP!
        goto :abort_no_rollback
    )
)
echo        PHP ................. OK

if not defined MYSQLDUMP (
    echo  [警告] 未找到 mysqldump，将跳过数据库备份
    set "SKIP_DB_BACKUP=1"
) else (
    echo        mysqldump .......... OK
)
echo        环境检测通过
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 2: 版本检查
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 2] 读取当前版本                                   |
echo  +----------------------------------------------------------+

set "CURRENT_VERSION=0.0.0"
if exist "%PROJECT_DIR%\VERSION" (
    set /p CURRENT_VERSION=<"%PROJECT_DIR%\VERSION"
)
for /f "tokens=1" %%V in ("!CURRENT_VERSION!") do set "CURRENT_VERSION=%%V"
echo        当前版本: !CURRENT_VERSION!
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 3: 读取升级包版本 & 比较
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 3] 读取升级包版本并校验                           |
echo  +----------------------------------------------------------+

if not exist "%UPGRADE_PKG_DIR%\VERSION" (
    echo  [错误] 升级包中未找到 VERSION 文件
    echo         期望路径: %UPGRADE_PKG_DIR%\VERSION
    goto :abort_no_rollback
)

set "NEW_VERSION=0.0.0"
set /p NEW_VERSION=<"%UPGRADE_PKG_DIR%\VERSION"
for /f "tokens=1" %%V in ("!NEW_VERSION!") do set "NEW_VERSION=%%V"
echo        目标版本: !NEW_VERSION!

REM 解析版本号 MAJOR.MINOR.PATCH
for /f "tokens=1-3 delims=." %%A in ("!CURRENT_VERSION!") do (
    set "CUR_MAJOR=%%A" & set "CUR_MINOR=%%B" & set "CUR_PATCH=%%C"
)
for /f "tokens=1-3 delims=." %%A in ("!NEW_VERSION!") do (
    set "NEW_MAJOR=%%A" & set "NEW_MINOR=%%B" & set "NEW_PATCH=%%C"
)
if not defined CUR_MAJOR set "CUR_MAJOR=0"
if not defined CUR_MINOR set "CUR_MINOR=0"
if not defined CUR_PATCH set "CUR_PATCH=0"
if not defined NEW_MAJOR set "NEW_MAJOR=0"
if not defined NEW_MINOR set "NEW_MINOR=0"
if not defined NEW_PATCH set "NEW_PATCH=0"

REM 拒绝降级
set "IS_DOWNGRADE=0"
if !NEW_MAJOR! lss !CUR_MAJOR! set "IS_DOWNGRADE=1"
if !NEW_MAJOR! equ !CUR_MAJOR! if !NEW_MINOR! lss !CUR_MINOR! set "IS_DOWNGRADE=1"
if !NEW_MAJOR! equ !CUR_MAJOR! if !NEW_MINOR! equ !CUR_MINOR! if !NEW_PATCH! lss !CUR_PATCH! set "IS_DOWNGRADE=1"

if "!IS_DOWNGRADE!"=="1" (
    echo.
    echo  [错误] 拒绝降级: !CURRENT_VERSION! -^> !NEW_VERSION!
    echo         不支持从高版本降级到低版本。
    goto :abort_no_rollback
)

REM 同版本检查
set "IS_SAME=0"
if "!CURRENT_VERSION!"=="!NEW_VERSION!" set "IS_SAME=1"
if "!IS_SAME!"=="1" (
    echo.
    echo  [警告] 当前已是 !CURRENT_VERSION! 版本，无需升级。
    set /p "FORCE_UPGRADE=  是否强制重新安装? ^(y/N^): "
    if /i not "!FORCE_UPGRADE!"=="y" (
        echo  操作已取消。
        goto :done
    )
    echo        继续强制重新安装...
)

echo        版本校验通过: !CURRENT_VERSION! -^> !NEW_VERSION!
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 4: 自动备份
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 4] 自动备份                                       |
echo  +----------------------------------------------------------+

set "BACKUP_DIR=%BACKUP_BASE%\upgrade_%TIMESTAMP%"
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM 读取 .env 中的数据库连接信息
if exist "%PROJECT_DIR%\.env" (
    for /f "usebackq tokens=1,* delims==" %%A in ("%PROJECT_DIR%\.env") do (
        if "%%A"=="DB_DATABASE" set "DB_NAME=%%B"
        if "%%A"=="DB_USERNAME" set "DB_USER=%%B"
        if "%%A"=="DB_PASSWORD" set "DB_PASS=%%B"
        if "%%A"=="DB_HOST" set "DB_HOST=%%B"
        if "%%A"=="DB_PORT" set "DB_PORT=%%B"
    )
)

REM ── 4a: 备份 .env ──
echo     [4a] 备份 .env ...
set "ENV_BACKUP_FILE=%BACKUP_DIR%\.env.backup.%TIMESTAMP%"
if exist "%PROJECT_DIR%\.env" (
    copy "%PROJECT_DIR%\.env" "!ENV_BACKUP_FILE!" >nul
    if !ERRORLEVEL! neq 0 (
        echo  [错误] .env 备份失败
        goto :abort_no_rollback
    )
    echo          已备份到 !ENV_BACKUP_FILE!
) else (
    echo          [跳过] .env 不存在
)

REM ── 4b: 备份数据库 ──
echo     [4b] 备份数据库 ...
set "DB_BACKUP_FILE=%BACKUP_DIR%\backup_%TIMESTAMP%.sql"

if defined SKIP_DB_BACKUP (
    echo          [跳过] mysqldump 不可用
) else (
    echo          正在导出数据库 !DB_NAME!（可能需要几分钟）...
    if "!DB_PASS!"=="" (
        "!MYSQLDUMP!" -h !DB_HOST! -P !DB_PORT! -u !DB_USER! --single-transaction --routines --triggers "!DB_NAME!" > "!DB_BACKUP_FILE!" 2>nul
    ) else (
        "!MYSQLDUMP!" -h !DB_HOST! -P !DB_PORT! -u !DB_USER! -p"!DB_PASS!" --single-transaction --routines --triggers "!DB_NAME!" > "!DB_BACKUP_FILE!" 2>nul
    )
    if !ERRORLEVEL! neq 0 (
        echo  [错误] 数据库备份失败，请确认 MySQL 已启动
        del "!DB_BACKUP_FILE!" >nul 2>&1
        goto :abort_no_rollback
    )
    REM 检查备份文件非空
    for %%F in ("!DB_BACKUP_FILE!") do set "BACKUP_SIZE=%%~zF"
    if "!BACKUP_SIZE!"=="0" (
        echo  [错误] 数据库备份文件为空
        del "!DB_BACKUP_FILE!" >nul 2>&1
        goto :abort_no_rollback
    )
    echo          数据库已备份 (!BACKUP_SIZE! 字节^)
)

REM ── 4c: 备份应用目录 ──
echo     [4c] 备份应用目录（可能需要几分钟）...
set "FILES_BACKUP_DIR=%BACKUP_DIR%\app_backup"
if not exist "!FILES_BACKUP_DIR!" mkdir "!FILES_BACKUP_DIR!"

REM 排除大目录以加快速度
echo vendor\> "%BACKUP_DIR%\_exclude.txt"
echo node_modules\>> "%BACKUP_DIR%\_exclude.txt"
echo .git\>> "%BACKUP_DIR%\_exclude.txt"

xcopy "%PROJECT_DIR%\*" "!FILES_BACKUP_DIR!\" /E /H /Y /Q /EXCLUDE:%BACKUP_DIR%\_exclude.txt >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo  [错误] 应用目录备份失败
    del "%BACKUP_DIR%\_exclude.txt" >nul 2>&1
    goto :abort_no_rollback
)
del "%BACKUP_DIR%\_exclude.txt" >nul 2>&1
echo          应用目录已备份

echo     备份全部完成
echo.

REM 从此处开始，失败需要回滚
set "ROLLBACK_NEEDED=1"

REM ═══════════════════════════════════════════════════════════════
REM  Step 5: 进入维护模式
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 5] 进入维护模式                                   |
echo  +----------------------------------------------------------+

cd /d "%PROJECT_DIR%"
"!PHP!" artisan down --refresh=30 2>nul
if !ERRORLEVEL! equ 0 (
    set "MAINTENANCE_MODE=1"
    echo        应用已进入维护模式
) else (
    echo        [警告] 无法进入维护模式，继续升级
)
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 6: 代码更新
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 6] 更新代码文件                                   |
echo  +----------------------------------------------------------+

REM 保留 .env 和 storage/app/ — 先暂存
set "TEMP_PRESERVE=%BACKUP_DIR%\_temp_preserve"
if not exist "%TEMP_PRESERVE%" mkdir "%TEMP_PRESERVE%"

if exist "%PROJECT_DIR%\.env" (
    copy "%PROJECT_DIR%\.env" "%TEMP_PRESERVE%\.env" >nul
)
if exist "%PROJECT_DIR%\storage\app" (
    if not exist "%TEMP_PRESERVE%\storage\app" mkdir "%TEMP_PRESERVE%\storage\app"
    xcopy "%PROJECT_DIR%\storage\app\*" "%TEMP_PRESERVE%\storage\app\" /E /H /Y /Q >nul 2>&1
)

REM 复制新代码 — 排除不应覆盖的内容
echo .env> "%BACKUP_DIR%\_copy_exclude.txt"
echo storage\app\>> "%BACKUP_DIR%\_copy_exclude.txt"
echo storage\logs\>> "%BACKUP_DIR%\_copy_exclude.txt"
echo vendor\>> "%BACKUP_DIR%\_copy_exclude.txt"
echo node_modules\>> "%BACKUP_DIR%\_copy_exclude.txt"
echo .git\>> "%BACKUP_DIR%\_copy_exclude.txt"

echo        复制升级包文件到项目目录...
xcopy "%UPGRADE_PKG_DIR%\*" "%PROJECT_DIR%\" /E /H /Y /Q /EXCLUDE:%BACKUP_DIR%\_copy_exclude.txt >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo  [错误] 代码文件复制失败
    del "%BACKUP_DIR%\_copy_exclude.txt" >nul 2>&1
    goto :rollback
)
del "%BACKUP_DIR%\_copy_exclude.txt" >nul 2>&1

REM 恢复保留的文件
if exist "%TEMP_PRESERVE%\.env" (
    copy "%TEMP_PRESERVE%\.env" "%PROJECT_DIR%\.env" >nul
)
if exist "%TEMP_PRESERVE%\storage\app" (
    xcopy "%TEMP_PRESERVE%\storage\app\*" "%PROJECT_DIR%\storage\app\" /E /H /Y /Q >nul 2>&1
)
rmdir /s /q "%TEMP_PRESERVE%" >nul 2>&1

echo        代码文件更新完成
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 7: 环境变量合并
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 7] 合并环境变量                                   |
echo  +----------------------------------------------------------+

if exist "!ENV_PATCH!" (
    echo        发现 env.patch，合并新配置项...
    REM 使用 PHP 安全地合并 — 只添加缺失的 key，不覆盖已有值
    call :merge_env_patch
    if !ERRORLEVEL! neq 0 (
        echo  [错误] 环境变量合并失败
        goto :rollback
    )
) else (
    echo        未发现 env.patch，跳过

    REM 兜底: 检查新版 .env.example 是否有新 key
    if exist "%PROJECT_DIR%\.env.example" (
        echo        检查 .env.example 中的新配置项...
        call :merge_env_example
        if !ERRORLEVEL! neq 0 (
            echo  [错误] .env.example 配置项检查失败
            goto :rollback
        )
    )
)
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 8: 数据库迁移
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 8] 安装依赖 ^& 数据库迁移                          |
echo  +----------------------------------------------------------+

cd /d "%PROJECT_DIR%"

REM Composer install
if defined COMPOSER (
    echo        安装 PHP 依赖...
    echo "!COMPOSER!" | findstr /i ".phar" >nul
    if !ERRORLEVEL! equ 0 (
        "!PHP!" "!COMPOSER!" install --no-dev --optimize-autoloader --no-interaction 2>&1
    ) else (
        "!COMPOSER!" install --no-dev --optimize-autoloader --no-interaction 2>&1
    )
    if !ERRORLEVEL! neq 0 (
        echo  [错误] Composer 依赖安装失败
        goto :rollback
    )
    echo        PHP 依赖安装完成
) else (
    echo        [警告] Composer 不可用，跳过依赖安装
)

echo        运行数据库迁移...
"!PHP!" artisan migrate --force --no-interaction
if !ERRORLEVEL! neq 0 (
    echo  [错误] 数据库迁移失败
    goto :rollback
)
echo        数据库迁移完成
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 9: 缓存清理与重建
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 9] 缓存清理与重建                                 |
echo  +----------------------------------------------------------+

cd /d "%PROJECT_DIR%"

echo        清理旧缓存...
"!PHP!" artisan config:clear --no-interaction >nul 2>&1
"!PHP!" artisan route:clear --no-interaction >nul 2>&1
"!PHP!" artisan view:clear --no-interaction >nul 2>&1
"!PHP!" artisan cache:clear --no-interaction >nul 2>&1
echo        旧缓存已清除

echo        重建缓存...
"!PHP!" artisan config:cache --no-interaction >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo  [错误] config:cache 失败
    goto :rollback
)
echo        config:cache ...... OK

"!PHP!" artisan route:cache --no-interaction >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo        [警告] route:cache 失败（可能存在闭包路由），跳过
) else (
    echo        route:cache ....... OK
)

"!PHP!" artisan view:cache --no-interaction >nul 2>&1
echo        view:cache ........ OK

REM 重建 storage 软链接
"!PHP!" artisan storage:link --force --no-interaction >nul 2>&1

echo        缓存重建完成
echo.

REM ═══════════════════════════════════════════════════════════════
REM  Step 10: 健康检查 & 退出维护模式
REM ═══════════════════════════════════════════════════════════════
echo  +----------------------------------------------------------+
echo  | [Step 10] 健康检查                                      |
echo  +----------------------------------------------------------+

cd /d "%PROJECT_DIR%"

REM 检查 artisan 基本功能
"!PHP!" artisan --version >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo  [错误] php artisan 命令执行失败，系统可能已损坏
    goto :rollback
)
echo        artisan 命令 ...... OK

REM 路由加载检查
"!PHP!" artisan route:list --compact --no-interaction >nul 2>&1
if !ERRORLEVEL! neq 0 (
    echo  [错误] 路由加载失败，应用可能无法正常运行
    goto :rollback
)
echo        路由加载 .......... OK

REM 数据库连接检查
if defined MYSQL (
    if "!DB_PASS!"=="" (
        "!MYSQL!" -h !DB_HOST! -P !DB_PORT! -u !DB_USER! -e "SELECT COUNT(*) FROM !DB_NAME!.users LIMIT 1" >nul 2>&1
    ) else (
        "!MYSQL!" -h !DB_HOST! -P !DB_PORT! -u !DB_USER! -p"!DB_PASS!" -e "SELECT COUNT(*) FROM !DB_NAME!.users LIMIT 1" >nul 2>&1
    )
    if !ERRORLEVEL! equ 0 (
        echo        数据库连接 ........ OK
    ) else (
        echo        [警告] 数据库查询失败，请手动确认
    )
)

REM 确认新版本号
set "VERIFY_VER=unknown"
if exist "%PROJECT_DIR%\VERSION" (
    set /p VERIFY_VER=<"%PROJECT_DIR%\VERSION"
    for /f "tokens=1" %%V in ("!VERIFY_VER!") do set "VERIFY_VER=%%V"
)
echo        已安装版本 ........ !VERIFY_VER!

echo        健康检查通过
echo.

REM ── 退出维护模式 ──
if "!MAINTENANCE_MODE!"=="1" (
    "!PHP!" artisan up 2>nul
    echo        应用已退出维护模式
    echo.
)

REM ═══════════════════════════════════════════════════════════════
REM  升级成功
REM ═══════════════════════════════════════════════════════════════
echo.
echo  +=========================================================+
echo  |                    升级成功！                            |
echo  +=========================================================+
echo  |                                                         |
echo  |  版本变更: !CURRENT_VERSION! → !NEW_VERSION!
echo  |                                                         |
echo  |  备份位置: %BACKUP_DIR%
echo  |    .env:     !ENV_BACKUP_FILE!
echo  |    数据库:   !DB_BACKUP_FILE!
echo  |    应用文件: !FILES_BACKUP_DIR!
echo  |                                                         |
echo  |  如需回滚，请手动恢复备份文件:                          |
echo  |    1. 恢复应用文件                                      |
echo  |    2. 恢复 .env                                         |
echo  |    3. mysql !DB_NAME! ^< backup_*.sql                    |
echo  |                                                         |
echo  +=========================================================+
echo.
goto :done

REM ═══════════════════════════════════════════════════════════════
REM  自动回滚
REM ═══════════════════════════════════════════════════════════════
:rollback
echo.
echo  +=========================================================+
echo  |  [错误] 升级失败！正在自动回滚...                       |
echo  +=========================================================+
echo.

if "!ROLLBACK_NEEDED!"=="0" goto :abort_no_rollback

REM ── 回滚代码文件 ──
if defined FILES_BACKUP_DIR (
    if exist "!FILES_BACKUP_DIR!" (
        echo  [回滚] 恢复应用文件...
        echo vendor\> "%BACKUP_DIR%\_rb_exclude.txt"
        echo node_modules\>> "%BACKUP_DIR%\_rb_exclude.txt"
        echo .git\>> "%BACKUP_DIR%\_rb_exclude.txt"
        xcopy "!FILES_BACKUP_DIR!\*" "%PROJECT_DIR%\" /E /H /Y /Q /EXCLUDE:%BACKUP_DIR%\_rb_exclude.txt >nul 2>&1
        del "%BACKUP_DIR%\_rb_exclude.txt" >nul 2>&1
        echo          应用文件已恢复
    )
)

REM ── 回滚 .env ──
if defined ENV_BACKUP_FILE (
    if exist "!ENV_BACKUP_FILE!" (
        echo  [回滚] 恢复 .env...
        copy "!ENV_BACKUP_FILE!" "%PROJECT_DIR%\.env" >nul
        echo          .env 已恢复
    )
)

REM ── 回滚数据库 ──
if defined DB_BACKUP_FILE (
    if exist "!DB_BACKUP_FILE!" (
        echo  [回滚] 恢复数据库（可能需要几分钟）...
        if defined MYSQL (
            if "!DB_PASS!"=="" (
                "!MYSQL!" -h !DB_HOST! -P !DB_PORT! -u !DB_USER! "!DB_NAME!" < "!DB_BACKUP_FILE!" 2>nul
            ) else (
                "!MYSQL!" -h !DB_HOST! -P !DB_PORT! -u !DB_USER! -p"!DB_PASS!" "!DB_NAME!" < "!DB_BACKUP_FILE!" 2>nul
            )
            if !ERRORLEVEL! equ 0 (
                echo          数据库已恢复
            ) else (
                echo  [严重] 数据库自动恢复失败！请手动导入:
                echo          !DB_BACKUP_FILE!
            )
        ) else (
            echo          [跳过] mysql 不可用，请手动导入: !DB_BACKUP_FILE!
        )
    )
)

REM ── 回滚后重装依赖 & 重建缓存 ──
echo  [回滚] 重建环境...
cd /d "%PROJECT_DIR%"
if defined COMPOSER (
    echo "!COMPOSER!" | findstr /i ".phar" >nul
    if !ERRORLEVEL! equ 0 (
        "!PHP!" "!COMPOSER!" install --no-dev --optimize-autoloader --no-interaction >nul 2>&1
    ) else (
        "!COMPOSER!" install --no-dev --optimize-autoloader --no-interaction >nul 2>&1
    )
)
"!PHP!" artisan config:clear --no-interaction >nul 2>&1
"!PHP!" artisan route:clear --no-interaction >nul 2>&1
"!PHP!" artisan view:clear --no-interaction >nul 2>&1
"!PHP!" artisan config:cache --no-interaction >nul 2>&1
"!PHP!" artisan route:cache --no-interaction >nul 2>&1

REM ── 退出维护模式 ──
if "!MAINTENANCE_MODE!"=="1" (
    "!PHP!" artisan up 2>nul
)

echo.
echo  +=========================================================+
echo  |  回滚完成 -- 系统已恢复到升级前状态                     |
echo  +=========================================================+
echo  |  版本: !CURRENT_VERSION!
echo  |  备份保留在: %BACKUP_DIR%
echo  |                                                         |
echo  |  请检查错误信息后重新尝试升级                           |
echo  +=========================================================+
echo.
goto :done

:merge_env_patch
"!PHP!" -r "$envFile='%PROJECT_DIR%\.env';$patchFile='!ENV_PATCH!';if(!file_exists($envFile)||!file_exists($patchFile))exit(1);$env=file_get_contents($envFile);$patch=file_get_contents($patchFile);$added=0;foreach(explode(PHP_EOL,$patch) as $line){$line=trim($line);if($line===''||$line[0]==='#')continue;$parts=explode('=',$line,2);if(count($parts)<2)continue;$key=trim($parts[0]);if(!preg_match('/^'.preg_quote($key,'/').'=/m',$env)){$env.=PHP_EOL.$line;$added++;echo '          + '.$key.PHP_EOL;}}file_put_contents($envFile,$env);echo '        合并完成，新增 '.$added.' 个配置项'.PHP_EOL;"
exit /b %ERRORLEVEL%

:merge_env_example
"!PHP!" -r "$envFile='%PROJECT_DIR%\.env';$exampleFile='%PROJECT_DIR%\.env.example';if(!file_exists($envFile)||!file_exists($exampleFile))exit(0);$env=file_get_contents($envFile);$example=file_get_contents($exampleFile);$added=0;foreach(explode(PHP_EOL,$example) as $line){$line=trim($line);if($line===''||$line[0]==='#')continue;$parts=explode('=',$line,2);if(count($parts)<2)continue;$key=trim($parts[0]);if(!preg_match('/^'.preg_quote($key,'/').'=/m',$env)){$env.=PHP_EOL.$line;$added++;echo '          + '.$key.PHP_EOL;}}if($added>0)file_put_contents($envFile,$env);echo '        从 .env.example 新增 '.$added.' 项'.PHP_EOL;"
exit /b %ERRORLEVEL%

:abort_no_rollback
echo.
echo  +=========================================================+
echo  |  升级中止 -- 未进行任何修改                             |
echo  |  请检查以上错误信息后重试                               |
echo  +=========================================================+
echo.

:done
endlocal
pause
