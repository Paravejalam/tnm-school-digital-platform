<?php

namespace App\Auth;

class Credentials
{
    public function __construct(
        private ?string $email = null,
        private ?string $password = null
    ) {
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function password(): ?string
    {
        return $this->password;
    }
}
