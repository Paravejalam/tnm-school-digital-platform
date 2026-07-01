<?php

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value !== false && $value !== null ? $value : $default;
    }
}

if (!function_exists('app_url')) {
    function app_url(): string
    {
        return env('APP_URL', 'http://localhost');
    }
}

if (!function_exists('app_mode')) {
    function app_mode(): string
    {
        return env('APP_MODE', 'development');
    }
}
