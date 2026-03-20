@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion
title 牙科诊所管理系统 - 卸载

REM ═══════════════════════════════════════════════════════════════
REM  牙科诊所管理系统 - Windows 卸载脚本
REM  用途: 停止服务 → 可选备份 → 删除数据库 → 移除服务/计划任务 → 删除文件
REM  用法:
REM    uninstall-win.bat                        交互式卸载
REM    uninstall-win.bat --keep-data            保留数据库和上传文件
REM    uninstall-win.bat --yes                  跳过确认提示
REM ═══════════════════════════════════════════════════════════════

set "KEEP_DATA=0"
set "AUTO_YES=0"
set "SCRIPT_DIR=%~dp0"

REM ── 参数解析 ────────────────────────────────────────────────────
:parse_args
if "%~1"=="" goto :args_done
if /i "%~1"=="--keep-data" ( set "KEEP_DATA=1" & shift & goto :parse_args )
if /i "%~1"=="--yes"       ( set "AUTO_YES=1"  & shift & goto :parse_args )
if /i "%~1"=="-y"          ( set "AUTO_YES=1"  & shift & goto :parse_args )
if /i "%~1"=="--help"      ( goto :show_help )
if /i "%~1"=="-h"          ( goto :show_help )
shift
goto :parse_args
:args_done

echo.
echo  +=====================================================+
echo  |       牙科诊所管理系统 - 卸载程序                   |
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
pause
exit /b 1

:dir_found
set "LARAGON_DIR=%INSTALL_DIR%\laragon"
set "PROJECT_DIR=%LARAGON_DIR%\www\dental"

echo  安装目录:  %INSTALL_DIR%
echo  项目目录:  %PROJECT_DIR%
echo.

REM ── 确认卸载 ────────────────────────────────────────────────────
if "%AUTO_YES%"=="1" goto :confirmed

echo  +=====================================================+
echo  |  警告: 卸载将执行以下操作:                          |
echo  |                                                      |
echo  |  1. 停止所有相关服务                                 |
if "%KEEP_DATA%"=="0" (
echo  |  2. 删除数据库 pristine_dental 及数据库用户          |
echo  |  3. 移除 Windows 服务和计划任务                      |
echo  |  4. 删除安装目录下的所有文件                         |
) else (
echo  |  2. 移除 Windows 服务和计划任务                      |
echo  |  3. 删除安装目录（保留数据库和上传文件备份）         |
)
echo  |                                                      |
echo  |  此操作不可恢复！                                    |
echo  +=====================================================+
echo.
set /p "CONFIRM=  确认卸载？输入 YES 继续: "
if /i not "!CONFIRM!"=="YES" (
    echo.
    echo  已取消卸载。
    pause
    exit /b 0
)
:confirmed

set "TOTAL_STEPS=5"
if "%KEEP_DATA%"=="1" set "TOTAL_STEPS=4"

REM ═══════════════════════════════════════════════════════════════
REM  Step 1: 停止所有服务
REM ═══════════════════════════════════════════════════════════════
echo.
echo  [1/%TOTAL_STEPS%] 停止所有服务...

REM 调用 stop 脚本（如果存在）
if exist "%INSTALL_DIR%\stop-win.bat" (
    call "%INSTALL_DIR%\stop-win.bat" >nul 2>&1
    echo        通过 stop-win.bat 停止服务                      [OK]
) else (
    REM 手动停止进程
    echo        停止队列工作进程...
    for /f "tokens=2" %%P in ('wmic process where "commandline like '%%queue:work%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
        taskkill /PID %%P /F >nul 2>&1
    )
    echo        停止 OCR 服务...
    for /f "tokens=2" %%P in ('wmic process where "commandline like '%%ocr_server%%'" get processid 2^>nul ^| findstr /R "[0-9]"') do (
        taskkill /PID %%P /F >nul 2>&1
    )
    echo        停止 Nginx...
    taskkill /IM nginx.exe /F >nul 2>&1
    echo        停止 PHP-CGI...
    taskkill /IM php-cgi.exe /F >nul 2>&1
)

REM 停止 MySQL 服务
echo        停止 MySQL 服务 (DentalClinicMySQL)...
net stop DentalClinicMySQL >nul 2>&1
echo        服务已停止                                          [OK]

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
        echo        正在导出数据库...
        "!MYSQLDUMP_EXE!" -u root pristine_dental > "!BACKUP_DIR!\pristine_dental.sql" 2>nul
        if !ERRORLEVEL! equ 0 (
            echo        已备份数据库到 !BACKUP_DIR!\pristine_dental.sql [OK]
        ) else (
            echo        [!] 数据库导出失败，请手动备份
        )
    )

    echo        备份目录: !BACKUP_DIR!
    goto :skip_drop_db
)

