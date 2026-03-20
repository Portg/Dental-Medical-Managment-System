#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════
#  牙科诊所管理系统 - Linux/macOS 服务启动脚本
#  用途: 依次启动 MySQL、Nginx、OCR 服务、队列工作进程
#  用法:
#    chmod +x deploy/start-linux.sh
#    ./deploy/start-linux.sh
#    ./deploy/start-linux.sh --install-dir /opt/dental
#    ./deploy/start-linux.sh --port 8080
# ═══════════════════════════════════════════════════════════════
set -euo pipefail

# ── 颜色定义 ──────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

info()  { echo -e "${CYAN}[INFO]${NC}  $*"; }
ok()    { echo -e "${GREEN}[ OK ]${NC}  $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
fail()  { echo -e "${RED}[FAIL]${NC}  $*"; }
die()   { fail "$*"; exit 1; }

# ── 参数解析 ──────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
INSTALL_DIR=""
APP_PORT=8000
OCR_PORT=5000
MYSQL_WAIT_MAX=30

while [[ $# -gt 0 ]]; do
    case "$1" in
        --install-dir)  INSTALL_DIR="$2"; shift 2 ;;
        --port)         APP_PORT="$2"; shift 2 ;;
        --ocr-port)     OCR_PORT="$2"; shift 2 ;;
        -h|--help)
            echo "用法: $0 [选项]"
            echo "  --install-dir DIR   项目安装目录 (默认: 脚本上级目录)"
            echo "  --port PORT         Web 服务器端口 (默认: 8000)"
            echo "  --ocr-port PORT     OCR 服务端口 (默认: 5000)"
            exit 0
            ;;
        *)  die "未知参数: $1 (使用 -h 查看帮助)" ;;
    esac
done

# 项目根目录: 默认为脚本所在 deploy/ 的上级目录
if [[ -z "$INSTALL_DIR" ]]; then
    INSTALL_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
fi

if [[ ! -f "$INSTALL_DIR/artisan" ]]; then
    die "项目目录无效: $INSTALL_DIR (未找到 artisan)"
fi

PID_DIR="$INSTALL_DIR/storage/pids"
LOG_DIR="$INSTALL_DIR/storage/logs"
mkdir -p "$PID_DIR" "$LOG_DIR"

# 从 .env 读取已有配置，避免脚本默认值与实际安装配置不一致
if [[ -f "$INSTALL_DIR/.env" ]]; then
    ENV_DB_PORT=$(grep -E '^DB_PORT=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '[:space:]\r' || true)
    ENV_APP_URL=$(grep -E '^APP_URL=' "$INSTALL_DIR/.env" 2>/dev/null | cut -d'=' -f2- | tr -d '\r' || true)
    if [[ -n "${ENV_DB_PORT:-}" ]]; then
        DB_PORT="$ENV_DB_PORT"
    else
        DB_PORT="3306"
    fi
    if [[ -n "${ENV_APP_URL:-}" ]]; then
        APP_URL="$ENV_APP_URL"
    fi
else
    DB_PORT="3306"
fi

# ── 平台检测 ──────────────────────────────────────────────────
detect_platform() {
    case "$(uname -s)" in
        Darwin*)  PLATFORM="macos" ;;
        Linux*)   PLATFORM="linux" ;;
        *)        PLATFORM="unknown" ;;
    esac
}
detect_platform

# ── 检测 systemd 是否可用 ────────────────────────────────────
HAS_SYSTEMCTL=0
if command -v systemctl &>/dev/null && [[ "$PLATFORM" == "linux" ]]; then
    # 确认 systemd 是否真正在运行（非容器环境）
    if systemctl is-system-running &>/dev/null || [[ "$(systemctl is-system-running 2>/dev/null)" == "running" ]] || [[ "$(systemctl is-system-running 2>/dev/null)" == "degraded" ]]; then
        HAS_SYSTEMCTL=1
    fi
