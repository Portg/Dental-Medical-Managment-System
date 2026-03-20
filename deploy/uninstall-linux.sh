#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════
#  牙科诊所管理系统 - Linux/macOS 卸载脚本
#  用途: 停止服务 → 可选备份 → 删除数据库 → 移除系统服务 → 删除文件
#  用法:
#    sudo ./uninstall-linux.sh
#    sudo ./uninstall-linux.sh --keep-data
#    sudo ./uninstall-linux.sh --install-dir /opt/dental --yes
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
fatal() { echo -e "${RED}[FATAL]${NC} $*" >&2; exit 1; }

# ── 参数解析 ──────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
INSTALL_DIR=""
KEEP_DATA=0
AUTO_YES=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --install-dir)  INSTALL_DIR="$2"; shift 2 ;;
        --keep-data)    KEEP_DATA=1; shift ;;
        --yes|-y)       AUTO_YES=1; shift ;;
        -h|--help)
            cat <<'HELP'
牙科诊所管理系统 - Linux/macOS 卸载脚本

用法: sudo ./uninstall-linux.sh [选项]

选项:
  --install-dir DIR   项目安装目录 (默认: /opt/dental)
  --keep-data         保留数据库，并备份上传文件和配置到 ~/dental-backup-*
  --yes, -y           跳过确认提示（危险）
  --help, -h          显示此帮助信息

示例:
  sudo ./uninstall-linux.sh                          # 交互式卸载
  sudo ./uninstall-linux.sh --keep-data              # 卸载但保留并备份数据
  sudo ./uninstall-linux.sh --install-dir /srv/app   # 指定安装目录
HELP
            exit 0
            ;;
        *)  fatal "未知参数: $1 (使用 -h 查看帮助)" ;;
    esac
done

# ── 检测安装目录 ──────────────────────────────────────────────
if [[ -z "$INSTALL_DIR" ]]; then
    # 优先检查脚本所在目录的上级
    if [[ -f "$SCRIPT_DIR/artisan" ]]; then
        INSTALL_DIR="$SCRIPT_DIR"
    elif [[ -f "$SCRIPT_DIR/../artisan" ]]; then
        INSTALL_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
    elif [[ -f "/opt/dental/artisan" ]]; then
        INSTALL_DIR="/opt/dental"
    else
        fatal "未找到安装目录。请使用 --install-dir 指定。"
    fi
fi

if [[ ! -f "$INSTALL_DIR/artisan" ]]; then
    fatal "目录 $INSTALL_DIR 不是有效的安装目录（未找到 artisan）"
fi

echo ""
echo -e "${BOLD}${CYAN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${CYAN}║       牙科诊所管理系统 - 卸载程序                   ║${NC}"
echo -e "${BOLD}${CYAN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""

info "安装目录: $INSTALL_DIR"
echo ""

# ── 读取数据库配置 ────────────────────────────────────────────
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="pristine_dental"
DB_USER="dental"

if [[ -f "$INSTALL_DIR/.env" ]]; then
    DB_HOST=$(grep -E "^DB_HOST=" "$INSTALL_DIR/.env" | cut -d= -f2- | tr -d '[:space:]"' || echo "127.0.0.1")
    DB_PORT=$(grep -E "^DB_PORT=" "$INSTALL_DIR/.env" | cut -d= -f2- | tr -d '[:space:]"' || echo "3306")
    DB_NAME=$(grep -E "^DB_DATABASE=" "$INSTALL_DIR/.env" | cut -d= -f2- | tr -d '[:space:]"' || echo "pristine_dental")
    DB_USER=$(grep -E "^DB_USERNAME=" "$INSTALL_DIR/.env" | cut -d= -f2- | tr -d '[:space:]"' || echo "dental")
fi

# ── 确认卸载 ──────────────────────────────────────────────────
if [[ "$AUTO_YES" -ne 1 ]]; then
    echo -e "${RED}${BOLD}  警告: 卸载将执行以下操作:${NC}"
    echo ""
    echo "  1. 停止所有相关服务和进程"
    if [[ "$KEEP_DATA" -eq 0 ]]; then
        echo "  2. 删除数据库 $DB_NAME 及用户 $DB_USER"
        echo "  3. 移除 systemd 服务、cron 任务、Nginx 配置、logrotate"
        echo "  4. 删除安装目录 $INSTALL_DIR"
    else
        echo "  2. 备份上传文件和配置到 ~/dental-backup-*"
        echo "  3. 移除 systemd 服务、cron 任务、Nginx 配置、logrotate"
        echo "  4. 删除安装目录 $INSTALL_DIR（保留数据库）"
    fi
    echo ""
    echo -e "${RED}  此操作不可恢复！${NC}"
    echo ""
    read -rp "  确认卸载？输入 YES 继续: " CONFIRM
    if [[ "$CONFIRM" != "YES" ]]; then
        echo ""
        info "已取消卸载。"
        exit 0
    fi
