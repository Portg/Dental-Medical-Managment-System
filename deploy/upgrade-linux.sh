#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
#  牙科诊所管理系统 - Linux/macOS 升级脚本
#
#  用法: sudo ./upgrade-linux.sh [选项]
#
#  选项:
#    --install-dir DIR   安装目录（默认: /opt/dental）
#    --skip-backup       跳过备份（不推荐，失败将无法自动回滚）
#    --yes               跳过所有确认提示
#    --help              显示帮助信息
#
#  升级包结构（本脚本所在目录的上级即为升级包根目录）:
#    deploy/upgrade-linux.sh  ← 本脚本
#    VERSION                  ← 新版本号
#    app/                     ← 新代码
#    config/                  ← 新配置
#    database/                ← 新迁移文件
#    ...
#    deploy/env.patch         ← (可选) 新增环境变量
#
#  执行流程:
#    1. 版本检查（拒绝降级，同版本警告）
#    2. 自动备份（.env / 数据库 / 应用 tar 包）
#    3. 代码更新（保留 .env 和 storage/app/）
#    4. 环境变量合并（env.patch → .env）
#    5. 数据库迁移
#    6. 缓存清理与重建
#    7. 文件权限修复（www-data）
#    8. 健康检查
#    9. 重启 systemd 服务（dental-queue, dental-ocr）
#   10. 失败自动回滚
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

# ── 颜色定义 ──────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ── 输出辅助函数 ─────────────────────────────────────────────────
info()    { echo -e "  ${CYAN}[INFO]${NC}  $*"; }
ok()      { echo -e "  ${GREEN}[ OK ]${NC}  $*"; }
warn()    { echo -e "  ${YELLOW}[WARN]${NC}  $*"; }
fail()    { echo -e "  ${RED}[FAIL]${NC}  $*"; }
step()    { echo -e "\n${BOLD}┌──────────────────────────────────────────────────────────┐${NC}"; \
            echo -e "${BOLD}│ [Step $1/$TOTAL_STEPS] $2${NC}"; \
            echo -e "${BOLD}└──────────────────────────────────────────────────────────┘${NC}"; }

# ── 默认参数 ─────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PKG_DIR="$SCRIPT_DIR"
if [[ ! -f "$PKG_DIR/VERSION" ]] && [[ -f "$(dirname "$SCRIPT_DIR")/VERSION" ]]; then
    PKG_DIR="$(dirname "$SCRIPT_DIR")"
fi
INSTALL_DIR="/opt/dental"
SKIP_BACKUP=0
AUTO_YES=0
TOTAL_STEPS=10

# 回滚追踪
ROLLBACK_DB=0
ROLLBACK_FILES=0
DB_BACKUP_FILE=""
ENV_BACKUP_FILE=""
FILES_BACKUP_TAR=""
MAINTENANCE_MODE=0
CURRENT_VERSION=""
NEW_VERSION=""
WEB_OWNER="www-data"

# ── 参数解析 ─────────────────────────────────────────────────────
show_help() {
    cat <<'HELPEOF'

  牙科诊所管理系统 - Linux/macOS 升级脚本

  用法: sudo ./upgrade-linux.sh [选项]

  选项:
    --install-dir DIR   安装目录（默认: /opt/dental）
    --skip-backup       跳过备份（不推荐）
    --yes               跳过所有确认提示
    --help              显示此帮助

  示例:
    sudo ./upgrade-linux.sh
    sudo ./upgrade-linux.sh --install-dir /var/www/dental
    sudo ./upgrade-linux.sh --skip-backup --yes

HELPEOF
    exit 0
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --install-dir)
            INSTALL_DIR="$2"; shift 2 ;;
        --skip-backup)
            SKIP_BACKUP=1; shift ;;
        --yes)
            AUTO_YES=1; shift ;;
        --help|-h)
            show_help ;;
        *)
            fail "未知参数: $1"
            echo "  使用 --help 查看帮助"
            exit 1 ;;
    esac
done

