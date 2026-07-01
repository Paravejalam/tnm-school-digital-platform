<?php

namespace App\HolidayCalendar;

class CreateHolidayCalendarRequest
{
    public function __construct(private array $payload = [])
    {
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
