<?php

namespace App\Audit;

use PDO;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function __construct(private ?PDO $database = null)
    {
    }

    public function create(array $attributes): AuditLog
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        $statement = $this->database->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)'
        );
        $statement->execute($this->attributes($attributes));
        $attributes['id'] = (int) $this->database->lastInsertId();

        return $this->mapEntity($attributes);
    }

    private function attributes(array $attributes): array
    {
        return [
            'user_id' => $attributes['user_id'] ?? null,
            'action' => $attributes['action'] ?? null,
            'entity_type' => $attributes['entity_type'] ?? null,
            'entity_id' => $attributes['entity_id'] ?? null,
            'old_values' => $attributes['old_values'] ?? null,
            'new_values' => $attributes['new_values'] ?? null,
            'ip_address' => $attributes['ip_address'] ?? null,
            'user_agent' => $attributes['user_agent'] ?? null,
        ];
    }

    private function mapEntity(array $row): AuditLog
    {
        return new AuditLog(
            isset($row['id']) ? (int) $row['id'] : null,
            isset($row['user_id']) ? (int) $row['user_id'] : null,
            $row['action'] ?? null,
            $row['entity_type'] ?? null,
            isset($row['entity_id']) ? (int) $row['entity_id'] : null,
            $row['old_values'] ?? null,
            $row['new_values'] ?? null,
            $row['ip_address'] ?? null,
            $row['user_agent'] ?? null,
            $row['created_at'] ?? null,
        );
    }
}
