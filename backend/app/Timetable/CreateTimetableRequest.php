<?php

namespace App\Timetable;

class CreateTimetableRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
