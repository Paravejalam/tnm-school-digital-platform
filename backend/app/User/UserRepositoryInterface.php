<?php

namespace App\User;

/**
 * User administration data access contract.
 *
 * Backs the /users resource (API-007). RBAC grants users.view,
 * users.create, users.update, users.delete and roles.assign, so the
 * module exposes pagination, read, create, update (including role
 * assignment) and soft-delete. Passwords are only ever stored as
 * bcrypt hashes via PasswordHasher.
 *
 * Authority: .github/AGENT.md
 */
interface UserRepositoryInterface
{
    /**
     * @return array{items: list<User>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page, int $perPage, ?string $search = null, ?bool $isActive = null): array;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(array $attributes): User;

    public function update(int $id, array $attributes): ?User;

    public function softDelete(int $id): bool;

    public function assignRole(int $userId, int $roleId): void;

    public function replaceRoles(int $userId, array $roleIds): void;

    public function roleExists(int $roleId): bool;

    /**
     * @return list<int>
     */
    public function findRoleIds(int $userId): array;
}
