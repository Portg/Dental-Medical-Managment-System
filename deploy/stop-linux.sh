#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════
#  牙科诊所管理系统 - Linux/macOS 服务停止脚本
#  用途: 按反向顺序停止 队列→OCR→Nginx→MySQL
#  策略: SIGTERM → 等待 10 秒 → SIGKILL
#  用法:
#    chmod +x deploy/stop-linux.sh
#    ./deploy/stop-linux.sh
#    ./deploy/stop-linux.sh --install-dir /opt/dental
#    ./deploy/stop-linux.sh --keep-mysql
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

# ── 参数解析 ──────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
INSTALL_DIR=""
KEEP_MYSQL=0
GRACEFUL_TIMEOUT=10

while [[ $# -gt 0 ]]; do
    case "$1" in
        --install-dir)  INSTALL_DIR="$2"; shift 2 ;;
        --keep-mysql)   KEEP_MYSQL=1; shift ;;
        -h|--help)
            echo "用法: $0 [选项]"
            echo "  --install-dir DIR   项目安装目录 (默认: 脚本上级目录)"
            echo "  --keep-mysql        保留 MySQL 运行不停止"
            exit 0
            ;;
        *)  fail "未知参数: $1 (使用 -h 查看帮助)"; exit 1 ;;
    esac
done

if [[ -z "$INSTALL_DIR" ]]; then
    INSTALL_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
fi

PID_DIR="$INSTALL_DIR/storage/pids"

# ── 平台检测 ──────────────────────────────────────────────────
case "$(uname -s)" in
    Darwin*)  PLATFORM="macos" ;;
    Linux*)   PLATFORM="linux" ;;
    *)        PLATFORM="unknown" ;;
esac

# ── 检测 systemd 是否可用 ────────────────────────────────────
HAS_SYSTEMCTL=0
if command -v systemctl &>/dev/null && [[ "$PLATFORM" == "linux" ]]; then
    if systemctl is-system-running &>/dev/null || [[ "$(systemctl is-system-running 2>/dev/null)" == "running" ]] || [[ "$(systemctl is-system-running 2>/dev/null)" == "degraded" ]]; then
        HAS_SYSTEMCTL=1
    fi
fi

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║      牙科诊所管理系统 - 停止服务                ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""
info "平台: $PLATFORM"
info "项目目录: $INSTALL_DIR"
info "PID 目录: $PID_DIR"
info "优雅关闭超时: ${GRACEFUL_TIMEOUT} 秒"
if [[ "$KEEP_MYSQL" -eq 1 ]]; then
    info "模式: 保留 MySQL 运行"
fi
echo ""

# ── 状态跟踪 ─────────────────────────────────────────────────
QUEUE_STATUS="未运行"
OCR_STATUS="未运行"
NGINX_STATUS="未运行"
PHP_STATUS="未运行"
MYSQL_STATUS="未运行"

# ── 辅助函数: 优雅停止进程 ───────────────────────────────────
# 参数: $1=PID, $2=显示名称
# 策略: SIGTERM → 等待 $GRACEFUL_TIMEOUT 秒 → SIGKILL
graceful_kill() {
    local pid="$1"
    local name="$2"

    if ! kill -0 "$pid" 2>/dev/null; then
        info "${name} 进程已不存在 (PID=${pid})"
        return 1
    fi

    info "发送 SIGTERM 给 ${name} (PID=${pid})..."
    kill "$pid" 2>/dev/null || true

    # 等待优雅关闭
    local wait=0
    while kill -0 "$pid" 2>/dev/null && [[ $wait -lt $GRACEFUL_TIMEOUT ]]; do
        sleep 1
        wait=$((wait + 1))
    done

    # 检查是否已停止
    if kill -0 "$pid" 2>/dev/null; then
        warn "${name} 未响应 SIGTERM（${GRACEFUL_TIMEOUT}秒），发送 SIGKILL..."
        kill -9 "$pid" 2>/dev/null || true
        sleep 1
        if kill -0 "$pid" 2>/dev/null; then
            fail "${name} 无法终止 (PID=${pid})"
            return 1
        fi
    fi

    ok "${name} 已停止"
    return 0
}

