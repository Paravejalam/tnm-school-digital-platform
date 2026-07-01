<?php

namespace App\AcademicClass;

interface AcademicClassServiceInterface
{
    public function list(AcademicClassListRequest $request): array;

    public function find(int $id): ?AcademicClass;

    public function create(CreateAcademicClassRequest $request): AcademicClass;

    public function update(UpdateAcademicClassRequest $request): ?AcademicClass;

    public function delete(int $id): bool;
}
