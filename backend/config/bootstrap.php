<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/../app/helpers/functions.php';

use App\Config\ConfigLoader;
use App\Config\EnvironmentLoader;

EnvironmentLoader::load(__DIR__ . '/../.env');
ConfigLoader::load(__DIR__ . '/app.php');
