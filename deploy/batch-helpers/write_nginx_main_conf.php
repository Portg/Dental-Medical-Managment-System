<?php

if ($argc < 5) {
    fwrite(STDERR, "Usage: php write_nginx_main_conf.php <target> <nginx_dir> <sites_dir> <log_dir>\n");
    exit(1);
}

[$script, $target, $nginxDir, $sitesDir, $logDir] = $argv;

$normalize = static function (string $path): string {
    return str_replace('\\', '/', $path);
};

$nginxDir = $normalize($nginxDir);
$sitesDir = $normalize($sitesDir);
$logDir = $normalize($logDir);

$conf = <<<NGINX
worker_processes 1;
error_log "{$logDir}/nginx-error.log";
pid "{$logDir}/nginx.pid";

events {
    worker_connections 1024;
}

http {
    include "{$nginxDir}/conf/mime.types";
    default_type application/octet-stream;
    access_log "{$logDir}/nginx-access.log";
    sendfile on;
    keepalive_timeout 65;
    client_max_body_size 100M;
    include "{$sitesDir}/*.conf";
}
NGINX;

if (@file_put_contents($target, $conf . PHP_EOL) === false) {
    fwrite(STDERR, "Failed to write Nginx main config: {$target}\n");
    exit(1);
}
