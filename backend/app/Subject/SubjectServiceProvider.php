<?php

namespace App\Subject;

use App\Support\AppContainer;
use App\Audit\AuditLoggerInterface;
use PDO;

class SubjectServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new SubjectValidator();
        $repository = new SubjectRepository($database);
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;
        $service = new SubjectService($repository, $validator, $database, $auditLogger);
        $controller = new SubjectController($service);

        $container->set(SubjectValidator::class, $validator);
        $container->set(SubjectRepository::class, $repository);
        $container->set(SubjectRepositoryInterface::class, $repository);
        $container->set(SubjectService::class, $service);
        $container->set(SubjectServiceInterface::class, $service);
        $container->set(SubjectController::class, $controller);

        $container->set('subject.validator', $validator);
        $container->set('subject.repository', $repository);
        $container->set('subject.service', $service);
        $container->set('subject.controller', $controller);
    }
}
