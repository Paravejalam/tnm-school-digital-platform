<?php

namespace App\User;

class UserListRequest
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
        $search = trim((string) ($this->query['search'] ?? ''));

        return $search !== '' ? $search : null;
    }

    public function isActive(): ?bool
    {
        if (!array_key_exists('is_active', $this->query)) {
            return null;
        }

        $value = $this->query['is_active'];

        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
