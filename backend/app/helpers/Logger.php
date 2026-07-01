<?php

namespace App\Helpers;

class Logger
{
    private string $path;

    public function __construct(string $path = __DIR__ . '/../../logs/app.log')
    {
        $this->path = $path;
        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0777, true);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context = []): void
    {
        $entry = sprintf(
            '[%s] %s %s' . PHP_EOL,
            gmdate('c'),
            $level,
            json_encode(['message' => $message, 'context' => $context], JSON_UNESCAPED_SLASHES)
        );

        file_put_contents($this->path, $entry, FILE_APPEND);
    }
}
