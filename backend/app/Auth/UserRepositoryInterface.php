<?php

namespace App\Auth;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    public function create(array $attributes): User;

    public function assignRole(int $userId, int $roleId): void;

    public function findUserRole(int $userId): ?string;
}