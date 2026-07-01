<?php

namespace App\Period;

interface PeriodRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?Period;

    public function findByName(string $name): ?Period;

    public function create(array $attributes): Period;

    public function update(int $id, array $attributes): ?Period;

    public function delete(int $id): bool;
}
