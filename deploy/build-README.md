# 部署系统指南

一键构建、安装、升级、运维牙科诊所管理系统，支持 Windows / Linux / macOS。

---

## 整体流程

```
开发机 (macOS/Linux)                    目标机器 (诊所电脑)
┌───────────────────┐                   ┌──────────────────────────────┐
│  源代码           │                   │                              │
│       │           │                   │  Windows:                    │
│       ▼           │     拷贝 zip      │    解压 → 双击「一键安装」   │
│  build.sh ────────┼──────────────────▶│    → 自动完成全部配置        │
│       │           │     或 U盘        │    → 浏览器打开系统          │
│       ▼           │                   │                              │
│  deploy/output/   │                   │  Linux/macOS:                │
│  └ dental-clinic  │                   │    解压 → sudo ./install.sh  │
│    -X.Y.Z-win.zip │                   │    → 自动安装依赖+配置       │
│                   │                   │    → 系统就绪                │
└───────────────────┘                   └──────────────────────────────┘
```

### 三种交付形态

| 形态 | 命令 | 适用场景 |
|------|------|----------|
| **全量安装包 (含运行环境)** | `build.sh --target win --assemble-runtime` | 首次部署 Windows，目标机无需联网 |
| **全量安装包 (不含运行环境)** | `build.sh --target linux` | 首次部署 Linux/macOS（安装时联网装系统包） |
| **升级包** | `build.sh --target win --upgrade` | 已有系统的版本升级 |

---

## 一、构建（开发机执行）

### 1.1 前置要求

| 工具 | 必需 | 用途 |
|------|------|------|
| Bash 4+ | 是 | macOS/Linux 自带；Windows 用 Git Bash |
| PHP 8.2+ | 是 | Composer 依赖安装、Schema 导出 |
| Composer | 是 | PHP 包管理 |
| zip | 是 | 打包归档 |
| rsync | 是 | 文件同步 |
| curl 或 wget | `--download-laragon` 时需要 | 下载 Laragon Portable |
| yakpro-po | 可选 | PHP 源码混淆（`composer global require nicoco007/yakpro-po`） |
| pip3 | 可选 | 下载 OCR Python 离线包 |

### 1.2 构建命令

```bash
# ★ 推荐：自动下载 PHP/MySQL/Nginx/Composer 并组装 Windows 一键安装包
./deploy/build.sh --target win --assemble-runtime

# 自定义组件版本（通过环境变量覆盖默认下载地址）
export PHP_DOWNLOAD_URL="https://windows.php.net/downloads/releases/php-8.3.15-nts-Win32-vs16-x64.zip"
./deploy/build.sh --target win --assemble-runtime

# 手动指定本地已有的完整 Laragon 目录（含 PHP/MySQL/Nginx）
./deploy/build.sh --target win --bundle-laragon ~/Downloads/laragon-full

# Linux / macOS 安装包（不需要 Laragon，安装时用 apt/yum/brew 装系统包）
./deploy/build.sh --target linux

# 升级包（仅代码+迁移，不含运行时依赖和 schema.sql）
./deploy/build.sh --target win --upgrade

# 跳过 OCR 和混淆（开发/测试用，构建更快）
./deploy/build.sh --target linux --skip-obfuscate --skip-ocr

# 指定版本号（覆盖 VERSION 文件）
./deploy/build.sh --target win --version 2.0.0
```

构建产物输出到 `deploy/output/` 目录。

### 1.3 构建参数一览

| 参数 | 说明 |
|------|------|
| `--target <win\|linux\|mac>` | **必选**，目标平台 |
| `--upgrade` | 生成升级包（不含 schema.sql、storage/、scripts/） |
| `--skip-obfuscate` | 跳过 PHP 代码混淆 |
| `--skip-ocr` | 跳过 OCR Python wheels 下载 |
| `--version <X.Y.Z>` | 覆盖 VERSION 文件中的版本号 |
| `--assemble-runtime` | ★ 自动下载 PHP/MySQL/Nginx/Composer 组装运行环境 |
| `--bundle-laragon <path>` | 将本地已有的完整 Laragon 目录打入安装包 |
| `--download-laragon` | 兼容旧参数，等同于 `--assemble-runtime` |
| `--laragon-url <url>` | 指定 Laragon core zip 地址（可选） |

环境变量（均可选，脚本自动从官网解析最新版本）:

