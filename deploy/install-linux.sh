#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════════════
#  牙科诊所管理系统 — Linux / macOS 离线安装脚本
#  Dental Clinic Management System — offline installer for Linux & macOS
#
#  用法 / Usage:
#    sudo ./install-linux.sh [选项]
#
#  选项 / Options:
#    --install-dir DIR    安装目录 (默认: /opt/dental)
#    --db-host HOST       数据库主机 (默认: 127.0.0.1)
#    --db-port PORT       数据库端口 (默认: 3306)
#    --db-name NAME       数据库名称 (默认: pristine_dental)
#    --db-user USER       数据库用户 (默认: dental)
#    --db-pass PASS       数据库密码 (默认: 随机生成)
#    --db-root-pass PASS  MySQL root 密码 (默认: 空)
#    --app-url URL        应用访问地址 (默认: http://localhost)
#    --port PORT          Web 服务监听端口 (默认: 80)
#    --skip-ocr           跳过 OCR 环境安装
#    --no-service         不创建 systemd 服务与 cron
#    --auto-deps          自动安装缺失的系统依赖（PHP, MySQL, Nginx, Composer）
#    --source-dir DIR     项目源文件目录（默认: 脚本所在目录的上级）
#    --help               显示帮助
#
#  此脚本假设从解压后的发行包内运行，项目文件位于 ../ 相对路径。
#  使用 --source-dir 可指定其他源目录。
#  脚本支持幂等运行——重复执行不会破坏已有数据。
# ═══════════════════════════════════════════════════════════════════════════════
set -euo pipefail

# ── 颜色输出 ──────────────────────────────────────────────────────────────────
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

ok()   { echo -e "  ${GREEN}[OK]${NC} $*"; }
fail() { echo -e "  ${RED}[FAIL]${NC} $*"; }
warn() { echo -e "  ${YELLOW}[WARN]${NC} $*"; }
info() { echo -e "  ${CYAN}[INFO]${NC} $*"; }

CURRENT_STEP=0
TOTAL_STEPS=12

step() {
    CURRENT_STEP=$((CURRENT_STEP + 1))
    echo ""
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BOLD}  [${CURRENT_STEP}/${TOTAL_STEPS}] $*${NC}"
    echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# ── 版本信息 ──────────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# 提前初始化 VERSION，确保 --help 调用 show_help() 时变量已定义
VERSION="unknown"
if [[ -f "$SCRIPT_DIR/VERSION" ]]; then
    VERSION="$(tr -d '[:space:]' < "$SCRIPT_DIR/VERSION")"
elif [[ -f "$SCRIPT_DIR/../VERSION" ]]; then
    VERSION="$(tr -d '[:space:]' < "$SCRIPT_DIR/../VERSION")"
fi

# ── 默认参数 ──────────────────────────────────────────────────────────────────
INSTALL_DIR="/opt/dental"
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="pristine_dental"
DB_USER="dental"
DB_PASS=""
DB_ROOT_PASS=""
APP_URL=""
LISTEN_PORT="80"
SKIP_OCR=false
NO_SERVICE=false
AUTO_DEPS=false
SOURCE_DIR_OVERRIDE=""

# ── 随机密码生成 ──────────────────────────────────────────────────────────────
generate_password() {
    # 16 chars, alphanumeric + some symbols safe for shell/SQL
    LC_ALL=C tr -dc 'A-Za-z0-9!@#%^*_+=' < /dev/urandom 2>/dev/null | head -c 16 || \
    openssl rand -base64 16 2>/dev/null | tr -d '/+=' | head -c 16 || \
    date +%s%N | sha256sum | head -c 16
}

# ── 解析命令行参数 ────────────────────────────────────────────────────────────
show_help() {
    cat <<HELPEOF
牙科诊所管理系统 v${VERSION} — Linux/macOS 安装脚本

用法: sudo $0 [选项]

选项:
  --install-dir DIR    安装目录           (默认: /opt/dental)
  --db-host HOST       数据库主机         (默认: 127.0.0.1)
  --db-port PORT       数据库端口         (默认: 3306)
  --db-name NAME       数据库名称         (默认: pristine_dental)
  --db-user USER       数据库用户         (默认: dental)
  --db-pass PASS       数据库密码         (默认: 随机生成)
  --db-root-pass PASS  MySQL root 密码    (默认: 空)
  --app-url URL        应用访问地址       (默认: http://localhost:<port>)
  --port PORT          Web 监听端口       (默认: 80)
  --skip-ocr           跳过 OCR 环境安装
  --no-service         不创建 systemd 服务与 cron
  --auto-deps          自动安装缺失的系统依赖（推荐一键安装）
  --source-dir DIR     项目源文件所在目录
  --help               显示此帮助

示例:
  sudo ./install-linux.sh --auto-deps             # 一键安装（自动装依赖）
  sudo ./install-linux.sh --install-dir /opt/dental --db-pass SECRET
  sudo ./install-linux.sh --skip-ocr --no-service
HELPEOF
    exit 0
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --install-dir)   INSTALL_DIR="$2";   shift 2 ;;
        --db-host)       DB_HOST="$2";       shift 2 ;;
        --db-port)       DB_PORT="$2";       shift 2 ;;
        --db-name)       DB_NAME="$2";       shift 2 ;;
        --db-user)       DB_USER="$2";       shift 2 ;;
        --db-pass)       DB_PASS="$2";       shift 2 ;;
        --db-root-pass)  DB_ROOT_PASS="$2";  shift 2 ;;
        --app-url)       APP_URL="$2";       shift 2 ;;
        --port)          LISTEN_PORT="$2";   shift 2 ;;
        --skip-ocr)      SKIP_OCR=true;      shift ;;
        --no-service)    NO_SERVICE=true;    shift ;;
        --auto-deps)     AUTO_DEPS=true;     shift ;;
        --source-dir)    SOURCE_DIR_OVERRIDE="$2"; shift 2 ;;
        --help|-h)       show_help ;;
        *)
            echo "未知选项 / Unknown option: $1"
            echo "使用 --help 查看帮助"
            exit 1
            ;;
    esac
done

# ── 源目录定位 ──────────────────────────────────────────────────────────────────
# 优先级: --source-dir > 脚本同级目录（如果有 artisan）> 上级目录
if [[ -n "$SOURCE_DIR_OVERRIDE" ]]; then
    SOURCE_DIR="$(cd "$SOURCE_DIR_OVERRIDE" && pwd)"
elif [[ -f "$SCRIPT_DIR/artisan" ]]; then
    SOURCE_DIR="$SCRIPT_DIR"
else
    SOURCE_DIR="$(cd "$SCRIPT_DIR/.." 2>/dev/null && pwd || echo "$SCRIPT_DIR")"
fi

VERSION="unknown"
if [[ -f "$SOURCE_DIR/VERSION" ]]; then
    VERSION="$(tr -d '[:space:]' < "$SOURCE_DIR/VERSION")"
elif [[ -f "$SCRIPT_DIR/VERSION" ]]; then
    VERSION="$(tr -d '[:space:]' < "$SCRIPT_DIR/VERSION")"
fi

