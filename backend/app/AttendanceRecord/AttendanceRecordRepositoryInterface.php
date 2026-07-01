<?php

namespace App\AttendanceRecord;

interface AttendanceRecordRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?AttendanceRecord;

    public function findByName(string $name): ?AttendanceRecord;

    public function create(array $attributes): AttendanceRecord;

    public function update(int $id, array $attributes): ?AttendanceRecord;

    public function delete(int $id): bool;
}
