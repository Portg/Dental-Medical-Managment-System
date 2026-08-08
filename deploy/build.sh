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
  --init-db-from-local         用本地库的结构+数据做初始数据库（默认只导结构）
                               注意：本地库全部数据都会进包，含账号/患者/日志
  --use-existing-mysql        Windows ZIP 复用目标机 127.0.0.1:3306 MySQL
                               安装时隐藏输入管理员密码，不管理 mysqld 生命周期
  --bundled-mysql-port <port> Windows ZIP 内置 MySQL 使用独立端口（如 3307）
                               适用于目标机已有 MySQL，保留原实例并避免版本冲突
  --runtime <laragon|xampp>    运行时形态（默认 laragon）
                               laragon: PHP-NTS + MySQL 5.7 + Nginx + php-cgi（自组装）
                               xampp:   Apache + mod_php + MariaDB（官方 portable 包）
                                        Web 层不再需要单独维护 php-cgi 进程
  --keep-dist                  保留 deploy/dist/ 不删除（编译 Inno .exe 安装包必须加）
  --version <X.Y.Z>            覆盖 VERSION 文件中的版本号
  --laragon-url <url>          Windows: 指定 laragon-wamp.exe 下载地址（.exe 直链）
  -h, --help                   显示此帮助信息

环境变量（均可选，有默认值）:
  PYTHON_DOWNLOAD_URL          Python Windows x64 安装器下载地址（OCR 用）

示例:
  ./deploy/build.sh --target win                          # Windows 全量 ZIP 安装包
  ./deploy/build.sh --target win --keep-dist              # 保留 dist/，之后可编译 build-installer.iss
  ./deploy/build.sh --target linux --upgrade              # Linux 升级包
  ./deploy/build.sh --target mac --skip-obfuscate         # macOS 不混淆
  ./deploy/build.sh --target win --version 2.0.0          # 指定版本号
  ./deploy/build.sh --target win --bundled-mysql-port 3307 # 内置 MySQL 与现有 3306 隔离
USAGE
    exit 0
}

# ── 参数解析 ───────────────────────────────────────────────────────────
TARGET=""
UPGRADE=false
SKIP_OBFUSCATE=false
SKIP_OCR=false
INIT_DB_FROM_LOCAL=false
USE_EXISTING_MYSQL=false
BUNDLED_MYSQL_PORT=""
KEEP_DIST=false
VERSION_OVERRIDE=""
LARAGON_INSTALLER_EXE=""
LARAGON_URL_OVERRIDE=""
# 运行时形态：
#   laragon（默认）— 下载 PHP-NTS / MySQL 5.7 / Nginx / composer 自行组装，
#                    Web 层是 Nginx + php-cgi（需额外维护一个 9000 端口的进程）
#   xampp          — 下载 XAMPP portable 解压即用，Web 层是 Apache + mod_php
#                    （PHP 跑在 Apache 进程内，没有 php-cgi 要管），数据库是 MariaDB
RUNTIME_FLAVOR="laragon"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --runtime)
            RUNTIME_FLAVOR="${2:-}"
            case "$RUNTIME_FLAVOR" in
                laragon|xampp|xampp-installer) ;;
                *) fatal "--runtime 只支持 laragon / xampp / xampp-installer（收到: ${RUNTIME_FLAVOR}）" ;;
            esac
            shift 2
            ;;
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
        --init-db-from-local)
            INIT_DB_FROM_LOCAL=true
            shift
            ;;
        --use-existing-mysql)
            USE_EXISTING_MYSQL=true
            shift
            ;;
        --bundled-mysql-port)
            BUNDLED_MYSQL_PORT="${2:-}"
            [[ -z "$BUNDLED_MYSQL_PORT" ]] && fatal "--bundled-mysql-port 需要指定端口号"
            shift 2
            ;;
        --keep-dist)
            KEEP_DIST=true
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
if [[ "$USE_EXISTING_MYSQL" == true ]] && [[ "$TARGET" != "win" || "$UPGRADE" == true ]]; then
    fatal "--use-existing-mysql 只支持 Windows 全量 ZIP 安装包"
fi
if [[ -n "$BUNDLED_MYSQL_PORT" ]]; then
    [[ "$TARGET" != "win" || "$UPGRADE" == true ]] && fatal "--bundled-mysql-port 只支持 Windows 全量 ZIP 安装包"
    [[ "$USE_EXISTING_MYSQL" == true ]] && fatal "--bundled-mysql-port 不能与 --use-existing-mysql 同时使用"
    [[ ! "$BUNDLED_MYSQL_PORT" =~ ^[0-9]+$ ]] && fatal "MySQL 端口必须是数字: $BUNDLED_MYSQL_PORT"
    BUNDLED_MYSQL_PORT_NUMBER=$((10#$BUNDLED_MYSQL_PORT))
    (( BUNDLED_MYSQL_PORT_NUMBER < 1 || BUNDLED_MYSQL_PORT_NUMBER > 65535 )) && fatal "MySQL 端口超出范围: $BUNDLED_MYSQL_PORT"
    BUNDLED_MYSQL_PORT="$BUNDLED_MYSQL_PORT_NUMBER"
fi

# ── SHA256 指纹表 ────────────────────────────────────────────────
#
#  为什么需要：下载全部走 HTTPS 且只检查「文件非空 / zip 能解开」，
#  这挡不住上游把同一个 URL 换成新版本，也挡不住缓存目录被改动。
#  而 .cache/ 是长期复用的——一旦混进错误的产物，后续每次构建都静默沿用。
#
#  只对**版本固定的 URL**生效。用 *_DOWNLOAD_URL 覆盖地址时自动跳过
#  （换镜像换版本本来就会换指纹），仅打印警告。
#  更新版本时同步更新此表：shasum -a 256 <文件>
# ─────────────────────────────────────────────────────────────────
expected_sha256() {
    case "$1" in
        # php-8.2.33-nts-Win32-vs16-x64.zip（与 windows.php.net/downloads/releases/sha256sum.txt 一致）
        php82)        echo "d0bd189522fa50255ee94ed4b340ed4330f5ae33a90a74205275b0f0b221d388" ;;
        # mysql-5.7.44-winx64.zip
        mysql57)      echo "aed661fe8120254a1dc30f5a4d5de346681922f4847cf025e2d4084eca78e70e" ;;
        # nginx-1.24.0.zip
        nginx)        echo "69a36bfd2a61d7a736fafd392708bd0fb6cf15d741f8028fe6d8bb5ebd670eb9" ;;
        # laragon 8.6.1 源码 zip
        laragon-core) echo "d66ae3c6f1949e39ee0cfb13cb79aa0d77b3542863291f707e4d4a96452ad4ae" ;;
        # vc_redist.x64.exe —— 当前锁定的是 aka.ms/vs/17 在 2026-08-01 取到的那一版。
        # ⚠ 待确认：该版本属于 VS2022 系列，Win7 SP1 兼容性需逐版本核实；
        #   若确认不兼容，应改用 VS2019 14.29 系列并同步更新此指纹与 URL。
        vcredist)     echo "cc0ff0eb1dc3f5188ae6300faef32bf5beeba4bdd6e8e445a9184072096b713b" ;;
        *)            echo "" ;;
    esac
}

sha256_of() {
    if command -v sha256sum &>/dev/null; then
        sha256sum "$1" 2>/dev/null | awk '{print $1}'
    elif command -v shasum &>/dev/null; then
        shasum -a 256 "$1" 2>/dev/null | awk '{print $1}'
    else
        echo ""
    fi
}

# 用法: verify_sha256 <文件> <指纹表 key> <地址是否被覆盖:true|false>
# 返回: 0=通过或无需校验, 1=不匹配
verify_sha256() {
    local file="$1" key="$2" overridden="${3:-false}"
    local expected actual

    expected="$(expected_sha256 "$key")"
    [[ -z "$expected" ]] && return 0

    if [[ "$overridden" == true ]]; then
        warn "$key: 下载地址被环境变量覆盖，跳过 SHA256 校验"
        return 0
    fi

    actual="$(sha256_of "$file")"
    if [[ -z "$actual" ]]; then
        warn "$key: 未找到 sha256sum/shasum，跳过 SHA256 校验"
        return 0
    fi

    if [[ "$actual" != "$expected" ]]; then
        error "$key: SHA256 不匹配 —— 产物与锁定版本不符"
        error "  期望: $expected"
        error "  实际: $actual"
        error "  文件: $file"
        return 1
    fi

    info "$key: SHA256 校验通过"
    return 0
}

