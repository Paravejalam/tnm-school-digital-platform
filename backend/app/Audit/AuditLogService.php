<?php

namespace App\Audit;

class AuditLogService implements AuditLogServiceInterface
{
    public function __construct(private AuditLogRepositoryInterface $repository)
    {
    }

    public function list(AuditLogListRequest $request): array
    {
        return $this->repository->paginate(
            $request->page(),
            $request->perPage(),
            $request->filters()
        );
    }

    public function find(int $id): ?AuditLog
    {
        return $this->repository->findById($id);
    }
}
