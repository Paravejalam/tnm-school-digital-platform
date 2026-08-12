<?php

namespace App\Permission;

interface PermissionServiceInterface
{
    /**
     * @return array{items: list<Permission>, total: int, page: int, per_page: int}
     */
    public function list(PermissionListRequest $request): array;

    public function find(int $id): ?Permission;
}
