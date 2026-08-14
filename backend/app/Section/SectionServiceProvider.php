<?php

namespace App\Section;

use App\Support\AppContainer;
use App\AcademicClass\AcademicClassRepository;
use App\Audit\AuditLoggerInterface;
use PDO;

class SectionServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new SectionValidator();
        $repository = new SectionRepository($database);
        $classRepository = new AcademicClassRepository($database);
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;
        $service = new SectionService($repository, $validator, $classRepository, $database, $auditLogger);
        $controller = new SectionController($service);

        $container->set(SectionValidator::class, $validator);
        $container->set(SectionRepository::class, $repository);
        $container->set(SectionRepositoryInterface::class, $repository);
        $container->set(SectionService::class, $service);
        $container->set(SectionServiceInterface::class, $service);
        $container->set(SectionController::class, $controller);

        $container->set('section.validator', $validator);
        $container->set('section.repository', $repository);
        $container->set('section.service', $service);
        $container->set('section.controller', $controller);
    }
}