fi

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║      牙科诊所管理系统 - 启动服务                ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""
info "平台: $PLATFORM"
info "项目目录: $INSTALL_DIR"
info "PID 目录: $PID_DIR"
info "Systemd: $(if [[ $HAS_SYSTEMCTL -eq 1 ]]; then echo '可用'; else echo '不可用'; fi)"
echo ""

# ── 辅助函数 ──────────────────────────────────────────────────
is_port_open() {
    local port="$1"
    if command -v ss &>/dev/null; then
        ss -tlnp 2>/dev/null | grep -q ":${port} " && return 0
    elif command -v lsof &>/dev/null; then
        lsof -iTCP:"$port" -sTCP:LISTEN &>/dev/null && return 0
    elif command -v netstat &>/dev/null; then
        netstat -tlnp 2>/dev/null | grep -q ":${port} " && return 0
    fi
    return 1
}

write_pid() {
    local name="$1" pid="$2"
    echo "$pid" > "$PID_DIR/${name}.pid"
}

check_pid_alive() {
    local pid_file="$PID_DIR/${1}.pid"
    if [[ -f "$pid_file" ]]; then
        local pid
        pid=$(cat "$pid_file")
        if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
            return 0
        fi
    fi
    return 1
}

# ── 状态变量 ──────────────────────────────────────────────────
MYSQL_OK=0
WEB_OK=0
WEB_MODE="none"
OCR_OK=0
QUEUE_OK=0
APP_URL="${APP_URL:-http://localhost:${APP_PORT}}"

# ══════════════════════════════════════════════════════════════
#  Step 1/6: 检测工具
# ══════════════════════════════════════════════════════════════
info "[1/6] 检测运行环境..."

if ! command -v php &>/dev/null; then
    die "未找到 PHP，请安装 PHP 8.2+"
fi
ok "PHP .............. $(php -r 'echo PHP_VERSION;')"

MYSQL_CLIENT=""
if command -v mysql &>/dev/null; then
    MYSQL_CLIENT="mysql"
    ok "MySQL 客户端 ...... 已找到"
else
    warn "MySQL 客户端未找到，无法检测数据库状态"
fi

HAS_NGINX=0
if command -v nginx &>/dev/null; then
    HAS_NGINX=1
    ok "Nginx ............. 已找到"
fi
echo ""

# ══════════════════════════════════════════════════════════════
#  Step 2/6: 启动 MySQL
# ══════════════════════════════════════════════════════════════
info "[2/6] 启动 MySQL 数据库..."

# 检查 MySQL 是否已运行
mysql_is_running() {
    pgrep -x mysqld &>/dev/null && return 0
    is_port_open "$DB_PORT" && return 0
    return 1
}

if mysql_is_running; then
    ok "MySQL 已在运行"
    MYSQL_OK=1
else
    # 方式1: systemctl（优先）
    if [[ "$HAS_SYSTEMCTL" -eq 1 ]]; then
        info "使用 systemctl 启动 MySQL..."
        if systemctl list-unit-files mysql.service &>/dev/null 2>&1; then
            sudo systemctl start mysql 2>/dev/null && MYSQL_OK=1
        elif systemctl list-unit-files mariadb.service &>/dev/null 2>&1; then
            sudo systemctl start mariadb 2>/dev/null && MYSQL_OK=1
        elif systemctl list-unit-files mysqld.service &>/dev/null 2>&1; then
            sudo systemctl start mysqld 2>/dev/null && MYSQL_OK=1
        fi
    fi

    # 方式2: macOS Homebrew
    if [[ "$MYSQL_OK" -eq 0 ]] && [[ "$PLATFORM" == "macos" ]]; then
        if command -v brew &>/dev/null; then
            info "使用 Homebrew 启动 MySQL..."
            brew services start mysql 2>/dev/null || brew services start mysql@8.0 2>/dev/null || true
            MYSQL_OK=1
        fi
    fi

    # 方式3: service 命令（无 systemd 的 Linux）
    if [[ "$MYSQL_OK" -eq 0 ]] && command -v service &>/dev/null; then
        info "使用 service 启动 MySQL..."
        sudo service mysql start 2>/dev/null && MYSQL_OK=1 || true
    fi

    # 方式4: 手动启动 mysqld_safe / mysqld
    if [[ "$MYSQL_OK" -eq 0 ]]; then
        warn "无法通过服务管理器启动 MySQL，尝试直接启动..."
        if command -v mysqld_safe &>/dev/null; then
            mysqld_safe &>/dev/null &
            write_pid "mysql" $!
            MYSQL_OK=1
        elif command -v mysqld &>/dev/null; then
            mysqld &>/dev/null &
            write_pid "mysql" $!
            MYSQL_OK=1
        fi
    fi

    if [[ "$MYSQL_OK" -eq 0 ]]; then
        die "无法启动 MySQL，请手动启动数据库后重试"
    fi
