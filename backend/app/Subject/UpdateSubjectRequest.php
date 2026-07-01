<?php

namespace App\Subject;

class UpdateSubjectRequest
{
    public function __construct(private int $id, private array $payload = [])
    {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
