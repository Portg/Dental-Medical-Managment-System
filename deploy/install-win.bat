@echo off
REM build.sh 会把本文件转成 GBK(CP936) + CRLF 再打包，因此这里必须是 936；
REM 用 65001 会让后续 GBK 字节被当成 UTF-8 解析，中文全部变乱码。
chcp 936 >nul 2>&1
setlocal EnableExtensions EnableDelayedExpansion

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "PS_EXE=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
set "PS_SCRIPT=%SCRIPT_DIR%\install-win.ps1"

REM ═══════════════════════════════════════════════════════════════
REM  参数解析
REM
REM  --unattended: 无人值守（Inno 的 [Run]/[Code] 以隐藏窗口调用）。
REM  此模式下**禁止**出现 choice / pause / set /p —— 窗口不可见，
REM  任何等待输入的语句都会让安装永久挂起。
REM  其余参数原样转发给 install-win.ps1（Inno 会传 {app}）。
REM ═══════════════════════════════════════════════════════════════
set "UNATTENDED=0"
set "PS_ARGS="

:parse_args
if "%~1"=="" goto :args_done
if /i "%~1"=="--unattended" ( set "UNATTENDED=1" & shift & goto :parse_args )
set PS_ARGS=!PS_ARGS! "%~1"
shift
goto :parse_args
:args_done

REM 没传安装目录就用本脚本所在目录。
REM install-win.ps1 的默认值写死为 C:\DentalClinic，而安装向导允许改路径；
REM 装 .NET/WMF 后重启、用户再来双击本脚本时是不带参数的，
REM 不补这一步就会去配置一个根本不存在的 C:\DentalClinic。
if not defined PS_ARGS set PS_ARGS="%SCRIPT_DIR%"

REM 退出码约定（供 Inno 的 [Code] 判断）:
REM   0    安装配置完成
REM   1    失败
REM   3010 前置组件已装好，需重启后重新运行安装程序
set "RC_REBOOT_REQUIRED=3010"

REM ═══════════════════════════════════════════════════════════════
REM  前置阶段日志
REM
REM  这一段（PowerShell 版本探测、.NET 4.8、WMF 5.1）跑在 install-win.ps1
REM  之前，而 Inno 以 runhidden 调用本脚本时根本没有控制台 —— 出错的话
REM  用户只看到一个失败弹窗，什么线索都没有。纯净 Win7 恰恰最容易卡在这里。
REM  日志追加而非覆盖：装 .NET / WMF 各需重启一次，一次完整安装会跑三遍本脚本，
REM  三遍的记录必须都留下才看得出卡在哪一步。
REM ═══════════════════════════════════════════════════════════════
set "LOG_DIR=%SCRIPT_DIR%\logs"
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%" >nul 2>&1
set "PREREQ_LOG=%LOG_DIR%\prereq.log"

call :log "===== install-win.bat 启动 (unattended=%UNATTENDED%) ====="

if not exist "%PS_EXE%" (
    call :log "[ERROR] PowerShell not found: %PS_EXE%"
    exit /b 1
)

if not exist "%PS_SCRIPT%" (
    call :log "[ERROR] PowerShell installer script not found: %PS_SCRIPT%"
    exit /b 1
)

REM ═══════════════════════════════════════════════════════════════
REM  PowerShell 版本门禁
REM
REM  Windows 7 SP1 出厂自带 PowerShell 2.0，而 install-win.ps1 使用了
REM  [Type]::new()（需 PS5）与 *> 重定向（需 PS3），在 PS2 上会在**解析阶段**
REM  就失败——报错信息还很难懂。因此这里先探测版本，低于 3 就安装随包的
REM  WMF 5.1（KB3191566），装完需要重启才生效。
REM ═══════════════════════════════════════════════════════════════
set "PS_MAJOR="
for /f "usebackq delims=" %%V in (`"%PS_EXE%" -NoProfile -Command "$PSVersionTable.PSVersion.Major" 2^>nul`) do set "PS_MAJOR=%%V"

if not defined PS_MAJOR (
    call :log "[ERROR] 无法探测 PowerShell 版本，请确认 %PS_EXE% 可正常运行。"
    exit /b 1
)

call :log "PowerShell 主版本: %PS_MAJOR%"
if %PS_MAJOR% GEQ 3 goto :ps_ok

