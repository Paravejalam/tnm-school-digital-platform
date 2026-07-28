<?php

namespace App\Timetable;

class Timetable
{
    public function __construct(
        private ?int $id = null,
        private ?string $timetableName = null,
        private ?int $academicSessionId = null,
        private ?int $classId = null,
        private ?int $sectionId = null,
        private ?int $subjectId = null,
        private ?int $teacherId = null,
        private ?string $status = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function timetableName(): ?string
    {
        return $this->timetableName;
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

    public function subjectId(): ?int
    {
        return $this->subjectId;
    }

    public function teacherId(): ?int
    {
        return $this->teacherId;
    }

    public function status(): ?string
    {
        return $this->status;
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
