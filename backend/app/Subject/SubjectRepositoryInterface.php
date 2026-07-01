<?php

namespace App\Subject;

interface SubjectRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?Subject;

    public function findByName(string $name): ?Subject;

    public function create(array $attributes): Subject;

    public function update(int $id, array $attributes): ?Subject;

    public function delete(int $id): bool;
}
