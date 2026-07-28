<?php

namespace App\Audit;

class AuditLog
{
    public function __construct(
        private ?int $id = null,
        private ?int $userId = null,
        private ?string $action = null,
        private ?string $entityType = null,
        private ?int $entityId = null,
        private ?string $oldValues = null,
        private ?string $newValues = null,
        private ?string $ipAddress = null,
        private ?string $userAgent = null,
        private ?string $createdAt = null,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function action(): ?string
    {
        return $this->action;
    }

    public function entityType(): ?string
    {
        return $this->entityType;
    }

    public function entityId(): ?int
    {
        return $this->entityId;
    }

    public function oldValues(): ?string
    {
        return $this->oldValues;
    }

    public function newValues(): ?string
    {
        return $this->newValues;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }
}
