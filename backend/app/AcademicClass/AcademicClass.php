<?php

namespace App\AcademicClass;

class AcademicClass
{
    public function __construct(
        private ?int $id = null,
        private ?string $className = null,
        private ?string $code = null,
        private ?int $academicSessionId = null,
        private ?string $status = null,
        private ?int $gradeLevel = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function className(): ?string
    {
        return $this->className;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function academicSessionId(): ?int
    {
        return $this->academicSessionId;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function gradeLevel(): ?int
    {
        return $this->gradeLevel;
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
