<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php update_ocr_env_path.php <target> <ocr_python_path>\n");
    exit(1);
}

[$script, $target, $ocrPythonPath] = $argv;

$env = @file_get_contents($target);
if ($env === false) {
    fwrite(STDERR, "Failed to read env file: {$target}\n");
    exit(1);
}

$env = preg_replace('/^OCR_PYTHON_PATH=.*/m', 'OCR_PYTHON_PATH=' . $ocrPythonPath, $env);

if (@file_put_contents($target, $env) === false) {
    fwrite(STDERR, "Failed to write env file: {$target}\n");
    exit(1);
}
