<?php

namespace App\HolidayCalendar;

interface HolidayCalendarServiceInterface
{
    public function list(HolidayCalendarListRequest $request): array;

    public function find(int $id): ?HolidayCalendar;

    public function create(CreateHolidayCalendarRequest $request): HolidayCalendar;

    public function update(UpdateHolidayCalendarRequest $request): ?HolidayCalendar;

    public function delete(int $id): bool;
}
