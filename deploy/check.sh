#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════
#  牙科诊所管理系统 - 系统健康检查脚本
#  Dental Clinic Management System — Health Check Script
#
#  用法:
#    bash deploy/check.sh [选项]
#
#  选项:
#    --install-dir DIR    安装目录 (默认: /opt/dental, Windows Git Bash 自动检测)
#    --help               显示帮助
#
#  兼容: Linux / macOS / Windows Git Bash
# ═══════════════════════════════════════════════════════════════════════
set -euo pipefail

# ── 颜色输出 ────────────────────────────────────────────────────────
if [[ -t 1 ]]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    CYAN='\033[0;36m'
    BOLD='\033[1m'
    DIM='\033[2m'
    NC='\033[0m'
else
    RED='' GREEN='' YELLOW='' CYAN='' BOLD='' DIM='' NC=''
fi

ok()   { echo -e "  ${GREEN}✓${NC} $*"; }
fail() { echo -e "  ${RED}✗${NC} $*"; }
warn() { echo -e "  ${YELLOW}⚠${NC} $*"; }
info() { echo -e "  ${CYAN}→${NC} $*"; }

step() {
    CURRENT_STEP=$((CURRENT_STEP + 1))
    echo ""
    echo -e "${BOLD}[${CURRENT_STEP}/${TOTAL_STEPS}] $*${NC}"
}

CURRENT_STEP=0
TOTAL_STEPS=10

# ── 统计计数器 ────────────────────────────────────────────────────────
COUNT_OK=0
COUNT_FAIL=0
COUNT_WARN=0

count_ok()   { COUNT_OK=$((COUNT_OK + 1));   ok "$@"; }
count_fail() { COUNT_FAIL=$((COUNT_FAIL + 1)); fail "$@"; }
count_warn() { COUNT_WARN=$((COUNT_WARN + 1)); warn "$@"; }

# ── 检测操作系统与默认路径 ────────────────────────────────────────────
detect_os() {
    case "$(uname -s)" in
        Linux*)  OS_TYPE="linux" ;;
        Darwin*) OS_TYPE="macos" ;;
        MINGW*|MSYS*|CYGWIN*)
            OS_TYPE="windows"
            ;;
        *)
            OS_TYPE="unknown"
            ;;
    esac
}

detect_os

HAS_SYSTEMCTL=0
if [[ "$OS_TYPE" == "linux" ]] && command -v systemctl &>/dev/null; then
    HAS_SYSTEMCTL=1
fi

# ── 默认安装目录 ──────────────────────────────────────────────────────
if [[ "$OS_TYPE" == "windows" ]]; then
    # Windows Git Bash: auto-detect from script location
    SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
    DEFAULT_INSTALL_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
else
    DEFAULT_INSTALL_DIR="/opt/dental"
fi

INSTALL_DIR="$DEFAULT_INSTALL_DIR"

# ── 解析命令行参数 ────────────────────────────────────────────────────
show_help() {
    echo "牙科诊所管理系统 — 系统健康检查"
    echo ""
    echo "用法: $0 [选项]"
    echo ""
    echo "选项:"
    echo "  --install-dir DIR    安装目录 (默认: $DEFAULT_INSTALL_DIR)"
    echo "  --help               显示此帮助"
    exit 0
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --install-dir) INSTALL_DIR="$2"; shift 2 ;;
        --help|-h)     show_help ;;
        *)
            echo "未知选项: $1"
            echo "使用 --help 查看帮助"
            exit 1
            ;;
    esac
done

# ── 读取版本 ──────────────────────────────────────────────────────────
VERSION="unknown"
if [[ -f "$INSTALL_DIR/VERSION" ]]; then
    VERSION="$(tr -d '[:space:]' < "$INSTALL_DIR/VERSION")"
fi

# ═══════════════════════════════════════════════════════════════════════
#  开始检查
# ═══════════════════════════════════════════════════════════════════════
echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║   牙科诊所管理系统 v${VERSION} — 系统健康检查              ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  检查目录: ${CYAN}${INSTALL_DIR}${NC}"
echo -e "  操作系统: ${CYAN}${OS_TYPE}${NC}"
echo -e "  检查时间: ${CYAN}$(date '+%Y-%m-%d %H:%M:%S')${NC}"

