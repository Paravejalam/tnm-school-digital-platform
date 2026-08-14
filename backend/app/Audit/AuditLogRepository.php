<?php

namespace App\Audit;

use PDO;
use Throwable;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    private const COLUMNS = 'id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at';

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

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        if (!$this->database instanceof PDO) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        try {
            $conditions = [];
            $params = [];

            $action = $filters['action'] ?? null;
            if (is_string($action) && $action !== '') {
                $conditions[] = 'action = :action';
                $params['action'] = $action;
            }

            $entityType = $filters['entity_type'] ?? null;
            if (is_string($entityType) && $entityType !== '') {
                $conditions[] = 'entity_type = :entity_type';
                $params['entity_type'] = $entityType;
            }

            $userId = $filters['user_id'] ?? null;
            if (is_numeric($userId) && (int) $userId > 0) {
                $conditions[] = 'user_id = :user_id';
                $params['user_id'] = (int) $userId;
            }

            $entityId = $filters['entity_id'] ?? null;
            if (is_numeric($entityId) && (int) $entityId > 0) {
                $conditions[] = 'entity_id = :entity_id';
                $params['entity_id'] = (int) $entityId;
            }

            $where = $conditions !== [] ? ' WHERE ' . implode(' AND ', $conditions) : '';

            $count = $this->database->prepare('SELECT COUNT(*) FROM audit_logs' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare(
                'SELECT ' . self::COLUMNS . ' FROM audit_logs' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
            );
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): AuditLog => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?AuditLog
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT ' . self::COLUMNS . ' FROM audit_logs WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
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
