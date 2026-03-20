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
  --bundle-laragon <path>      Windows: 将已有的 Laragon 完整目录打入安装包
  --assemble-runtime           Windows: ★ 自动下载 PHP/MySQL/Nginx/Composer 组装运行环境
  --download-laragon           (兼容旧参数，等同于 --assemble-runtime)
  --laragon-url <url>          配合 --download-laragon 指定 Laragon core zip（可选）
  -h, --help                   显示此帮助信息

环境变量（均可选，有默认值）:
  LARAGON_DOWNLOAD_URL         Laragon core zip 下载地址
  PHP_DOWNLOAD_URL             PHP Windows zip 下载地址
  MYSQL_DOWNLOAD_URL           MySQL Windows zip 下载地址
  NGINX_DOWNLOAD_URL           Nginx Windows zip 下载地址
  COMPOSER_DOWNLOAD_URL        Composer phar 下载地址

示例:
  ./deploy/build.sh --target win --assemble-runtime       # ★ 自动下载组装运行环境，真·一键构建
  ./deploy/build.sh --target win --bundle-laragon ~/Downloads/laragon  # 手动指定完整 Laragon 目录
  ./deploy/build.sh --target win                          # Windows 安装包（需自备 Laragon）
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
BUNDLE_LARAGON=""
DOWNLOAD_LARAGON=false
ASSEMBLE_RUNTIME=false
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
        --bundle-laragon)
            BUNDLE_LARAGON="${2:-}"
            [[ -z "$BUNDLE_LARAGON" ]] && fatal "--bundle-laragon 需要指定 Laragon 目录路径"
            shift 2
            ;;
        --download-laragon)
            DOWNLOAD_LARAGON=true
            ASSEMBLE_RUNTIME=true
            shift
            ;;
        --assemble-runtime)
            ASSEMBLE_RUNTIME=true
            shift
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

# 校验互斥参数
if [[ "$ASSEMBLE_RUNTIME" == true ]] && [[ -n "$BUNDLE_LARAGON" ]]; then
    fatal "--assemble-runtime / --download-laragon 与 --bundle-laragon 不能同时使用"
fi

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

