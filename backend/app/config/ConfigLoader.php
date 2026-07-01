<?php

declare(strict_types=1);

namespace App\Config;

class ConfigLoader
{
    private static array $config = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $name = pathinfo($path, PATHINFO_FILENAME);
        $loaded = require $path;
        self::$config[$name] = is_array($loaded) ? $loaded : [];
    }

    public static function loadDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (basename($file) === 'bootstrap.php') {
                continue;
            }

            self::load($file);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function all(): array
    {
        return self::$config;
    }
}
