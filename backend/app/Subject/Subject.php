<?php

namespace App\Subject;

class Subject
{
    public function __construct(
        private ?int $id = null,
        private ?string $subjectName = null,
        private ?string $code = null,
        private ?int $sectionId = null,
        private ?string $status = null,
        private ?string $description = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function subjectName(): ?string
    {
        return $this->subjectName;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function sectionId(): ?int
    {
        return $this->sectionId;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function description(): ?string
    {
        return $this->description;
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
