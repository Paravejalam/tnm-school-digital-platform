<?php

namespace App\Role;

/**
 * Role administration data access contract.
 *
 * Backs the /roles resource (API-007). RBAC grants only roles.view and
 * roles.assign — there are no seeded roles.create/update/delete
 * permissions — so the module exposes paginated listing and single-role
 * reads (including assigned permission slugs). Roles are immutable via
 * the API and protected by the seeded RBAC model.
 *
 * Authority: .github/AGENT.md
 */
interface RoleRepositoryInterface
{
    /**
     * @return array{items: list<Role>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page, int $perPage, ?string $search = null): array;

    public function findById(int $id): ?Role;

    /**
     * @return list<string>
     */
    public function findPermissionSlugs(int $roleId): array;
}
