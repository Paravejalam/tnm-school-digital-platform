<?php

namespace App\AcademicClass;

interface AcademicClassRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?AcademicClass;

    public function findByName(string $name): ?AcademicClass;

    public function create(array $attributes): AcademicClass;

    public function update(int $id, array $attributes): ?AcademicClass;

    public function delete(int $id): bool;
}
