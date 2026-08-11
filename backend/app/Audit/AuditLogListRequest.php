<?php

namespace App\Audit;

class AuditLogListRequest
{
    public function __construct(private array $query = [])
    {
    }

    public function page(): int
    {
        return max(1, (int) ($this->query['page'] ?? 1));
    }

    public function perPage(): int
    {
        return min(100, max(1, (int) ($this->query['per_page'] ?? 15)));
    }

    public function filters(): array
    {
        $filters = [];

        $action = trim((string) ($this->query['action'] ?? ''));
        if ($action !== '') {
            $filters['action'] = $action;
        }

        $entityType = trim((string) ($this->query['entity_type'] ?? ''));
        if ($entityType !== '') {
            $filters['entity_type'] = $entityType;
        }

        if (array_key_exists('user_id', $this->query) && is_numeric($this->query['user_id']) && (int) $this->query['user_id'] > 0) {
            $filters['user_id'] = (int) $this->query['user_id'];
        }

        if (array_key_exists('entity_id', $this->query) && is_numeric($this->query['entity_id']) && (int) $this->query['entity_id'] > 0) {
            $filters['entity_id'] = (int) $this->query['entity_id'];
        }

        return $filters;
    }
}
