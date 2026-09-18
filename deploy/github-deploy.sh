#!/usr/bin/env bash
# 从 GitHub Releases 一键安装或升级（Linux / macOS）。
set -euo pipefail

DEFAULT_REPO="Portg/Dental-Medical-Managment-System"
REPO="${DENTAL_GITHUB_REPO:-$DEFAULT_REPO}"
RELEASE_VERSION="latest"
VERSION_EXPLICIT=false
INSTALL_DIR="/opt/dental"
MODE="auto"
DRY_RUN=false
VERIFY_CHECKSUM=true
INSTALL_ARGS=()

usage() {
    cat <<'EOF'
牙科诊所管理系统 — GitHub 一键部署/更新

用法:
  ./github-deploy.sh [--install|--update|--auto] [选项] [-- 安装参数]

模式:
  --auto                 已安装则升级，否则首次安装（默认）
  --install              下载全量包并安装
  --update               下载升级包并安全升级

选项:
  --repo OWNER/REPO      GitHub 仓库
  --version VERSION      发布版本，如 1.2.0 或 v1.2.0（默认 latest）
  --install-dir DIR      安装目录（默认 /opt/dental）
  --no-verify            不校验 SHA256（不推荐）
  --dry-run              只显示将下载的地址
  -h, --help             显示帮助

-- 之后的参数原样透传给底层脚本，例如：
  ./github-deploy.sh --install -- --port 8088 --skip-ocr
  ./github-deploy.sh --update  -- --skip-backup

环境变量:
  DENTAL_GITHUB_REPO     默认 GitHub 仓库
  DENTAL_RELEASE_BASE_URL 自定义 Release 下载根地址（内网镜像/测试）
  GH_TOKEN               GitHub 下载令牌（私有仓库可用）
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --auto) MODE="auto"; shift ;;
        --install) MODE="install"; shift ;;
        --update) MODE="update"; shift ;;
        --repo) REPO="${2:-}"; shift 2 ;;
        --version) RELEASE_VERSION="${2:-}"; VERSION_EXPLICIT=true; shift 2 ;;
        --install-dir) INSTALL_DIR="${2:-}"; shift 2 ;;
        --no-verify) VERIFY_CHECKSUM=false; shift ;;
        --dry-run) DRY_RUN=true; shift ;;
        -h|--help) usage; exit 0 ;;
        --) shift; INSTALL_ARGS=("$@"); break ;;
        *) echo "未知参数: $1" >&2; usage >&2; exit 2 ;;
    esac
done

if [[ ! "$REPO" =~ ^[^/]+/[^/]+$ ]]; then
    echo "GitHub 仓库格式错误: $REPO（应为 OWNER/REPO）" >&2
    exit 2
fi

case "${DENTAL_PLATFORM:-$(uname -s)}" in
    Linux|linux) PLATFORM="linux" ;;
    Darwin|darwin|mac|macos) PLATFORM="mac" ;;
    *) echo "此入口仅支持 Linux 和 macOS；Windows 请运行 github-deploy-windows.bat" >&2; exit 2 ;;
esac

if [[ "$MODE" == "auto" ]]; then
    if [[ -f "$INSTALL_DIR/artisan" && -f "$INSTALL_DIR/VERSION" ]]; then
        MODE="update"
    else
        MODE="install"
    fi
fi

if [[ "$MODE" == "update" ]]; then
    ASSET="dental-clinic-${PLATFORM}-upgrade.zip"
else
    ASSET="dental-clinic-${PLATFORM}.zip"
fi

if [[ -n "${DENTAL_RELEASE_BASE_URL:-}" ]]; then
    # 镜像地址是「一个目录里放着固定文件名的包」，没有版本维度。此时 --version
    # 无处可用 —— 与其静默忽略，让人以为装上了指定版本，不如直接停下来。
    if [[ "$VERSION_EXPLICIT" == true ]]; then
        echo "已设置 DENTAL_RELEASE_BASE_URL（$DENTAL_RELEASE_BASE_URL），该地址不带版本维度，--version $RELEASE_VERSION 不会生效。" >&2
        echo "请二选一：要指定版本就不要设 DENTAL_RELEASE_BASE_URL；要用镜像就去掉 --version。" >&2
        exit 2
    fi
    BASE_URL="${DENTAL_RELEASE_BASE_URL%/}"
elif [[ "$RELEASE_VERSION" == "latest" ]]; then
    BASE_URL="https://github.com/${REPO}/releases/latest/download"
else
    TAG="$RELEASE_VERSION"
    [[ "$TAG" == v* ]] || TAG="v${TAG}"
    BASE_URL="https://github.com/${REPO}/releases/download/${TAG}"
fi

ASSET_URL="${BASE_URL}/${ASSET}"
CHECKSUM_URL="${BASE_URL}/SHA256SUMS"

echo "平台:       $PLATFORM"
echo "操作:       $MODE"
echo "安装目录:   $INSTALL_DIR"
echo "部署包:     $ASSET_URL"

if $DRY_RUN; then
    exit 0
