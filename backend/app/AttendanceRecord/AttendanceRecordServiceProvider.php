<?php

namespace App\AttendanceRecord;

use App\Support\AppContainer;
use PDO;

class AttendanceRecordServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new AttendanceRecordValidator();
        $repository = new AttendanceRecordRepository($database);
        $service = new AttendanceRecordService($repository, $validator);
        $controller = new AttendanceRecordController($service);

        $container->set(AttendanceRecordValidator::class, $validator);
        $container->set(AttendanceRecordRepository::class, $repository);
        $container->set(AttendanceRecordRepositoryInterface::class, $repository);
        $container->set(AttendanceRecordService::class, $service);
        $container->set(AttendanceRecordServiceInterface::class, $service);
        $container->set(AttendanceRecordController::class, $controller);

        $container->set('attendancerecord.validator', $validator);
        $container->set('attendancerecord.repository', $repository);
        $container->set('attendancerecord.service', $service);
        $container->set('attendancerecord.controller', $controller);
    }
}
