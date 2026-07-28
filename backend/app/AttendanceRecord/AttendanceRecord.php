<?php

namespace App\AttendanceRecord;

class AttendanceRecord
{
    public function __construct(
        private ?int $id = null,
        private ?string $recordName = null,
        private ?int $attendanceId = null,
        private ?int $studentId = null,
        private ?string $status = null,
        private ?string $note = null,
        private ?int $recordedBy = null,
        private ?string $recordedAt = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
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

    public function note(): ?string
    {
        return $this->note;
    }

    public function recordedBy(): ?int
    {
        return $this->recordedBy;
    }

    public function recordedAt(): ?string
    {
        return $this->recordedAt;
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