fi

# 等待 MySQL 就绪
if [[ "$MYSQL_OK" -eq 1 ]]; then
    WAIT=0
    while ! mysql_is_running; do
        WAIT=$((WAIT + 2))
        if [[ $WAIT -ge $MYSQL_WAIT_MAX ]]; then
            die "MySQL 启动超时（等待 ${MYSQL_WAIT_MAX} 秒），请检查数据库配置"
        fi
        info "等待 MySQL 就绪... (${WAIT}/${MYSQL_WAIT_MAX} 秒)"
        sleep 2
    done
    ok "MySQL 已就绪"
fi
echo ""

# ══════════════════════════════════════════════════════════════
#  Step 3/6: 启动 Nginx（或 PHP 内置服务器作为备选）
# ══════════════════════════════════════════════════════════════
info "[3/6] 启动 Web 服务器..."

# 检查 Nginx 是否已在运行
if pgrep -x nginx &>/dev/null; then
    ok "Nginx 已在运行"
    WEB_OK=1
    WEB_MODE="nginx"
    APP_URL="http://localhost"
fi

# 检查 PHP 内置服务器是否已在运行
if [[ "$WEB_OK" -eq 0 ]] && check_pid_alive "php-server"; then
    ok "PHP 内置服务器已在运行"
    WEB_OK=1
    WEB_MODE="php-builtin"
fi

if [[ "$WEB_OK" -eq 0 ]]; then
    # 检查是否有 Nginx 配置
    NGINX_CONF=""
    for conf_path in \
        "/etc/nginx/sites-enabled/dental-clinic.conf" \
        "/etc/nginx/sites-enabled/dental" \
        "/etc/nginx/sites-available/dental-clinic.conf" \
        "/etc/nginx/conf.d/dental-clinic.conf" \
        "/etc/nginx/conf.d/dental.conf" \
        "/etc/nginx/dental-clinic.conf" \
        "/usr/local/etc/nginx/servers/dental.conf" \
        "/usr/local/etc/nginx/servers/dental-clinic.conf" \
        "$INSTALL_DIR/deploy/nginx.conf"; do
        if [[ -f "$conf_path" ]]; then
            NGINX_CONF="$conf_path"
            break
        fi
    done

    # 方式1: systemctl 启动 Nginx
    if [[ "$HAS_NGINX" -eq 1 ]] && [[ -n "$NGINX_CONF" ]]; then
        info "启动 Nginx..."
        if [[ "$HAS_SYSTEMCTL" -eq 1 ]]; then
            sudo systemctl start nginx 2>/dev/null || true
        elif [[ "$PLATFORM" == "macos" ]] && command -v brew &>/dev/null; then
            brew services start nginx 2>/dev/null || nginx 2>/dev/null || true
        else
            sudo nginx 2>/dev/null || nginx 2>/dev/null || true
        fi

        sleep 1
        if pgrep -x nginx &>/dev/null; then
            ok "Nginx 已启动"
            WEB_OK=1
            WEB_MODE="nginx"
            APP_URL="http://localhost"
        else
            warn "Nginx 启动失败，使用 PHP 内置服务器"
        fi
    fi

    # 方式2: PHP 内置服务器（备选）
    if [[ "$WEB_OK" -eq 0 ]]; then
        # 检查端口冲突
        if is_port_open "$APP_PORT"; then
            warn "端口 ${APP_PORT} 已被占用，请使用 --port 指定其他端口"
        else
            info "启动 PHP 内置开发服务器 (端口 ${APP_PORT})..."
            cd "$INSTALL_DIR"
            nohup php -S "0.0.0.0:${APP_PORT}" -t public > "$LOG_DIR/php-server.log" 2>&1 &
            PHP_PID=$!
            write_pid "php-server" "$PHP_PID"
            WEB_MODE="php-builtin"

            sleep 1
            if kill -0 "$PHP_PID" 2>/dev/null; then
                ok "PHP 内置服务器已启动 (PID=${PHP_PID}, 端口 ${APP_PORT})"
                WEB_OK=1
            else
                die "PHP 内置服务器启动失败，请检查 $LOG_DIR/php-server.log"
            fi
        fi
    fi