# 默认 APP_URL
if [[ -z "$APP_URL" ]]; then
    if [[ "$LISTEN_PORT" == "80" ]]; then
        APP_URL="http://localhost"
    else
        APP_URL="http://localhost:${LISTEN_PORT}"
    fi
fi

# 默认 DB_PASS：若未指定则生成随机密码
DB_PASS_GENERATED=false
if [[ -z "$DB_PASS" ]]; then
    DB_PASS="$(generate_password)"
    DB_PASS_GENERATED=true
fi

# ── 失败处理 ──────────────────────────────────────────────────────────────────
INSTALL_SUCCEEDED=false

cleanup_on_failure() {
    if [[ "$INSTALL_SUCCEEDED" != "true" ]]; then
        echo ""
        echo -e "${RED}================================================================${NC}"
        echo -e "${RED}  安装未完成！请检查以上错误信息。${NC}"
        if [[ "${PARTIAL_INSTALL_DETECTED:-false}" == "true" ]]; then
            echo -e "${RED}  检测到安装目录中存在上次中断/失败留下的残留文件。${NC}"
            echo -e "${RED}  如需重新安装，建议先备份 .env 和 storage/app 后清理安装目录。${NC}"
        fi
        echo -e "${RED}  修复问题后可重新运行此脚本（支持幂等执行）。${NC}"
        echo -e "${RED}================================================================${NC}"
    fi
}
trap cleanup_on_failure EXIT

# ═══════════════════════════════════════════════════════════════════════════════
#  开始安装
# ═══════════════════════════════════════════════════════════════════════════════
echo ""
echo -e "${BOLD}================================================================${NC}"
echo -e "${BOLD}  牙科诊所管理系统 v${VERSION} — 安装程序 (Linux/macOS)${NC}"
echo -e "${BOLD}================================================================${NC}"
echo ""
echo "  安装目录:  $INSTALL_DIR"
echo "  数据库:    $DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"
echo "  访问地址:  $APP_URL"
echo "  监听端口:  $LISTEN_PORT"
echo "  OCR 支持:  $(if $SKIP_OCR; then echo '跳过'; else echo '启用'; fi)"
echo "  systemd:   $(if $NO_SERVICE; then echo '跳过'; else echo '启用'; fi)"

# ══════════════════════════════════════════════════════════════════════════════
#  Step 1: Pre-flight — 检查运行环境
# ══════════════════════════════════════════════════════════════════════════════
step "Pre-flight 检查"

# ── 1a. Root / sudo 检测 ─────────────────────────────────────────────────────
if [[ "$(id -u)" -ne 0 ]]; then
    fail "此脚本需要 root 权限运行。请使用: sudo $0 $*"
    exit 1
fi
ok "root 权限确认"

# ── 1b. 操作系统检测 ─────────────────────────────────────────────────────────
OS_TYPE=""
DISTRO=""
PKG_MANAGER=""
INIT_SYSTEM=""

case "$(uname -s)" in
    Linux*)
        OS_TYPE="linux"
        # Detect init system
        if command -v systemctl &>/dev/null && systemctl --version &>/dev/null 2>&1; then
            INIT_SYSTEM="systemd"
        fi
        # Detect distro
        if [[ -f /etc/os-release ]]; then
            # shellcheck disable=SC1091
            source /etc/os-release
            DISTRO="${ID:-unknown}"
            ok "操作系统: Linux — ${PRETTY_NAME:-$DISTRO} ($(uname -r))"
        else
            DISTRO="unknown"
            ok "操作系统: Linux ($(uname -r))"
        fi
        # Detect package manager
        if command -v apt-get &>/dev/null; then
            PKG_MANAGER="apt"
        elif command -v dnf &>/dev/null; then
            PKG_MANAGER="dnf"
        elif command -v yum &>/dev/null; then
            PKG_MANAGER="yum"
        elif command -v pacman &>/dev/null; then
            PKG_MANAGER="pacman"
        fi
        ;;
    Darwin*)
        OS_TYPE="macos"
        DISTRO="macos"
        INIT_SYSTEM="launchd"
        ok "操作系统: macOS $(sw_vers -productVersion 2>/dev/null || echo 'unknown')"
        if command -v brew &>/dev/null; then
            PKG_MANAGER="brew"
        fi
        ;;
    *)
        fail "不支持的操作系统: $(uname -s)"
        exit 1
        ;;
esac

# Platform-specific helpers
if [[ "$OS_TYPE" == "macos" ]]; then
    SED_INPLACE=(sed -i '')
    WEB_USER="_www"
    WEB_GROUP="_www"
else
    SED_INPLACE=(sed -i)
    # Detect web user
    if id www-data &>/dev/null; then
        WEB_USER="www-data"
        WEB_GROUP="www-data"
    elif id nginx &>/dev/null; then
        WEB_USER="nginx"
        WEB_GROUP="nginx"
    elif id apache &>/dev/null; then
        WEB_USER="apache"
        WEB_GROUP="apache"
    else
        WEB_USER="nobody"
        WEB_GROUP="nogroup"
    fi
fi
info "Web 服务用户: ${WEB_USER}:${WEB_GROUP}"
info "Init 系统: ${INIT_SYSTEM:-none}"

# ── 1c. 磁盘空间检查 (>1 GB) ────────────────────────────────────────────────
INSTALL_PARENT="$(dirname "$INSTALL_DIR")"
mkdir -p "$INSTALL_PARENT" 2>/dev/null || true

if [[ "$OS_TYPE" == "macos" ]]; then
    AVAIL_KB=$(df -k "$INSTALL_PARENT" 2>/dev/null | awk 'NR==2 {print $4}')
else
    AVAIL_KB=$(df -k "$INSTALL_PARENT" 2>/dev/null | awk 'NR==2 {print $4}')
fi

if [[ -n "${AVAIL_KB:-}" ]] && [[ "$AVAIL_KB" =~ ^[0-9]+$ ]]; then
    AVAIL_MB=$((AVAIL_KB / 1024))
    if [[ "$AVAIL_MB" -lt 1024 ]]; then
        fail "磁盘空间不足: ${AVAIL_MB} MB 可用（至少需要 1024 MB）"
        fail "目标分区: $INSTALL_PARENT"
        exit 1
    fi
    ok "磁盘空间: ${AVAIL_MB} MB 可用"
else
    warn "无法检测磁盘空间，继续安装"
fi

# ── 1d. 必要依赖检查 ─────────────────────────────────────────────────────────
MISSING_DEPS=()

