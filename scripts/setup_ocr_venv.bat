@echo off
chcp 65001 >nul
REM ============================================================
REM OCR Python Virtual Environment Setup (Windows)
REM Creates scripts\venv with PaddleOCR + Flask dependencies.
REM
REM Usage:
REM   scripts\setup_ocr_venv.bat             install only
REM   scripts\setup_ocr_venv.bat --start     install + start server
REM ============================================================

setlocal enabledelayedexpansion

set "SCRIPT_DIR=%~dp0"
set "VENV_DIR=%SCRIPT_DIR%venv"
set "REQ_FILE=%SCRIPT_DIR%requirements.txt"
set "START_SERVER=0"

if "%~1"=="--start" set "START_SERVER=1"

REM ── 1. Find Python 3.9-3.12 ────────────────────────────────────────
set "PYTHON="

REM Try py launcher first (standard Windows Python installer)
where py >nul 2>&1
if %errorlevel%==0 (
    for /f "tokens=*" %%v in ('py -3 --version 2^>nul') do set "PY_VER=%%v"
    if defined PY_VER (
        set "PYTHON=py -3"
        goto :found_python
    )
)

REM Try python3 / python in PATH
for %%p in (python3 python) do (
    where %%p >nul 2>&1
    if !errorlevel!==0 (
        for /f "tokens=*" %%v in ('%%p --version 2^>nul') do (
            echo %%v | findstr /r "3\.[0-9]" >nul
            if !errorlevel!==0 (
                REM Not Python 3, skip
            ) else (
                set "PYTHON=%%p"
                goto :found_python
            )
        )
    )
)

echo ERROR: Python 3.9+ not found. Please install Python from https://www.python.org >&2
exit /b 1

:found_python
echo [INFO] Using %PYTHON%
for /f "tokens=*" %%v in ('%PYTHON% --version') do echo        %%v

REM ── 2. Create virtual environment ──────────────────────────────────
if not exist "%VENV_DIR%" (
    echo [INFO] Creating virtual environment at %VENV_DIR% ...
    %PYTHON% -m venv "%VENV_DIR%"
) else (
    echo [INFO] Virtual environment already exists at %VENV_DIR%
)

REM ── 3. Install / upgrade dependencies ──────────────────────────────
echo [INFO] Installing dependencies ...
"%VENV_DIR%\Scripts\pip.exe" install --upgrade pip -q
"%VENV_DIR%\Scripts\pip.exe" install -r "%REQ_FILE%"

REM ── 4. Verify installation ─────────────────────────────────────────
echo.
echo [INFO] Verifying installation ...
"%VENV_DIR%\Scripts\python.exe" -c "from paddleocr import PaddleOCR; print('  PaddleOCR OK')" 2>nul
"%VENV_DIR%\Scripts\python.exe" -c "from flask import Flask; print('  Flask OK')" 2>nul
"%VENV_DIR%\Scripts\python.exe" -c "from PIL import Image; print('  Pillow OK')" 2>nul

REM ── 5. Print .env config ───────────────────────────────────────────
echo.
echo ============================================================
echo [OK] OCR environment ready.
echo.
echo   Add to .env:
echo     OCR_PYTHON_PATH=%VENV_DIR%\Scripts\python.exe
echo     OCR_TIMEOUT=120
echo     OCR_SERVER_URL=http://127.0.0.1:5000
echo.
echo   Start persistent OCR server (recommended):
echo     %VENV_DIR%\Scripts\python.exe %SCRIPT_DIR%ocr_server.py
echo ============================================================

REM ── 6. Optionally start server ─────────────────────────────────────
if "%START_SERVER%"=="1" (
    echo.
    echo [INFO] Starting OCR server ...
    "%VENV_DIR%\Scripts\python.exe" "%SCRIPT_DIR%ocr_server.py"
)

endlocal
