<?php

namespace App\Attendance;

use App\Support\AppContainer;
use App\AcademicSession\AcademicSessionRepository;
use App\AcademicClass\AcademicClassRepository;
use App\Section\SectionRepository;
use App\Student\StudentRepository;
use PDO;

class AttendanceServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new AttendanceValidator();
        $repository = new AttendanceRepository($database);
        $sessionRepository = new AcademicSessionRepository($database);
        $classRepository = new AcademicClassRepository($database);
        $sectionRepository = new SectionRepository($database);
        $studentRepository = new StudentRepository($database);
        $service = new AttendanceService($repository, $validator, $sessionRepository, $classRepository, $sectionRepository, $studentRepository);
        $controller = new AttendanceController($service);

        $container->set(AttendanceValidator::class, $validator);
        $container->set(AttendanceRepository::class, $repository);
        $container->set(AttendanceRepositoryInterface::class, $repository);
        $container->set(AttendanceService::class, $service);
        $container->set(AttendanceServiceInterface::class, $service);
        $container->set(AttendanceController::class, $controller);

        $container->set('attendance.validator', $validator);
        $container->set('attendance.repository', $repository);
        $container->set('attendance.service', $service);
        $container->set('attendance.controller', $controller);
    }
}
