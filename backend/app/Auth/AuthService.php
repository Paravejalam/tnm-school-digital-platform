<?php

namespace App\Auth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private PasswordHasher $passwordHasher,
        private JwtHelper $jwtHelper,
        private AuthValidator $validator
    ) {
    }
}