<?php

namespace App\Auth;

interface RefreshTokenRepositoryInterface
{
    public function store(User $user, string $token, int $ttlSeconds): void;

    public function find(string $token): ?array;

    public function revoke(string $token): void;

    public function revokeAllForUser(int $userId): void;
}
