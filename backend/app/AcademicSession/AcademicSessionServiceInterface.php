<?php

namespace App\AcademicSession;

interface AcademicSessionServiceInterface
{
    public function list(AcademicSessionListRequest $request): array;

    public function find(int $id): ?AcademicSession;

    public function create(CreateAcademicSessionRequest $request): AcademicSession;

    public function update(UpdateAcademicSessionRequest $request): ?AcademicSession;

    public function delete(int $id): bool;
}
