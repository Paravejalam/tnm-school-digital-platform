<?php

namespace App\HolidayCalendar;

class HolidayCalendar
{
    public function __construct(
        private ?int $id = null,
        private ?string $holidayName = null,
        private ?int $academicSessionId = null,
        private ?string $status = null,
        private ?string $holidayDate = null,
        private ?string $holidayType = null,
        private ?int $isRecurring = null,
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

    public function holidayName(): ?string
    {
        return $this->holidayName;
    }

    public function academicSessionId(): ?int
    {
        return $this->academicSessionId;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function holidayDate(): ?string
    {
        return $this->holidayDate;
    }

    public function holidayType(): ?string
    {
        return $this->holidayType;
    }

    public function isRecurring(): ?int
    {
        return $this->isRecurring;
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
