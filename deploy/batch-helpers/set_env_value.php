<?php

/**
 * 就地设置 .env 中的某个键。
 *
 * 必须"替换"而不是"追加"：Laravel 的 Env 使用不可变（immutable）仓库，
 * 同一个键在文件中出现多次时以**第一次**出现的值为准，
 * 追加到文件末尾的赋值会被静默忽略。
 *
 * 用法: php set_env_value.php <env_file> <KEY> <VALUE>
 */

if ($argc < 4) {
    fwrite(STDERR, "Usage: php set_env_value.php <env_file> <KEY> <VALUE>\n");
    exit(1);
}

[$script, $target, $key, $value] = $argv;

if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) {
    fwrite(STDERR, "Invalid env key: {$key}\n");
    exit(1);
}

$env = @file_get_contents($target);
if ($env === false) {
    fwrite(STDERR, "Failed to read env file: {$target}\n");
    exit(1);
}

$line = $key . '=' . $value;
$pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

if (preg_match($pattern, $env)) {
    $env = preg_replace($pattern, $line, $env, 1);
} else {
    if ($env !== '' && substr($env, -1) !== "\n") {
        $env .= "\n";
    }
    $env .= $line . "\n";
}

if (@file_put_contents($target, $env) === false) {
    fwrite(STDERR, "Failed to write env file: {$target}\n");
    exit(1);
}
