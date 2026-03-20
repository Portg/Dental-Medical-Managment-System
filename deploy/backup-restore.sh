#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════
#  牙科诊所管理系统 - 备份与恢复工具
#  Dental Clinic Management System — Backup & Restore Tool
#
#  用法:
#    bash deploy/backup-restore.sh backup  [--install-dir DIR] [--output-dir DIR]
#    bash deploy/backup-restore.sh restore <backup-file> [--install-dir DIR]
#    bash deploy/backup-restore.sh list    [--output-dir DIR]
#
#  选项:
#    --install-dir DIR    安装目录 (默认: /opt/dental)
#    --output-dir DIR     备份输出目录 (默认: /backups)
#    --help               显示帮助
#
#  Windows 用户请在 Git Bash 中运行此脚本。
# ═══════════════════════════════════════════════════════════════════════
set -euo pipefail

# ── 颜色输出 ────────────────────────────────────────────────────────
if [[ -t 1 ]]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    CYAN='\033[0;36m'
    BOLD='\033[1m'
    NC='\033[0m'
else
    RED='' GREEN='' YELLOW='' CYAN='' BOLD='' NC=''
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

die() {
    echo -e "${RED}错误: $*${NC}" >&2
    exit 1
}

CURRENT_STEP=0
TOTAL_STEPS=1

# ── 默认参数 ──────────────────────────────────────────────────────────
INSTALL_DIR="/opt/dental"
OUTPUT_DIR="/backups"
ACTION=""
RESTORE_FILE=""

# ── 帮助信息 ──────────────────────────────────────────────────────────
show_help() {
    echo "牙科诊所管理系统 — 备份与恢复工具"
    echo ""
    echo "用法:"
    echo "  $0 backup  [选项]           创建备份"
    echo "  $0 restore <备份文件> [选项] 从备份恢复"
    echo "  $0 list    [选项]           列出可用备份"
    echo ""
    echo "选项:"
    echo "  --install-dir DIR    安装目录 (默认: /opt/dental)"
    echo "  --output-dir DIR     备份输出/搜索目录 (默认: /backups)"
    echo "  --help               显示此帮助"
    echo ""
    echo "Windows 用户: 请在 Git Bash 中运行此脚本。"
    exit 0
}