| 环境变量 | 说明 | 自动解析来源 |
|----------|------|-------------|
| `PHP_DOWNLOAD_URL` | PHP Windows zip 直链 | windows.php.net 索引页，取最新 8.2.x NTS x64 |
| `MYSQL_DOWNLOAD_URL` | MySQL Windows zip 直链 | dev.mysql.com HEAD 探测，取最新 8.0.x / 8.4.x |
| `NGINX_DOWNLOAD_URL` | Nginx Windows zip 直链 | nginx.org 下载页，取最新偶数版（稳定版） |
| `COMPOSER_DOWNLOAD_URL` | Composer phar 直链 | 默认 latest-stable |
| `LARAGON_DOWNLOAD_URL` | Laragon core zip（面板程序，可选） | GitHub 8.6.1 |

### 1.4 构建流程详解

```
build.sh 执行步骤:

  [1] 清理构建目录 (deploy/dist/)
      │
  [2] rsync 复制项目文件（排除 .git/node_modules/vendor/tests/deploy 等）
      │
  [3] Composer install --no-dev（生产依赖，优化自动加载）
      │
  [4] PHP 代码混淆（yakpro-po，可选，--skip-obfuscate 跳过）
      │
  [5] 导出数据库 Schema（仅全量包，优先 artisan schema:dump，回退 mysqldump）
      │
  [6] 复制部署脚本到包内（install/upgrade/start/stop + .env.deploy 模板）
      │
  [7] 下载 OCR Python wheels（可选，--skip-ocr 跳过，按 --target 平台区分）
      │
  [8] 打包 Laragon Portable（仅 --bundle-laragon / --download-laragon）
      │
  [9] 生成升级包元数据（仅 --upgrade：env.patch + UPGRADE.md）
      │
  [10] zip 压缩 → deploy/output/dental-clinic-X.Y.Z-{target}.zip
```

### 1.5 运行环境自动组装与缓存

使用 `--assemble-runtime` 时，脚本**分别下载**各组件并组装为完整运行环境：

```
deploy/.cache/
├── laragon-core.zip        ← Laragon 面板程序
├── php.zip                 ← PHP Windows x64
├── mysql.zip               ← MySQL Windows x64
├── nginx.zip               ← Nginx Windows x64
├── laragon-core/           ← 解压缓存
├── php-extracted/
├── mysql-extracted/
├── nginx-extracted/
└── laragon/                ← ★ 最终组装结果
    ├── laragon.exe
    └── bin/
        ├── php/php-8.2.x-.../php.exe
        ├── mysql/mysql-8.0.x-.../bin/mysqld.exe
        ├── nginx/nginx-1.x.x/nginx.exe
        └── composer/composer.phar
```

**缓存机制**：
- 首次构建自动下载，后续构建复用缓存（秒级完成）
- 需要更新组件版本时，删除对应缓存文件后重新构建即可
- `rm -rf deploy/.cache/` 可完全清除缓存

**自定义组件版本**（通过环境变量覆盖默认地址）：

```bash
# 升级 PHP 版本
export PHP_DOWNLOAD_URL="https://windows.php.net/downloads/releases/php-8.3.15-nts-Win32-vs16-x64.zip"
rm -rf deploy/.cache/php* deploy/.cache/laragon/bin/php
./deploy/build.sh --target win --assemble-runtime
```

**为什么不用 Laragon Full？**

Laragon Full（含 PHP/MySQL/Nginx 的一体包）没有稳定的直链下载地址，且版本更新不及时。
`--assemble-runtime` 直接从各组件的官方源下载，版本可控、地址稳定。

### 1.6 产物结构

**全量安装包 (含 Laragon)**

```
dental-clinic-1.0.0-win/
├── 一键安装.bat              ← 用户双击此文件
├── install-win.bat           ← 实际安装逻辑 (18 步)
├── upgrade-win.bat           ← 升级脚本
├── start-win.bat             ← 启动服务
├── stop-win.bat              ← 停止服务
├── laragon-startup.bat       ← 桌面快捷方式入口
├── check.sh / backup-restore.sh / export-data.sh  ← 运维工具
├── .env.deploy               ← 环境变量模板
├── VERSION                   ← 版本号
├── laragon/                  ← Laragon Portable (PHP+MySQL+Nginx+Composer)
├── ocr-wheels/               ← OCR Python 离线包 (可选)
│
├── app/                      ← 项目代码（可能已混淆）
├── config/
├── database/
│   ├── migrations/
│   └── schema/mysql-schema.sql  ← 全量 schema
├── public/
├── resources/
├── routes/
├── storage/                  ← 目录结构骨架
├── vendor/                   ← Composer 生产依赖
├── artisan
└── composer.json / composer.lock
```

