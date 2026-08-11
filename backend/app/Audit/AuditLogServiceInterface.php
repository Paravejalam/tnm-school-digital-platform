<?php

namespace App\Audit;

interface AuditLogServiceInterface
{
    /**
     * @return array{items: list<AuditLog>, total: int, page: int, per_page: int}
     */
    public function list(AuditLogListRequest $request): array;

    public function find(int $id): ?AuditLog;
}
