<?php

declare(strict_types=1);

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
use App\HolidayCalendar\HolidayCalendarServiceProvider;
use App\Http\Pipeline;
use App\Http\RequestHelper;
use App\Period\PeriodServiceProvider;
use App\Section\SectionServiceProvider;
use App\Student\StudentServiceProvider;
use App\Subject\SubjectServiceProvider;
use App\Support\AppContainer;
use App\Support\ErrorHandler;
use App\Support\Logger;
use App\Teacher\TeacherServiceProvider;
use App\Timetable\TimetableServiceProvider;
use PDO;
use Throwable;

/**
 * Application Kernel
 *
 * Boot sequence:
 * Environment -> Configuration -> Container -> Logger -> Database -> Providers -> Router -> Pipeline
 *
 * Authority: .github/AGENT.md
 */
class Kernel
{
    private AppContainer $container;
    private ?Logger $logger  = null;
    private ?PDO $database   = null;
    private ?RequestHelper $request = null;

    public function __construct(private readonly string $basePath)
    {
        $this->container = new AppContainer();
    }

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    public function bootstrap(): self
    {
        // 1. Environment
        EnvironmentLoader::load($this->basePath . '/.env');

        // 2. Configuration
        ConfigLoader::loadDirectory($this->basePath . '/config');

        $appConfig   = ConfigLoader::get('app', []);
        $appSettings = $appConfig['app'] ?? [];
        $dbConfig    = ConfigLoader::get('database', []);
        $isDev       = (bool) ($appSettings['debug'] ?? false);

        // 3. Container — register core services
        $this->container->set('config', ConfigLoader::all());

        // 4. Logger
        $logPath    = $this->basePath . '/logs/app.log';
        $this->logger = new Logger($logPath, $isDev ? 'DEBUG' : 'INFO');
        $this->container->set('logger', $this->logger);

        // 5. Request
        $this->request = RequestHelper::fromGlobals();
        $this->container->set('request', $this->request);

        // 6. Error handler
        $errorHandler = new ErrorHandler($isDev);
        $this->container->set('errorHandler', $errorHandler);

        // 7. Pipeline
        $this->container->set('pipeline', new Pipeline());

        // 8. Database — graceful skip if config is empty or DB not reachable at boot
        if (!empty($dbConfig)) {
            try {
                $this->database = ConnectionManager::getInstance()->connect([
                    'host'     => $dbConfig['host']     ?? '127.0.0.1',
                    'port'     => $dbConfig['port']     ?? 3306,
                    'database' => $dbConfig['database'] ?? $dbConfig['name'] ?? 'tnm_school_platform',
                    'username' => $dbConfig['username'] ?? $dbConfig['user'] ?? 'root',
                    'password' => $dbConfig['password'] ?? '',
                ]);
                $this->container->set('database', $this->database);
            } catch (Throwable $e) {
                $this->logger->warning('Database connection failed at boot', [
                    'message' => $e->getMessage(),
                ]);
                // Continue boot — health check and public routes still available
            }
        }

        // 9. Domain module service providers
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

        // 10. Router
        $this->container->set('router', new Router($this->container));

        // 11. Global exception handler — catches anything not caught by handle()
        set_exception_handler(function (Throwable $exception): void {
            /** @var ErrorHandler|null $handler */
            $handler = $this->container->get('errorHandler');

            if ($handler instanceof ErrorHandler) {
                $response = $handler->handle($exception);
            } else {
                http_response_code(500);
                $response = ['success' => false, 'status' => 500, 'error' => ['message' => 'Internal server error.']];
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        });

        return $this;
    }

    // -------------------------------------------------------------------------
    // Handle
    // -------------------------------------------------------------------------

    public function handle(): array
    {
        $router = $this->container->get('router');
        if (!$router instanceof Router) {
            return ['success' => false, 'status' => 503, 'error' => ['message' => 'Router unavailable.']];
        }

        $request = $this->container->get('request');
        if (!$request instanceof RequestHelper) {
            return ['success' => false, 'status' => 503, 'error' => ['message' => 'Request unavailable.']];
        }

        $pipeline = $this->container->get('pipeline');
        if (!$pipeline instanceof Pipeline) {
            return ['success' => false, 'status' => 503, 'error' => ['message' => 'Pipeline unavailable.']];
        }

        // Request logging hook (runtime — no external provider)
        $this->logger?->info('Request received', [
            'method' => $request->method(),
            'path'   => $request->path(),
        ]);

        $response = $pipeline->handle(
            fn (): array => $router->dispatch($request->method(), $request->path(), $request),
            $this->middlewareFor($request),
            $request
        );

        // Response logging hook
        $this->logger?->debug('Response dispatched', [
            'status' => $response['status'] ?? 'unknown',
        ]);

        return $response;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getContainer(): AppContainer
    {
        return $this->container;
    }

    // -------------------------------------------------------------------------
    // Middleware resolution
    // -------------------------------------------------------------------------

    /**
     * Return the middleware stack for a given request.
     *
     * Protected route prefixes require AuthMiddleware.
     * Future: rate limiting middleware hook here.
     * Future: RBAC middleware hook here (after Auth).
     */
    private function middlewareFor(RequestHelper $request): array
    {
        $path   = rtrim($request->path(), '/') ?: '/';
        $method = $request->method();

        $protectedPrefixes = [
            '/students',
            '/teachers',
            '/academic-sessions',
            '/classes',
            '/sections',
            '/subjects',
            '/attendance',
            '/attendance-records',
            '/timetables',
            '/periods',
            '/holiday-calendars',
        ];

        $requiresAuth = $method === 'POST' && $path === '/auth/logout';

        if (!$requiresAuth) {
            foreach ($protectedPrefixes as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                    $requiresAuth = true;
                    break;
                }
            }
        }

        if (!$requiresAuth) {
            return [];
        }

        $middleware = $this->container->get(AuthMiddleware::class);

        // Future hook: rate limiting middleware
        // $rateLimiter = $this->container->get(RateLimitMiddleware::class);

        // Future hook: RBAC middleware
        // $rbac = $this->container->get(RbacMiddleware::class);

        return $middleware instanceof AuthMiddleware ? [$middleware] : [];
    }
}