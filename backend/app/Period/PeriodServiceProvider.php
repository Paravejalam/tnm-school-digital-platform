<?php

namespace App\Period;

use App\Support\AppContainer;
use App\Timetable\TimetableRepository;
use App\Audit\AuditLoggerInterface;
use PDO;

class PeriodServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new PeriodValidator();
        $repository = new PeriodRepository($database);
        $timetableRepository = new TimetableRepository($database);
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;
        $service = new PeriodService($repository, $validator, $timetableRepository, $database, $auditLogger);
        $controller = new PeriodController($service);

        $container->set(PeriodValidator::class, $validator);
        $container->set(PeriodRepository::class, $repository);
        $container->set(PeriodRepositoryInterface::class, $repository);
        $container->set(PeriodService::class, $service);
        $container->set(PeriodServiceInterface::class, $service);
        $container->set(PeriodController::class, $controller);

        $container->set('period.validator', $validator);
        $container->set('period.repository', $repository);
        $container->set('period.service', $service);
        $container->set('period.controller', $controller);
    }
}
