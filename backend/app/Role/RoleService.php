<?php

namespace App\Role;

class RoleService implements RoleServiceInterface
{
    public function __construct(private RoleRepositoryInterface $repository)
    {
    }

    public function list(RoleListRequest $request): array
    {
        return $this->repository->paginate(
            $request->page(),
            $request->perPage(),
            $request->search()
        );
    }

    public function find(int $id): ?Role
    {
        return $this->repository->findById($id);
    }
}
