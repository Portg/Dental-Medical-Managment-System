#!/usr/bin/env bash
# ============================================================
# Dental Medical Management System — 在容器里跑测试
#
# 为什么要有这个脚本：
#   1. 部署目标是 MySQL 8.0（见 docker-compose.yml 的 mysql 服务），
#      而开发机常常是 8.4 或更高。测试跑在 8.0 上才和生产同版本。
#   2. 测试库跑的是 migrate:fresh，会反复 drop/create 整个 schema。
#      用宿主机上那个共享的测试库时，只要有另一个进程同时碰它，
#      就会冒出「migrations 表已存在 / 不存在」这类和业务无关的报错。
#      独立容器 + 独立端口可以从根上排除这种干扰。
#
# 用法：
#   ./test-in-docker.sh                      # 全量
#   ./test-in-docker.sh --filter=LabCaseApi  # 参数原样透传给 artisan test
#   ./test-in-docker.sh --fresh              # 重建容器（换 MySQL 版本或库脏了时用）
#   ./test-in-docker.sh --stop               # 用完删掉容器
#
# 容器默认保留，下次直接复用，省掉每次几十秒的启动时间。
# ============================================================
set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

info()  { echo -e "${CYAN}[INFO]${NC}  $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
fail()  { echo -e "${RED}[FAIL]${NC}  $*"; exit 1; }

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

CONTAINER=dental-test-mysql
IMAGE=mysql:8.0          # 与 docker-compose.yml 的 mysql 服务保持一致
HOST_PORT=33061          # 避开宿主机 3306 上可能已在跑的 MySQL
ROOT_PASS=testsecret

# phpunit.xml 里 <server name="DB_DATABASE" value="dental_medical_test"/>
# 会覆盖 shell 环境变量，所以库名必须是这个，容器里得先建好。
TEST_DB=dental_medical_test

# ── 参数 ──────────────────────────────────────────────────────
FRESH=0
ARGS=()
for arg in "$@"; do
    case "$arg" in
        --fresh) FRESH=1 ;;
        --stop|--down)
            docker rm -f "$CONTAINER" >/dev/null 2>&1 && ok "容器 $CONTAINER 已删除" || info "容器不存在，无需删除"
            exit 0
            ;;
        *) ARGS+=("$arg") ;;
    esac
done

command -v docker >/dev/null 2>&1 || fail "未找到 docker"
docker info >/dev/null 2>&1 || fail "docker 守护进程没在运行"

# ── 容器 ──────────────────────────────────────────────────────
if [ "$FRESH" = "1" ]; then
    info "重建容器 ..."
    docker rm -f "$CONTAINER" >/dev/null 2>&1 || true
fi

if [ -n "$(docker ps -q -f name="^${CONTAINER}$")" ]; then
    ok "复用运行中的容器 $CONTAINER"
elif [ -n "$(docker ps -aq -f name="^${CONTAINER}$")" ]; then
    info "启动已存在的容器 $CONTAINER ..."
    docker start "$CONTAINER" >/dev/null
else
    # 变量名后紧跟全角括号时必须用 ${}：bash 会把中文的字节当成变量名的一部分
    info "创建 ${IMAGE} 容器（端口 ${HOST_PORT}）..."
    docker run -d --name "$CONTAINER" \
        -e MYSQL_ROOT_PASSWORD="$ROOT_PASS" \
        -e MYSQL_DATABASE="$TEST_DB" \
        -p "${HOST_PORT}:3306" \
        "$IMAGE" \
        --character-set-server=utf8mb4 \
        --collation-server=utf8mb4_unicode_ci >/dev/null
fi

info "等待 MySQL 就绪 ..."
# 不能只用 mysqladmin ping：MySQL 官方镜像初始化时会先起一个临时服务，
# 那阶段 ping 就已经成功，但紧接着服务会重启，此时发过去的语句会失败。
# 判据改成「一条真实查询能返回结果」。
MYSQL_VERSION=""
for i in $(seq 1 60); do
    MYSQL_VERSION="$(docker exec "$CONTAINER" mysql -uroot -p"$ROOT_PASS" -N -B \
        -e 'SELECT VERSION()' 2>/dev/null | tr -d '\r')" || true
    if [ -n "$MYSQL_VERSION" ]; then
        ok "MySQL 就绪（${MYSQL_VERSION}）"
        break
    fi
    [ "$i" = "60" ] && fail "MySQL 启动超时（3 分钟）。试试 ./test-in-docker.sh --fresh"
    sleep 3
done

# 复用已有容器时这个库不一定在（比如上次用 --fresh 换过镜像）。
# 吃掉 mysql 客户端「命令行传密码不安全」的告警，但失败时把原文带出来。
if ! CREATE_OUT="$(docker exec "$CONTAINER" mysql -uroot -p"$ROOT_PASS" \
        -e "CREATE DATABASE IF NOT EXISTS \`${TEST_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1)"; then
    fail "创建测试库 ${TEST_DB} 失败：${CREATE_OUT}"
fi

# ── 跑测试 ────────────────────────────────────────────────────
# 只覆盖连接信息；DB_DATABASE 交给 phpunit.xml。
# shell 环境变量优先于 .env（Laravel 的 Dotenv 默认不覆写已有变量），
# 所以宿主机 .env 里的库配置不会干扰这里。
echo ""
info "artisan test ${ARGS[*]:-（全量）}"
echo ""

set +e
# ${ARGS[@]+"${ARGS[@]}"}：不带参数跑全量时 ARGS 是空数组，
# 而 bash 3.2（macOS 自带）在 set -u 下展开空数组会报 unbound variable。
DB_HOST=127.0.0.1 \
DB_PORT="$HOST_PORT" \
DB_USERNAME=root \
DB_PASSWORD="$ROOT_PASS" \
php artisan test ${ARGS[@]+"${ARGS[@]}"}
STATUS=$?
set -e

echo ""
if [ "$STATUS" = "0" ]; then
    ok "测试通过"
else
    warn "测试失败（退出码 ${STATUS}）"
fi
info "容器 $CONTAINER 保留中，下次直接复用；删除请执行 ./test-in-docker.sh --stop"

exit "$STATUS"
