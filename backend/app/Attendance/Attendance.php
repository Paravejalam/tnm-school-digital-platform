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
        private ?string $status = null,
        private ?string $remarks = null,
        private ?int $markedBy = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
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

    public function remarks(): ?string
    {
        return $this->remarks;
    }

    public function markedBy(): ?int
    {
        return $this->markedBy;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?string
    {
        return $this->deletedAt;
    }
}
