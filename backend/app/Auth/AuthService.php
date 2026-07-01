<?php

namespace App\Auth;

class AuthService
{
    public function __construct(
        private PasswordHasher $passwordHasher,
        private JwtHelper $jwtHelper,
        private AuthValidator $validator
    ) {
    }
}