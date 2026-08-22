<?php

namespace App\AdmissionEnquiry;

use App\Support\AppContainer;
use App\Audit\AuditLoggerInterface;
use PDO;

class AdmissionEnquiryServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $validator = new AdmissionEnquiryValidator();
        $repository = new AdmissionEnquiryRepository($database);
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;
        $service = new AdmissionEnquiryService($repository, $validator, $database, $auditLogger);
        $controller = new AdmissionEnquiryController($service);

        $container->set(AdmissionEnquiryValidator::class, $validator);
        $container->set(AdmissionEnquiryRepository::class, $repository);
        $container->set(AdmissionEnquiryRepositoryInterface::class, $repository);
        $container->set(AdmissionEnquiryService::class, $service);
        $container->set(AdmissionEnquiryServiceInterface::class, $service);
        $container->set(AdmissionEnquiryController::class, $controller);

        $container->set('admissionenquiry.validator', $validator);
        $container->set('admissionenquiry.repository', $repository);
        $container->set('admissionenquiry.service', $service);
        $container->set('admissionenquiry.controller', $controller);
    }
}
