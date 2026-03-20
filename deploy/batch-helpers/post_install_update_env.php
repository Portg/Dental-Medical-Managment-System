<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php post_install_update_env.php <env_file>\n");
    exit(1);
}

[$script, $envFile] = $argv;

$env = @file_get_contents($envFile);
if ($env === false) {
    fwrite(STDERR, "[错误] .env 文件不存在: {$envFile}\n");
    exit(1);
}

$env = preg_replace('/^APP_URL=.*/m', 'APP_URL=http://localhost/dental', $env);
$env = preg_replace('/^APP_NAME=.*/m', 'APP_NAME=牙科诊所管理系统', $env);
$env = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=pristine_dental', $env);
$env = preg_replace('/^DB_USERNAME=.*/m', 'DB_USERNAME=root', $env);
$env = preg_replace('/^DB_PASSWORD=.*/m', 'DB_PASSWORD=', $env);

if (@file_put_contents($envFile, $env) === false) {
    fwrite(STDERR, "Failed to write env file: {$envFile}\n");
    exit(1);
}
