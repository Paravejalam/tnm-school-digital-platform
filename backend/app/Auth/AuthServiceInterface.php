<?php

namespace App\Auth;

interface AuthServiceInterface
{
    public function login(LoginRequest $request): AuthenticatedUser;

    public function register(RegisterRequest $request): AuthenticatedUser;

    public function refresh(string $refreshToken): AuthenticatedUser;

    public function logout(?string $token = null): void;

    public function profile(?string $token = null): ?User;

    public function changePassword(ChangePasswordRequest $request, ?string $token = null): void;
}
