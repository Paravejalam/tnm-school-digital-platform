<?php

namespace App\HolidayCalendar;

class HolidayCalendar
{
    public function __construct(
        private ?int $id = null,
        private ?string $holidayName = null,
        private ?int $academicSessionId = null,
        private ?string $status = null
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
}