# PHP 8.2+
if command -v php &>/dev/null; then
    PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
    PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")
    if [[ "$PHP_MAJOR" -gt 8 ]] || { [[ "$PHP_MAJOR" -eq 8 ]] && [[ "$PHP_MINOR" -ge 2 ]]; }; then
        ok "PHP ${PHP_VER}"
    else
        fail "PHP ${PHP_VER} — 需要 8.2+"
        MISSING_DEPS+=("php>=8.2")
    fi
    # Check required extensions
    REQUIRED_EXTS=(pdo_mysql mbstring openssl tokenizer xml ctype json bcmath fileinfo gd)
    MISSING_EXTS=()
    for ext in "${REQUIRED_EXTS[@]}"; do
        if ! php -m 2>/dev/null | grep -qi "^${ext}$"; then
            MISSING_EXTS+=("$ext")
        fi
    done
    if [[ ${#MISSING_EXTS[@]} -gt 0 ]]; then
        warn "缺少 PHP 扩展: ${MISSING_EXTS[*]}"
    fi
else
    fail "未找到 PHP"
    MISSING_DEPS+=("php>=8.2")
fi

# MySQL / MariaDB client
MYSQL_CMD=""
if command -v mysql &>/dev/null; then
    MYSQL_CMD="mysql"
    ok "MySQL 客户端: $(mysql --version 2>/dev/null | head -1)"
elif command -v mariadb &>/dev/null; then
    MYSQL_CMD="mariadb"
    ok "MariaDB 客户端: $(mariadb --version 2>/dev/null | head -1)"
else
    fail "未找到 mysql / mariadb 客户端"
    MISSING_DEPS+=("mysql-client")
fi

# Composer
if command -v composer &>/dev/null; then
    ok "Composer: $(composer --version 2>/dev/null | head -1)"
else
    fail "未找到 Composer"
    MISSING_DEPS+=("composer")
fi

# Web server (Nginx or Apache) — warn only, not blocking
WEB_SERVER=""
if command -v nginx &>/dev/null; then
    WEB_SERVER="nginx"
    ok "Nginx: $(nginx -v 2>&1 | head -1)"
elif command -v apache2 &>/dev/null || command -v httpd &>/dev/null; then
    WEB_SERVER="apache"
    ok "Apache: $(apache2 -v 2>/dev/null | head -1 || httpd -v 2>/dev/null | head -1)"
else
    warn "未找到 Nginx 或 Apache — 安装完成后需要手动配置 Web 服务器"
fi

# Python 3.8+ (for OCR, non-blocking)
PYTHON_CMD=""
PYTHON_AVAILABLE=false
if ! $SKIP_OCR; then
    for py_cmd in python3.12 python3.11 python3.10 python3.9 python3.8 python3; do
        if command -v "$py_cmd" &>/dev/null; then
            PY_MAJOR=$("$py_cmd" -c "import sys; print(sys.version_info.major)" 2>/dev/null || echo 0)
            PY_MINOR=$("$py_cmd" -c "import sys; print(sys.version_info.minor)" 2>/dev/null || echo 0)
            if [[ "$PY_MAJOR" -ge 3 ]] && [[ "$PY_MINOR" -ge 8 ]]; then
                PYTHON_CMD="$py_cmd"
                PYTHON_AVAILABLE=true
                ok "Python ${PY_MAJOR}.${PY_MINOR} ($py_cmd)"
                break
            fi
        fi
    done
    if ! $PYTHON_AVAILABLE; then
        warn "未找到 Python 3.8+ — OCR 功能将不可用（不影响核心功能）"
    fi
else
    info "OCR 已跳过 (--skip-ocr)"
fi

# Handle missing deps
if [[ ${#MISSING_DEPS[@]} -gt 0 ]]; then
    echo ""

    if [[ "$AUTO_DEPS" == true ]]; then
        # ── 自动安装缺失依赖 ──────────────────────────────────────────
        info "自动安装缺失依赖: ${MISSING_DEPS[*]}"
        echo ""

        case "$PKG_MANAGER" in
            apt)
                info "使用 apt 安装依赖..."
                apt-get update -qq
                # 添加 PHP 8.2 PPA (如果默认源没有)
                if ! apt-cache show php8.2 &>/dev/null; then
                    info "添加 PHP 8.2 PPA..."
                    apt-get install -y software-properties-common
                    add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
                    apt-get update -qq
                fi
                apt-get install -y \
                    php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
                    php8.2-xml php8.2-bcmath php8.2-gd php8.2-curl php8.2-zip \
                    mysql-server nginx unzip curl
                # Install Composer
                if ! command -v composer &>/dev/null; then
                    info "安装 Composer..."
                    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                fi
                ok "APT 依赖安装完成"
                ;;
            dnf)
                info "使用 dnf 安装依赖..."
                dnf install -y https://rpms.remirepo.net/fedora/remi-release-"$(rpm -E %fedora)".rpm 2>/dev/null || true
                dnf module enable -y php:remi-8.2 2>/dev/null || true
                dnf install -y \
                    php php-fpm php-mysqlnd php-mbstring \
                    php-xml php-bcmath php-gd php-zip \
                    mysql-server nginx unzip curl
                if ! command -v composer &>/dev/null; then
                    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                fi
                ok "DNF 依赖安装完成"
                ;;
            yum)
                info "使用 yum 安装依赖..."
                yum install -y epel-release 2>/dev/null || true
                yum install -y https://rpms.remirepo.net/enterprise/remi-release-"$(rpm -E %rhel)".rpm 2>/dev/null || true
                yum module enable -y php:remi-8.2 2>/dev/null || true
                yum install -y \
                    php php-fpm php-mysqlnd php-mbstring \
                    php-xml php-bcmath php-gd php-zip \
                    mysql-server nginx unzip curl
                if ! command -v composer &>/dev/null; then
                    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
                fi
                ok "YUM 依赖安装完成"
                ;;
            brew)
                info "使用 Homebrew 安装依赖..."
                brew install php@8.2 mysql nginx composer
                brew services start mysql
                ok "Homebrew 依赖安装完成"
                ;;
            *)
                fail "无法自动安装: 未识别的包管理器"
                fail "请手动安装: PHP 8.2+, MySQL/MariaDB, Composer, Nginx"
                exit 1
                ;;
        esac

        # 重新检测刚安装的组件
        MYSQL_CMD=""
        if command -v mysql &>/dev/null; then MYSQL_CMD="mysql"; fi
        if command -v mariadb &>/dev/null; then MYSQL_CMD="mariadb"; fi

        # 启动 MySQL 服务（新安装的可能未启动）
        if [[ "$INIT_SYSTEM" == "systemd" ]]; then
            systemctl enable mysql 2>/dev/null || systemctl enable mysqld 2>/dev/null || systemctl enable mariadb 2>/dev/null || true
            systemctl start mysql 2>/dev/null || systemctl start mysqld 2>/dev/null || systemctl start mariadb 2>/dev/null || true
        fi

        echo ""
    else
        # ── 仅打印安装提示 ────────────────────────────────────────────
        fail "缺少必要依赖: ${MISSING_DEPS[*]}"
        echo ""
        echo -e "  ${BOLD}方式一（推荐）: 添加 --auto-deps 自动安装${NC}"
        echo -e "  ${CYAN}sudo $0 --auto-deps $*${NC}"
        echo ""
        echo -e "  ${BOLD}方式二: 手动安装后重新运行${NC}"
        case "$PKG_MANAGER" in
            apt)
                echo -e "  ${CYAN}sudo apt update && sudo apt install -y php8.2 php8.2-fpm php8.2-mysql \\${NC}"
                echo -e "  ${CYAN}  php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-gd php8.2-curl \\${NC}"
                echo -e "  ${CYAN}  php8.2-zip mysql-server nginx composer${NC}"
                ;;
            dnf)
                echo -e "  ${CYAN}sudo dnf install -y php php-fpm php-mysqlnd php-mbstring \\${NC}"
                echo -e "  ${CYAN}  php-xml php-bcmath php-gd php-zip mysql-server nginx composer${NC}"
                ;;
            yum)
                echo -e "  ${CYAN}sudo yum install -y php php-fpm php-mysqlnd php-mbstring \\${NC}"
                echo -e "  ${CYAN}  php-xml php-bcmath php-gd php-zip mysql-server nginx composer${NC}"
                ;;
            brew)
                echo -e "  ${CYAN}brew install php@8.2 mysql nginx composer${NC}"
                ;;
            *)
                echo -e "  ${CYAN}请安装: PHP 8.2+, MySQL/MariaDB, Composer, Nginx${NC}"
                ;;
        esac
        echo ""
        exit 1
    fi
