<?php

namespace App\Student;

interface StudentRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $name = null, ?string $admissionNumber = null): array;

    public function findById(int $id): ?Student;

    public function findByAdmissionNumber(string $admissionNumber): ?Student;

    public function create(array $attributes): Student;

    public function update(int $id, array $attributes): ?Student;

    public function delete(int $id): bool;
}