# 部署系统指南

一键构建、安装、升级、运维牙科诊所管理系统，支持 Windows / Linux / macOS。

---

## ⚠️ 本分支为 Windows 7 专用版（win7/deploy-only）

本分支面向 **Windows 7 SP1 x64** 目标机，技术栈与 `master` 相同
（PHP 8.2 + Laravel 11），**部署层**做了 Win7 适配。

> **注意**：本分支当前**不只有部署层改动**。除部署脚本外还带有三个业务提交
> （患者满意度调查闭环、复诊率/患者来源报表导出、诊疗页划价 Tab 与排班复制跨度），
> 合计影响约 61 个文件。计划将这三个提交拆到独立的 feature 分支后，
> 本分支才恢复为纯部署分支。在此之前请勿把「本分支 == 部署层」当作前提。

### 为什么不需要降级 PHP / Laravel

一个常见误解是「PHP 8 不支持 Win7」。实际分界线在 **8.3**：

> As of version 8.3.0, PHP requires Windows 8 or Windows Server 2012.
> Versions after 7.2.0 required Windows 2008 R2 or **Windows 7**.
> —— [php.net install.windows.manual](https://www.php.net/manual/en/install.windows.manual.php)

也就是 **PHP 8.0 / 8.1 / 8.2 都支持 Win7**，因此 Laravel 11（要求 PHP ≥ 8.2）
可以原样运行。本分支曾一度把整套栈降到 PHP 7.4 + Laravel 8，那是基于错误前提，
已回退——降级会把 Laravel 8（2023-01 EOL）和一批 dompdf critical 漏洞带进来，
没有任何必要。

### 实际锁死的版本

| 组件 | 锁定 | 上限原因 |
| --- | --- | --- |
| PHP | **8.2.33**（VS16 x64 NTS） | 8.3 起最低要求 Windows 8 |
| Laravel | 11.x（同 master） | 不需要改动 |
| MySQL | **5.7.44** | MySQL 8.0 的支持平台只列到 Server 2016 / Windows 10 |
| Python（OCR） | **3.8.10** | 3.9 起最低要求 Windows 8.1 |
| PaddleOCR | **2.7.3** | 3.x 的依赖树要求 Python ≥ 3.9 |
| Inno Setup `MinVersion` | **6.1sp1** | Windows 7 SP1 |

改动这些版本前，请先确认对应组件的 Win7 支持情况——不要凭印象。

### 运行环境不用 Laragon 安装器

`laragon-wamp.exe` 的安装器自身要求 Windows 10。`build.sh --target win` 改为
下载 laragon-core（纯文件包）再填入上表锁定的 PHP / MySQL / Nginx / Composer，
产出目录结构与 Laragon 完全一致，`install-win.ps1` 无需改动即可识别。

可用环境变量覆盖下载地址（内网镜像场景）：
`PHP_DOWNLOAD_URL`、`MYSQL_DOWNLOAD_URL`、`NGINX_DOWNLOAD_URL`、
`LARAGON_DOWNLOAD_URL`、`COMPOSER_DOWNLOAD_URL`、`PYTHON_DOWNLOAD_URL`、
`VCREDIST_DOWNLOAD_URL`、`WMF_DOWNLOAD_URL`、`DOTNET48_DOWNLOAD_URL`。

覆盖任一地址会**跳过该组件的 SHA256 校验**（换镜像换版本必然换指纹），
构建时会打印警告。默认地址均带指纹校验，见 `build.sh` 的 `expected_sha256()`。

### Win7 特有的三个前置组件（已随包提供）

1. **VC++ 2015-2022 x64 运行库** —— PHP 的 VS16 构建依赖它。缺失时的典型症状
   是 `php.exe` 双击无反应或提示缺少 `VCRUNTIME140.dll`。安装包根目录附带
   `vc_redist.x64.exe`。

2. **WMF 5.1（PowerShell 5.1）** —— Windows 7 SP1 出厂自带 **PowerShell 2.0**，
   而 `install-win.ps1` 用到了 `[Type]::new()`（需 PS5）与 `*>` 重定向（需 PS3），
   在 PS2 上会在**解析阶段**就失败。`install-win.bat` 会先探测
   `$PSVersionTable.PSVersion.Major`，低于 3 时提示并静默安装随包的
   `wmf51\*.msu`（KB3191566），**装完需重启**再重新运行安装程序。

3. **.NET Framework 4.8** —— WMF 5.1 的安装前提是 .NET Framework **4.5.2**
   （不是 4.5），而纯净 Win7 SP1 只带 .NET 3.5.1。不随包提供的话，
   离线目标机装不了 WMF，也就跑不了 `install-win.ps1` ——「离线安装」
   在纯净 Win7 上根本不成立。安装包内附 `dotnet48\ndp48-x86-x64-allos-enu.exe`
   （约 115 MB），`install-win.bat` 读注册表 `NDP\v4\Full` 的 `Release` 值
   （< 379893 即低于 4.5.2）按需静默安装。

   > **纯净 Win7 需要重启两次**：装 .NET 4.8 → 重启 → 装 WMF 5.1 → 重启 →
   > 再运行 `install-win.bat` 完成配置。脚本每一步都会明确提示。
   >
   > .NET 4.8 要求系统已打 **SP1 + KB4474419（SHA-2 代码签名）+ KB4490628**。
   > 缺失时安装器返回 5100，脚本会把这三个 KB 号打印出来。

### 构建机必须是 PHP 8.2.x

`vendor/` 由构建机的 composer 产出。在 PHP 8.3+ 上构建可能拉进要求 8.3 的包，
装到目标机（PHP 8.2）上就会崩。`build.sh` 会在组装运行时前校验构建机版本，
不是 8.2.x 直接中止。

### OCR 会在不支持的机器上自动降级

PaddleOCR 依赖的 paddlepaddle **需要 CPU 支持 AVX 指令集**（2011 年前的机器没有）。
安装脚本执行 `import paddleocr` 自检，失败时不中止安装，而是：

1. 打印警告说明原因；
2. 把 `.env` 中的 `OCR_ENABLED` 就地改为 `false`；
3. 继续完成其余安装。

此时工作日志的图片识别关闭、提示改为手工录入（`work_log.ocr_disabled`），
其余功能不受影响。三个 OCR 入口（`OcrService`、`WorkLogService`、`ocr:serve`）
都会校验该开关。

> `.env` 必须**就地替换**而非追加 —— Laravel 的 Env 使用不可变仓库，
> 同一个键以文件中**首次**出现的值为准。改键请用 `batch-helpers/set_env_value.php`。

### OCR 依赖的版本交集很窄

`paddleocr 2.7.3` 要求 `Pillow>=10.0.0`，而 Pillow 提供 `cp38/win_amd64`
轮子的最高版本是 `10.4.0`（11.x 起要求 Python ≥3.9）——交集只有 10.0～10.4。
锁到 9.x 会直接 `ResolutionImpossible`。修改 `scripts/requirements.txt` 后，
请先用下面的命令验证能解析，再跑完整构建：

```bash
pip3 download --platform win_amd64 --python-version 3.8 --only-binary=:all: \
    -d /tmp/wheeltest -r scripts/requirements.txt
```

PaddleOCR 2.x 与 3.x 的接口差异由 `scripts/paddle_compat.py` 抹平
（`ocr()` vs `predict()`、返回结构不同），下游的表格重建与纠错逻辑无需改动。

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

### 交付形态（当前有效）

| 形态 | 命令 | 适用场景 |
|------|------|----------|
| **Windows 全量安装包（Win7 自组装运行时）** | `build.sh --target win` | 首次部署 Windows 7 SP1 x64；包内自带 PHP 8.2/MySQL 5.7/Nginx/Composer，目标机离线安装 |
| **Linux/macOS 全量安装包** | `build.sh --target linux` | 首次部署 Linux/macOS（安装时联网装系统包） |
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
| curl 或 wget | 是 | 下载 PHP/MySQL/Nginx/Python/VC++/WMF 等运行时组件 |
| yakpro-po | 可选 | PHP 源码混淆（`composer global require nicoco007/yakpro-po`） |
| pip3 | 可选 | 下载 OCR Python 离线包 |

### 1.2 构建命令

```bash
# Windows（推荐）：自动下载并组装 Win7 兼容运行时
./deploy/build.sh --target win

# Linux / macOS 安装包
./deploy/build.sh --target linux

# 升级包（仅代码+迁移，不含 schema.sql、storage/、scripts/）
./deploy/build.sh --target win --upgrade

# 跳过 OCR 和混淆（开发/测试用，构建更快）
./deploy/build.sh --target linux --skip-obfuscate --skip-ocr

# 保留 dist/ 以便随后编译 Inno .exe 安装包
./deploy/build.sh --target win --keep-dist

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
| `--keep-dist` | 保留 `deploy/dist/` 不删除 —— **编译 Inno `.exe` 安装包必须加** |
| `--version <X.Y.Z>` | 覆盖 VERSION 文件中的版本号 |
| `--laragon-url <url>` | ⚠️ **本分支请勿使用**：会跳过 Win7 自组装运行时，改打入要求 Windows 10 的 `laragon-wamp.exe` |

环境变量（均可选）:

| 环境变量 | 说明 |
|----------|------|
| `PYTHON_DOWNLOAD_URL` | Windows OCR 用 Python 安装器下载地址（默认官方 **3.8.10**，Win7 上可用的最高版本；3.9 起要求 Windows 8.1） |
| `DOTNET48_DOWNLOAD_URL` | .NET Framework 4.8 离线安装包下载地址（WMF 5.1 的前置） |

### 1.3.1 Windows：自组装运行时（本分支唯一正确方式）

```bash
./deploy/build.sh --target win
```

说明：
- 构建时自动下载并组装 PHP 8.2 / MySQL 5.7 / Nginx / Composer 到
  `deploy/.cache/win7-runtime`，目录结构与 Laragon 一致，后续构建复用缓存
- 同时下载 VC++ 2015-2022 x64 运行库与 WMF 5.1，一并打进安装包
- 打包时整体复制到安装包的 `laragon/` 目录，目标机无需联网

> ⚠️ **不要用 `--laragon-url`。** 传入该参数会跳过上述自组装流程，
> 转而打入 `laragon-wamp.exe` —— 那个安装器自身要求 Windows 10，
> 在 Win7 上双击即失败。该参数仅为兼容旧的 Win10 交付流程而保留。

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
  [8] 组装并复制 Win7 运行时到安装包（PHP 8.2/MySQL 5.7/Nginx/Composer
      + VC++ 运行库 + WMF 5.1 + .NET 4.8）
      ※ --upgrade 跳过本步：升级包不含运行时（否则平白多出约 1.3GB）
      ※ 复用缓存前校验 .build-manifest 与各产物 SHA256，版本漂移即重建
      │
  [9] 生成升级包元数据（仅 --upgrade：env.patch + UPGRADE.md）
      │
  [10] zip 压缩 → deploy/output/dental-clinic-X.Y.Z-{target}.zip
```

### 1.5 运行时构建缓存

`--target win` 会在 `deploy/.cache/` 下建立缓存，后续构建直接复用：

```
deploy/.cache/
├── win7-runtime/           ← 组装好的运行时（PHP/MySQL/Nginx/Composer）
│   └── .build-manifest     ← 产出所依据的下载地址清单，复用前逐行比对
├── php82.zip               ← PHP 8.2.33 原始包
├── mysql57.zip             ← MySQL 5.7.44 原始包
├── nginx-win7.zip          ← Nginx 原始包
├── vc_redist.x64.exe       ← VC++ 2015-2022 运行库
├── wmf51.zip / wmf51-extracted/   ← WMF 5.1（PowerShell 5.1 for Win7）
├── ndp48-x86-x64-allos-enu.exe    ← .NET Framework 4.8 离线包
├── python-3.8.10-amd64.exe ← OCR 用 Python 安装器
└── ocr-wheels-win/         ← OCR 离线 wheel 包
```

**缓存有效性判据**（三者全过才复用）：

1. **齐备**：PHP + MySQL + Composer 都在，避免上次中断留下的残缺产物被反复复用；
2. **版本清单**：`win7-runtime/.build-manifest` 与当前锁定的下载地址逐行一致，
   且 `bin/php/` 下存在锁定版本的目录名；
3. **产物指纹**：各原始包的 SHA256 与 `build.sh` 的 `expected_sha256()` 一致。

> 只查「有没有 `php.exe`」是不够的。本分支历史上出现过 PHP 7.4 的组装结果
> （`.cache/php74-*`），文件名同样是 `php.exe`，存在性检查一律放行，
> 于是错误版本的运行时被静默打进安装包。上面第 2、3 条就是为了堵这个洞。

任一条不过 → 丢弃缓存重新组装。需强制刷新时直接删除 `win7-runtime/`。

### 1.6 产物结构

**Windows 全量安装包（Laragon 安装器模式）**

```
dental-clinic-1.0.0-win/
├── setup.bat                 ← 用户双击此文件（推荐入口）
├── install-win.bat           ← 实际安装逻辑 (18 步)
├── install-win.ps1           ← Windows 安装主逻辑
├── upgrade-win.bat           ← 升级脚本
├── start-win.bat             ← 启动服务
├── stop-win.bat              ← 停止服务
├── laragon-startup.bat       ← 桌面快捷方式入口
├── check.sh / backup-restore.sh / export-data.sh  ← 运维工具
├── .env.deploy               ← 环境变量模板
├── VERSION                   ← 版本号
├── laragon/                  ← 自组装运行时（PHP 8.2/MySQL 5.7/Nginx/Composer）
├── vc_redist.x64.exe         ← VC++ 2015-2022 x64 运行库
├── wmf51/                    ← WMF 5.1（PowerShell 2.0 的机器需先装）
├── python-installer.exe      ← OCR 用 Python 安装器（可选）
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

#### 推荐标准流程（自组装运行时 + 还原基础数据 + 替换部署包）

```
开发机：
1) ./deploy/build.sh --target win
2) 交付 deploy/output/dental-clinic-X.Y.Z-win.zip

