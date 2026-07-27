<?php

namespace App\Auth;

interface AuthRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    public function create(array $attributes): User;

    public function storeToken(User $user, string $token): void;

    public function revokeToken(string $token): void;

    public function assignRole(int $userId, int $roleId): void;

    public function findUserRole(int $userId): ?string;
}