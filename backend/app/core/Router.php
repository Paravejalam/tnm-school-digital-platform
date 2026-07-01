<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\AuthController;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Student\StudentController;
use App\Teacher\TeacherController;
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

        if ($normalizedMethod === 'GET' && $normalizedPath === '/students') {
            return $this->studentController()->index($this->request($request));
        }

        if ($normalizedMethod === 'POST' && $normalizedPath === '/students') {
            return $this->studentController()->store($this->request($request));
        }

        if (preg_match('#^/students/(\d+)$#', $normalizedPath, $matches) === 1) {
            $studentId = (int) $matches[1];

            if ($normalizedMethod === 'GET') {
                return $this->studentController()->show($studentId);
            }

            if ($normalizedMethod === 'PUT' || $normalizedMethod === 'PATCH') {
                return $this->studentController()->update($studentId, $this->request($request));
            }

            if ($normalizedMethod === 'DELETE') {
                return $this->studentController()->destroy($studentId);
            }
        }

        if ($normalizedMethod === 'GET' && $normalizedPath === '/teachers') {
            return $this->teacherController()->index($this->request($request));
        }

        if ($normalizedMethod === 'POST' && $normalizedPath === '/teachers') {
            return $this->teacherController()->store($this->request($request));
        }

        if (preg_match('#^/teachers/(\d+)$#', $normalizedPath, $matches) === 1) {
            $teacherId = (int) $matches[1];

            if ($normalizedMethod === 'GET') {
                return $this->teacherController()->show($teacherId);
            }

            if ($normalizedMethod === 'PUT' || $normalizedMethod === 'PATCH') {
                return $this->teacherController()->update($teacherId, $this->request($request));
            }

            if ($normalizedMethod === 'DELETE') {
                return $this->teacherController()->destroy($teacherId);
            }
        }

        return ResponseHelper::error('Route not found', 404);
    }

    private function authController(): AuthController
    {
        $controller = $this->container?->get(AuthController::class) ?? $this->container?->get('auth.controller');

        return $controller instanceof AuthController ? $controller : new AuthController();
    }

    private function studentController(): StudentController
    {
        $controller = $this->container?->get(StudentController::class) ?? $this->container?->get('student.controller');

        return $controller instanceof StudentController ? $controller : new StudentController();
    }

    private function teacherController(): TeacherController
    {
        $controller = $this->container?->get(TeacherController::class) ?? $this->container?->get('teacher.controller');

        return $controller instanceof TeacherController ? $controller : new TeacherController();
    }

    private function request(?RequestHelper $request): RequestHelper
    {
        return $request ?? RequestHelper::fromGlobals();
    }
}