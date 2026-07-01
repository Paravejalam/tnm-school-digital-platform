<?php

namespace App\Teacher;

use App\Support\AppContainer;
use PDO;

class TeacherServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new TeacherValidator();
        $repository = new TeacherRepository($database);
        $service = new TeacherService($repository, $validator);
        $controller = new TeacherController($service);

        $container->set(TeacherValidator::class, $validator);
        $container->set(TeacherRepository::class, $repository);
        $container->set(TeacherRepositoryInterface::class, $repository);
        $container->set(TeacherService::class, $service);
        $container->set(TeacherServiceInterface::class, $service);
        $container->set(TeacherController::class, $controller);

        $container->set('teacher.validator', $validator);
        $container->set('teacher.repository', $repository);
        $container->set('teacher.service', $service);
        $container->set('teacher.controller', $controller);
    }
}