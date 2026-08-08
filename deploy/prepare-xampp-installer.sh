#!/usr/bin/env bash
# ============================================================================
#  准备 XAMPP 官方安装器（Win7 目标 / --runtime xampp-installer）
#
#  与 prepare-xampp-runtime.sh 的关系：
#    两者产出同一个 XAMPP 8.2.12，但交付方式不同。
#      prepare-xampp-runtime.sh  下载 portable zip，解压成文件树随包发出，
#                                装机时由 install-win.ps1 重写其中 67 处
#                                写死的 \xampp\... 路径。
#      本脚本                    下载官方 installer.exe 原样随包发出，
#                                装机时在目标机静默安装到 C:\xampp。
#                                因为落点就是 XAMPP 默认的 C:\xampp，
#                                那 67 处硬编码路径天生就是对的，不需要重写。
#
#  代价（选这条路前请知悉）：
#    portable 方案在**构建期**就能断言 mod_php、php8ts.dll（TS 构建）、
#    browscap.ini、8 个配置文件都在，还能锁运行时的 SHA256。
#    installer 是个压缩黑盒，构建机（macOS/Linux）跑不了它，
#    所以这里只能校验安装器本身的 SHA256 —— 里面到底装出什么，
#    只有目标机跑完才知道。
#
#  Win7 兼容性依据（实测，非文档推断）：
#    安装器内嵌的 application manifest 里 supportedOS 同时声明了
#    Vista / 7 / 8 / 8.1 / 10，没有 Windows 版本门禁。
#
#  无人值守依据：
#    二进制里含 <description>BitRock Installer</description> 与 ::bitrock_*
#    过程，属 BitRock/InstallBuilder。该家族按设计支持
#      --mode unattended --unattendedmodeui none --prefix <dir>
#    注意：这些选项字符串在二进制里搜不到，因为 Tcl 载荷是压缩的 ——
#    「它是 BitRock」是实测的，「这个二进制确实接受这些参数」只能在目标机验。
#
#  用法:
#    ./deploy/prepare-xampp-installer.sh [输出目录]
#      不给输出目录时只准备缓存，不复制。
#  环境变量:
#    XAMPP_INSTALLER_URL  覆盖下载地址（覆盖后跳过指纹校验，仅告警）
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

# 版本锁定。与 prepare-xampp-runtime.sh 保持同一版本，便于两条路互相回退。
XAMPP_VERSION="8.2.12"
XAMPP_FILE="xampp-windows-x64-${XAMPP_VERSION}-0-VS16-installer.exe"
XAMPP_URL="${XAMPP_INSTALLER_URL:-https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/${XAMPP_VERSION}/${XAMPP_FILE}/download}"
XAMPP_URL_OVERRIDDEN=$([[ -n "${XAMPP_INSTALLER_URL:-}" ]] && echo true || echo false)

# shasum -a 256 <文件> 取得
XAMPP_SHA256="12e818ce5aec79fe646606df3a80b35da865ec0213646ad7c92044dcfcec7535"

XAMPP_CACHE_EXE="$CACHE_DIR/$XAMPP_FILE"

sha256_of() {
    if command -v sha256sum &>/dev/null; then
        sha256sum "$1" 2>/dev/null | awk '{print $1}'
    elif command -v shasum &>/dev/null; then
        shasum -a 256 "$1" 2>/dev/null | awk '{print $1}'
    fi
}

mkdir -p "$CACHE_DIR"

# ── 下载（带缓存）────────────────────────────────────────────────────────────
if [[ -f "$XAMPP_CACHE_EXE" ]] && [[ -s "$XAMPP_CACHE_EXE" ]]; then
    info "复用缓存: $XAMPP_CACHE_EXE ($(du -h "$XAMPP_CACHE_EXE" | cut -f1))"
