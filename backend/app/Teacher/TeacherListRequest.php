<?php

namespace App\Teacher;

class TeacherListRequest
{
    public function __construct(private array $query = [])
    {
    }

    public function page(): int
    {
        return max(1, (int) ($this->query['page'] ?? 1));
    }

    public function perPage(): int
    {
        return min(100, max(1, (int) ($this->query['per_page'] ?? 15)));
    }

    public function name(): ?string
    {
        $name = trim((string) ($this->query['name'] ?? ''));

        return $name !== '' ? $name : null;
    }

    public function employeeId(): ?string
    {
        $employeeId = trim((string) ($this->query['employee_id'] ?? ''));

        return $employeeId !== '' ? $employeeId : null;
    }
}