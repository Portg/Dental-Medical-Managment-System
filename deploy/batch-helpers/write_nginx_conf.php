<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php write_nginx_conf.php <target> <root>\n");
    exit(1);
}

[$script, $target, $root] = $argv;

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
        include fastcgi_params;
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
