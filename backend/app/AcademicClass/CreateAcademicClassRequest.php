<?php

namespace App\AcademicClass;

class CreateAcademicClassRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