else
    info "下载 XAMPP 安装器 ${XAMPP_VERSION}（约 150MB）..."
    if command -v curl &>/dev/null; then
        curl -fSL --progress-bar --retry 3 --retry-delay 5 -o "$XAMPP_CACHE_EXE.part" "$XAMPP_URL" \
            || fatal "XAMPP 安装器下载失败: $XAMPP_URL"
    elif command -v wget &>/dev/null; then
        wget -q --show-progress --tries=3 -O "$XAMPP_CACHE_EXE.part" "$XAMPP_URL" \
            || fatal "XAMPP 安装器下载失败: $XAMPP_URL"
    else
        fatal "需要 curl 或 wget 才能下载 XAMPP 安装器"
    fi
    mv "$XAMPP_CACHE_EXE.part" "$XAMPP_CACHE_EXE"
    ok "下载完成"
fi

# ── 指纹校验 ────────────────────────────────────────────────────────────────
ACTUAL_SHA="$(sha256_of "$XAMPP_CACHE_EXE")"
if [[ "$XAMPP_URL_OVERRIDDEN" == true ]]; then
    warn "已用 XAMPP_INSTALLER_URL 覆盖下载地址，跳过指纹校验（实际 SHA256: $ACTUAL_SHA）"
elif [[ -z "$ACTUAL_SHA" ]]; then
    warn "本机没有 sha256sum/shasum，无法校验指纹"
elif [[ "$ACTUAL_SHA" != "$XAMPP_SHA256" ]]; then
    fatal "XAMPP 安装器指纹不符
  期望: $XAMPP_SHA256
  实际: $ACTUAL_SHA
  删除 $XAMPP_CACHE_EXE 后重试；若确属官方新版本，请同步更新本脚本里的 XAMPP_SHA256。"
else
    ok "指纹校验通过: $ACTUAL_SHA"
fi

# ── 结构校验：构建期能查的就这么多，如实说明 ────────────────────────────────
[[ "$(head -c 2 "$XAMPP_CACHE_EXE")" == "MZ" ]] \
    || fatal "不是 Windows 可执行文件（缺 MZ 头），下载可能被劫持或损坏"
ok "PE 可执行文件"

# BitRock 标识 —— 无人值守安装依赖它
if command -v strings &>/dev/null; then
    # 两个坑都躲开：
    #   LC_ALL=C —— 二进制里有非 UTF-8 字节，默认 locale 下 grep 会报
    #               illegal byte sequence 直接放弃，把「找到了」误判成「没找到」。
    #   grep -c 而非 -q —— 本脚本是 set -euo pipefail，-q 命中后立即退出会让
    #               strings 收到 SIGPIPE，pipefail 于是把整条管道判成失败。
    #               -c 读完全部输入，不会提前关闭管道。
    BITROCK_HITS="$(LC_ALL=C strings -a "$XAMPP_CACHE_EXE" 2>/dev/null | LC_ALL=C grep -c 'BitRock Installer' || true)"
    if [[ "${BITROCK_HITS:-0}" -gt 0 ]]; then
        ok "BitRock Installer（支持 --mode unattended）"
    else
        fatal "未在安装器里找到 BitRock 标识 —— 无人值守安装的前提不成立"
    fi
    # Win7 支持：内嵌 manifest 的 supportedOS 声明
    WIN7_HITS="$(LC_ALL=C strings -a "$XAMPP_CACHE_EXE" 2>/dev/null | LC_ALL=C grep -c 'application support for Windows 7' || true)"
    if [[ "${WIN7_HITS:-0}" -gt 0 ]]; then
        ok "manifest 声明支持 Windows 7"
    else
        warn "未在 manifest 里看到 Windows 7 声明 —— 请在目标机确认安装器能启动"
    fi
else
    warn "本机没有 strings，跳过 BitRock/Win7 标识检查"
fi

info "体积: $(du -h "$XAMPP_CACHE_EXE" | cut -f1)"
warn "构建期只能验到这里：安装器内部装出什么（mod_php / php8ts.dll / browscap.ini）"
warn "无法在构建机上确认，只有目标机安装完才知道 —— 这是选 installer 路线的固有代价。"

# ── 复制到输出目录 ──────────────────────────────────────────────────────────
if [[ -n "$OUT_DIR" ]]; then
    mkdir -p "$OUT_DIR"
    cp "$XAMPP_CACHE_EXE" "$OUT_DIR/xampp-installer.exe"
    ok "已复制到 $OUT_DIR/xampp-installer.exe"
fi
