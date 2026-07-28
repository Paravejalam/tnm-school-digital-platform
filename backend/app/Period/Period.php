<?php

namespace App\Period;

class Period
{
    public function __construct(
        private ?int $id = null,
        private ?string $periodName = null,
        private ?int $timetableId = null,
        private ?string $status = null,
        private ?string $dayOfWeek = null,
        private ?string $startTime = null,
        private ?string $endTime = null,
        private ?int $periodOrder = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private ?string $deletedAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function periodName(): ?string
    {
        return $this->periodName;
    }

    public function timetableId(): ?int
    {
        return $this->timetableId;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function dayOfWeek(): ?string
    {
        return $this->dayOfWeek;
    }

    public function startTime(): ?string
    {
        return $this->startTime;
    }

    public function endTime(): ?string
    {
        return $this->endTime;
    }

    public function periodOrder(): ?int
    {
        return $this->periodOrder;
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