# ── Step 1: PHP 版本与扩展 ────────────────────────────────────────────
step "检查 PHP 版本与扩展..."

if command -v php &>/dev/null; then
    PHP_FULL_VERSION=$(php -r "echo phpversion();" 2>/dev/null || echo "unknown")
    PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;" 2>/dev/null || echo 0)
    PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;" 2>/dev/null || echo 0)

    if [[ "$PHP_MAJOR" -gt 8 ]] || { [[ "$PHP_MAJOR" -eq 8 ]] && [[ "$PHP_MINOR" -ge 2 ]]; }; then
        count_ok "PHP $PHP_FULL_VERSION"
    else
        count_fail "PHP $PHP_FULL_VERSION (需要 8.2+)"
    fi

    # 检查必需扩展
    REQUIRED_EXTS=(pdo_mysql mbstring openssl tokenizer xml ctype json bcmath gd zip)
    MISSING_EXTS=()
    for ext in "${REQUIRED_EXTS[@]}"; do
        if ! php -m 2>/dev/null | grep -qi "^${ext}$"; then
            MISSING_EXTS+=("$ext")
        fi
    done

    if [[ ${#MISSING_EXTS[@]} -eq 0 ]]; then
        count_ok "PHP 扩展完整 (${REQUIRED_EXTS[*]})"
    else
        count_warn "缺少 PHP 扩展: ${MISSING_EXTS[*]}"
    fi
else
    count_fail "PHP 未安装"
fi

# ── Step 2: MySQL 连接与版本 ──────────────────────────────────────────
step "检查 MySQL 连接与版本..."

# 从 .env 读取数据库配置
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_USER="root"
DB_PASS=""
DB_NAME="pristine_dental"

if [[ -f "$INSTALL_DIR/.env" ]]; then
    DB_HOST=$(grep -E '^DB_HOST=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "127.0.0.1")
    DB_PORT=$(grep -E '^DB_PORT=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "3306")
    DB_USER=$(grep -E '^DB_USERNAME=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "root")
    DB_PASS=$(grep -E '^DB_PASSWORD=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "")
    DB_NAME=$(grep -E '^DB_DATABASE=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "pristine_dental")
fi

MYSQL_CMD=""
if command -v mysql &>/dev/null; then
    MYSQL_CMD="mysql"
elif command -v mariadb &>/dev/null; then
    MYSQL_CMD="mariadb"
fi

if [[ -n "$MYSQL_CMD" ]]; then
    MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
    if [[ -n "$DB_PASS" ]]; then
        MYSQL_ARGS+=(-p"$DB_PASS")
    fi

    # 测试连接
    if $MYSQL_CMD "${MYSQL_ARGS[@]}" -e "SELECT 1" &>/dev/null; then
        MYSQL_VER=$($MYSQL_CMD "${MYSQL_ARGS[@]}" -e "SELECT VERSION();" -sN 2>/dev/null || echo "unknown")
        count_ok "MySQL 连接正常 (v${MYSQL_VER}, ${DB_HOST}:${DB_PORT})"

        # 检查数据库是否存在
        if $MYSQL_CMD "${MYSQL_ARGS[@]}" -e "USE \`${DB_NAME}\`;" &>/dev/null; then
            TABLE_COUNT=$($MYSQL_CMD "${MYSQL_ARGS[@]}" -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}';" -sN 2>/dev/null || echo "?")
            count_ok "数据库 ${DB_NAME} 存在 (${TABLE_COUNT} 张表)"
        else
            count_warn "数据库 ${DB_NAME} 不存在"
        fi
    else
        count_fail "MySQL 连接失败 (${DB_HOST}:${DB_PORT})"
    fi
else
    count_fail "MySQL/MariaDB 客户端未安装"
fi

# ── Step 3: Web 服务器状态 ────────────────────────────────────────────
step "检查 Web 服务器..."

WEBSERVER_FOUND=false

# Nginx
if command -v nginx &>/dev/null; then
    if pgrep -x nginx &>/dev/null 2>&1 || { [[ "$HAS_SYSTEMCTL" -eq 1 ]] && systemctl is-active nginx &>/dev/null 2>&1; }; then
        NGINX_VER=$(nginx -v 2>&1 | sed -n 's/.*\/\([0-9.]*\).*/\1/p' || echo "unknown")
        count_ok "Nginx 运行中 (v${NGINX_VER})"
        WEBSERVER_FOUND=true
    else
        count_warn "Nginx 已安装但未运行"
        WEBSERVER_FOUND=true
    fi
fi

# Apache
if command -v apache2 &>/dev/null || command -v httpd &>/dev/null; then
    APACHE_CMD=""
    if command -v apache2 &>/dev/null; then APACHE_CMD="apache2"; fi
    if command -v httpd &>/dev/null; then APACHE_CMD="httpd"; fi

    if pgrep -x "$APACHE_CMD" &>/dev/null 2>&1 || pgrep -x apache2 &>/dev/null 2>&1 || pgrep -x httpd &>/dev/null 2>&1 || { [[ "$HAS_SYSTEMCTL" -eq 1 ]] && systemctl is-active apache2 &>/dev/null 2>&1; } || { [[ "$HAS_SYSTEMCTL" -eq 1 ]] && systemctl is-active httpd &>/dev/null 2>&1; }; then
        count_ok "Apache 运行中"
        WEBSERVER_FOUND=true
    else
        count_warn "Apache 已安装但未运行"
        WEBSERVER_FOUND=true
    fi
fi

if ! $WEBSERVER_FOUND; then
    count_warn "未检测到 Nginx 或 Apache（开发环境可使用 php artisan serve）"
fi

# ── Step 4: Laravel 状态 ──────────────────────────────────────────────
step "检查 Laravel..."

if [[ -f "$INSTALL_DIR/artisan" ]]; then
    count_ok "artisan 文件存在"

    # 尝试 php artisan about，回退到 --version
    cd "$INSTALL_DIR" 2>/dev/null || true
    if php artisan about &>/dev/null 2>&1; then
        LARAVEL_VER=$(php artisan about 2>/dev/null | grep -i "Laravel Version" | head -1 || echo "")
        if [[ -n "$LARAVEL_VER" ]]; then
            count_ok "Laravel: $LARAVEL_VER"
        else
            LARAVEL_VER=$(php artisan --version 2>/dev/null || echo "unknown")
            count_ok "Laravel: $LARAVEL_VER"
        fi
    else
        LARAVEL_VER=$(php artisan --version 2>/dev/null || echo "")
        if [[ -n "$LARAVEL_VER" ]]; then
            count_ok "Laravel: $LARAVEL_VER"
        else
            count_fail "Laravel artisan 无法执行（可能缺少依赖或配置错误）"
        fi
    fi

    # 检查 vendor 目录
    if [[ -d "$INSTALL_DIR/vendor" ]]; then
        count_ok "vendor/ 目录存在"
    else
        count_fail "vendor/ 目录缺失（请运行 composer install）"
    fi
else
    count_fail "artisan 文件不存在于 $INSTALL_DIR"
fi

# ── Step 5: .env 配置检查 ─────────────────────────────────────────────
step "检查 .env 配置..."

if [[ -f "$INSTALL_DIR/.env" ]]; then
    count_ok ".env 文件存在"

    # APP_KEY
    APP_KEY=$(grep -E '^APP_KEY=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "")
    if [[ -n "$APP_KEY" ]] && [[ "$APP_KEY" != "=" ]]; then
        count_ok "APP_KEY 已设置"
    else
        count_fail "APP_KEY 未设置（请运行 php artisan key:generate）"
    fi

    # APP_DEBUG
    APP_DEBUG=$(grep -E '^APP_DEBUG=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "")
    APP_ENV=$(grep -E '^APP_ENV=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "")
    if [[ "$APP_ENV" == "production" ]]; then
        if [[ "$APP_DEBUG" == "false" ]]; then
            count_ok "APP_DEBUG=false (生产环境正确)"
        else
            count_warn "APP_DEBUG=$APP_DEBUG (生产环境应设为 false)"
        fi
    else
        count_ok "APP_ENV=$APP_ENV, APP_DEBUG=$APP_DEBUG"
    fi

    # QUEUE_CONNECTION
    QUEUE_CONN=$(grep -E '^QUEUE_CONNECTION=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "sync")
    info "QUEUE_CONNECTION=$QUEUE_CONN"

else
    count_fail ".env 文件不存在"
fi

# ── Step 6: 目录权限 ──────────────────────────────────────────────────
step "检查目录权限..."

check_writable() {
    local dir="$1"
    local label="$2"
    if [[ -d "$dir" ]]; then
        if [[ -w "$dir" ]]; then
            count_ok "$label 可写"
        else
            count_fail "$label 不可写 ($dir)"
        fi
    else
        count_fail "$label 目录不存在 ($dir)"
    fi
}

check_writable "$INSTALL_DIR/storage"               "storage/"
check_writable "$INSTALL_DIR/storage/logs"           "storage/logs/"
check_writable "$INSTALL_DIR/storage/framework"      "storage/framework/"
check_writable "$INSTALL_DIR/storage/app"            "storage/app/"
check_writable "$INSTALL_DIR/bootstrap/cache"        "bootstrap/cache/"

# ── Step 7: 磁盘空间 ─────────────────────────────────────────────────
step "检查磁盘空间..."

# 获取安装目录所在分区的磁盘使用率
if command -v df &>/dev/null; then
    DISK_USAGE=$(df -h "$INSTALL_DIR" 2>/dev/null | tail -1 || echo "")
    if [[ -n "$DISK_USAGE" ]]; then
        DISK_PCT=$(echo "$DISK_USAGE" | awk '{print $5}' | tr -d '%')
        DISK_AVAIL=$(echo "$DISK_USAGE" | awk '{print $4}')

        if [[ "$DISK_PCT" -lt 80 ]]; then
            count_ok "磁盘使用 ${DISK_PCT}% (可用 ${DISK_AVAIL})"
        elif [[ "$DISK_PCT" -lt 90 ]]; then
            count_warn "磁盘使用 ${DISK_PCT}% (可用 ${DISK_AVAIL}) — 建议清理"
        else
            count_fail "磁盘使用 ${DISK_PCT}% (可用 ${DISK_AVAIL}) — 空间不足！"
        fi
    else
        count_warn "无法获取磁盘信息"
    fi
else
    count_warn "df 命令不可用"
fi

# ── Step 8: OCR 服务 ─────────────────────────────────────────────────
step "检查 OCR 服务..."

OCR_URL="http://127.0.0.1:5000"
if [[ -f "$INSTALL_DIR/.env" ]]; then
    ENV_OCR_URL=$(grep -E '^OCR_SERVER_URL=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "")
    if [[ -n "$ENV_OCR_URL" ]]; then
        OCR_URL="$ENV_OCR_URL"
    fi
fi

if command -v curl &>/dev/null; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 3 --max-time 5 "${OCR_URL}/health" 2>/dev/null || echo "000")
    if [[ "$HTTP_CODE" == "200" ]]; then
        count_ok "OCR 服务运行中 (${OCR_URL})"
    else
        count_warn "OCR 服务未响应 (${OCR_URL}, HTTP ${HTTP_CODE}) — OCR 功能不可用"
    fi
else
    count_warn "curl 不可用，跳过 OCR 服务检查"
fi

# 检查 OCR Python 环境
VENV_DIR="$INSTALL_DIR/scripts/venv"
if [[ -f "$VENV_DIR/bin/python3" ]] || [[ -f "$VENV_DIR/Scripts/python.exe" ]]; then
    count_ok "OCR Python 虚拟环境已安装"
else
    info "OCR Python 虚拟环境未安装（可选功能）"
fi

# ── Step 9: 队列 Worker ──────────────────────────────────────────────
step "检查队列 Worker..."

QUEUE_RUNNING=false

# 方法 1: 检查 artisan queue:work 进程
if pgrep -f "artisan.*queue:work" &>/dev/null 2>&1; then
    QUEUE_PID=$(pgrep -f "artisan.*queue:work" 2>/dev/null | head -1)
    count_ok "队列 Worker 运行中 (PID: ${QUEUE_PID})"
    QUEUE_RUNNING=true
fi

# 方法 2: 检查 supervisor (常见部署方式)
if ! $QUEUE_RUNNING && command -v supervisorctl &>/dev/null; then
    if supervisorctl status 2>/dev/null | grep -qi "queue\|worker\|dental"; then
        SUPERVISOR_STATUS=$(supervisorctl status 2>/dev/null | grep -i "queue\|worker\|dental" | head -1)
        count_ok "Supervisor 队列: $SUPERVISOR_STATUS"
        QUEUE_RUNNING=true
    fi
fi

# 方法 3: 检查 systemd service
if ! $QUEUE_RUNNING && [[ "$HAS_SYSTEMCTL" -eq 1 ]]; then
    for svc in dental-worker dental-queue laravel-worker; do
        if systemctl is-active "$svc" &>/dev/null 2>&1; then
            count_ok "Systemd 队列服务 ${svc} 运行中"
            QUEUE_RUNNING=true
            break
        fi
    done
fi

if ! $QUEUE_RUNNING; then
    QUEUE_CONN=$(grep -E '^QUEUE_CONNECTION=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]' || echo "sync")
    if [[ "$QUEUE_CONN" == "sync" ]]; then
        count_ok "队列使用同步模式 (QUEUE_CONNECTION=sync)"
    else
        count_warn "队列 Worker 未运行 (QUEUE_CONNECTION=${QUEUE_CONN})"
    fi
fi

# ── Step 10: 定时任务 / Scheduler ────────────────────────────────────
step "检查定时任务 (Scheduler)..."

CRON_FOUND=false

# 检查当前用户 crontab
if command -v crontab &>/dev/null; then
    CRON_CONTENT=$(crontab -l 2>/dev/null || echo "")
    if echo "$CRON_CONTENT" | grep -q "artisan.*schedule:run"; then
        count_ok "Cron 定时任务已配置 (当前用户)"
        CRON_FOUND=true
    fi
fi

# 检查 /etc/crontab
if ! $CRON_FOUND && [[ -f /etc/crontab ]]; then
    if grep -q "artisan.*schedule:run" /etc/crontab 2>/dev/null; then
        count_ok "Cron 定时任务已配置 (/etc/crontab)"
        CRON_FOUND=true
    fi
fi

# 检查 /etc/cron.d/ 下的文件
if ! $CRON_FOUND && [[ -d /etc/cron.d ]]; then
    if grep -rq "artisan.*schedule:run" /etc/cron.d/ 2>/dev/null; then
        count_ok "Cron 定时任务已配置 (/etc/cron.d/)"
        CRON_FOUND=true
    fi
fi

# 检查 systemd timer
if ! $CRON_FOUND && [[ "$HAS_SYSTEMCTL" -eq 1 ]]; then
    for timer in dental-scheduler laravel-scheduler; do
        if systemctl is-active "${timer}.timer" &>/dev/null 2>&1; then
            count_ok "Systemd 定时器 ${timer} 运行中"
            CRON_FOUND=true
            break
        fi
    done
fi

if ! $CRON_FOUND; then
    count_warn "未检测到 Laravel Scheduler 定时任务"
    info "建议添加: * * * * * cd $INSTALL_DIR && php artisan schedule:run >> /dev/null 2>&1"
fi

# ═══════════════════════════════════════════════════════════════════════
#  汇总报告
# ═══════════════════════════════════════════════════════════════════════
echo ""
echo -e "${BOLD}══════════════════════════════════════════════════════════${NC}"
echo -e "${BOLD}  检查汇总${NC}"
echo -e "${BOLD}══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${GREEN}✓ 通过: ${COUNT_OK}${NC}"
echo -e "  ${RED}✗ 失败: ${COUNT_FAIL}${NC}"
echo -e "  ${YELLOW}⚠ 警告: ${COUNT_WARN}${NC}"
echo ""

if [[ "$COUNT_FAIL" -eq 0 ]] && [[ "$COUNT_WARN" -eq 0 ]]; then
    echo -e "  ${GREEN}${BOLD}系统状态: 一切正常${NC}"
elif [[ "$COUNT_FAIL" -eq 0 ]]; then
    echo -e "  ${YELLOW}${BOLD}系统状态: 基本正常，有 ${COUNT_WARN} 项警告需关注${NC}"
else
    echo -e "  ${RED}${BOLD}系统状态: 存在 ${COUNT_FAIL} 项故障需修复${NC}"
fi

echo ""

# 退出码: 0=全部通过, 1=有失败
if [[ "$COUNT_FAIL" -gt 0 ]]; then
    exit 1
else
    exit 0
fi
