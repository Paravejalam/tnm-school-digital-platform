<?php

namespace App\Audit;

interface AuditLogRepositoryInterface
{
    public function create(array $attributes): AuditLog;
}