fi

# ══════════════════════════════════════════════════════════════════════════════
#  Step 2: 复制项目文件
# ══════════════════════════════════════════════════════════════════════════════
step "创建安装目录并复制项目文件"

# Verify source
if [[ ! -f "$SOURCE_DIR/artisan" ]]; then
    fail "未在 $SOURCE_DIR 找到 Laravel 项目文件（artisan 缺失）"
    fail "请确保从发行包的 deploy/ 目录内运行此脚本"
    exit 1
fi

# Create target
PARTIAL_INSTALL_DETECTED=false
if [[ -d "$INSTALL_DIR" ]]; then
    info "目录已存在: $INSTALL_DIR（更新模式）"
    if [[ ! -f "$INSTALL_DIR/artisan" ]] || [[ -d "$INSTALL_DIR/storage" ]] || [[ -f "$INSTALL_DIR/.env" ]] || [[ -d "$INSTALL_DIR/vendor" ]] || [[ -d "$INSTALL_DIR/scripts/venv" ]]; then
        PARTIAL_INSTALL_DETECTED=true
        echo ""
        echo -e "  ${YELLOW}检测到安装目录中已有残留文件。${NC}"
        echo -e "  ${YELLOW}这通常表示上次安装中断、失败，或目录已存在旧版本文件。${NC}"
        echo -e "  ${YELLOW}脚本将继续以覆盖/修复方式安装。${NC}"
        echo -e "  ${YELLOW}如果后续仍失败，建议先备份 .env 和 storage/app 后清理 ${INSTALL_DIR}。${NC}"
        echo ""
    fi
else
    mkdir -p "$INSTALL_DIR"
    ok "已创建: $INSTALL_DIR"
fi

# Ensure framework directories
mkdir -p "$INSTALL_DIR"/{storage/framework/{sessions,views,cache/data},storage/logs,storage/app/public,bootstrap/cache}

# Copy files
if command -v rsync &>/dev/null; then
    rsync -a --delete \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='.git' \
        --exclude='.env' \
        --exclude='storage/logs/*.log' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        --exclude='storage/framework/cache/data/*' \
        --exclude='deploy' \
        "$SOURCE_DIR/" "$INSTALL_DIR/"
    ok "文件同步完成 (rsync)"
else
    cp -R "$SOURCE_DIR/"* "$INSTALL_DIR/" 2>/dev/null || true
    cp "$SOURCE_DIR/".??* "$INSTALL_DIR/" 2>/dev/null || true
    rm -rf "$INSTALL_DIR/node_modules" "$INSTALL_DIR/.git" "$INSTALL_DIR/deploy" 2>/dev/null || true
    ok "文件复制完成 (cp)"
fi

# Re-ensure framework dirs after rsync --delete
mkdir -p "$INSTALL_DIR"/{storage/framework/{sessions,views,cache/data},storage/logs,storage/app/public,bootstrap/cache}

# Set ownership
chown -R "${WEB_USER}:${WEB_GROUP}" "$INSTALL_DIR" 2>/dev/null || true
chmod -R 775 "$INSTALL_DIR/storage" 2>/dev/null || true
chmod -R 775 "$INSTALL_DIR/bootstrap/cache" 2>/dev/null || true
chmod +x "$INSTALL_DIR/artisan" 2>/dev/null || true
ok "文件权限已设置 (owner=${WEB_USER}, storage/bootstrap 775)"

# ══════════════════════════════════════════════════════════════════════════════
#  Step 3: 配置 .env
# ══════════════════════════════════════════════════════════════════════════════
step "生成 .env 配置文件"

ENV_TEMPLATE="$INSTALL_DIR/.env.deploy"
# Fallback: look in deploy/ source
if [[ ! -f "$ENV_TEMPLATE" ]]; then
    ENV_TEMPLATE="$SCRIPT_DIR/.env.deploy"
fi
if [[ ! -f "$ENV_TEMPLATE" ]]; then
    fail "未找到 .env.deploy 模板文件"
    exit 1
fi

# OCR Python path
if $SKIP_OCR || ! $PYTHON_AVAILABLE; then
    OCR_PYTHON_PATH=""
else
    OCR_PYTHON_PATH="$INSTALL_DIR/scripts/venv/bin/python3"
fi

# Re-install detection: if .env exists, ask user
PRESERVE_ENV=false
if [[ -f "$INSTALL_DIR/.env" ]]; then
    echo ""
    echo -e "  ${YELLOW}检测到已有 .env 配置文件。${NC}"
    echo ""
    echo "    [1] 保留现有 .env（推荐用于升级）"
    echo "    [2] 覆盖为全新配置（现有配置将备份）"
    echo ""
    # If stdin is not a terminal (non-interactive), default to preserve
    if [[ -t 0 ]]; then
        read -r -p "  请选择 [1/2] (默认: 1): " ENV_CHOICE
    else
        ENV_CHOICE="1"
        info "非交互模式，默认保留已有 .env"
    fi
    case "${ENV_CHOICE:-1}" in
        2)
            BACKUP_NAME=".env.backup.$(date +%Y%m%d%H%M%S)"
            cp "$INSTALL_DIR/.env" "$INSTALL_DIR/${BACKUP_NAME}"
            ok "已有 .env 备份为: ${BACKUP_NAME}"
            PRESERVE_ENV=false
            ;;
        *)
            PRESERVE_ENV=true
            ok "保留现有 .env"
            ;;
    esac
fi

if ! $PRESERVE_ENV; then
    cp "$ENV_TEMPLATE" "$INSTALL_DIR/.env"

    # Escape special chars in DB_PASS for sed delimiter '|'
    DB_PASS_ESCAPED=$(printf '%s\n' "$DB_PASS" | sed 's/[|&/\]/\\&/g')

    "${SED_INPLACE[@]}" "s|{{DB_HOST}}|${DB_HOST}|g"                     "$INSTALL_DIR/.env"
    "${SED_INPLACE[@]}" "s|{{DB_PORT}}|${DB_PORT}|g"                     "$INSTALL_DIR/.env"
    "${SED_INPLACE[@]}" "s|{{DB_DATABASE}}|${DB_NAME}|g"                 "$INSTALL_DIR/.env"
    "${SED_INPLACE[@]}" "s|{{DB_USERNAME}}|${DB_USER}|g"                 "$INSTALL_DIR/.env"
    "${SED_INPLACE[@]}" "s|{{DB_PASSWORD}}|${DB_PASS_ESCAPED}|g"        "$INSTALL_DIR/.env"
    "${SED_INPLACE[@]}" "s|{{APP_URL}}|${APP_URL}|g"                     "$INSTALL_DIR/.env"
    "${SED_INPLACE[@]}" "s|{{OCR_PYTHON_PATH}}|${OCR_PYTHON_PATH}|g"    "$INSTALL_DIR/.env"

    ok ".env 已从模板生成"