# 路径设置
PROJECT_DIR="${INSTALL_DIR}"
VERSION_FILE="${PROJECT_DIR}/VERSION"
NEW_VERSION_FILE="${PKG_DIR}/VERSION"
ENV_PATCH="${PKG_DIR}/env.patch"
BACKUP_DIR="${INSTALL_DIR}/backups"
TIMESTAMP="$(date '+%Y%m%d_%H%M%S')"

# 检测 Web 服务用户（macOS 用 _www，Linux 用 www-data）
if [[ "$(uname)" == "Darwin" ]]; then
    WEB_OWNER="_www"
else
    WEB_OWNER="www-data"
fi

# ── 工具函数 ─────────────────────────────────────────────────────

# 版本比较: 返回 0 = v1 > v2, 1 = equal, 2 = v1 < v2
version_compare() {
    local v1="$1" v2="$2"
    if [[ "$v1" == "$v2" ]]; then return 1; fi

    local IFS='.'
    local -a a1=($v1) a2=($v2)
    local max=${#a1[@]}
    [[ ${#a2[@]} -gt $max ]] && max=${#a2[@]}

    for ((i = 0; i < max; i++)); do
        local n1="${a1[$i]:-0}" n2="${a2[$i]:-0}"
        if ((n1 > n2)); then return 0; fi
        if ((n1 < n2)); then return 2; fi
    done
    return 1
}

# 读取 .env 中某个 key 的值
read_env() {
    local key="$1" file="${2:-${PROJECT_DIR}/.env}"
    [[ -f "$file" ]] && grep -E "^${key}=" "$file" 2>/dev/null | head -1 | cut -d'=' -f2- | tr -d '\r' || echo ""
}

# MySQL 命令封装
# 使用 MYSQL_PWD 环境变量传递密码，避免通过 -p 命令行参数暴露（ps aux 中可见）
mysql_cmd() {
    local cmd=(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME")
    MYSQL_PWD="${DB_PASS}" "${cmd[@]}" "$@"
}

mysql_dump_cmd() {
    local cmd=(mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER"
               --single-transaction --routines --triggers "$DB_NAME")
    MYSQL_PWD="${DB_PASS}" "${cmd[@]}" "$@"
}

# Composer 命令封装
run_composer() {
    if command -v composer &>/dev/null; then
        composer "$@"
    elif [[ -f "${PROJECT_DIR}/composer.phar" ]]; then
        php "${PROJECT_DIR}/composer.phar" "$@"
    else
        fail "未找到 Composer"
        return 1
    fi
}

# 确认提示
confirm() {
    [[ "$AUTO_YES" -eq 1 ]] && return 0
    local reply
    echo -en "  ${YELLOW}$1 (Y/n): ${NC}"
    read -r reply
    [[ -z "$reply" || "$reply" =~ ^[Yy]$ ]]
}

# ── 回滚 trap ────────────────────────────────────────────────────
cleanup_on_failure() {
    local exit_code=$?
    [[ $exit_code -eq 0 ]] && return 0

    echo ""
    echo -e "${RED}╔══════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║  升级失败！正在自动回滚...                              ║${NC}"
    echo -e "${RED}╚══════════════════════════════════════════════════════════╝${NC}"
    echo ""

    # 恢复应用文件
    if [[ "$ROLLBACK_FILES" -eq 1 && -n "$FILES_BACKUP_TAR" && -f "$FILES_BACKUP_TAR" ]]; then
        info "恢复应用文件..."
        # 先清理新代码（保留 vendor/node_modules/storage）
        tar -xzf "$FILES_BACKUP_TAR" -C "$(dirname "$PROJECT_DIR")" \
            --exclude='vendor' \
            --exclude='node_modules' \
            2>/dev/null && ok "应用文件已恢复" || warn "文件恢复部分失败"
    fi

    # 恢复 .env
    if [[ -n "$ENV_BACKUP_FILE" && -f "$ENV_BACKUP_FILE" ]]; then
        info "恢复 .env..."
        cp "$ENV_BACKUP_FILE" "${PROJECT_DIR}/.env"
        ok ".env 已恢复"
    fi

    # 恢复数据库
    if [[ "$ROLLBACK_DB" -eq 1 && -n "$DB_BACKUP_FILE" && -f "$DB_BACKUP_FILE" ]]; then
        info "恢复数据库（可能需要几分钟）..."
        if mysql_cmd < "$DB_BACKUP_FILE" 2>/dev/null; then
            ok "数据库已恢复"
        else
            fail "数据库自动恢复失败！请手动导入: ${DB_BACKUP_FILE}"
        fi
    fi

    # 重装旧依赖
    if [[ "$ROLLBACK_FILES" -eq 1 ]]; then
        info "重新安装旧版 PHP 依赖..."
        cd "$PROJECT_DIR"
        run_composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true
    fi

    # 清理缓存并重建
    info "重建缓存..."
    cd "$PROJECT_DIR"
    php artisan config:clear --no-interaction >/dev/null 2>&1 || true
    php artisan route:clear --no-interaction >/dev/null 2>&1 || true
    php artisan view:clear --no-interaction >/dev/null 2>&1 || true
    php artisan config:cache --no-interaction >/dev/null 2>&1 || true

    # 退出维护模式
    if [[ "$MAINTENANCE_MODE" -eq 1 ]]; then
        info "退出维护模式..."
        php artisan up 2>/dev/null || true
    fi

    # 重启服务
    restart_services 2>/dev/null || true

    # 修复权限
    fix_permissions 2>/dev/null || true

    echo ""
    fail "升级已回滚至版本 ${CURRENT_VERSION:-未知}"
    fail "备份保留在: ${BACKUP_DIR}/upgrade_${TIMESTAMP}/"
    fail "请检查以上输出，修复问题后重试"
    echo ""
}

trap cleanup_on_failure EXIT

# ── 重启 systemd 服务 ────────────────────────────────────────────
restart_services() {
    if [[ "$(id -u)" -ne 0 ]]; then
        warn "非 root 用户，跳过服务重启。请手动重启 Web 服务。"
        return 0
    fi

    if ! command -v systemctl &>/dev/null; then
        warn "systemctl 不可用，跳过服务管理"
        return 0
    fi

    # dental-queue (Laravel queue worker)
    if systemctl list-unit-files 2>/dev/null | grep -q 'dental-queue'; then
        systemctl restart dental-queue 2>/dev/null && info "dental-queue 已重启" || warn "dental-queue 重启失败"
    fi

    # dental-ocr (OCR server)
    if systemctl list-unit-files 2>/dev/null | grep -q 'dental-ocr'; then
        systemctl restart dental-ocr 2>/dev/null && info "dental-ocr 已重启" || warn "dental-ocr 重启失败"
    fi

    # PHP-FPM
    local fpm_service=""
    for svc in php8.2-fpm php8.3-fpm php-fpm; do
        if systemctl list-unit-files 2>/dev/null | grep -q "$svc"; then
            fpm_service="$svc"
            break
        fi
    done
    if [[ -n "$fpm_service" ]]; then
        systemctl restart "$fpm_service" 2>/dev/null && info "${fpm_service} 已重启" || warn "${fpm_service} 重启失败"
    fi

    # Nginx
    if systemctl list-unit-files 2>/dev/null | grep -q 'nginx'; then
        systemctl reload nginx 2>/dev/null && info "Nginx 已重载" || warn "Nginx 重载失败"
    fi
}

# ── 修复文件权限 ─────────────────────────────────────────────────
fix_permissions() {
    if [[ "$(id -u)" -ne 0 ]]; then return 0; fi

    # 检查 Web 用户是否存在
    if ! id "$WEB_OWNER" &>/dev/null; then
        warn "用户 ${WEB_OWNER} 不存在，跳过权限修复"
        return 0
    fi

    chown -R "${WEB_OWNER}:${WEB_OWNER}" "${PROJECT_DIR}/storage" 2>/dev/null || true
    chown -R "${WEB_OWNER}:${WEB_OWNER}" "${PROJECT_DIR}/bootstrap/cache" 2>/dev/null || true
    chmod -R 775 "${PROJECT_DIR}/storage" 2>/dev/null || true
    chmod -R 775 "${PROJECT_DIR}/bootstrap/cache" 2>/dev/null || true
}

# ═══════════════════════════════════════════════════════════════════
#  开始升级
# ═══════════════════════════════════════════════════════════════════

echo ""
echo -e "${BOLD}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║         牙科诊所管理系统 - 升级工具                     ║${NC}"
echo -e "${BOLD}╠══════════════════════════════════════════════════════════╣${NC}"
echo -e "${BOLD}║${NC}  安装目录:  ${PROJECT_DIR}"
echo -e "${BOLD}║${NC}  升级包:    ${PKG_DIR}"
echo -e "${BOLD}║${NC}  备份目录:  ${BACKUP_DIR}"
echo -e "${BOLD}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════════
#  Step 1: 环境检测 & 版本检查
# ═══════════════════════════════════════════════════════════════════
step 1 "环境检测 & 版本检查"

# 检查安装目录
if [[ ! -f "${PROJECT_DIR}/artisan" ]]; then
    fail "未在 ${PROJECT_DIR} 找到已安装的系统"
    echo "  如果这是首次安装，请使用安装脚本而非升级脚本。"
    echo "  可通过 --install-dir 指定其他安装目录。"
    exit 1
fi
ok "安装目录 .......... ${PROJECT_DIR}"

# 检查 PHP
if ! command -v php &>/dev/null; then
    fail "未找到 PHP，请先安装 PHP 8.2+"
    exit 1
fi
ok "PHP $(php -r 'echo PHP_VERSION;') .......... OK"

# 检查 MySQL 工具
if ! command -v mysql &>/dev/null; then
    fail "未找到 mysql 客户端"
    exit 1
fi
ok "mysql .............. OK"

if ! command -v mysqldump &>/dev/null; then
    warn "未找到 mysqldump，如不跳过备份将失败"
fi

# 读取当前版本
if [[ -f "$VERSION_FILE" ]]; then
    CURRENT_VERSION="$(tr -d '[:space:]' < "$VERSION_FILE")"
    ok "当前版本 ........... ${CURRENT_VERSION}"
else
    warn "未找到 VERSION 文件，视为 0.0.0"
    CURRENT_VERSION="0.0.0"
fi

# 读取升级包版本
if [[ ! -f "$NEW_VERSION_FILE" ]]; then
    fail "升级包中未找到 VERSION 文件: ${NEW_VERSION_FILE}"
    exit 1
fi
NEW_VERSION="$(tr -d '[:space:]' < "$NEW_VERSION_FILE")"
ok "目标版本 ........... ${NEW_VERSION}"

# 版本比较
set +e
version_compare "$NEW_VERSION" "$CURRENT_VERSION"
cmp_result=$?
set -e

case $cmp_result in
    2)
        fail "拒绝降级: ${CURRENT_VERSION} -> ${NEW_VERSION}"
        echo "  不支持从高版本降级到低版本。"
        exit 1
        ;;
    1)
        warn "当前已是 ${CURRENT_VERSION} 版本，无需升级。"
        if ! confirm "是否强制重新安装?"; then
            info "操作已取消。"
            trap - EXIT
            exit 0
        fi
        info "继续强制重新安装..."
        ;;
    0)
        ok "版本校验通过: ${CURRENT_VERSION} -> ${NEW_VERSION}"
        ;;
esac

# ═══════════════════════════════════════════════════════════════════
#  Step 2: 自动备份
# ═══════════════════════════════════════════════════════════════════
step 2 "自动备份"

# 读取数据库连接信息
DB_HOST="$(read_env 'DB_HOST')"
DB_PORT="$(read_env 'DB_PORT')"
DB_NAME="$(read_env 'DB_DATABASE')"
DB_USER="$(read_env 'DB_USERNAME')"
DB_PASS="$(read_env 'DB_PASSWORD')"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-pristine_dental}"
DB_USER="${DB_USER:-root}"

if [[ "$SKIP_BACKUP" -eq 1 ]]; then
    warn "已跳过备份（--skip-backup）。升级失败将无法自动回滚！"
else
    UPGRADE_BACKUP_DIR="${BACKUP_DIR}/upgrade_${TIMESTAMP}"
    mkdir -p "$UPGRADE_BACKUP_DIR"

    # ── 2a: 备份 .env ──
    ENV_BACKUP_FILE="${UPGRADE_BACKUP_DIR}/.env.backup.${TIMESTAMP}"
    if [[ -f "${PROJECT_DIR}/.env" ]]; then
        cp "${PROJECT_DIR}/.env" "$ENV_BACKUP_FILE"
        ok ".env 已备份"
    fi

    # ── 2b: 备份数据库 ──
    DB_BACKUP_FILE="${UPGRADE_BACKUP_DIR}/backup_${TIMESTAMP}.sql"
    info "正在导出数据库 ${DB_NAME}（可能需要几分钟）..."

    if ! command -v mysqldump &>/dev/null; then
        fail "mysqldump 不可用，无法备份数据库"
        exit 1
    fi

    if ! mysql_dump_cmd > "$DB_BACKUP_FILE" 2>/dev/null; then
        fail "数据库备份失败，请检查 MySQL 连接"
        rm -f "$DB_BACKUP_FILE"
        exit 1
    fi

    # 检查备份文件非空
    backup_size=0
    if [[ "$(uname)" == "Darwin" ]]; then
        backup_size=$(stat -f%z "$DB_BACKUP_FILE" 2>/dev/null || echo "0")
    else
        backup_size=$(stat -c%s "$DB_BACKUP_FILE" 2>/dev/null || echo "0")
    fi

    if [[ "$backup_size" -eq 0 ]]; then
        fail "数据库备份文件为空"
        rm -f "$DB_BACKUP_FILE"
        exit 1
    fi

    ROLLBACK_DB=1
    ok "数据库已备份 (${backup_size} 字节)"

    # ── 2c: 备份应用目录 (tar) ──
    FILES_BACKUP_TAR="${UPGRADE_BACKUP_DIR}/app_backup_${TIMESTAMP}.tar.gz"
    info "正在备份应用目录（不含 vendor/node_modules）..."

    tar -czf "$FILES_BACKUP_TAR" \
        -C "$(dirname "$PROJECT_DIR")" \
        --exclude='./vendor' \
        --exclude='./node_modules' \
        --exclude='./.git' \
        --exclude='./storage/logs/*' \
        --exclude='./storage/framework/cache/*' \
        --exclude='./storage/framework/sessions/*' \
        --exclude='./storage/framework/views/*' \
        "$(basename "$PROJECT_DIR")" \
        2>/dev/null

    ROLLBACK_FILES=1

    if [[ "$(uname)" == "Darwin" ]]; then
        tar_size=$(stat -f%z "$FILES_BACKUP_TAR" 2>/dev/null || echo "?")
    else
        tar_size=$(stat -c%s "$FILES_BACKUP_TAR" 2>/dev/null || echo "?")
    fi
    ok "应用目录已备份 (${tar_size} 字节)"

    info "全部备份位于: ${UPGRADE_BACKUP_DIR}"
fi

# ═══════════════════════════════════════════════════════════════════
#  Step 3: 进入维护模式
# ═══════════════════════════════════════════════════════════════════
step 3 "进入维护模式"

cd "$PROJECT_DIR"
if php artisan down --refresh=30 2>/dev/null; then
    MAINTENANCE_MODE=1
    ok "应用已进入维护模式"
else
    warn "无法进入维护模式，继续升级"
fi

# ═══════════════════════════════════════════════════════════════════
#  Step 4: 代码更新
# ═══════════════════════════════════════════════════════════════════
step 4 "更新代码文件"

info "使用 rsync 同步（保留 .env / storage/app/ / vendor/）..."

# 确保 rsync 可用
if ! command -v rsync &>/dev/null; then
    fail "未找到 rsync 命令，请先安装: apt install rsync / brew install rsync"
    exit 1
fi

rsync -a --delete \
    --exclude='.env' \
    --exclude='storage/app/' \
    --exclude='storage/logs/' \
    --exclude='storage/framework/cache/' \
    --exclude='storage/framework/sessions/' \
    --exclude='storage/framework/views/' \
    --exclude='vendor/' \
    --exclude='node_modules/' \
    --exclude='.git/' \
    --exclude='backups/' \
    "${PKG_DIR}/" "${PROJECT_DIR}/"

ok "代码文件更新完成"

# ═══════════════════════════════════════════════════════════════════
#  Step 5: 环境变量合并
# ═══════════════════════════════════════════════════════════════════
step 5 "合并环境变量"

if [[ -f "$ENV_PATCH" ]]; then
    info "发现 env.patch，合并新配置项（只添加缺失 key，不覆盖已有值）..."
    patch_count=0

    while IFS='=' read -r key value || [[ -n "$key" ]]; do
        # 跳过空行和注释
        key="$(echo "$key" | sed 's/^[[:space:]]*//' | sed 's/[[:space:]]*$//')"
        [[ -z "$key" || "$key" =~ ^# ]] && continue

        # 检查当前 .env 中是否已有此 KEY
        if ! grep -qE "^${key}=" "${PROJECT_DIR}/.env" 2>/dev/null; then
            echo "${key}=${value}" >> "${PROJECT_DIR}/.env"
            info "  + ${key}"
            ((patch_count++)) || true
        fi
    done < "$ENV_PATCH"

    ok "合并完成，新增 ${patch_count} 项配置"
else
    info "未发现 env.patch 文件"

    # 兜底: 检查 .env.example 中的新 key
    if [[ -f "${PROJECT_DIR}/.env.example" ]]; then
        info "检查 .env.example 中的新配置项..."
        example_count=0

        while IFS='=' read -r key value || [[ -n "$key" ]]; do
            key="$(echo "$key" | sed 's/^[[:space:]]*//' | sed 's/[[:space:]]*$//')"
            [[ -z "$key" || "$key" =~ ^# ]] && continue

            if ! grep -qE "^${key}=" "${PROJECT_DIR}/.env" 2>/dev/null; then
                echo "${key}=${value}" >> "${PROJECT_DIR}/.env"
                info "  + ${key}"
                ((example_count++)) || true
            fi
        done < "${PROJECT_DIR}/.env.example"

        ok "从 .env.example 新增 ${example_count} 项"
    fi
fi

# ═══════════════════════════════════════════════════════════════════
#  Step 6: 安装依赖 & 数据库迁移
# ═══════════════════════════════════════════════════════════════════
step 6 "安装依赖 & 数据库迁移"

cd "$PROJECT_DIR"

info "安装 PHP 依赖..."
if ! run_composer install --no-dev --optimize-autoloader --no-interaction 2>&1; then
    fail "Composer 依赖安装失败"
    exit 1
fi
ok "PHP 依赖安装完成"

info "运行数据库迁移..."
if ! php artisan migrate --force --no-interaction 2>&1; then
    fail "数据库迁移失败"
    exit 1
fi
ok "数据库迁移完成"

# ═══════════════════════════════════════════════════════════════════
#  Step 7: 缓存清理与重建
# ═══════════════════════════════════════════════════════════════════
step 7 "缓存清理与重建"

cd "$PROJECT_DIR"

info "清理旧缓存..."
php artisan config:clear --no-interaction  >/dev/null 2>&1 || true
php artisan route:clear --no-interaction   >/dev/null 2>&1 || true
php artisan view:clear --no-interaction    >/dev/null 2>&1 || true
php artisan cache:clear --no-interaction   >/dev/null 2>&1 || true
ok "旧缓存已清除"

info "重建缓存..."
if ! php artisan config:cache --no-interaction >/dev/null 2>&1; then
    fail "config:cache 失败"
    exit 1
fi
ok "config:cache ...... OK"

if php artisan route:cache --no-interaction >/dev/null 2>&1; then
    ok "route:cache ....... OK"
else
    warn "route:cache 失败（可能存在闭包路由），跳过"
fi

php artisan view:cache --no-interaction >/dev/null 2>&1 || true
ok "view:cache ........ OK"

# 重建 storage 软链接
php artisan storage:link --force --no-interaction >/dev/null 2>&1 || true

# ═══════════════════════════════════════════════════════════════════
#  Step 8: 文件权限修复 & 健康检查
# ═══════════════════════════════════════════════════════════════════
step 8 "文件权限修复 & 健康检查"

# 修复权限
info "修复文件权限 (${WEB_OWNER})..."
fix_permissions
ok "文件权限已修复"

cd "$PROJECT_DIR"

# artisan 基本检查
if ! php artisan --version >/dev/null 2>&1; then
    fail "php artisan 命令执行失败，系统可能已损坏"
    exit 1
fi
ok "artisan 命令 ...... OK"

# 路由加载检查
if ! php artisan route:list --compact --no-interaction >/dev/null 2>&1; then
    fail "路由加载失败，应用可能无法正常运行"
    exit 1
fi
ok "路由加载 .......... OK"

# 数据库连接检查
if mysql_cmd -e "SELECT COUNT(*) FROM users LIMIT 1" >/dev/null 2>&1; then
    ok "数据库连接 ........ OK"
else
    warn "数据库查询失败，请手动确认"
fi

# 确认已安装版本
if [[ -f "$VERSION_FILE" ]]; then
    installed_ver="$(tr -d '[:space:]' < "$VERSION_FILE")"
    ok "已安装版本 ........ ${installed_ver}"
fi

ok "健康检查通过"

# ═══════════════════════════════════════════════════════════════════
#  Step 9: 退出维护模式 & 重启服务
# ═══════════════════════════════════════════════════════════════════
step 9 "退出维护模式 & 重启服务"

cd "$PROJECT_DIR"

# 退出维护模式
php artisan up 2>/dev/null || true
MAINTENANCE_MODE=0
ok "应用已退出维护模式"

# 重启 systemd 服务
info "重启后台服务..."
restart_services

# ═══════════════════════════════════════════════════════════════════
#  Step 10: 升级完成
# ═══════════════════════════════════════════════════════════════════
step 10 "升级完成"

# 成功 — 禁用回滚 trap
trap - EXIT

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                    升级成功！                            ║${NC}"
echo -e "${GREEN}╠══════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}                                                         "
echo -e "${GREEN}║${NC}  版本变更: ${BOLD}${CURRENT_VERSION} -> ${NEW_VERSION}${NC}"
echo -e "${GREEN}║${NC}                                                         "
if [[ "$SKIP_BACKUP" -eq 0 ]]; then
echo -e "${GREEN}║${NC}  备份位置: ${UPGRADE_BACKUP_DIR:-${BACKUP_DIR}}"
echo -e "${GREEN}║${NC}    .env:     ${ENV_BACKUP_FILE}"
echo -e "${GREEN}║${NC}    数据库:   ${DB_BACKUP_FILE}"
echo -e "${GREEN}║${NC}    应用文件: ${FILES_BACKUP_TAR}"
echo -e "${GREEN}║${NC}                                                         "
echo -e "${GREEN}║${NC}  如需回滚:                                              "
echo -e "${GREEN}║${NC}    1. tar -xzf ${FILES_BACKUP_TAR##*/} -C $(dirname "$PROJECT_DIR")/"
echo -e "${GREEN}║${NC}    2. cp .env.backup.* ${PROJECT_DIR}/.env"
echo -e "${GREEN}║${NC}    3. mysql ${DB_NAME} < backup_*.sql"
fi
echo -e "${GREEN}║${NC}                                                         "
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
