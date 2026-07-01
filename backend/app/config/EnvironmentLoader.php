<?php

declare(strict_types=1);

namespace App\Config;

class EnvironmentLoader
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (!str_contains($trimmed, '=')) {
                continue;
            }

            [$rawKey, $rawValue] = array_pad(explode('=', $trimmed, 2), 2, '');
            $key = trim($rawKey);
            $value = trim($rawValue);

            if ($key === '' || array_key_exists($key, $_ENV)) {
                continue;
            }

            if (($value !== '' && ($value[0] === '"' || $value[0] === "'")) && strlen($value) >= 2) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv(sprintf('%s=%s', $key, $value));
        }
    }
}
