@echo off
REM build.sh 会把本文件转成 GBK(CP936) + CRLF 再打包，因此这里必须是 936；
REM 用 65001 会让后续 GBK 字节被当成 UTF-8 解析，中文全部变乱码。
chcp 936 >nul 2>&1
setlocal enabledelayedexpansion
title 牙科诊所管理系统 - 卸载

REM ═══════════════════════════════════════════════════════════════
REM  牙科诊所管理系统 - Windows 卸载脚本
REM  用途: 停止服务 → 可选备份 → 删除数据库 → 移除服务/计划任务 → 删除文件
REM  用法:
REM    uninstall-win.bat                        交互式卸载
REM    uninstall-win.bat --keep-data            保留数据库和上传文件
REM    uninstall-win.bat --yes                  跳过确认提示
REM    uninstall-win.bat --cleanup-only         只清运行时产物，不删文件（供 Inno 卸载器调用）
REM
REM  非交互约定：--yes / --cleanup-only 一律不得出现 pause 或 set /p。
REM  Inno 的 [UninstallRun] 用 runhidden waituntilterminated 调用本脚本，
REM  窗口不可见，任何等待输入的语句都会让卸载永久挂起。
REM ═══════════════════════════════════════════════════════════════

set "KEEP_DATA=0"
set "AUTO_YES=0"
set "CLEANUP_ONLY=0"
set "NO_PAUSE=0"
set "SCRIPT_DIR=%~dp0"

REM ── 参数解析 ────────────────────────────────────────────────────
:parse_args
if "%~1"=="" goto :args_done
if /i "%~1"=="--keep-data"    ( set "KEEP_DATA=1" & shift & goto :parse_args )
if /i "%~1"=="--yes"          ( set "AUTO_YES=1"  & set "NO_PAUSE=1" & shift & goto :parse_args )
if /i "%~1"=="-y"             ( set "AUTO_YES=1"  & set "NO_PAUSE=1" & shift & goto :parse_args )
if /i "%~1"=="--cleanup-only" ( set "CLEANUP_ONLY=1" & set "AUTO_YES=1" & set "NO_PAUSE=1" & shift & goto :parse_args )
if /i "%~1"=="--help"         ( goto :show_help )
if /i "%~1"=="-h"             ( goto :show_help )
shift
goto :parse_args
:args_done

echo.
echo  +=====================================================+
echo  ^|       牙科诊所管理系统 - 卸载程序                   ^|
echo  +=====================================================+
echo.

REM ── 检测安装目录 ────────────────────────────────────────────────
REM 优先检查脚本所在目录是否就是安装目录
set "INSTALL_DIR="

REM 检查是否从安装目录内运行
if exist "%SCRIPT_DIR%laragon\www\dental\artisan" (
    set "INSTALL_DIR=%SCRIPT_DIR:~0,-1%"
    goto :dir_found
)
REM 检查默认路径
if exist "C:\DentalClinic\laragon\www\dental\artisan" (
    set "INSTALL_DIR=C:\DentalClinic"
    goto :dir_found
)

echo  [!] 未找到安装目录。
echo      请在安装目录下运行此脚本，或确认系统已安装。
echo.
call :maybe_pause
exit /b 1

:dir_found
set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "PROJECT_DIR=%LARAGON_DIR%\www\dental"
set "EXTERNAL_MYSQL=0"
if exist "%INSTALL_DIR%\existing-mysql.conf" set "EXTERNAL_MYSQL=1"
set "APP_DB_HOST=127.0.0.1"
set "APP_DB_PORT=3306"
set "APP_DB_NAME=pristine_dental"
set "APP_DB_USER=root"
set "APP_DB_PASS="
if exist "%PROJECT_DIR%\.env" (
    for /f "usebackq tokens=1,* delims==" %%A in ("%PROJECT_DIR%\.env") do (
        if /i "%%A"=="DB_HOST" set "APP_DB_HOST=%%B"
        if /i "%%A"=="DB_PORT" set "APP_DB_PORT=%%B"
        if /i "%%A"=="DB_DATABASE" set "APP_DB_NAME=%%B"
        if /i "%%A"=="DB_USERNAME" set "APP_DB_USER=%%B"
        if /i "%%A"=="DB_PASSWORD" set "APP_DB_PASS=%%B"
    )
)

