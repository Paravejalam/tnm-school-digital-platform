<?php

use App\Controllers\BaseController;

$router = new class extends BaseController {
    public function dispatch(string $method, string $path): array
    {
        if ($method === 'GET' && $path === '/health') {
            return $this->json(['service' => 'tnm-school-platform', 'status' => 'ok']);
        }

        return $this->error('Route not found', 404);
    }
};

return $router;
