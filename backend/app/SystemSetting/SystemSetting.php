<?php

namespace App\SystemSetting;

/**
 * System Setting entity.
 *
 * Mirrors database/migrations/0019_create_system_settings_table.sql.
 * The `system_settings` table has no soft-delete column; settings are
 * seed-managed and only updated at runtime.
 *
 * Authority: .github/AGENT.md
 */
class SystemSetting
{
    public function __construct(
        private ?int $id = null,
        private ?string $key = null,
        private mixed $value = null,
        private ?string $type = null,
        private ?string $module = null,
        private ?string $description = null,
        private ?int $isPublic = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function key(): ?string
    {
        return $this->key;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function module(): ?string
    {
        return $this->module;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isPublic(): ?int
    {
        return $this->isPublic;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