# ── 下载并解压一个 zip 包的辅助函数 ──────────────────────────────
# 用法: download_and_extract <url> <cache_zip_path> <extract_dir> <component_name> [sha_key] [url_overridden]
# 返回: 0=成功, 1=失败
download_and_extract() {
    local url="$1" cache_zip="$2" extract_dir="$3" name="$4"
    local sha_key="${5:-}" overridden="${6:-false}"

    # ── 缓存指纹校验（必须早于「已有解压结果 → 跳过」）
    # 否则一个指纹不对的 zip 只要解压过一次，后续构建就再也不会被检查到。
    if [[ -n "$sha_key" ]] && [[ -n "$(expected_sha256 "$sha_key")" ]] && [[ "$overridden" != true ]]; then
        if [[ -f "$cache_zip" ]]; then
            if ! verify_sha256 "$cache_zip" "$sha_key" "$overridden"; then
                warn "$name: 缓存指纹不符，清除缓存并重新下载"
                rm -f "$cache_zip"
                rm -rf "$extract_dir"
            fi
        elif [[ -d "$extract_dir" ]]; then
            # 有解压结果却没有源 zip：无从校验，不予复用
            warn "$name: 缺少可校验的源 zip，丢弃无法验证的解压缓存"
            rm -rf "$extract_dir"
        fi
    fi

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

        # 刚下载的产物也要对指纹 —— HTTPS 只保证传输，不保证对端给的是同一个版本
        if [[ -n "$sha_key" ]] && ! verify_sha256 "$cache_zip" "$sha_key" "$overridden"; then
            rm -f "$cache_zip"
            error "$name: 下载产物指纹不符，已删除"
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

# ════════════════════════════════════════════════════════════════════════
#  组装 Windows 7 运行环境（本分支默认路径，替代 laragon-wamp.exe）
#
#  为什么不用 laragon-wamp.exe：它的安装器要求 Windows 10。这里改为下载
#  laragon-core（纯文件，无安装器）再自行填入运行时，产出目录结构与 Laragon
#  完全一致，install-win.ps1 无需改动即可识别。
#
#  版本锁定原因（改动前务必逐条确认 Win7 兼容性）：
#    PHP   8.2.33 VS16 x64 NTS — PHP 8.3 起最低要求 Windows 8/Server 2012，
#                                8.2 仍支持 Win7/2008 R2，故 8.2 是天花板。
#                                （见 php.net install.windows.manual）
#    MySQL 5.7.44 winx64       — MySQL 8.0 的支持平台仅列到 Server 2016/Win10
#    Nginx 1.24.0              — 保守选择，Win7 上验证充分
#    Python 3.8.10（OCR）      — 3.9 起最低要求 Windows 8.1
#
#  另随包提供 VC++ 2015-2022 x64 运行库，它是 PHP VS16 构建的依赖。
#  install-win.ps1 保持 PowerShell 2.0 语法兼容，不再要求 WMF/.NET 更新。
# ════════════════════════════════════════════════════════════════════════
# ── Win7 前置组件下载（VC++ 运行库）────────────────────────────────────
#
# 定义必须在顶层：它原本嵌在 `if RUNTIME_FLAVOR == laragon` 块内部，
# --runtime xampp 时那个块不执行，函数根本不会被定义，调用处直接
# command not found（外面套着 `if !` 所以表现为「前置组件缺失」，
# 误导性极强 —— 实测踩过一次）。
download_win7_prereqs() {
    PREREQ_OK=true

    # ── VC++ 2015-2022 x64 运行库
    # PHP 的 VS16 构建依赖它；Win7 常见的「php.exe 双击无反应/缺少 VCRUNTIME140.dll」
    # 就是缺这个。随包提供，避免要求现场联网。
    #
    # ⚠ aka.ms/vs/17/release 是**移动地址**，永远指向最新的 VS2022 运行库。
    #   后果有二：构建不可复现（同一个 tag 两次构建装进去的运行库不同）；
    #   而且 VS2022 系列是否仍支持 Win7 SP1 需要逐版本确认，跟着它漂移等于
    #   把「目标机能不能跑起来」交给上游随时改。
    #   因此这里固定 SHA-256：指纹对不上就失败，逼人显式确认换版本。
    #   要换版本：改 VCREDIST_DOWNLOAD_URL，跑一次拿到实际指纹，
    #   再更新 expected_sha256() 里的 vcredist 条目。
    VCREDIST_URL="${VCREDIST_DOWNLOAD_URL:-https://aka.ms/vs/17/release/vc_redist.x64.exe}"
    VCREDIST_OVERRIDDEN=$([[ -n "${VCREDIST_DOWNLOAD_URL:-}" ]] && echo true || echo false)
    VCREDIST_CACHE="$CACHE_DIR/vc_redist.x64.exe"

    if [[ -s "$VCREDIST_CACHE" ]] && ! verify_sha256 "$VCREDIST_CACHE" "vcredist" "$VCREDIST_OVERRIDDEN"; then
        warn "VC++ 运行库: 缓存指纹不符，重新下载"
        rm -f "$VCREDIST_CACHE"
    fi

    if [[ ! -s "$VCREDIST_CACHE" ]]; then
        if curl -fsSL --retry 2 -o "$VCREDIST_CACHE" "$VCREDIST_URL" \
           && [[ -s "$VCREDIST_CACHE" ]] \
           && verify_sha256 "$VCREDIST_CACHE" "vcredist" "$VCREDIST_OVERRIDDEN"; then
            info "VC++ 2015-2022 x64 运行库已下载"
        else
            rm -f "$VCREDIST_CACHE"
            error "VC++ 运行库下载失败或指纹不符 —— PHP 在目标机上将无法启动"; PREREQ_OK=false
        fi
    else
        info "VC++ 运行库: 使用缓存（指纹已校验）"
    fi

    [[ "$PREREQ_OK" == true ]]
}

WIN7_RUNTIME_DIR=""
# XAMPP 运行时的解压目录（--runtime xampp 时由 prepare-xampp-runtime.sh 产出）
XAMPP_RUNTIME_SRC=""
# --runtime xampp-installer 时指向缓存里的官方安装器（见下方 prepare 步骤）
XAMPP_INSTALLER_SRC=""

# CACHE_DIR 必须在两条运行时分支之外定义。
# 它原本只在 Laragon 分支内赋值，--runtime xampp 时那段不执行，
# 后面打包处引用 $CACHE_DIR/vc_redist.x64.exe 会在 set -u 下直接 unbound。
CACHE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.cache"

# XAMPP 运行时的准备放在 download_win7_prereqs() 定义之后（见本段末尾），
# 因为它要调用那个函数 —— bash 是顺序执行的，在定义之前调用会 command not found。

# --upgrade 明确排除：升级包只带代码和迁移，目标机上的 PHP/MySQL/Nginx 早已装好。
# 少了这个判断，升级包会连约 1.3GB 运行时一起下载、组装并打包。
# RUNTIME_FLAVOR == xampp 时同样跳过：那条路径不需要自组装 Laragon。
if [[ "$TARGET" == "win" ]] && [[ "$UPGRADE" == false ]] && [[ "$RUNTIME_FLAVOR" == "laragon" ]] && [[ -z "${LARAGON_INSTALLER_EXE:-}" ]]; then
    CACHE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.cache"
    ASSEMBLED_DIR="$CACHE_DIR/win7-runtime"

    PHP_URL="${PHP_DOWNLOAD_URL:-https://windows.php.net/downloads/releases/php-8.2.33-nts-Win32-vs16-x64.zip}"
    MYSQL_URL="${MYSQL_DOWNLOAD_URL:-https://dev.mysql.com/get/Downloads/MySQL-5.7/mysql-5.7.44-winx64.zip}"
    NGINX_URL="${NGINX_DOWNLOAD_URL:-https://nginx.org/download/nginx-1.24.0.zip}"
    LARAGON_CORE_URL="${LARAGON_DOWNLOAD_URL:-https://github.com/leokhoa/laragon/archive/refs/tags/8.6.1.zip}"
    COMPOSER_URL="${COMPOSER_DOWNLOAD_URL:-https://getcomposer.org/download/latest-stable/composer.phar}"

    # 地址是否被环境变量覆盖 —— 覆盖了就不能再拿锁定版本的指纹去卡
    PHP_URL_OVERRIDDEN=$([[ -n "${PHP_DOWNLOAD_URL:-}" ]] && echo true || echo false)
    MYSQL_URL_OVERRIDDEN=$([[ -n "${MYSQL_DOWNLOAD_URL:-}" ]] && echo true || echo false)
    NGINX_URL_OVERRIDDEN=$([[ -n "${NGINX_DOWNLOAD_URL:-}" ]] && echo true || echo false)
    LARAGON_URL_OVERRIDDEN=$([[ -n "${LARAGON_DOWNLOAD_URL:-}" ]] && echo true || echo false)

    # 构建机 PHP 必须是 8.2.x —— vendor/ 由本机的 composer 产出，
    # 在 8.3+ 上构建可能拉进要求 8.3 的包，装到 Win7 目标机上就会崩。
    BUILD_PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "")"
    if [[ "$BUILD_PHP_VER" != "8.2" ]]; then
        fatal "构建机 PHP 版本为 ${BUILD_PHP_VER:-未知}，Win7 包必须在 PHP 8.2.x 上构建（目标机运行时为 8.2，8.3+ 不支持 Win7）"
    fi
    info "构建机 PHP: $(php -r 'echo PHP_VERSION;') ✓"

    # ── 缓存清单：记录这份组装结果是用哪些地址产出的 ──────────────
    # 只查「有没有 php.exe / mysqld.exe」是不够的：本分支历史上出现过
    # PHP 7.4 的组装结果（.cache/php74-*），文件名同样是 php.exe，
    # 存在性检查一律放行，于是错误版本的运行时被静默打进安装包。
    # 这里把产出所依据的 URL 落成清单，复用前逐行比对，任何一项漂移即重建。
    RUNTIME_MANIFEST="$ASSEMBLED_DIR/.build-manifest"
    EXPECTED_PHP_DIR="$(basename "$PHP_URL" .zip)"
    RUNTIME_MANIFEST_EXPECTED="$(cat <<MANIFEST
schema=2
php=$PHP_URL
mysql=$MYSQL_URL
nginx=$NGINX_URL
laragon=$LARAGON_CORE_URL
composer=$COMPOSER_URL
MANIFEST
)"

    # 缓存完整性检查：PHP / MySQL / Composer 三者齐备才算可复用。
    # 只查 PHP+MySQL 会让上次 Composer 下载失败的残缺缓存被反复复用，
    # 而安装脚本是强制要求 Composer 的。
    HAS_PHP=false; HAS_MYSQL=false; HAS_COMPOSER=false
    for _d in "$ASSEMBLED_DIR"/bin/php/*/; do
        [[ -f "${_d}php.exe" ]] && HAS_PHP=true && break
    done
    for _d in "$ASSEMBLED_DIR"/bin/mysql/*/; do
        [[ -f "${_d}bin/mysqld.exe" ]] && HAS_MYSQL=true && break
    done
    [[ -s "$ASSEMBLED_DIR/bin/composer/composer.phar" ]] && HAS_COMPOSER=true

    # 版本一致性：清单必须存在且与当前锁定的地址完全一致
    MANIFEST_OK=false
    if [[ -f "$RUNTIME_MANIFEST" ]]; then
        if [[ "$(cat "$RUNTIME_MANIFEST")" == "$RUNTIME_MANIFEST_EXPECTED" ]]; then
            MANIFEST_OK=true
        else
            warn "运行环境缓存的构建清单与当前锁定版本不一致"
        fi
    else
        warn "运行环境缓存缺少构建清单（由旧版 build.sh 产出，无法确认版本）"
    fi

    # PHP 目录名必须带上锁定的版本号 —— 目标机的 install-win.ps1 靠目录名发现运行时
    PHP_DIR_OK=false
    [[ -d "$ASSEMBLED_DIR/bin/php/$EXPECTED_PHP_DIR" ]] && PHP_DIR_OK=true

