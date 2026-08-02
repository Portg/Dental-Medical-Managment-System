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
REM 一个坏掉的 SysWOW64 副本等）这里仍读得到版本，至少能判断该不该装 WMF。
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
REM 万一探测到的是一行错误文本，"Some error" GEQ 3 会为真，
REM 于是带着 PS2 一路冲进 install-win.ps1，在解析阶段炸得莫名其妙。
echo %PS_MAJOR%| findstr /r "^[0-9][0-9]*$" >nul
if errorlevel 1 (
    call :log "[ERROR] PowerShell 版本探测结果不是数字: [%PS_MAJOR%]"
    echo.
    echo  PowerShell 版本探测结果异常: [%PS_MAJOR%]
    echo  详细信息见: %PREREQ_LOG%
    echo.
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
REM  前置 0: Win7 SHA-2 支持（KB4490628 服务堆栈 + KB4474419 签名）
REM
REM  微软自 2019 年起用 SHA-2 重签所有更新包。纯净 Win7 SP1 不支持 SHA-2，
REM  wusa 拿到这类 MSU 的典型表现**不是**报错，而是长时间卡在
REM  "Searching for updates on this computer" 永不返回 —— 这正是装 .NET/WMF
REM  时「一直停着」的头号原因。必须按 SSU → SHA-2 的顺序先补上。
REM
REM  win7-prereq\ 下的文件名带 01- / 02- 前缀，即安装顺序，for 按名字排序遍历。
REM ═══════════════════════════════════════════════════════════════
if not exist "%SCRIPT_DIR%\win7-prereq" (
    call :log "未随包提供 win7-prereq，跳过 SHA-2 前置（老安装包或已手工处理）"
    goto :sha2_done
)

REM wusa 走 CBS/Windows Update 栈，wuauserv 被禁用时会无限等待而不报错。
REM 取的是数值状态（4=RUNNING）而非 "RUNNING" 字样：sc 的输出标签在中文系统上
REM 是本地化的，按字样匹配会取空；取空时也走启动分支，重复启动无害。
set "WU_STATE="
for /f "tokens=3" %%S in ('sc query wuauserv 2^>nul ^| findstr /i /c:"STATE"') do set "WU_STATE=%%S"
call :log "Windows Update 服务状态码: [!WU_STATE!]（4=RUNNING）"
if not "!WU_STATE!"=="4" (
    echo  正在启动 Windows Update 服务（wusa 依赖它，被停用会导致安装卡死）...
    sc config wuauserv start= demand >>"%PREREQ_LOG%" 2>&1
    net start wuauserv >>"%PREREQ_LOG%" 2>&1
    call :log "已尝试启动 wuauserv"
)

REM :install_msu 是 call 的子过程，其中的 exit /b 1 只把错误码交回这里、
REM 不会中止脚本。必须逐个判断，否则前置装失败会被忽略、继续去装注定失败的 .NET。
set "PREREQ_REBOOT=0"
for %%M in ("%SCRIPT_DIR%\win7-prereq\*.msu") do (
    call :install_msu "%%~fM"
    if errorlevel 1 (
        call :maybe_pause
        exit /b 1
    )
)

if "!PREREQ_REBOOT!"=="1" (
    echo.
    echo  =======================================================
    echo    SHA-2 前置补丁安装完成，请重启电脑后重新运行本安装程序
    echo  =======================================================
    echo.
    call :log "SHA-2 前置就绪，需重启后重新运行（退出码 %RC_REBOOT_REQUIRED%）"
    call :maybe_pause
    exit /b %RC_REBOOT_REQUIRED%
)

:sha2_done

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

REM ── 安装单个 MSU 补丁 ───────────────────────────────────────────
REM
REM  用法: call :install_msu "<完整路径.msu>"
REM
REM  不直接 `wusa ... /quiet`（同步阻塞）的原因：wusa 不往 stdout 写进度，
REM  输出又被重定向进日志，一旦 CBS 卡住，界面上就只剩一行「请勿关闭窗口」，
REM  在装和挂死完全无法区分，用户只能干等。这里改为异步启动 + 轮询 +
REM  每分钟心跳 + 超时上报，退出码通过临时文件回传。
REM
REM  超时后**不杀进程**：wusa 中途被杀可能损坏服务堆栈，届时连正常的
REM  Windows Update 都会坏掉。只上报并退出，让用户决定是继续等还是排查。
REM ────────────────────────────────────────────────────────────────
:install_msu
setlocal EnableDelayedExpansion
set "MSU=%~1"
set "MSU_NAME=%~n1"
set "MSU_TIMEOUT_SEC=2700"
set "LOCAL_REBOOT=%PREREQ_REBOOT%"

REM 从文件名里取 KB 号（形如 01-windows6.1-kb4490628-x64）
set "KB="
for %%T in (!MSU_NAME:-= !) do (
    echo %%T | findstr /i /b /c:"kb" >nul && set "KB=%%T"
)

if defined KB (
    wmic qfe get hotfixid 2>nul | findstr /i "!KB!" >nul && (
        echo  !KB! 已安装，跳过
        call :log "!KB! 已安装，跳过"
        endlocal & set "PREREQ_REBOOT=%LOCAL_REBOOT%"
        goto :eof
    )
)

echo.
echo  正在安装 !KB!（首次安装可能需要 10-40 分钟，请勿关机）...
echo  进度可查看: C:\Windows\Logs\CBS\CBS.log
call :log "开始安装 !MSU!"

set "MSU_CODE=%LOG_DIR%\.msu_code.tmp"
set "MSU_RUNNER=%LOG_DIR%\.msu_run.bat"
del /q "!MSU_CODE!" >nul 2>&1

> "!MSU_RUNNER!" echo @echo off
>>"!MSU_RUNNER!" echo wusa.exe "!MSU!" /quiet /norestart ^>^>"%PREREQ_LOG%" 2^>^&1
>>"!MSU_RUNNER!" echo echo %%ERRORLEVEL%%^>"!MSU_CODE!"

start "" /b cmd /c "!MSU_RUNNER!"

set /a MSU_WAITED=0
:msu_poll
if exist "!MSU_CODE!" goto :msu_finished
ping -n 16 127.0.0.1 >nul 2>&1
set /a MSU_WAITED+=15
if !MSU_WAITED! GEQ !MSU_TIMEOUT_SEC! goto :msu_timeout
set /a MSU_TICK=!MSU_WAITED! %% 60
if !MSU_TICK! EQU 0 echo    ...仍在安装，已等待 !MSU_WAITED! 秒
goto :msu_poll

:msu_timeout
echo.
echo  [错误] !KB! 安装超过 !MSU_TIMEOUT_SEC! 秒仍未结束。
echo         wusa 仍在后台运行，**不要关机**，也不要重复运行安装程序。
echo         请检查：
echo           1. C:\Windows\Logs\CBS\CBS.log 尾部时间戳是否还在推进
echo           2. 任务管理器里 TiWorker.exe / TrustedInstaller.exe 是否在占用 CPU
echo         若两者都静止，多为 Windows Update 组件损坏，需先修复 WU 再重试。
call :log "[ERROR] !KB! 安装超时（!MSU_TIMEOUT_SEC! 秒），未杀进程"
endlocal & set "PREREQ_REBOOT=%LOCAL_REBOOT%"
exit /b 1

:msu_finished
set "MSU_RC="
set /p MSU_RC=<"!MSU_CODE!"
del /q "!MSU_CODE!" "!MSU_RUNNER!" >nul 2>&1
call :log "!KB! (wusa) 退出码: !MSU_RC!"

REM 0=成功 3010=成功待重启 2359302=已安装 0x80240017=不适用于本系统
REM 注意必须用括号包住：`if cond A & goto B` 里的 goto 不属于 if 体，会无条件执行。
REM 0x80240017 在 %ERRORLEVEL% 里可能以无符号或有符号（-2145124329）两种形式出现。
if "!MSU_RC!"=="0" (
    set "LOCAL_REBOOT=1"
    goto :msu_ok
)
if "!MSU_RC!"=="3010" (
    set "LOCAL_REBOOT=1"
    goto :msu_ok
)
if "!MSU_RC!"=="2359302"     goto :msu_ok
if "!MSU_RC!"=="2149842967"  goto :msu_ok
if "!MSU_RC!"=="-2145124329" goto :msu_ok

echo  [错误] !KB! 安装失败（错误码 !MSU_RC!）。
echo         常见原因：系统未打 SP1，或 Windows Update 组件损坏。
call :log "[ERROR] !KB! 安装失败，错误码 !MSU_RC!"
endlocal & set "PREREQ_REBOOT=%LOCAL_REBOOT%"
exit /b 1

:msu_ok
echo  !KB! 就绪
endlocal & set "PREREQ_REBOOT=%LOCAL_REBOOT%"
goto :eof

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

REM 读注册表里的 PowerShell 版本，结果放进 PS_RAW（读不到就保持未定义）。
REM 两个注册表视图都试：64 位系统上如果本脚本被 32 位宿主（比如 32 位的 Inno
REM 安装程序）拉起，默认视图会被重定向到 Wow6432Node，那里没有这些键。
REM 反过来在 32 位系统上 /reg:64 会直接报错，输出为空，正好被跳过。
:probe_ps_registry
for /f "tokens=3" %%V in ('reg query "%~1" /v PowerShellVersion 2^>nul ^| findstr /i "PowerShellVersion"') do set "PS_RAW=%%V"
if not defined PS_RAW for /f "tokens=3" %%V in ('reg query "%~1" /v PowerShellVersion /reg:64 2^>nul ^| findstr /i "PowerShellVersion"') do set "PS_RAW=%%V"
goto :eof
