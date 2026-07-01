<?php

namespace App\Core;

class Router
{
    public function dispatch(string $method, string $path): array
    {
        if ($method === 'GET' && $path === '/health') {
            return [
                'status' => 'success',
                'data' => ['service' => 'tnm-school-platform', 'status' => 'ok'],
                'timestamp' => gmdate('c'),
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Route not found',
            'timestamp' => gmdate('c'),
        ];
    }
}
