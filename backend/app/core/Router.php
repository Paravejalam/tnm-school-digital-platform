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
use App\HolidayCalendar\HolidayCalendarController;
use App\Period\PeriodController;
use App\Section\SectionController;
use App\Student\StudentController;
use App\Subject\SubjectController;
use App\Support\AppContainer;
use App\Teacher\TeacherController;
use App\Timetable\TimetableController;

/**
 * Application Router
 *
 * Dispatches HTTP requests to controllers.
 * Route pattern: METHOD /path -> Controller::action
 *
 * Architecture is locked. Add new routes by extending the resourceController map.
 * Do NOT add business logic here. Route registration only.
 *
 * Authority: .github/AGENT.md
 */
class Router
{
    public function __construct(private ?AppContainer $container = null)
    {
    }

    public function dispatch(string $method, string $path, ?RequestHelper $request = null): array
    {
        $m = strtoupper($method);
        $p = rtrim($path, '/') ?: '/';

        // ------------------------------------------------------------------
        // Health check — GET /health
        // Required fields: status, application, environment, php_version,
        //                  timestamp, uptime (AGENT.md Sprint 2 requirement)
        // ------------------------------------------------------------------
        if ($m === 'GET' && $p === '/health') {
            $startTime = defined('TNM_START') ? TNM_START : microtime(true);
            $uptime    = round(microtime(true) - $startTime, 4);

            return ResponseHelper::success([
                'status'      => 'ok',
                'application' => 'tnm-school-platform',
                'environment' => (string) ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'unknown'),
                'php_version' => PHP_VERSION,
                'timestamp'   => gmdate('c'),
                'uptime'      => $uptime,
            ]);
        }

        // ------------------------------------------------------------------
        // API root — GET /
        // ------------------------------------------------------------------
        if ($m === 'GET' && $p === '/') {
            return ResponseHelper::success([
                'service' => 'tnm-school-platform',
                'status'  => 'ready',
                'version' => '3.0',
            ]);
        }

        // ------------------------------------------------------------------
        // Authentication routes (public — no auth middleware)
        // ------------------------------------------------------------------
        if ($m === 'POST' && $p === '/auth/login') {
            return $this->authController()->login($this->req($request));
        }

        if ($m === 'POST' && $p === '/auth/register') {
            return $this->authController()->register($this->req($request));
        }

        if ($m === 'POST' && $p === '/auth/logout') {
            return $this->authController()->logout($this->req($request));
        }

        // ------------------------------------------------------------------
        // Resource routes (protected — AuthMiddleware applied via Kernel)
        // ------------------------------------------------------------------
        $resource = $this->resolveResource($p);
        if ($resource !== null) {
            [$basePath, $controller] = $resource;

            if ($m === 'GET' && $p === $basePath) {
                return $controller->index($this->req($request));
            }

            if ($m === 'POST' && $p === $basePath) {
                return $controller->store($this->req($request));
            }

            if (preg_match('#^' . preg_quote($basePath, '#') . '/(\d+)$#', $p, $matches) === 1) {
                $id = (int) $matches[1];

                if ($m === 'GET') {
                    return $controller->show($id);
                }

                if ($m === 'PUT' || $m === 'PATCH') {
                    return $controller->update($id, $this->req($request));
                }

                if ($m === 'DELETE') {
                    return $controller->destroy($id);
                }
            }
        }

        return ResponseHelper::error('Route not found.', 404);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function authController(): AuthController
    {
        $controller = $this->container?->get(AuthController::class)
            ?? $this->container?->get('auth.controller');

        return $controller instanceof AuthController ? $controller : new AuthController();
    }

    /**
     * Resolve the base path and controller instance for a resource path.
     *
     * @return array{0: string, 1: object}|null
     */
    private function resolveResource(string $path): ?array
    {
        $map = [
            '/students'          => [StudentController::class,         'student.controller'],
            '/teachers'          => [TeacherController::class,          'teacher.controller'],
            '/academic-sessions' => [AcademicSessionController::class,  'academicsession.controller'],
            '/classes'           => [AcademicClassController::class,    'academicclass.controller'],
            '/sections'          => [SectionController::class,          'section.controller'],
            '/subjects'          => [SubjectController::class,          'subject.controller'],
            '/attendance'        => [AttendanceController::class,       'attendance.controller'],
            '/attendance-records'=> [AttendanceRecordController::class, 'attendancerecord.controller'],
            '/timetables'        => [TimetableController::class,        'timetable.controller'],
            '/periods'           => [PeriodController::class,           'period.controller'],
            '/holiday-calendars' => [HolidayCalendarController::class,  'holidaycalendar.controller'],
        ];

        foreach ($map as $basePath => [$className, $serviceKey]) {
            if ($path === $basePath || str_starts_with($path, $basePath . '/')) {
                $controller = $this->container?->get($className)
                    ?? $this->container?->get($serviceKey);

                return ($controller instanceof $className)
                    ? [$basePath, $controller]
                    : null;
            }
        }

        return null;
    }

    private function req(?RequestHelper $request): RequestHelper
    {
        return $request ?? RequestHelper::fromGlobals();
    }
}