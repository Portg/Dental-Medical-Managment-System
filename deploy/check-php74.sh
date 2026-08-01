#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════
#  PHP 7.4 语法合规检查（win7/laravel-8 分支专用）
#
#  本分支的目标机运行 PHP 7.4.33 —— PHP 8.0 起不再支持 Windows 7。
#  但开发机通常装的是 PHP 8.x，`php -l` 会放行 PHP 8 语法，
#  问题要到装机时才暴露。此脚本用真正的 PHP 7.4 解释器逐文件校验。
#
#  常见会被拦下的 PHP 8 语法：
#    match 表达式、enum、?->、构造器属性提升、命名参数、
#    联合类型、mixed/never 类型、readonly、非捕获 catch、
#    参数列表尾逗号、一等可调用 foo(...)
#
#  用法:
#    ./deploy/check-php74.sh                    # 自动探测 php7.4
#    PHP74=/path/to/php7.4 ./deploy/check-php74.sh
# ═══════════════════════════════════════════════════════════════════════

set -uo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# ── 定位 PHP 7.4 ───────────────────────────────────────────────────────
if [[ -z "${PHP74:-}" ]]; then
    for candidate in \
        /usr/local/opt/php@7.4/bin/php \
        /opt/homebrew/opt/php@7.4/bin/php \
        "$(command -v php7.4 2>/dev/null)"
    do
        if [[ -n "$candidate" ]] && [[ -x "$candidate" ]]; then
            PHP74="$candidate"
            break
        fi
    done
fi

if [[ -z "${PHP74:-}" ]]; then
    echo -e "${YELLOW}未找到 PHP 7.4 解释器，跳过检查。${NC}"
    echo "  macOS:  brew install shivammathur/php/php@7.4"
    echo "  Docker: docker run --rm -v \"\$PWD\":/app -w /app php:7.4-cli ./deploy/check-php74.sh"
    echo "  或显式指定: PHP74=/path/to/php7.4 $0"
    exit 0
fi

ACTUAL_VER="$("$PHP74" -r 'echo PHP_VERSION;' 2>/dev/null)"
case "$ACTUAL_VER" in
    7.4.*) ;;
    *) echo -e "${RED}PHP74 指向的不是 7.4（实际 ${ACTUAL_VER:-未知}）${NC}"; exit 2 ;;
esac

echo "使用解释器: $PHP74 (PHP $ACTUAL_VER)"
echo ""

# ── 逐文件校验 ─────────────────────────────────────────────────────────
CHECKED=0
FAILED=0
FAIL_LOG="$(mktemp)"

SCAN_DIRS=""
for d in app config database routes tests bootstrap; do
    [[ -d "$PROJECT_ROOT/$d" ]] && SCAN_DIRS="$SCAN_DIRS $PROJECT_ROOT/$d"
done

FILE_LIST="$(mktemp)"
# shellcheck disable=SC2086
find $SCAN_DIRS -name '*.php' -not -path '*/venv/*' > "$FILE_LIST" 2>/dev/null

while IFS= read -r f; do
    [[ -n "$f" ]] || continue
    CHECKED=$((CHECKED + 1))
    if ! "$PHP74" -l "$f" >/dev/null 2>>"$FAIL_LOG"; then
        FAILED=$((FAILED + 1))
    fi
done < "$FILE_LIST"
rm -f "$FILE_LIST"

echo "已检查 $CHECKED 个文件"

if [[ "$FAILED" -gt 0 ]]; then
    echo -e "${RED}✗ $FAILED 个文件无法被 PHP 7.4 解析：${NC}"
    grep -E 'Parse error|Fatal error' "$FAIL_LOG" | sed 's/^/  /' | sort -u
    rm -f "$FAIL_LOG"
    echo ""
    echo -e "${YELLOW}这些文件在 Windows 7 目标机上会直接 500，必须改成 PHP 7.4 兼容写法。${NC}"
    exit 1
fi

rm -f "$FAIL_LOG"
echo -e "${GREEN}✓ 全部通过 PHP 7.4 语法检查${NC}"
