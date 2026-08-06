<?php

if ($argc < 4) {
    fwrite(STDERR, "Usage: php write_nginx_conf.php <target> <root> <nginx_dir>\n");
    exit(1);
}

[$script, $target, $root, $nginxDir] = $argv;
$nginxDir = str_replace('\\', '/', $nginxDir);

$conf = <<<NGINX
server {
    listen 80;
    server_name localhost;
    root "{$root}";

    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include "{$nginxDir}/conf/fastcgi_params";
    }

    location ~ /\.ht {
        deny all;
    }

    client_max_body_size 100M;
}
NGINX;

if (@file_put_contents($target, $conf . PHP_EOL) === false) {
    fwrite(STDERR, "Failed to write nginx config: {$target}\n");
    exit(1);
}
