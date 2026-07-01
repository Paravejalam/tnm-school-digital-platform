<?php

namespace App\Auth;

interface TokenRepositoryInterface
{
    public function storeToken(User $user, string $token): void;

    public function revokeToken(string $token): void;
}