<?php

namespace App\AttendanceRecord;

class AttendanceRecord
{
    public function __construct(
        private ?int $id = null,
        private ?string $recordName = null,
        private ?int $attendanceId = null,
        private ?int $studentId = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function recordName(): ?string
    {
        return $this->recordName;
    }

    public function attendanceId(): ?int
    {
        return $this->attendanceId;
    }

    public function studentId(): ?int
    {
        return $this->studentId;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