echo.
echo  =======================================================
echo    检测到 PowerShell %PS_MAJOR%.0，安装程序需要 3.0 以上
echo  =======================================================
echo.
echo  Windows 7 出厂自带 PowerShell 2.0，需要先安装 WMF 5.1。
echo.

REM ═══════════════════════════════════════════════════════════════
REM  前置 1: .NET Framework 4.5.2+
REM
REM  WMF 5.1 (KB3191566) 的安装前提是 .NET Framework **4.5.2** 或更高，
REM  纯净 Win7 SP1 只带 .NET 3.5.1，直接装 WMF 会失败。
REM  判据是注册表 NDP\v4\Full 的 Release 值：
REM    378389=4.5  378675=4.5.1  379893=4.5.2  528040=4.8
REM  参考 learn.microsoft.com «如何：确定安装的 .NET Framework 版本»
REM ═══════════════════════════════════════════════════════════════
set "DOTNET_MIN=379893"
set "DOTNET_RELEASE="
for /f "tokens=3" %%R in ('reg query "HKLM\SOFTWARE\Microsoft\NET Framework Setup\NDP\v4\Full" /v Release /reg:64 2^>nul ^| findstr /i /c:"Release"') do set "DOTNET_RELEASE=%%R"

set "DOTNET_DEC=0"
if defined DOTNET_RELEASE set /a DOTNET_DEC=%DOTNET_RELEASE% 2>nul

call :log ".NET Framework Release=%DOTNET_DEC% (门槛 %DOTNET_MIN%)"
if %DOTNET_DEC% GEQ %DOTNET_MIN% (
    echo  .NET Framework 检查通过 ^(Release=%DOTNET_DEC%^)
    echo.
    goto :dotnet_ok
)

echo  当前 .NET Framework 版本不满足 WMF 5.1 的要求
echo  ^(需要 Release ^>= %DOTNET_MIN% 即 4.5.2，实测 %DOTNET_DEC%^)
echo.

set "DOTNET_EXE="
for %%F in ("%SCRIPT_DIR%\dotnet48\*.exe") do set "DOTNET_EXE=%%~fF"

if not defined DOTNET_EXE (
    echo  [错误] 安装包内未找到 .NET Framework 4.8 离线安装文件（dotnet48\*.exe）。
    echo         请手动安装 .NET Framework 4.8 后重试：
    echo         https://dotnet.microsoft.com/download/dotnet-framework/net48
    call :log "[ERROR] 缺少 dotnet48\*.exe，无法安装 .NET 4.8"
    exit /b 1
)

if "%UNATTENDED%"=="1" goto :dotnet_install

echo  即将安装: !DOTNET_EXE!
echo  安装过程约需 10 分钟，完成后**必须重启电脑**，再重新运行本安装程序。
echo.
choice /c YN /n /m "  现在安装 .NET Framework 4.8 吗？(Y/N) "
if errorlevel 2 (
    echo  已取消。请先安装 .NET Framework 4.8 再运行安装程序。
    call :log "用户取消了 .NET 4.8 安装"
    exit /b 1
)

:dotnet_install
echo.
echo  正在安装 .NET Framework 4.8，请勿关闭窗口（约 10 分钟）...
call :log "开始安装 .NET 4.8: !DOTNET_EXE!"
"!DOTNET_EXE!" /q /norestart >>"%PREREQ_LOG%" 2>&1
set "DOTNET_CODE=!ERRORLEVEL!"
call :log ".NET 4.8 安装器退出码: !DOTNET_CODE!"

REM 退出码：0=成功，1641/3010=成功但需重启，5100=系统不满足前置条件
if "!DOTNET_CODE!"=="0"    goto :dotnet_installed
if "!DOTNET_CODE!"=="3010" goto :dotnet_installed
if "!DOTNET_CODE!"=="1641" goto :dotnet_installed

echo.
echo  [错误] .NET Framework 4.8 安装失败（错误码 !DOTNET_CODE!）。
if "!DOTNET_CODE!"=="5100" (
    echo         错误码 5100 表示系统缺少前置更新。Win7 上装 .NET 4.8 需要：
    echo           - Windows 7 Service Pack 1
    echo           - KB4474419  SHA-2 代码签名支持
    echo           - KB4490628  服务堆栈更新
    echo         请先通过 Windows Update 安装以上更新，或从微软更新目录手动下载。
)
call :log "[ERROR] .NET 4.8 安装失败，错误码 !DOTNET_CODE!（5100=缺 SP1/KB4474419/KB4490628）"
exit /b 1

