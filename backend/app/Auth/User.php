<?php

namespace App\Auth;

class User
{
    public function __construct(
        private ?int $id = null,
        private ?string $name = null,
        private ?string $email = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function email(): ?string
    {
        return $this->email;
    }
}