<?php

namespace App\Audit;

interface AuditLoggerInterface
{
    public function log(string $action, string $entityType, ?int $entityId, ?array $oldValues = null, ?array $newValues = null): void;
}
