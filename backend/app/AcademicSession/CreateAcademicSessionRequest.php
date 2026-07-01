<?php

namespace App\AcademicSession;

class CreateAcademicSessionRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