# ── 自动组装 Windows 运行环境 (--assemble-runtime) ──────────────
if [[ "$ASSEMBLE_RUNTIME" == true ]]; then
    if [[ "$TARGET" != "win" ]]; then
        fatal "--assemble-runtime 仅适用于 --target win (Linux/macOS 使用系统包管理器)"
    fi

    CACHE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.cache"
    ASSEMBLED_DIR="$CACHE_DIR/laragon"

    # 检查缓存是否已有完整组装结果（PHP + MySQL 都必须存在）
    NEED_ASSEMBLE=true
    HAS_PHP=false
    HAS_MYSQL=false
    for _d in "$ASSEMBLED_DIR"/bin/php/*/; do
        [[ -f "${_d}php.exe" ]] && HAS_PHP=true && break
    done
    for _d in "$ASSEMBLED_DIR"/bin/mysql/*/; do
        [[ -f "${_d}bin/mysqld.exe" ]] && HAS_MYSQL=true && break
    done
    if [[ "$HAS_PHP" == true ]] && [[ "$HAS_MYSQL" == true ]]; then
        NEED_ASSEMBLE=false
    fi

    if [[ "$NEED_ASSEMBLE" == false ]]; then
        info "使用已组装的运行环境缓存: $ASSEMBLED_DIR"
    else
        echo ""
        echo -e "${BOLD}${CYAN}组装 Windows 运行环境 (PHP + MySQL + Nginx + Composer)${NC}"
        echo -e "${CYAN}缓存目录: $CACHE_DIR${NC}"
        echo -e "${CYAN}组装后的 PHP/MySQL 等将供后续构建复用，无需重复下载${NC}"
        echo ""

        mkdir -p "$ASSEMBLED_DIR/bin/php" "$ASSEMBLED_DIR/bin/mysql" "$ASSEMBLED_DIR/bin/nginx" "$ASSEMBLED_DIR/bin/composer" "$ASSEMBLED_DIR/etc/mysql" "$ASSEMBLED_DIR/etc/nginx" "$ASSEMBLED_DIR/etc/nginx/sites-enabled" "$ASSEMBLED_DIR/www" "$ASSEMBLED_DIR/data"

        # ── 自动解析最新版本下载地址 ──
        # 从各组件官网索引页抓取当前最新版本，避免硬编码版本号导致 404
        COMPOSER_URL="${COMPOSER_DOWNLOAD_URL:-https://getcomposer.org/download/latest-stable/composer.phar}"

        # PHP: 从 windows.php.net 索引页抓取最新 8.2.x NTS x64
        if [[ -n "${PHP_DOWNLOAD_URL:-}" ]]; then
            PHP_URL="$PHP_DOWNLOAD_URL"
        else
            echo -e "  ${CYAN}解析最新 PHP 8.2 版本...${NC}"
            PHP_FILENAME=$(curl -fsSL "https://windows.php.net/downloads/releases/" 2>/dev/null \
                | grep -oE 'php-8\.2\.[0-9]+-nts-Win32-vs16-x64\.zip' | sort -V | tail -1)
            if [[ -n "$PHP_FILENAME" ]]; then
                PHP_URL="https://windows.php.net/downloads/releases/$PHP_FILENAME"
                info "PHP: 检测到最新版本 $PHP_FILENAME"
            else
                # 回退：尝试 archives 页面
                PHP_FILENAME=$(curl -fsSL "https://windows.php.net/downloads/releases/archives/" 2>/dev/null \
                    | grep -oE 'php-8\.2\.[0-9]+-nts-Win32-vs16-x64\.zip' | sort -V | tail -1)
                if [[ -n "$PHP_FILENAME" ]]; then
                    PHP_URL="https://windows.php.net/downloads/releases/archives/$PHP_FILENAME"
                    info "PHP: 使用归档版本 $PHP_FILENAME"
                else
                    PHP_URL=""
                    error "PHP: 无法自动解析最新版本"
                    echo "    请手动设置: export PHP_DOWNLOAD_URL=\"https://windows.php.net/downloads/releases/php-8.2.x-nts-Win32-vs16-x64.zip\""
                fi
            fi
        fi

        # MySQL: dev.mysql.com 下载链接比较稳定，用 HEAD 请求探测最新小版本
        if [[ -n "${MYSQL_DOWNLOAD_URL:-}" ]]; then
            MYSQL_URL="$MYSQL_DOWNLOAD_URL"
        else
            echo -e "  ${CYAN}解析最新 MySQL 版本...${NC}"
            MYSQL_URL=""
            # 从高到低探测 8.0.x（用 HEAD 请求，秒级响应）
            for mysql_ver in 42 41 40 39 38 37 36; do
                TEST_URL="https://dev.mysql.com/get/Downloads/MySQL-8.0/mysql-8.0.${mysql_ver}-winx64.zip"
                HTTP_CODE=$(curl -fsSI -o /dev/null -w '%{http_code}' --max-time 5 "$TEST_URL" 2>/dev/null || echo "000")
                if [[ "$HTTP_CODE" == "200" ]] || [[ "$HTTP_CODE" == "302" ]]; then
                    MYSQL_URL="$TEST_URL"
                    info "MySQL: 检测到 mysql-8.0.${mysql_ver}"
                    break
                fi
            done
            # 回退: 探测 MySQL 8.4 LTS
            if [[ -z "$MYSQL_URL" ]]; then
                for mysql_minor in 5 4 3 2 1 0; do
                    TEST_URL="https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-8.4.${mysql_minor}-winx64.zip"
                    HTTP_CODE=$(curl -fsSI -o /dev/null -w '%{http_code}' --max-time 5 "$TEST_URL" 2>/dev/null || echo "000")
                    if [[ "$HTTP_CODE" == "200" ]] || [[ "$HTTP_CODE" == "302" ]]; then
                        MYSQL_URL="$TEST_URL"
                        info "MySQL: 检测到 mysql-8.4.${mysql_minor}"
                        break
                    fi
                done
            fi
            if [[ -z "$MYSQL_URL" ]]; then
                error "MySQL: 无法自动解析最新版本"
                echo "    请手动设置: export MYSQL_DOWNLOAD_URL=\"https://dev.mysql.com/get/Downloads/MySQL-8.0/mysql-8.0.xx-winx64.zip\""
            fi
        fi

        # Nginx: 从官网下载页抓取最新稳定版（偶数次版本号为稳定版）
        if [[ -n "${NGINX_DOWNLOAD_URL:-}" ]]; then
            NGINX_URL="$NGINX_DOWNLOAD_URL"
        else
            echo -e "  ${CYAN}解析最新 Nginx 稳定版...${NC}"
            # 偶数次版本号(1.24.x, 1.26.x, 1.28.x)为稳定版
            NGINX_FILENAME=$(curl -fsSL "https://nginx.org/en/download.html" 2>/dev/null \
                | grep -oE 'nginx-[0-9]+\.(([0-9]*[02468])\.[0-9]+)' | sort -V | tail -1)
            if [[ -n "$NGINX_FILENAME" ]]; then
                NGINX_URL="https://nginx.org/download/${NGINX_FILENAME}.zip"
                info "Nginx: 检测到最新稳定版 $NGINX_FILENAME"
            else
                NGINX_URL=""
                warn "Nginx: 无法自动解析版本，安装时将回退到 PHP 内置服务器"
            fi
        fi

        # Laragon core (可选)
        LARAGON_URL="${LARAGON_URL_OVERRIDE:-${LARAGON_DOWNLOAD_URL:-https://github.com/leokhoa/laragon/archive/refs/tags/8.6.1.zip}}"

        echo ""
        echo -e "  组件下载地址:"
        echo -e "    PHP:      ${PHP_URL:-(解析失败)}"
        echo -e "    MySQL:    ${MYSQL_URL:-(解析失败)}"
        echo -e "    Nginx:    ${NGINX_URL:-(解析失败)}"
        echo -e "    Composer: $COMPOSER_URL"
        echo -e "    Laragon:  $LARAGON_URL"
        echo ""

        ASSEMBLE_FAILED=false

        # ── 1. Laragon core (面板程序) ──
        if download_and_extract "$LARAGON_URL" "$CACHE_DIR/laragon-core.zip" "$CACHE_DIR/laragon-core" "Laragon core"; then
            # 复制 laragon.exe 和配置到组装目录
            if [[ -f "$CACHE_DIR/laragon-core/laragon.exe" ]]; then
                cp "$CACHE_DIR/laragon-core/laragon.exe" "$ASSEMBLED_DIR/"
            fi
            # 复制 bin/laragon (管理工具)
            if [[ -d "$CACHE_DIR/laragon-core/bin/laragon" ]]; then
                cp -r "$CACHE_DIR/laragon-core/bin/laragon" "$ASSEMBLED_DIR/bin/"
            fi
            # 复制 etc 配置模板
            if [[ -d "$CACHE_DIR/laragon-core/etc" ]]; then
                cp -rn "$CACHE_DIR/laragon-core/etc/"* "$ASSEMBLED_DIR/etc/" 2>/dev/null || true
            fi
        else
            warn "Laragon core 下载失败 — 跳过面板程序（不影响 PHP/MySQL/Nginx）"
        fi

        # ── 2. PHP ──
        if [[ -z "$PHP_URL" ]]; then
            error "PHP: 无下载地址，跳过"
            ASSEMBLE_FAILED=true
        elif download_and_extract "$PHP_URL" "$CACHE_DIR/php.zip" "$CACHE_DIR/php-extracted" "PHP"; then
            # 判断解压后的目录名
            PHP_DIRNAME=$(ls "$CACHE_DIR/php-extracted/" 2>/dev/null | head -1)
            if [[ -f "$CACHE_DIR/php-extracted/php.exe" ]]; then
                # zip 直接是文件（无子目录）— 用文件名推导目录名
                PHP_VER_NAME=$(basename "$PHP_URL" .zip)
                mkdir -p "$ASSEMBLED_DIR/bin/php/$PHP_VER_NAME"
                cp -r "$CACHE_DIR/php-extracted/"* "$ASSEMBLED_DIR/bin/php/$PHP_VER_NAME/"
            elif [[ -n "$PHP_DIRNAME" ]] && [[ -f "$CACHE_DIR/php-extracted/$PHP_DIRNAME/php.exe" ]]; then
                cp -r "$CACHE_DIR/php-extracted/$PHP_DIRNAME" "$ASSEMBLED_DIR/bin/php/"
            else
                # 兜底：整个目录都复制
                PHP_VER_NAME=$(basename "$PHP_URL" .zip)
                mkdir -p "$ASSEMBLED_DIR/bin/php/$PHP_VER_NAME"
                cp -r "$CACHE_DIR/php-extracted/"* "$ASSEMBLED_DIR/bin/php/$PHP_VER_NAME/"
            fi
            # 配置 php.ini（从开发模板复制）
            for php_dir in "$ASSEMBLED_DIR"/bin/php/*/; do
                if [[ -f "${php_dir}php.ini-production" ]] && [[ ! -f "${php_dir}php.ini" ]]; then
                    cp "${php_dir}php.ini-production" "${php_dir}php.ini"
                    # 启用项目所需的扩展
                    sed -i '' -e 's/^;extension=pdo_mysql/extension=pdo_mysql/' \
                             -e 's/^;extension=mbstring/extension=mbstring/' \
                             -e 's/^;extension=openssl/extension=openssl/' \
                             -e 's/^;extension=fileinfo/extension=fileinfo/' \
                             -e 's/^;extension=gd/extension=gd/' \
                             -e 's/^;extension=curl/extension=curl/' \
                             -e 's/^;extension=zip/extension=zip/' \
                             -e 's/^;extension=exif/extension=exif/' \
                             -e 's/^;extension=intl/extension=intl/' \
                             "${php_dir}php.ini"
                    info "PHP: 已生成 php.ini（已启用必要扩展）"
                fi
            done
        else
            error "PHP 下载失败 — 安装包将缺少 PHP！"
            ASSEMBLE_FAILED=true
        fi

        # ── 3. MySQL ──
        if [[ -z "$MYSQL_URL" ]]; then
            error "MySQL: 无下载地址，跳过"
            ASSEMBLE_FAILED=true
        elif download_and_extract "$MYSQL_URL" "$CACHE_DIR/mysql.zip" "$CACHE_DIR/mysql-extracted" "MySQL"; then
            MYSQL_DIRNAME=$(ls "$CACHE_DIR/mysql-extracted/" 2>/dev/null | head -1)
            if [[ -n "$MYSQL_DIRNAME" ]] && [[ -f "$CACHE_DIR/mysql-extracted/$MYSQL_DIRNAME/bin/mysqld.exe" ]]; then
                cp -r "$CACHE_DIR/mysql-extracted/$MYSQL_DIRNAME" "$ASSEMBLED_DIR/bin/mysql/"
            elif [[ -f "$CACHE_DIR/mysql-extracted/bin/mysqld.exe" ]]; then
                MYSQL_VER_NAME=$(basename "$MYSQL_URL" .zip)
                mkdir -p "$ASSEMBLED_DIR/bin/mysql/$MYSQL_VER_NAME"
                cp -r "$CACHE_DIR/mysql-extracted/"* "$ASSEMBLED_DIR/bin/mysql/$MYSQL_VER_NAME/"
            fi
            # 生成 my.ini
            MYSQL_ACTUAL_DIR=$(ls -d "$ASSEMBLED_DIR"/bin/mysql/*/ 2>/dev/null | head -1)
            if [[ -n "$MYSQL_ACTUAL_DIR" ]] && [[ ! -f "$ASSEMBLED_DIR/etc/mysql/my.ini" ]]; then
                cat > "$ASSEMBLED_DIR/etc/mysql/my.ini" <<'MYINI'
