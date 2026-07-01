<?php

namespace App\Attendance;

class CreateAttendanceRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