# 通过 PID 文件停止进程
stop_by_pid_file() {
    local name="$1"
    local pid_file="$2"
    local display_name="$3"

    if [[ ! -f "$pid_file" ]]; then
        return 1
    fi

    local pid
    pid=$(cat "$pid_file")

    if [[ -z "$pid" ]]; then
        rm -f "$pid_file"
        return 1
    fi

    if graceful_kill "$pid" "$display_name"; then
        rm -f "$pid_file"
        return 0
    else
        rm -f "$pid_file"
        return 1
    fi
}

# ══════════════════════════════════════════════════════════════
#  Step 1/4: 停止队列工作进程
# ══════════════════════════════════════════════════════════════
info "[1/4] 停止队列工作进程..."

QUEUE_STOPPED=0

# 方式1: 通过 PID 文件 (storage/queue.pid)
QUEUE_PID_FILE="$INSTALL_DIR/storage/queue.pid"
if [[ -f "$QUEUE_PID_FILE" ]]; then
    if stop_by_pid_file "queue" "$QUEUE_PID_FILE" "队列工作进程"; then
        QUEUE_STOPPED=1
        QUEUE_STATUS="已停止"
    fi
fi

# 方式1b: 通过 storage/pids/queue-worker.pid
if [[ "$QUEUE_STOPPED" -eq 0 ]] && [[ -f "$PID_DIR/queue-worker.pid" ]]; then
    if stop_by_pid_file "queue-worker" "$PID_DIR/queue-worker.pid" "队列工作进程"; then
        QUEUE_STOPPED=1
        QUEUE_STATUS="已停止"
    fi
fi

# 方式2: 通过进程名查找
if [[ "$QUEUE_STOPPED" -eq 0 ]]; then
    QUEUE_PIDS=$(pgrep -f "artisan queue:work" 2>/dev/null || true)
    if [[ -n "$QUEUE_PIDS" ]]; then
        info "通过进程名查找到队列进程..."
        for pid in $QUEUE_PIDS; do
            graceful_kill "$pid" "队列工作进程 (PID=${pid})" || true
        done
        QUEUE_STATUS="已停止"
    else
        info "队列工作进程未运行"
    fi
fi
echo ""

# ══════════════════════════════════════════════════════════════
#  Step 2/4: 停止 OCR 服务
# ══════════════════════════════════════════════════════════════
info "[2/4] 停止 OCR 服务..."

OCR_STOPPED=0

# 方式1: 通过 scripts/ocr_server.pid
OCR_PID_FILE="$INSTALL_DIR/scripts/ocr_server.pid"
if [[ -f "$OCR_PID_FILE" ]]; then
    if stop_by_pid_file "ocr" "$OCR_PID_FILE" "OCR 服务"; then
        OCR_STOPPED=1
        OCR_STATUS="已停止"
    fi
fi

# 方式1b: 通过 storage/pids/ocr-server.pid
if [[ "$OCR_STOPPED" -eq 0 ]] && [[ -f "$PID_DIR/ocr-server.pid" ]]; then
    if stop_by_pid_file "ocr-server" "$PID_DIR/ocr-server.pid" "OCR 服务"; then
        OCR_STOPPED=1
        OCR_STATUS="已停止"
    fi
fi

# 方式2: 通过进程名查找
if [[ "$OCR_STOPPED" -eq 0 ]]; then
    OCR_PIDS=$(pgrep -f "ocr_server.py" 2>/dev/null || true)
    if [[ -n "$OCR_PIDS" ]]; then
        info "通过进程名查找到 OCR 进程..."
        for pid in $OCR_PIDS; do
            graceful_kill "$pid" "OCR 服务 (PID=${pid})" || true
        done
        OCR_STATUS="已停止"
    else
        info "OCR 服务未运行"
    fi
fi
echo ""

# ══════════════════════════════════════════════════════════════
#  Step 3/4: 停止 Nginx / PHP 内置服务器
# ══════════════════════════════════════════════════════════════
info "[3/4] 停止 Web 服务器..."

# ─ 停止 PHP 内置服务器 ─
if [[ -f "$PID_DIR/php-server.pid" ]]; then
    if stop_by_pid_file "php-server" "$PID_DIR/php-server.pid" "PHP 内置服务器"; then
        PHP_STATUS="已停止"
    fi
