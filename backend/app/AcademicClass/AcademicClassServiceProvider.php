<?php

namespace App\AcademicClass;

use App\Support\AppContainer;
use PDO;

class AcademicClassServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new AcademicClassValidator();
        $repository = new AcademicClassRepository($database);
        $service = new AcademicClassService($repository, $validator);
        $controller = new AcademicClassController($service);

        $container->set(AcademicClassValidator::class, $validator);
        $container->set(AcademicClassRepository::class, $repository);
        $container->set(AcademicClassRepositoryInterface::class, $repository);
        $container->set(AcademicClassService::class, $service);
        $container->set(AcademicClassServiceInterface::class, $service);
        $container->set(AcademicClassController::class, $controller);

        $container->set('academicclass.validator', $validator);
        $container->set('academicclass.repository', $repository);
        $container->set('academicclass.service', $service);
        $container->set('academicclass.controller', $controller);
    }
}
