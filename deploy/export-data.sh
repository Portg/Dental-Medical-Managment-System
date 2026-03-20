#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════
#  牙科诊所管理系统 - 数据导出工具 (机器迁移)
#  Dental Clinic Management System — Data Export for Machine Migration
#
#  用法:
#    bash deploy/export-data.sh [选项]
#
#  选项:
#    --install-dir DIR    安装目录 (默认: /opt/dental)
#    --output-dir DIR     输出目录 (默认: 当前目录)
#    --help               显示帮助
#
#  输出:
#    dental-export-{YYYY-MM-DD}.tar.gz — 完整迁移包
#
#  在新机器上导入:
#    1. 使用 install-linux.sh 安装全新系统
#    2. 运行: bash deploy/import-data.sh <导出文件>
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
TOTAL_STEPS=7

# ── 默认参数 ──────────────────────────────────────────────────────────
INSTALL_DIR="/opt/dental"
OUTPUT_DIR="."

# ── 帮助信息 ──────────────────────────────────────────────────────────
show_help() {
    echo "牙科诊所管理系统 — 数据导出工具 (机器迁移)"
    echo ""
    echo "用法: $0 [选项]"
    echo ""
    echo "选项:"
    echo "  --install-dir DIR    安装目录 (默认: /opt/dental)"
    echo "  --output-dir DIR     输出目录 (默认: 当前目录)"
    echo "  --help               显示此帮助"
    echo ""
    echo "输出:"
    echo "  dental-export-{YYYY-MM-DD}.tar.gz"
    echo ""
    echo "在新机器上导入:"
    echo "  1. 使用 install-linux.sh 安装全新系统"
    echo "  2. 运行: bash deploy/import-data.sh <导出文件>"
    exit 0
}

# ── 解析命令行参数 ────────────────────────────────────────────────────
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

# ── 读取版本 ──────────────────────────────────────────────────────────
VERSION="unknown"
if [[ -f "$INSTALL_DIR/VERSION" ]]; then
    VERSION="$(tr -d '[:space:]' < "$INSTALL_DIR/VERSION")"
fi

EXPORT_DATE=$(date +%Y-%m-%d)
EXPORT_TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
EXPORT_NAME="dental-export-${EXPORT_DATE}"

# ═══════════════════════════════════════════════════════════════════════
#  开始导出
# ═══════════════════════════════════════════════════════════════════════
echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║   牙科诊所管理系统 v${VERSION} — 数据导出 (机器迁移)       ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  安装目录: ${CYAN}${INSTALL_DIR}${NC}"
echo -e "  输出目录: ${CYAN}${OUTPUT_DIR}${NC}"
echo -e "  导出日期: ${CYAN}${EXPORT_TIMESTAMP}${NC}"
echo -e "  系统版本: ${CYAN}v${VERSION}${NC}"

# 验证安装目录
if [[ ! -f "$INSTALL_DIR/artisan" ]]; then
    die "安装目录无效: $INSTALL_DIR (artisan 文件不存在)"
fi

# 创建临时工作目录
WORK_DIR=$(mktemp -d)
PACKAGE_DIR="$WORK_DIR/$EXPORT_NAME"
mkdir -p "$PACKAGE_DIR"
trap "rm -rf '$WORK_DIR'" EXIT

# ── Step 1: 完整数据库导出 ────────────────────────────────────────────
step "导出完整数据库 (结构 + 数据)..."

# 查找 mysqldump
MYSQLDUMP_CMD=""
if command -v mysqldump &>/dev/null; then
    MYSQLDUMP_CMD="mysqldump"
elif command -v mariadb-dump &>/dev/null; then
    MYSQLDUMP_CMD="mariadb-dump"
else
    die "未找到 mysqldump 或 mariadb-dump"
fi

MYSQL_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
if [[ -n "$DB_PASS" ]]; then
    MYSQL_ARGS+=(-p"$DB_PASS")
fi

# 测试数据库连接
MYSQL_CMD=""
if command -v mysql &>/dev/null; then
    MYSQL_CMD="mysql"
elif command -v mariadb &>/dev/null; then
    MYSQL_CMD="mariadb"
else
    die "未找到 mysql 或 mariadb 客户端"
fi

if ! $MYSQL_CMD "${MYSQL_ARGS[@]}" -e "SELECT 1" &>/dev/null; then
    die "无法连接数据库 (${DB_HOST}:${DB_PORT})，请确认 MySQL 已启动"
fi

# 导出完整数据库（结构 + 数据）
DUMP_FILE="$PACKAGE_DIR/database.sql"
if ! $MYSQLDUMP_CMD "${MYSQL_ARGS[@]}" \
    --single-transaction \
    --routines \
    --triggers \
    --add-drop-table \
    --create-options \
    --set-charset \
    "$DB_NAME" > "$DUMP_FILE" 2>/dev/null; then
    die "数据库导出失败"
