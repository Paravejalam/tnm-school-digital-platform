<?php

namespace App\AcademicSession;

use App\Support\AppContainer;
use App\Audit\AuditLoggerInterface;
use PDO;

class AcademicSessionServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new AcademicSessionValidator();
        $repository = new AcademicSessionRepository($database);
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;
        $service = new AcademicSessionService($repository, $validator, $database, $auditLogger);
        $controller = new AcademicSessionController($service);

        $container->set(AcademicSessionValidator::class, $validator);
        $container->set(AcademicSessionRepository::class, $repository);
        $container->set(AcademicSessionRepositoryInterface::class, $repository);
        $container->set(AcademicSessionService::class, $service);
        $container->set(AcademicSessionServiceInterface::class, $service);
        $container->set(AcademicSessionController::class, $controller);

        $container->set('academicsession.validator', $validator);
        $container->set('academicsession.repository', $repository);
        $container->set('academicsession.service', $service);
        $container->set('academicsession.controller', $controller);
    }
}
