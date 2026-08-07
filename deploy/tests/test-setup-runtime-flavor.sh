#!/usr/bin/env bash
# ============================================================================
#  setup.bat 必须对运行时形态无感（laragon / xampp 通用）
#
#  为什么有这个测试：
#    加 --runtime xampp 时，setup.bat 里的路径改了 17 处
#    %INSTALL_DIR%\laragon\www\dental -> %APP_ROOT%，看着改完了，
#    但**漏掉了运行时目录本身的拷贝与校验**：
#        if not exist "%PKG_DIR%\laragon" ( echo [ERROR] Missing laragon runtime ... )
#    于是 XAMPP 包一双击 setup.bat 就报「Missing laragon runtime in package」，
#    连第一步都过不去。
#
#    这类 bug 静态读代码很容易漏（我漏了），而在 macOS 上又跑不了 .bat 去发现它。
#    所以把约束固化成断言，由 build.sh 在打包时执行。
# ============================================================================
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

fail() { echo "FAIL: $1" >&2; exit 1; }

assert_contains() {
    grep -qF -- "$1" "$setup_template" || fail "$2"
}

# ── 运行时目录名必须是变量，且两种形态都要有分支 ─────────────────────────
assert_contains 'set "RUNTIME_DIR_NAME=laragon"' \
    'setup.bat 没有默认的运行时目录名'
assert_contains 'set "RUNTIME_DIR_NAME=xampp"' \
    'setup.bat 不会在 XAMPP 包里切换运行时目录名'
assert_contains 'set "APP_ROOT=' \
    'setup.bat 没有把应用代码目标目录抽成变量'

# 判定依据必须是包内实际存在的文件，而不是别的什么
assert_contains 'if exist "%PKG_DIR%\xampp\apache\bin\httpd.exe"' \
    'setup.bat 判定 XAMPP 形态的依据不是包内的 httpd.exe'

# ── 拷贝与校验不允许写死 laragon ───────────────────────────────────────
# 允许出现的例外：
#   - 注释（REM）
#   - 默认值赋值 set "RUNTIME_DIR_NAME=laragon" / set "APP_ROOT=...\laragon\..."
#   - laragon-startup.bat / laragon-wamp.exe 这类**文件名**（if exist 守卫，XAMPP 包里
#     不存在会自动跳过）
#   - 成对分支：`if "%RUNTIME_DIR_NAME%"=="xampp" ( ... ) else ( ...laragon... )`
#     —— 这种写法两种形态都照顾到了，是正确的
#
# 判定「成对分支」的办法：先把每一段 if xampp (...) else (...) 整体删掉，
# 剩下的 laragon 才是真正写死的。
stripped="$(mktemp)"
trap 'rm -f "$setup_template" "$stripped"' EXIT
awk '
    /if "%RUNTIME_DIR_NAME%"=="xampp"/ { in_pair = 1 }
    in_pair && /^\)$/                  { in_pair = 0; next }
    in_pair                            { next }
    { print }
' "$setup_template" > "$stripped"

offenders="$(grep -n 'laragon' "$stripped" \
    | grep -viE '^\s*[0-9]+:\s*REM' \
    | grep -vE 'set "RUNTIME_DIR_NAME=laragon"' \
    | grep -vE 'set "APP_ROOT=%INSTALL_DIR%\\laragon' \
    | grep -viE 'laragon-startup\.bat|laragon-wamp\.exe' \
    || true)"

if [[ -n "$offenders" ]]; then
    echo "FAIL: setup.bat 里仍有写死的 laragon 路径，XAMPP 包会在这些地方失败：" >&2
    echo "$offenders" >&2
    exit 1
fi

# ── 不能用 !VAR!：模板显式 DisableDelayedExpansion ─────────────────────
# 路径里含 ! 时延迟展开会把它吃掉，所以模板是刻意禁用的。
# 在这种模板里写 !RUNTIME_DIR_NAME! 会变成字面量，直接把包做废。
if grep -q 'DisableDelayedExpansion' "$setup_template"; then
    # 只看可执行行：REM 注释里提到 !VAR! 是无害的，扫进去会误报
    # （2026-08-07 就误报过一次，注释里解释延迟展开而已）。
    if grep -vE '^[[:space:]]*(REM|::)' "$setup_template" | grep -qE '![A-Z_]+!'; then
        fail 'setup.bat 声明了 DisableDelayedExpansion 却使用了 !VAR! 延迟展开语法'
    fi
fi

echo 'PASS: setup.bat 对运行时形态无感（laragon / xampp 通用）'
