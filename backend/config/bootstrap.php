<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $basePath = __DIR__ . '/../app/';
    $relativePath = str_replace('\\', '/', $relative);
    $path = $basePath . $relativePath . '.php';

    if (is_file($path)) {
        require_once $path;
        return;
    }

    $segments = explode('/', $relativePath);
    $directory = $basePath;

    for ($index = 0; $index < count($segments) - 1; $index++) {
        $segment = $segments[$index];
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
    $matches = glob($directory . $fileName);
    if ($matches !== false && isset($matches[0]) && is_file($matches[0])) {
        require_once $matches[0];
    }
});

require_once __DIR__ . '/../app/helpers/functions.php';

use App\Config\ConfigLoader;
use App\Config\EnvironmentLoader;

EnvironmentLoader::load(__DIR__ . '/../.env');
ConfigLoader::load(__DIR__ . '/app.php');
