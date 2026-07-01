<?php

namespace App\Section;

interface SectionServiceInterface
{
    public function list(SectionListRequest $request): array;

    public function find(int $id): ?Section;

    public function create(CreateSectionRequest $request): Section;

    public function update(UpdateSectionRequest $request): ?Section;

    public function delete(int $id): bool;
}
