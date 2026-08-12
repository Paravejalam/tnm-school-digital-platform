<?php

namespace App\Permission;

/**
 * Permission catalog data access contract.
 *
 * Backs the /permissions resource (API-007). Permissions are
 * predefined and managed via seed data — there are no seeded
 * permissions.create/update/delete slugs — so the module is read-only:
 * paginated catalog listing (optionally filtered by module) and
 * single-permission reads. Access is gated by the seeded roles.view
 * permission (admin and super-admin), consistent with the Roles module.
 *
 * Authority: .github/AGENT.md
 */
interface PermissionRepositoryInterface
{
    /**
     * @return array{items: list<Permission>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page, int $perPage, ?string $module = null, ?string $search = null): array;

    public function findById(int $id): ?Permission;

    public function findBySlug(string $slug): ?Permission;
}
