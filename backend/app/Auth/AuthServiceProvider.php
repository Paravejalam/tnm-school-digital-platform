<?php

namespace App\Auth;

use App\Support\AppContainer;

class AuthServiceProvider
{
    public function register(AppContainer $container): void
    {
        $passwordHasher = new PasswordHasher();
        $jwtHelper = new JwtHelper();
        $authValidator = new AuthValidator();
        $authService = new AuthService($passwordHasher, $jwtHelper, $authValidator);

        $container->set(PasswordHasher::class, $passwordHasher);
        $container->set(JwtHelper::class, $jwtHelper);
        $container->set(AuthValidator::class, $authValidator);
        $container->set(AuthService::class, $authService);
        $container->set(AuthServiceInterface::class, $authService);
        $container->set(AuthController::class, new AuthController($authService));
        $container->set(AuthMiddleware::class, new AuthMiddleware());

        $container->set('auth.passwordHasher', $passwordHasher);
        $container->set('auth.jwtHelper', $jwtHelper);
        $container->set('auth.validator', $authValidator);
        $container->set('auth.service', $authService);
        $container->set('auth.controller', $container->get(AuthController::class));
        $container->set('auth.middleware', $container->get(AuthMiddleware::class));
    }
}