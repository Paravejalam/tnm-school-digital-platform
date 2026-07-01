<?php

namespace App\Auth;

class RegisterRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function credentials(): Credentials
    {
        return new Credentials(
            $this->payload['email'] ?? null,
            $this->payload['password'] ?? null
        );
    }
}