fi

# ══════════════════════════════════════════════════════════════════════════════
#  Step 4: 生成 APP_KEY
# ══════════════════════════════════════════════════════════════════════════════
step "生成 APP_KEY"

cd "$INSTALL_DIR"

if grep -q '^APP_KEY=$' "$INSTALL_DIR/.env" 2>/dev/null; then
    php artisan key:generate --force --no-interaction
    ok "APP_KEY 已生成"
else
    info "APP_KEY 已存在，跳过"
fi

# ══════════════════════════════════════════════════════════════════════════════
#  Step 5: 安装 PHP 依赖
# ══════════════════════════════════════════════════════════════════════════════
step "安装 PHP 依赖 (composer install)"

cd "$INSTALL_DIR"

# If vendor/ exists with autoload, skip unless composer.lock changed
composer install --optimize-autoloader --no-dev --no-interaction 2>&1 | tail -5
ok "PHP 依赖安装完成"

# ══════════════════════════════════════════════════════════════════════════════
#  Step 6: 数据库初始化
# ══════════════════════════════════════════════════════════════════════════════
step "创建数据库并初始化数据"

# Build mysql args for root connection (used to create DB + user)
MYSQL_ROOT_ARGS=()
MYSQL_ROOT_ARGS+=(-h "$DB_HOST" -P "$DB_PORT" -u root)
if [[ -n "$DB_ROOT_PASS" ]]; then
    MYSQL_ROOT_ARGS+=(-p"$DB_ROOT_PASS")
fi

# Test root connection
if ! $MYSQL_CMD "${MYSQL_ROOT_ARGS[@]}" -e "SELECT 1" &>/dev/null; then
    # Fallback: try without password (common on fresh installs)
    MYSQL_ROOT_ARGS=(-h "$DB_HOST" -P "$DB_PORT" -u root)
    if ! $MYSQL_CMD "${MYSQL_ROOT_ARGS[@]}" -e "SELECT 1" &>/dev/null; then
        fail "无法以 root 连接 MySQL ($DB_HOST:$DB_PORT)"
        fail "请确认 MySQL 服务已启动，并使用 --db-root-pass 指定 root 密码"
        echo ""
        case "$OS_TYPE" in
            linux)
                info "启动 MySQL:  sudo systemctl start mysql  (或 mysqld / mariadb)"
                ;;
            macos)
                info "启动 MySQL:  brew services start mysql"
                ;;
        esac
        exit 1
    fi
fi
ok "MySQL root 连接成功"

# Create database (idempotent)
$MYSQL_CMD "${MYSQL_ROOT_ARGS[@]}" -e \
    "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
ok "数据库 \`${DB_NAME}\` 已就绪"

# Create dedicated user with grants (idempotent)
# For MySQL 8+ use CREATE USER IF NOT EXISTS; for 5.7 use GRANT which auto-creates
$MYSQL_CMD "${MYSQL_ROOT_ARGS[@]}" -e "
    CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST}';
    FLUSH PRIVILEGES;
" 2>/dev/null || {
    # MySQL 5.7 fallback (no IF NOT EXISTS for CREATE USER)
    $MYSQL_CMD "${MYSQL_ROOT_ARGS[@]}" -e "
        GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}';
        FLUSH PRIVILEGES;
    " 2>/dev/null || warn "数据库用户创建/授权可能需要手动检查"
}
ok "数据库用户 '${DB_USER}' 已授权"

# Also allow localhost if DB_HOST is 127.0.0.1
if [[ "$DB_HOST" == "127.0.0.1" ]]; then
    $MYSQL_CMD "${MYSQL_ROOT_ARGS[@]}" -e "
        CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
        GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
        FLUSH PRIVILEGES;
    " 2>/dev/null || true
fi

# Database schema: prefer schema.sql dump (faster), else run migrations
cd "$INSTALL_DIR"

SCHEMA_SQL=""
for candidate in "$INSTALL_DIR/database/schema.sql" "$INSTALL_DIR/database/schema/mysql-schema.sql" "$SOURCE_DIR/database/schema.sql"; do
    if [[ -f "$candidate" ]]; then
        SCHEMA_SQL="$candidate"
        break
    fi
done

