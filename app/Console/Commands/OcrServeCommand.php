<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class OcrServeCommand extends Command
{
    protected $signature = 'ocr:serve
                            {--host=127.0.0.1 : Bind address}
                            {--port=5000 : Listen port}';

    protected $description = 'Start the persistent OCR recognition server (PaddleOCR)';

    public function handle(): int
    {
        $pythonPath = config('services.ocr.python_path', 'python3');
        $scriptPath = base_path('scripts/ocr_server.py');

        if (!file_exists($scriptPath)) {
            $this->error("OCR server script not found: {$scriptPath}");
            return self::FAILURE;
        }

        $host = $this->option('host');
        $port = $this->option('port');

        $this->info("Starting OCR server on http://{$host}:{$port} ...");
        $this->info('Press Ctrl+C to stop.');
        $this->newLine();

        $process = new Process([
            $pythonPath, $scriptPath,
            '--host', $host,
            '--port', (string) $port,
        ]);
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            // Stream Python stdout/stderr to console in real time
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('OCR server exited with error.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