**升级包**

```
dental-clinic-1.0.0-win-upgrade/
├── upgrade-win.bat
├── .env.deploy
├── VERSION
├── UPGRADE.md                ← 升级说明
├── env.patch                 ← 新增的环境变量列表
├── app/ config/ database/ public/ resources/ routes/
├── vendor/
└── artisan / composer.json / composer.lock
```

---

## 二、安装（目标机器执行）

### 2.1 Windows 安装

#### 用户操作

```
1. 收到 dental-clinic-X.Y.Z-win.zip
2. 解压到任意位置
3. 双击「一键安装.bat」
4. 等待自动完成（约 3-5 分钟）
5. 浏览器自动打开 http://localhost/dental
```

包含 Laragon 的安装包**无需联网、无需预装任何软件**。

#### install-win.bat 内部流程（18 步）

```
用法: install-win.bat [安装目录] [选项]
      默认安装到 C:\DentalClinic

选项:
  --db-host <host>       数据库主机       (默认 127.0.0.1)
  --db-port <port>       数据库端口       (默认 3306)
  --db-name <name>       数据库名         (默认 pristine_dental)
  --db-user <user>       数据库用户       (默认 root)
  --db-pass <pass>       数据库密码       (默认 空)
  --app-url <url>        应用地址         (默认 http://localhost)
  --no-ocr               跳过 OCR 环境安装
  --no-service           跳过 Windows 服务注册
  --yes / -y             静默模式（跳过确认提示）
```

```
  Step  1  检查管理员权限
  Step  2  检查磁盘空间 (≥2GB) & 已有安装
  Step  3  检测 Laragon 运行环境 (PHP/MySQL/Nginx/Composer/Python)
           └ 自动发现版本：PHP 8.x → MySQL 8.x → Nginx → Composer
           └ 版本验证：PHP ≥ 8.2
  Step  4  启动 MySQL（自动用 Laragon my.ini 启动，最多等 60 秒）
  Step  5  CREATE DATABASE（utf8mb4_unicode_ci）
  Step  6  创建专用 MySQL 用户（如指定了 --db-pass）
  Step  7  生成 .env（从 .env.deploy 模板替换 {{占位符}}）
  Step  8  生成 APP_KEY (php artisan key:generate)
  Step  9  数据库初始化
           └ 优先: 导入 schema.sql（快速）
           └ 回退: artisan migrate（逐个迁移）
  Step 10  数据库填充 (db:seed)（仅首次安装，已有数据则跳过）
  Step 11  创建 Storage 软链接
  Step 12  缓存优化 (config:cache + route:cache + view:cache)
  Step 13  配置日志清理计划任务（每周清理 30 天前日志）
  Step 14  OCR 环境安装
           └ 创建 Python venv → 优先离线安装 (ocr-wheels/) → 回退在线安装
           └ 更新 .env OCR_PYTHON_PATH
  Step 15  配置 Nginx
           └ 自动生成 sites-enabled/auto.dental.conf
           └ Root → {项目}/public，FastCGI → 127.0.0.1:9000
           └ 验证 nginx -t
  Step 16  注册 Windows 服务（MySQL 开机自启）
           └ 优先 NSSM → 回退 sc.exe
  Step 17  设置 Windows 计划任务
           └ DentalClinic-Scheduler: artisan schedule:run（每分钟）
           └ DentalClinic-QueueWorker: artisan queue:work（开机启动）
  Step 18  最终验证
           └ artisan --version ✓
           └ 数据库连接 ✓
           └ route:list ✓
```

安装完成后显示：
- 访问地址：http://localhost
- 管理员：`admin@example.com` / `password`（首次登录务必修改）

#### 备选：Inno Setup .exe 安装包

适合需要图形安装界面的场景。

