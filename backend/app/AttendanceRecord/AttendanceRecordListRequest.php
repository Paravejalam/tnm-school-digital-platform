<?php

namespace App\AttendanceRecord;

class AttendanceRecordListRequest
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

    public function search(): ?string
    {
        $search = trim((string) ($this->query['search'] ?? $this->query['name'] ?? ''));

        return $search !== '' ? $search : null;
    }
}
