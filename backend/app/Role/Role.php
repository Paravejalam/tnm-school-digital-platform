<?php

namespace App\Role;

class Role
{
    /**
     * @param list<string> $permissionSlugs
     */
    public function __construct(
        private ?int $id = null,
        private ?string $name = null,
        private ?string $slug = null,
        private ?string $description = null,
        private ?bool $isSystem = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private array $permissionSlugs = []
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function slug(): ?string
    {
        return $this->slug;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isSystem(): ?bool
    {
        return $this->isSystem;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        return $this->permissionSlugs;
    }
}