fi
echo ""

# ══════════════════════════════════════════════════════════════
#  Step 4/6: 启动 OCR 服务（可选，需要 Python venv）
# ══════════════════════════════════════════════════════════════
info "[4/6] 启动 OCR 识别服务（可选）..."

# 检测 OCR 虚拟环境（Linux/macOS 路径）
OCR_VENV_PYTHON=""
for venv_python in \
    "$INSTALL_DIR/scripts/venv/bin/python" \
    "$INSTALL_DIR/scripts/venv/bin/python3"; do
    if [[ -x "$venv_python" ]]; then
        OCR_VENV_PYTHON="$venv_python"
        break
    fi
done

OCR_SCRIPT="$INSTALL_DIR/scripts/ocr_server.py"
OCR_PID_FILE="$INSTALL_DIR/scripts/ocr_server.pid"

if [[ -z "$OCR_VENV_PYTHON" ]]; then
    info "Python venv 不存在，跳过 OCR 服务"
elif [[ ! -f "$OCR_SCRIPT" ]]; then
    info "OCR 脚本不存在，跳过"
else
    # 检查 OCR 是否已运行
    if is_port_open "$OCR_PORT"; then
        ok "OCR 服务已在运行（端口 ${OCR_PORT}）"
        OCR_OK=1
    elif [[ -f "$OCR_PID_FILE" ]] && kill -0 "$(cat "$OCR_PID_FILE")" 2>/dev/null; then
        ok "OCR 服务已在运行 (PID=$(cat "$OCR_PID_FILE"))"
        OCR_OK=1
    else
        info "启动 OCR 服务（端口 ${OCR_PORT}）..."
        nohup "$OCR_VENV_PYTHON" "$OCR_SCRIPT" --port "$OCR_PORT" \
            > "$LOG_DIR/ocr-server.log" 2>&1 &
        OCR_PID=$!
        # 保存 PID 到 scripts/ocr_server.pid（符合规范）
        echo "$OCR_PID" > "$OCR_PID_FILE"
        # 也保存到 storage/pids/ 便于统一管理
        write_pid "ocr-server" "$OCR_PID"

        sleep 2
        if kill -0 "$OCR_PID" 2>/dev/null; then
            ok "OCR 服务已启动 (PID=${OCR_PID})"
            OCR_OK=1
        else
            warn "OCR 服务启动失败，请检查 $LOG_DIR/ocr-server.log"
        fi
    fi
fi
echo ""

# ══════════════════════════════════════════════════════════════
#  Step 5/6: 启动 Laravel 队列工作进程
# ══════════════════════════════════════════════════════════════
info "[5/6] 启动 Laravel 队列工作进程..."

QUEUE_PID_FILE="$INSTALL_DIR/storage/queue.pid"

