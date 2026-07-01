<?php

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('app_url')) {
    function app_url(): string
    {
        return env('APP_URL', 'http://localhost');
    }
}
