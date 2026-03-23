#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════
#  牙科诊所管理系统 — 构建脚本
#
#  用法:
#    ./deploy/build.sh --target win|linux|mac [--upgrade] [--skip-obfuscate] [--version X.Y.Z]
#
#  示例:
#    ./deploy/build.sh --target win                          # Windows 全量安装包
#    ./deploy/build.sh --target linux --upgrade              # Linux 升级包
#    ./deploy/build.sh --target mac --skip-obfuscate         # macOS 不混淆
#    ./deploy/build.sh --target win --version 2.0.0          # 指定版本号
# ═══════════════════════════════════════════════════════════════════════

set -euo pipefail

# ── 颜色定义 ───────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# ── 输出函数 ───────────────────────────────────────────────────────────
STEP_NUM=0
TOTAL_STEPS=0

step() {
    STEP_NUM=$((STEP_NUM + 1))
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}[${STEP_NUM}/${TOTAL_STEPS}]${NC} ${CYAN}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

info() {
    echo -e "  ${GREEN}✓${NC} $1"
}

warn() {
    echo -e "  ${YELLOW}!${NC} $1"
}

error() {
    echo -e "  ${RED}✗${NC} $1" >&2
}

fatal() {
    echo ""
    echo -e "${RED}════════════════════════════════════════════════════════════════${NC}" >&2
    echo -e "${RED}  构建失败: $1${NC}" >&2
    echo -e "${RED}════════════════════════════════════════════════════════════════${NC}" >&2
    exit 1
}

# ── 帮助信息 ───────────────────────────────────────────────────────────
usage() {
    cat <<'USAGE'
牙科诊所管理系统 — 构建脚本

用法:
  ./deploy/build.sh --target <platform> [选项]

必选参数:
  --target <win|linux|mac>     目标平台

可选参数:
  --upgrade                    生成升级包（仅代码+迁移，不含SQL和运行时依赖）
  --skip-obfuscate             跳过 PHP 代码混淆
  --skip-ocr                   跳过 OCR Python wheels 打包（减小包体积）
  --version <X.Y.Z>            覆盖 VERSION 文件中的版本号
  --laragon-url <url>          Windows: 指定 laragon-wamp.exe 下载地址（.exe 直链）
  -h, --help                   显示此帮助信息

环境变量（均可选，有默认值）:
  PYTHON_DOWNLOAD_URL          Python Windows x64 安装器下载地址（OCR 用）

示例:
  ./deploy/build.sh --target win --laragon-url https://example.com/laragon-wamp.exe
  ./deploy/build.sh --target win                          # Windows 安装包（需手动放置 laragon-wamp.exe）
  ./deploy/build.sh --target linux --upgrade              # Linux 升级包
  ./deploy/build.sh --target mac --skip-obfuscate         # macOS 不混淆
  ./deploy/build.sh --target win --version 2.0.0          # 指定版本号
USAGE
    exit 0
}

# ── 参数解析 ───────────────────────────────────────────────────────────
TARGET=""
UPGRADE=false
SKIP_OBFUSCATE=false
SKIP_OCR=false
VERSION_OVERRIDE=""
LARAGON_INSTALLER_EXE=""
LARAGON_URL_OVERRIDE=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --target)
            TARGET="${2:-}"
            [[ -z "$TARGET" ]] && fatal "--target 需要指定平台 (win|linux|mac)"
            shift 2
            ;;
        --upgrade)
            UPGRADE=true
            shift
            ;;
        --skip-obfuscate)
            SKIP_OBFUSCATE=true
            shift
            ;;
        --skip-ocr)
            SKIP_OCR=true
            shift
            ;;
        --version)
            VERSION_OVERRIDE="${2:-}"
            [[ -z "$VERSION_OVERRIDE" ]] && fatal "--version 需要指定版本号"
            shift 2
            ;;
        --laragon-url)
            LARAGON_URL_OVERRIDE="${2:-}"
            [[ -z "$LARAGON_URL_OVERRIDE" ]] && fatal "--laragon-url 需要指定下载地址"
            shift 2
            ;;
        -h|--help)
            usage
            ;;
        *)
            fatal "未知参数: $1 (使用 --help 查看帮助)"
            ;;
    esac
done

# 校验必选参数
[[ -z "$TARGET" ]] && fatal "必须指定 --target (win|linux|mac)，使用 --help 查看帮助"

case "$TARGET" in
    win|linux|mac) ;;
    *) fatal "--target 仅支持 win、linux、mac，当前值: $TARGET" ;;
esac

