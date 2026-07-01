<?php

namespace App\Timetable;

use App\Support\AppContainer;
use PDO;

class TimetableServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new TimetableValidator();
        $repository = new TimetableRepository($database);
        $service = new TimetableService($repository, $validator);
        $controller = new TimetableController($service);

        $container->set(TimetableValidator::class, $validator);
        $container->set(TimetableRepository::class, $repository);
        $container->set(TimetableRepositoryInterface::class, $repository);
        $container->set(TimetableService::class, $service);
        $container->set(TimetableServiceInterface::class, $service);
        $container->set(TimetableController::class, $controller);

        $container->set('timetable.validator', $validator);
        $container->set('timetable.repository', $repository);
        $container->set('timetable.service', $service);
        $container->set('timetable.controller', $controller);
    }
}