fi

STEP=0
if [[ "$KEEP_DATA" -eq 0 ]]; then
    TOTAL=5
else
    TOTAL=5
fi

next_step() {
    STEP=$((STEP + 1))
    echo ""
    echo -e "${BOLD}[${STEP}/${TOTAL}]${NC} ${CYAN}$1${NC}"
}

# ═══════════════════════════════════════════════════════════════
# Step 1: 停止所有服务
# ═══════════════════════════════════════════════════════════════
next_step "停止所有服务"

# 调用 stop 脚本（如果存在）
STOP_SCRIPT=""
if [[ -f "$INSTALL_DIR/stop-linux.sh" ]]; then
    STOP_SCRIPT="$INSTALL_DIR/stop-linux.sh"
elif [[ -f "$INSTALL_DIR/deploy/stop-linux.sh" ]]; then
    STOP_SCRIPT="$INSTALL_DIR/deploy/stop-linux.sh"
fi

if [[ -n "$STOP_SCRIPT" ]]; then
    chmod +x "$STOP_SCRIPT"
    "$STOP_SCRIPT" --install-dir "$INSTALL_DIR" 2>/dev/null || true
    ok "通过 stop-linux.sh 停止服务"
else
    # 手动停止
    info "手动停止服务..."

    # 停止 systemd 服务
    for svc in dental-queue dental-ocr; do
        if systemctl is-active "$svc" &>/dev/null; then
            systemctl stop "$svc" 2>/dev/null || true
            ok "停止 $svc"
        fi
    done

    # 停止队列进程
    pkill -f "artisan queue:work" 2>/dev/null || true
    # 停止 OCR 进程
    pkill -f "ocr_server.py" 2>/dev/null || true

    ok "服务已停止"
fi

# ═══════════════════════════════════════════════════════════════
# Step 2: 备份数据（如果 --keep-data）
# ═══════════════════════════════════════════════════════════════
next_step "备份用户数据"

if [[ "$KEEP_DATA" -eq 1 ]]; then
    BACKUP_DIR="$HOME/dental-backup-$(date +%Y%m%d%H%M%S)"
    mkdir -p "$BACKUP_DIR"

    # 备份 .env
    if [[ -f "$INSTALL_DIR/.env" ]]; then
        cp "$INSTALL_DIR/.env" "$BACKUP_DIR/.env"
        ok "已备份 .env"
    fi

    # 备份上传文件
    if [[ -d "$INSTALL_DIR/storage/app/public" ]]; then
        cp -r "$INSTALL_DIR/storage/app/public" "$BACKUP_DIR/uploads" 2>/dev/null || true
        ok "已备份上传文件"
    fi

    # 导出数据库
    if command -v mysqldump &>/dev/null; then
        info "正在导出数据库..."
        if mysqldump -h "$DB_HOST" -P "$DB_PORT" -u root "$DB_NAME" > "$BACKUP_DIR/$DB_NAME.sql" 2>/dev/null; then
            ok "已备份数据库到 $BACKUP_DIR/$DB_NAME.sql"
        else
            warn "数据库导出失败，请手动备份"
        fi
    fi

    ok "备份目录: $BACKUP_DIR"
else
    info "未指定 --keep-data，跳过备份"
fi

# ═══════════════════════════════════════════════════════════════
# Step 3: 删除数据库和用户
# ═══════════════════════════════════════════════════════════════
next_step "清理数据库"

if [[ "$KEEP_DATA" -eq 1 ]]; then
    info "保留数据库（--keep-data）"
else
    if command -v mysql &>/dev/null; then
        # 确保 MySQL 在运行
        if systemctl is-active mysql &>/dev/null || systemctl is-active mysqld &>/dev/null || systemctl is-active mariadb &>/dev/null; then
            MYSQL_RUNNING=1
        elif pgrep -x mysqld &>/dev/null; then
            MYSQL_RUNNING=1
        elif [[ "$(uname)" == "Darwin" ]] && brew services list 2>/dev/null | grep -q "mysql.*started"; then
            MYSQL_RUNNING=1
        else
            MYSQL_RUNNING=0
            warn "MySQL 未运行，跳过数据库清理"
        fi

        if [[ "$MYSQL_RUNNING" -eq 1 ]]; then
            info "删除数据库 $DB_NAME..."
            mysql -h "$DB_HOST" -P "$DB_PORT" -u root -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;" 2>/dev/null && \
                ok "数据库 $DB_NAME 已删除" || warn "数据库删除失败"

            info "删除用户 $DB_USER..."
            mysql -h "$DB_HOST" -P "$DB_PORT" -u root -e "DROP USER IF EXISTS '$DB_USER'@'localhost';" 2>/dev/null && \
                ok "用户 $DB_USER 已删除" || warn "用户删除失败"
        fi
    else
        warn "未找到 mysql 命令，跳过数据库清理"
        warn "如需手动删除: DROP DATABASE $DB_NAME; DROP USER '$DB_USER'@'localhost';"
    fi