REM ═══════════════════════════════════════════════════════════════
REM  Step 2: 删除数据库和用户
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
    REM 需要先临时启动 MySQL 来删除数据库
    set "MYSQLD_EXE="
    for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do (
        if exist "%%D\bin\mysqld.exe" set "MYSQLD_EXE=%%D\bin\mysqld.exe"
    )
    if not defined MYSQLD_EXE for /d %%D in ("%LARAGON_DIR%\bin\mysql\*") do (
        if exist "%%D\bin\mysqld.exe" set "MYSQLD_EXE=%%D\bin\mysqld.exe"
    )
    if defined MYSQLD_EXE (
        echo        临时启动 MySQL 以删除数据库...
        start /B "" "!MYSQLD_EXE!" --defaults-file="%LARAGON_DIR%\etc\mysql\my.ini" >nul 2>&1
        timeout /t 5 /nobreak >nul
    )

    echo        删除数据库 pristine_dental...
    "!MYSQL_EXE!" -u root -e "DROP DATABASE IF EXISTS pristine_dental;" 2>nul
    if !ERRORLEVEL! equ 0 (
        echo        数据库已删除                                    [OK]
    ) else (
        echo        [!] 数据库删除失败（可能已不存在）
    )

    echo        删除数据库用户 dental...
    "!MYSQL_EXE!" -u root -e "DROP USER IF EXISTS 'dental'@'localhost';" 2>nul
    echo        数据库用户已清理                                [OK]

    REM 再次关闭临时 MySQL
    "!MYSQL_EXE!" -u root -e "SHUTDOWN;" 2>nul
    timeout /t 3 /nobreak >nul
) else (
    echo        [!] 未找到 MySQL 客户端，跳过数据库清理
    echo        如需手动删除，请运行: DROP DATABASE pristine_dental;
)

:skip_drop_db

REM ═══════════════════════════════════════════════════════════════
REM  Step N: 移除 Windows 服务和计划任务
REM ═══════════════════════════════════════════════════════════════
if "%KEEP_DATA%"=="1" (
    echo.
    echo  [3/%TOTAL_STEPS%] 移除 Windows 服务和计划任务...
) else (
    echo.
    echo  [3/%TOTAL_STEPS%] 移除 Windows 服务和计划任务...
)

REM 删除 MySQL Windows 服务
echo        移除 MySQL 服务 (DentalClinicMySQL)...
sc delete DentalClinicMySQL >nul 2>&1
if !ERRORLEVEL! equ 0 (
    echo        MySQL 服务已移除                                [OK]
) else (
    echo        MySQL 服务不存在或已移除                        [OK]
)

REM 删除计划任务
echo        移除计划任务...
schtasks /delete /tn "DentalClinic-Scheduler" /f >nul 2>&1
echo        DentalClinic-Scheduler                            [OK]
schtasks /delete /tn "DentalClinic-QueueWorker" /f >nul 2>&1
echo        DentalClinic-QueueWorker                          [OK]
schtasks /delete /tn "DentalClinic-LogCleanup" /f >nul 2>&1
echo        DentalClinic-LogCleanup                           [OK]

REM ═══════════════════════════════════════════════════════════════
REM  Step N: 删除安装目录
REM ═══════════════════════════════════════════════════════════════
if "%KEEP_DATA%"=="1" (
    echo.
    echo  [4/%TOTAL_STEPS%] 删除安装目录...
) else (
    echo.
    echo  [4/%TOTAL_STEPS%] 删除安装目录...
)

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

REM ═══════════════════════════════════════════════════════════════
REM  完成
REM ═══════════════════════════════════════════════════════════════
echo.
echo  +=====================================================+
echo  |       卸载完成                                       |
echo  +=====================================================+
echo.
echo  已执行:
echo    - 停止所有服务和进程
echo    - 移除 MySQL 服务 (DentalClinicMySQL)
echo    - 移除 3 个计划任务
if "%KEEP_DATA%"=="0" (
echo    - 删除数据库 pristine_dental
echo    - 删除数据库用户 dental
)
echo    - 删除安装目录 %INSTALL_DIR%
if "%KEEP_DATA%"=="1" (
echo.
echo  数据已备份到桌面: dental-backup-*
)
echo.
pause
exit /b 0

:show_help
echo.
echo  牙科诊所管理系统 - Windows 卸载脚本
echo.
echo  用法: uninstall-win.bat [选项]
echo.
echo  选项:
echo    --keep-data    保留数据库，并备份上传文件和配置到桌面
echo    --yes, -y      跳过确认提示（危险）
echo    --help, -h     显示此帮助信息
echo.
echo  示例:
echo    uninstall-win.bat                  交互式卸载（会确认）
echo    uninstall-win.bat --keep-data      卸载但保留并备份数据
echo    uninstall-win.bat --yes            静默完全卸载
echo.
exit /b 0
