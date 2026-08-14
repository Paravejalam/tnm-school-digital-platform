<?php

namespace App\AcademicSession;

class AcademicSession
{
    public function __construct(
        private ?int $id = null,
        private ?string $sessionName = null,
        private ?string $status = null,
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?int $isCurrent = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function sessionName(): ?string
    {
        return $this->sessionName;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function startDate(): ?string
    {
        return $this->startDate;
    }

    public function endDate(): ?string
    {
        return $this->endDate;
    }

    public function isCurrent(): ?int
    {
        return $this->isCurrent;
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
