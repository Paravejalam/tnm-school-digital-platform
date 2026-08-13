<?php

namespace App\AcademicSession;

use PDO;
use Throwable;

class AcademicSessionRepository implements AcademicSessionRepositoryInterface
{
    public function __construct(private ?PDO $database = null)
    {
    }

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        if (!$this->database instanceof PDO) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        try {
            $filters = ['deleted_at IS NULL'];
            $params = [];

            if ($search !== null) {
                $filters[] = 'session_name LIKE :search';
                $params['search'] = '%' . $search . '%';
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM academic_sessions' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare('SELECT id, session_name, start_date, end_date, status, is_current, created_at, updated_at FROM academic_sessions' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): AcademicSession => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?AcademicSession
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, session_name, start_date, end_date, status, is_current, created_at, updated_at FROM academic_sessions WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByName(string $name): ?AcademicSession
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, session_name, start_date, end_date, status, is_current, created_at, updated_at FROM academic_sessions WHERE session_name = :name AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['name' => $name]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): AcademicSession
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO academic_sessions (session_name, start_date, end_date, status, is_current) VALUES (:session_name, :start_date, :end_date, :status, :is_current)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapEntity($attributes);
    }

    public function update(int $id, array $attributes): ?AcademicSession
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        if (!$current instanceof AcademicSession) {
            return null;
        }

        try {
            $merged = [
                'session_name' => $current->sessionName(),
                'start_date' => $current->startDate(),
                'end_date' => $current->endDate(),
                'status' => $current->status() ?? 'active',
                'is_current' => $current->isCurrent() ?? 0,
            ];
            $merged = array_merge($merged, $attributes);
            $statement = $this->database->prepare('UPDATE academic_sessions SET session_name = :session_name, start_date = :start_date, end_date = :end_date, status = :status, is_current = :is_current WHERE id = :id AND deleted_at IS NULL');
            $params = $this->attributes($merged);
            $params['id'] = $id;
            $statement->execute($params);
        } catch (Throwable) {
        }

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        if (!$this->database instanceof PDO) {
            return false;
        }

        try {
            $statement = $this->database->prepare('UPDATE academic_sessions SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        return [
            'session_name' => $attributes['session_name'] ?? null,
            'start_date' => $attributes['start_date'] ?? null,
            'end_date' => $attributes['end_date'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'is_current' => $attributes['is_current'] ?? null,
        ];
    }

    private function mapEntity(array $row): AcademicSession
    {
        return new AcademicSession(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['session_name'] ?? null,
            $row['status'] ?? 'active',
            $row['start_date'] ?? null,
            $row['end_date'] ?? null,
            isset($row['is_current']) ? (int) $row['is_current'] : null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            null,
        );
    }
}
