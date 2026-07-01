<?php

namespace App\HolidayCalendar;

interface HolidayCalendarRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?HolidayCalendar;

    public function findByName(string $name): ?HolidayCalendar;

    public function create(array $attributes): HolidayCalendar;

    public function update(int $id, array $attributes): ?HolidayCalendar;

    public function delete(int $id): bool;
}