[mysqld]
port=3306
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
innodb_buffer_pool_size=256M
max_connections=100
skip-grant-tables=0

[client]
port=3306
default-character-set=utf8mb4
MYINI
                info "MySQL: 已生成 etc/mysql/my.ini"
            fi
        else
            error "MySQL 下载失败 — 安装包将缺少 MySQL！"
            ASSEMBLE_FAILED=true
        fi

        # ── 4. Nginx ──
        if [[ -z "$NGINX_URL" ]]; then
            warn "Nginx: 无下载地址，跳过"
        elif download_and_extract "$NGINX_URL" "$CACHE_DIR/nginx.zip" "$CACHE_DIR/nginx-extracted" "Nginx"; then
            NGINX_DIRNAME=$(ls "$CACHE_DIR/nginx-extracted/" 2>/dev/null | head -1)
            if [[ -n "$NGINX_DIRNAME" ]] && [[ -f "$CACHE_DIR/nginx-extracted/$NGINX_DIRNAME/nginx.exe" ]]; then
                cp -r "$CACHE_DIR/nginx-extracted/$NGINX_DIRNAME" "$ASSEMBLED_DIR/bin/nginx/"
            elif [[ -f "$CACHE_DIR/nginx-extracted/nginx.exe" ]]; then
                NGINX_VER_NAME=$(basename "$NGINX_URL" .zip)
                mkdir -p "$ASSEMBLED_DIR/bin/nginx/$NGINX_VER_NAME"
                cp -r "$CACHE_DIR/nginx-extracted/"* "$ASSEMBLED_DIR/bin/nginx/$NGINX_VER_NAME/"
            fi
        else
            warn "Nginx 下载失败 — 安装时将回退到 PHP 内置服务器"
        fi

        # ── 5. Composer ──
        mkdir -p "$ASSEMBLED_DIR/bin/composer"
        COMPOSER_PHAR="$ASSEMBLED_DIR/bin/composer/composer.phar"
        if [[ ! -f "$COMPOSER_PHAR" ]]; then
            echo -e "  ${CYAN}下载 Composer ...${NC}"
            if command -v curl &>/dev/null; then
                curl -fSL -o "$COMPOSER_PHAR" "$COMPOSER_URL" 2>/dev/null
            elif command -v wget &>/dev/null; then
                wget -q -O "$COMPOSER_PHAR" "$COMPOSER_URL" 2>/dev/null
            fi
            if [[ -f "$COMPOSER_PHAR" ]] && [[ -s "$COMPOSER_PHAR" ]]; then
                info "Composer: 下载完成"
            else
                warn "Composer 下载失败 — 安装时将尝试使用系统 Composer"
                rm -f "$COMPOSER_PHAR"
            fi
        else
            info "Composer: 使用缓存"
        fi

        # ── 组装结果验证 ──
        if [[ "$ASSEMBLE_FAILED" == true ]]; then
            echo ""
            error "运行环境组装不完整（PHP 或 MySQL 下载失败）"
            echo ""
            echo "  可能原因:"
            echo "    1. 自动版本解析失败（网络问题或官网页面变化）"
            echo "    2. 下载被中断或服务器拒绝"
            echo ""
            echo "  解决方法: 手动指定下载地址"
            echo "    # 从 https://windows.php.net/download/ 获取最新 PHP 8.2 NTS x64 zip 直链"
            echo "    export PHP_DOWNLOAD_URL=\"https://windows.php.net/downloads/releases/php-8.2.xx-nts-Win32-vs16-x64.zip\""
            echo "    # 从 https://dev.mysql.com/downloads/mysql/ 获取最新 MySQL 8.0 zip 直链"
            echo "    export MYSQL_DOWNLOAD_URL=\"https://dev.mysql.com/get/Downloads/MySQL-8.0/mysql-8.0.xx-winx64.zip\""
            echo "    ./deploy/build.sh --target win --assemble-runtime"
            echo ""
            echo "  或者清除缓存后重试:"
            echo "    rm -rf deploy/.cache/php* deploy/.cache/mysql* deploy/.cache/laragon/bin/php deploy/.cache/laragon/bin/mysql"
            echo "    ./deploy/build.sh --target win --assemble-runtime"
            echo ""
            fatal "运行环境组装失败"
        fi

        echo ""
        info "运行环境组装完成: $ASSEMBLED_DIR"
    fi

    BUNDLE_LARAGON="$ASSEMBLED_DIR"
