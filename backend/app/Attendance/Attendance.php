<?php

namespace App\Attendance;

class Attendance
{
    public function __construct(
        private ?int $id = null,
        private ?string $attendanceDate = null,
        private ?int $academicSessionId = null,
        private ?int $classId = null,
        private ?int $sectionId = null,
        private ?int $studentId = null,
        private ?string $status = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function attendanceDate(): ?string
    {
        return $this->attendanceDate;
    }

    public function academicSessionId(): ?int
    {
        return $this->academicSessionId;
    }

    public function classId(): ?int
    {
        return $this->classId;
    }

    public function sectionId(): ?int
    {
        return $this->sectionId;
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
