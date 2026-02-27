#!/usr/bin/env bash
# ============================================================
# Dental Medical Management System — One-Click Local Setup
# Supports: macOS / Linux
# Usage:
#   chmod +x setup.sh
#   ./setup.sh           # Docker mode (default)
#   ./setup.sh native    # Native mode (requires local PHP/MySQL)
# ============================================================
set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

info()  { echo -e "${CYAN}[INFO]${NC}  $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
fail()  { echo -e "${RED}[FAIL]${NC}  $*"; exit 1; }

MODE="${1:-docker}"
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

# ── Step 1: Environment File ──────────────────────────────────
setup_env() {
    if [ ! -f .env ]; then
        info "Creating .env from .env.example ..."
        cp .env.example .env

        if [ "$MODE" = "docker" ]; then
            # Override DB settings for Docker
            sed -i.bak 's/^DB_HOST=.*/DB_HOST=mysql/' .env
            sed -i.bak 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' .env
            sed -i.bak 's/^DB_DATABASE=.*/DB_DATABASE=pristine_dental/' .env
            sed -i.bak 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
            sed -i.bak 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env
            sed -i.bak 's/^REDIS_HOST=.*/REDIS_HOST=redis/' .env
            rm -f .env.bak
        fi
        ok ".env created"
    else
        ok ".env already exists, skipping"
    fi
}

# ── Step 2a: Docker Mode ──────────────────────────────────────
run_docker() {
    # Check Docker
    if ! command -v docker &>/dev/null; then
        fail "Docker not found. Install: https://docs.docker.com/get-docker/"
    fi
    if ! docker info &>/dev/null; then
        fail "Docker daemon not running. Please start Docker Desktop."
    fi

    info "Building and starting containers ..."
    docker compose up -d --build

    info "Waiting for MySQL to be ready ..."
    local retries=30
    while [ $retries -gt 0 ]; do
        if docker compose exec mysql mysqladmin ping -h localhost -psecret --silent 2>/dev/null; then
            break
        fi
        retries=$((retries - 1))
        sleep 2
    done
    [ $retries -eq 0 ] && fail "MySQL did not start in time"
    ok "MySQL is ready"

    info "Installing Composer dependencies ..."
    docker compose exec app composer install --no-interaction --prefer-dist

    info "Generating application key ..."
    docker compose exec app php artisan key:generate --force

    info "Running database migrations ..."
    docker compose exec app php artisan migrate --force

    info "Seeding database ..."
    docker compose exec app php artisan db:seed --force || warn "Seeder had warnings (may be OK if already seeded)"

    info "Setting storage permissions ..."
    docker compose exec app chmod -R 775 storage bootstrap/cache
    docker compose exec app chown -R www-data:www-data storage bootstrap/cache

    info "Clearing caches ..."
    docker compose exec app php artisan config:clear
    docker compose exec app php artisan cache:clear
    docker compose exec app php artisan view:clear

    ok "Docker setup complete!"
    echo ""
    echo -e "  ${GREEN}Application:${NC}  http://localhost"
    echo -e "  ${GREEN}MySQL:${NC}        localhost:3306  (root / secret)"
    echo -e "  ${GREEN}Redis:${NC}        localhost:6379"
    echo ""
    echo -e "  Useful commands:"
    echo -e "    docker compose logs -f app      # View app logs"
    echo -e "    docker compose exec app bash     # Enter container"
    echo -e "    docker compose down              # Stop all"
    echo -e "    docker compose down -v           # Stop + delete data"
}

# ── Step 2b: Native Mode ──────────────────────────────────────
run_native() {
    # Check PHP
    if ! command -v php &>/dev/null; then
        fail "PHP not found. Install: brew install php (macOS) / sudo apt install php8.2 (Linux)"
    fi
    PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    info "PHP version: $PHP_VER"

    # Check required extensions
    for ext in pdo_mysql mbstring xml gd zip bcmath; do
        if ! php -m | grep -qi "$ext"; then
            warn "PHP extension '$ext' not found — may cause issues"
        fi
    done

    # Check Composer
    if ! command -v composer &>/dev/null; then
        fail "Composer not found. Install: https://getcomposer.org/download/"
    fi

    # Check MySQL
    if ! command -v mysql &>/dev/null; then
        warn "MySQL CLI not found. Make sure MySQL 5.7+ is running on port 3306."
    fi

    info "Installing Composer dependencies ..."
    composer install --no-interaction --prefer-dist

    info "Generating application key ..."
    php artisan key:generate --force

    info "Running database migrations ..."
    php artisan migrate --force

    info "Seeding database ..."
    php artisan db:seed --force || warn "Seeder had warnings"

    info "Setting storage permissions ..."
    chmod -R 775 storage bootstrap/cache

    info "Clearing caches ..."
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear

    # Check Node/NPM (optional)
    if command -v npm &>/dev/null; then
        info "Installing NPM dependencies ..."
        npm install
        info "Building frontend assets ..."
        npm run dev
        ok "Frontend built"
    else
        warn "npm not found — skipping frontend build (existing assets in public/ will still work)"
    fi

    ok "Native setup complete!"
    echo ""
    info "Starting development server ..."
    echo -e "  ${GREEN}Application:${NC}  http://localhost:8000"
    echo ""
    echo -e "  Press Ctrl+C to stop."
    echo ""
    php artisan serve
}

# ── Main ──────────────────────────────────────────────────────
echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN} Dental Medical Management System Setup${NC}"
echo -e "${CYAN} Mode: ${YELLOW}${MODE}${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

setup_env

case "$MODE" in
    docker)  run_docker ;;
    native)  run_native ;;
    *)       fail "Unknown mode: $MODE (use 'docker' or 'native')" ;;
esac
