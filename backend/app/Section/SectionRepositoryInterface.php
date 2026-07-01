<?php

namespace App\Section;

interface SectionRepositoryInterface
{
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?Section;

    public function findByName(string $name): ?Section;

    public function create(array $attributes): Section;

    public function update(int $id, array $attributes): ?Section;

    public function delete(int $id): bool;
}
