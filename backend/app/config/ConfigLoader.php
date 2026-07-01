<?php

namespace App\Config;

class ConfigLoader
{
    private static array $config = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        self::$config = require $path;
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
}