echo  安装目录:  %INSTALL_DIR%
echo  项目目录:  %PROJECT_DIR%
echo.

REM ── 确认卸载 ────────────────────────────────────────────────────
if "%AUTO_YES%"=="1" goto :confirmed

echo  +=====================================================+
echo  ^|  警告: 卸载将执行以下操作:                          ^|
echo  ^|                                                      ^|
echo  ^|  1. 停止所有相关服务                                 ^|
if "%KEEP_DATA%"=="0" (
if "%EXTERNAL_MYSQL%"=="1" (
echo  ^|  2. 保留目标机现有 MySQL 和 pristine_dental          ^|
) else (
echo  ^|  2. 删除数据库 pristine_dental 及数据库用户          ^|
)
echo  ^|  3. 移除 Windows 服务和计划任务                      ^|
echo  ^|  4. 删除安装目录下的所有文件                         ^|
) else (
echo  ^|  2. 移除 Windows 服务和计划任务                      ^|
echo  ^|  3. 删除安装目录（保留数据库和上传文件备份）         ^|
)
echo  ^|                                                      ^|
echo  ^|  此操作不可恢复！                                    ^|
echo  +=====================================================+
echo.
set /p "CONFIRM=  确认卸载？输入 YES 继续: "
if /i not "!CONFIRM!"=="YES" (
    echo.
    echo  已取消卸载。
    call :maybe_pause
    exit /b 0
)
:confirmed

REM 步骤: 1 停服务 / 2 备份或删库 / 3 移除服务与计划任务 / 4 删除安装目录
REM --cleanup-only 不做第 4 步（文件删除交给 Inno 卸载器）
set "TOTAL_STEPS=4"
if "%CLEANUP_ONLY%"=="1" set "TOTAL_STEPS=3"

REM ═══════════════════════════════════════════════════════════════
REM  Step 1: 停止所有服务
REM ═══════════════════════════════════════════════════════════════
echo.
echo  [1/%TOTAL_STEPS%] 停止所有服务...

REM 调用 stop 脚本（如果存在）
set "DO_MANUAL_KILL=0"
if exist "%INSTALL_DIR%\stop-win.bat" (
    call "%INSTALL_DIR%\stop-win.bat" >nul 2>&1
    echo        通过 stop-win.bat 停止服务                      [OK]
) else (
    set "DO_MANUAL_KILL=1"
)

if "!DO_MANUAL_KILL!"=="1" (
    echo        停止队列工作进程...
    for /f "tokens=2" %%P in ('wmic process where "commandline like '%%queue:work%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
        taskkill /PID %%P /F >nul 2>&1
    )
    echo        停止 OCR 服务...
    for /f "tokens=2" %%P in ('wmic process where "commandline like '%%ocr_server%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
        taskkill /PID %%P /F >nul 2>&1
    )
    echo        停止 Nginx（仅本安装目录）...
    call :kill_by_cmdline nginx.exe "%INSTALL_DIR%"
    echo        停止 PHP-CGI（仅本安装目录）...
    call :kill_by_cmdline php-cgi.exe "%INSTALL_DIR%"
)

REM 停止 Apache 服务（xampp 形态）。只动本系统注册的这一个实例。
if exist "%INSTALL_DIR%\xampp\apache\bin\httpd.exe" (
    echo        停止 Apache 服务 (DentalClinicApache)...
    net stop DentalClinicApache >nul 2>&1
    echo        服务已停止                                          [OK]
)

REM 停止 MySQL 服务
if "%EXTERNAL_MYSQL%"=="1" (
    echo        现有 MySQL 由目标机管理、保持运行             [跳过]
) else (
    echo        停止 MySQL 服务 (DentalClinicMySQL)...
    net stop DentalClinicMySQL >nul 2>&1
    echo        服务已停止                                          [OK]
)