fi

# ── 兼容: 旧的 --download-laragon 单 URL 模式（如缓存中有旧的完整 Laragon） ──
# 如果 ASSEMBLE_RUNTIME 未启用但 DOWNLOAD_LARAGON 启用且缓存中有旧的完整包，仍支持
# （此分支在新的 --assemble-runtime 逻辑已处理后不会执行）

# 校验 --bundle-laragon
if [[ -n "$BUNDLE_LARAGON" ]]; then
    if [[ "$TARGET" != "win" ]]; then
        fatal "--bundle-laragon 仅适用于 --target win"
    fi
    if [[ ! -d "$BUNDLE_LARAGON" ]]; then
        fatal "Laragon 目录不存在: $BUNDLE_LARAGON"
    fi
    # 检查是否包含 laragon.exe 或 bin/ 目录
    if [[ ! -f "$BUNDLE_LARAGON/laragon.exe" ]] && [[ ! -d "$BUNDLE_LARAGON/bin" ]]; then
        fatal "目录不像有效的 Laragon Portable: 未找到 laragon.exe 或 bin/ 子目录\n  提示: 从 https://laragon.org/download/ 下载 Laragon Full Portable 并解压"
    fi
    # 检查是否包含 PHP（安装脚本依赖 bin/php/ 下的 PHP）
    PHP_FOUND=false
    if [[ -d "$BUNDLE_LARAGON/bin/php" ]]; then
        # 检查各种可能的 PHP 目录命名: php-8*, php8*, php*, 或直接 php.exe
        for php_dir in "$BUNDLE_LARAGON"/bin/php/php*/; do
            if [[ -f "${php_dir}php.exe" ]]; then
                PHP_FOUND=true
                info "检测到 PHP: $(basename "$php_dir")"
                break
            fi
        done
        if [[ "$PHP_FOUND" == false ]] && [[ -f "$BUNDLE_LARAGON/bin/php/php.exe" ]]; then
            PHP_FOUND=true
            info "检测到 PHP: bin/php/php.exe"
        fi
    fi
    if [[ "$PHP_FOUND" == false ]]; then
        echo ""
        error "Laragon 目录中未找到 PHP！"
        echo ""
        echo "  安装脚本需要 PHP 位于以下任一位置:"
        echo "    $BUNDLE_LARAGON/bin/php/php-8.x.x-.../php.exe"
        echo "    $BUNDLE_LARAGON/bin/php/php.exe"
        echo ""
        echo "  bin/php/ 目录内容:"
        if [[ -d "$BUNDLE_LARAGON/bin/php" ]]; then
            ls -la "$BUNDLE_LARAGON/bin/php/" | head -20
        else
            echo "    (目录不存在)"
        fi
        echo ""
        fatal "Laragon Portable 缺少 PHP，请下载包含 PHP 8.2+ 的 Laragon Full 版本"
    fi
    info "将打包 Laragon Portable: $BUNDLE_LARAGON"
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
if [[ -n "$BUNDLE_LARAGON" ]]; then
    TOTAL_STEPS=$((TOTAL_STEPS + 1))  # bundle laragon
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
if [[ -n "$BUNDLE_LARAGON" ]]; then
echo -e "  Laragon:  ${BOLD}${BUNDLE_LARAGON}${NC}"
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

        # 替换原始 app/ 目录
        rm -rf "$APP_SRC"
        mv "$APP_OBFUSCATED" "$APP_SRC"
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
        for script in install-win.bat upgrade-win.bat start-win.bat stop-win.bat uninstall-win.bat laragon-startup.bat; do
            if [[ -f "$PROJECT_ROOT/deploy/$script" ]]; then
                cp "$PROJECT_ROOT/deploy/$script" "$DIST_DIR/"
                info "复制 $script"
            else
                warn "脚本不存在，跳过: $script"
            fi
        done
        # 创建 setup.bat 快捷入口
        cat > "$DIST_DIR/setup.bat" <<'SHORTCUT_BAT'
