<?php

namespace App\User;

class CreateUserRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function roleIds(): array
    {
        $roleIds = $this->payload['roles'] ?? [];
        if (!is_array($roleIds)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $roleIds)));
    }
}
