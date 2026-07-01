<?php

namespace App\Support;

class Logger
{
    private string $path;
    private string $level;

    public function __construct(string $path = __DIR__ . '/../../logs/app.log', string $level = 'DEBUG')
    {
        $this->path = $path;
        $this->level = strtoupper($level);

        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0777, true);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $entry = [
            'timestamp' => gmdate('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
        ];

        file_put_contents($this->path, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

    private function shouldLog(string $level): bool
    {
        $levels = ['DEBUG' => 10, 'INFO' => 20, 'WARNING' => 30, 'ERROR' => 40];
        return ($levels[$level] ?? 0) >= ($levels[$this->level] ?? 0);
    }
}
