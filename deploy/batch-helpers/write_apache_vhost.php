<?php

/**
 * 生成 Apache 的 VirtualHost 配置（替代 write_nginx_conf.php）。
 *
 * 与 Nginx 方案的差别：
 *   - Nginx 不能自己跑 PHP，必须再维护一个 php-cgi 监听 9000，start-win.bat 里
 *     有一整段在探测端口、拉起进程、轮询就绪。Apache + mod_php 把 PHP 跑在
 *     自己进程里，这一整块负担消失。
 *   - rewrite 规则不写在这里：public/.htaccess 已经有一份，而且是 2026-03-23
 *     为修 Apache 下 POST 路由 404 专门加的（提交 d85a04a），是被跑通过的。
 *     所以这里只需要 AllowOverride All 把它启用。
 *
 * 用法: php write_apache_vhost.php <目标文件> <DocumentRoot> [端口]
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php write_apache_vhost.php <target> <document_root> [port]\n");
    exit(1);
}

$target = $argv[1];
$root   = $argv[2];
$port   = isset($argv[3]) && $argv[3] !== '' ? (int) $argv[3] : 80;

if ($port < 1 || $port > 65535) {
    fwrite(STDERR, "Invalid port: {$argv[3]}\n");
    exit(1);
}

// Apache 配置里一律用正斜杠：反斜杠在 httpd.conf 里是转义字符，
// C:\DentalClinic\... 会被解析成乱七八糟的东西。
$root = rtrim(str_replace('\\', '/', $root), '/');

if ($root === '') {
    fwrite(STDERR, "Document root must not be empty\n");
    exit(1);
}

// 端口 80 时**不能**再写 Listen：XAMPP 的 httpd.conf 自带 `Listen 80`，
// 重复声明同一个 IP:port 会让 Apache 直接拒绝启动：
//   AH00526: Cannot define multiple Listeners on the same IP:port
// （这条是用真 Apache 校验时踩出来的，不是推测。）
// 非默认端口才需要补一行 Listen，否则 <VirtualHost *:8080> 没有监听者、不生效。
$listenLine = $port === 80 ? '' : "Listen {$port}\n\n";

$conf = <<<APACHE
# 由 deploy/batch-helpers/write_apache_vhost.php 生成，请勿手工编辑。
# 重新生成: php write_apache_vhost.php <target> <document_root> [port]

{$listenLine}<VirtualHost *:{$port}>
    ServerName localhost
    DocumentRoot "{$root}"

    <Directory "{$root}">
        # AllowOverride All 是关键：Laravel 的前端控制器 rewrite 在
        # public/.htaccess 里，不开这个整站只有首页能访问，
        # 其余路径（含 POST /login）全部 404。
        AllowOverride All
        Require all granted

        Options -Indexes +FollowSymLinks
        DirectoryIndex index.php index.html
    </Directory>

    # 上传体积上限对齐原 Nginx 配置的 client_max_body_size 100M。
    # 注意 PHP 侧的 upload_max_filesize / post_max_size 也要够大，
    # 两者取小值生效。
    LimitRequestBody 104857600

    # .ht* 不允许被下载（Apache 默认配置里通常已有，这里显式兜一道）
    <FilesMatch "^\.ht">
        Require all denied
    </FilesMatch>

    ErrorLog "logs/dental-error.log"
    CustomLog "logs/dental-access.log" common
</VirtualHost>
APACHE;

if (@file_put_contents($target, $conf . PHP_EOL) === false) {
    fwrite(STDERR, "Failed to write Apache vhost config: {$target}\n");
    exit(1);
}
