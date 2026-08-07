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
set "SELFTEST=0"
set "PS_ARGS="
set "ORIG_ARGS=%*"

:parse_args
if "%~1"=="" goto :args_done
if /i "%~1"=="--unattended" ( set "UNATTENDED=1" & shift & goto :parse_args )
if /i "%~1"=="--selftest"   ( set "SELFTEST=1"   & shift & goto :parse_args )
set PS_ARGS=!PS_ARGS! "%~1"
shift
goto :parse_args
:args_done

REM 没传安装目录就用本脚本所在目录。
REM install-win.ps1 的默认值写死为 C:\DentalClinic，而安装向导允许改路径；
REM 用户直接双击本脚本时不会传安装目录；不补这一步就会去配置一个
REM 根本不存在的 C:\DentalClinic。
if not defined PS_ARGS set PS_ARGS="%SCRIPT_DIR%"
if "%UNATTENDED%"=="1" set PS_ARGS=!PS_ARGS! "--non-interactive"

REM ═══════════════════════════════════════════════════════════════
REM  启动阶段日志
REM
REM  PowerShell 探测跑在 install-win.ps1 之前，而 Inno 以 runhidden 调用本脚本时
REM  没有控制台。日志保留探测失败原因，避免现场只看到一个失败弹窗。
REM ═══════════════════════════════════════════════════════════════
set "LOG_DIR=%SCRIPT_DIR%\logs"
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%" >nul 2>&1
set "PREREQ_LOG=%LOG_DIR%\prereq.log"

call :log "===== install-win.bat 启动 (unattended=%UNATTENDED%) ====="

REM ═══════════════════════════════════════════════════════════════
REM  管理员权限：install-win.ps1 必须管理员。非 unattended 时尝试 UAC 提升。
REM  Inno 以 PrivilegesRequired=admin 调用时已是提升进程，net session 会成功。
REM ═══════════════════════════════════════════════════════════════
net session >nul 2>&1
if errorlevel 1 (
    if "%UNATTENDED%"=="1" (
        call :log "[ERROR] 需要管理员权限（unattended 模式无法弹出 UAC）"
        echo.
        echo  安装需要管理员权限。请右键「以管理员身份运行」后重试。
        echo.
        exit /b 1
    )
    if "%SELFTEST%"=="1" goto :after_admin_check
    call :log "当前非管理员，请求 UAC 提升..."
    echo  需要管理员权限，请在 UAC 提示中选择「是」...
    "%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -LiteralPath '%~f0' -Verb RunAs -ArgumentList '%ORIG_ARGS%' -Wait"
    set "ELEV_EXIT=!ERRORLEVEL!"
    if not "!ELEV_EXIT!"=="0" if not "!ELEV_EXIT!"=="1" (
        echo.
        echo  无法自动提升。请右键本脚本，选择「以管理员身份运行」。
        echo.
        exit /b 1
    )
    exit /b !ELEV_EXIT!
)
:after_admin_check

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
REM  Windows 7 SP1 出厂自带 PowerShell 2.0。install-win.ps1 已限制为 PS2 语法，
REM  因此这里只确认 PowerShell 能正常执行，不再安装 WMF/.NET/Windows Update。
REM ═══════════════════════════════════════════════════════════════
set "PS_MAJOR="
set "PS_RAW="
set "PS_OUT=%LOG_DIR%\ps-probe.out"
set "PS_ERR=%LOG_DIR%\ps-probe.err"
del "%PS_OUT%" "%PS_ERR%" >nul 2>&1

REM 探测 1：直接问 PowerShell。
REM
REM 这里刻意**不用** for /f 去跑命令：命令以带引号的路径开头时，cmd 对反引号内
REM 容的引号处理有坑，一旦解析歪了就是「没有输出」，和 PowerShell 本身起不来
REM 的表现完全一样，没法区分。改成普通重定向到文件，再用 for /f 读文件——
REM 读文件是 usebackq 里最不含糊的一种形态。
REM stderr 也必须留下：之前写的是 2>nul，把真实错误吞了，现场只剩一句
REM 「无法探测」，连 powershell 报了什么都看不到。
"%PS_EXE%" -NoProfile -NonInteractive -Command "$PSVersionTable.PSVersion.Major" >"%PS_OUT%" 2>"%PS_ERR%"

if exist "%PS_OUT%" for /f "usebackq delims=" %%V in ("%PS_OUT%") do set "PS_MAJOR=%%V"
if defined PS_MAJOR set "PS_MAJOR=!PS_MAJOR: =!"

REM 探测 2：回退读注册表。
REM PowerShell 进程起不来时（.NET 3.5.1 功能被关、组策略限制、WOW64 重定向到
REM 一个坏掉的 SysWOW64 副本等）这里仍读得到版本，能给出更具体的诊断。
REM PS3+ 装好后会写 \3\ 这个键，PS1/2 只有 \1\。
if not defined PS_MAJOR (
    call :probe_ps_registry "HKLM\SOFTWARE\Microsoft\PowerShell\3\PowerShellEngine"
    if not defined PS_RAW call :probe_ps_registry "HKLM\SOFTWARE\Microsoft\PowerShell\1\PowerShellEngine"
    if defined PS_RAW (
        for /f "delims=." %%M in ("!PS_RAW!") do set "PS_MAJOR=%%M"
        call :log "PowerShell 版本取自注册表: !PS_RAW!（powershell.exe 无法直接执行）"
    )
)