# 检查是否已在运行
if [[ -f "$QUEUE_PID_FILE" ]] && kill -0 "$(cat "$QUEUE_PID_FILE")" 2>/dev/null; then
    ok "队列工作进程已在运行 (PID=$(cat "$QUEUE_PID_FILE"))"
    QUEUE_OK=1
elif pgrep -f "artisan queue:work" &>/dev/null; then
    ok "队列工作进程已在运行"
    QUEUE_OK=1
else
    info "启动队列工作进程..."
    cd "$INSTALL_DIR"
    nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 \
        > "$LOG_DIR/queue-worker.log" 2>&1 &
    QUEUE_PID=$!
    echo "$QUEUE_PID" > "$QUEUE_PID_FILE"
    write_pid "queue-worker" "$QUEUE_PID"

    sleep 1
    if kill -0 "$QUEUE_PID" 2>/dev/null; then
        ok "队列工作进程已启动 (PID=${QUEUE_PID})"
        QUEUE_OK=1
    else
        warn "队列工作进程启动失败，请检查 $LOG_DIR/queue-worker.log"
    fi
fi
echo ""

# ══════════════════════════════════════════════════════════════
#  Step 6/6: 状态汇总
# ══════════════════════════════════════════════════════════════
echo -e "${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║           服务启动状态汇总                       ║${NC}"
echo -e "${BOLD}╠══════════════════════════════════════════════════╣${NC}"
echo -e "${BOLD}║${NC}                                                  ${BOLD}║${NC}"

if [[ "$MYSQL_OK" -eq 1 ]]; then
    echo -e "${BOLD}║${NC}  MySQL ............. ${GREEN}已就绪${NC}                      ${BOLD}║${NC}"
else
    echo -e "${BOLD}║${NC}  MySQL ............. ${RED}未启动${NC}                      ${BOLD}║${NC}"
fi

if [[ "$WEB_MODE" == "nginx" ]]; then
    echo -e "${BOLD}║${NC}  Web 服务器 ........ ${GREEN}Nginx${NC}                       ${BOLD}║${NC}"
elif [[ "$WEB_MODE" == "php-builtin" ]]; then
    echo -e "${BOLD}║${NC}  Web 服务器 ........ ${GREEN}PHP 内置 (:${APP_PORT})${NC}         ${BOLD}║${NC}"
else
    echo -e "${BOLD}║${NC}  Web 服务器 ........ ${RED}未启动${NC}                      ${BOLD}║${NC}"
fi

if [[ "$OCR_OK" -eq 1 ]]; then
    echo -e "${BOLD}║${NC}  OCR 服务 .......... ${GREEN}已启动 (:${OCR_PORT})${NC}          ${BOLD}║${NC}"
else
    echo -e "${BOLD}║${NC}  OCR 服务 .......... ${YELLOW}未配置${NC}                      ${BOLD}║${NC}"
fi

if [[ "$QUEUE_OK" -eq 1 ]]; then
    echo -e "${BOLD}║${NC}  队列工作进程 ...... ${GREEN}运行中${NC}                      ${BOLD}║${NC}"
else
    echo -e "${BOLD}║${NC}  队列工作进程 ...... ${RED}未启动${NC}                      ${BOLD}║${NC}"
fi

echo -e "${BOLD}║${NC}                                                  ${BOLD}║${NC}"
echo -e "${BOLD}║${NC}  访问地址: ${CYAN}${APP_URL}${NC}"
echo -e "${BOLD}║${NC}  停止服务: ${CYAN}./deploy/stop-linux.sh${NC}"
echo -e "${BOLD}║${NC}                                                  ${BOLD}║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""

# 尝试打开浏览器（非 SSH 环境下）
if [[ -n "${DISPLAY:-}" ]] || [[ "$PLATFORM" == "macos" ]]; then
    if [[ "$PLATFORM" == "macos" ]]; then
        open "$APP_URL" 2>/dev/null || true
    elif command -v xdg-open &>/dev/null; then
        xdg-open "$APP_URL" 2>/dev/null || true
    fi
fi
