<?php

namespace App\Auth;

use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Throwable;

class AuthMiddleware
{
    public function __construct(private ?JwtHelper $jwtHelper = null)
    {
    }

    public function handle(?RequestHelper $request, callable $next): mixed
    {
        if (!$request instanceof RequestHelper || !$this->jwtHelper instanceof JwtHelper) {
            return ResponseHelper::error('Authentication middleware unavailable.', 503);
        }

        $token = $this->token($request);
        if ($token === null || $token === '') {
            return ResponseHelper::error('Authentication token is required.', 401);
        }

        try {
            $this->jwtHelper->decode($token);
        } catch (Throwable) {
            return ResponseHelper::error('Authentication token is invalid.', 401);
        }

        return $next();
    }

    private function token(RequestHelper $request): ?string
    {
        $json = $request->json();
        if (is_array($json) && isset($json['token']) && is_string($json['token'])) {
            return $json['token'];
        }

        $requestData = $request->all();
        $headers = $requestData['headers'] ?? [];
        if (!is_array($headers)) {
            return null;
        }

        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'authorization' && is_string($value)) {
                return str_starts_with($value, 'Bearer ') ? substr($value, 7) : $value;
            }
        }

        return null;
    }
}