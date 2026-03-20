<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php merge_missing_env.php <env_file> <source_file>\n");
    exit(1);
}

[$script, $envFile, $sourceFile] = $argv;

$env = @file_get_contents($envFile);
if ($env === false) {
    fwrite(STDERR, "Failed to read env file: {$envFile}\n");
    exit(1);
}

$source = @file_get_contents($sourceFile);
if ($source === false) {
    fwrite(STDERR, "Failed to read source file: {$sourceFile}\n");
    exit(1);
}

$added = 0;
foreach (preg_split('/\R/', $source) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }

    $parts = explode('=', $line, 2);
    if (count($parts) < 2) {
        continue;
    }

    $key = trim($parts[0]);
    if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $env)) {
        $env .= PHP_EOL . $line;
        $added++;
        echo "          + {$key}" . PHP_EOL;
    }
}

if ($added > 0 && @file_put_contents($envFile, $env) === false) {
    fwrite(STDERR, "Failed to write env file: {$envFile}\n");
    exit(1);
}

echo $added . PHP_EOL;
