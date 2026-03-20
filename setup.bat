@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion

:: ============================================================
:: Dental Medical Management System — One-Click Local Setup
:: Supports: Windows 10/11
:: Usage:
::   setup.bat           — Docker mode (default)
::   setup.bat native    — Native mode (requires local PHP/MySQL)
:: ============================================================

set "MODE=%~1"
if "%MODE%"=="" set "MODE=docker"
set "PROJECT_DIR=%~dp0"
cd /d "%PROJECT_DIR%"

echo.
echo ========================================
echo  Dental Medical Management System Setup
echo  Mode: %MODE%
echo ========================================
echo.

:: ── Step 1: Environment File ──────────────────────────────
if not exist .env (
    echo [INFO]  Creating .env from .env.example ...
    copy .env.example .env >nul

    if "%MODE%"=="docker" (
        powershell -Command "(Get-Content .env) -replace '^DB_HOST=.*','DB_HOST=mysql' -replace '^DB_PASSWORD=.*','DB_PASSWORD=secret' -replace '^DB_DATABASE=.*','DB_DATABASE=pristine_dental' -replace '^CACHE_DRIVER=.*','CACHE_DRIVER=redis' -replace '^QUEUE_CONNECTION=.*','QUEUE_CONNECTION=redis' -replace '^REDIS_HOST=.*','REDIS_HOST=redis' | Set-Content .env"
    )
    echo [OK]    .env created
) else (
    echo [OK]    .env already exists, skipping
)

if "%MODE%"=="docker" goto :docker_mode
if "%MODE%"=="native" goto :native_mode
echo [FAIL]  Unknown mode: %MODE% (use 'docker' or 'native')
exit /b 1

:: ── Docker Mode ───────────────────────────────────────────
:docker_mode
where docker >nul 2>&1
if %errorlevel% neq 0 (
    echo [FAIL]  Docker not found. Install Docker Desktop: https://docs.docker.com/desktop/install/windows-install/
    exit /b 1
)

echo [INFO]  Building and starting containers ...
docker compose up -d --build
if %errorlevel% neq 0 (
    echo [FAIL]  docker compose up failed
    exit /b 1
)

echo [INFO]  Waiting for MySQL to be ready ...
set retries=30
:wait_mysql
docker compose exec mysql mysqladmin ping -h localhost -psecret --silent >nul 2>&1
if %errorlevel%==0 goto :mysql_ready
set /a retries-=1
if %retries% leq 0 (
    echo [FAIL]  MySQL did not start in time
    exit /b 1
)
timeout /t 2 /nobreak >nul
goto :wait_mysql

:mysql_ready
echo [OK]    MySQL is ready

echo [INFO]  Installing Composer dependencies ...
docker compose exec app composer install --no-interaction --prefer-dist

echo [INFO]  Generating application key ...
docker compose exec app php artisan key:generate --force

echo [INFO]  Running database migrations ...
docker compose exec app php artisan migrate --force

echo [INFO]  Seeding database ...
docker compose exec app php artisan db:seed --force

echo [INFO]  Setting storage permissions ...
docker compose exec app chmod -R 775 storage bootstrap/cache

echo [INFO]  Clearing caches ...
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear

REM OCR environment (runs on host)
call :setup_ocr

echo.
echo [OK]    Docker setup complete!
echo.
echo   Application:  http://localhost
echo   MySQL:        localhost:3306  (root / secret)
echo   Redis:        localhost:6379
echo.
echo   Useful commands:
echo     docker compose logs -f app      View app logs
echo     docker compose exec app bash     Enter container
echo     docker compose down              Stop all
echo     docker compose down -v           Stop + delete data
goto :eof

:: ── Native Mode ───────────────────────────────────────────
:native_mode
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [FAIL]  PHP not found. Install from https://windows.php.net/download/
    echo         Or use Laragon: https://laragon.org/download/
    exit /b 1
)

php -r "echo 'PHP ' . PHP_VERSION;" & echo.

where composer >nul 2>&1
if %errorlevel% neq 0 (
    echo [FAIL]  Composer not found. Install: https://getcomposer.org/download/
    exit /b 1
)

echo [INFO]  Installing Composer dependencies ...
composer install --no-interaction --prefer-dist

echo [INFO]  Generating application key ...
php artisan key:generate --force

echo [INFO]  Running database migrations ...
php artisan migrate --force

echo [INFO]  Seeding database ...
php artisan db:seed --force

echo [INFO]  Clearing caches ...
php artisan config:clear
php artisan cache:clear
php artisan view:clear

where npm >nul 2>&1
if %errorlevel%==0 (
    echo [INFO]  Installing NPM dependencies ...
    npm install
    echo [INFO]  Building frontend assets ...
    npm run dev
    echo [OK]    Frontend built
) else (
    echo [WARN]  npm not found, skipping frontend build
)

REM OCR environment
call :setup_ocr

echo.
echo [OK]    Native setup complete!
echo.
echo [INFO]  Starting development server ...
echo   Application:  http://localhost:8000
echo   Press Ctrl+C to stop.
echo.
php artisan serve
goto :eof

:: ── OCR Environment Setup ────────────────────────────────────
:setup_ocr
set "OCR_VENV=%PROJECT_DIR%scripts\venv"
set "OCR_PY="

REM Find Python 3
where py >nul 2>&1
if %errorlevel%==0 (
    set "OCR_PY=py -3"
    goto :ocr_found_python
)
for %%p in (python3 python) do (
    where %%p >nul 2>&1
    if !errorlevel!==0 (
        %%p --version 2>nul | findstr /r "3\.[0-9]" >nul
        if !errorlevel!==0 (
            set "OCR_PY=%%p"
            goto :ocr_found_python
        ) else (
            REM Not Python 3
        )
    )
)
echo [WARN]  Python 3 not found, skipping OCR setup (OCR feature will be unavailable)
goto :eof

:ocr_found_python
echo [INFO]  Setting up OCR environment ...

if not exist "%OCR_VENV%" (
    %OCR_PY% -m venv "%OCR_VENV%"
)

"%OCR_VENV%\Scripts\pip.exe" install --upgrade pip -q
"%OCR_VENV%\Scripts\pip.exe" install -r "%PROJECT_DIR%scripts\requirements.txt" -q

REM Add OCR config to .env if missing
findstr /b "OCR_PYTHON_PATH=" .env >nul 2>&1
if %errorlevel% neq 0 (
    echo.>> .env
    echo # OCR Service>> .env
    echo OCR_PYTHON_PATH=%OCR_VENV%\Scripts\python.exe>> .env
    echo OCR_TIMEOUT=120>> .env
    echo OCR_SERVER_URL=http://127.0.0.1:5000>> .env
)

echo [OK]    OCR environment ready
echo          Start OCR server: %OCR_VENV%\Scripts\python.exe %PROJECT_DIR%scripts\ocr_server.py
goto :eof
