<?php

namespace App\Timetable;

interface TimetableServiceInterface
{
    public function list(TimetableListRequest $request): array;

    public function find(int $id): ?Timetable;

    public function create(CreateTimetableRequest $request): Timetable;

    public function update(UpdateTimetableRequest $request): ?Timetable;

    public function delete(int $id): bool;
}
