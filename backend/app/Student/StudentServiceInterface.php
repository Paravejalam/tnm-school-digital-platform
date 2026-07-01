<?php

namespace App\Student;

interface StudentServiceInterface
{
    public function list(StudentListRequest $request): array;

    public function find(int $id): ?Student;

    public function create(CreateStudentRequest $request): Student;

    public function update(UpdateStudentRequest $request): ?Student;

    public function delete(int $id): bool;
}