<?php

namespace App\Timetable;

use App\Support\AppContainer;
use App\AcademicSession\AcademicSessionRepository;
use App\AcademicClass\AcademicClassRepository;
use App\Section\SectionRepository;
use App\Subject\SubjectRepository;
use App\Teacher\TeacherRepository;
use App\Audit\AuditLoggerInterface;
use PDO;

class TimetableServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new TimetableValidator();
        $repository = new TimetableRepository($database);
        $sessionRepository = new AcademicSessionRepository($database);
        $classRepository = new AcademicClassRepository($database);
        $sectionRepository = new SectionRepository($database);
        $subjectRepository = new SubjectRepository($database);
        $teacherRepository = new TeacherRepository($database);
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;
        $service = new TimetableService($repository, $validator, $sessionRepository, $classRepository, $sectionRepository, $subjectRepository, $teacherRepository, $database, $auditLogger);
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
