<?php

namespace App\SystemSetting;

class SystemSettingListRequest
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

    public function module(): ?string
    {
        $module = trim((string) ($this->query['module'] ?? ''));

        return $module !== '' ? $module : null;
    }

    public function isPublic(): ?bool
    {
        if (!array_key_exists('is_public', $this->query)) {
            return null;
        }

        $value = $this->query['is_public'];

        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
