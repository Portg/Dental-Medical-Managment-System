# 构建安装包指南

将 Laragon + 项目代码打包成一个 `.exe` 安装包，用户双击即可完成全部部署。

## 前置要求

- Windows 10/11 电脑（用于构建）
- [Inno Setup 6](https://jrsoftware.org/isdl.php)（免费，下载安装即可）

## 构建步骤

### 1. 下载 Laragon Portable

从 [Laragon 官网](https://laragon.org/download/) 下载 **Laragon Full (64-bit)** 的 Portable 版本。

解压到本目录下，形成如下结构：

```
deploy/
├── laragon-portable/
│   ├── bin/
│   │   ├── php/php-8.2.*/
│   │   ├── mysql/mysql-8.0.*/
│   │   ├── nginx/nginx-*/
│   │   ├── nodejs/node-*/
│   │   ├── redis/redis-*/
│   │   └── composer/
│   ├── etc/
│   ├── www/
│   └── laragon.exe
├── build-installer.iss
├── post-install.bat
├── laragon-startup.bat
└── build-README.md
```

### 2. 放入项目代码

将项目代码复制到 `laragon-portable/www/dental/`：

```bash
# 在项目根目录执行（Git Bash）
rsync -av --exclude='node_modules' --exclude='vendor' --exclude='.git' \
  --exclude='storage/logs/*.log' --exclude='deploy' \
  ./ deploy/laragon-portable/www/dental/
```

或者手动复制（排除 `node_modules`、`vendor`、`.git`、`deploy` 目录）。

### 3. 配置 Nginx

编辑 `laragon-portable/etc/nginx/sites-enabled/auto.dental.conf`（如无则新建）：

```nginx
server {
    listen 80;
    server_name localhost;
    root "C:/DentalClinic/laragon/www/dental/public";

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    client_max_body_size 100M;
}
```

> 安装目录默认为 `C:\DentalClinic`，如需修改也需同步更新此配置。

### 4. 编译安装包

1. 打开 **Inno Setup Compiler**
2. File → Open → 选择 `deploy/build-installer.iss`
3. 点击 **Compile**（或按 Ctrl+F9）
4. 等待编译完成
5. 生成的安装包在 `deploy/output/牙科诊所管理系统_安装包_v1.0.exe`

### 5. 测试

在一台干净的 Windows 电脑上测试：

1. 双击 `.exe` 安装
2. 按向导完成（自动配置约需 3-5 分钟）
3. 双击桌面快捷方式启动
4. 浏览器打开 `http://localhost/dental`
5. 用 `admin@example.com` / `password` 登录

## 安装包大小

| 组件 | 大小 |
|------|------|
| Laragon Portable Full | ~300 MB |
| 项目代码 | ~80 MB |
| 压缩后安装包 | ~200-250 MB |

## 注意事项

- 安装包用 LZMA2 压缩，最终 `.exe` 约 200-250MB
- 安装时需要管理员权限（MySQL 服务需要）
- `post-install.bat` 可幂等重复运行，不会破坏已有数据
- 如需更新项目代码，只需替换 `www/dental/` 目录内容后重新编译
- 版本号在 `build-installer.iss` 的 `#define MyAppVersion` 处修改
