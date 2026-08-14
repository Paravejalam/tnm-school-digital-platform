<?php

namespace App\Permission;

class PermissionService implements PermissionServiceInterface
{
    public function __construct(private PermissionRepositoryInterface $repository)
    {
    }

    public function list(PermissionListRequest $request): array
    {
        return $this->repository->paginate(
            $request->page(),
            $request->perPage(),
            $request->module(),
            $request->search()
        );
    }

    public function find(int $id): ?Permission
    {
        return $this->repository->findById($id);
    }
}
