<?php

namespace App\Student;

use App\Support\AppContainer;
use PDO;

class StudentServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new StudentValidator();
        $repository = new StudentRepository($database);
        $service = new StudentService($repository, $validator);
        $controller = new StudentController($service);

        $container->set(StudentValidator::class, $validator);
        $container->set(StudentRepository::class, $repository);
        $container->set(StudentRepositoryInterface::class, $repository);
        $container->set(StudentService::class, $service);
        $container->set(StudentServiceInterface::class, $service);
        $container->set(StudentController::class, $controller);

        $container->set('student.validator', $validator);
        $container->set('student.repository', $repository);
        $container->set('student.service', $service);
        $container->set('student.controller', $controller);
    }
}