REM ═══════════════════════════════════════════════════════════════
REM  Step 2: 备份数据（可选）
REM ═══════════════════════════════════════════════════════════════
if "%KEEP_DATA%"=="1" (
    echo.
    echo  [2/%TOTAL_STEPS%] 备份用户数据...

    set "BACKUP_DIR=%USERPROFILE%\Desktop\dental-backup-%DATE:~0,4%%DATE:~5,2%%DATE:~8,2%"
    mkdir "!BACKUP_DIR!" >nul 2>&1

    REM 备份上传文件
    if exist "%PROJECT_DIR%\storage\app\public" (
        xcopy /E /I /Q "%PROJECT_DIR%\storage\app\public" "!BACKUP_DIR!\uploads" >nul 2>&1
        echo        已备份上传文件到 !BACKUP_DIR!\uploads       [OK]
    )

    REM 备份 .env
    if exist "%PROJECT_DIR%\.env" (
        copy /Y "%PROJECT_DIR%\.env" "!BACKUP_DIR!\.env" >nul 2>&1
        echo        已备份 .env 配置                             [OK]
    )

    REM 导出数据库
    set "MYSQLDUMP_EXE="
    for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do (
        if exist "%%D\bin\mysqldump.exe" set "MYSQLDUMP_EXE=%%D\bin\mysqldump.exe"
    )
    if not defined MYSQLDUMP_EXE for /d %%D in ("%LARAGON_DIR%\bin\mysql\*") do (
        if exist "%%D\bin\mysqldump.exe" set "MYSQLDUMP_EXE=%%D\bin\mysqldump.exe"
    )
    if defined MYSQLDUMP_EXE (
        if "%EXTERNAL_MYSQL%"=="0" net start DentalClinicMySQL >nul 2>&1
        echo        正在导出数据库...
        set "MYSQL_PWD=!APP_DB_PASS!"
        "!MYSQLDUMP_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! !APP_DB_NAME! > "!BACKUP_DIR!\pristine_dental.sql" 2>nul
        if !ERRORLEVEL! equ 0 (
            echo        已备份数据库到 !BACKUP_DIR!\pristine_dental.sql [OK]
        ) else (
            echo        [!] 数据库导出失败，请手动备份
        )
        set "MYSQL_PWD="
        if "%EXTERNAL_MYSQL%"=="0" net stop DentalClinicMySQL >nul 2>&1
    )

    echo        备份目录: !BACKUP_DIR!
    goto :skip_drop_db
)

REM ═══════════════════════════════════════════════════════════════
REM  Step 2: 删除数据库和用户
if "%EXTERNAL_MYSQL%"=="1" (
    echo.
    echo  [2/%TOTAL_STEPS%] 保留现有 MySQL 数据...
    echo        pristine_dental 不由卸载脚本删除             [跳过]
    goto :skip_drop_db
)
REM ═══════════════════════════════════════════════════════════════
echo.
echo  [2/%TOTAL_STEPS%] 删除数据库和用户...

REM 查找 MySQL 客户端
set "MYSQL_EXE="
for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do (
    if exist "%%D\bin\mysql.exe" set "MYSQL_EXE=%%D\bin\mysql.exe"
)
if not defined MYSQL_EXE for /d %%D in ("%LARAGON_DIR%\bin\mysql\*") do (
    if exist "%%D\bin\mysql.exe" set "MYSQL_EXE=%%D\bin\mysql.exe"
)

