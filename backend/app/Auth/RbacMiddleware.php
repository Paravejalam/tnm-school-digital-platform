<?php

declare(strict_types=1);

namespace App\Auth;

use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use PDO;
use Throwable;
use TypeError;

class RbacMiddleware
{
    private const RESOURCE_PERMISSION_PREFIX = [
        '/students'           => 'students',
        '/teachers'           => 'teachers',
        '/academic-sessions'  => 'academic-sessions',
        '/classes'            => 'classes',
        '/sections'           => 'sections',
        '/subjects'           => 'subjects',
        '/attendance'         => 'attendance',
        '/attendance-records' => 'attendance',
        '/timetables'         => 'timetables',
        '/periods'            => 'periods',
        '/holiday-calendars'  => 'holiday-calendars',
        '/settings'           => 'system-settings',
        '/audit-logs'         => 'audit-logs',
    ];

    private const METHOD_ACTION = [
        'GET'    => 'view',
        'POST'   => 'create',
        'PUT'    => 'update',
        'PATCH'  => 'update',
        'DELETE' => 'delete',
    ];

    public function __construct(
        private ?JwtHelper $jwtHelper = null,
        private ?PDO $database = null
    ) {
    }

    public function handle(?RequestHelper $request, callable $next): mixed
    {
        if (!$request instanceof RequestHelper || !$this->jwtHelper instanceof JwtHelper) {
            return ResponseHelper::error('Authorization middleware unavailable.', 503);
        }

        $token = $this->token($request);
        if ($token === null || $token === '') {
            return ResponseHelper::error('Authorization token is required.', 401);
        }

        try {
            $payload = $this->jwtHelper->decode($token);
        } catch (Throwable) {
            return ResponseHelper::error('Authorization token is invalid.', 401);
        }

        $userId = isset($payload['id']) ? (int) $payload['id'] : 0;
        if ($userId <= 0) {
            return ResponseHelper::error('Authorization token is invalid.', 401);
        }

        $path   = rtrim($request->path(), '/') ?: '/';
        $method = strtoupper($request->method());

        if ($method === 'HEAD' || $method === 'OPTIONS') {
            return $next();
        }

        $prefix = $this->resolvePermissionPrefix($path);
        if ($prefix === null) {
            return $next();
        }

        $action = self::METHOD_ACTION[$method] ?? null;
        if ($action === null) {
            return $next();
        }

        $slug = $prefix . '.' . $action;

        if (!$this->database instanceof PDO) {
            return ResponseHelper::error('Authorization service unavailable.', 503);
        }

        try {
            $statement = $this->database->prepare(
                'SELECT 1 FROM user_roles ur
                 JOIN role_permissions rp ON ur.role_id = rp.role_id
                 JOIN permissions p ON rp.permission_id = p.id
                 WHERE ur.user_id = :user_id AND p.slug = :slug
                 LIMIT 1'
            );
        } catch (TypeError) {
            return ResponseHelper::error('Authorization service unavailable.', 503);
        }

        $statement->execute([
            'user_id' => $userId,
            'slug'    => $slug,
        ]);

        if ($statement->fetchColumn() === false) {
            return ResponseHelper::error('Insufficient permissions.', 403);
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

    private function resolvePermissionPrefix(string $path): ?string
    {
        foreach (self::RESOURCE_PERMISSION_PREFIX as $routePrefix => $permPrefix) {
            if ($path === $routePrefix || str_starts_with($path, $routePrefix . '/')) {
                return $permPrefix;
            }
        }

        return null;
    }
}
