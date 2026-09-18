@echo off
setlocal EnableExtensions
title Dental Clinic - GitHub Deploy

if not defined DENTAL_GITHUB_REPO set "DENTAL_GITHUB_REPO=Portg/Dental-Medical-Managment-System"
set "DEPLOY_URL=https://github.com/%DENTAL_GITHUB_REPO%/releases/latest/download/github-deploy-windows.ps1"
set "DEPLOY_SCRIPT=%TEMP%\dental-github-deploy-%RANDOM%.ps1"
set "PS_EXE=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"

echo Downloading GitHub deployment script...
"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -Command "try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]3072 } catch {}; $wc = New-Object Net.WebClient; $wc.Headers.Add('User-Agent','dental-clinic-deployer'); $wc.DownloadFile('%DEPLOY_URL%','%DEPLOY_SCRIPT%')"
if errorlevel 1 goto :download_failed

"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -File "%DEPLOY_SCRIPT%" %*
set "EXIT_CODE=%ERRORLEVEL%"
del "%DEPLOY_SCRIPT%" >nul 2>&1
if not "%EXIT_CODE%"=="0" pause
exit /b %EXIT_CODE%

:download_failed
echo.
echo Failed to download:
echo   %DEPLOY_URL%
echo Check the network and GitHub Release, then try again.
echo.
pause
exit /b 1