else
    # 尝试通过进程名查找属于本项目的 PHP 服务器
    PHP_PIDS=$(pgrep -f "php -S" 2>/dev/null || true)
    if [[ -n "$PHP_PIDS" ]]; then
        for pid in $PHP_PIDS; do
            if ps -p "$pid" -o args= 2>/dev/null | grep -q "$INSTALL_DIR"; then
                info "找到本项目的 PHP 服务器进程 (PID=${pid})"
                graceful_kill "$pid" "PHP 内置服务器" || true
                PHP_STATUS="已停止"
            fi
        done
        if [[ "$PHP_STATUS" != "已停止" ]]; then
            info "未找到本项目的 PHP 服务器进程"
        fi
    else
        info "PHP 内置服务器未运行"
    fi
fi

# ─ 停止 Nginx ─
if [[ -f "$PID_DIR/nginx.pid" ]]; then
    if stop_by_pid_file "nginx" "$PID_DIR/nginx.pid" "Nginx"; then
        NGINX_STATUS="已停止"
    fi
elif pgrep -x nginx &>/dev/null; then
    # Nginx 在运行，尝试通过服务管理器停止
    if [[ "$HAS_SYSTEMCTL" -eq 1 ]]; then
        if systemctl is-active nginx &>/dev/null; then
            info "使用 systemctl 停止 Nginx..."
            sudo systemctl stop nginx 2>/dev/null && NGINX_STATUS="已停止" && ok "Nginx 已停止" || true
        fi
    elif [[ "$PLATFORM" == "macos" ]] && command -v brew &>/dev/null; then
        brew services stop nginx 2>/dev/null && NGINX_STATUS="已停止" && ok "Nginx 已停止" || true
    fi

    if [[ "$NGINX_STATUS" == "未运行" ]]; then
        # 非本脚本启动的 Nginx
        info "Nginx 正在运行，但非本脚本启动，跳过"
        NGINX_STATUS="跳过（非本脚本启动）"
    fi
else
    info "Nginx 未运行"
fi
echo ""

# ══════════════════════════════════════════════════════════════
#  Step 4/4: 停止 MySQL
# ══════════════════════════════════════════════════════════════
info "[4/4] 停止 MySQL..."

if [[ "$KEEP_MYSQL" -eq 1 ]]; then
    info "已指定 --keep-mysql，跳过 MySQL"
    MYSQL_STATUS="保留运行"
else
    # 检查是否有 PID 文件（说明是我们手动启动的）
    if [[ -f "$PID_DIR/mysql.pid" ]]; then
        if stop_by_pid_file "mysql" "$PID_DIR/mysql.pid" "MySQL"; then
            MYSQL_STATUS="已停止"
        fi
    elif pgrep -x mysqld &>/dev/null; then
        info "MySQL 正在运行，尝试停止..."

        # 方式1: systemctl
        if [[ "$HAS_SYSTEMCTL" -eq 1 ]]; then
            if systemctl is-active mysql &>/dev/null; then
                sudo systemctl stop mysql 2>/dev/null && MYSQL_STATUS="已停止"
            elif systemctl is-active mariadb &>/dev/null; then
                sudo systemctl stop mariadb 2>/dev/null && MYSQL_STATUS="已停止"
            elif systemctl is-active mysqld &>/dev/null; then
                sudo systemctl stop mysqld 2>/dev/null && MYSQL_STATUS="已停止"
            fi
        fi

        # 方式2: macOS Homebrew
        if [[ "$MYSQL_STATUS" == "未运行" ]] && [[ "$PLATFORM" == "macos" ]]; then
            if command -v brew &>/dev/null; then
                brew services stop mysql 2>/dev/null || brew services stop mysql@8.0 2>/dev/null || true
                MYSQL_STATUS="已停止"
            fi
        fi

        # 方式3: mysqladmin shutdown
        if [[ "$MYSQL_STATUS" == "未运行" ]] && command -v mysqladmin &>/dev/null; then
            info "使用 mysqladmin 优雅关闭..."
            mysqladmin -u root shutdown 2>/dev/null && MYSQL_STATUS="已停止" || true
        fi

        # 方式4: service 命令
        if [[ "$MYSQL_STATUS" == "未运行" ]] && command -v service &>/dev/null; then
            sudo service mysql stop 2>/dev/null && MYSQL_STATUS="已停止" || true
        fi

        # 方式5: 直接 kill（最后手段）
        if [[ "$MYSQL_STATUS" == "未运行" ]]; then
            MYSQL_PID=$(pgrep -x mysqld 2>/dev/null || true)
            if [[ -n "$MYSQL_PID" ]]; then
                graceful_kill "$MYSQL_PID" "MySQL" && MYSQL_STATUS="已停止" || MYSQL_STATUS="需手动停止"
            fi
        fi

        if [[ "$MYSQL_STATUS" == "已停止" ]]; then
            ok "MySQL 已停止"
        elif [[ "$MYSQL_STATUS" != "需手动停止" ]]; then
            warn "无法自动停止 MySQL，请手动停止"
            MYSQL_STATUS="需手动停止"
        fi
    else
        info "MySQL 未运行"
    fi