@echo off
chcp 65001 >nul 2>&1
echo.
echo  Starting installer...
echo.
call "%~dp0install-win.bat" "%~dp0"
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
# Step N: 打包 Laragon Portable（仅 Windows + --bundle-laragon）
# ═══════════════════════════════════════════════════════════════════════
if [[ -n "$BUNDLE_LARAGON" ]]; then
    step "打包 Laragon Portable 运行环境"

    LARAGON_DEST="$DIST_DIR/laragon"
    info "正在复制 Laragon Portable（这可能需要几分钟）..."
    rsync -a \
        --exclude='tmp/*' \
        --exclude='www/*' \
        "$BUNDLE_LARAGON/" "$LARAGON_DEST/"

    # 创建项目在 Laragon www 下的目录结构标记
    mkdir -p "$LARAGON_DEST/www"
    info "Laragon Portable 已打包"

    LARAGON_SIZE=$(du -sh "$LARAGON_DEST" 2>/dev/null | cut -f1)
    info "Laragon 大小: ${LARAGON_SIZE}"

    # 更新 setup.bat —— 告诉 install-win.bat Laragon 已内置
    cat > "$DIST_DIR/setup.bat" <<'BUNDLED_BAT'
@echo off
setlocal EnableExtensions DisableDelayedExpansion
chcp 936 >nul 2>&1

