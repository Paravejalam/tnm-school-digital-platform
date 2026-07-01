<?php

namespace App\Teacher;

interface TeacherServiceInterface
{
    public function list(TeacherListRequest $request): array;

    public function find(int $id): ?Teacher;

    public function create(CreateTeacherRequest $request): Teacher;

    public function update(UpdateTeacherRequest $request): ?Teacher;

    public function delete(int $id): bool;
}