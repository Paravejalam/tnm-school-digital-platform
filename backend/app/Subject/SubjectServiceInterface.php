<?php

namespace App\Subject;

interface SubjectServiceInterface
{
    public function list(SubjectListRequest $request): array;

    public function find(int $id): ?Subject;

    public function create(CreateSubjectRequest $request): Subject;

    public function update(UpdateSubjectRequest $request): ?Subject;

    public function delete(int $id): bool;
}
