<?php

namespace App\Auth;

interface AuthServiceInterface
{
    public function login(LoginRequest $request): AuthenticatedUser;

    public function register(RegisterRequest $request): AuthenticatedUser;

    public function logout(?string $token = null): void;
}
