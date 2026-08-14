<?php

namespace App\Auth;

use App\Support\AppContainer;
use App\Audit\AuditLoggerInterface;
use App\Student\StudentRepository;
use App\Teacher\TeacherRepository;
use PDO;

class AuthServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $passwordHasher = new PasswordHasher();
        $jwtHelper = new JwtHelper();
        $authValidator = new AuthValidator();
        $userRepository = new UserRepository($database);
        $tokenRepository = new TokenRepository($database);
        $refreshTokenRepository = new RefreshTokenRepository($database);
        $authRepository = new AuthRepository($userRepository, $tokenRepository);
        $studentRepository = new StudentRepository($database);
        $teacherRepository = new TeacherRepository($database);

        $container->bind(AuthService::class, function (AppContainer $container) use ($passwordHasher, $jwtHelper, $authValidator, $authRepository, $database, $refreshTokenRepository, $studentRepository, $teacherRepository) {
            $auditLogger = $container->get(AuditLoggerInterface::class);
            $auditLogger = $auditLogger instanceof AuditLoggerInterface ? $auditLogger : null;

            return new AuthService($passwordHasher, $jwtHelper, $authValidator, $authRepository, $database, $refreshTokenRepository, $studentRepository, $teacherRepository, $auditLogger);
        });
        $container->bind(AuthServiceInterface::class, fn (AppContainer $container) => $container->get(AuthService::class));
        $container->bind(AuthController::class, fn (AppContainer $container) => new AuthController($container->get(AuthService::class)));
        $container->bind('auth.service', fn (AppContainer $container) => $container->get(AuthService::class));
        $container->bind('auth.controller', fn (AppContainer $container) => $container->get(AuthController::class));

        $container->set(PasswordHasher::class, $passwordHasher);
        $container->set(JwtHelper::class, $jwtHelper);
        $container->set(AuthValidator::class, $authValidator);
        $container->set(UserRepository::class, $userRepository);
        $container->set(UserRepositoryInterface::class, $userRepository);
        $container->set(TokenRepository::class, $tokenRepository);
        $container->set(TokenRepositoryInterface::class, $tokenRepository);
        $container->set(RefreshTokenRepository::class, $refreshTokenRepository);
        $container->set(RefreshTokenRepositoryInterface::class, $refreshTokenRepository);
        $container->set(AuthRepository::class, $authRepository);
        $container->set(AuthRepositoryInterface::class, $authRepository);
        $container->set(AuthMiddleware::class, new AuthMiddleware($jwtHelper));
        $container->set(RbacMiddleware::class, new RbacMiddleware($jwtHelper, $database));

        $container->set('auth.passwordHasher', $passwordHasher);
        $container->set('auth.jwtHelper', $jwtHelper);
        $container->set('auth.validator', $authValidator);
        $container->set('auth.userRepository', $userRepository);
        $container->set('auth.tokenRepository', $tokenRepository);
        $container->set('auth.refreshTokenRepository', $refreshTokenRepository);
        $container->set('auth.repository', $authRepository);
        $container->set('auth.middleware', $container->get(AuthMiddleware::class));
        $container->set('auth.rbacMiddleware', $container->get(RbacMiddleware::class));
    }
}