if defined MYSQL_EXE (
    REM 只启动本系统的 DentalClinicMySQL；如果服务未注册，再直接启动随包实例。
    net start DentalClinicMySQL >nul 2>&1
    timeout /t 3 /nobreak >nul
    set "MYSQL_PWD=!APP_DB_PASS!"
    "!MYSQL_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! -e "SELECT 1" >nul 2>&1
    set "MYSQL_READY_RC=!ERRORLEVEL!"

    set "MYSQLD_EXE="
    for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do (
        if exist "%%D\bin\mysqld.exe" set "MYSQLD_EXE=%%D\bin\mysqld.exe"
    )
    if not defined MYSQLD_EXE for /d %%D in ("%LARAGON_DIR%\bin\mysql\*") do (
        if exist "%%D\bin\mysqld.exe" set "MYSQLD_EXE=%%D\bin\mysqld.exe"
    )
    if not "!MYSQL_READY_RC!"=="0" if defined MYSQLD_EXE (
        echo        临时启动 MySQL 以删除数据库...
        REM 随包的运行时是自组装的，laragon-core 里没有 etc\mysql\my.ini，
        REM 装机时也不生成。硬传 --defaults-file 指向不存在的文件，mysqld
        REM 起不来，后面的 DROP DATABASE 就静默失败 —— 库其实没删掉。
        REM 有 my.ini 就用，没有就退回显式 basedir/datadir（与 install-win.ps1 一致）。
        set "MYSQL_INI=%LARAGON_DIR%\etc\mysql\my.ini"
        if exist "!MYSQL_INI!" (
            start /B "" "!MYSQLD_EXE!" --defaults-file="!MYSQL_INI!" >nul 2>&1
        ) else (
            for %%B in ("!MYSQLD_EXE!\..\..") do set "MYSQL_BASEDIR=%%~fB"
            start /B "" "!MYSQLD_EXE!" --basedir="!MYSQL_BASEDIR!" --datadir="%LARAGON_DIR%\data\mysql" >nul 2>&1
        )
        timeout /t 5 /nobreak >nul
    )

    echo        删除数据库 !APP_DB_NAME!...
    "!MYSQL_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! -e "DROP DATABASE IF EXISTS `!APP_DB_NAME!`;" 2>nul
    if !ERRORLEVEL! equ 0 (
        echo        数据库已删除                                    [OK]
    ) else (
        echo        [!] 数据库删除失败（可能已不存在）
    )

    echo        删除数据库用户 dental...
    "!MYSQL_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! -e "DROP USER IF EXISTS 'dental'@'localhost';" 2>nul
    echo        数据库用户已清理                                [OK]

    REM 再次关闭临时 MySQL
    "!MYSQL_EXE!" -h !APP_DB_HOST! -P !APP_DB_PORT! -u !APP_DB_USER! -e "SHUTDOWN;" 2>nul
    set "MYSQL_PWD="
    set "APP_DB_PASS="
    timeout /t 3 /nobreak >nul
) else (
    echo        [!] 未找到 MySQL 客户端，跳过数据库清理
    echo        如需手动删除，请运行: DROP DATABASE pristine_dental;
)

:skip_drop_db

REM ═══════════════════════════════════════════════════════════════
REM  Step N: 移除 Windows 服务和计划任务
REM ═══════════════════════════════════════════════════════════════
echo.
echo  [3/%TOTAL_STEPS%] 移除 Windows 服务和计划任务...

REM 删除 Apache Windows 服务。优先用 httpd -k uninstall（Apache 自己注册的，
REM 由它自己卸最干净），httpd.exe 已被删或卸不掉时再用 sc delete 兜底。
if exist "%INSTALL_DIR%\xampp\apache\bin\httpd.exe" (
    echo        移除 Apache 服务 (DentalClinicApache)...
    "%INSTALL_DIR%\xampp\apache\bin\httpd.exe" -k uninstall -n DentalClinicApache >nul 2>&1
)
sc query DentalClinicApache >nul 2>&1
if !ERRORLEVEL! equ 0 sc delete DentalClinicApache >nul 2>&1

REM 删除 MySQL Windows 服务
if "%EXTERNAL_MYSQL%"=="1" (
    echo        现有 MySQL 服务不属于本系统                 [跳过]
) else (
    echo        移除 MySQL 服务 (DentalClinicMySQL)...
    sc delete DentalClinicMySQL >nul 2>&1
    if !ERRORLEVEL! equ 0 (
        echo        MySQL 服务已移除                                [OK]
    ) else (
        echo        MySQL 服务不存在或已移除                        [OK]
    )
)