# ── 下载并解压一个 zip 包的辅助函数 ──────────────────────────────
# 用法: download_and_extract <url> <cache_zip_path> <extract_dir> <component_name>
# 返回: 0=成功, 1=失败
download_and_extract() {
    local url="$1" cache_zip="$2" extract_dir="$3" name="$4"

    # 已有解压结果 → 跳过
    if [[ -d "$extract_dir" ]] && [[ "$(ls -A "$extract_dir" 2>/dev/null)" ]]; then
        info "$name: 使用缓存 $extract_dir"
        return 0
    fi

    mkdir -p "$(dirname "$cache_zip")"

    # 下载（如果缓存不存在或已损坏）
    if [[ -f "$cache_zip" ]]; then
        # 校验缓存完整性
        if ! unzip -tq "$cache_zip" &>/dev/null; then
            warn "$name: 缓存 zip 已损坏（可能是上次下载中断），重新下载"
            rm -f "$cache_zip"
        else
            info "$name: 使用已缓存 zip"
        fi
    fi

    if [[ ! -f "$cache_zip" ]]; then
        if [[ -z "$url" ]]; then
            error "$name: 未提供下载地址"
            return 1
        fi
        echo -e "  ${CYAN}下载 $name ...${NC}"
        echo -e "  ${CYAN}  $url${NC}"
        if command -v curl &>/dev/null; then
            curl -fSL --progress-bar --retry 2 --retry-delay 3 -o "$cache_zip" "$url" || { rm -f "$cache_zip"; error "$name 下载失败"; return 1; }
        elif command -v wget &>/dev/null; then
            wget -q --show-progress --tries=3 -O "$cache_zip" "$url" || { rm -f "$cache_zip"; error "$name 下载失败"; return 1; }
        else
            error "需要 curl 或 wget"; return 1
        fi

        # 下载后立即校验
        if ! unzip -tq "$cache_zip" &>/dev/null; then
            if file "$cache_zip" 2>/dev/null | grep -qi 'html\|text'; then
                error "$name: 下载的文件是 HTML 网页而非 zip — URL 可能需要替换为直链"
            else
                local actual_size
                actual_size=$(du -sh "$cache_zip" 2>/dev/null | cut -f1)
                error "$name: zip 文件无效或不完整 ($actual_size)，可能下载被截断"
            fi
            rm -f "$cache_zip"
            return 1
        fi
    fi

    # 解压
    local tmp_dir="${extract_dir}_tmp"
    rm -rf "$tmp_dir" "$extract_dir"
    mkdir -p "$tmp_dir" "$extract_dir"
    unzip -q "$cache_zip" -d "$tmp_dir" || { rm -rf "$tmp_dir" "$extract_dir"; return 1; }

    # 如果 zip 内只有一层目录，扁平化
    local items=("$tmp_dir"/*)
    if [[ ${#items[@]} -eq 1 ]] && [[ -d "${items[0]}" ]]; then
        mv "${items[0]}"/* "$extract_dir/" 2>/dev/null || true
        mv "${items[0]}"/.* "$extract_dir/" 2>/dev/null || true
    else
        mv "$tmp_dir"/* "$extract_dir/" 2>/dev/null || true
        mv "$tmp_dir"/.* "$extract_dir/" 2>/dev/null || true
    fi
    rm -rf "$tmp_dir"

    local dl_size
    dl_size=$(du -sh "$extract_dir" 2>/dev/null | cut -f1)
    info "$name: 解压完成 ($dl_size)"
    return 0
}

# ── 下载 laragon-wamp.exe (--laragon-url <.exe>) ──────────────────
if [[ "$TARGET" == "win" ]] && [[ -n "${LARAGON_URL_OVERRIDE:-}" ]]; then
    CACHE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.cache"
    LARAGON_INSTALLER_CACHE="$CACHE_DIR/laragon-wamp.exe"

    mkdir -p "$CACHE_DIR"
    if [[ ! -f "$LARAGON_INSTALLER_CACHE" ]] || [[ ! -s "$LARAGON_INSTALLER_CACHE" ]]; then
        info "下载 Laragon Windows 安装器..."
        if command -v curl &>/dev/null; then
            curl -fSL --progress-bar --retry 2 --retry-delay 3 -o "$LARAGON_INSTALLER_CACHE" "$LARAGON_URL_OVERRIDE" || fatal "Laragon Windows 安装器下载失败"
        elif command -v wget &>/dev/null; then
            wget -q --show-progress --tries=3 -O "$LARAGON_INSTALLER_CACHE" "$LARAGON_URL_OVERRIDE" || fatal "Laragon Windows 安装器下载失败"
        else
            fatal "需要 curl 或 wget 下载 Laragon Windows 安装器"
        fi
    else
        info "使用缓存的 Laragon Windows 安装器"
    fi

    LARAGON_INSTALLER_EXE="$LARAGON_INSTALLER_CACHE"
fi

# ── 项目根目录定位 ─────────────────────────────────────────────────────
# 支持从项目根目录或 deploy/ 子目录执行
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# 验证项目结构
[[ ! -f "$PROJECT_ROOT/artisan" ]] && fatal "无法定位项目根目录（未找到 artisan 文件）"
[[ ! -f "$PROJECT_ROOT/composer.json" ]] && fatal "无法定位项目根目录（未找到 composer.json）"

# ── 读取版本号 ─────────────────────────────────────────────────────────
if [[ -n "$VERSION_OVERRIDE" ]]; then
    VERSION="$VERSION_OVERRIDE"
elif [[ -f "$PROJECT_ROOT/VERSION" ]]; then
    VERSION="$(tr -d '[:space:]' < "$PROJECT_ROOT/VERSION")"
else
    fatal "未找到 VERSION 文件，请用 --version 指定版本号"
fi

# 校验版本号格式
if ! echo "$VERSION" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+'; then
    fatal "版本号格式不正确: '$VERSION'（应为 X.Y.Z）"
fi

# ── 计算总步骤数 ───────────────────────────────────────────────────────
TOTAL_STEPS=7
if [[ "$SKIP_OBFUSCATE" == false ]]; then
    TOTAL_STEPS=$((TOTAL_STEPS + 1))
fi
if [[ "$UPGRADE" == false ]]; then
    TOTAL_STEPS=$((TOTAL_STEPS + 1))  # schema dump
fi

# ── 构建路径 ───────────────────────────────────────────────────────────
DIST_DIR="$PROJECT_ROOT/deploy/dist"
OUTPUT_DIR="$PROJECT_ROOT/deploy/output"

SUFFIX="${TARGET}"
if [[ "$UPGRADE" == true ]]; then
    SUFFIX="${TARGET}-upgrade"
fi
ARCHIVE_NAME="dental-clinic-${VERSION}-${SUFFIX}.zip"
ARCHIVE_PATH="$OUTPUT_DIR/$ARCHIVE_NAME"

# ── 构建开始 ───────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${CYAN}║         牙科诊所管理系统 — 构建脚本                        ║${NC}"
echo -e "${BOLD}${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  版本:     ${BOLD}${VERSION}${NC}"
echo -e "  目标:     ${BOLD}${TARGET}${NC}"
echo -e "  模式:     ${BOLD}$(if [[ "$UPGRADE" == true ]]; then echo '升级包'; else echo '全量安装包'; fi)${NC}"
echo -e "  混淆:     ${BOLD}$(if [[ "$SKIP_OBFUSCATE" == true ]]; then echo '跳过'; else echo '启用'; fi)${NC}"
if [[ -n "$LARAGON_INSTALLER_EXE" ]]; then
echo -e "  Laragon:  ${BOLD}${LARAGON_INSTALLER_EXE}${NC}"
fi
echo -e "  项目根:   ${BOLD}${PROJECT_ROOT}${NC}"
BUILD_START_TIME=$(date +%s)

# ═══════════════════════════════════════════════════════════════════════
# Step 1: 清理并创建构建目录
# ═══════════════════════════════════════════════════════════════════════
step "清理并创建构建目录"

if [[ -d "$DIST_DIR" ]]; then
    warn "删除旧的 dist/ 目录..."
    rm -rf "$DIST_DIR"
fi

# 清理上次构建可能残留的解压目录（构建中途失败时会残留）
ARCHIVE_ROOT_NAME_CLEAN="dental-clinic-${VERSION}-${SUFFIX}"
if [[ -d "$PROJECT_ROOT/deploy/$ARCHIVE_ROOT_NAME_CLEAN" ]]; then
    warn "删除残留的构建目录: $ARCHIVE_ROOT_NAME_CLEAN/"
    rm -rf "$PROJECT_ROOT/deploy/$ARCHIVE_ROOT_NAME_CLEAN"
fi

if [[ -f "$ARCHIVE_PATH" ]]; then
    warn "删除旧的产物: $ARCHIVE_NAME"
    rm -f "$ARCHIVE_PATH"
fi

mkdir -p "$DIST_DIR"
mkdir -p "$OUTPUT_DIR"
info "创建 deploy/dist/"
info "创建 deploy/output/"

# ═══════════════════════════════════════════════════════════════════════
# Step 2: 复制项目文件
# ═══════════════════════════════════════════════════════════════════════
step "复制项目文件"

# rsync 排除列表
RSYNC_EXCLUDES=(
    --exclude='.git'
    --exclude='.git/'
    --exclude='.gitattributes'
    --exclude='.gitignore'
    --exclude='.github/'
    --exclude='node_modules/'
    --exclude='vendor/'
    --exclude='tests/'
    --exclude='deploy/'
    --exclude='.env'
    --exclude='.env.backup'
    --exclude='.env.docker'
    --exclude='.env.example'
    --exclude='.env.live.config'
    --exclude='storage/logs/*.log'
    --exclude='storage/framework/cache/*'
    --exclude='storage/framework/sessions/*'
    --exclude='storage/framework/views/*'
    --exclude='.claude/'
    --exclude='ai-dev-template/'
    --exclude='.idea/'
    --exclude='.vscode/'
    --exclude='.scribe/'
    --exclude='.DS_Store'
    --exclude='.phpunit.result.cache'
    --exclude='.styleci.yml'
    --exclude='.dockerignore'
    --exclude='docker/'
    --exclude='docker-compose.yml'
    --exclude='docs/'
    --exclude='doc/'
    --exclude='CLAUDE.md'
    --exclude='webpack.mix.js'
    --exclude='package.json'
    --exclude='package-lock.json'
    --exclude='package.xml'
    --exclude='composer.phar'
    --exclude='scripts/venv/'
    --exclude='scripts/__pycache__/'
    --exclude='public/uploads/*'
    --exclude='public/hot'
    --exclude='public/storage'
    --exclude='public/docs'
)

# 升级包额外排除项（不含运行时依赖和静态资源以外的大文件）
if [[ "$UPGRADE" == true ]]; then
    RSYNC_EXCLUDES+=(
        --exclude='storage/'
        --exclude='scripts/'
    )
fi

rsync -a \
    "${RSYNC_EXCLUDES[@]}" \
    "$PROJECT_ROOT/" \
    "$DIST_DIR/"

# 确保 storage 子目录结构存在（全量包需要）
if [[ "$UPGRADE" == false ]]; then
    mkdir -p "$DIST_DIR/storage/app/public"
    mkdir -p "$DIST_DIR/storage/framework/cache/data"
    mkdir -p "$DIST_DIR/storage/framework/sessions"
    mkdir -p "$DIST_DIR/storage/framework/views"
    mkdir -p "$DIST_DIR/storage/logs"
    # 创建 .gitkeep 占位文件
    touch "$DIST_DIR/storage/app/.gitkeep"
    touch "$DIST_DIR/storage/app/public/.gitkeep"
    touch "$DIST_DIR/storage/framework/.gitkeep"
    touch "$DIST_DIR/storage/framework/cache/.gitkeep"
    touch "$DIST_DIR/storage/framework/sessions/.gitkeep"
    touch "$DIST_DIR/storage/framework/views/.gitkeep"
    touch "$DIST_DIR/storage/logs/.gitkeep"
    info "创建 storage/ 目录结构"
fi

# 确保 bootstrap/cache 目录存在
mkdir -p "$DIST_DIR/bootstrap/cache"

FILE_COUNT=$(find "$DIST_DIR" -type f | wc -l | tr -d ' ')
info "已复制 ${FILE_COUNT} 个文件到 dist/"

# ═══════════════════════════════════════════════════════════════════════
# Step 3: 安装 Composer 依赖（生产模式）
# ═══════════════════════════════════════════════════════════════════════
step "安装 Composer 依赖（生产模式）"

if ! command -v composer &>/dev/null; then
    # 尝试使用项目内的 composer.phar
    if [[ -f "$PROJECT_ROOT/composer.phar" ]]; then
        COMPOSER_CMD="php $PROJECT_ROOT/composer.phar"
        warn "使用项目内 composer.phar"
    else
        fatal "未找到 composer 命令，请先安装 Composer"
    fi
else
    COMPOSER_CMD="composer"
fi

(
    cd "$DIST_DIR"
    $COMPOSER_CMD install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        2>&1 | tail -5
)

VENDOR_SIZE=$(du -sh "$DIST_DIR/vendor" 2>/dev/null | cut -f1)
info "Composer 依赖安装完成 (vendor: ${VENDOR_SIZE})"

# ═══════════════════════════════════════════════════════════════════════
# Step 4: PHP 代码混淆（可选）
# ═══════════════════════════════════════════════════════════════════════
if [[ "$SKIP_OBFUSCATE" == false ]]; then
    step "PHP 代码混淆"

    YAKPRO_CNF="$PROJECT_ROOT/deploy/yakpro-po.cnf"

    YAKPRO_HOME="$HOME/.yakpro-po"
    YAKPRO_BIN="$YAKPRO_HOME/yakpro-po.php"

    if ! command -v yakpro-po &>/dev/null && [[ ! -f "$YAKPRO_BIN" ]]; then
        info "yakpro-po 未安装，正在自动安装到 $YAKPRO_HOME ..."
        git clone --depth 1 https://github.com/pk-fr/yakpro-po.git "$YAKPRO_HOME" 2>&1 | tail -2
        git clone --depth 1 --branch=4.x https://github.com/nikic/PHP-Parser.git "$YAKPRO_HOME/PHP-Parser" 2>&1 | tail -2
    fi

    # 确定 yakpro-po 可执行路径
    if command -v yakpro-po &>/dev/null; then
        YAKPRO_CMD="yakpro-po"
    elif [[ -f "$YAKPRO_BIN" ]]; then
        YAKPRO_CMD="php $YAKPRO_BIN"
    else
        YAKPRO_CMD=""
    fi

    if [[ -z "$YAKPRO_CMD" ]]; then
        warn "yakpro-po 自动安装失败，跳过代码混淆"
    elif [[ ! -f "$YAKPRO_CNF" ]]; then
        warn "混淆配置文件不存在: deploy/yakpro-po.cnf，跳过代码混淆"
    else
        # 混淆 app/ 目录，输出到临时目录后替换
        APP_SRC="$DIST_DIR/app"
        # macOS 上 app/ 和 App/ 是同一个目录（case-insensitive）
        if [[ -d "$DIST_DIR/App" ]] && [[ ! -d "$DIST_DIR/app" ]]; then
            APP_SRC="$DIST_DIR/App"
        fi
        APP_OBFUSCATED="$DIST_DIR/_app_obfuscated"

        $YAKPRO_CMD "$APP_SRC" \
            -o "$APP_OBFUSCATED" \
            --config-file "$YAKPRO_CNF" \
            2>&1 | tail -3

        APP_OBFUSCATED_REAL="$APP_OBFUSCATED"
        if [[ -d "$APP_OBFUSCATED/yakpro-po/obfuscated" ]]; then
            APP_OBFUSCATED_REAL="$APP_OBFUSCATED/yakpro-po/obfuscated"
        fi
        if [[ ! -f "$APP_OBFUSCATED_REAL/Http/Kernel.php" ]]; then
            fatal "代码混淆产物异常：未找到 $APP_OBFUSCATED_REAL/Http/Kernel.php"
        fi

        # 替换原始 app/ 目录
        rm -rf "$APP_SRC"
        mv "$APP_OBFUSCATED_REAL" "$APP_SRC"
        if [[ -d "$APP_OBFUSCATED" ]]; then
            rm -rf "$APP_OBFUSCATED"
        fi
        info "代码混淆完成"
    fi
fi

# ═══════════════════════════════════════════════════════════════════════
# Step 5: 数据库 Schema 导出（仅全量包）
# ═══════════════════════════════════════════════════════════════════════
if [[ "$UPGRADE" == false ]]; then
    step "导出数据库 Schema"

    SCHEMA_DIR="$DIST_DIR/database/schema"
    mkdir -p "$SCHEMA_DIR"

    SCHEMA_DUMPED=false

    # 方法 1: 使用 artisan schema:dump
    if [[ -f "$PROJECT_ROOT/.env" ]] && command -v php &>/dev/null; then
        (
            cd "$PROJECT_ROOT"
            if php artisan schema:dump --path="$SCHEMA_DIR/mysql-schema.sql" 2>/dev/null; then
                true
            else
                # schema:dump 可能不支持 --path 参数，尝试不带路径
                if php artisan schema:dump 2>/dev/null; then
                    # 默认输出到 database/schema/mysql-schema.dump
                    if [[ -f "$PROJECT_ROOT/database/schema/mysql-schema.dump" ]]; then
                        cp "$PROJECT_ROOT/database/schema/mysql-schema.dump" "$SCHEMA_DIR/mysql-schema.sql"
                    fi
                fi
            fi
        ) && SCHEMA_DUMPED=true
    fi

    # 方法 2: 使用 mysqldump 回退
    if [[ "$SCHEMA_DUMPED" == false ]] && command -v mysqldump &>/dev/null; then
        if [[ -f "$PROJECT_ROOT/.env" ]]; then
            # 从 .env 读取数据库配置
            DB_HOST=$(grep -E '^DB_HOST=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
            DB_PORT=$(grep -E '^DB_PORT=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
            DB_DATABASE=$(grep -E '^DB_DATABASE=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
            DB_USERNAME=$(grep -E '^DB_USERNAME=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
            DB_PASSWORD=$(grep -E '^DB_PASSWORD=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")

            DB_HOST="${DB_HOST:-127.0.0.1}"
            DB_PORT="${DB_PORT:-3306}"

            if [[ -n "$DB_DATABASE" ]]; then
                MYSQLDUMP_ARGS=(
                    -h "$DB_HOST"
                    -P "$DB_PORT"
                    -u "$DB_USERNAME"
                    --no-data
                    --routines
                    --triggers
                    --single-transaction
                    "$DB_DATABASE"
                )
                if [[ -n "$DB_PASSWORD" ]]; then
                    MYSQLDUMP_ARGS=(-p"$DB_PASSWORD" "${MYSQLDUMP_ARGS[@]}")
                fi

                if mysqldump "${MYSQLDUMP_ARGS[@]}" > "$SCHEMA_DIR/mysql-schema.sql" 2>/dev/null; then
                    SCHEMA_DUMPED=true
                fi
            fi
        fi
    fi

    if [[ "$SCHEMA_DUMPED" == true ]] && [[ -f "$SCHEMA_DIR/mysql-schema.sql" ]]; then
        SCHEMA_SIZE=$(du -sh "$SCHEMA_DIR/mysql-schema.sql" | cut -f1)
        info "Schema 导出完成 (${SCHEMA_SIZE})"
    else
        warn "Schema 导出失败 — 安装包中将不包含数据库 schema"
        warn "部署时需要手动运行 php artisan migrate"
    fi
fi

# ═══════════════════════════════════════════════════════════════════════
# Step N: 复制部署脚本和配置
# ═══════════════════════════════════════════════════════════════════════
step "复制部署脚本和配置"

# 安装/升级/启停脚本放在 zip 根目录（解压后直接可见，一键执行）
# 项目代码放在 dental/ 子目录

# 复制 .env.deploy 模板到项目目录
cp "$PROJECT_ROOT/deploy/.env.deploy" "$DIST_DIR/.env.deploy"
info "复制 .env.deploy 模板"

# 复制 VERSION 文件
cp "$PROJECT_ROOT/VERSION" "$DIST_DIR/VERSION"
info "复制 VERSION"

# 运维工具（所有平台通用）
for tool in check.sh backup-restore.sh export-data.sh; do
    if [[ -f "$PROJECT_ROOT/deploy/$tool" ]]; then
        cp "$PROJECT_ROOT/deploy/$tool" "$DIST_DIR/$tool"
        chmod +x "$DIST_DIR/$tool"
    fi
done

case "$TARGET" in
    win)
        # Windows 脚本放到 zip 根目录
        for script in install-win.bat install-win.ps1 upgrade-win.bat start-win.bat stop-win.bat uninstall-win.bat laragon-startup.bat; do
            if [[ -f "$PROJECT_ROOT/deploy/$script" ]]; then
                cp "$PROJECT_ROOT/deploy/$script" "$DIST_DIR/"
                info "复制 $script"
            else
                warn "脚本不存在，跳过: $script"
            fi
        done
        if [[ -d "$PROJECT_ROOT/deploy/batch-helpers" ]]; then
            mkdir -p "$DIST_DIR/batch-helpers"
            cp -R "$PROJECT_ROOT/deploy/batch-helpers/." "$DIST_DIR/batch-helpers/"
            info "复制 batch-helpers/"
        else
            warn "目录不存在，跳过: deploy/batch-helpers"
        fi
        # 创建 setup.bat 快捷入口（Laragon 已预装模式）
        cat > "$DIST_DIR/setup.bat" <<'SHORTCUT_BAT'
@echo off
setlocal EnableExtensions DisableDelayedExpansion
chcp 936 >nul 2>&1

set "INSTALL_DIR=C:\DentalClinic"
set "PKG_DIR=%~dp0"
if "%PKG_DIR:~-1%"=="\" set "PKG_DIR=%PKG_DIR:~0,-1%"

echo.
echo  =======================================================
echo    Dental Clinic Management System - Installer
echo  =======================================================
echo.

echo  [1/3] Stopping running services...
taskkill /f /im mysqld.exe   >nul 2>&1
taskkill /f /im nginx.exe    >nul 2>&1
taskkill /f /im php.exe      >nul 2>&1
taskkill /f /im php-cgi.exe  >nul 2>&1
timeout /t 2 /nobreak >nul 2>&1

echo  [2/3] Copying application files...
for %%D in (app bootstrap config database public resources routes storage vendor) do (
    if exist "%PKG_DIR%\%%D" (
        xcopy "%PKG_DIR%\%%D" "%INSTALL_DIR%\laragon\www\dental\%%D\" /E /I /H /Y /Q >nul 2>&1
    )
)
if exist "%PKG_DIR%\.env.deploy" copy "%PKG_DIR%\.env.deploy" "%INSTALL_DIR%\laragon\www\dental\.env.deploy" /Y >nul 2>&1
if exist "%PKG_DIR%\VERSION"     copy "%PKG_DIR%\VERSION"     "%INSTALL_DIR%\laragon\www\dental\VERSION" /Y >nul 2>&1

for %%F in (install-win.bat install-win.ps1 upgrade-win.bat start-win.bat stop-win.bat uninstall-win.bat laragon-startup.bat) do (
    if exist "%PKG_DIR%\%%F" copy "%PKG_DIR%\%%F" "%INSTALL_DIR%\%%F" /Y >nul 2>&1
)
if exist "%PKG_DIR%\batch-helpers" xcopy "%PKG_DIR%\batch-helpers" "%INSTALL_DIR%\batch-helpers\" /E /I /H /Y /Q >nul 2>&1
echo         App files copied.

echo  [3/3] Running installer...
echo.
call "%INSTALL_DIR%\install-win.bat" "%INSTALL_DIR%"
SHORTCUT_BAT
        info "创建 setup.bat（双击即可安装）"
        ;;
    linux|mac)
        for script in install-linux.sh upgrade-linux.sh start-linux.sh stop-linux.sh uninstall-linux.sh; do
            if [[ -f "$PROJECT_ROOT/deploy/$script" ]]; then
                cp "$PROJECT_ROOT/deploy/$script" "$DIST_DIR/"
                chmod +x "$DIST_DIR/$script"
                info "复制 $script"
            else
                warn "脚本不存在，跳过: $script"
            fi
        done
        # 创建 install.sh 快捷入口
        cat > "$DIST_DIR/install.sh" <<'SHORTCUT_SH'
#!/usr/bin/env bash
# 一键安装入口 — 自动调用 install-linux.sh
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "$SCRIPT_DIR/install-linux.sh" --source-dir "$SCRIPT_DIR" "$@"
SHORTCUT_SH
        chmod +x "$DIST_DIR/install.sh"
        info "创建 install.sh（一键安装入口）"
        ;;
esac

# ═══════════════════════════════════════════════════════════════════════
# Step N: 下载 OCR Python 依赖
# ═══════════════════════════════════════════════════════════════════════
step "准备 OCR 服务依赖"

OCR_REQUIREMENTS="$PROJECT_ROOT/scripts/requirements.txt"

if [[ -f "$OCR_REQUIREMENTS" ]]; then
    # 始终复制 OCR 脚本（安装时用 pip install -r requirements.txt 联网安装）
    OCR_SCRIPTS_DIR="$DIST_DIR/scripts"
    mkdir -p "$OCR_SCRIPTS_DIR"
    for ocr_file in ocr_service.py ocr_server.py ocr_corrections.json requirements.txt setup_ocr_venv.sh setup_ocr_venv.bat; do
        if [[ -f "$PROJECT_ROOT/scripts/$ocr_file" ]]; then
            cp "$PROJECT_ROOT/scripts/$ocr_file" "$OCR_SCRIPTS_DIR/"
        fi
    done
    chmod +x "$OCR_SCRIPTS_DIR"/*.sh 2>/dev/null || true
    info "复制 OCR 服务脚本"

    if [[ "$TARGET" == "win" ]] && [[ "$SKIP_OCR" == false ]]; then
        PYTHON_INSTALLER_URL="${PYTHON_DOWNLOAD_URL:-https://www.python.org/ftp/python/3.11.9/python-3.11.9-amd64.exe}"
        PYTHON_INSTALLER_CACHE="$PROJECT_ROOT/deploy/.cache/python-installer.exe"
        PYTHON_INSTALLER_DIST="$DIST_DIR/python-installer.exe"

        mkdir -p "$(dirname "$PYTHON_INSTALLER_CACHE")"
        if [[ -f "$PYTHON_INSTALLER_CACHE" ]] && [[ -s "$PYTHON_INSTALLER_CACHE" ]]; then
            cp "$PYTHON_INSTALLER_CACHE" "$PYTHON_INSTALLER_DIST"
            info "复制缓存的 Python 安装器"
        else
            warn "正在下载 Windows Python 安装器（供 OCR 静默安装使用）..."
            if command -v curl &>/dev/null; then
                curl -fSL --progress-bar --retry 2 --retry-delay 3 -o "$PYTHON_INSTALLER_DIST" "$PYTHON_INSTALLER_URL" || rm -f "$PYTHON_INSTALLER_DIST"
            elif command -v wget &>/dev/null; then
                wget -q --show-progress --tries=3 -O "$PYTHON_INSTALLER_DIST" "$PYTHON_INSTALLER_URL" || rm -f "$PYTHON_INSTALLER_DIST"
            fi

            if [[ -f "$PYTHON_INSTALLER_DIST" ]] && [[ -s "$PYTHON_INSTALLER_DIST" ]]; then
                cp "$PYTHON_INSTALLER_DIST" "$PYTHON_INSTALLER_CACHE"
                info "Python 安装器已打包"
            else
                warn "Python 安装器下载失败，目标机若无 Python 则 OCR 安装会失败"
            fi
        fi
    fi

    # OCR wheels 打包（默认打包，--skip-ocr 跳过）
    if [[ "$SKIP_OCR" == true ]]; then
        info "跳过 OCR wheels 打包（--skip-ocr）"
    else
        OCR_WHEELS_DIR="$DIST_DIR/ocr-wheels"
        mkdir -p "$OCR_WHEELS_DIR"

        # 带缓存的下载
        OCR_CACHE_DIR="$PROJECT_ROOT/deploy/.cache/ocr-wheels-${TARGET}"
        REQ_HASH=$(md5sum "$OCR_REQUIREMENTS" 2>/dev/null | cut -d' ' -f1 || md5 -q "$OCR_REQUIREMENTS" 2>/dev/null)
        OCR_CACHE_HASH_FILE="$OCR_CACHE_DIR/.requirements_hash"

        if [[ -d "$OCR_CACHE_DIR" ]] && [[ -f "$OCR_CACHE_HASH_FILE" ]] && [[ "$(cat "$OCR_CACHE_HASH_FILE")" == "$REQ_HASH" ]]; then
            info "使用缓存的 OCR wheels (deploy/.cache/ocr-wheels-${TARGET}/)"
            cp "$OCR_CACHE_DIR"/*.whl "$OCR_WHEELS_DIR/" 2>/dev/null || true
            cp "$OCR_CACHE_DIR"/*.tar.gz "$OCR_WHEELS_DIR/" 2>/dev/null || true
            WHEEL_COUNT=$(find "$OCR_WHEELS_DIR" -type f \( -name '*.whl' -o -name '*.tar.gz' \) | wc -l | tr -d ' ')
            WHEEL_SIZE=$(du -sh "$OCR_WHEELS_DIR" 2>/dev/null | cut -f1)
            info "从缓存复制 ${WHEEL_COUNT} 个 wheel 包 (${WHEEL_SIZE})"
        elif command -v pip &>/dev/null || command -v pip3 &>/dev/null; then
            PIP_CMD="pip3"
            if ! command -v pip3 &>/dev/null; then
                PIP_CMD="pip"
            fi

            PIP_DOWNLOAD_ARGS=()
            case "$TARGET" in
                win)
                    PIP_DOWNLOAD_ARGS=(
                        --platform win_amd64
                        --python-version 3.11
                        --only-binary=:all:
                    )
                    ;;
                linux)
                    PIP_DOWNLOAD_ARGS=(
                        --platform manylinux2014_x86_64
                        --python-version 3.11
                        --only-binary=:all:
                    )
                    ;;
                mac)
                    PIP_DOWNLOAD_ARGS=()
                    ;;
            esac

            # 优先使用锁定版本文件（跳过依赖解析，大幅加速）
            OCR_LOCK_FILE="$PROJECT_ROOT/scripts/requirements-lock.txt"
            if [[ -f "$OCR_LOCK_FILE" ]]; then
                PIP_REQ_FILE="$OCR_LOCK_FILE"
                PIP_DOWNLOAD_ARGS+=(--no-deps)
                info "使用锁定版本 (requirements-lock.txt)，跳过依赖解析"
            else
                PIP_REQ_FILE="$OCR_REQUIREMENTS"
            fi

            warn "正在下载 OCR Python wheels (目标: $TARGET)，首次下载需几分钟，后续构建使用缓存..."
            if $PIP_CMD download \
                "${PIP_DOWNLOAD_ARGS[@]}" \
                -d "$OCR_WHEELS_DIR" \
                -r "$PIP_REQ_FILE"; then
                WHEEL_COUNT=$(find "$OCR_WHEELS_DIR" -type f \( -name '*.whl' -o -name '*.tar.gz' \) | wc -l | tr -d ' ')
                WHEEL_SIZE=$(du -sh "$OCR_WHEELS_DIR" 2>/dev/null | cut -f1)
                info "下载 ${WHEEL_COUNT} 个 wheel 包 (${WHEEL_SIZE})"

                # 写入缓存
                rm -rf "$OCR_CACHE_DIR"
                mkdir -p "$OCR_CACHE_DIR"
                cp "$OCR_WHEELS_DIR"/*.whl "$OCR_CACHE_DIR/" 2>/dev/null || true
                cp "$OCR_WHEELS_DIR"/*.tar.gz "$OCR_CACHE_DIR/" 2>/dev/null || true
                echo "$REQ_HASH" > "$OCR_CACHE_HASH_FILE"
                info "已缓存到 deploy/.cache/ocr-wheels-${TARGET}/"
            else
                warn "OCR Python wheels 下载失败 — 部署时需要联网安装"
                rm -rf "$OCR_WHEELS_DIR"
            fi
        else
            warn "未找到 pip/pip3，跳过 OCR wheel 下载"
        fi
    fi
else
    warn "未找到 scripts/requirements.txt，跳过 OCR 依赖"
fi

# ═══════════════════════════════════════════════════════════════════════
# Step N: 复制 Laragon Windows 安装器（可选）
# ═══════════════════════════════════════════════════════════════════════
if [[ -n "$LARAGON_INSTALLER_EXE" ]]; then
    cp "$LARAGON_INSTALLER_EXE" "$DIST_DIR/laragon-wamp.exe"
    info "复制 Laragon Windows 安装器"
fi

# ═══════════════════════════════════════════════════════════════════════
# Step N: 生成 setup.bat（Laragon 安装器模式）
# ═══════════════════════════════════════════════════════════════════════
if [[ "$TARGET" == "win" ]] && [[ -n "$LARAGON_INSTALLER_EXE" ]]; then
    cat > "$DIST_DIR/setup.bat" <<'LARAGON_INSTALLER_BAT'
@echo off
setlocal EnableExtensions DisableDelayedExpansion
chcp 936 >nul 2>&1

echo.
echo  =======================================================
echo    Dental Clinic Management System - Laragon Installer
echo  =======================================================
echo.
echo  This package will install Laragon and then deploy the app.
echo.

set "INSTALL_DIR=C:\DentalClinic"
set /p "INSTALL_DIR=Install path [%INSTALL_DIR%]: "
if "%INSTALL_DIR%"=="" set "INSTALL_DIR=C:\DentalClinic"
if "%INSTALL_DIR:~-1%"=="\" set "INSTALL_DIR=%INSTALL_DIR:~0,-1%"

echo.
echo  Install path: %INSTALL_DIR%
echo.

if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"
if errorlevel 1 (
    echo  [ERROR] Failed to create install directory.
    pause
    exit /b 1
)

echo  [1/4] Copying application files...
xcopy "%~dp0app" "%INSTALL_DIR%\laragon\www\dental\app\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0bootstrap" "%INSTALL_DIR%\laragon\www\dental\bootstrap\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0config" "%INSTALL_DIR%\laragon\www\dental\config\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0database" "%INSTALL_DIR%\laragon\www\dental\database\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0public" "%INSTALL_DIR%\laragon\www\dental\public\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0resources" "%INSTALL_DIR%\laragon\www\dental\resources\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0routes" "%INSTALL_DIR%\laragon\www\dental\routes\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0storage" "%INSTALL_DIR%\laragon\www\dental\storage\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0vendor" "%INSTALL_DIR%\laragon\www\dental\vendor\" /E /I /H /Y /Q >nul 2>&1
if exist "%~dp0scripts" xcopy "%~dp0scripts" "%INSTALL_DIR%\laragon\www\dental\scripts\" /E /I /H /Y /Q >nul 2>&1
copy "%~dp0artisan" "%INSTALL_DIR%\laragon\www\dental\" /Y >nul 2>&1
copy "%~dp0composer.json" "%INSTALL_DIR%\laragon\www\dental\" /Y >nul 2>&1
copy "%~dp0composer.lock" "%INSTALL_DIR%\laragon\www\dental\" /Y >nul 2>&1
copy "%~dp0.env.deploy" "%INSTALL_DIR%\laragon\www\dental\.env.deploy" /Y >nul 2>&1
copy "%~dp0VERSION" "%INSTALL_DIR%\laragon\www\dental\" /Y >nul 2>&1
echo         App files copied.

echo  [2/4] Copying installer assets...
copy "%~dp0laragon-wamp.exe" "%INSTALL_DIR%\laragon-wamp.exe" /Y >nul 2>&1
if exist "%~dp0ocr-wheels" xcopy "%~dp0ocr-wheels" "%INSTALL_DIR%\ocr-wheels\" /E /I /H /Y /Q >nul 2>&1
if exist "%~dp0python-installer.exe" copy "%~dp0python-installer.exe" "%INSTALL_DIR%\python-installer.exe" /Y >nul 2>&1
for %%F in (install-win.bat install-win.ps1 upgrade-win.bat start-win.bat stop-win.bat uninstall-win.bat laragon-startup.bat) do (
    if exist "%~dp0%%F" copy "%~dp0%%F" "%INSTALL_DIR%\" /Y >nul 2>&1
)
if exist "%~dp0batch-helpers" xcopy "%~dp0batch-helpers" "%INSTALL_DIR%\batch-helpers\" /E /I /H /Y /Q >nul 2>&1
echo         Installer assets copied.

echo  [3/4] Normalizing batch file encoding...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$enc = [System.Text.Encoding]::GetEncoding(936); $targets = @('install-win.bat','upgrade-win.bat','start-win.bat','stop-win.bat','uninstall-win.bat','laragon-startup.bat'); foreach ($name in $targets) { $path = Join-Path '%INSTALL_DIR%' $name; if (-not (Test-Path $path)) { continue }; $bytes = [System.IO.File]::ReadAllBytes($path); try { $text = [System.Text.Encoding]::UTF8.GetString($bytes); if ($text.Contains([char]0xFFFD)) { throw 'decode-failed' } } catch { $text = [System.Text.Encoding]::Default.GetString($bytes) }; $text = $text -replace \"`r?`n\", \"`r`n\"; [System.IO.File]::WriteAllText($path, $text, $enc) }" >nul 2>&1
echo         Batch files normalized.

echo  [4/4] Launching installer...
echo.
call "%INSTALL_DIR%\install-win.bat" "%INSTALL_DIR%"
LARAGON_INSTALLER_BAT
    info "更新 setup.bat（Laragon 安装器模式）"
fi

# ═══════════════════════════════════════════════════════════════════════
# Step N: 升级包特殊处理
# ═══════════════════════════════════════════════════════════════════════
if [[ "$UPGRADE" == true ]]; then
    step "生成升级包元数据"

    # 生成 env.patch — 从 .env.deploy 提取占位符配置行，供升级脚本补充缺失 key
    if [[ -f "$PROJECT_ROOT/deploy/.env.deploy" ]]; then
        grep '{{[^}]\+}}' "$PROJECT_ROOT/deploy/.env.deploy" | sed -E 's/\{\{[^}]+\}\}//g' > "$DIST_DIR/env.patch" || true
        if [[ -s "$DIST_DIR/env.patch" ]]; then
            info "生成 env.patch（需配置的环境变量列表）"
        fi
    fi

    # 生成升级说明
    cat > "$DIST_DIR/UPGRADE.md" <<UPGRADE_EOF
# 升级说明 — v${VERSION}

## 升级步骤

1. 备份当前系统（数据库 + 代码）
2. 解压升级包，覆盖项目目录（保留 .env 和 storage/）
3. 运行数据库迁移:
   \`\`\`bash
   php artisan migrate --force
   \`\`\`
4. 清除缓存:
   \`\`\`bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   \`\`\`
5. 检查 env.patch 中是否有新增的环境变量需要配置
6. 重启 Web 服务

## 包含的迁移文件

请查看 database/migrations/ 目录中的新增迁移。
UPGRADE_EOF
    info "生成 UPGRADE.md"
fi

# ═══════════════════════════════════════════════════════════════════════
# Step N: Windows .bat 文件编码转换 (UTF-8 → GBK) 及行尾 (LF → CRLF)
# 中文 Windows CMD 用 GBK (CP936) 解析 .bat 文件，UTF-8 多字节序列会
# 被误读为 GBK 字符，导致 "," / "INSTALL_DIR" / "-" 被当作命令执行。
# ═══════════════════════════════════════════════════════════════════════
if [[ "$TARGET" == "win" ]]; then
    step "转换 .bat 文件编码 (UTF-8 → GBK) 及行尾 (CRLF)"
    bat_count=0
    while IFS= read -r -d '' bat_file; do
        if command -v python3 &>/dev/null; then
            # Python: 编码转换 + 行尾转换一步完成
            # - 项目 .bat 文件为 UTF-8，转为 GBK（errors='replace' 将方框画线等无法映射的字符替换为 ?）
            # - Laragon/vendor 内置 .bat 可能已是 ANSI，读 UTF-8 会失败，退回仅做行尾转换
            python3 -c '
import sys, pathlib
p = pathlib.Path(sys.argv[1])
try:
    text = p.read_text(encoding="utf-8")
    text = text.replace("\r\n", "\n").replace("\n", "\r\n")
    p.write_bytes(text.encode("gbk", errors="replace"))
except UnicodeDecodeError:
    # 已是非 UTF-8 编码（ANSI/ASCII），仅修正行尾
    data = p.read_bytes().replace(b"\r\n", b"\n").replace(b"\n", b"\r\n")
    p.write_bytes(data)
' "$bat_file"
        else
            # 回退: 无 Python，仅处理行尾（GBK 编码问题需手动处理）
            warn "未找到 python3，跳过 GBK 编码转换，仅转换行尾"
            if command -v perl &>/dev/null; then
                perl -pi -e 's/\r?\n/\r\n/' "$bat_file"
            else
                sed -i '' -e 's/\r$//' "$bat_file"
                sed -i '' -e 's/$/\r/' "$bat_file"
            fi
        fi
        bat_count=$((bat_count + 1))
    done < <(find "$DIST_DIR" -name '*.bat' -print0)
    info "已转换 ${bat_count} 个 .bat 文件（编码: GBK, 行尾: CRLF）"

    step "转换 PowerShell 脚本编码 (UTF-8 with BOM)"
    ps1_count=0
    while IFS= read -r -d '' ps1_file; do
        if command -v python3 &>/dev/null; then
            python3 -c '
import pathlib, sys
p = pathlib.Path(sys.argv[1])
# Use utf-8-sig so an existing BOM is consumed instead of duplicated.
text = p.read_text(encoding="utf-8-sig")
text = text.replace("\r\n", "\n").replace("\n", "\r\n")
p.write_text(text, encoding="utf-8-sig")
' "$ps1_file"
        else
            warn "未找到 python3，跳过 PowerShell BOM 编码转换: $(basename "$ps1_file")"
            continue
        fi
        ps1_count=$((ps1_count + 1))
    done < <(find "$DIST_DIR" -name '*.ps1' -print0)
    info "已转换 ${ps1_count} 个 .ps1 文件（编码: UTF-8 with BOM, 行尾: CRLF）"
fi

# ═══════════════════════════════════════════════════════════════════════
# Step N: 打包
# ═══════════════════════════════════════════════════════════════════════
step "创建发布包"

# 删除旧的同名归档
if [[ -f "$ARCHIVE_PATH" ]]; then
    rm -f "$ARCHIVE_PATH"
    warn "删除旧的归档: $ARCHIVE_NAME"
fi

# 创建压缩包（在 dist 的父目录执行，使归档内路径为 dental-clinic-VERSION-TARGET/...）
ARCHIVE_ROOT_NAME="dental-clinic-${VERSION}-${SUFFIX}"

# 重命名 dist 目录为目标名称以获得干净的归档路径
mv "$DIST_DIR" "$PROJECT_ROOT/deploy/$ARCHIVE_ROOT_NAME"

(
    cd "$PROJECT_ROOT/deploy"
    zip -r -q -9 "$ARCHIVE_PATH" "$ARCHIVE_ROOT_NAME/" \
        -x "$ARCHIVE_ROOT_NAME/.DS_Store" \
        -x "$ARCHIVE_ROOT_NAME/**/.DS_Store"
)

# 恢复目录名称（方便调试）
mv "$PROJECT_ROOT/deploy/$ARCHIVE_ROOT_NAME" "$DIST_DIR"

if [[ ! -f "$ARCHIVE_PATH" ]]; then
    fatal "归档创建失败"
fi

ARCHIVE_SIZE=$(du -sh "$ARCHIVE_PATH" | cut -f1)
info "归档创建完成: $ARCHIVE_NAME ($ARCHIVE_SIZE)"

# ═══════════════════════════════════════════════════════════════════════
# 构建摘要
# ═══════════════════════════════════════════════════════════════════════
BUILD_END_TIME=$(date +%s)
BUILD_DURATION=$((BUILD_END_TIME - BUILD_START_TIME))
BUILD_MINUTES=$((BUILD_DURATION / 60))
BUILD_SECONDS=$((BUILD_DURATION % 60))

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                     构建完成                               ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${BOLD}归档路径:${NC}  $ARCHIVE_PATH"
echo -e "  ${BOLD}归档大小:${NC}  $ARCHIVE_SIZE"
echo -e "  ${BOLD}版本:${NC}      $VERSION"
echo -e "  ${BOLD}目标平台:${NC}  $TARGET"
echo -e "  ${BOLD}构建模式:${NC}  $(if [[ "$UPGRADE" == true ]]; then echo '升级包'; else echo '全量安装包'; fi)"
echo -e "  ${BOLD}构建耗时:${NC}  ${BUILD_MINUTES}分${BUILD_SECONDS}秒"
echo ""

# 列出归档内容概要
echo -e "  ${BOLD}归档内容:${NC}"
if command -v python3 &>/dev/null; then
    python3 - "$ARCHIVE_PATH" <<'PY'
import collections
import sys
import zipfile

archive = sys.argv[1]
counter = collections.Counter()

with zipfile.ZipFile(archive) as zf:
    for name in zf.namelist():
        parts = name.split('/')
        if len(parts) < 2 or not parts[1]:
            continue
        counter[parts[1]] += 1

for key, count in counter.most_common(15):
    print(f"    {count:<8} {key}")
PY
elif command -v zipinfo &>/dev/null; then
    zipinfo -1 "$ARCHIVE_PATH" | LC_ALL=C sed 's|[^/]*/||' | LC_ALL=C cut -d'/' -f1 | sort | uniq -c | sort -rn | head -15 | while read -r count name; do
        if [[ -n "$name" ]]; then
            printf "    %-8s %s\n" "$count" "$name"
        fi
    done
else
    # 回退: 使用 unzip -l
    unzip -l "$ARCHIVE_PATH" | tail -1
fi

echo ""
echo -e "  ${CYAN}提示: 使用以下命令查看完整内容:${NC}"
echo -e "    unzip -l $ARCHIVE_PATH"
echo ""

# 清理 dist 目录
rm -rf "$DIST_DIR"
info "已清理 dist/ 临时目录"
echo ""
