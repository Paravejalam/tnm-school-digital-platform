<?php

namespace App\Teacher;

interface TeacherRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $name = null, ?string $employeeId = null): array;

    public function findById(int $id): ?Teacher;

    public function findByEmployeeId(string $employeeId): ?Teacher;

    public function create(array $attributes): Teacher;

    public function update(int $id, array $attributes): ?Teacher;

    public function delete(int $id): bool;
}