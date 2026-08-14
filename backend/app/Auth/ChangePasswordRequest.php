<?php

namespace App\Auth;

class ChangePasswordRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function currentPassword(): ?string
    {
        $current = $this->payload['current_password'] ?? null;

        return is_string($current) ? $current : null;
    }

    public function newPassword(): ?string
    {
        $new = $this->payload['new_password'] ?? null;

        return is_string($new) ? $new : null;
    }

    public function confirmPassword(): ?string
    {
        $confirm = $this->payload['confirm_password'] ?? null;

        return is_string($confirm) ? $confirm : null;
    }
}
