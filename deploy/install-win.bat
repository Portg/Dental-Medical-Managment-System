@echo off
chcp 65001 >nul 2>&1
setlocal EnableExtensions EnableDelayedExpansion

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"
set "PS_EXE=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"
set "PS_SCRIPT=%SCRIPT_DIR%\install-win.ps1"

if not exist "%PS_EXE%" (
    echo [ERROR] PowerShell not found: %PS_EXE%
    exit /b 1
)

if not exist "%PS_SCRIPT%" (
    echo [ERROR] PowerShell installer script not found: %PS_SCRIPT%
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
    echo [ERROR] 无法探测 PowerShell 版本，请确认 %PS_EXE% 可正常运行。
    exit /b 1
)

if %PS_MAJOR% GEQ 3 goto :ps_ok

echo.
echo  =======================================================
echo    检测到 PowerShell %PS_MAJOR%.0，安装程序需要 3.0 以上
echo  =======================================================
echo.
echo  Windows 7 出厂自带 PowerShell 2.0，需要先安装 WMF 5.1。
echo.

set "WMF_MSU="
for %%F in ("%SCRIPT_DIR%\wmf51\*.msu") do set "WMF_MSU=%%~fF"

if not defined WMF_MSU (
    echo  [错误] 安装包内未找到 WMF 5.1 安装文件（wmf51\*.msu）。
    echo         请手动安装 Windows Management Framework 5.1 后重试：
    echo         https://www.microsoft.com/download/details.aspx?id=54616
    echo.
    echo         注意：WMF 5.1 需要 .NET Framework 4.5 或更高版本。
    exit /b 1
)

echo  即将安装: !WMF_MSU!
echo  安装过程约需几分钟，完成后**必须重启电脑**，再重新运行本安装程序。
echo.
choice /c YN /n /m "  现在安装 WMF 5.1 吗？(Y/N) "
if errorlevel 2 (
    echo  已取消。请先安装 WMF 5.1 再运行安装程序。
    exit /b 1
)

echo.
echo  正在安装 WMF 5.1，请勿关闭窗口...
wusa.exe "!WMF_MSU!" /quiet /norestart
set "WUSA_CODE=!ERRORLEVEL!"

REM wusa 退出码：0=成功，3010=成功但需重启，2359302=已安装
if "!WUSA_CODE!"=="0"       goto :wmf_done
if "!WUSA_CODE!"=="3010"    goto :wmf_done
if "!WUSA_CODE!"=="2359302" goto :wmf_done

echo.
echo  [错误] WMF 5.1 安装失败（错误码 !WUSA_CODE!）。
echo         常见原因：缺少 .NET Framework 4.5+，或系统未打 SP1。
exit /b 1

:wmf_done
echo.
echo  =======================================================
echo    WMF 5.1 安装完成，请重启电脑后重新运行本安装程序
echo  =======================================================
echo.
pause
exit /b 0

:ps_ok
REM install-win.ps1 ships with UTF-8 BOM; run it directly.
"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -File "%PS_SCRIPT%" %*
set "EXIT_CODE=%ERRORLEVEL%"

endlocal & exit /b %EXIT_CODE%
