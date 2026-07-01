<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\ResponseHelper;

class Router
{
    public function dispatch(string $method, string $path): array
    {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = rtrim($path, '/') ?: '/';

        if ($normalizedMethod === 'GET' && $normalizedPath === '/health') {
            return ResponseHelper::success([
                'service' => 'tnm-school-platform',
                'status' => 'ok',
            ]);
        }

        if ($normalizedMethod === 'GET' && $normalizedPath === '/') {
            return ResponseHelper::success([
                'service' => 'tnm-school-platform',
                'status' => 'ready',
            ]);
        }

        return ResponseHelper::error('Route not found', 404);
    }
}