# ── 解析命令行参数 ────────────────────────────────────────────────────
if [[ $# -lt 1 ]]; then
    show_help
fi

ACTION="$1"
shift

# restore 需要紧跟备份文件路径
if [[ "$ACTION" == "restore" ]]; then
    if [[ $# -lt 1 ]] || [[ "$1" == --* ]]; then
        die "restore 需要指定备份文件，例如: $0 restore /backups/dental-backup-2026-03-17-120000.tar.gz"
    fi
    RESTORE_FILE="$1"
    shift
fi

while [[ $# -gt 0 ]]; do
    case "$1" in
        --install-dir) INSTALL_DIR="$2"; shift 2 ;;
        --output-dir)  OUTPUT_DIR="$2";  shift 2 ;;
        --help|-h)     show_help ;;
        *)
            die "未知选项: $1 (使用 --help 查看帮助)"
            ;;
    esac
done

# ── 从 .env 读取数据库配置 ────────────────────────────────────────────
read_db_config() {
    DB_HOST="127.0.0.1"
    DB_PORT="3306"
    DB_USER="root"
    DB_PASS=""
    DB_NAME="pristine_dental"

    if [[ -f "$INSTALL_DIR/.env" ]]; then
        DB_HOST=$(grep -E '^DB_HOST=' "$INSTALL_DIR/.env" | cut -d'=' -f2- | tr -d '[:space:]' || echo "127.0.0.1")
        DB_PORT=$(grep -E '^DB_PORT=' "$INSTALL_DIR/.env" | cut -d'=' -f2- | tr -d '[:space:]' || echo "3306")
        DB_USER=$(grep -E '^DB_USERNAME=' "$INSTALL_DIR/.env" | cut -d'=' -f2- | tr -d '[:space:]' || echo "root")
        DB_PASS=$(grep -E '^DB_PASSWORD=' "$INSTALL_DIR/.env" | cut -d'=' -f2- | tr -d '[:space:]' || echo "")
        DB_NAME=$(grep -E '^DB_DATABASE=' "$INSTALL_DIR/.env" | cut -d'=' -f2- | tr -d '[:space:]' || echo "pristine_dental")
    fi
}

# 构建 mysql/mysqldump 命令参数数组（不包含密码，密码通过 MYSQL_PWD 传递）
build_mysql_args() {
    MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
}

# 封装调用，通过 MYSQL_PWD 环境变量传递密码，避免命令行参数暴露（ps aux 中可见）
run_mysql() {
    MYSQL_PWD="$DB_PASS" $MYSQL_CMD "${MYSQL_ARGS[@]}" "$@"
}

run_mysqldump() {
    MYSQL_PWD="$DB_PASS" $MYSQLDUMP_CMD "${MYSQL_ARGS[@]}" "$@"
}

# 查找 mysql 客户端
find_mysql_cmd() {
    MYSQL_CMD=""
    MYSQLDUMP_CMD=""
    if command -v mysql &>/dev/null; then
        MYSQL_CMD="mysql"
    elif command -v mariadb &>/dev/null; then
        MYSQL_CMD="mariadb"
    else
        die "未找到 mysql 或 mariadb 客户端"
    fi

    if command -v mysqldump &>/dev/null; then
        MYSQLDUMP_CMD="mysqldump"
    elif command -v mariadb-dump &>/dev/null; then
        MYSQLDUMP_CMD="mariadb-dump"
    else
        die "未找到 mysqldump 或 mariadb-dump"
    fi
}

# 人类可读的文件大小
human_size() {
    local file="$1"
    if [[ "$(uname -s)" == "Darwin" ]]; then
        stat -f '%z' "$file" 2>/dev/null | awk '{
            if ($1 >= 1073741824) { printf "%.1f GB\n", $1/1073741824 }
            else if ($1 >= 1048576) { printf "%.1f MB\n", $1/1048576 }
            else if ($1 >= 1024) { printf "%.1f KB\n", $1/1024 }
            else { printf "%d B\n", $1 }
        }'
    else
        stat --printf='%s' "$file" 2>/dev/null | awk '{
            if ($1 >= 1073741824) { printf "%.1f GB\n", $1/1073741824 }
            else if ($1 >= 1048576) { printf "%.1f MB\n", $1/1048576 }
            else if ($1 >= 1024) { printf "%.1f KB\n", $1/1024 }
            else { printf "%d B\n", $1 }
        }'
    fi
}