:dotnet_installed
echo.
echo  =======================================================
echo    .NET Framework 4.8 安装完成
echo    请重启电脑后重新运行本安装程序，以继续安装 WMF 5.1
echo  =======================================================
echo.
call :log ".NET 4.8 就绪，需重启后重新运行（退出码 %RC_REBOOT_REQUIRED%）"
call :maybe_pause
exit /b %RC_REBOOT_REQUIRED%

:dotnet_ok

REM ═══════════════════════════════════════════════════════════════
REM  前置 2: WMF 5.1
REM ═══════════════════════════════════════════════════════════════
set "WMF_MSU="
for %%F in ("%SCRIPT_DIR%\wmf51\*.msu") do set "WMF_MSU=%%~fF"

if not defined WMF_MSU (
    echo  [错误] 安装包内未找到 WMF 5.1 安装文件（wmf51\*.msu）。
    echo         请手动安装 Windows Management Framework 5.1 后重试：
    echo         https://www.microsoft.com/download/details.aspx?id=54616
    call :log "[ERROR] 缺少 wmf51\*.msu，无法安装 WMF 5.1"
    exit /b 1
)

if "%UNATTENDED%"=="1" goto :wmf_install

echo  即将安装: !WMF_MSU!
echo  安装过程约需几分钟，完成后**必须重启电脑**，再重新运行本安装程序。
echo.
choice /c YN /n /m "  现在安装 WMF 5.1 吗？(Y/N) "
if errorlevel 2 (
    echo  已取消。请先安装 WMF 5.1 再运行安装程序。
    call :log "用户取消了 WMF 5.1 安装"
    exit /b 1
)

:wmf_install
echo.
echo  正在安装 WMF 5.1，请勿关闭窗口...
call :log "开始安装 WMF 5.1: !WMF_MSU!"
wusa.exe "!WMF_MSU!" /quiet /norestart >>"%PREREQ_LOG%" 2>&1
set "WUSA_CODE=!ERRORLEVEL!"
call :log "WMF 5.1 (wusa) 退出码: !WUSA_CODE!"

REM wusa 退出码：0=成功，3010=成功但需重启，2359302=已安装
if "!WUSA_CODE!"=="0"       goto :wmf_done
if "!WUSA_CODE!"=="3010"    goto :wmf_done
if "!WUSA_CODE!"=="2359302" goto :wmf_done

echo.
echo  [错误] WMF 5.1 安装失败（错误码 !WUSA_CODE!）。
echo         常见原因：系统未打 SP1，或缺少 KB4474419（SHA-2 代码签名支持）。
call :log "[ERROR] WMF 5.1 安装失败，错误码 !WUSA_CODE!"
exit /b 1

:wmf_done
echo.
echo  =======================================================
echo    WMF 5.1 安装完成，请重启电脑后重新运行本安装程序
echo  =======================================================
echo.
call :log "WMF 5.1 就绪，需重启后重新运行（退出码 %RC_REBOOT_REQUIRED%）"
call :maybe_pause
exit /b %RC_REBOOT_REQUIRED%

:ps_ok
REM install-win.ps1 ships with UTF-8 BOM; run it directly.
call :log "前置检查通过，转入 install-win.ps1（其自身日志在 logs\install-*.log）"
"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -File "%PS_SCRIPT%" !PS_ARGS!
set "EXIT_CODE=!ERRORLEVEL!"
call :log "install-win.ps1 退出码: !EXIT_CODE!"

endlocal & exit /b %EXIT_CODE%

REM ── 仅在交互模式下暂停 ──────────────────────────────────────────
REM 隐藏窗口（Inno runhidden）里 pause 等不到按键，会永久挂起安装流程
:maybe_pause
if "%UNATTENDED%"=="1" goto :eof
pause
goto :eof

REM ── 同时输出到控制台与日志 ──────────────────────────────────────
REM 隐藏窗口下控制台那份没人看得到，日志是唯一线索
:log
echo %~1
>>"%PREREQ_LOG%" echo [%DATE% %TIME%] %~1
goto :eof
