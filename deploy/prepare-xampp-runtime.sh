#!/usr/bin/env bash
# ============================================================================
#  准备 XAMPP portable 运行时（Win7 目标）
#
#  为什么单独成脚本而不是塞进 build.sh：
#    build.sh 里组装 Laragon 运行时那一段（下载 PHP/MySQL/Nginx/composer 再拼成
#    Laragon 目录布局）有 200 多行。XAMPP 走的是完全不同的路径 —— 官方提供
#    portable 压缩包，解压即用，不需要逐件组装。两套逻辑混在一个函数里会很难读，
#    也难以单独测试。build.sh 只需要在选择 xampp 运行时时调用本脚本。
#
#  为什么用 portable 包而不是 installer：
#    build.sh 的注释已经写明，当初避开 laragon-wamp.exe 正是因为**它的安装器
#    要求 Windows 10**。XAMPP 的 installer .exe 同样是 Windows 安装程序，
#    在构建机（macOS/Linux）上根本跑不了，也无法在 Win7 上无人值守展开。
#    portable zip 是纯文件，任何平台都能解压。
#
#  Win7 兼容性依据：
#    XAMPP 官方下载页明确列出支持 "Windows 2008, 2012, Vista, 7, 8"
#    （仅排除 XP / 2003）。8.2.12 带的是 Apache 2.4.58 + MariaDB 10.4.32 +
#    PHP 8.2.12（VS16 构建，与现方案的 PHP 8.2.33 同一工具链）。
#
#  用法:
#    ./deploy/prepare-xampp-runtime.sh [输出目录]
#      不给输出目录时只准备缓存，不复制。
#  环境变量:
#    XAMPP_DOWNLOAD_URL  覆盖下载地址（覆盖后跳过指纹校验，仅告警）
# ============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()  { echo -e "${CYAN}[INFO]${NC}  $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
fatal() { echo -e "${RED}[FAIL]${NC}  $*" >&2; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CACHE_DIR="$SCRIPT_DIR/.cache"
OUT_DIR="${1:-}"

# 版本锁定。改版本时必须同步更新 URL 与指纹，并重新确认 Win7 兼容性。
XAMPP_VERSION="8.2.12"
XAMPP_FILE="xampp-portable-windows-x64-${XAMPP_VERSION}-0-VS16.zip"
XAMPP_URL="${XAMPP_DOWNLOAD_URL:-https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/${XAMPP_VERSION}/${XAMPP_FILE}/download}"
XAMPP_URL_OVERRIDDEN=$([[ -n "${XAMPP_DOWNLOAD_URL:-}" ]] && echo true || echo false)

# shasum -a 256 <文件> 取得；留空表示尚未锁定（首次下载后填入）
XAMPP_SHA256="ce3bdf852bd62c7363cb51d66e709b6a9bf5f3ea59bc1712ffda11d9238e5651"

XAMPP_CACHE_ZIP="$CACHE_DIR/$XAMPP_FILE"
XAMPP_RUNTIME_DIR="$CACHE_DIR/xampp-runtime"
# 与 build.sh 的 .build-manifest 同一思路：把产出所依据的地址落盘，
# 复用前逐行比对，任何一项漂移即重建。只查「有没有 httpd.exe」挡不住版本漂移。
XAMPP_MANIFEST="$XAMPP_RUNTIME_DIR/.build-manifest"
XAMPP_MANIFEST_EXPECTED="schema=xampp-1
version=$XAMPP_VERSION
url=$XAMPP_URL"

sha256_of() {
    if command -v sha256sum &>/dev/null; then
        sha256sum "$1" 2>/dev/null | awk '{print $1}'
    elif command -v shasum &>/dev/null; then
        shasum -a 256 "$1" 2>/dev/null | awk '{print $1}'
    fi
}

mkdir -p "$CACHE_DIR"

# ── 缓存复用判定 ────────────────────────────────────────────────────────────
REUSE=false
if [[ -f "$XAMPP_MANIFEST" ]] \
   && [[ "$(cat "$XAMPP_MANIFEST")" == "$XAMPP_MANIFEST_EXPECTED" ]] \
   && [[ -f "$XAMPP_RUNTIME_DIR/xampp/apache/bin/httpd.exe" ]] \
   && [[ -f "$XAMPP_RUNTIME_DIR/xampp/php/php.exe" ]] \
   && [[ -f "$XAMPP_RUNTIME_DIR/xampp/mysql/bin/mysqld.exe" ]]; then
    REUSE=true
fi

if [[ "$REUSE" == true ]]; then
    ok "复用已解压的 XAMPP 运行时: $XAMPP_RUNTIME_DIR"
else
    [[ -d "$XAMPP_RUNTIME_DIR" ]] && { warn "丢弃无法确认版本的 XAMPP 缓存并重新解压"; rm -rf "$XAMPP_RUNTIME_DIR"; }

    # ── 下载 ────────────────────────────────────────────────────────────────
    if [[ -s "$XAMPP_CACHE_ZIP" ]]; then
        info "使用已缓存的安装包: $XAMPP_CACHE_ZIP"
    else
        info "下载 XAMPP ${XAMPP_VERSION} portable（约 216 MB）..."
        # -L 跟随 SourceForge 的 302 跳转到实际镜像
        if command -v curl &>/dev/null; then
            curl -fSL --progress-bar --retry 3 --retry-delay 5 -o "$XAMPP_CACHE_ZIP.part" "$XAMPP_URL" \
                || fatal "XAMPP 下载失败: $XAMPP_URL"
        elif command -v wget &>/dev/null; then
            wget -q --show-progress --tries=3 -O "$XAMPP_CACHE_ZIP.part" "$XAMPP_URL" \
                || fatal "XAMPP 下载失败: $XAMPP_URL"
        else
            fatal "需要 curl 或 wget"
        fi
        # 下载完才改名：中断产生的半个文件不会被下次当成有效缓存
        mv "$XAMPP_CACHE_ZIP.part" "$XAMPP_CACHE_ZIP"
    fi

    # ── 指纹校验 ────────────────────────────────────────────────────────────
    ACTUAL_SHA="$(sha256_of "$XAMPP_CACHE_ZIP")"
    if [[ -z "$XAMPP_SHA256" ]]; then
        warn "尚未锁定 SHA256 指纹。本次实测值如下，确认无误后填入本脚本 XAMPP_SHA256："
        warn "  $ACTUAL_SHA"
    elif [[ "$XAMPP_URL_OVERRIDDEN" == true ]]; then
        warn "下载地址被 XAMPP_DOWNLOAD_URL 覆盖，跳过指纹校验（实测 ${ACTUAL_SHA}）"
    elif [[ "$ACTUAL_SHA" != "$XAMPP_SHA256" ]]; then
        fatal "XAMPP 指纹不匹配
  期望: $XAMPP_SHA256
  实际: $ACTUAL_SHA
包可能损坏或被替换。确认来源后删除 $XAMPP_CACHE_ZIP 重试。"
    else
        ok "SHA256 指纹校验通过"
    fi

    # ── 解压 ────────────────────────────────────────────────────────────────
    command -v unzip &>/dev/null || fatal "需要 unzip"
    info "解压中（文件数多，需要几分钟）..."
    mkdir -p "$XAMPP_RUNTIME_DIR"
    unzip -q -o "$XAMPP_CACHE_ZIP" -d "$XAMPP_RUNTIME_DIR" || fatal "解压失败"

    [[ -f "$XAMPP_RUNTIME_DIR/xampp/apache/bin/httpd.exe" ]] \
        || fatal "解压结果里找不到 xampp/apache/bin/httpd.exe，压缩包结构与预期不符"

    printf '%s\n' "$XAMPP_MANIFEST_EXPECTED" > "$XAMPP_MANIFEST"
    ok "XAMPP 运行时就绪: $XAMPP_RUNTIME_DIR"
fi

# ── 关键路径自检 ────────────────────────────────────────────────────────────
# install-win.ps1 要按这些路径发现运行时，缺一个都装不起来，构建期就卡住。
info "校验关键组件："
MISSING=0
while IFS='|' read -r rel desc; do
    [[ -z "$rel" ]] && continue
    if [[ -e "$XAMPP_RUNTIME_DIR/$rel" ]]; then
        printf "    %-46s %s\n" "$rel" "OK"
    else
        printf "    %-46s %s\n" "$rel" "缺失（${desc}）"
        MISSING=$((MISSING + 1))
    fi
done <<'COMPONENTS'
xampp/apache/bin/httpd.exe|Apache 主程序
xampp/apache/conf/httpd.conf|Apache 主配置
xampp/apache/conf/extra/httpd-vhosts.conf|vhost 引入点
xampp/php/php.exe|PHP CLI
xampp/php/php.ini|PHP 配置
xampp/mysql/bin/mysqld.exe|MariaDB 服务端
xampp/mysql/bin/mysql.exe|MariaDB 客户端
xampp/mysql/bin/my.ini|MariaDB 配置
COMPONENTS

[[ "$MISSING" -gt 0 ]] && fatal "缺少 $MISSING 个关键组件，XAMPP 包结构与预期不符"

# mod_php 是本方案的核心：没有它就还得回去维护 php-cgi 进程。
#
# 注意路径：XAMPP 把 Apache 的 PHP 模块放在 **php/** 目录下，不在 apache/modules/。
# httpd-xampp.conf 里是 `LoadModule php_module "/xampp/php/php8apache2_4.dll"`。
# （第一版按 apache/modules/php*apache*.dll 找，实测直接报缺失——是检查写错了，不是包缺东西。）
MOD_PHP="$(ls "$XAMPP_RUNTIME_DIR"/xampp/php/php*apache*.dll 2>/dev/null | head -1 || true)"
[[ -n "$MOD_PHP" ]] && ok "mod_php: $(basename "$MOD_PHP")" \
                    || fatal "找不到 mod_php（php/php*apache*.dll），Apache 无法内嵌 PHP"

# mod_php 必须配 TS（线程安全）构建的 PHP。现方案打的是 php-8.2.33-**nts**，
# 那是给 FastCGI 用的，装不了 mod_php —— 这也是换 XAMPP 顺带解决的问题之一。
[[ -f "$XAMPP_RUNTIME_DIR/xampp/php/php8ts.dll" ]] \
    && ok "PHP 运行时: php8ts.dll（线程安全，mod_php 要求）" \
    || fatal "找不到 php8ts.dll —— PHP 不是 TS 构建，mod_php 无法工作"

# XAMPP 的配置文件里写的是 /xampp/... 这种从盘符根开始的绝对路径。
# 装到 C:\DentalClinic\xampp 之后由 install-win.ps1 的 Repair-XamppHardcodedPaths 重写
# （不用 XAMPP 自带的 setup_xampp.bat：它靠相对路径找 php.exe，失败还返回 0）。
# 这里只校验「重写目标存在 + 被引用的文件真的在包里」。
XAMPP_PATH_TARGETS=0
for rel in xampp/php/php.ini xampp/mysql/bin/my.ini xampp/apache/conf/httpd.conf \
           xampp/apache/conf/extra/httpd-xampp.conf xampp/apache/conf/extra/httpd-ssl.conf; do
    [[ -f "$XAMPP_RUNTIME_DIR/$rel" ]] && XAMPP_PATH_TARGETS=$((XAMPP_PATH_TARGETS + 1))
done
[[ "$XAMPP_PATH_TARGETS" -ge 4 ]] \
    && ok "待重写的配置文件就位（$XAMPP_PATH_TARGETS 个，安装时改写内置 /xampp/... 路径）" \
    || fatal "待重写的配置文件不全（只找到 $XAMPP_PATH_TARGETS 个），包结构与预期不符"

# php.ini 的 browscap 指向 php/extras/browscap.ini。这个文件缺失不是警告，
# 而是 php.exe 直接 "Unable to start standard module" 起不来 ——
# 2026-08-04 那次装机就死在这里（当时是路径没重写，文件其实在）。
[[ -f "$XAMPP_RUNTIME_DIR/xampp/php/extras/browscap.ini" ]] \
    && ok "browscap.ini 就位（php.ini 引用它，缺失会让 php.exe 启动失败）" \
    || fatal "找不到 php/extras/browscap.ini —— php.ini 引用了它，php.exe 会以 Unable to start standard module 退出"

info "体积: $(du -sh "$XAMPP_RUNTIME_DIR" | awk '{print $1}')"

# ── 复制到输出目录 ──────────────────────────────────────────────────────────
if [[ -n "$OUT_DIR" ]]; then
    info "复制到 $OUT_DIR ..."
    mkdir -p "$OUT_DIR"
    cp -R "$XAMPP_RUNTIME_DIR/xampp" "$OUT_DIR/"
    ok "已复制"
fi
