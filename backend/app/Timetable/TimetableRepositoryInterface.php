<?php

namespace App\Timetable;

interface TimetableRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?Timetable;

    public function findByName(string $name): ?Timetable;

    public function create(array $attributes): Timetable;

    public function update(int $id, array $attributes): ?Timetable;

    public function delete(int $id): bool;
}
