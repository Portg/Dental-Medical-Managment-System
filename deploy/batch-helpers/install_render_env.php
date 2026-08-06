<?php

if ($argc < 9) {
    fwrite(STDERR, "Usage: php install_render_env.php <template> <target> <db_host> <db_port> <db_name> <db_user> <db_pass> <app_url> [ocr_python_path]\n");
    exit(1);
}

[$script, $template, $target, $dbHost, $dbPort, $dbName, $dbUser, $dbPass, $appUrl] = $argv;
$ocrPythonPath = $argv[9] ?? '';

if ($dbPass === '__DENTAL_DB_PASSWORD_EMPTY__') {
    $dbPass = '';
} elseif ($dbPass === '__DENTAL_DB_PASSWORD_FROM_ENV__') {
    $dbPass = getenv('DENTAL_DB_PASSWORD');
    if ($dbPass === false) {
        fwrite(STDERR, "DENTAL_DB_PASSWORD is not set\n");
        exit(1);
    }
}

$tpl = @file_get_contents($template);
if ($tpl === false) {
    fwrite(STDERR, "Failed to read template: {$template}\n");
    exit(1);
}

$replacements = [
    '{{DB_HOST}}' => $dbHost,
    '{{DB_PORT}}' => $dbPort,
    '{{DB_DATABASE}}' => $dbName,
    '{{DB_USERNAME}}' => $dbUser,
    '{{DB_PASSWORD}}' => $dbPass,
    '{{APP_URL}}' => $appUrl,
    '{{OCR_PYTHON_PATH}}' => $ocrPythonPath,
];

$env = str_replace(array_keys($replacements), array_values($replacements), $tpl);
if (@file_put_contents($target, $env) === false) {
    fwrite(STDERR, "Failed to write target: {$target}\n");
    exit(1);
}
