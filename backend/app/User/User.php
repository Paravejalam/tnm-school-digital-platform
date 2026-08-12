<?php

namespace App\User;

class User
{
    /**
     * @param list<int> $roleIds
     * @param list<string> $roleSlugs
     */
    public function __construct(
        private ?int $id = null,
        private ?string $name = null,
        private ?string $email = null,
        private ?string $passwordHash = null,
        private ?bool $isActive = null,
        private ?string $lastLoginAt = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private array $roleIds = [],
        private array $roleSlugs = []
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

    public function email(): ?string
    {
        return $this->email;
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function lastLoginAt(): ?string
    {
        return $this->lastLoginAt;
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
     * @return list<int>
     */
    public function roleIds(): array
    {
        return $this->roleIds;
    }

    /**
     * @return list<string>
     */
    public function roleSlugs(): array
    {
        return $this->roleSlugs;
    }
}
