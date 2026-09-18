#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
SCRIPT="$ROOT_DIR/deploy/github-deploy.sh"

bash -n "$SCRIPT"

# Windows 引导脚本必须保持 Windows 7 自带 PowerShell 2.0 可解析的语法范围。
if grep -Eq '::new\(|\$PSCommandPath|Expand-Archive|Invoke-WebRequest' \
    "$ROOT_DIR/deploy/github-deploy-windows.ps1"; then
    echo "Windows launcher contains PowerShell 3+ syntax" >&2
    exit 1
fi
grep -q -- '--unattended' "$ROOT_DIR/deploy/upgrade-win.bat"
grep -q 'endlocal & exit /b %EXIT_CODE%' "$ROOT_DIR/deploy/upgrade-win.bat"

linux_output="$(DENTAL_PLATFORM=linux "$SCRIPT" --install --version 1.2.3 --dry-run)"
grep -q 'releases/download/v1.2.3/dental-clinic-linux.zip' <<< "$linux_output"

mac_output="$(DENTAL_PLATFORM=mac "$SCRIPT" --update --dry-run)"
grep -q 'releases/latest/download/dental-clinic-mac-upgrade.zip' <<< "$mac_output"

custom_output="$(DENTAL_PLATFORM=linux DENTAL_RELEASE_BASE_URL=https://mirror.example/releases "$SCRIPT" --update --dry-run)"
grep -q 'https://mirror.example/releases/dental-clinic-linux-upgrade.zip' <<< "$custom_output"

if DENTAL_PLATFORM=windows "$SCRIPT" --dry-run >/dev/null 2>&1; then
    echo "expected unsupported platform to fail" >&2
    exit 1
fi

# 镜像地址没有版本维度，此时 --version 不该被静默忽略。
if DENTAL_PLATFORM=linux DENTAL_RELEASE_BASE_URL=https://mirror.example/releases \
    "$SCRIPT" --update --version 1.2.3 --dry-run >/dev/null 2>&1; then
    echo "expected --version with a mirror base URL to fail" >&2
    exit 1
fi

# 通过本地 Release 镜像完成一次“下载 -> SHA256 -> 解压 -> 调安装入口”的闭环。
if command -v zip >/dev/null 2>&1 && command -v sha256sum >/dev/null 2>&1; then
    TEST_DIR="$(mktemp -d "${TMPDIR:-/tmp}/dental-launcher-test.XXXXXX")"
    trap 'rm -rf "$TEST_DIR"' EXIT
    mkdir -p "$TEST_DIR/package/release" "$TEST_DIR/bin"
    printf '%s\n' '#!/usr/bin/env bash' 'printf "%s\n" "$*" > "$TEST_MARKER"' \
        > "$TEST_DIR/package/release/install.sh"
    chmod +x "$TEST_DIR/package/release/install.sh"
    (cd "$TEST_DIR/package" && zip -q -r "$TEST_DIR/dental-clinic-linux.zip" release)
    (cd "$TEST_DIR" && sha256sum dental-clinic-linux.zip > SHA256SUMS)
    printf '%s\n' '#!/usr/bin/env bash' 'exec "$@"' > "$TEST_DIR/bin/sudo"
    chmod +x "$TEST_DIR/bin/sudo"

    TEST_MARKER="$TEST_DIR/called" \
    PATH="$TEST_DIR/bin:$PATH" \
    DENTAL_PLATFORM=linux \
    DENTAL_RELEASE_BASE_URL="file://$TEST_DIR" \
        "$SCRIPT" --install --install-dir "$TEST_DIR/install-target" -- --port 8088 >/dev/null

    grep -q -- "--install-dir $TEST_DIR/install-target --auto-deps --port 8088" "$TEST_DIR/called"

    # 升级同样要把 -- 后的参数透传下去（漏掉的话命令照常成功，参数却没生效）。
    mkdir -p "$TEST_DIR/upgrade-pkg/release"
    printf '%s\n' '#!/usr/bin/env bash' 'printf "%s\n" "$*" > "$TEST_MARKER"' \
        > "$TEST_DIR/upgrade-pkg/release/upgrade-linux.sh"
    chmod +x "$TEST_DIR/upgrade-pkg/release/upgrade-linux.sh"
    (cd "$TEST_DIR/upgrade-pkg" && zip -q -r "$TEST_DIR/dental-clinic-linux-upgrade.zip" release)
    (cd "$TEST_DIR" && sha256sum dental-clinic-linux.zip dental-clinic-linux-upgrade.zip > SHA256SUMS)

    TEST_MARKER="$TEST_DIR/called-upgrade" \
    PATH="$TEST_DIR/bin:$PATH" \
    DENTAL_PLATFORM=linux \
    DENTAL_RELEASE_BASE_URL="file://$TEST_DIR" \
        "$SCRIPT" --update --install-dir "$TEST_DIR/install-target" -- --skip-backup >/dev/null

    grep -q -- "--install-dir $TEST_DIR/install-target --yes --skip-backup" "$TEST_DIR/called-upgrade"
fi

echo "GitHub deploy launcher tests passed"
