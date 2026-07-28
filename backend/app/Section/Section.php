<?php

namespace App\Section;

class Section
{
    public function __construct(
        private ?int $id = null,
        private ?string $sectionName = null,
        private ?string $code = null,
        private ?int $classId = null,
        private ?string $status = null,
        private ?int $capacity = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function sectionName(): ?string
    {
        return $this->sectionName;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function classId(): ?int
    {
        return $this->classId;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function capacity(): ?int
    {
        return $this->capacity;
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
