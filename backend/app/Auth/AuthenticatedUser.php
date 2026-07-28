<?php

namespace App\Auth;

class AuthenticatedUser
{
    public function __construct(
        private ?User $user = null,
        private ?string $token = null,
        private ?string $refreshToken = null
    ) {
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function token(): ?string
    {
        return $this->token;
    }

    public function refreshToken(): ?string
    {
        return $this->refreshToken;
    }
}
