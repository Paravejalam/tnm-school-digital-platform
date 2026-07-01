<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\AuthController;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Support\AppContainer;

class Router
{
    public function __construct(private ?AppContainer $container = null)
    {
    }

    public function dispatch(string $method, string $path, ?RequestHelper $request = null): array
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

        if ($normalizedMethod === 'POST' && $normalizedPath === '/auth/login') {
            return $this->authController()->login($this->request($request));
        }

        if ($normalizedMethod === 'POST' && $normalizedPath === '/auth/register') {
            return $this->authController()->register($this->request($request));
        }

        if ($normalizedMethod === 'POST' && $normalizedPath === '/auth/logout') {
            return $this->authController()->logout($this->request($request));
        }

        return ResponseHelper::error('Route not found', 404);
    }

    private function authController(): AuthController
    {
        $controller = $this->container?->get(AuthController::class) ?? $this->container?->get('auth.controller');

        return $controller instanceof AuthController ? $controller : new AuthController();
    }

    private function request(?RequestHelper $request): RequestHelper
    {
        return $request ?? RequestHelper::fromGlobals();
    }
}