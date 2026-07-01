<?php

namespace App\Period;

use App\Support\AppContainer;
use PDO;

class PeriodServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new PeriodValidator();
        $repository = new PeriodRepository($database);
        $service = new PeriodService($repository, $validator);
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