# ═══════════════════════════════════════════════════════════════════════
#  backup 命令
# ═══════════════════════════════════════════════════════════════════════
do_backup() {
    TOTAL_STEPS=5
    TIMESTAMP=$(date +%Y-%m-%d-%H%M%S)
    BACKUP_NAME="dental-backup-${TIMESTAMP}"
    WORK_DIR=$(mktemp -d)

    # 确保临时目录在退出时清理
    trap "rm -rf '$WORK_DIR'" EXIT

    echo ""
    echo -e "${BOLD}╔══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}║   牙科诊所管理系统 — 创建备份                          ║${NC}"
    echo -e "${BOLD}╚══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  安装目录: ${CYAN}${INSTALL_DIR}${NC}"
    echo -e "  输出目录: ${CYAN}${OUTPUT_DIR}${NC}"
    echo -e "  备份时间: ${CYAN}${TIMESTAMP}${NC}"

    # 验证安装目录
    if [[ ! -f "$INSTALL_DIR/artisan" ]]; then
        die "安装目录无效: $INSTALL_DIR (artisan 文件不存在)"
    fi

    read_db_config
    find_mysql_cmd
    build_mysql_args

    # ── Step 1: 导出数据库 ────────────────────────────────────────────
    step "导出数据库..."

    DUMP_FILE="$WORK_DIR/database.sql"
    if ! run_mysqldump \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        "$DB_NAME" > "$DUMP_FILE" 2>/dev/null; then
        die "数据库导出失败"
    fi

    DUMP_SIZE=$(human_size "$DUMP_FILE")
    ok "数据库已导出: database.sql (${DUMP_SIZE})"

    # ── Step 2: 归档 storage/app/ ────────────────────────────────────
    step "归档用户上传文件..."

    STORAGE_DIR="$INSTALL_DIR/storage/app"
    if [[ -d "$STORAGE_DIR" ]]; then
        tar -cf "$WORK_DIR/storage_app.tar" -C "$INSTALL_DIR/storage" app 2>/dev/null
        STORAGE_SIZE=$(human_size "$WORK_DIR/storage_app.tar")
        ok "用户上传文件已归档: storage_app.tar (${STORAGE_SIZE})"
    else
        warn "storage/app/ 目录不存在，跳过"
        # 创建空占位
        mkdir -p "$WORK_DIR/empty_app"
        tar -cf "$WORK_DIR/storage_app.tar" -C "$WORK_DIR" empty_app 2>/dev/null
        rm -rf "$WORK_DIR/empty_app"
    fi

    # ── Step 3: 备份 .env ────────────────────────────────────────────
    step "备份配置文件..."

    if [[ -f "$INSTALL_DIR/.env" ]]; then
        cp "$INSTALL_DIR/.env" "$WORK_DIR/env_backup"
        ok ".env 已备份"
    else
        warn ".env 不存在"
        touch "$WORK_DIR/env_backup"
    fi

    # 写入备份元数据
    cat > "$WORK_DIR/backup_meta.txt" <<METAEOF
# Dental Clinic Management System — Backup Metadata
backup_date=${TIMESTAMP}
install_dir=${INSTALL_DIR}
db_name=${DB_NAME}
db_host=${DB_HOST}
version=$(cat "$INSTALL_DIR/VERSION" 2>/dev/null | tr -d '[:space:]' || echo "unknown")
hostname=$(hostname 2>/dev/null || echo "unknown")
METAEOF
    ok "备份元数据已写入"

    # ── Step 4: 打包为 tar.gz ────────────────────────────────────────
    step "打包备份文件..."

    mkdir -p "$OUTPUT_DIR"
    BACKUP_FILE="$OUTPUT_DIR/${BACKUP_NAME}.tar.gz"

    tar -czf "$BACKUP_FILE" -C "$WORK_DIR" \
        database.sql \
        storage_app.tar \
        env_backup \
        backup_meta.txt

    FINAL_SIZE=$(human_size "$BACKUP_FILE")
    ok "备份打包完成"

    # ── Step 5: 验证备份 ──────────────────────────────────────────────
    step "验证备份完整性..."

    if tar -tzf "$BACKUP_FILE" &>/dev/null; then
        count=0
        while IFS= read -r _; do
            count=$((count + 1))
        done < <(tar -tzf "$BACKUP_FILE" 2>/dev/null)
        ok "备份文件完整 (${count} 个条目)"
    else
        fail "备份文件验证失败！"
        exit 1
    fi

    # ── 完成 ──────────────────────────────────────────────────────────
    echo ""
    echo -e "${GREEN}══════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  备份完成！${NC}"
    echo -e "${GREEN}══════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "  备份文件: ${CYAN}${BACKUP_FILE}${NC}"
    echo -e "  文件大小: ${CYAN}${FINAL_SIZE}${NC}"
    echo ""
    echo -e "  恢复命令:"
    echo -e "  ${CYAN}bash deploy/backup-restore.sh restore ${BACKUP_FILE}${NC}"
    echo ""
}