fi
echo ""

# ── 清理残留 PID 文件 ────────────────────────────────────────
if [[ -d "$PID_DIR" ]]; then
    for pid_file in "$PID_DIR"/*.pid; do
        [[ -f "$pid_file" ]] || continue
        pid=$(cat "$pid_file")
        if [[ -n "$pid" ]] && ! kill -0 "$pid" 2>/dev/null; then
            rm -f "$pid_file"
        fi
    done
fi

# 清理 scripts/ocr_server.pid 如果进程已不在
if [[ -f "$INSTALL_DIR/scripts/ocr_server.pid" ]]; then
    pid=$(cat "$INSTALL_DIR/scripts/ocr_server.pid")
    if [[ -n "$pid" ]] && ! kill -0 "$pid" 2>/dev/null; then
        rm -f "$INSTALL_DIR/scripts/ocr_server.pid"
    fi
fi

# 清理 storage/queue.pid 如果进程已不在
if [[ -f "$INSTALL_DIR/storage/queue.pid" ]]; then
    pid=$(cat "$INSTALL_DIR/storage/queue.pid")
    if [[ -n "$pid" ]] && ! kill -0 "$pid" 2>/dev/null; then
        rm -f "$INSTALL_DIR/storage/queue.pid"
    fi
fi

# ── 状态汇总 ─────────────────────────────────────────────────
echo -e "${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║           服务停止状态汇总                       ║${NC}"
echo -e "${BOLD}╠══════════════════════════════════════════════════╣${NC}"
echo -e "${BOLD}║${NC}                                                  ${BOLD}║${NC}"

# 颜色化状态
colorize_status() {
    local status="$1"
    case "$status" in
        "已停止")       echo -e "${GREEN}${status}${NC}" ;;
        "未运行")       echo -e "${CYAN}${status}${NC}" ;;
        "保留运行")     echo -e "${YELLOW}${status}${NC}" ;;
        "需手动停止")   echo -e "${RED}${status}${NC}" ;;
        *)              echo -e "${YELLOW}${status}${NC}" ;;
    esac
}

echo -e "${BOLD}║${NC}  队列工作进程 ...... $(colorize_status "$QUEUE_STATUS")                      ${BOLD}║${NC}"
echo -e "${BOLD}║${NC}  OCR 服务 .......... $(colorize_status "$OCR_STATUS")                      ${BOLD}║${NC}"
echo -e "${BOLD}║${NC}  PHP 服务器 ........ $(colorize_status "$PHP_STATUS")                      ${BOLD}║${NC}"
echo -e "${BOLD}║${NC}  Nginx ............. $(colorize_status "$NGINX_STATUS")                      ${BOLD}║${NC}"
echo -e "${BOLD}║${NC}  MySQL ............. $(colorize_status "$MYSQL_STATUS")                      ${BOLD}║${NC}"
echo -e "${BOLD}║${NC}                                                  ${BOLD}║${NC}"
echo -e "${BOLD}║${NC}  所有服务已处理完毕                              ${BOLD}║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""