REM 删除计划任务
echo        移除计划任务...
schtasks /delete /tn "DentalClinic-Scheduler" /f >nul 2>&1
echo        DentalClinic-Scheduler                            [OK]
schtasks /delete /tn "DentalClinic-QueueWorker" /f >nul 2>&1
echo        DentalClinic-QueueWorker                          [OK]
schtasks /delete /tn "DentalClinic-AutoStart" /f >nul 2>&1
echo        DentalClinic-AutoStart                            [OK]
schtasks /delete /tn "DentalClinic-ServiceWatchdog" /f >nul 2>&1
echo        DentalClinic-ServiceWatchdog                      [OK]
schtasks /delete /tn "DentalClinic-LogCleanup" /f >nul 2>&1
echo        DentalClinic-LogCleanup                           [OK]

REM ═══════════════════════════════════════════════════════════════
REM  Step 4: 删除安装目录
REM
REM  --cleanup-only 必须跳过这一步。Inno 的 [UninstallRun] 是在
REM  unins000.exe 仍在 %INSTALL_DIR% 里运行时调用本脚本的，此刻
REM  rmdir 会连同 unins000.exe / unins000.dat（Inno 的卸载清单）一起删，
REM  导致 Inno 无法完成自己的文件清理和「添加/删除程序」注销，
REM  而正在执行的本脚本自身被删还会让 cmd.exe 读不到后续命令。
REM  这一步交给 Inno 完成，本脚本只负责它不知道的运行时产物。
REM ═══════════════════════════════════════════════════════════════
if "%CLEANUP_ONLY%"=="1" (
    echo.
    echo        运行时产物已清理，文件删除由安装程序接管。   [OK]
    goto :summary
)

echo.
echo  [4/%TOTAL_STEPS%] 删除安装目录...

REM 先切出安装目录再删除
cd /d "%USERPROFILE%"

echo        删除 %INSTALL_DIR% ...
REM 使用 rmdir 删除整个安装目录
rmdir /S /Q "%INSTALL_DIR%" >nul 2>&1
if exist "%INSTALL_DIR%" (
    echo        [!] 部分文件未能删除（可能被占用），请手动删除:
    echo            %INSTALL_DIR%
) else (
    echo        安装目录已删除                                  [OK]
)

:summary

REM ═══════════════════════════════════════════════════════════════
REM  完成
REM ═══════════════════════════════════════════════════════════════
echo.
echo  +=====================================================+
echo  ^|       卸载完成                                       ^|
echo  +=====================================================+
echo.
echo  已执行:
echo    - 停止所有服务和进程
echo    - 移除 MySQL 服务 (DentalClinicMySQL)
echo    - 移除 Apache 服务 (DentalClinicApache)
echo    - 移除 3 个计划任务
if "%KEEP_DATA%"=="0" (
echo    - 删除数据库 pristine_dental
echo    - 删除数据库用户 dental
)
if "%CLEANUP_ONLY%"=="1" (
echo    - 安装目录由安装程序删除
) else (
echo    - 删除安装目录 %INSTALL_DIR%
)
if "%KEEP_DATA%"=="1" (
echo.
echo  数据已备份到桌面: dental-backup-*
)
echo.
call :maybe_pause
exit /b 0

REM ── 仅在交互模式下暂停 ──────────────────────────────────────────
REM 隐藏窗口（Inno runhidden）里 pause 等不到按键，会永久挂起卸载流程
:maybe_pause
if "%NO_PAUSE%"=="1" goto :eof
pause
goto :eof

:show_help
echo.
echo  牙科诊所管理系统 - Windows 卸载脚本
echo.
echo  用法: uninstall-win.bat [选项]
echo.
echo  选项:
echo    --keep-data      保留数据库，并备份上传文件和配置到桌面
echo    --yes, -y        跳过确认提示（危险），且不暂停
echo    --cleanup-only   只清数据库/服务/计划任务，不删除安装目录、不暂停
echo                     （供 Inno 卸载器 runhidden 调用，文件删除由 Inno 负责）
echo    --help, -h       显示此帮助信息
echo.
echo  示例:
echo    uninstall-win.bat                  交互式卸载（会确认）
echo    uninstall-win.bat --keep-data      卸载但保留并备份数据
echo    uninstall-win.bat --yes            静默完全卸载
echo    uninstall-win.bat --cleanup-only   仅清运行时产物
echo.
exit /b 0

REM ── 仅终止 CommandLine 含指定安装目录的进程 ──
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
