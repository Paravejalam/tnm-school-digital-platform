<?php

namespace App\Permission;

class Permission
{
    public function __construct(
        private ?int $id = null,
        private ?string $name = null,
        private ?string $slug = null,
        private ?string $module = null,
        private ?string $description = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
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

    public function module(): ?string
    {
        return $this->module;
    }

    public function description(): ?string
    {
        return $this->description;
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
