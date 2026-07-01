<?php

namespace App\Period;

class CreatePeriodRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
