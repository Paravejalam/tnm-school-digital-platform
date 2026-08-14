<?php

namespace App\Role;

interface RoleServiceInterface
{
    /**
     * @return array{items: list<Role>, total: int, page: int, per_page: int}
     */
    public function list(RoleListRequest $request): array;

    public function find(int $id): ?Role;
}
