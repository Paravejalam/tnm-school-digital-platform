<?php

namespace App\Audit;

interface AuditLogRepositoryInterface
{
    public function create(array $attributes): AuditLog;

    /**
     * @return array{items: list<AuditLog>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page, int $perPage, array $filters = []): array;

    public function findById(int $id): ?AuditLog;
}
