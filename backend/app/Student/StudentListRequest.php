<?php

namespace App\Student;

class StudentListRequest
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

    public function admissionNumber(): ?string
    {
        $admissionNumber = trim((string) ($this->query['admission_number'] ?? ''));

        return $admissionNumber !== '' ? $admissionNumber : null;
    }
}