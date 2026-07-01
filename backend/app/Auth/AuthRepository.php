<?php

namespace App\Auth;

class AuthRepository implements AuthRepositoryInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private TokenRepositoryInterface $tokens
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    public function findById(int $id): ?User
    {
        return $this->users->findById($id);
    }

    public function create(array $attributes): User
    {
        return $this->users->create($attributes);
    }

    public function storeToken(User $user, string $token): void
    {
        $this->tokens->storeToken($user, $token);
    }

    public function revokeToken(string $token): void
    {
        $this->tokens->revokeToken($token);
    }
}