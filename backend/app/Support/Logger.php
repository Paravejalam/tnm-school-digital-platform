<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Runtime Logger
 *
 * Writes structured JSON log entries to a file.
 * Log levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
 * Entries include: timestamp (UTC ISO-8601), level, message, context.
 *
 * Authority: .github/AGENT.md Section 24.9 — Logging Requirements
 *
 * SECURITY: Tokens, passwords, and secrets must never appear in log context.
 *           Callers are responsible for scrubbing sensitive data before logging.
 */
class Logger
{
    private const LEVELS = [
        'DEBUG'    => 10,
        'INFO'     => 20,
        'WARNING'  => 30,
        'ERROR'    => 40,
        'CRITICAL' => 50,
    ];

    private string $path;
    private int $minimumLevel;

    public function __construct(
        string $path  = '',
        string $level = 'DEBUG'
    ) {
        if ($path === '') {
            $path = dirname(__DIR__, 2) . '/logs/app.log';
        }
        $this->path = $path;

        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $this->minimumLevel = self::LEVELS[strtoupper($level)] ?? self::LEVELS['DEBUG'];
    }

    // -------------------------------------------------------------------------
    // Public logging methods
    // -------------------------------------------------------------------------

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
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

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    // -------------------------------------------------------------------------
    // Core log writer
    // -------------------------------------------------------------------------

    public function log(string $level, string $message, array $context = []): void
    {
        $normalized = strtoupper($level);
        if (!$this->shouldLog($normalized)) {
            return;
        }

        $entry = json_encode([
            'timestamp' => gmdate('c'),
            'level'     => $normalized,
            'message'   => $message,
            'context'   => $context,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($entry !== false) {
            file_put_contents($this->path, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function shouldLog(string $level): bool
    {
        return (self::LEVELS[$level] ?? 0) >= $this->minimumLevel;
    }
}