fi

# ═══════════════════════════════════════════════════════════════
# Step 4: 移除系统服务和配置
# ═══════════════════════════════════════════════════════════════
next_step "移除系统服务和配置"

# ── systemd 服务 ──────────────────────────────────────────────
for svc in dental-queue dental-ocr; do
    SVC_FILE="/etc/systemd/system/${svc}.service"
    if [[ -f "$SVC_FILE" ]]; then
        systemctl disable "$svc" 2>/dev/null || true
        rm -f "$SVC_FILE"
        ok "移除 systemd 服务: $svc"
    fi
done

# 重新加载 systemd
if command -v systemctl &>/dev/null; then
    systemctl daemon-reload 2>/dev/null || true
fi

# ── cron 任务 ─────────────────────────────────────────────────
info "清理 cron 任务..."
# 检测 web 用户
if id www-data &>/dev/null; then
    WEB_USER="www-data"
elif id _www &>/dev/null; then
    WEB_USER="_www"
elif id nginx &>/dev/null; then
    WEB_USER="nginx"
else
    WEB_USER=""
fi

# 清理 crontab 中包含 artisan schedule:run 的条目
remove_cron_entry() {
    local user="$1"
    if crontab -u "$user" -l 2>/dev/null | grep -q "artisan schedule:run"; then
        crontab -u "$user" -l 2>/dev/null | grep -v "artisan schedule:run" | crontab -u "$user" - 2>/dev/null
        ok "移除 $user 的 cron 任务"
    fi
}

if [[ -n "$WEB_USER" ]]; then
    remove_cron_entry "$WEB_USER"
fi
remove_cron_entry "root"

# 清理 /etc/cron.d/ 下的文件
if [[ -f "/etc/cron.d/dental-clinic" ]]; then
    rm -f "/etc/cron.d/dental-clinic"
    ok "移除 /etc/cron.d/dental-clinic"
fi

# ── Nginx 配置 ────────────────────────────────────────────────
info "清理 Nginx 配置..."
NGINX_CHANGED=0

for nginx_conf in \
    /etc/nginx/sites-enabled/dental-clinic.conf \
    /etc/nginx/sites-available/dental-clinic.conf \
    /etc/nginx/conf.d/dental-clinic.conf \
    /etc/nginx/dental-clinic.conf \
    /usr/local/etc/nginx/servers/dental-clinic.conf; do
    if [[ -f "$nginx_conf" ]] || [[ -L "$nginx_conf" ]]; then
        rm -f "$nginx_conf"
        ok "移除 $nginx_conf"
        NGINX_CHANGED=1
    fi
done

# 重新加载 Nginx
if [[ "$NGINX_CHANGED" -eq 1 ]]; then
    if command -v nginx &>/dev/null; then
        nginx -t 2>/dev/null && nginx -s reload 2>/dev/null || true
        ok "Nginx 已重新加载"
    fi
fi

# ── logrotate 配置 ────────────────────────────────────────────
if [[ -f "/etc/logrotate.d/dental-clinic" ]]; then
    rm -f "/etc/logrotate.d/dental-clinic"
    ok "移除 /etc/logrotate.d/dental-clinic"
fi

# ═══════════════════════════════════════════════════════════════
# Step 5: 删除安装目录
# ═══════════════════════════════════════════════════════════════
next_step "删除安装目录"

info "删除 $INSTALL_DIR ..."

# 安全检查：不允许删除根目录或常见系统路径
case "$INSTALL_DIR" in
    /|/usr|/etc|/var|/home|/root|/tmp)
        fatal "安全检查失败: 不允许删除系统目录 $INSTALL_DIR"
        ;;
esac

rm -rf "$INSTALL_DIR"

if [[ -d "$INSTALL_DIR" ]]; then
    warn "部分文件未能删除，请手动清理: $INSTALL_DIR"
else
    ok "安装目录已删除"
fi

# ═══════════════════════════════════════════════════════════════
# 完成
# ═══════════════════════════════════════════════════════════════
echo ""
echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${GREEN}║       卸载完成                                       ║${NC}"
echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo "  已执行:"
echo "    - 停止所有服务和进程"
echo "    - 移除 systemd 服务 (dental-queue, dental-ocr)"
echo "    - 清理 cron 任务、Nginx 配置、logrotate"
if [[ "$KEEP_DATA" -eq 0 ]]; then
    echo "    - 删除数据库 $DB_NAME 和用户 $DB_USER"
fi
echo "    - 删除安装目录 $INSTALL_DIR"
if [[ "$KEEP_DATA" -eq 1 ]]; then
    echo ""
    echo "  数据已备份到: $BACKUP_DIR"
fi
echo ""