echo.
echo  =======================================================
echo    Dental Clinic Management System - Offline Installer
echo  =======================================================
echo.
echo  This package already includes PHP, MySQL and Nginx.
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

set "PARTIAL_INSTALL=0"
if exist "%INSTALL_DIR%\laragon" set "PARTIAL_INSTALL=1"
if exist "%INSTALL_DIR%\install-win.bat" set "PARTIAL_INSTALL=1"
if exist "%INSTALL_DIR%\laragon\www\dental" set "PARTIAL_INSTALL=1"

if "%PARTIAL_INSTALL%"=="1" (
    echo  [WARN] Existing installation files were found in:
    echo         %INSTALL_DIR%
    echo.
    echo         This usually means a previous install was interrupted or failed.
    echo         setup.bat will clean the old runtime files and retry.
    echo.
    set "RETRY_CONFIRM=N"
    set /p "RETRY_CONFIRM=Continue and clean previous files? [Y/N]: "
    if /I not "%RETRY_CONFIRM%"=="Y" (
        echo  Installation cancelled.
        pause
        exit /b 1
    )

    echo  [0/4] Cleaning previous installation files...
    if exist "%INSTALL_DIR%\laragon" rmdir /S /Q "%INSTALL_DIR%\laragon" >nul 2>&1
    if exist "%INSTALL_DIR%\ocr-wheels" rmdir /S /Q "%INSTALL_DIR%\ocr-wheels" >nul 2>&1
    for %%F in (install-win.bat upgrade-win.bat start-win.bat stop-win.bat uninstall-win.bat laragon-startup.bat) do (
        if exist "%INSTALL_DIR%\%%F" del /F /Q "%INSTALL_DIR%\%%F" >nul 2>&1
    )

    if exist "%INSTALL_DIR%\laragon" (
        echo  [ERROR] Failed to clean previous laragon directory.
        echo          Please close any running Laragon/MySQL/Nginx windows and try again.
        pause
        exit /b 1
    )
    echo         Previous files cleaned.
)

