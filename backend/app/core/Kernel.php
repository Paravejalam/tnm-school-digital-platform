<?php

namespace App\Core;

use App\AcademicClass\AcademicClassServiceProvider;
use App\AcademicSession\AcademicSessionServiceProvider;
use App\Attendance\AttendanceServiceProvider;
use App\AttendanceRecord\AttendanceRecordServiceProvider;
use App\Auth\AuthMiddleware;
use App\Auth\AuthServiceProvider;
use App\Config\ConfigLoader;
use App\Config\EnvironmentLoader;
use App\Database\ConnectionManager;
use App\Http\Pipeline;
use App\HolidayCalendar\HolidayCalendarServiceProvider;
use App\Http\RequestHelper;
use App\Period\PeriodServiceProvider;
use App\Section\SectionServiceProvider;
use App\Student\StudentServiceProvider;
use App\Subject\SubjectServiceProvider;
use App\Teacher\TeacherServiceProvider;
use App\Timetable\TimetableServiceProvider;
use App\Support\AppContainer;
use App\Support\ErrorHandler;
use App\Support\Logger;
use PDO;
use Throwable;

class Kernel
{
    private string $basePath;
    private AppContainer $container;
    private ?Logger $logger = null;
    private ?PDO $database = null;
    private ?RequestHelper $request = null;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->container = new AppContainer();
    }

    public function bootstrap(): self
    {
        EnvironmentLoader::load($this->basePath . '/.env');
        ConfigLoader::loadDirectory($this->basePath . '/config');

        $appConfig = ConfigLoader::get('app', []);
        $appSettings = $appConfig['app'] ?? [];
        $dbConfig = ConfigLoader::get('database', []);

        $this->container->set('config', ConfigLoader::all());
        $this->logger = new Logger($this->basePath . '/logs/app.log', 'DEBUG');
        $this->container->set('logger', $this->logger);
        $this->request = RequestHelper::fromGlobals();
        $this->container->set('request', $this->request);

        if (!empty($dbConfig)) {
            $this->database = ConnectionManager::getInstance()->connect([
                'host' => $dbConfig['host'] ?? '127.0.0.1',
                'port' => $dbConfig['port'] ?? 3306,
                'database' => $dbConfig['database'] ?? $dbConfig['name'] ?? 'tnm_school_platform',
                'username' => $dbConfig['username'] ?? $dbConfig['user'] ?? 'root',
                'password' => $dbConfig['password'] ?? '',
            ]);
            $this->container->set('database', $this->database);
        }

        $this->container->set('errorHandler', new ErrorHandler((bool) ($appSettings['debug'] ?? false)));
        $this->container->set('pipeline', new Pipeline());

        (new AuthServiceProvider())->register($this->container);
        (new StudentServiceProvider())->register($this->container);
        (new TeacherServiceProvider())->register($this->container);
        (new AcademicSessionServiceProvider())->register($this->container);
        (new AcademicClassServiceProvider())->register($this->container);
        (new SectionServiceProvider())->register($this->container);
        (new SubjectServiceProvider())->register($this->container);
        (new AttendanceServiceProvider())->register($this->container);
        (new AttendanceRecordServiceProvider())->register($this->container);
        (new TimetableServiceProvider())->register($this->container);
        (new PeriodServiceProvider())->register($this->container);
        (new HolidayCalendarServiceProvider())->register($this->container);

        $this->container->set('router', new Router($this->container));

        set_exception_handler(function (Throwable $exception): void {
            $handler = $this->container->get('errorHandler');
            if ($handler instanceof ErrorHandler) {
                $response = $handler->handle($exception);
                http_response_code(500);
                echo json_encode($response, JSON_UNESCAPED_SLASHES);
                return;
            }

            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_SLASHES);
        });

        return $this;
    }

    public function getContainer(): AppContainer
    {
        return $this->container;
    }

    public function handle(): array
    {
        $router = $this->container->get('router');
        if (!$router instanceof Router) {
            return ['success' => false, 'message' => 'Router unavailable'];
        }

        $request = $this->container->get('request');
        if (!$request instanceof RequestHelper) {
            return ['success' => false, 'message' => 'Request unavailable'];
        }

        $pipeline = $this->container->get('pipeline');
        if (!$pipeline instanceof Pipeline) {
            return ['success' => false, 'message' => 'Pipeline unavailable'];
        }

        return $pipeline->handle(
            fn (): array => $router->dispatch($request->method(), $request->path(), $request),
            $this->middlewareFor($request),
            $request
        );
    }

    private function middlewareFor(RequestHelper $request): array
    {
        $path = rtrim($request->path(), '/') ?: '/';
        $protectedPrefixes = ['/students', '/teachers', '/academic-sessions', '/classes', '/sections', '/subjects', '/attendance', '/attendance-records', '/timetables', '/periods', '/holiday-calendars'];
        $requiresAuth = $request->method() === 'POST' && $path === '/auth/logout';

        foreach ($protectedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                $requiresAuth = true;
                break;
            }
        }

        if ($requiresAuth) {
            $middleware = $this->container->get(AuthMiddleware::class);

            return $middleware instanceof AuthMiddleware ? [$middleware] : [];
        }

        return [];
    }
}