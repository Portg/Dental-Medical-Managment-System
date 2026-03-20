<?php

if ($argc < 8) {
    fwrite(STDERR, "Usage: php install_update_env.php <target> <app_url> <db_host> <db_port> <db_name> <db_user> <db_pass>\n");
    exit(1);
}

[$script, $target, $appUrl, $dbHost, $dbPort, $dbName, $dbUser, $dbPass] = $argv;

$env = @file_get_contents($target);
if ($env === false) {
    fwrite(STDERR, "Failed to read env file: {$target}\n");
    exit(1);
}

$env = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $env);
$env = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $env);
$env = preg_replace('/^APP_URL=.*/m', 'APP_URL=' . $appUrl, $env);
$env = preg_replace('/^DB_HOST=.*/m', 'DB_HOST=' . $dbHost, $env);
$env = preg_replace('/^DB_PORT=.*/m', 'DB_PORT=' . $dbPort, $env);
$env = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=' . $dbName, $env);
$env = preg_replace('/^DB_USERNAME=.*/m', 'DB_USERNAME=' . $dbUser, $env);
$env = preg_replace('/^DB_PASSWORD=.*/m', 'DB_PASSWORD=' . $dbPass, $env);

if (@file_put_contents($target, $env) === false) {
    fwrite(STDERR, "Failed to write env file: {$target}\n");
    exit(1);
}
