<?php

namespace App\Audit;

use App\Support\AppContainer;
use App\Auth\JwtHelper;
use App\Http\RequestHelper;
use PDO;

class AuditLogServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;
        $jwtHelper = $container->get(JwtHelper::class);
        $jwtHelper = $jwtHelper instanceof JwtHelper ? $jwtHelper : null;
        $request = $container->get('request');
        $request = $request instanceof RequestHelper ? $request : null;

        $repository = new AuditLogRepository($database);
        $auditLogger = new AuditLogger($database, $repository, $jwtHelper, $request);

        $container->set(AuditLogRepository::class, $repository);
        $container->set(AuditLogRepositoryInterface::class, $repository);
        $container->set(AuditLoggerInterface::class, $auditLogger);
        $container->set(AuditLogger::class, $auditLogger);
        $container->set('audit.repository', $repository);
        $container->set('audit.logger', $auditLogger);
    }
}