if not defined PS_MAJOR (
    call :log "[ERROR] 无法探测 PowerShell 版本。"
    call :log "        PS_EXE=%PS_EXE%"
    if exist "%PS_ERR%" (
        for /f "usebackq delims=" %%E in ("%PS_ERR%") do call :log "        powershell 报错: %%E"
    )
    echo.
    echo  =======================================================
    echo    无法探测 PowerShell 版本
    echo  =======================================================
    echo.
    echo  详细信息已写入: %PREREQ_LOG%
    echo.
    echo  请在目标机手动执行下面这条命令，看它报什么错：
    echo    "%PS_EXE%" -NoProfile -Command "$PSVersionTable.PSVersion.Major"
    echo.
    echo  常见原因：
    echo    1. .NET Framework 3.5.1 功能被关闭 —— PowerShell 2.0 依赖它，
    echo       在「控制面板 ^> 程序和功能 ^> 打开或关闭 Windows 功能」中开启
    echo    2. 组策略/杀毒软件拦截了 powershell.exe
    echo    3. 系统文件损坏 —— 以管理员身份执行 sfc /scannow 后重试
    echo.
    exit /b 1
)

REM 必须确认是纯数字：批处理的 GEQ 遇到非数字会退化成字符串比较，
REM 万一探测到的是一行错误文本，批处理数字比较会产生误判。
echo %PS_MAJOR%| findstr /r "^[0-9][0-9]*$" >nul
if errorlevel 1 (
    call :log "[ERROR] PowerShell 版本探测结果不是数字: [%PS_MAJOR%]"
    echo.
    echo  PowerShell 版本探测结果异常: [%PS_MAJOR%]
    echo  详细信息见: %PREREQ_LOG%
    echo.
    exit /b 1
)

if %PS_MAJOR% LSS 2 (
    call :log "[ERROR] PowerShell %PS_MAJOR%.0 过旧，最低要求 2.0"
    echo  当前 PowerShell 版本过旧，安装程序最低需要 PowerShell 2.0。
    exit /b 1
)

call :log "PowerShell 主版本: %PS_MAJOR%"

REM 自检必须放在版本探测和最低版本门禁之后，但在主安装动作之前
if "%SELFTEST%"=="1" goto :selftest

REM install-win.ps1 ships with UTF-8 BOM; run it directly.
call :log "PowerShell 2.0+ 检查通过，转入 install-win.ps1（其自身日志在 logs\install-*.log）"
"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -File "%PS_SCRIPT%" !PS_ARGS!
set "EXIT_CODE=!ERRORLEVEL!"
call :log "install-win.ps1 退出码: !EXIT_CODE!"

endlocal & exit /b %EXIT_CODE%

REM ── 自检模式（--selftest）───────────────────────────────────────
REM
REM  只读：不安装任何东西，只确认 PowerShell 版本与配置脚本是否存在。
REM  开发机（macOS/Linux）跑不了批处理，这就是最快的验证回路。
REM ────────────────────────────────────────────────────────────────
:selftest
echo.
echo  ===== install-win.bat 自检（不安装任何东西）=====
echo.
echo  [脚本] %~f0
echo  [目录] %SCRIPT_DIR%
echo  [日志] %PREREQ_LOG%
echo.

echo  --- PowerShell ---
echo    探测到主版本: %PS_MAJOR%   ^(最低要求 2；不安装 WMF/.NET^)
if exist "%PS_SCRIPT%" (echo    install-win.ps1: 已找到) else (echo    install-win.ps1: 缺失)
echo.
echo  ===== 自检结束，未做任何修改 =====
echo.
call :log "自检完成"
exit /b 0

REM ── 同时输出到控制台与日志 ──────────────────────────────────────
REM 隐藏窗口下控制台那份没人看得到，日志是唯一线索
:log
echo %~1
>>"%PREREQ_LOG%" echo [%DATE% %TIME%] %~1
goto :eof

REM 读注册表里的 PowerShell 版本，结果放进 PS_RAW（读不到就保持未定义）。
REM 两个注册表视图都试：64 位系统上如果本脚本被 32 位宿主（比如 32 位的 Inno
REM 安装程序）拉起，默认视图会被重定向到 Wow6432Node，那里没有这些键。
REM 反过来在 32 位系统上 /reg:64 会直接报错，输出为空，正好被跳过。
:probe_ps_registry
for /f "tokens=3" %%V in ('reg query "%~1" /v PowerShellVersion 2^>nul ^| findstr /i "PowerShellVersion"') do set "PS_RAW=%%V"
if not defined PS_RAW for /f "tokens=3" %%V in ('reg query "%~1" /v PowerShellVersion /reg:64 2^>nul ^| findstr /i "PowerShellVersion"') do set "PS_RAW=%%V"
goto :eof