# ═══════════════════════════════════════════════════════════════════════
#  restore 命令
# ═══════════════════════════════════════════════════════════════════════
do_restore() {
    TOTAL_STEPS=8

    echo ""
    echo -e "${BOLD}╔══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}║   牙科诊所管理系统 — 恢复备份                          ║${NC}"
    echo -e "${BOLD}╚══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  备份文件: ${CYAN}${RESTORE_FILE}${NC}"
    echo -e "  安装目录: ${CYAN}${INSTALL_DIR}${NC}"

    # 验证备份文件
    if [[ ! -f "$RESTORE_FILE" ]]; then
        die "备份文件不存在: $RESTORE_FILE"
    fi

    # ── Step 1: 验证备份完整性 ────────────────────────────────────────
    step "验证备份完整性..."

    if ! tar -tzf "$RESTORE_FILE" &>/dev/null; then
        die "备份文件损坏或格式不正确: $RESTORE_FILE"
    fi

    # 检查必需文件
    REQUIRED_FILES=("database.sql" "env_backup" "backup_meta.txt")
    for rf in "${REQUIRED_FILES[@]}"; do
        if ! tar -tzf "$RESTORE_FILE" | grep -q "^${rf}$"; then
            die "备份文件缺少必需内容: $rf"
        fi
    done
    ok "备份文件完整"

    # 读取备份元数据
    WORK_DIR=$(mktemp -d)
    trap "rm -rf '$WORK_DIR'" EXIT

    tar -xzf "$RESTORE_FILE" -C "$WORK_DIR" backup_meta.txt
    if [[ -f "$WORK_DIR/backup_meta.txt" ]]; then
        BACKUP_DATE=$(grep '^backup_date=' "$WORK_DIR/backup_meta.txt" | cut -d'=' -f2- || echo "unknown")
        BACKUP_VERSION=$(grep '^version=' "$WORK_DIR/backup_meta.txt" | cut -d'=' -f2- || echo "unknown")
        BACKUP_HOST=$(grep '^hostname=' "$WORK_DIR/backup_meta.txt" | cut -d'=' -f2- || echo "unknown")
        info "备份来源: ${BACKUP_HOST}, 日期: ${BACKUP_DATE}, 版本: v${BACKUP_VERSION}"
    fi

    # 确认恢复
    echo ""
    echo -e "  ${RED}${BOLD}警告: 恢复操作将覆盖当前的数据库和上传文件！${NC}"
    echo -e "  ${YELLOW}建议先运行 backup 创建当前状态的备份。${NC}"
    echo ""
    read -p "  确认继续恢复? (y/N): " CONFIRM
    if [[ "$CONFIRM" != "y" ]] && [[ "$CONFIRM" != "Y" ]]; then
        echo "  已取消恢复操作。"
        exit 0
    fi

    # ── Step 2: 停止服务 ──────────────────────────────────────────────
    step "停止相关服务..."

    SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

    # 尝试调用 stop-linux.sh
    if [[ -f "$SCRIPT_DIR/stop-linux.sh" ]]; then
        bash "$SCRIPT_DIR/stop-linux.sh" 2>/dev/null && ok "服务已停止 (stop-linux.sh)" || warn "stop-linux.sh 执行异常"
    else
        # 手动尝试停止 queue worker
        if pgrep -f "artisan.*queue:work" &>/dev/null 2>&1; then
            pkill -f "artisan.*queue:work" 2>/dev/null || true
            ok "队列 Worker 已停止"
        fi
        # 停止 OCR 服务
        if pgrep -f "ocr_server.py" &>/dev/null 2>&1; then
            pkill -f "ocr_server.py" 2>/dev/null || true
            ok "OCR 服务已停止"
        fi
        info "Web 服务器需要手动管理"
    fi

    # ── Step 3: 解压备份 ──────────────────────────────────────────────
    step "解压备份文件..."

    tar -xzf "$RESTORE_FILE" -C "$WORK_DIR"
    ok "备份已解压到临时目录"

    # ── Step 4: 恢复数据库 ────────────────────────────────────────────
    step "恢复数据库..."

    # 从待恢复的 .env 或当前 .env 读取数据库配置
    if [[ -f "$INSTALL_DIR/.env" ]]; then
        read_db_config
    elif [[ -f "$WORK_DIR/env_backup" ]]; then
        # 临时使用备份的 .env
        INSTALL_DIR_ORIG="$INSTALL_DIR"
        cp "$WORK_DIR/env_backup" "$WORK_DIR/.env_temp"
        INSTALL_DIR="$WORK_DIR"
        mv "$WORK_DIR/.env_temp" "$WORK_DIR/.env"
        read_db_config
        INSTALL_DIR="$INSTALL_DIR_ORIG"
        rm -f "$WORK_DIR/.env"
    else
        read_db_config
    fi

    find_mysql_cmd
    build_mysql_args

    # 测试数据库连接
    if ! run_mysql -e "SELECT 1" &>/dev/null; then
        die "无法连接数据库 (${DB_HOST}:${DB_PORT})，请确认 MySQL 已启动"
    fi

    # 确认目标数据库已存在。恢复脚本通常使用应用数据库用户，默认不假设其拥有 CREATE DATABASE 权限。
    if ! run_mysql -e "USE \`${DB_NAME}\`;" &>/dev/null; then
        die "目标数据库 ${DB_NAME} 不存在，或当前用户 ${DB_USER} 无权访问。请先创建数据库并授予权限后重试。"
    fi

    # 导入 SQL
    if [[ -f "$WORK_DIR/database.sql" ]]; then
        run_mysql "$DB_NAME" < "$WORK_DIR/database.sql"
        ok "数据库已恢复"
    else
        die "备份中缺少 database.sql"
    fi

    # ── Step 5: 恢复 storage/app/ ────────────────────────────────────
    step "恢复用户上传文件..."

    if [[ -f "$WORK_DIR/storage_app.tar" ]]; then
        # 备份当前 storage/app
        if [[ -d "$INSTALL_DIR/storage/app" ]]; then
            mv "$INSTALL_DIR/storage/app" "$INSTALL_DIR/storage/app.pre-restore.$(date +%Y%m%d%H%M%S)" 2>/dev/null || true
        fi
        mkdir -p "$INSTALL_DIR/storage"
        tar -xf "$WORK_DIR/storage_app.tar" -C "$INSTALL_DIR/storage" 2>/dev/null
        ok "用户上传文件已恢复"
    else
        warn "备份中无 storage_app.tar，跳过"
    fi

    # ── Step 6: 恢复 .env ─────────────────────────────────────────────
    step "恢复配置文件..."

    if [[ -f "$WORK_DIR/env_backup" ]]; then
        # 备份当前 .env
        if [[ -f "$INSTALL_DIR/.env" ]]; then
            cp "$INSTALL_DIR/.env" "$INSTALL_DIR/.env.pre-restore.$(date +%Y%m%d%H%M%S)"
            info "当前 .env 已备份"
        fi
        cp "$WORK_DIR/env_backup" "$INSTALL_DIR/.env"
        ok ".env 已恢复"
    else
        warn "备份中无 env_backup，跳过"
    fi

    # ── Step 7: 运行迁移 & 缓存重建 ──────────────────────────────────
    step "运行数据库迁移和缓存重建..."

    if [[ -f "$INSTALL_DIR/artisan" ]]; then
        cd "$INSTALL_DIR"

        # 运行迁移（如果数据库 schema 比备份新）
        info "检查数据库迁移..."
        php artisan migrate --force --no-interaction 2>&1 | tail -3
        ok "数据库迁移完成"

        # 清理并重建缓存
        info "重建缓存..."
        php artisan config:clear --no-interaction  2>/dev/null || true
        php artisan route:clear --no-interaction   2>/dev/null || true
        php artisan view:clear --no-interaction    2>/dev/null || true
        php artisan cache:clear --no-interaction   2>/dev/null || true

        php artisan config:cache --no-interaction  2>/dev/null || true
        php artisan route:cache --no-interaction   2>/dev/null || true
        php artisan view:cache --no-interaction    2>/dev/null || true
        ok "缓存已重建"
    else
        warn "artisan 不存在，跳过迁移和缓存操作"
    fi

    # ── Step 8: 启动服务 ──────────────────────────────────────────────
    step "启动服务..."

    if [[ -f "$SCRIPT_DIR/start-linux.sh" ]]; then
        bash "$SCRIPT_DIR/start-linux.sh" 2>/dev/null && ok "服务已启动 (start-linux.sh)" || warn "start-linux.sh 执行异常"
    else
        info "请手动启动 Web 服务器和队列 Worker"
    fi

    # ── 完成 ──────────────────────────────────────────────────────────
    echo ""
    echo -e "${GREEN}══════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  恢复完成！${NC}"
    echo -e "${GREEN}══════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "  恢复摘要:"
    echo -e "    数据库:     ${GREEN}✓${NC} ${DB_NAME}"
    echo -e "    上传文件:   ${GREEN}✓${NC} storage/app/"
    echo -e "    配置文件:   ${GREEN}✓${NC} .env"
    echo -e "    数据库迁移: ${GREEN}✓${NC} 已执行"
    echo -e "    缓存:       ${GREEN}✓${NC} 已重建"
    echo ""
    echo -e "  ${YELLOW}建议: 运行 bash deploy/check.sh 验证系统状态${NC}"
    echo ""
}

