<?php

declare(strict_types=1);

namespace App\Core;

use App\AcademicClass\AcademicClassController;
use App\AcademicSession\AcademicSessionController;
use App\Attendance\AttendanceController;
use App\AttendanceRecord\AttendanceRecordController;
use App\Auth\AuthController;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Period\PeriodController;
use App\Section\SectionController;
use App\Student\StudentController;
use App\Subject\SubjectController;
use App\Teacher\TeacherController;
use App\Timetable\TimetableController;
use App\HolidayCalendar\HolidayCalendarController;
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

        $resource = $this->resourceController($normalizedPath);
        if ($resource !== null) {
            [$basePath, $controller] = $resource;

            if ($normalizedMethod === 'GET' && $normalizedPath === $basePath) {
                return $controller->index($this->request($request));
            }

            if ($normalizedMethod === 'POST' && $normalizedPath === $basePath) {
                return $controller->store($this->request($request));
            }

            if (preg_match('#^' . preg_quote($basePath, '#') . '/(\d+)$#', $normalizedPath, $matches) === 1) {
                $id = (int) $matches[1];

                if ($normalizedMethod === 'GET') {
                    return $controller->show($id);
                }

                if ($normalizedMethod === 'PUT' || $normalizedMethod === 'PATCH') {
                    return $controller->update($id, $this->request($request));
                }

                if ($normalizedMethod === 'DELETE') {
                    return $controller->destroy($id);
                }
            }
        }

        return ResponseHelper::error('Route not found', 404);
    }

    private function authController(): AuthController
    {
        $controller = $this->container?->get(AuthController::class) ?? $this->container?->get('auth.controller');

        return $controller instanceof AuthController ? $controller : new AuthController();
    }

    private function resourceController(string $path): ?array
    {
        $resources = [
            '/students' => [StudentController::class, 'student.controller'],
            '/teachers' => [TeacherController::class, 'teacher.controller'],
            '/academic-sessions' => [AcademicSessionController::class, 'academicsession.controller'],
            '/classes' => [AcademicClassController::class, 'academicclass.controller'],
            '/sections' => [SectionController::class, 'section.controller'],
            '/subjects' => [SubjectController::class, 'subject.controller'],
            '/attendance' => [AttendanceController::class, 'attendance.controller'],
            '/attendance-records' => [AttendanceRecordController::class, 'attendancerecord.controller'],
            '/timetables' => [TimetableController::class, 'timetable.controller'],
            '/periods' => [PeriodController::class, 'period.controller'],
            '/holiday-calendars' => [HolidayCalendarController::class, 'holidaycalendar.controller'],
        ];

        foreach ($resources as $basePath => [$className, $serviceKey]) {
            if ($path === $basePath || str_starts_with($path, $basePath . '/')) {
                $controller = $this->container?->get($className) ?? $this->container?->get($serviceKey);

                return $controller instanceof $className ? [$basePath, $controller] : null;
            }
        }

        return null;
    }

    private function request(?RequestHelper $request): RequestHelper
    {
        return $request ?? RequestHelper::fromGlobals();
    }
}