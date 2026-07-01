<?php

namespace App\Support;

use App\Config\ConfigLoader;
use App\Config\EnvironmentLoader;
use App\Database\ConnectionManager;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use PDO;
use Throwable;

class Bootstrap
{
    private static ?self $instance = null;
    private ?Logger $logger = null;
    private ?PDO $database = null;
    private ?ErrorHandler $errorHandler = null;
    private ?RequestHelper $request = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function initialize(string $basePath): array
    {
        EnvironmentLoader::load($basePath . '/.env');
        ConfigLoader::loadDirectory($basePath . '/config');

        $appConfig = ConfigLoader::get('app', []);
        $appSettings = $appConfig['app'] ?? [];
        $dbConfig = ConfigLoader::get('database', []);

        $this->logger = new Logger($basePath . '/logs/app.log', 'DEBUG');
        $this->request = RequestHelper::fromGlobals();
        $this->errorHandler = new ErrorHandler((bool) ($appSettings['debug'] ?? false));

        if (!empty($dbConfig)) {
            $this->database = ConnectionManager::getInstance()->connect([
                'host' => $dbConfig['host'] ?? '127.0.0.1',
                'port' => $dbConfig['port'] ?? 3306,
                'database' => $dbConfig['database'] ?? $dbConfig['name'] ?? 'tnm_school_platform',
                'username' => $dbConfig['username'] ?? $dbConfig['user'] ?? 'root',
                'password' => $dbConfig['password'] ?? '',
            ]);
        }

        set_exception_handler(function (Throwable $exception) {
            $handler = $this->errorHandler;
            $response = $handler ? $handler->handle($exception) : ResponseHelper::error($exception->getMessage(), 500);
            http_response_code(500);
            echo json_encode($response, JSON_UNESCAPED_SLASHES);
        });

        return [
            'config' => ConfigLoader::all(),
            'database' => $this->database,
            'logger' => $this->logger,
            'request' => $this->request,
        ];
    }
}
