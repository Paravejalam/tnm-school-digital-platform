<?php

namespace App\User;

use App\Auth\PasswordHasher;
use App\Audit\AuditLoggerInterface;
use App\Support\AppContainer;
use PDO;

class UserServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $passwordHasher = $container->get(PasswordHasher::class);
        $passwordHasher = $passwordHasher instanceof PasswordHasher ? $passwordHasher : null;
        $auditLogger = $container->get(AuditLoggerInterface::class);
        $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;

        $validator = new UserValidator();
        $repository = new UserRepository($database);
        $service = new UserService($repository, $validator, $passwordHasher, $database, $auditLogger);
        $controller = new UserController($service);

        $container->set(UserValidator::class, $validator);
        $container->set(UserRepository::class, $repository);
        $container->set(UserRepositoryInterface::class, $repository);
        $container->set(UserService::class, $service);
        $container->set(UserServiceInterface::class, $service);
        $container->set(UserController::class, $controller);

        $container->set('user.validator', $validator);
        $container->set('user.repository', $repository);
        $container->set('user.service', $service);
        $container->set('user.controller', $controller);
    }
}