# ── Win7 前置组件下载（VC++ 运行库）
#
# 必须独立于「运行时是否命中缓存」。VC++ 下载原本写在缓存重建分支里，
# 结果是：只要 .cache/win7-runtime 还在（PHP/MySQL/Composer 齐、清单校验通过），
# 整个 else 分支就不执行，新加的组件永远下不下来，打出来的包看着成功，
# 装到纯净 Win7 上却缺前置。

    if [[ "$HAS_PHP" == true ]] && [[ "$HAS_MYSQL" == true ]] && [[ "$HAS_COMPOSER" == true ]] \
       && [[ "$MANIFEST_OK" == true ]] && [[ "$PHP_DIR_OK" == true ]]; then
        info "使用已组装的 Win7 运行环境缓存: $ASSEMBLED_DIR"
        info "  PHP 目录: ${EXPECTED_PHP_DIR}（清单校验通过）"
        WIN7_RUNTIME_DIR="$ASSEMBLED_DIR"
    else
        # 版本不符的缓存必须整个丢弃再重建，否则旧版本的文件会和新版本混在一起
        if [[ -d "$ASSEMBLED_DIR" ]] && { [[ "$MANIFEST_OK" == false ]] || [[ "$PHP_DIR_OK" == false ]]; }; then
            warn "丢弃无法确认版本的运行环境缓存并重新组装: $ASSEMBLED_DIR"
            [[ "$PHP_DIR_OK" == false ]] && warn "  期望的 PHP 目录不存在: bin/php/$EXPECTED_PHP_DIR"
            rm -rf "$ASSEMBLED_DIR"
        fi

        echo ""
        echo -e "${BOLD}${CYAN}组装 Windows 7 运行环境 (PHP 8.2 + MySQL 5.7 + Nginx + Composer)${NC}"
        echo ""

        mkdir -p "$ASSEMBLED_DIR"/bin/{php,mysql,nginx,composer} \
                 "$ASSEMBLED_DIR"/etc/{mysql,nginx/sites-enabled} \
                 "$ASSEMBLED_DIR"/{www,data}

        ASSEMBLE_OK=true

        # ── laragon-core：只取目录骨架与 laragon.exe，不含任何 PHP/MySQL 二进制
        if download_and_extract "$LARAGON_CORE_URL" "$CACHE_DIR/laragon-core.zip" "$CACHE_DIR/laragon-core" "Laragon core" "laragon-core" "$LARAGON_URL_OVERRIDDEN"; then
            [[ -f "$CACHE_DIR/laragon-core/laragon.exe" ]] && cp "$CACHE_DIR/laragon-core/laragon.exe" "$ASSEMBLED_DIR/"
            [[ -d "$CACHE_DIR/laragon-core/bin/laragon" ]] && cp -r "$CACHE_DIR/laragon-core/bin/laragon" "$ASSEMBLED_DIR/bin/"
            [[ -d "$CACHE_DIR/laragon-core/etc" ]] && cp -rn "$CACHE_DIR/laragon-core/etc/"* "$ASSEMBLED_DIR/etc/" 2>/dev/null || true
        else
            warn "Laragon core 获取失败，将只使用自组装的 PHP/MySQL/Nginx"
        fi

        # ── PHP 8.2.33（VS16 x64 NTS）—— Win7 上可用的最高 PHP 分支
        if download_and_extract "$PHP_URL" "$CACHE_DIR/php82.zip" "$CACHE_DIR/php82-extracted" "PHP 8.2" "php82" "$PHP_URL_OVERRIDDEN"; then
            PHP_VER_NAME="$(basename "$PHP_URL" .zip)"
            mkdir -p "$ASSEMBLED_DIR/bin/php/$PHP_VER_NAME"
            cp -r "$CACHE_DIR/php82-extracted/"* "$ASSEMBLED_DIR/bin/php/$PHP_VER_NAME/"
            PHP_TARGET="$ASSEMBLED_DIR/bin/php/$PHP_VER_NAME"

            # 必需扩展必须逐个确认 DLL 真的在包内 —— 不同 PHP 分支的扩展
            # 集合与命名并不一致（例如 7.4 的 GD 叫 gd2 且不带 zip），
            # 想当然写进 php.ini 只会在目标机上静默加载失败。
            PHP_EXTS=(mbstring openssl pdo_mysql mysqli fileinfo gd zip curl exif intl sodium)
            for _ext in "${PHP_EXTS[@]}"; do
                if [[ ! -f "$PHP_TARGET/ext/php_${_ext}.dll" ]]; then
                    error "PHP 包内缺少 ext/php_${_ext}.dll —— 该扩展是本系统必需项"
                    ASSEMBLE_OK=false
                fi
            done
            [[ "$ASSEMBLE_OK" == true ]] && info "必需扩展 DLL 齐备（${#PHP_EXTS[@]} 项）"

            # php.ini：以 production 模板为基线，打开本系统必需的扩展
            if [[ -f "$PHP_TARGET/php.ini-production" ]] && [[ ! -f "$PHP_TARGET/php.ini" ]]; then
                cp "$PHP_TARGET/php.ini-production" "$PHP_TARGET/php.ini"
                {
                    echo ""
                    echo "; ── 牙科诊所管理系统 Win7 版所需扩展 ──"
                    echo "; 扩展名与 ext/php_*.dll 的文件名一一对应，上面已逐个校验存在性。"
                    echo "; bcmath / ctype / tokenizer / json / xml 已编译进核心，无需在此声明。"
                    echo "extension_dir = \"ext\""
                    for _ext in "${PHP_EXTS[@]}"; do
                        echo "extension=$_ext"
                    done
                    echo "memory_limit = 512M"
                    echo "upload_max_filesize = 32M"
                    echo "post_max_size = 32M"
                    echo "max_execution_time = 300"
                    echo "date.timezone = Asia/Shanghai"
                    echo "cgi.fix_pathinfo = 0"
                } >> "$PHP_TARGET/php.ini"
                info "已生成 php.ini（含必需扩展）"
            fi
        else
            error "PHP 8.2 下载失败"; ASSEMBLE_OK=false
        fi

        # ── MySQL 5.7.44
        if download_and_extract "$MYSQL_URL" "$CACHE_DIR/mysql57.zip" "$CACHE_DIR/mysql57-extracted" "MySQL 5.7" "mysql57" "$MYSQL_URL_OVERRIDDEN"; then
            for _d in "$CACHE_DIR/mysql57-extracted"/*/; do
                if [[ -f "${_d}bin/mysqld.exe" ]]; then
                    cp -r "$_d" "$ASSEMBLED_DIR/bin/mysql/$(basename "$_d")"
                    break
                fi
            done
            if [[ -f "$CACHE_DIR/mysql57-extracted/bin/mysqld.exe" ]]; then
                mkdir -p "$ASSEMBLED_DIR/bin/mysql/mysql-5.7.44-winx64"
                cp -r "$CACHE_DIR/mysql57-extracted/"* "$ASSEMBLED_DIR/bin/mysql/mysql-5.7.44-winx64/"
            fi

            # ── 裁剪 MySQL 的非运行时产物
            #
            # 官方 winx64 zip 里约 870MB 是给「编译链接 MySQL 客户端程序」和
            # 「跑官方测试」用的，跑一个数据库一个字节都用不上。不裁剪，
            # 安装包会从 ~540MB 涨到 ~1.0GB。
            #
            # 注意：这一步必须在构建脚本里做。此前本机缓存是手工删过这些文件的，
            # 于是「构建产物 540MB」这个结论根本无法从干净缓存复现 ——
            # 换台机器构建就会得到一个体积翻倍的包。
            _MYSQL_DIR="$ASSEMBLED_DIR/bin/mysql/mysql-5.7.44-winx64"
            if [[ -d "$_MYSQL_DIR" ]]; then
                _MYSQL_BEFORE=$(du -sh "$_MYSQL_DIR" 2>/dev/null | cut -f1)

                # 静态链接库：编译期产物（mysqlserver.lib 一个就 553MB）
                rm -f "$_MYSQL_DIR"/lib/*.lib
                # 调试符号：约 118MB
                rm -f "$_MYSQL_DIR"/bin/*.pdb
                # 头文件：编译期产物
                rm -rf "$_MYSQL_DIR/include"
                # MeCab 日语分词词典：仅 mecab 全文检索解析器需要，本系统是 zh-CN
                rm -rf "$_MYSQL_DIR/lib/mecab"
                # 嵌入式服务器与测试用二进制（libmysqld.dll 只服务于这些程序）
                rm -f "$_MYSQL_DIR"/lib/libmysqld.dll
                rm -f "$_MYSQL_DIR"/bin/*_embedded.exe
                rm -f "$_MYSQL_DIR"/bin/mysql_client_test*.exe
                rm -f "$_MYSQL_DIR"/bin/mysqltest*.exe
                rm -f "$_MYSQL_DIR"/bin/mysqlxtest.exe
                # 官方测试套件与基准（部分发行版带）
                rm -rf "$_MYSQL_DIR/mysql-test" "$_MYSQL_DIR/sql-bench"

                # 裁完必须还能跑：这四个是安装/备份/卸载脚本实际调用的
                for _need in mysqld.exe mysql.exe mysqldump.exe mysqladmin.exe; do
                    if [[ ! -f "$_MYSQL_DIR/bin/$_need" ]]; then
                        error "MySQL 裁剪后缺少 bin/$_need —— 裁剪规则过宽"; ASSEMBLE_OK=false
                    fi
                done
                if [[ ! -d "$_MYSQL_DIR/share" ]]; then
                    error "MySQL 裁剪后缺少 share/（错误信息文件），mysqld 将无法启动"; ASSEMBLE_OK=false
                fi

                _MYSQL_AFTER=$(du -sh "$_MYSQL_DIR" 2>/dev/null | cut -f1)
                info "裁剪 MySQL 非运行时产物: ${_MYSQL_BEFORE} → ${_MYSQL_AFTER}"
            fi
        else
            error "MySQL 5.7 下载失败"; ASSEMBLE_OK=false
        fi

        # ── Nginx
        if download_and_extract "$NGINX_URL" "$CACHE_DIR/nginx-win7.zip" "$CACHE_DIR/nginx-win7-extracted" "Nginx" "nginx" "$NGINX_URL_OVERRIDDEN"; then
            for _d in "$CACHE_DIR/nginx-win7-extracted"/*/; do
                if [[ -f "${_d}nginx.exe" ]]; then
                    cp -r "$_d" "$ASSEMBLED_DIR/bin/nginx/$(basename "$_d")"
                    break
                fi
            done
            if [[ -f "$CACHE_DIR/nginx-win7-extracted/nginx.exe" ]]; then
                mkdir -p "$ASSEMBLED_DIR/bin/nginx/nginx-1.24.0"
                cp -r "$CACHE_DIR/nginx-win7-extracted/"* "$ASSEMBLED_DIR/bin/nginx/nginx-1.24.0/"
            fi
        else
            error "Nginx 下载失败"; ASSEMBLE_OK=false
        fi

        # ── Composer
        # 安装脚本把 Composer 当作硬性前置（install-win.ps1 找不到就退出），
        # 所以这里下载失败必须让整个组装失败，不能只 warn 后把残缺缓存留下。
        #
        # 指纹校验走上游 sidecar：latest-stable 是移动地址，写死指纹会在
        # Composer 发新版时把构建卡死。getcomposer.org 对每个下载地址都提供
        # 同名 .sha256sum，取它来卡当次下载的完整性。
        if [[ ! -s "$ASSEMBLED_DIR/bin/composer/composer.phar" ]]; then
            if curl -fsSL --retry 2 -o "$ASSEMBLED_DIR/bin/composer/composer.phar" "$COMPOSER_URL" \
               && [[ -s "$ASSEMBLED_DIR/bin/composer/composer.phar" ]]; then
                COMPOSER_EXPECTED="$(curl -fsSL --retry 2 --max-time 30 "${COMPOSER_URL}.sha256sum" 2>/dev/null | awk '{print $1}')"
                COMPOSER_ACTUAL="$(sha256_of "$ASSEMBLED_DIR/bin/composer/composer.phar")"
                if [[ -z "$COMPOSER_EXPECTED" ]]; then
                    warn "Composer: 无法获取上游 SHA256（${COMPOSER_URL}.sha256sum），跳过校验"
                    info "Composer 已就位"
                elif [[ -n "$COMPOSER_ACTUAL" ]] && [[ "$COMPOSER_ACTUAL" != "$COMPOSER_EXPECTED" ]]; then
                    rm -f "$ASSEMBLED_DIR/bin/composer/composer.phar"
                    error "Composer: SHA256 不匹配（期望 ${COMPOSER_EXPECTED}，实际 ${COMPOSER_ACTUAL}）"; ASSEMBLE_OK=false
                else
                    info "Composer 已就位（SHA256 校验通过）"
                fi
            else
                rm -f "$ASSEMBLED_DIR/bin/composer/composer.phar"
                error "Composer 下载失败 —— 安装脚本强制要求 Composer"; ASSEMBLE_OK=false
            fi
        fi


        if [[ "$ASSEMBLE_OK" == true ]]; then
            # 组装成功才写清单 —— 半成品不留清单，下次构建会当作不可信缓存丢弃
            printf '%s\n' "$RUNTIME_MANIFEST_EXPECTED" > "$RUNTIME_MANIFEST"
            WIN7_RUNTIME_DIR="$ASSEMBLED_DIR"
            info "Win7 运行环境组装完成: $ASSEMBLED_DIR"
        else
            rm -rf "$ASSEMBLED_DIR"
            fatal "Win7 运行环境组装失败（PHP 或 MySQL 缺失），请检查网络或用 PHP_DOWNLOAD_URL/MYSQL_DOWNLOAD_URL 指定镜像"
        fi
    fi

    # VC++ 运行库的下载与运行时缓存是否命中无关，必须无条件执行。
    if ! download_win7_prereqs; then
        fatal "Win7 前置组件缺失（VC++ 运行库），拒绝产出装不上的安装包"
    fi
fi

# ── XAMPP 运行时（--runtime xampp）─────────────────────────────────────
#
# 与上面的 Laragon 自组装二选一。XAMPP 官方提供 portable 压缩包，解压即用，
# 不需要逐件下载组装，所以这里只负责调用准备脚本、拿到目录；
# 下载/校验/解压/自检的细节都在 deploy/prepare-xampp-runtime.sh 里。
#
# 位置必须在 download_win7_prereqs() 定义之后（该函数定义在本段之前不远处）。
# ── xampp-installer：只把官方安装器放进包，不铺运行时文件树 ────────────────
# 应用不放 htdocs：setup.bat 复制应用时 C:\xampp 还不存在（XAMPP 要等
# install-win.ps1 才安装）。所以应用固定在 {安装目录}\dental，由 Apache
# vhost 的 DocumentRoot 指过去 —— write_apache_vhost.php 接受任意路径并
# 生成对应的 <Directory> 授权块，不依赖 htdocs。
if [[ "$TARGET" == "win" ]] && [[ "$UPGRADE" == false ]] && [[ "$RUNTIME_FLAVOR" == "xampp-installer" ]]; then
    step "准备 XAMPP 官方安装器"
    XAMPP_INSTALLER_PREPARE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/prepare-xampp-installer.sh"
    [[ -x "$XAMPP_INSTALLER_PREPARE" ]] || fatal "找不到可执行的 prepare-xampp-installer.sh"
    # 只准备缓存，不给输出目录 —— 此处 DIST_DIR 还没定义（它在下面的
    # 「项目根目录定位」之后才赋值）。复制统一放到后面与 xampp 树同一处做。
    "$XAMPP_INSTALLER_PREPARE" || fatal "XAMPP 安装器准备失败"
    XAMPP_INSTALLER_SRC="$CACHE_DIR/xampp-windows-x64-8.2.12-0-VS16-installer.exe"
    [[ -f "$XAMPP_INSTALLER_SRC" ]] || fatal "XAMPP 安装器不存在: $XAMPP_INSTALLER_SRC"

    # XAMPP 的 Apache / PHP 是 VS16 构建，依赖 VC++ 运行库，而官方安装器
    # **不负责**装它。不在这里补一次，目标机上 httpd.exe 直接起不来。
    if ! download_win7_prereqs; then
        fatal "Win7 前置组件缺失（VC++ 运行库），拒绝产出装不上的安装包"
    fi
    info "包内含 xampp-installer.exe，目标机静默安装到 C:\xampp"
fi

if [[ "$TARGET" == "win" ]] && [[ "$UPGRADE" == false ]] && [[ "$RUNTIME_FLAVOR" == "xampp" ]]; then
    step "准备 XAMPP portable 运行时"
    XAMPP_PREPARE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/prepare-xampp-runtime.sh"
    [[ -x "$XAMPP_PREPARE" ]] || fatal "找不到可执行的 $XAMPP_PREPARE"
    "$XAMPP_PREPARE" || fatal "XAMPP 运行时准备失败"
    XAMPP_RUNTIME_SRC="$CACHE_DIR/xampp-runtime/xampp"
    [[ -d "$XAMPP_RUNTIME_SRC" ]] || fatal "XAMPP 运行时目录不存在: $XAMPP_RUNTIME_SRC"

    # XAMPP 的 Apache / PHP 同样是 VS16 构建，一样依赖 VC++ 运行库。
    # 这个下载原本只挂在 Laragon 分支里，不在这里补一次的话，
    # XAMPP 包会缺 vc_redist.x64.exe，目标机上 httpd.exe 直接起不来。
    if ! download_win7_prereqs; then
        fatal "Win7 前置组件缺失（VC++ 运行库），拒绝产出装不上的安装包"
    fi
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
# 必须与实际的 step "..." 调用数一致，否则会打出 [10/9] 这种进度。
# 无条件执行的 7 步：清理目录 / 复制项目 / Composer / 复制部署脚本 /
# OCR / 发布内容校验 / 打包
TOTAL_STEPS=7
if [[ "$SKIP_OBFUSCATE" == false ]]; then
    TOTAL_STEPS=$((TOTAL_STEPS + 1))  # PHP 代码混淆
