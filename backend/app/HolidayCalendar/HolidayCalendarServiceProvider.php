<?php

namespace App\HolidayCalendar;

use App\Support\AppContainer;
use App\AcademicSession\AcademicSessionRepository;
use App\Audit\AuditLoggerInterface;
use PDO;

class HolidayCalendarServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new HolidayCalendarValidator();
        $repository = new HolidayCalendarRepository($database);
        $sessionRepository = new AcademicSessionRepository($database);
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;
        $service = new HolidayCalendarService($repository, $validator, $sessionRepository, $database, $auditLogger);
        $controller = new HolidayCalendarController($service);

        $container->set(HolidayCalendarValidator::class, $validator);
        $container->set(HolidayCalendarRepository::class, $repository);
        $container->set(HolidayCalendarRepositoryInterface::class, $repository);
        $container->set(HolidayCalendarService::class, $service);
        $container->set(HolidayCalendarServiceInterface::class, $service);
        $container->set(HolidayCalendarController::class, $controller);

        $container->set('holidaycalendar.validator', $validator);
        $container->set('holidaycalendar.repository', $repository);
        $container->set('holidaycalendar.service', $service);
        $container->set('holidaycalendar.controller', $controller);
    }
}
