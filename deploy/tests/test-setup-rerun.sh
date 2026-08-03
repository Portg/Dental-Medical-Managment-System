#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
build_script="$repo_root/deploy/build.sh"
setup_template="$(mktemp)"
trap 'rm -f "$setup_template"' EXIT

awk '
    /cat > "\$DIST_DIR\/setup\.bat" <<'\''SHORTCUT_BAT'\''/ { in_setup = 1; next }
    in_setup && /^SHORTCUT_BAT$/ { exit }
    in_setup { print }
' "$build_script" > "$setup_template"

if [[ ! -s "$setup_template" ]]; then
    echo "FAIL: setup.bat template was not found in deploy/build.sh" >&2
    exit 1
fi

assert_contains() {
    local expected="$1"
    local explanation="$2"
    if ! grep -qF -- "$expected" "$setup_template"; then
        echo "FAIL: $explanation" >&2
        exit 1
    fi
}

assert_contains 'if /I "%PKG_DIR%"=="%INSTALL_DIR%" set "IN_PLACE=1"' \
    'setup.bat does not detect package and installation directory aliasing'
assert_contains 'if "%IN_PLACE%"=="0" (' \
    'setup.bat does not guard package-to-install copies during an in-place rerun'
assert_contains 'call :copy_dir' \
    'setup.bat directory copies do not check xcopy failures'
assert_contains 'call :copy_file' \
    'setup.bat file copies do not check copy failures'
assert_contains ':copy_failed' \
    'setup.bat has no explicit copy failure path'

if grep -qiE 'taskkill .*/im mysqld\.exe' "$setup_template"; then
    echo 'FAIL: setup.bat may stop an unrelated MySQL instance' >&2
    exit 1
fi

echo 'PASS: setup.bat supports safe in-place reruns and checked copies'
