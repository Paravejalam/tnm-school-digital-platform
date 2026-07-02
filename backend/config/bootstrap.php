<?php

declare(strict_types=1);

/**
 * T.N. Memorial Public School Digital Platform
 * Application Bootstrap — PSR-4 Autoloader + Environment + Configuration
 * Authority: .github/AGENT.md
 */

// ---------------------------------------------------------------------------
// PSR-4 Autoloader (App\ -> backend/app/)
// ---------------------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative     = substr($class, strlen($prefix));
    $basePath      = __DIR__ . '/../app/';
    $relativePath  = str_replace('\\', '/', $relative);
    $path          = $basePath . $relativePath . '.php';

    if (is_file($path)) {
        require_once $path;
        return;
    }

    // Case-insensitive fallback scan for module directories
    $segments  = explode('/', $relativePath);
    $directory = $basePath;

    for ($i = 0; $i < count($segments) - 1; $i++) {
        $segment = $segments[$i];
        $matches = glob($directory . '*', GLOB_ONLYDIR);
        if ($matches === false) {
            return;
        }

        $resolved = null;
        foreach ($matches as $match) {
            if (strcasecmp(basename($match), $segment) === 0) {
                $resolved = $match . '/';
                break;
            }
        }

        if ($resolved === null) {
            return;
        }

        $directory = $resolved;
    }

    $fileName = $segments[count($segments) - 1] . '.php';
    $matches  = glob($directory . $fileName);
    if ($matches !== false && isset($matches[0]) && is_file($matches[0])) {
        require_once $matches[0];
    }
});

// ---------------------------------------------------------------------------
// Global helper functions (env(), app_url(), app_mode())
// ---------------------------------------------------------------------------
require_once __DIR__ . '/../app/helpers/functions.php';

// ---------------------------------------------------------------------------
// Environment — load .env before any config file references env()
// ---------------------------------------------------------------------------
use App\Config\EnvironmentLoader;
use App\Config\ConfigLoader;

EnvironmentLoader::load(__DIR__ . '/../.env');

// ---------------------------------------------------------------------------
// Configuration — load all config files in /config (except this file)
// ---------------------------------------------------------------------------
ConfigLoader::loadDirectory(__DIR__);