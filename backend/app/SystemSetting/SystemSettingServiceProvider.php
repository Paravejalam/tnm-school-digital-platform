<?php

namespace App\SystemSetting;

use App\Audit\AuditLoggerInterface;
use App\Support\AppContainer;
use PDO;

class SystemSettingServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new SystemSettingValidator();
        $repository = new SystemSettingRepository($database);
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;
        $service = new SystemSettingService($repository, $validator, $database, $auditLogger);
        $controller = new SystemSettingController($service);

        $container->set(SystemSettingValidator::class, $validator);
        $container->set(SystemSettingRepository::class, $repository);
        $container->set(SystemSettingRepositoryInterface::class, $repository);
        $container->set(SystemSettingService::class, $service);
        $container->set(SystemSettingServiceInterface::class, $service);
        $container->set(SystemSettingController::class, $controller);

        $container->set('systemsetting.validator', $validator);
        $container->set('systemsetting.repository', $repository);
        $container->set('systemsetting.service', $service);
        $container->set('systemsetting.controller', $controller);
    }
}