fi

# 获取表数量
TABLE_COUNT=$($MYSQL_CMD "${MYSQL_ARGS[@]}" -e \
    "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}';" -sN 2>/dev/null || echo "?")
DUMP_SIZE=$(human_size "$DUMP_FILE")
ok "数据库已导出: database.sql (${TABLE_COUNT} 张表, ${DUMP_SIZE})"

# ── Step 2: 导出用户上传文件 ──────────────────────────────────────────
step "导出用户上传文件..."

STORAGE_DIR="$INSTALL_DIR/storage/app"
if [[ -d "$STORAGE_DIR" ]]; then
    # 计算文件数量
    FILE_COUNT=$(find "$STORAGE_DIR" -type f 2>/dev/null | wc -l | tr -d ' ')
    tar -cf "$PACKAGE_DIR/storage_app.tar" -C "$INSTALL_DIR/storage" app 2>/dev/null
    STORAGE_SIZE=$(human_size "$PACKAGE_DIR/storage_app.tar")
    ok "用户上传文件已导出: storage_app.tar (${FILE_COUNT} 个文件, ${STORAGE_SIZE})"
else
    warn "storage/app/ 目录不存在，跳过"
    # 创建空归档作为占位符
    mkdir -p "$WORK_DIR/empty_app"
    tar -cf "$PACKAGE_DIR/storage_app.tar" -C "$WORK_DIR" empty_app 2>/dev/null
    rm -rf "$WORK_DIR/empty_app"
    info "已创建空的 storage_app.tar 占位"
fi

# ── Step 3: 导出 .env（密码脱敏）────────────────────────────────────
step "导出配置文件 (.env, 敏感信息已脱敏)..."

if [[ -f "$INSTALL_DIR/.env" ]]; then
    # 复制 .env 并脱敏敏感字段
    cp "$INSTALL_DIR/.env" "$PACKAGE_DIR/env_export"

    # 脱敏数据库密码
    if [[ "$(uname -s)" == "Darwin" ]]; then
        sed -i '' 's/^DB_PASSWORD=.*/DB_PASSWORD=__REPLACE_ME__/' "$PACKAGE_DIR/env_export"
        sed -i '' 's/^REDIS_PASSWORD=.*/REDIS_PASSWORD=__REPLACE_ME__/' "$PACKAGE_DIR/env_export"
        # 清空 APP_KEY（新机器应重新生成）
        sed -i '' 's/^APP_KEY=.*/APP_KEY=/' "$PACKAGE_DIR/env_export"
    else
        sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=__REPLACE_ME__/' "$PACKAGE_DIR/env_export"
        sed -i 's/^REDIS_PASSWORD=.*/REDIS_PASSWORD=__REPLACE_ME__/' "$PACKAGE_DIR/env_export"
        sed -i 's/^APP_KEY=.*/APP_KEY=/' "$PACKAGE_DIR/env_export"
    fi

    ok ".env 已导出 (DB_PASSWORD, REDIS_PASSWORD 已脱敏, APP_KEY 已清空)"
else
    warn ".env 不存在"
    touch "$PACKAGE_DIR/env_export"
fi

# ── Step 4: 导出 VERSION ─────────────────────────────────────────────
step "导出版本信息..."

if [[ -f "$INSTALL_DIR/VERSION" ]]; then
    cp "$INSTALL_DIR/VERSION" "$PACKAGE_DIR/VERSION"
    ok "VERSION: v${VERSION}"
else
    echo "$VERSION" > "$PACKAGE_DIR/VERSION"
    warn "VERSION 文件不存在，使用默认值: $VERSION"
fi

# ── Step 5: 生成 Manifest ────────────────────────────────────────────
step "生成导出清单 (manifest)..."