# Check if DB already has tables (upgrade scenario)
TABLE_COUNT=$($MYSQL_CMD "${MYSQL_ROOT_ARGS[@]}" -N -e \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';" 2>/dev/null || echo "0")

if [[ "${TABLE_COUNT:-0}" -gt 0 ]]; then
    info "数据库已包含 ${TABLE_COUNT} 张表 — 运行增量迁移"
    php artisan migrate --force --no-interaction 2>&1 | tail -5
    ok "增量迁移完成"
else
    if [[ -n "$SCHEMA_SQL" ]]; then
        info "导入基线 schema: $(basename "$SCHEMA_SQL")"
        $MYSQL_CMD "${MYSQL_ROOT_ARGS[@]}" "$DB_NAME" < "$SCHEMA_SQL"
        ok "Schema 导入完成"
        # Run remaining migrations
        php artisan migrate --force --no-interaction 2>&1 | tail -5
        ok "增量迁移完成"
    else
        info "未找到 schema.sql — 运行完整迁移"
        php artisan migrate --force --no-interaction 2>&1 | tail -5
        ok "数据库迁移完成"
    fi

    # Detect fresh vs existing installation
    TABLE_COUNT_CHECK=$(php "${INSTALL_DIR}/artisan" tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('users') && \App\User::count() > 0 ? 'has_data' : 'empty';" 2>/dev/null || echo "empty")

    if [[ "$TABLE_COUNT_CHECK" == *"has_data"* ]]; then
        info "检测到已有数据，跳过数据填充"
    else
        info "首次安装，初始化系统数据..."
        (cd "$INSTALL_DIR" && sudo -u "$WEB_USER" php artisan db:seed --force --no-interaction 2>&1) || \
        (cd "$INSTALL_DIR" && php artisan db:seed --force --no-interaction 2>&1)
        ok "系统数据初始化完成"
    fi
fi

# ══════════════════════════════════════════════════════════════════════════════
#  Step 7: Storage link + 缓存优化
# ══════════════════════════════════════════════════════════════════════════════
step "Storage 链接与缓存优化"

cd "$INSTALL_DIR"

# Storage link
php artisan storage:link --force --no-interaction 2>/dev/null || true
ok "Storage 软链接已创建"

# Clear stale caches
php artisan config:clear --no-interaction  2>/dev/null || true
php artisan route:clear --no-interaction   2>/dev/null || true
php artisan view:clear --no-interaction    2>/dev/null || true

# Rebuild caches
php artisan config:cache --no-interaction  2>/dev/null && ok "配置缓存" || warn "配置缓存失败"
php artisan route:cache --no-interaction   2>/dev/null && ok "路由缓存" || warn "路由缓存失败"
php artisan view:cache --no-interaction    2>/dev/null && ok "视图缓存" || warn "视图缓存失败"

# ══════════════════════════════════════════════════════════════════════════════
#  Step 8: OCR Python 环境
# ══════════════════════════════════════════════════════════════════════════════
step "安装 OCR Python 环境"

if $SKIP_OCR; then
    info "已跳过 (--skip-ocr)"
elif ! $PYTHON_AVAILABLE; then
    warn "Python 3.8+ 不可用，跳过 OCR 安装"
else
    VENV_DIR="$INSTALL_DIR/scripts/venv"
    REQ_FILE="$INSTALL_DIR/scripts/requirements.txt"
    WHEELS_DIR="$INSTALL_DIR/ocr-wheels"

    # Create venv (idempotent)
    if [[ ! -d "$VENV_DIR" ]]; then
        info "创建 Python 虚拟环境..."
        $PYTHON_CMD -m venv "$VENV_DIR"
        ok "虚拟环境已创建: $VENV_DIR"
    else
        info "虚拟环境已存在"
    fi

    # Upgrade pip
    "$VENV_DIR/bin/pip" install --upgrade pip -q 2>/dev/null || true

    # Install deps: prefer local wheels (offline), else PyPI
    if [[ -d "$WHEELS_DIR" ]] && ls "$WHEELS_DIR"/*.whl &>/dev/null 2>&1; then
        info "从本地 wheels 安装 OCR 依赖 (离线模式)..."
        "$VENV_DIR/bin/pip" install --no-index --find-links="$WHEELS_DIR" -r "$REQ_FILE" 2>&1 | tail -5
    else
        info "从 PyPI 在线安装 OCR 依赖..."
        "$VENV_DIR/bin/pip" install -r "$REQ_FILE" 2>&1 | tail -5
    fi

    # Verify
    VERIFY_OK=true
    "$VENV_DIR/bin/python3" -c "from paddleocr import PaddleOCR" 2>/dev/null && ok "PaddleOCR" || { warn "PaddleOCR 验证失败"; VERIFY_OK=false; }
    "$VENV_DIR/bin/python3" -c "from flask import Flask"         2>/dev/null && ok "Flask"      || { warn "Flask 验证失败"; VERIFY_OK=false; }
    "$VENV_DIR/bin/python3" -c "from PIL import Image"           2>/dev/null && ok "Pillow"     || { warn "Pillow 验证失败"; VERIFY_OK=false; }


    if $VERIFY_OK; then
        ok "OCR 环境安装完成"
    else
        warn "OCR 部分组件安装异常，可稍后运行 scripts/setup_ocr_venv.sh 修复"
    fi

    # Re-cache config after .env changes (OCR path)
    info "刷新配置缓存..."
    sudo -u "$WEB_USER" php "${INSTALL_DIR}/artisan" config:cache --no-interaction 2>/dev/null || \
        php "${INSTALL_DIR}/artisan" config:cache --no-interaction 2>/dev/null || true
fi

# ══════════════════════════════════════════════════════════════════════════════
#  Step 9: Web 服务器配置
# ══════════════════════════════════════════════════════════════════════════════
step "生成 Web 服务器配置"

NGINX_CONF_PATH=""
APACHE_CONF_PATH=""

# Derive server_name from APP_URL
SERVER_NAME=$(echo "$APP_URL" | sed -E 's|https?://||; s|/.*||; s|:[0-9]+$||')
[[ -z "$SERVER_NAME" ]] && SERVER_NAME="localhost"

if [[ "$OS_TYPE" == "macos" ]]; then
    NGINX_ACCESS_LOG="/usr/local/var/log/nginx/dental-access.log"
    NGINX_ERROR_LOG="/usr/local/var/log/nginx/dental-error.log"
else
    NGINX_ACCESS_LOG="/var/log/nginx/dental-access.log"
    NGINX_ERROR_LOG="/var/log/nginx/dental-error.log"
fi

if [[ "$WEB_SERVER" == "nginx" ]] || command -v nginx &>/dev/null; then
    # Generate Nginx config
    NGINX_CONF_FILE="dental-clinic.conf"

    if [[ "$OS_TYPE" == "linux" ]]; then
        if [[ -d /etc/nginx/sites-available ]]; then
            NGINX_CONF_PATH="/etc/nginx/sites-available/${NGINX_CONF_FILE}"
            NGINX_ENABLED_PATH="/etc/nginx/sites-enabled/${NGINX_CONF_FILE}"
        elif [[ -d /etc/nginx/conf.d ]]; then
            NGINX_CONF_PATH="/etc/nginx/conf.d/${NGINX_CONF_FILE}"
        else
            NGINX_CONF_PATH="/etc/nginx/${NGINX_CONF_FILE}"
        fi
    elif [[ "$OS_TYPE" == "macos" ]]; then
        BREW_PREFIX="$(brew --prefix 2>/dev/null || echo '/usr/local')"
        NGINX_CONF_PATH="${BREW_PREFIX}/etc/nginx/servers/${NGINX_CONF_FILE}"
        mkdir -p "${BREW_PREFIX}/etc/nginx/servers" 2>/dev/null || true
    fi

    cat > "$NGINX_CONF_PATH" <<NGINXEOF
server {
    listen ${LISTEN_PORT};
    listen [::]:${LISTEN_PORT};
    server_name ${SERVER_NAME};

    root ${INSTALL_DIR}/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 20M;

    # Logging
    access_log ${NGINX_ACCESS_LOG};
    error_log  ${NGINX_ERROR_LOG};

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known) {
        deny all;
    }
}
NGINXEOF

    ok "Nginx 配置已生成: $NGINX_CONF_PATH"

    # Enable site (Debian/Ubuntu style)
    if [[ -n "${NGINX_ENABLED_PATH:-}" ]] && [[ ! -L "$NGINX_ENABLED_PATH" ]]; then
        ln -sf "$NGINX_CONF_PATH" "$NGINX_ENABLED_PATH" 2>/dev/null || true
        ok "Nginx site 已启用"
    fi

    # Detect PHP-FPM socket path and patch config
    PHP_FPM_SOCK=""
    for sock_candidate in \
        /run/php/php-fpm.sock \
        /run/php/php8.2-fpm.sock \
        /run/php/php8.3-fpm.sock \
        /var/run/php-fpm/www.sock \
        /var/run/php/php-fpm.sock \
        /tmp/php-fpm.sock; do
        if [[ -S "$sock_candidate" ]]; then
            PHP_FPM_SOCK="$sock_candidate"
            break
        fi
    done

    if [[ -n "$PHP_FPM_SOCK" ]] && [[ "$PHP_FPM_SOCK" != "/run/php/php-fpm.sock" ]]; then
        "${SED_INPLACE[@]}" "s|unix:/run/php/php-fpm.sock|unix:${PHP_FPM_SOCK}|g" "$NGINX_CONF_PATH"
        info "PHP-FPM socket 路径已调整为: $PHP_FPM_SOCK"
    elif [[ -z "$PHP_FPM_SOCK" ]]; then
        warn "未检测到 PHP-FPM socket，请手动编辑 fastcgi_pass 行: $NGINX_CONF_PATH"
    fi

    # Validate nginx config
    if command -v nginx &>/dev/null; then
        if nginx -t 2>/dev/null; then
            ok "Nginx 配置语法验证通过"
        else
            warn "Nginx 配置语法验证失败，请手动检查"
        fi
    fi

    echo ""
    info "启用 Nginx 站点:"
    echo -e "    ${CYAN}sudo nginx -t && sudo systemctl reload nginx${NC}"

elif [[ "$WEB_SERVER" == "apache" ]] || command -v apache2 &>/dev/null || command -v httpd &>/dev/null; then
    # Generate Apache config
    if [[ -d /etc/apache2/sites-available ]]; then
        APACHE_CONF_PATH="/etc/apache2/sites-available/dental-clinic.conf"
    elif [[ -d /etc/httpd/conf.d ]]; then
        APACHE_CONF_PATH="/etc/httpd/conf.d/dental-clinic.conf"
    else
        APACHE_CONF_PATH="/etc/apache2/dental-clinic.conf"
    fi

    cat > "$APACHE_CONF_PATH" <<APACHEEOF
<VirtualHost *:${LISTEN_PORT}>
    ServerName ${SERVER_NAME}
    DocumentRoot ${INSTALL_DIR}/public

    <Directory ${INSTALL_DIR}/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    <Directory ${INSTALL_DIR}>
        Options -Indexes
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/dental-error.log
    CustomLog \${APACHE_LOG_DIR}/dental-access.log combined
</VirtualHost>
APACHEEOF

    ok "Apache 配置已生成: $APACHE_CONF_PATH"

    # Enable site on Debian/Ubuntu
    if command -v a2ensite &>/dev/null; then
        a2ensite dental-clinic.conf 2>/dev/null || true
        a2enmod rewrite 2>/dev/null || true
        ok "Apache site 已启用, mod_rewrite 已启用"
    fi

    echo ""
    info "启用 Apache 站点:"
    if command -v apache2 &>/dev/null; then
        echo -e "    ${CYAN}sudo apachectl configtest && sudo systemctl reload apache2${NC}"
    else
        echo -e "    ${CYAN}sudo httpd -t && sudo systemctl reload httpd${NC}"
    fi
else
    warn "未检测到 Nginx 或 Apache，跳过 Web 服务器配置生成"
    echo ""
    info "请手动配置 Web 服务器，将 document root 指向:"
    echo -e "    ${CYAN}${INSTALL_DIR}/public${NC}"
fi

# ══════════════════════════════════════════════════════════════════════════════
#  Step 10: Systemd 服务 (Linux only)
# ══════════════════════════════════════════════════════════════════════════════
step "创建 systemd 服务"

if $NO_SERVICE; then
    info "已跳过 (--no-service)"
elif [[ "$INIT_SYSTEM" != "systemd" ]]; then
    if [[ "$OS_TYPE" == "macos" ]]; then
        info "macOS 不使用 systemd，跳过服务创建"
        echo ""
        info "手动启动 Queue Worker:"
        echo -e "    ${CYAN}cd ${INSTALL_DIR} && php artisan queue:work database --queue=backups,default --sleep=3 --tries=3 &${NC}"
        if ! $SKIP_OCR && $PYTHON_AVAILABLE; then
            info "手动启动 OCR 服务:"
            echo -e "    ${CYAN}${INSTALL_DIR}/scripts/venv/bin/python3 ${INSTALL_DIR}/scripts/ocr_server.py &${NC}"
        fi
    else
        warn "未检测到 systemd，跳过服务创建"
    fi
else
    # ── dental-queue.service ─────────────────────────────────────────────
    cat > /etc/systemd/system/dental-queue.service <<QUEUEEOF
[Unit]
Description=Dental Clinic - Laravel Queue Worker
After=network.target mysql.service

[Service]
User=${WEB_USER}
Group=${WEB_GROUP}
Restart=always
RestartSec=5
WorkingDirectory=${INSTALL_DIR}
ExecStart=/usr/bin/php ${INSTALL_DIR}/artisan queue:work database --queue=backups,default --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/var/log/dental-queue.log
StandardError=append:/var/log/dental-queue.log

[Install]
WantedBy=multi-user.target
QUEUEEOF

    # Use the actual php path if not at /usr/bin/php
    PHP_BIN=$(command -v php)
    if [[ "$PHP_BIN" != "/usr/bin/php" ]]; then
        "${SED_INPLACE[@]}" "s|/usr/bin/php|${PHP_BIN}|g" /etc/systemd/system/dental-queue.service
    fi

    ok "dental-queue.service 已创建"

    # ── dental-ocr.service ───────────────────────────────────────────────
    if ! $SKIP_OCR && $PYTHON_AVAILABLE; then
        cat > /etc/systemd/system/dental-ocr.service <<OCREOF
[Unit]
Description=Dental Clinic - OCR Server (PaddleOCR)
After=network.target

[Service]
User=${WEB_USER}
Group=${WEB_GROUP}
Restart=always
RestartSec=10
WorkingDirectory=${INSTALL_DIR}/scripts
ExecStart=${INSTALL_DIR}/scripts/venv/bin/python3 ${INSTALL_DIR}/scripts/ocr_server.py
StandardOutput=append:/var/log/dental-ocr.log
StandardError=append:/var/log/dental-ocr.log

[Install]
WantedBy=multi-user.target
OCREOF
        ok "dental-ocr.service 已创建"
    fi

    # Reload and enable
    systemctl daemon-reload
    systemctl enable dental-queue.service 2>/dev/null || true
    systemctl start dental-queue.service 2>/dev/null || true
    ok "dental-queue.service 已启用并启动"

    if [[ -f /etc/systemd/system/dental-ocr.service ]]; then
        systemctl enable dental-ocr.service 2>/dev/null || true
        systemctl start dental-ocr.service 2>/dev/null || true
        ok "dental-ocr.service 已启用并启动"
    fi
fi

# ── Log rotation ──────────────────────────────────────────
if [[ "$OS_TYPE" == "linux" ]] && [[ -d /etc/logrotate.d ]]; then
    info "配置日志轮转..."
    cat > /etc/logrotate.d/dental-clinic <<LOGROTATE_EOF
${INSTALL_DIR}/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 ${WEB_USER} ${WEB_GROUP}
    sharedscripts
    postrotate
        # Signal PHP-FPM to reopen logs
        if command -v systemctl &>/dev/null; then
            systemctl reload php8.2-fpm 2>/dev/null || \
            systemctl reload php8.3-fpm 2>/dev/null || \
            systemctl reload php-fpm 2>/dev/null || true
        fi
    endscript
}
LOGROTATE_EOF
    ok "日志轮转已配置 (/etc/logrotate.d/dental-clinic)"
elif [[ "$OS_TYPE" == "macos" ]]; then
    info "macOS 提示: 请手动配置 newsyslog 或使用 Laravel daily 日志通道"
fi

# ══════════════════════════════════════════════════════════════════════════════
#  Step 11: Cron Job (Laravel Scheduler)
# ══════════════════════════════════════════════════════════════════════════════
step "配置定时任务 (cron)"

if $NO_SERVICE; then
    info "已跳过 (--no-service)"
else
    PHP_BIN=$(command -v php)
    CRON_LINE="* * * * * ${PHP_BIN} ${INSTALL_DIR}/artisan schedule:run >> /dev/null 2>&1"

    # Add to crontab for web user (idempotent)
    EXISTING_CRON=""
    EXISTING_CRON=$(crontab -u "$WEB_USER" -l 2>/dev/null || true)

    if echo "$EXISTING_CRON" | grep -qF "artisan schedule:run"; then
        info "Laravel scheduler cron 已存在，跳过"
    else
        (echo "$EXISTING_CRON"; echo "$CRON_LINE") | crontab -u "$WEB_USER" - 2>/dev/null || {
            # Fallback: add to root crontab
            EXISTING_ROOT_CRON=$(crontab -l 2>/dev/null || true)
            if ! echo "$EXISTING_ROOT_CRON" | grep -qF "artisan schedule:run"; then
                (echo "$EXISTING_ROOT_CRON"; echo "$CRON_LINE") | crontab - 2>/dev/null || true
                info "Cron 已添加到 root 用户 (无法写入 ${WEB_USER} 的 crontab)"
            fi
        }
        ok "Laravel scheduler cron 已配置"
    fi
    info "Cron: ${CRON_LINE}"
fi

# ══════════════════════════════════════════════════════════════════════════════
#  Step 12: 安装完成 — 输出摘要
# ══════════════════════════════════════════════════════════════════════════════
INSTALL_SUCCEEDED=true

step "安装完成"

echo ""
echo -e "${GREEN}================================================================${NC}"
echo -e "${GREEN}  安装成功！${NC}"
echo -e "${GREEN}================================================================${NC}"
echo ""
echo -e "  ${BOLD}系统信息${NC}"
echo -e "  ────────────────────────────────────────────"
echo -e "  版本:      ${CYAN}v${VERSION}${NC}"
echo -e "  安装目录:  ${CYAN}${INSTALL_DIR}${NC}"
echo -e "  访问地址:  ${CYAN}${APP_URL}${NC}"
echo ""
echo -e "  ${BOLD}数据库${NC}"
echo -e "  ────────────────────────────────────────────"
echo -e "  主机:      ${DB_HOST}:${DB_PORT}"
echo -e "  数据库:    ${DB_NAME}"
echo -e "  用户:      ${DB_USER}"
if $DB_PASS_GENERATED; then
echo -e "  密码:      ${YELLOW}${DB_PASS}${NC}  ${RED}<-- 自动生成，请妥善保存${NC}"
else
echo -e "  密码:      (已指定)"
fi
echo ""
echo -e "  ${BOLD}默认管理员账号${NC}"
echo -e "  ────────────────────────────────────────────"
echo -e "  用户名:    ${YELLOW}admin${NC}  (或 admin@example.com)"
echo -e "  密码:      ${YELLOW}password${NC}"
echo -e "  ${RED}** 首次登录后请立即修改密码！ **${NC}"
echo ""
echo -e "  ${BOLD}服务状态${NC}"
echo -e "  ────────────────────────────────────────────"

# Web server status
if [[ -n "$NGINX_CONF_PATH" ]]; then
    echo -e "  Nginx 配置:     ${CYAN}${NGINX_CONF_PATH}${NC}"
elif [[ -n "$APACHE_CONF_PATH" ]]; then
    echo -e "  Apache 配置:    ${CYAN}${APACHE_CONF_PATH}${NC}"
fi

# Service status
if [[ "$INIT_SYSTEM" == "systemd" ]] && ! $NO_SERVICE; then
    QUEUE_STATUS=$(systemctl is-active dental-queue.service 2>/dev/null || echo "unknown")
    echo -e "  Queue Worker:   ${QUEUE_STATUS}"
    if [[ -f /etc/systemd/system/dental-ocr.service ]]; then
        OCR_STATUS=$(systemctl is-active dental-ocr.service 2>/dev/null || echo "unknown")
        echo -e "  OCR Server:     ${OCR_STATUS}"
    fi
fi

# Cron status
if ! $NO_SERVICE; then
    echo -e "  Scheduler:      cron (* * * * *)"
fi

echo ""
echo -e "  ${BOLD}下一步操作${NC}"
echo -e "  ────────────────────────────────────────────"

NEXT_STEP=1

# Web server reload hint
if [[ "$WEB_SERVER" == "nginx" ]]; then
    echo -e "  ${NEXT_STEP}. 检查并重载 Nginx:"
    echo -e "     ${CYAN}sudo nginx -t && sudo systemctl reload nginx${NC}"
    NEXT_STEP=$((NEXT_STEP + 1))
elif [[ "$WEB_SERVER" == "apache" ]]; then
    echo -e "  ${NEXT_STEP}. 检查并重载 Apache:"
    if command -v apache2 &>/dev/null; then
        echo -e "     ${CYAN}sudo apachectl configtest && sudo systemctl reload apache2${NC}"
    else
        echo -e "     ${CYAN}sudo httpd -t && sudo systemctl reload httpd${NC}"
    fi
    NEXT_STEP=$((NEXT_STEP + 1))
elif [[ -z "$WEB_SERVER" ]]; then
    echo -e "  ${NEXT_STEP}. 安装并配置 Web 服务器 (Nginx 推荐), document root:"
    echo -e "     ${CYAN}${INSTALL_DIR}/public${NC}"
    NEXT_STEP=$((NEXT_STEP + 1))
fi

# Quick test hint
echo -e "  ${NEXT_STEP}. 快速测试 (内置服务器):"
echo -e "     ${CYAN}cd ${INSTALL_DIR} && php artisan serve --host=0.0.0.0 --port=8000${NC}"
NEXT_STEP=$((NEXT_STEP + 1))

# Password change reminder
echo -e "  ${NEXT_STEP}. 登录系统并修改默认管理员密码"
NEXT_STEP=$((NEXT_STEP + 1))

# DNS/Firewall hint
if [[ "$LISTEN_PORT" != "80" ]] && [[ "$LISTEN_PORT" != "443" ]]; then
    echo -e "  ${NEXT_STEP}. 确保防火墙允许端口 ${LISTEN_PORT}:"
    echo -e "     ${CYAN}sudo firewall-cmd --add-port=${LISTEN_PORT}/tcp --permanent && sudo firewall-cmd --reload${NC}"
    echo -e "     或: ${CYAN}sudo ufw allow ${LISTEN_PORT}/tcp${NC}"
    NEXT_STEP=$((NEXT_STEP + 1))
fi

echo ""
echo -e "${BOLD}================================================================${NC}"
echo ""