echo  [1/4] Copying runtime environment...
if exist "%~dp0laragon" (
    xcopy "%~dp0laragon" "%INSTALL_DIR%\laragon\" /E /I /H /Y /Q >nul 2>&1
    if errorlevel 1 (
        echo  [ERROR] Failed to copy laragon runtime files.
        pause
        exit /b 1
    )
    echo         Runtime copied.
) else (
    echo  [ERROR] Missing laragon directory in package.
    pause
    exit /b 1
)

echo  [2/4] Copying application files...
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

if exist "%~dp0ocr-wheels" (
    xcopy "%~dp0ocr-wheels" "%INSTALL_DIR%\ocr-wheels\" /E /I /H /Y /Q >nul 2>&1
)

for %%F in (install-win.bat upgrade-win.bat start-win.bat stop-win.bat uninstall-win.bat laragon-startup.bat) do (
    if exist "%~dp0%%F" copy "%~dp0%%F" "%INSTALL_DIR%\" /Y >nul 2>&1
)

echo  [3/4] Normalizing batch file encoding...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$enc = [System.Text.Encoding]::GetEncoding(936); $targets = @('install-win.bat','upgrade-win.bat','start-win.bat','stop-win.bat','uninstall-win.bat','laragon-startup.bat'); foreach ($name in $targets) { $path = Join-Path '%INSTALL_DIR%' $name; if (-not (Test-Path $path)) { continue }; $bytes = [System.IO.File]::ReadAllBytes($path); try { $text = [System.Text.Encoding]::UTF8.GetString($bytes); if ($text.Contains([char]0xFFFD)) { throw 'decode-failed' } } catch { $text = [System.Text.Encoding]::Default.GetString($bytes) }; $text = $text -replace \"`r?`n\", \"`r`n\"; [System.IO.File]::WriteAllText($path, $text, $enc) }" >nul 2>&1
if errorlevel 1 (
    echo         [WARN] Batch encoding normalization failed. Install may still work if the package was already built as GBK.
) else (
    echo         Batch files normalized to GBK + CRLF.
)

echo  [4/4] Launching installer...
echo.

call "%INSTALL_DIR%\install-win.bat" "%INSTALL_DIR%"
BUNDLED_BAT
    info "更新 setup.bat（Laragon 内置模式）"
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
if command -v zipinfo &>/dev/null; then
    # 统计目录级别的文件分布
    zipinfo -1 "$ARCHIVE_PATH" | sed 's|[^/]*/||' | cut -d'/' -f1 | sort | uniq -c | sort -rn | head -15 | while read -r count name; do
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