目标 Windows 机：
1) 解压安装包
2) 双击 setup.bat（会调用 install-win.bat / install-win.ps1）
3) 覆盖安装同一路径（如 C:\DentalClinic）以替换部署包
4) 安装流程会重建数据库结构并执行 db:seed（初始化基础数据）
```

说明：
- “替换部署包”建议采用**同目录覆盖安装**，避免路径漂移带来的服务与计划任务异常。
- 需要“还原基础数据”时，执行覆盖安装即可；安装程序会按标准初始化数据库并填充基础种子数据。
- 若只想升级且保留历史业务数据，请使用 `upgrade-win.bat`，不要走覆盖安装。

包含 Laragon 的安装包默认会同时打包 OCR 所需的 Python 安装器与 wheels，目标机**无需联网、无需预装任何软件**即可完成核心功能安装。

说明：
- 默认安装会把 `PHP / MySQL / Nginx / Composer / Python(OCR)` 一并处理。
- 只有显式使用 `--no-ocr` 时，安装器才允许跳过 OCR 依赖。
- 如果 OCR 依赖安装或健康检查失败，安装会直接中断，不再以“安装完成但功能不可用”的状态结束。

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
2. 运行 **`build.sh --target win --keep-dist`** 生成并保留 `deploy/dist/`
   > `--keep-dist` 不能省。不加的话 build.sh 在收尾时会删掉 `deploy/dist/`，
   > 而 `build-installer.iss` 的每一条 `Source:` 都指向该目录，Inno 直接报找不到文件。
   > 运行时（PHP/MySQL/Nginx）由 build.sh 自组装并放进 `dist/laragon/`，无需另外准备 Laragon。
3. 用 Inno Setup Compiler 打开 `build-installer.iss` → Compile
4. 生成的 `.exe` 在 `deploy/output/` 目录

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
├── .cache/                 # 运行时与前置组件下载缓存（.gitignore）
│   ├── php82.zip / mysql57.zip / nginx-win7.zip
│   ├── wmf51.zip / vc_redist.x64.exe
│   ├── ndp48-x86-x64-allos-enu.exe   # .NET 4.8 离线包
│   ├── ocr-wheels-win/               # OCR wheels
│   └── win7-runtime/                 # 自组装运行时（含 .build-manifest 版本清单）
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
├── laragon/                       ← 由 laragon-wamp.exe 安装得到
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
| 自组装运行时 PHP+MySQL+Nginx（仅 Windows） | ~300 MB |
| 前置组件 VC++ / WMF 5.1 / .NET 4.8（仅 Windows） | ~210 MB |
| **完整 Windows 安装包（含运行时 + 前置 + OCR）** | **~660 MB** |
| **完整 Windows 安装包（含运行时 + 前置，无 OCR）** | **~460 MB** |
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
