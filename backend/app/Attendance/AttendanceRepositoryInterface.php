<?php

namespace App\Attendance;

interface AttendanceRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?Attendance;

    public function findByName(string $name): ?Attendance;

    public function create(array $attributes): Attendance;

    public function update(int $id, array $attributes): ?Attendance;

    public function delete(int $id): bool;
}