fi
# 全量包导出 schema，升级包生成升级元数据 —— 二选一，总有一步
TOTAL_STEPS=$((TOTAL_STEPS + 1))
if [[ "$TARGET" == "win" ]]; then
    TOTAL_STEPS=$((TOTAL_STEPS + 2))  # .bat 转 GBK + .ps1 转 UTF-8 BOM
fi

# ── 构建路径 ───────────────────────────────────────────────────────────
DIST_DIR="$PROJECT_ROOT/deploy/dist"
OUTPUT_DIR="$PROJECT_ROOT/deploy/output"

SUFFIX="${TARGET}"
# 两种 xampp 形态必须产出不同文件名，否则后构建的会**静默覆盖**前一个 ——
# 现场拿错包是真实发生过的事（产物名带 commit 短哈希也正是为此）。
if [[ "$UPGRADE" == true ]]; then
    SUFFIX="${TARGET}-upgrade"
elif [[ "$RUNTIME_FLAVOR" == "xampp-installer" ]]; then
    SUFFIX="${TARGET}-xampp-installer"
elif [[ "$USE_EXISTING_MYSQL" == true ]]; then
    SUFFIX="${TARGET}-existing-mysql"
elif [[ -n "$BUNDLED_MYSQL_PORT" ]]; then
    SUFFIX="${TARGET}-bundled-mysql-${BUNDLED_MYSQL_PORT}"
fi

# 产物名带上 commit 短哈希：现场反复拿旧包测试、以为改动没生效，是真实发生过的事。
# 文件名里有构建标识，一眼就能对上是哪次提交出的包。
BUILD_ID="$(cd "$PROJECT_ROOT" 2>/dev/null && git rev-parse --short HEAD 2>/dev/null)"
[[ -n "$(cd "$PROJECT_ROOT" 2>/dev/null && git status --porcelain 2>/dev/null)" ]] && BUILD_ID="${BUILD_ID}-dirty"
[[ -z "$BUILD_ID" ]] && BUILD_ID="nogit"