1. 在 Windows 上安装 [Inno Setup 6](https://jrsoftware.org/isdl.php)
2. 运行 `build.sh --target win` 生成 `deploy/dist/`
3. 将 Laragon Portable 放入 `deploy/laragon-portable/`
4. 用 Inno Setup Compiler 打开 `build-installer.iss` → Compile
5. 生成的 `.exe` 在 `deploy/output/` 目录

生成的 `.exe` 安装包特性：
- 图形安装向导（中文界面）
- 自动选择安装路径（默认 `C:\DentalClinic`）
- 创建桌面快捷方式和开始菜单
- 安装后自动运行配置脚本
- 内置卸载功能（自动停止服务、清理数据）

### 2.2 Linux / macOS 安装

```bash
# 解压
unzip dental-clinic-X.Y.Z-linux.zip
cd dental-clinic-X.Y.Z-linux/

# ★ 推荐：一键安装（自动安装系统依赖）
sudo ./install.sh --auto-deps

# 自定义安装
sudo ./install.sh \
  --auto-deps \
  --install-dir /opt/dental \
  --db-pass 'StrongPassword123' \
  --port 8080

# 如果已有 PHP/MySQL/Nginx
sudo ./install.sh
```

#### install-linux.sh 内部流程（12 步）

```
选项:
  --install-dir DIR      安装目录 (默认 /opt/dental)
  --db-host HOST         数据库主机 (默认 127.0.0.1)
  --db-port PORT         数据库端口 (默认 3306)
  --db-name NAME         数据库名 (默认 pristine_dental)
  --db-user USER         数据库用户 (默认 dental)
  --db-pass PASS         数据库密码 (默认 随机生成)
  --db-root-pass PASS    MySQL root 密码 (默认 空)
  --app-url URL          应用地址 (默认 http://localhost)
  --port PORT            Web 服务端口 (默认 80)
  --skip-ocr             跳过 OCR 环境
  --no-service           不创建 systemd 服务
  --auto-deps            自动安装缺失的系统依赖
  --source-dir DIR       项目源文件目录（默认: 脚本上级目录）
```

```
  Step  1  环境检测 (PHP/MySQL/Nginx/Composer)
  Step  2  安装缺失依赖 (--auto-deps)
           └ 自动检测发行版 (Ubuntu/Debian/CentOS/RHEL/macOS)
           └ apt/yum/brew 安装 PHP 8.2 + 扩展 + MySQL + Nginx + Composer
  Step  3  复制项目文件到安装目录 (rsync)
  Step  4  创建数据库 & 用户
  Step  5  生成 .env 配置
  Step  6  artisan key:generate + migrate + db:seed
  Step  7  缓存优化 + storage:link
  Step  8  配置 Nginx 站点 + PHP-FPM
  Step  9  修复文件权限 (www-data / _www)
  Step 10  创建 systemd 服务
           └ dental-queue (artisan queue:work)
           └ dental-ocr (OCR Python 服务)
           └ cron job (artisan schedule:run)
  Step 11  OCR 环境安装 (Python venv + 离线 wheels)
  Step 12  健康检查 & 最终验证
```

---

## 三、升级

### 3.1 升级流程概览

```
  版本检查 → 自动备份 → 维护模式 → 代码更新 → 环境变量合并
         → 依赖安装 → 数据库迁移 → 缓存重建 → 健康检查
                                                    │
                                              ┌─────┴─────┐
                                              │ 通过       │ 失败
                                              ▼           ▼
                                        退出维护      自动回滚
                                        升级完成      恢复原状
```

### 3.2 Windows 升级

```bat
REM 将升级包解压到任意目录，运行：
upgrade-win.bat C:\DentalClinic
```

### 3.3 Linux / macOS 升级

```bash
sudo ./upgrade-linux.sh --install-dir /opt/dental

# 跳过备份（不推荐，失败无法自动回滚）
sudo ./upgrade-linux.sh --skip-backup --yes
```

### 3.4 升级脚本内部流程（10 步）

```
  Step  1  环境检测 (PHP/mysqldump 可用性)
  Step  2  版本检查
           └ 读取当前版本 & 升级包版本
           └ 拒绝降级 (X.Y.Z → 更低版本)
           └ 同版本提示确认
  Step  3  读取升级包版本并校验
  Step  4  自动备份（此步骤之后的失败将触发自动回滚）
           └ 4a: 备份 .env
           └ 4b: mysqldump 导出数据库
           └ 4c: 备份应用目录 (xcopy/tar)
  Step  5  进入维护模式 (artisan down --refresh=30)
  Step  6  代码更新
           └ 保留: .env + storage/app/ + storage/logs/
           └ 更新: app/ config/ database/ public/ resources/ routes/ vendor/
  Step  7  环境变量合并
           └ 优先: 读取 env.patch，只添加缺失的 key（不覆盖已有值）
           └ 兜底: 检查 .env.example 中的新 key
  Step  8  安装依赖 (composer install --no-dev) + 数据库迁移
  Step  9  缓存清理与重建
           └ config:cache + route:cache + view:cache + storage:link
  Step 10  健康检查 → 退出维护模式 → 重启服务
```

### 3.5 自动回滚

Step 4 备份完成后，后续任何步骤失败都会自动触发回滚：

1. 恢复应用文件（从 xcopy 备份/tar 包）
2. 恢复 .env
3. 恢复数据库（从 mysqldump 备份）
4. 重装旧版依赖 (composer install)
5. 重建缓存
6. 退出维护模式
7. 重启服务

备份保留在 `backups/upgrade_YYYYMMDD_HHMMSS/` 目录，不自动删除。

---

## 四、日常运维

### 4.1 启动 / 停止

#### Windows

```bat
REM 方式 1: 双击桌面快捷方式（指向 laragon-startup.bat）

REM 方式 2: 脚本启动
start-win.bat [安装目录]

REM 停止所有服务
stop-win.bat
```

`start-win.bat` 启动顺序：
1. MySQL（优先 Laragon → 回退 mysqld 直接启动 → 回退 Windows 服务）
2. Web 服务器（优先 Laragon Nginx → 回退独立 Nginx → 回退 PHP 内置服务器）
3. OCR 服务（检测 Python venv + ocr_server.py）
4. Laravel 队列工作进程
5. 自动打开浏览器

`stop-win.bat` 停止顺序（反向，先优雅后强制）：
1. 队列工作进程 → 2. OCR 服务 → 3. Nginx / PHP-CGI → 4. MySQL

#### Linux / macOS

```bash
# 启动
./start-linux.sh

# 停止
./stop-linux.sh

# 如果配置了 systemd
sudo systemctl start dental-queue dental-ocr
sudo systemctl stop dental-queue dental-ocr
sudo systemctl restart nginx php8.2-fpm
```

### 4.2 健康检查

```bash
bash deploy/check.sh [--install-dir /opt/dental]
```

检查项目（10 项）：
1. PHP 版本 (≥8.2) 与扩展 (pdo_mysql, mbstring, openssl 等)
2. MySQL 连接与版本、数据库是否存在、表数量
3. Web 服务器状态 (Nginx / Apache)
4. Laravel 状态 (artisan 可运行、vendor/ 完整)
5. .env 配置 (APP_KEY、APP_DEBUG、QUEUE_CONNECTION)
6. 目录权限 (storage/、bootstrap/cache/ 可写)
7. 磁盘空间使用率
8. OCR 服务状态 (HTTP 健康检查)
9. 队列 Worker 运行状态 (进程/supervisor/systemd)
10. 定时任务配置 (crontab/systemd timer)

输出汇总：✓ 通过 / ✗ 失败 / ⚠ 警告，退出码 0=正常 1=有故障。

### 4.3 备份与恢复

```bash
# 完整备份（数据库 + 代码 + 上传文件）
bash deploy/backup-restore.sh backup [--install-dir DIR] [--output-dir DIR]

# 恢复
bash deploy/backup-restore.sh restore <backup-file> [--install-dir DIR]

# 列出已有备份
bash deploy/backup-restore.sh list [--output-dir DIR]

# 数据导出（迁移到新机器用）
bash deploy/export-data.sh
```

---

## 五、目录结构参考

### deploy/ 脚本清单

```
deploy/
│
│  ── 构建 ──────────────────────────────
├── build.sh                # 主构建脚本（在开发机运行）
├── build-installer.iss     # Inno Setup 配置（可选，生成 .exe 安装包）
├── build-README.md         # 本文档
├── .env.deploy             # 环境变量模板（含 {{占位符}}）
├── yakpro-po.cnf           # PHP 源码混淆配置
│
│  ── 安装 ──────────────────────────────
├── install-win.bat         # Windows 安装脚本 (18 步)
├── install-linux.sh        # Linux/macOS 安装脚本 (12 步)
│
│  ── 升级 ──────────────────────────────
├── upgrade-win.bat         # Windows 升级脚本 (10 步，带自动回滚)
├── upgrade-linux.sh        # Linux/macOS 升级脚本 (10 步，带自动回滚)
│
│  ── 启停 ──────────────────────────────
├── start-win.bat           # Windows 启动所有服务 (MySQL→Nginx→OCR→队列→浏览器)
├── stop-win.bat            # Windows 停止所有服务 (队列→OCR→Nginx→MySQL)
├── start-linux.sh          # Linux/macOS 启动
├── stop-linux.sh           # Linux/macOS 停止
├── laragon-startup.bat     # Laragon 启动入口（桌面快捷方式目标）
│
│  ── 运维 ──────────────────────────────
├── check.sh                # 健康检查 (10 项检测)
├── backup-restore.sh       # 备份与恢复
├── export-data.sh          # 数据导出（迁移机器用）
│
│  ── 构建产物 & 缓存 ──────────────────
├── .cache/                 # Laragon 下载缓存（.gitignore）
│   ├── laragon-portable.zip
│   └── laragon/
├── dist/                   # 构建临时目录（.gitignore）
└── output/                 # 最终产物（.gitignore）
    ├── dental-clinic-1.0.0-win.zip
    ├── dental-clinic-1.0.0-linux.zip
    ├── dental-clinic-1.0.0-win-upgrade.zip
    └── 牙科诊所管理系统_安装包_v1.0.0.exe  (Inno Setup)
```

### 目标机器安装后目录 (Windows)

```
C:\DentalClinic\
├── laragon/                       ← Laragon Portable
│   ├── bin/
│   │   ├── php/php-8.x/           ← PHP
│   │   ├── mysql/mysql-8.x/       ← MySQL
│   │   ├── nginx/nginx-x.x/       ← Nginx
│   │   └── composer/composer.phar  ← Composer
│   ├── etc/
│   │   ├── mysql/my.ini
│   │   └── nginx/sites-enabled/auto.dental.conf
│   ├── data/                       ← MySQL 数据目录
│   └── www/
│       └── dental/                 ← 项目根目录
│           ├── .env                ← 运行时配置（安装脚本生成）
│           ├── artisan
│           ├── app/ config/ database/ public/ resources/ routes/
│           ├── storage/
│           │   ├── app/public/     ← 上传文件
│           │   └── logs/           ← 日志
│           ├── vendor/
│           └── scripts/
│               ├── venv/           ← OCR Python 虚拟环境
│               └── ocr_server.py
│
├── backups/                        ← 升级时的自动备份
├── install-win.bat
├── upgrade-win.bat
├── start-win.bat / stop-win.bat
├── laragon-startup.bat
└── VERSION
```

---

## 六、安装包大小参考

| 组件 | 大小 |
|------|------|
| 项目代码（混淆后） | ~50 MB |
| Composer vendor | ~80 MB |
| OCR Python wheels | ~200 MB |
| Laragon Portable（仅 Windows） | ~300 MB |
| **完整 Windows 安装包（含 Laragon + OCR）** | **~400 MB** |
| **完整 Windows 安装包（含 Laragon, 无 OCR）** | **~200 MB** |
| **Linux/macOS 安装包（含 OCR）** | **~330 MB** |
| **Linux/macOS 安装包（无 OCR）** | **~130 MB** |
| **升级包** | **~50 MB** |

---

## 七、.env.deploy 模板

安装脚本使用 `deploy/.env.deploy` 作为模板生成 `.env`，其中的 `{{占位符}}` 会被替换为实际值：

| 占位符 | 来源 | 默认值 |
|--------|------|--------|
| `{{APP_URL}}` | `--app-url` 参数 | `http://localhost` |
| `{{DB_HOST}}` | `--db-host` 参数 | `127.0.0.1` |
| `{{DB_PORT}}` | `--db-port` 参数 | `3306` |
| `{{DB_DATABASE}}` | `--db-name` 参数 | `pristine_dental` |
| `{{DB_USERNAME}}` | `--db-user` 参数 | `root` |
| `{{DB_PASSWORD}}` | `--db-pass` 参数 | _(空)_ |
| `{{OCR_PYTHON_PATH}}` | 安装脚本自动检测 | venv 内的 python 路径 |

---

## 八、注意事项

- **幂等安全** — 所有脚本支持重复运行，不会破坏已有数据
- **自动回滚** — 升级脚本备份后的任何失败都会自动回滚到升级前状态
- **敏感信息保护** — `.env` 中的数据库密码不会在升级时被覆盖
- **OCR 可选** — 不安装 OCR 不影响其他功能，使用 `--skip-ocr` / `--no-ocr` 跳过
- **离线部署** — 含 Laragon 的 Windows 包和 OCR wheels 均支持完全离线安装
- **默认管理员** — `admin@example.com` / `password`（**首次登录后请立即修改**）
- **日志管理** — Windows 安装时自动创建计划任务清理 30 天前日志
- **Windows 服务** — MySQL 注册为 `DentalClinicMySQL` Windows 服务，开机自启