# ═══════════════════════════════════════════════════════════════════════
#  list 命令
# ═══════════════════════════════════════════════════════════════════════
do_list() {
    echo ""
    echo -e "${BOLD}╔══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}║   牙科诊所管理系统 — 可用备份列表                      ║${NC}"
    echo -e "${BOLD}╚══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  搜索目录: ${CYAN}${OUTPUT_DIR}${NC}"
    echo ""

    if [[ ! -d "$OUTPUT_DIR" ]]; then
        warn "目录不存在: $OUTPUT_DIR"
        exit 0
    fi

    # 查找备份文件
    BACKUP_COUNT=0
    echo -e "  ${BOLD}%-50s  %10s  %s${NC}" | awk '{printf "  %-50s  %10s  %s\n", "文件名", "大小", "备份日期"}'
    echo "  $(printf '%.0s─' {1..80})"

    # 按修改时间排序 (最新在前)
    while IFS= read -r backup_file; do
        if [[ -z "$backup_file" ]]; then
            continue
        fi
        BACKUP_COUNT=$((BACKUP_COUNT + 1))
        FNAME=$(basename "$backup_file")
        FSIZE=$(human_size "$backup_file")

        # 尝试从文件名提取日期
        FDATE=$(echo "$FNAME" | grep -oE '[0-9]{4}-[0-9]{2}-[0-9]{2}-[0-9]{6}' || echo "unknown")
        if [[ "$FDATE" != "unknown" ]]; then
            FDATE_FMT="${FDATE:0:10} ${FDATE:11:2}:${FDATE:13:2}:${FDATE:15:2}"
        else
            # 回退到文件修改时间
            if [[ "$(uname -s)" == "Darwin" ]]; then
                FDATE_FMT=$(stat -f '%Sm' -t '%Y-%m-%d %H:%M:%S' "$backup_file" 2>/dev/null || echo "unknown")
            else
                FDATE_FMT=$(stat --printf='%y' "$backup_file" 2>/dev/null | cut -d'.' -f1 || echo "unknown")
            fi
        fi

        printf "  %-50s  %10s  %s\n" "$FNAME" "$FSIZE" "$FDATE_FMT"
    done < <(ls -t "$OUTPUT_DIR"/dental-backup-*.tar.gz 2>/dev/null || true)

    echo ""
    if [[ "$BACKUP_COUNT" -eq 0 ]]; then
        info "未找到备份文件 (dental-backup-*.tar.gz)"
    else
        ok "共找到 ${BACKUP_COUNT} 个备份文件"
    fi
    echo ""
}

# ═══════════════════════════════════════════════════════════════════════
#  主分发
# ═══════════════════════════════════════════════════════════════════════
case "$ACTION" in
    backup)  do_backup ;;
    restore) do_restore ;;
    list)    do_list ;;
    --help|-h) show_help ;;
    *)
        die "未知命令: $ACTION (可用: backup, restore, list)"
        ;;
esac