ARCHIVE_NAME="dental-clinic-${VERSION}-${SUFFIX}-${BUILD_ID}.zip"
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
if [[ "$USE_EXISTING_MYSQL" == true ]]; then
echo -e "  MySQL:    ${BOLD}复用目标机 127.0.0.1:3306（密码安装时输入）${NC}"
fi
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
    # storage/app/ 是运行期产物目录，里面全是**构建机本地的业务数据**，一件都不该进包：
    #   storage/app/<备份名>/*.zip     spatie/laravel-backup 的输出，zip 里是整库 dump
    #                                  （db-dumps/mysql-*.sql，含账号密码哈希与患者手机号）
    #   storage/app/work_log_images/   工作日志上传的原图
    # 2026-08-06 那次 win/xampp 全量包就这样带出去了 3 个整库转储和 2 张工作日志照片 ——
    # 排除清单当时覆盖了 storage/logs 和 storage/framework，独独漏了 storage/app。
    # 目录结构不用担心：下面 UPGRADE == false 分支会把 storage/app/public 等重新 mkdir 出来。
    # 注意这和 --init-db-from-local 是两件事：后者是刻意导出当前库作初始数据，
    # 走的是 database/schema/mysql-schema.sql，不依赖 storage/app 里的任何东西。
    --exclude='storage/app/*'
    --exclude='.claude/'
    # AI/编辑器工具目录：装到诊所机器上没有任何用途，而且带的是内部提示词与配置
    --exclude='.gstack/'
    --exclude='.superpowers/'
    --exclude='.cursor/'
    --exclude='.worktrees/'
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

        # 输出必须留存并检查，不能只 tail 掉。yakpro-po 对配置文件的拒绝
        # 只是 stderr 上的一行 Warning，然后**照常执行**并回退到它自带的
        # 默认配置 —— 那份会混淆类名和命名空间，产出连 artisan 都起不来的包。
        # 这行 Warning 恰好是输出的第一行，之前的 `| tail -3` 正好把它丢掉了。
        # 放 dist 之外：写在 dist 里会被一起打进安装包，泄露构建机路径
        YAKPRO_OUT="$(mktemp -t yakpro-po.XXXXXX)"
        $YAKPRO_CMD "$APP_SRC" \
            -o "$APP_OBFUSCATED" \
            --config-file "$YAKPRO_CNF" \
            > "$YAKPRO_OUT" 2>&1 || true
        tail -3 "$YAKPRO_OUT"

        if grep -q 'is not a valid yakpro-po config file' "$YAKPRO_OUT"; then
            fatal "yakpro-po 拒绝了 deploy/yakpro-po.cnf（前两行必须是 '<?php' 和 '// YAK Pro - Php Obfuscator: Config File'），已回退到默认配置。默认配置会混淆类名与命名空间，产物无法启动。详见 $YAKPRO_OUT"
        fi
        if ! grep -q "Using \[$YAKPRO_CNF\] Config File" "$YAKPRO_OUT"; then
            fatal "yakpro-po 没有使用项目配置 deploy/yakpro-po.cnf，详见 $YAKPRO_OUT"
        fi
        # 写错的选项名不会报错，只会变成动态属性并被忽略 —— 设置静默失效
        if grep -q 'dynamic property Config::' "$YAKPRO_OUT"; then
            warn "yakpro-po 配置里有无效选项名（已被忽略）:"
            grep -oE 'dynamic property Config::\$[a-z_]+' "$YAKPRO_OUT" | sort -u | sed 's/^/    /'
            fatal "请对照 yakpro-po 的 include/classes/config.php 修正选项名后重新构建"
        fi
        rm -f "$YAKPRO_OUT"

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
# Step 5: 导出数据库（仅全量包）
#
# 默认：只导结构（--no-data），装机后由 artisan migrate + db:seed 生成数据。
#       这是常规交付形态，产物不含任何本地库里的业务数据。
#
# --init-db-from-local：改为导出**本地开发库的结构 + 数据**作为初始数据库。
#       用于「装完即可用、带一套现成配置」的交付。注意这会把本地库里的
#       全部数据（含账号、患者、日志）打进安装包，发给谁就等于给谁看，
#       只在明确需要时使用。
# ═══════════════════════════════════════════════════════════════════════
if [[ "$UPGRADE" == false ]] && [[ "$INIT_DB_FROM_LOCAL" == true ]]; then
    step "导出初始数据库（本地库结构 + 数据）"

    SCHEMA_DIR="$DIST_DIR/database/schema"
    mkdir -p "$SCHEMA_DIR"
    SCHEMA_FILE="$SCHEMA_DIR/mysql-schema.sql"

    # install-win.ps1 先找 database\schema.sql，找不到才用 database\schema\mysql-schema.sql。
    # 项目里若混进了前者会盖过本步骤的产物，装机时导入的就是旧文件。
    rm -f "$DIST_DIR/database/schema.sql"

    [[ ! -f "$PROJECT_ROOT/.env" ]] && fatal "--init-db-from-local 需要 .env 来定位本地库"

    DB_HOST=$(grep -E '^DB_HOST=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
    DB_PORT=$(grep -E '^DB_PORT=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
    DB_DATABASE=$(grep -E '^DB_DATABASE=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
    DB_USERNAME=$(grep -E '^DB_USERNAME=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")
    DB_PASSWORD=$(grep -E '^DB_PASSWORD=' "$PROJECT_ROOT/.env" | cut -d'=' -f2- | tr -d '"' | tr -d "'")

    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-3306}"
    [[ -z "$DB_DATABASE" ]] && fatal ".env 里没有 DB_DATABASE，无法导出初始数据库"

    info "数据源: ${DB_USERNAME}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}"
    warn "本地库的全部数据都会进安装包 —— 账号、患者、日志一并发给使用方"

    # 密码走 MYSQL_PWD 而非 -p 参数：命令行参数会出现在 ps 输出里
    MYSQLDUMP_ARGS=(
        -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME"
        --default-character-set=utf8mb4
        --single-transaction
        --routines --triggers
        --hex-blob
        --add-drop-table
        --no-tablespaces
        "$DB_DATABASE"
    )
    # MySQL 8+ 客户端专有：GTID 语句 5.7 不认；column-statistics 查的表 5.7 没有。
    # 老客户端不认这两个参数，所以失败后退回基础参数重试一次。
    MYSQLDUMP_COMPAT_ARGS=(--set-gtid-purged=OFF --column-statistics=0)

    DB_DUMPED=false
    if command -v mysqldump &>/dev/null; then
        if MYSQL_PWD="$DB_PASSWORD" mysqldump "${MYSQLDUMP_COMPAT_ARGS[@]}" "${MYSQLDUMP_ARGS[@]}" > "$SCHEMA_FILE" 2>/dev/null && [[ -s "$SCHEMA_FILE" ]]; then
            DB_DUMPED=true
        elif MYSQL_PWD="$DB_PASSWORD" mysqldump "${MYSQLDUMP_ARGS[@]}" > "$SCHEMA_FILE" 2>/dev/null && [[ -s "$SCHEMA_FILE" ]]; then
            DB_DUMPED=true
        fi
    fi

    # 回退：构建机没装 mysql 客户端时，借开发容器里的 mysqldump
    if [[ "$DB_DUMPED" == false ]] && command -v docker &>/dev/null; then
        DB_DUMP_CONTAINER="${DB_DUMP_CONTAINER:-mysql}"
        if docker ps --format '{{.Names}}' 2>/dev/null | grep -qx "$DB_DUMP_CONTAINER"; then
            info "构建机无 mysqldump，改用容器 ${DB_DUMP_CONTAINER} 内的 mysqldump"
            if docker exec -e MYSQL_PWD="$DB_PASSWORD" "$DB_DUMP_CONTAINER" \
                 mysqldump -u "$DB_USERNAME" \
                 --default-character-set=utf8mb4 --single-transaction \
                 --routines --triggers --hex-blob --add-drop-table \
                 --no-tablespaces --set-gtid-purged=OFF \
                 "$DB_DATABASE" > "$SCHEMA_FILE" 2>/dev/null && [[ -s "$SCHEMA_FILE" ]]; then
                DB_DUMPED=true
            fi
        fi
    fi

    [[ "$DB_DUMPED" == false ]] && fatal "初始数据库导出失败（${DB_USERNAME}@${DB_HOST}:${DB_PORT}/${DB_DATABASE}）—— 请确认数据库可连接，或去掉 --init-db-from-local"

    # ── MySQL 5.7 兼容性与完整性检查 ─────────────────────────────
    # 这些问题在构建机上完全看不出来，只会在诊所的 Win7 上导入时炸。
    if grep -qi 'utf8mb4_0900' "$SCHEMA_FILE"; then
        fatal "导出的 SQL 含 utf8mb4_0900_* 排序规则 —— 那是 MySQL 8 的默认排序规则，5.7 上不存在，导入会报 Unknown collation。请把本地库统一为 utf8mb4_unicode_ci 后重新构建"
    fi
    if grep -qiE '^(CREATE DATABASE|USE `)' "$SCHEMA_FILE"; then
        fatal "导出的 SQL 含 CREATE DATABASE / USE 语句 —— 目标库名是 pristine_dental，与本地库不同名，会导入到错误的库"
    fi

    DUMP_TABLES=$(grep -c '^CREATE TABLE' "$SCHEMA_FILE" 2>/dev/null || true)
    DUMP_INSERTS=$(grep -c '^INSERT INTO' "$SCHEMA_FILE" 2>/dev/null || true)
    [[ "${DUMP_TABLES:-0}" -lt 50 ]] && fatal "导出的 SQL 只有 ${DUMP_TABLES} 张表，明显不完整（本系统约 125 张）"

    # users 必须有数据：装机脚本据此判断库是否为空，空则跑 db:seed 重建基础数据，
    # 且随包若无任何用户，装完将无账号可登录。
    #
    # 注：此处原先还有一层顾虑 —— db:seed 会让 MenuItemsSeeder truncate 掉随包的
    # menu_items / role_menu_items。该 seeder 现已改为按 title_key 幂等 upsert
    # （既有项就地更新、未定义项只报告不删除），不再有这个风险。
    if ! grep -qE '^INSERT INTO `users`' "$SCHEMA_FILE"; then
        fatal "导出的 SQL 里 users 表没有数据 —— 装机后将无账号可登录，且会被判定为空库而重跑 db:seed。请确认本地库的 users 表非空"
    fi

    SCHEMA_SIZE=$(du -h "$SCHEMA_FILE" | cut -f1)
    info "初始数据库导出完成: ${DUMP_TABLES} 张表 / ${DUMP_INSERTS} 条 INSERT 语句 (${SCHEMA_SIZE})"
    info "  装机时导入到 pristine_dental；users 非空 → db:seed 自动跳过"

elif [[ "$UPGRADE" == false ]]; then
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
        if [[ "$USE_EXISTING_MYSQL" == true ]]; then
            touch "$DIST_DIR/use-existing-mysql.flag"
            info "标记安装包复用目标机 MySQL"
        fi
        if [[ -n "$BUNDLED_MYSQL_PORT" ]]; then
            printf '%s\n' "$BUNDLED_MYSQL_PORT" > "$DIST_DIR/bundled-mysql-port.txt"
            info "标记内置 MySQL 独立端口: $BUNDLED_MYSQL_PORT"
        fi
        # 创建 setup.bat 快捷入口（Laragon 已预装模式）
        cat > "$DIST_DIR/setup.bat" <<'SHORTCUT_BAT'
@echo off
setlocal EnableExtensions DisableDelayedExpansion
chcp 936 >nul 2>&1

REM 需要管理员：不足则弹出 UAC（Inno 已是 admin 时会直接通过）
REM 判据与提升都放在顶层：( ) 块内的 %ERRORLEVEL% 在 DisableDelayedExpansion
REM 下于进块前就展开完，取到的是 net session 失败留下的 1，于是提升后的子进程
REM 即使装成功也会被报成失败。install-win.bat 用的是延迟展开形式（该文件
REM 启用了延迟展开），这里没有延迟展开，只能靠摊平到顶层逐行读。
net session >nul 2>&1
if not errorlevel 1 goto :have_admin
echo.
echo  需要管理员权限，请在 UAC 提示中选择「是」...
echo.
powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -LiteralPath '%~f0' -Verb RunAs -Wait"
set "ELEV_RC=%ERRORLEVEL%"
exit /b %ELEV_RC%
:have_admin

set "INSTALL_DIR=C:\DentalClinic"
set "PKG_DIR=%~dp0"
if "%PKG_DIR:~-1%"=="\" set "PKG_DIR=%PKG_DIR:~0,-1%"
for %%I in ("%INSTALL_DIR%") do set "INSTALL_DIR=%%~fI"
for %%I in ("%PKG_DIR%") do set "PKG_DIR=%%~fI"
set "IN_PLACE=0"
if /I "%PKG_DIR%"=="%INSTALL_DIR%" set "IN_PLACE=1"

echo.
echo  =======================================================
echo    Dental Clinic Management System - Installer
echo  =======================================================
echo.

REM 判据是「目录到底在不在」，不是 errorlevel。
REM mkdir 被 if not exist 挡掉时**不会重置 errorlevel**，它保留上一条命令的值；
REM 于是「安装目录已存在」这条分支会把之前任何一个非零 errorlevel
REM 误报成 "Failed to create install directory"，重装直接死在这里。
REM 目录不存在时 mkdir 会跑、errorlevel 被重置，所以首次安装看不出问题。
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%" >nul 2>&1
if not exist "%INSTALL_DIR%" (
    echo  [ERROR] Failed to create install directory:
    echo          %INSTALL_DIR%
    exit /b 1
)

REM ═══════════════════════════════════════════════════════════════
REM  setup.bat 自己的日志
REM
REM  此前 setup.bat 一行日志都不写：它在调用 install-win.bat 之前失败的话，
REM  磁盘上什么都没有 —— prereq.log 和 install-*.log 都还没开始写。
REM  现场只能说「报错了」，看不到是哪一步、哪个文件。为此反复折腾了好几轮。
REM  从这里开始每一步都落盘到 logs\setup.log，xcopy 的真实错误也进去。
REM ═══════════════════════════════════════════════════════════════
if not exist "%INSTALL_DIR%\logs" mkdir "%INSTALL_DIR%\logs" >nul 2>&1
set "SETUP_LOG=%INSTALL_DIR%\logs\setup.log"
>"%SETUP_LOG%" echo ===== setup.bat start =====
call :log "INSTALL_DIR = %INSTALL_DIR%"
call :log "PKG_DIR     = %PKG_DIR%"
call :log "IN_PLACE    = %IN_PLACE%"

echo  [1/3] Stopping running services...
REM 只调用本系统已有的停止脚本；绝不按进程名结束目标机的其他 PHP/Nginx。
REM
REM 下面刻意**不用 if (...) 包多条语句**：
REM   - ( ) 块里的 %ERRORLEVEL% 在 DisableDelayedExpansion 下于进块前就展开完，
REM     记下来的是上一条命令的退出码，不是刚跑的那条；
REM   - ( ) 块里放 :label / goto 在 cmd 里行为是坏的。
REM 所以一律用 goto 跳过，退出码在顶层逐行读。
REM ── 先把部署脚本刷新成包内的版本，再调用 stop-win.bat ──
REM
REM 顺序在这里是关键。下面调的是 %INSTALL_DIR%\stop-win.bat，也就是**上一次
REM 装机留在目标机上的那份**，不是包里的。包里的副本原本要到「复制文件」
REM 阶段才写过去，于是只要机器上有旧安装，就永远先跑旧脚本 ——
REM 对 stop-win.bat 的任何修复都没机会在这台机器的第一次重装里生效。
REM 2026-08-07 23:14 的 setup.log 就是这样：裸管道已经修好并打进包，
REM 现场跑的却仍是旧版，照样死在
REM   The syntax of the command is incorrect.
REM
REM 这些是几十 KB 的 .bat/.ps1，不属于运行时目录，不会被正在运行的服务占用，
REM 所以提前复制是安全的；真正需要先停服务的是后面 xampp/laragon 那棵树。
if "%IN_PLACE%"=="1" goto :scripts_refreshed
call :log "refreshing deployment scripts before stopping services"
for %%F in (install-win.bat install-win.ps1 upgrade-win.bat start-win.bat stop-win.bat uninstall-win.bat laragon-startup.bat) do (
    if exist "%PKG_DIR%\%%F" copy "%PKG_DIR%\%%F" "%INSTALL_DIR%\%%F" /Y >nul 2>>"%SETUP_LOG%"
)
if exist "%PKG_DIR%\batch-helpers" xcopy "%PKG_DIR%\batch-helpers" "%INSTALL_DIR%\batch-helpers\" /E /I /H /Y /Q >nul 2>>"%SETUP_LOG%"
:scripts_refreshed

if not exist "%INSTALL_DIR%\stop-win.bat" goto :no_stop_script
call :log "calling stop-win.bat (refreshed copy)"
call "%INSTALL_DIR%\stop-win.bat" "%INSTALL_DIR%" --background >>"%SETUP_LOG%" 2>&1
call :log_rc "stop-win.bat" %ERRORLEVEL%
goto :after_stop_script
:no_stop_script
call :log "stop-win.bat not present (first install)"
:after_stop_script

REM 内置数据库与 Apache 都是 auto-start 服务，而它们的文件就在待覆盖的运行时
REM 目录里（xampp\mysql\data、xampp\php\php8ts.dll、xampp\apache\logs\*）。
REM 进程还活着，xcopy 就会撞上被占用的文件，而那类失败以前只会得到一句
REM 「could not be copied」，看不出是谁占着。
REM 这里各补一次 net stop：它是**同步**的（sc stop 是异步的），返回即已停止；
REM 服务不存在或已停止时返回非零，属正常，不当失败。
REM 只停本系统注册的这两个服务名，绝不按进程名批量终止 —— 目标机上可能还有
REM 别人的 Apache / MySQL。
sc query DentalClinicApache >nul 2>&1
if errorlevel 1 goto :no_apache_service
call :log "stopping DentalClinicApache (net stop is synchronous)"
net stop DentalClinicApache >>"%SETUP_LOG%" 2>&1
call :log_rc "net stop DentalClinicApache" %ERRORLEVEL%
:no_apache_service
sc query DentalClinicMySQL >nul 2>&1
if errorlevel 1 goto :no_db_service
call :log "stopping DentalClinicMySQL (net stop is synchronous)"
net stop DentalClinicMySQL >>"%SETUP_LOG%" 2>&1
call :log_rc "net stop DentalClinicMySQL" %ERRORLEVEL%
:no_db_service
timeout /t 2 /nobreak >nul 2>&1

REM 应用代码的目标目录随运行时形态而变：
REM   laragon -> {安装目录}\laragon\www\dental
REM   xampp   -> {安装目录}\xampp\htdocs\dental
REM 按包内目录存在性判定，与 install-win.ps1 的识别方式保持一致，
REM 这样同一份 setup.bat 两种包都能用，不需要构建期分叉出两个模板。
REM 三种形态：
REM   laragon         包内有 laragon\ 文件树，应用放 laragon\www\dental
REM   xampp           包内有 xampp\ 文件树，应用放 xampp\htdocs\dental
REM   xampp-installer 包内只有 xampp-installer.exe，XAMPP 由 install-win.ps1
REM                   在目标机静默装到 C:\xampp。此时应用**不能**放 htdocs ——
REM                   本脚本复制应用时 C:\xampp 还不存在。所以固定放
REM                   {安装目录}\dental，由 Apache vhost 的 DocumentRoot 指过去。
set "RUNTIME_DIR_NAME=laragon"
set "APP_ROOT=%INSTALL_DIR%\laragon\www\dental"
set "XAMPP_INSTALLER_MODE=0"
if exist "%PKG_DIR%\xampp\apache\bin\httpd.exe" (
    set "RUNTIME_DIR_NAME=xampp"
    set "APP_ROOT=%INSTALL_DIR%\xampp\htdocs\dental"
)
if exist "%PKG_DIR%\xampp-installer.exe" (
    set "XAMPP_INSTALLER_MODE=1"
    set "RUNTIME_DIR_NAME="
    set "APP_ROOT=%INSTALL_DIR%\dental"
)

REM 已完整安装过的 XAMPP 运行时无需在每次 setup.bat 重跑时覆盖。
REM PHP/Apache/OCR 进程会长期加载这些 DLL；重复 xcopy 整棵运行时既慢，也容易
REM 因杀毒软件或残留进程的短暂文件句柄报“共享冲突”。应用和部署脚本仍会同步。
set "TARGET_RUNTIME_READY=0"
if not "%RUNTIME_DIR_NAME%"=="xampp" goto :runtime_ready_checked
if not exist "%INSTALL_DIR%\xampp\apache\bin\httpd.exe" goto :runtime_ready_checked
if not exist "%INSTALL_DIR%\xampp\php\php.exe" goto :runtime_ready_checked
if not exist "%INSTALL_DIR%\xampp\mysql\bin\mysqld.exe" goto :runtime_ready_checked
set "TARGET_RUNTIME_READY=1"
:runtime_ready_checked

echo  [2/3] Copying runtime and application files...

REM 运行时必须先落到 %INSTALL_DIR%\%RUNTIME_DIR_NAME%，
REM 否则 install-win.ps1 会因找不到运行时目录而直接退出。
REM 目录名随包内形态而定（laragon 或 xampp），不能写死。
REM 安装包允许直接解压到 C:\DentalClinic。此时源和目标相同，绝不能 xcopy 自身。
REM installer 模式没有运行时文件树可复制，只需把安装器本身放过去。
if not "%XAMPP_INSTALLER_MODE%"=="1" goto :copy_runtime_tree
call :log "xampp-installer mode: copying installer only"
if "%IN_PLACE%"=="0" call :copy_file "%PKG_DIR%\xampp-installer.exe" "%INSTALL_DIR%\xampp-installer.exe" "XAMPP installer"
if errorlevel 1 goto :copy_failed
echo         XAMPP will be installed by install-win.ps1 (C:\xampp).
goto :runtime_done
:copy_runtime_tree

if "%TARGET_RUNTIME_READY%"=="1" goto :runtime_copy_skipped_existing
if "%IN_PLACE%"=="1" goto :runtime_copy_skipped_in_place
if not exist "%PKG_DIR%\%RUNTIME_DIR_NAME%" goto :runtime_source_missing
echo         Copying %RUNTIME_DIR_NAME% runtime, please wait...
call :copy_dir "%PKG_DIR%\%RUNTIME_DIR_NAME%" "%INSTALL_DIR%\%RUNTIME_DIR_NAME%" "%RUNTIME_DIR_NAME% runtime"
if errorlevel 1 goto :copy_failed
goto :runtime_done

:runtime_copy_skipped_existing
echo         Existing complete XAMPP runtime detected; runtime overlay skipped.
call :log "existing complete XAMPP runtime detected; runtime overlay skipped"
goto :runtime_done

:runtime_copy_skipped_in_place
if not exist "%INSTALL_DIR%\%RUNTIME_DIR_NAME%" goto :runtime_in_place_missing
echo         Package is already in the install directory; runtime self-copy skipped.
goto :runtime_done

:runtime_source_missing
echo  [ERROR] Missing %RUNTIME_DIR_NAME% runtime in package:
echo          %PKG_DIR%\%RUNTIME_DIR_NAME%
exit /b 1

:runtime_in_place_missing
echo  [ERROR] In-place package is missing %RUNTIME_DIR_NAME% runtime:
echo          %INSTALL_DIR%\%RUNTIME_DIR_NAME%
exit /b 1
:runtime_done

for %%D in (app bootstrap config database public resources routes storage vendor scripts) do (
    if exist "%PKG_DIR%\%%D" (
        call :copy_dir "%PKG_DIR%\%%D" "%APP_ROOT%\%%D" "application directory %%D"
        if errorlevel 1 goto :copy_failed
    )
)

REM artisan 与 composer.json/lock 缺一不可：
REM install-win.ps1 会显式校验 artisan 是否存在，缺了直接判定「项目不完整」。
for %%F in (artisan composer.json composer.lock .env.deploy VERSION .htaccess) do (
    if exist "%PKG_DIR%\%%F" (
        call :copy_file "%PKG_DIR%\%%F" "%APP_ROOT%\%%F" "application file %%F"
        if errorlevel 1 goto :copy_failed
    )
)

REM 同目录重跑时这些资源已经在正确位置；跳过可避免 copy "file" "file"。
if "%IN_PLACE%"=="0" (
    for %%F in (install-win.bat install-win.ps1 upgrade-win.bat start-win.bat stop-win.bat uninstall-win.bat laragon-startup.bat) do (
        if exist "%PKG_DIR%\%%F" (
            call :copy_file "%PKG_DIR%\%%F" "%INSTALL_DIR%\%%F" "deployment script %%F"
            if errorlevel 1 goto :copy_failed
        )
    )
    if exist "%PKG_DIR%\batch-helpers" (
        call :copy_dir "%PKG_DIR%\batch-helpers" "%INSTALL_DIR%\batch-helpers" "batch helpers"
        if errorlevel 1 goto :copy_failed
    )

    REM OCR 离线资源与运行库安装器（安装脚本按需取用）
    if exist "%PKG_DIR%\ocr-wheels" (
        call :copy_dir "%PKG_DIR%\ocr-wheels" "%INSTALL_DIR%\ocr-wheels" "OCR wheels"
        if errorlevel 1 goto :copy_failed
    )
    for %%F in (python-installer.exe vc_redist.x64.exe) do (
        if exist "%PKG_DIR%\%%F" (
            call :copy_file "%PKG_DIR%\%%F" "%INSTALL_DIR%\%%F" "runtime installer %%F"
            if errorlevel 1 goto :copy_failed
        )
    )
)
echo         Files copied.

echo  [3/3] Running installer...
echo.
set "MYSQL_INSTALL_ARGS="
set "BUNDLED_MYSQL_PORT="
if exist "%PKG_DIR%\bundled-mysql-port.txt" set /p BUNDLED_MYSQL_PORT=<"%PKG_DIR%\bundled-mysql-port.txt"
if defined BUNDLED_MYSQL_PORT set "MYSQL_INSTALL_ARGS=--db-port %BUNDLED_MYSQL_PORT%"
if exist "%PKG_DIR%\use-existing-mysql.flag" set "MYSQL_INSTALL_ARGS=--use-existing-mysql --db-admin-user root"
call "%INSTALL_DIR%\install-win.bat" "%INSTALL_DIR%" %MYSQL_INSTALL_ARGS%
set "SETUP_RC=%ERRORLEVEL%"

REM 无论成败都把日志位置说清楚 —— 窗口关掉之后就找不回来了
call :log_rc "install-win.bat" %SETUP_RC%
call :log "===== setup.bat end ====="
echo.
echo  Logs:
echo    %INSTALL_DIR%\logs\setup.log       file copying (this script)
echo    %INSTALL_DIR%\logs\install-*.log   configuration
echo    %INSTALL_DIR%\logs\prereq.log      PowerShell bootstrap
if "%RUNTIME_DIR_NAME%"=="xampp" (
    echo    %INSTALL_DIR%\xampp\mysql\data\mysql-error.log
) else (
    echo    %INSTALL_DIR%\laragon\data\mysql-error.log
)
echo.
exit /b %SETUP_RC%

:log
>>"%SETUP_LOG%" echo [%DATE% %TIME%] %~1
exit /b 0

:log_rc
REM 退出码由调用方在顶层读好再传进来 —— 见上面关于 ( ) 块里 %ERRORLEVEL% 的说明
>>"%SETUP_LOG%" echo [%DATE% %TIME%] %~1 returned %~2
exit /b 0

REM xcopy 的错误必须留下来。
REM 原来是 /Q + >nul 2>&1：文件名和错误信息全丢，失败时只剩一句
REM 「could not be copied」，根本不知道是哪个文件被谁占着。
REM 现在保留 /Q（不然 2 万个文件名会把日志刷爆），但 stderr 进日志 ——
REM xcopy 的 "Access denied"/"Sharing violation" 都在 stderr 上，/Q 不影响它。
:copy_dir
call :log "xcopy %~1 -> %~2"
xcopy "%~1" "%~2\" /E /I /H /Y /Q >nul 2>>"%SETUP_LOG%"
if errorlevel 1 goto :copy_dir_failed
exit /b 0
:copy_dir_failed
echo  [ERROR] Failed to copy %~3.
echo          Source: %~1
echo          Target: %~2
call :log "FAILED: xcopy %~1 -> %~2"
exit /b 1

:copy_file
copy "%~1" "%~2" /Y >nul 2>>"%SETUP_LOG%"
if errorlevel 1 goto :copy_file_failed
exit /b 0
:copy_file_failed
echo  [ERROR] Failed to copy %~3.
echo          Source: %~1
echo          Target: %~2
call :log "FAILED: copy %~1 -> %~2"
exit /b 1

:copy_failed
echo.
echo  Setup stopped because package files could not be copied.
echo  Close programs using C:\DentalClinic files, then run setup.bat as Administrator.
echo.
echo  What exactly failed is recorded in:
echo    %SETUP_LOG%
echo.
call :log "===== setup.bat aborted at copy stage ====="
exit /b 1
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
    # OCR 脚本：整目录同步，不再逐个列文件名。
    #
    # 原先是硬编码 6 个文件名的白名单，结果漏了两个必需件：
    #   - paddle_compat.py    ocr_service.py 与 ocr_server.py 都 import 它，
    #                         缺了它 pip 装得再顺利，OCR 一跑就 ImportError；
    #   - worklog_ocr.py      PHP 侧直接调用。
    # 白名单这种写法每加一个文件就要记得同步，漏了还不报错 —— 换成目录同步。
    OCR_SCRIPTS_DIR="$DIST_DIR/scripts"
    mkdir -p "$OCR_SCRIPTS_DIR"
    rsync -a --delete \
        --exclude 'venv/' --exclude '__pycache__/' --exclude '*.pyc' \
        --exclude 'wheels/' \
        "$PROJECT_ROOT/scripts/" "$OCR_SCRIPTS_DIR/"
    chmod +x "$OCR_SCRIPTS_DIR"/*.sh 2>/dev/null || true
    info "复制 OCR 服务脚本（$(find "$OCR_SCRIPTS_DIR" -maxdepth 1 -type f | wc -l | tr -d ' ') 个文件）"

    # 必需件断言：缺任何一个，OCR 装完也跑不起来，且现场只会看到一句 ImportError
    for required in ocr_server.py ocr_service.py worklog_ocr.py paddle_compat.py requirements.txt; do
        [[ -f "$OCR_SCRIPTS_DIR/$required" ]] || fatal "OCR 脚本缺失: scripts/$required"
    done

    # 锁文件必须能被 pip 在**中文 Windows（GBK 区域）**下读出来。
    # pip 的 auto_decode 只认前两行的 coding 声明或 BOM，否则按区域编码解码；
    # 非 ASCII 且无声明 = 目标机上 UnicodeDecodeError，离线安装整个失败。
    for lock in "$OCR_SCRIPTS_DIR/requirements-lock.txt" "$OCR_SCRIPTS_DIR/requirements.txt"; do
        [[ -f "$lock" ]] || continue
        if ! python3 - "$lock" <<'LOCKCHK'
import sys, codecs, re
p = sys.argv[1]
data = open(p, 'rb').read()
BOMS = [(codecs.BOM_UTF8, 'utf-8'), (codecs.BOM_UTF16, 'utf-16'),
        (codecs.BOM_UTF16_BE, 'utf-16-be'), (codecs.BOM_UTF16_LE, 'utf-16-le')]
ENC = re.compile(rb"coding[:=]\s*([-\w.]+)")
for bom, enc in BOMS:
    if data.startswith(bom):
        data[len(bom):].decode(enc); sys.exit(0)
for line in data.split(b'\n')[:2]:
    if line[0:1] == b'#' and ENC.search(line):
        data.decode(ENC.search(line).groups()[0].decode('ascii')); sys.exit(0)
data.decode('gbk')   # 模拟中文 Windows 的回退路径
LOCKCHK
        then
            fatal "$(basename "$lock") 在 GBK 区域下无法被 pip 解码 —— 请把首行改成 '# -*- coding: utf-8 -*-' 或改为纯 ASCII"
        fi
    done
    info "OCR requirements 文件可被 pip 在 GBK 区域下解码"

    # ── 锁文件里的关键 pin 必须在 ────────────────────────────────────────
    # 这两个 pin 只在**首次生成**锁文件时由 PIP_EXTRA_REQUIREMENTS 加入
    # （见下方 `[[ ! -f "$OCR_LOCK_FILE" ]]` 那个判断）。锁文件一旦存在，
    # 后续构建就用 --no-deps 照单下载，不再做依赖解析 —— 所以任何一次在
    # 缺这两个 pin 的情况下生成的锁文件，会把错误**固化**下来且永不自动纠正。
    # 库里就这样带过 protobuf==5.29.6（违反 paddlepaddle 的 Windows 约束）
    # 且完全没有 exceptiongroup，目标机上 OCR 必崩。
    #   protobuf     paddlepaddle-2.6.2 的元数据：
    #                Requires-Dist: protobuf<=3.20.2,>=3.1.0; platform_system == "Windows"
    #   exceptiongroup  anyio-4.5.2 的元数据：
    #                Requires-Dist: exceptiongroup >=1.0.2 ; python_version < "3.11"
    #                目标机是 Python 3.8，而 --no-deps 不会替我们把它带出来。
    _lock="$PROJECT_ROOT/scripts/requirements-lock.txt"
    if [[ -f "$_lock" ]]; then
        # 用 fatal 而不是 ASSERT_FAIL：后者由「发布包校验」那一步维护，
        # 在这里赋值会被那一步开头的 ASSERT_FAIL=false 冲掉（实测构建仍返回 0）。
        # 而且 pin 错了等于 wheel 集合就是错的，没必要再往下打包。
        _pb=$(LC_ALL=C grep -E '^protobuf==' "$_lock" | head -1 | cut -d= -f3)
        [[ -n "$_pb" ]] || fatal "requirements-lock.txt 里没有 protobuf 版本"
        [[ "$_pb" == "3.20.2" ]] \
            || fatal "requirements-lock.txt 的 protobuf==$_pb 违反 paddlepaddle 2.6.2 的 Windows 约束（须 <=3.20.2），OCR 在目标机上会崩"
        LC_ALL=C grep -qE '^exceptiongroup==' "$_lock" \
            || fatal "requirements-lock.txt 缺 exceptiongroup —— Python 3.8 下 anyio 需要它，而 --no-deps 不会自动带出"
        info "OCR 锁文件关键 pin 校验通过（protobuf==$_pb + exceptiongroup）"
    fi

    # 升级包不带 Python 安装器：upgrade-win.bat 全文不引用它（也不装 Python），
    # 而它占 27MB。OCR 运行时由全量安装建立，升级只更新 scripts/ 里的 .py。
    if [[ "$TARGET" == "win" ]] && [[ "$SKIP_OCR" == false ]] && [[ "$UPGRADE" == false ]]; then
        # Win7 版：Python 3.8.10 是官方最后一个提供 Windows 7 安装器的版本
        # （3.9 起最低要求 Windows 8.1），切勿升级。
        PYTHON_INSTALLER_URL="${PYTHON_DOWNLOAD_URL:-https://www.python.org/ftp/python/3.8.10/python-3.8.10-amd64.exe}"
        # 缓存文件名带上版本号：旧缓存是 Python 3.11.9（PHP 8 时期留下的），
        # 若沿用固定文件名会把装不上 Win7 的 3.11 静默打进安装包。
        PYTHON_INSTALLER_CACHE="$PROJECT_ROOT/deploy/.cache/$(basename "$PYTHON_INSTALLER_URL")"
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
    # 升级包同样不带：342MB，而 upgrade-win.bat 不跑 pip，带了也没人用。
    if [[ "$UPGRADE" == true ]]; then
        info "跳过 OCR wheels 打包（升级包不重建 OCR 运行时依赖）"
    elif [[ "$SKIP_OCR" == true ]]; then
        info "跳过 OCR wheels 打包（--skip-ocr）"
    else
        OCR_WHEELS_DIR="$DIST_DIR/ocr-wheels"
        mkdir -p "$OCR_WHEELS_DIR"

        # 带缓存的下载。锁文件同样属于缓存输入：只改锁文件时必须让旧 wheel
        # 缓存失效，否则会继续把已经证明不兼容的 protobuf 等旧包打进去。
        OCR_CACHE_DIR="$PROJECT_ROOT/deploy/.cache/ocr-wheels-${TARGET}"
        OCR_LOCK_FILE="$PROJECT_ROOT/scripts/requirements-lock.txt"
        OCR_HASH_FILES=("$OCR_REQUIREMENTS")
        if [[ -f "$OCR_LOCK_FILE" ]]; then
            OCR_HASH_FILES+=("$OCR_LOCK_FILE")
        fi
        REQ_HASH=$(cksum "${OCR_HASH_FILES[@]}" | cksum | awk '{print $1 "-" $2}')
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
            PIP_EXTRA_REQUIREMENTS=()
            case "$TARGET" in
                win)
                    # 与 Win7 目标机上的 Python 3.8.10 保持一致
                    PIP_DOWNLOAD_ARGS=(
                        --platform win_amd64
                        --python-version 3.8
                        --only-binary=:all:
                    )
                    # 跨平台 pip download 会按构建机 Python 解释环境标记，可能漏掉
                    # Python 3.8 专用的 exceptiongroup；Paddle 2.6.2 又明确要求
                    # protobuf <=3.20.2。首次生成锁文件时显式钉住两者。
                    if [[ ! -f "$OCR_LOCK_FILE" ]]; then
                        PIP_EXTRA_REQUIREMENTS+=(exceptiongroup==1.2.2 protobuf==3.20.2)
                    fi
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
            if [[ -f "$OCR_LOCK_FILE" ]]; then
                PIP_REQ_FILE="$OCR_LOCK_FILE"
                PIP_DOWNLOAD_ARGS+=(--no-deps)
                info "使用锁定版本 (requirements-lock.txt)，跳过依赖解析"
            else
                PIP_REQ_FILE="$OCR_REQUIREMENTS"
            fi

            warn "正在下载 OCR Python wheels (目标: $TARGET)，首次下载需几分钟，后续构建使用缓存..."
            # 数组可能为空（有锁文件时 PIP_EXTRA_REQUIREMENTS 就是空的），
            # 而 bash 3.2（macOS 自带）在 set -u 下展开空数组会报 unbound
            # variable —— 4.4 才修。用 ${arr[@]+"${arr[@]}"} 这个惯用法兜住。
            # 这条只有在 wheels 缓存失效时才会走到，所以潜伏了很久：
            # 2026-08-08 因为给 requirements-lock.txt 加 coding 声明使缓存
            # 失效，才第一次暴露。
            if $PIP_CMD download \
                ${PIP_DOWNLOAD_ARGS[@]+"${PIP_DOWNLOAD_ARGS[@]}"} \
                -d "$OCR_WHEELS_DIR" \
                -r "$PIP_REQ_FILE" \
                ${PIP_EXTRA_REQUIREMENTS[@]+"${PIP_EXTRA_REQUIREMENTS[@]}"}; then
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

                # ── 生成锁定文件（仅 win，且当前没有锁文件时）
                # requirements.txt 只锁 5 个顶层包，传递依赖有 80 个且全部浮动；
                # 不落锁，两次干净构建拿到的 wheel 集合就可能不同，出问题无法复现。
                # 从这次实际下载到的 wheel 反推版本，来源即产物本身，不会写错。
                if [[ "$TARGET" == "win" ]] && [[ ! -f "$OCR_LOCK_FILE" ]] && command -v python3 &>/dev/null; then
                    if python3 - "$OCR_WHEELS_DIR" "$OCR_LOCK_FILE" <<'LOCKGEN'
import re, sys, pathlib
wheels_dir, lock_path = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])
pins = {}
for f in sorted(wheels_dir.iterdir()):
    if f.name.endswith('.whl'):
        parts = f.name[:-4].split('-')
        name, ver = parts[0], parts[1]
    elif f.name.endswith('.tar.gz'):
        name, _, ver = f.name[:-7].rpartition('-')
    else:
        continue
    pins[re.sub(r'[-_.]+', '-', name).lower()] = (name, ver)
if not pins:
    sys.exit(1)
header = (
    # 第一行必须是 coding 声明：pip 读 requirements 文件时，前两行没有
    # coding 声明也没有 BOM 就回退到系统区域编码。中文 Windows 上那是 GBK，
    # 下面的中文注释（UTF-8）会让 pip 抛 UnicodeDecodeError，离线安装全崩。
    # 2026-08-06 那次装机的 OCR 就是这样失败的。
    "# -*- coding: utf-8 -*-\n"
    "# OCR 依赖锁定文件 —— Windows 7 / Python 3.8.10 / win_amd64\n"
    "# 由 deploy/build.sh 在 pip download 成功后自动生成，请勿手工编辑。\n"
    "# 升级依赖：删除本文件与 deploy/.cache/ocr-wheels-win/ 后重新构建。\n"
    "# build.sh 检测到本文件会加 --no-deps，跳过依赖解析直接按版本下载。\n"
)
lock_path.write_text(header + "\n".join(f"{pins[k][0]}=={pins[k][1]}" for k in sorted(pins)) + "\n")
print(len(pins))
LOCKGEN
                    then
                        info "已生成 scripts/requirements-lock.txt（锁定传递依赖，请提交到版本库）"
                    else
                        warn "生成 requirements-lock.txt 失败，传递依赖仍未锁定"
                    fi
                fi
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
    warn "注意：laragon-wamp.exe 需要 Windows 10，且内置 PHP 8.x，不能用于 Win7 目标机"
fi

# ── 复制 XAMPP 运行环境（--runtime xampp）
# 目录名必须是 xampp —— install-win.ps1 以 {安装目录}\xampp 为根定位运行时，
# 且 XAMPP 自带配置里写死了 /xampp/... 的绝对路径（装机时由 setup_xampp.bat 重写）。
if [[ -n "$XAMPP_INSTALLER_SRC" ]] && [[ -f "$XAMPP_INSTALLER_SRC" ]]; then
    cp "$XAMPP_INSTALLER_SRC" "$DIST_DIR/xampp-installer.exe"
    info "复制 XAMPP 官方安装器（$(du -h "$DIST_DIR/xampp-installer.exe" | cut -f1)）"

    # VC++ 运行库同样必须带：XAMPP 的 Apache/PHP 是 VS16 构建依赖它，
    # 而官方安装器**不负责**装它。这一步原先只挂在「复制运行时文件树」的
    # 两个分支里，installer 模式没有文件树可复制，于是漏掉了 ——
    # 发布包校验（assert_exists vc_redist.x64.exe）当场拦下了。
    if [[ -s "$CACHE_DIR/vc_redist.x64.exe" ]]; then
        cp "$CACHE_DIR/vc_redist.x64.exe" "$DIST_DIR/vc_redist.x64.exe"
        info "复制 VC++ 2015-2022 x64 运行库"
    else
        fatal "缓存里没有 vc_redist.x64.exe —— XAMPP 的 Apache/PHP 起不来"
    fi
fi

if [[ -n "$XAMPP_RUNTIME_SRC" ]] && [[ -d "$XAMPP_RUNTIME_SRC" ]]; then
    cp -r "$XAMPP_RUNTIME_SRC" "$DIST_DIR/xampp"
    RUNTIME_SIZE=$(du -sh "$DIST_DIR/xampp" 2>/dev/null | cut -f1)
    info "复制 XAMPP 运行环境到安装包 ($RUNTIME_SIZE)"

    # VC++ 运行库：XAMPP 的 Apache/PHP 同样是 VS16 构建，依赖它
    if [[ -s "$CACHE_DIR/vc_redist.x64.exe" ]]; then
        cp "$CACHE_DIR/vc_redist.x64.exe" "$DIST_DIR/vc_redist.x64.exe"
        info "复制 VC++ 2015-2022 x64 运行库"
    fi
fi

# ── 复制自组装的 Win7 运行环境（PHP 8.2 / MySQL 5.7 / Nginx / Composer）
# 目录名必须是 laragon —— install-win.ps1 以 {安装目录}\laragon 为根定位运行时。
if [[ -n "$WIN7_RUNTIME_DIR" ]] && [[ -d "$WIN7_RUNTIME_DIR" ]]; then
    cp -r "$WIN7_RUNTIME_DIR" "$DIST_DIR/laragon"
    RUNTIME_SIZE=$(du -sh "$DIST_DIR/laragon" 2>/dev/null | cut -f1)
    info "复制 Win7 运行环境到安装包 ($RUNTIME_SIZE)"

    # VC++ 运行库（PHP VS16 构建的依赖）
    if [[ -s "$CACHE_DIR/vc_redist.x64.exe" ]]; then
        cp "$CACHE_DIR/vc_redist.x64.exe" "$DIST_DIR/vc_redist.x64.exe"
        info "复制 VC++ 2015-2022 x64 运行库"
    fi

fi

# ═══════════════════════════════════════════════════════════════════════
# Step N: 生成 setup.bat（Laragon 安装器模式）
# ═══════════════════════════════════════════════════════════════════════
if [[ "$TARGET" == "win" ]] && [[ -n "$LARAGON_INSTALLER_EXE" ]]; then
    cat > "$DIST_DIR/setup.bat" <<'LARAGON_INSTALLER_BAT'
@echo off
setlocal EnableExtensions DisableDelayedExpansion
chcp 936 >nul 2>&1

REM 同上：块内 %ERRORLEVEL% 在 DisableDelayedExpansion 下取不到 powershell
REM 的实际退出码，必须摊平到顶层。
net session >nul 2>&1
if not errorlevel 1 goto :have_admin
echo  需要管理员权限，请在 UAC 提示中选择「是」...
powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -LiteralPath '%~f0' -Verb RunAs -Wait"
set "ELEV_RC=%ERRORLEVEL%"
exit /b %ELEV_RC%
:have_admin

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

REM 同上：判目录存在，不判 errorlevel。
REM 这一处更容易踩到 —— 上面那行 set /p 用户直接回车（接受默认路径）时
REM 就会把 errorlevel 置成 1，安装目录又已存在的话必然误报。
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"
if not exist "%INSTALL_DIR%" (
    echo  [ERROR] Failed to create install directory.
    pause
    exit /b 1
)

REM 目标目录随运行时形态而变（同 setup.bat 的判定）
set "APP_ROOT=%INSTALL_DIR%\laragon\www\dental"
if exist "%~dp0xampp\apache\bin\httpd.exe" set "APP_ROOT=%INSTALL_DIR%\xampp\htdocs\dental"

echo  [1/4] Copying application files...
xcopy "%~dp0app" "%APP_ROOT%\app\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0bootstrap" "%APP_ROOT%\bootstrap\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0config" "%APP_ROOT%\config\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0database" "%APP_ROOT%\database\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0public" "%APP_ROOT%\public\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0resources" "%APP_ROOT%\resources\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0routes" "%APP_ROOT%\routes\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0storage" "%APP_ROOT%\storage\" /E /I /H /Y /Q >nul 2>&1
xcopy "%~dp0vendor" "%APP_ROOT%\vendor\" /E /I /H /Y /Q >nul 2>&1
if exist "%~dp0scripts" xcopy "%~dp0scripts" "%APP_ROOT%\scripts\" /E /I /H /Y /Q >nul 2>&1
copy "%~dp0artisan" "%APP_ROOT%\" /Y >nul 2>&1
copy "%~dp0composer.json" "%APP_ROOT%\" /Y >nul 2>&1
copy "%~dp0composer.lock" "%APP_ROOT%\" /Y >nul 2>&1
copy "%~dp0.env.deploy" "%APP_ROOT%\.env.deploy" /Y >nul 2>&1
copy "%~dp0VERSION" "%APP_ROOT%\" /Y >nul 2>&1
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
# Step N: 打包前内容断言
#
# 前面每一步缺件时大多只是 warn，于是可以一路「成功」地产出一个装不上的包。
# 这里是最后一道闸：Win7 全量包必须齐的东西，缺一样就失败，不许出包。
# ═══════════════════════════════════════════════════════════════════════
if [[ "$TARGET" == "win" ]] && [[ "$UPGRADE" != true ]]; then
    step "校验发布包内容"

    ASSERT_FAIL=false
    assert_exists() {   # <路径> <说明>
        if [[ -e "$DIST_DIR/$1" ]]; then
            info "  ✓ $2"
        else
            error "  ✗ 缺失: $1（$2）"; ASSERT_FAIL=true
        fi
    }
    # 纯净 Win7 只要求系统自带 PowerShell 2.0，不再携带或安装 Windows 更新包。
    assert_exists     "vc_redist.x64.exe"   "VC++ 2015-2022 x64 运行库"
    # 运行时布局按形态区分：Laragon 是 laragon/bin/{php,mysql}，
    # XAMPP 是扁平的 xampp/{php,mysql,apache}（没有版本号子目录）。
    if [[ "$RUNTIME_FLAVOR" == "xampp-installer" ]]; then
        # 这条路只能验安装器本身：里面装出什么，构建机看不见（见
        # prepare-xampp-installer.sh 顶部关于这项代价的说明）。
        assert_exists "xampp-installer.exe"           "XAMPP 官方安装器"
        if [[ -f "$DIST_DIR/xampp-installer.exe" ]]; then
            _sz=$(du -m "$DIST_DIR/xampp-installer.exe" | cut -f1)
            if [[ "$_sz" -lt 100 ]]; then
                error "  ✗ xampp-installer.exe 只有 ${_sz}MB，明显不完整（应约 150MB）"
                ASSERT_FAIL=true
            fi
        fi
        if [[ -d "$DIST_DIR/xampp" ]]; then
            error "  ✗ installer 模式下不该同时铺 xampp/ 文件树"
            ASSERT_FAIL=true
        fi
    elif [[ "$RUNTIME_FLAVOR" == "xampp" ]]; then
        assert_exists "xampp/php/php.exe"              "PHP 运行时（XAMPP）"
        assert_exists "xampp/php/php8ts.dll"           "PHP TS 运行库（mod_php 依赖）"
        assert_exists "xampp/php/php8apache2_4.dll"    "mod_php 模块"
        assert_exists "xampp/apache/bin/httpd.exe"     "Apache"
        assert_exists "xampp/mysql/bin/mysqld.exe"     "MariaDB"
        assert_exists "xampp/setup_xampp.bat"          "XAMPP 路径重写脚本"
    else
        assert_exists "laragon/bin/php"     "PHP 运行时"
        assert_exists "laragon/bin/mysql"   "MySQL 运行时"
    fi
    assert_exists     "install-win.bat"     "安装脚本"
    assert_exists     "install-win.ps1"     "配置脚本"

    # setup.bat 必须允许安装包直接解压到 C:\DentalClinic 后重复执行。
    # 回归测试验证同目录检测、跳过自复制、复制错误显式失败三件事。
    # setup.bat 不能写死运行时目录名 —— XAMPP 包会在「Missing laragon runtime」
    # 上直接失败，连第一步都过不去（真实发生过）。
    if ! bash "$PROJECT_ROOT/deploy/tests/test-setup-runtime-flavor.sh"; then
        error "  ✗ setup.bat 运行时形态无感测试失败"
        ASSERT_FAIL=true
    else
        info "  ✓ setup.bat 对运行时形态无感"
    fi

    if ! bash "$PROJECT_ROOT/deploy/tests/test-setup-rerun.sh"; then
        error "  ✗ setup.bat 重复执行回归测试失败"
        ASSERT_FAIL=true
    else
        info "  ✓ setup.bat 可安全重复执行"
    fi

    # 脚本必须是当前版本，不能是残留的旧副本。--selftest 是最近加的，
    # 拿它当版本水印：缺了就说明 dist 里混进了过时的 BAT。
    if [[ -f "$DIST_DIR/install-win.bat" ]] && ! grep -q -- "--selftest" "$DIST_DIR/install-win.bat" 2>/dev/null; then
        error "  ✗ install-win.bat 不含 --selftest —— dist 里是过时的副本"
        ASSERT_FAIL=true
    fi

    # PowerShell 2.0 回归门禁。::new() 需要 PS5，*> 需要 PS3；任何一个回归都会
    # 让纯净 Win7 在解析 install-win.ps1 时直接失败。
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && grep -qF '::new(' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 含 PS5 专用 ::new() 语法"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && grep -qF '*>' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 含 PS3 专用 *> 重定向"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && grep -qE '(^|[[:space:]])-WindowStyle[[:space:]]' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 使用 PowerShell 2 不兼容的 Start-Process -WindowStyle 参数组合"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && grep -qF '[string[]]$Args' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 使用 PowerShell 自动变量 \$args 保存 MySQL 连接参数"
        ASSERT_FAIL=true
    fi
    # PowerShell 2.0 + ErrorActionPreference=Stop 时，直接把原生命令的 stderr
    # 合并到 stdout 会在读取 LASTEXITCODE 之前终止脚本。只允许在
    # Invoke-Native* 包装器内部使用这种重定向。
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && awk '/^[[:space:]]*& / && /2>&1/ && !/\$FilePath/ { found=1 } END { exit !found }' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 含未包装的原生 stderr 合并，会在 PowerShell 2.0 中误终止"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && ! grep -qF 'function Invoke-NativeQuiet' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 缺少 PowerShell 2.0 原生命令兼容包装器"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && ! grep -qF 'DentalClinic-ServiceWatchdog' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 未注册 Win7 后台服务健康检查任务"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/start-win.bat" ]] && ! grep -qF 'PHP FastCGI' "$DIST_DIR/start-win.bat"; then
        error "  ✗ start-win.bat 未启动 PHP FastCGI，Nginx 将无法执行 PHP"
        ASSERT_FAIL=true
    fi
    # 打包后 .bat 为 GBK；任何 chcp 65001 都会在中文 Win7 上拆坏多字节命令
    while IFS= read -r -d '' _bat_chk; do
        if grep -qE 'chcp[[:space:]]+65001' "$_bat_chk"; then
            error "  ✗ $(basename "$_bat_chk") 仍含 chcp 65001（必须与 GBK 打包一致用 936）"
            ASSERT_FAIL=true
        fi
    done < <(find "$DIST_DIR" -maxdepth 1 -name '*.bat' -print0 2>/dev/null)
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && grep -qE '\[System\.IO\.File\]::WriteAllLines' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 仍调用 File.WriteAllLines（.NET 4+ Encoding 重载，Win7 纯净机可能失败）"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && ! grep -qF 'APP_KEY preserved' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 未保留已有 APP_KEY 的合并路径"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/laragon-startup.bat" ]] && grep -qF 'localhost/dental' "$DIST_DIR/laragon-startup.bat"; then
        error "  ✗ laragon-startup.bat 仍打开 /dental（站点 root 已是 public）"
        ASSERT_FAIL=true
    fi
    if [[ ! -f "$DIST_DIR/batch-helpers/write_nginx_main_conf.php" ]] || ! grep -qF 'worker_processes' "$DIST_DIR/batch-helpers/write_nginx_main_conf.php"; then
        error "  ✗ Nginx 主配置生成器不完整"
        ASSERT_FAIL=true
    fi
    if [[ -f "$DIST_DIR/install-win.ps1" ]] && ! grep -qF '__DENTAL_DB_PASSWORD_EMPTY__' "$DIST_DIR/install-win.ps1"; then
        error "  ✗ install-win.ps1 未处理 Windows PowerShell 2.0 的空密码环境变量语义"
        ASSERT_FAIL=true
    fi
    for env_helper in install_render_env.php install_update_env.php; do
        if [[ -f "$DIST_DIR/batch-helpers/$env_helper" ]] && ! grep -qF '__DENTAL_DB_PASSWORD_EMPTY__' "$DIST_DIR/batch-helpers/$env_helper"; then
            error "  ✗ $env_helper 未解析空数据库密码哨兵"
            ASSERT_FAIL=true
        fi
    done
    if [[ -f "$DIST_DIR/use-existing-mysql.flag" ]]; then
        if ! grep -qF -- '--use-existing-mysql --db-admin-user root' "$DIST_DIR/setup.bat"; then
            error "  ✗ 现有 MySQL 安装包没有把安全模式参数传给安装脚本"
            ASSERT_FAIL=true
        fi
        if grep -qiE 'taskkill .*/im mysqld\.exe' "$DIST_DIR/setup.bat"; then
            error "  ✗ 现有 MySQL 安装包仍会全局终止 mysqld.exe"
            ASSERT_FAIL=true
        fi
        for lifecycle_script in start-win.bat stop-win.bat uninstall-win.bat; do
            if ! grep -qF 'existing-mysql.conf' "$DIST_DIR/$lifecycle_script"; then
                error "  ✗ $lifecycle_script 未识别现有 MySQL 生命周期标记"
                ASSERT_FAIL=true
            fi
        done
        if ! grep -qF 'MYSQL_CHECK_RC' "$DIST_DIR/start-win.bat" || ! grep -qF 'SELECT 1' "$DIST_DIR/start-win.bat"; then
            error "  ✗ start-win.bat 未在启动应用前检查现有 MySQL 连接"
            ASSERT_FAIL=true
        fi
    fi
    if [[ -f "$DIST_DIR/bundled-mysql-port.txt" ]]; then
        if ! grep -qF 'bundled-mysql-port.txt' "$DIST_DIR/setup.bat" || ! grep -qF -- '--db-port %BUNDLED_MYSQL_PORT%' "$DIST_DIR/setup.bat"; then
            error "  ✗ 独立端口安装包没有把 MySQL 端口传给安装脚本"
            ASSERT_FAIL=true
        fi
        if ! grep -qF 'bundled-mysql.conf' "$DIST_DIR/install-win.ps1"; then
            error "  ✗ 独立端口安装包未记录内置 MySQL 生命周期标记"
            ASSERT_FAIL=true
        fi
        if ! grep -qF "'bind-address=127.0.0.1'" "$DIST_DIR/install-win.ps1" || ! grep -qF 'Set-Content -Path $mysqlIni' "$DIST_DIR/install-win.ps1"; then
            error "  ✗ 独立端口安装包不会生成仅监听本机的 my.ini"
            ASSERT_FAIL=true
        fi
        if ! grep -qF 'Refusing to clear a live MySQL data directory' "$DIST_DIR/install-win.ps1"; then
            error "  ✗ 独立端口安装包可能在覆盖安装时清理仍在运行的 MySQL 数据目录"
            ASSERT_FAIL=true
        fi
        if grep -qiE 'taskkill .*/im mysqld\.exe' "$DIST_DIR/stop-win.bat"; then
            error "  ✗ stop-win.bat 仍会全局终止 mysqld.exe"
            ASSERT_FAIL=true
        fi
        if grep -qF 'Get-Process -Name "mysqld"' "$DIST_DIR/install-win.ps1"; then
            error "  ✗ install-win.ps1 仍会按进程名停止全部 mysqld"
            ASSERT_FAIL=true
        fi
        for lifecycle_script in start-win.bat stop-win.bat uninstall-win.bat; do
            if ! grep -qF 'DentalClinicMySQL' "$DIST_DIR/$lifecycle_script"; then
                error "  ✗ $lifecycle_script 未限定为 DentalClinicMySQL 服务"
                ASSERT_FAIL=true
            fi
        done
    fi
    if [[ -f "$DIST_DIR/install-win.bat" ]] && grep -qiE 'wusa\.exe|KB3191566|dotnet48|win7-prereq' "$DIST_DIR/install-win.bat"; then
        error "  ✗ install-win.bat 仍包含 Windows Update / WMF / .NET 硬前置"
        ASSERT_FAIL=true
    fi

    if [[ "$ASSERT_FAIL" == true ]]; then
        fatal "发布包内容校验未通过，拒绝产出装不上的安装包（详见上方 ✗ 项）"
    fi
    info "发布包内容校验通过"
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
ARCHIVE_ROOT_NAME="dental-clinic-${VERSION}-${SUFFIX}-${BUILD_ID}"

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
#
# --keep-dist 必须保留：build-installer.iss 的每一条 Source 都指向 deploy/dist/，
# 删掉之后 Inno Setup Compiler 直接报找不到文件，等于 .exe 安装包根本编不出来。
if [[ "$KEEP_DIST" == true ]]; then
    info "保留 dist/ 目录（--keep-dist）：$DIST_DIR"
    echo -e "  ${CYAN}下一步（编译 .exe 安装包）:${NC}"
    echo -e "    用 Inno Setup Compiler 打开 deploy/build-installer.iss → Compile"
else
    rm -rf "$DIST_DIR"
    info "已清理 dist/ 临时目录（编译 Inno 安装包请改用 --keep-dist）"
fi
echo ""
