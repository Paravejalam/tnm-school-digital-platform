<?php

namespace App\AttendanceRecord;

class CreateAttendanceRecordRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
