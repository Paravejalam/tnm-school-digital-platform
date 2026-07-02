<?php

declare(strict_types=1);

/**
 * T.N. Memorial Public School Digital Platform
 * Backend Entry Point
 *
 * Boot sequence:
 * Environment -> Configuration -> Container -> Providers -> Router -> Middleware -> Controller -> Response
 *
 * Authority: .github/AGENT.md
 */

define('TNM_START', microtime(true));
define('TNM_BASE_PATH', dirname(__DIR__));

require_once TNM_BASE_PATH . '/config/bootstrap.php';

use App\Core\Kernel;

$kernel = (new Kernel(TNM_BASE_PATH))->bootstrap();

$response = $kernel->handle();

$status = (int) ($response['status'] ?? 200);
if ($status < 100 || $status > 599) {
    $status = 200;
}

http_response_code($status);
header('Content-Type: application/json; charset=utf-8');
header('X-Powered-By: TNM-School-Platform');

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);