MANIFEST_FILE="$PACKAGE_DIR/manifest.txt"
cat > "$MANIFEST_FILE" <<MANIFESTEOF
# ═══════════════════════════════════════════════════════════════
#  牙科诊所管理系统 — 数据导出清单
#  Dental Clinic Management System — Export Manifest
# ═══════════════════════════════════════════════════════════════
#
# 导出日期: ${EXPORT_TIMESTAMP}
# 系统版本: v${VERSION}
# 源主机名: $(hostname 2>/dev/null || echo "unknown")
# 数据库名: ${DB_NAME}
# 数据库表: ${TABLE_COUNT} 张
# 操作系统: $(uname -s) $(uname -r)
#
# ── 包含文件 ──────────────────────────────────────────────────
#
# database.sql      完整数据库转储 (结构 + 数据, 含存储过程/触发器)
# storage_app.tar   用户上传文件 (storage/app/)
# env_export        .env 配置 (DB_PASSWORD 已脱敏为 __REPLACE_ME__)
# VERSION           系统版本号
# manifest.txt      本清单文件
#
# ── 导入步骤 ──────────────────────────────────────────────────
#
# 1. 在新机器上使用 install-linux.sh 安装全新系统
#    bash deploy/install-linux.sh --install-dir /opt/dental
#
# 2. 运行导入脚本:
#    bash deploy/import-data.sh ${EXPORT_NAME}.tar.gz
#
# 3. 或手动导入:
#    a. 解压导出包:
#       tar -xzf ${EXPORT_NAME}.tar.gz
#
#    b. 编辑 env_export，填入新机器的数据库密码:
#       将 DB_PASSWORD=__REPLACE_ME__ 替换为实际密码
#       复制到安装目录: cp env_export /opt/dental/.env
#
#    c. 导入数据库:
#       mysql -u root -p ${DB_NAME} < database.sql
#
#    d. 恢复上传文件:
#       tar -xf storage_app.tar -C /opt/dental/storage/
#
#    e. 生成 APP_KEY 并重建缓存:
#       cd /opt/dental
#       php artisan key:generate --force
#       php artisan migrate --force
#       php artisan config:cache
#       php artisan route:cache
#       php artisan view:cache
#
# ═══════════════════════════════════════════════════════════════
MANIFESTEOF

ok "清单已生成: manifest.txt"

# ── Step 6: 打包为 tar.gz ────────────────────────────────────────────
step "打包导出文件..."

mkdir -p "$OUTPUT_DIR"
EXPORT_FILE="$OUTPUT_DIR/${EXPORT_NAME}.tar.gz"

# 如果同名文件已存在，添加时间戳后缀
if [[ -f "$EXPORT_FILE" ]]; then
    OLD_EXPORT_NAME="$EXPORT_NAME"
    EXPORT_NAME="dental-export-${EXPORT_DATE}-$(date +%H%M%S)"
    EXPORT_FILE="$OUTPUT_DIR/${EXPORT_NAME}.tar.gz"
    mv "$WORK_DIR/$OLD_EXPORT_NAME" "$WORK_DIR/$EXPORT_NAME"
    PACKAGE_DIR="$WORK_DIR/$EXPORT_NAME"
    info "同名文件已存在，改用: ${EXPORT_NAME}.tar.gz"
fi

tar -czf "$EXPORT_FILE" -C "$WORK_DIR" "$EXPORT_NAME"

FINAL_SIZE=$(human_size "$EXPORT_FILE")
ok "导出包: ${EXPORT_FILE} (${FINAL_SIZE})"

# ── Step 7: 验证导出包 ───────────────────────────────────────────────
step "验证导出包完整性..."

if tar -tzf "$EXPORT_FILE" &>/dev/null; then
    echo ""
    echo -e "  ${DIM:-}包内容:${NC}"
    tar -tzf "$EXPORT_FILE" | while IFS= read -r entry; do
        echo -e "    ${entry}"
    done
    echo ""
    ok "导出包验证通过"
else
    fail "导出包验证失败！"
    exit 1
fi

# ═══════════════════════════════════════════════════════════════════════
#  完成
# ═══════════════════════════════════════════════════════════════════════
echo ""
echo -e "${GREEN}══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  数据导出完成！${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${BOLD}导出摘要:${NC}"
echo -e "    导出文件:   ${CYAN}${EXPORT_FILE}${NC}"
echo -e "    文件大小:   ${CYAN}${FINAL_SIZE}${NC}"
echo -e "    系统版本:   v${VERSION}"
echo -e "    数据库:     ${DB_NAME} (${TABLE_COUNT} 张表)"
echo -e "    导出日期:   ${EXPORT_TIMESTAMP}"
echo ""
echo -e "  ${BOLD}在新机器上导入:${NC}"
echo ""
echo -e "    ${CYAN}1.${NC} 使用 install-linux.sh 安装全新系统:"
echo -e "       ${CYAN}bash deploy/install-linux.sh --install-dir /opt/dental${NC}"
echo ""
echo -e "    ${CYAN}2.${NC} 运行导入脚本:"
echo -e "       ${CYAN}bash deploy/import-data.sh ${EXPORT_FILE}${NC}"
echo ""
echo -e "    ${YELLOW}注意: 导入前请编辑 env_export 中的 DB_PASSWORD=__REPLACE_ME__${NC}"
echo -e "    ${YELLOW}      替换为新机器的实际数据库密码。${NC}"
echo ""
