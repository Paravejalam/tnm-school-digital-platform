<?php

namespace App\AcademicSession;

interface AcademicSessionRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?AcademicSession;

    public function findByName(string $name): ?AcademicSession;

    public function create(array $attributes): AcademicSession;

    public function update(int $id, array $attributes): ?AcademicSession;

    public function delete(int $id): bool;
}