fi

for command_name in unzip; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "缺少命令: $command_name" >&2
        exit 1
    fi
done
if command -v curl >/dev/null 2>&1; then
    DOWNLOADER="curl"
elif command -v wget >/dev/null 2>&1; then
    DOWNLOADER="wget"
else
    echo "需要 curl 或 wget 才能从 GitHub 下载部署包" >&2
    exit 1
fi

TEMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/dental-github-deploy.XXXXXX")"
cleanup() { rm -rf "$TEMP_DIR"; }
trap cleanup EXIT INT TERM

download() {
    local url="$1" output="$2"
    if [[ "$DOWNLOADER" == "curl" ]]; then
        local curl_args=(-fL --retry 3 --connect-timeout 20 -A dental-clinic-deployer -o "$output")
        if [[ -t 2 ]]; then
            curl_args+=(--progress-bar)
        else
            curl_args+=(-sS)
        fi
        if [[ -n "${GH_TOKEN:-}" ]]; then
            curl_args+=(-H "Authorization: Bearer ${GH_TOKEN}")
        fi
        curl "${curl_args[@]}" "$url"
    else
        local wget_args=(-q --tries=3 --timeout=20 -O "$output")
        if [[ -n "${GH_TOKEN:-}" ]]; then
            wget_args+=(--header="Authorization: Bearer ${GH_TOKEN}")
        fi
        wget "${wget_args[@]}" "$url"
    fi
}

echo "正在下载部署包..."
download "$ASSET_URL" "$TEMP_DIR/$ASSET"

if $VERIFY_CHECKSUM; then
    echo "正在校验 SHA256..."
    download "$CHECKSUM_URL" "$TEMP_DIR/SHA256SUMS"
    EXPECTED_HASH="$(awk -v name="$ASSET" '$2 == name || $2 == "*" name { print $1; exit }' "$TEMP_DIR/SHA256SUMS")"
    if [[ ! "$EXPECTED_HASH" =~ ^[0-9a-fA-F]{64}$ ]]; then
        echo "SHA256SUMS 中没有 $ASSET 的有效校验值" >&2
        exit 1
    fi
    if command -v sha256sum >/dev/null 2>&1; then
        ACTUAL_HASH="$(sha256sum "$TEMP_DIR/$ASSET" | awk '{print $1}')"
    else
        ACTUAL_HASH="$(shasum -a 256 "$TEMP_DIR/$ASSET" | awk '{print $1}')"
    fi
    ACTUAL_HASH_NORMALIZED="$(printf '%s' "$ACTUAL_HASH" | tr '[:upper:]' '[:lower:]')"
    EXPECTED_HASH_NORMALIZED="$(printf '%s' "$EXPECTED_HASH" | tr '[:upper:]' '[:lower:]')"
    if [[ "$ACTUAL_HASH_NORMALIZED" != "$EXPECTED_HASH_NORMALIZED" ]]; then
        echo "部署包 SHA256 校验失败，已停止部署" >&2
        exit 1
    fi
    echo "SHA256 校验通过"
fi

unzip -q "$TEMP_DIR/$ASSET" -d "$TEMP_DIR/package"
if [[ "$MODE" == "update" ]]; then
    ENTRYPOINT="$(find "$TEMP_DIR/package" -type f -name upgrade-linux.sh -print -quit)"
else
    ENTRYPOINT="$(find "$TEMP_DIR/package" -type f -name install.sh -print -quit)"
fi
if [[ -z "$ENTRYPOINT" ]]; then
    echo "部署包内容不完整：找不到执行入口" >&2
    exit 1
fi
chmod +x "$ENTRYPOINT"

PACKAGE_VERSION_FILE="$(dirname "$ENTRYPOINT")/VERSION"
if [[ -f "$PACKAGE_VERSION_FILE" ]]; then
    echo "目标版本:   $(tr -d '[:space:]' < "$PACKAGE_VERSION_FILE")"
fi

run_as_root() {
    if [[ "$(id -u)" -eq 0 ]]; then
        "$@"
    elif command -v sudo >/dev/null 2>&1; then
        sudo "$@"
    else
        echo "部署需要 root 权限，但系统中没有 sudo；请切换到 root 后重试" >&2
        return 1
    fi
}

if [[ "$MODE" == "update" ]]; then
    # 升级同样透传 -- 后的参数（upgrade-linux.sh 有 --skip-backup 这类开关）。
    # 之前这里把它们丢了：命令照常成功，参数却没生效，最难发现。
    run_as_root "$ENTRYPOINT" --install-dir "$INSTALL_DIR" --yes \
        ${INSTALL_ARGS[@]+"${INSTALL_ARGS[@]}"}
else
    # macOS 自带 Bash 3.2 在 set -u 下展开空数组会报错，用兼容写法规避。
    run_as_root "$ENTRYPOINT" --install-dir "$INSTALL_DIR" --auto-deps \
        ${INSTALL_ARGS[@]+"${INSTALL_ARGS[@]}"}
fi

echo "GitHub 部署完成。"
