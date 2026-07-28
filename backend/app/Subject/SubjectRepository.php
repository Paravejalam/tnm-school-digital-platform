<?php

namespace App\Subject;

use PDO;
use Throwable;

class SubjectRepository implements SubjectRepositoryInterface
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
                $filters[] = 'name LIKE :search';
                $params['search'] = '%' . $search . '%';
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM subjects' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare('SELECT id, name, code, description, status, created_at, updated_at FROM subjects' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): Subject => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?Subject
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, name, code, description, status, created_at, updated_at FROM subjects WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByName(string $name): ?Subject
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, name, code, description, status, created_at, updated_at FROM subjects WHERE name = :name AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['name' => $name]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): Subject
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO subjects (name, code, description, status) VALUES (:name, :code, :description, :status)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapEntity($attributes);
    }

    public function update(int $id, array $attributes): ?Subject
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof Subject ? SubjectResponse::fromEntity($current) : [], $attributes);
            $statement = $this->database->prepare('UPDATE subjects SET name = :name, code = :code, description = :description, status = :status WHERE id = :id AND deleted_at IS NULL');
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
            $statement = $this->database->prepare('UPDATE subjects SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        return [
            'name' => $attributes['name'] ?? null,
            'code' => $attributes['code'] ?? null,
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? 'active',
        ];
    }

    private function mapEntity(array $row): Subject
    {
        return new Subject(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['code'] ?? null,
            null,
            $row['status'] ?? 'active',
            $row['description'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            null,
        );
    }
}
