<?php

namespace App\Teacher;

class CreateTeacherRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}