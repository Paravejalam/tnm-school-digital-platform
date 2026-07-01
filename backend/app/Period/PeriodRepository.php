<?php

namespace App\Period;

use PDO;
use Throwable;

class PeriodRepository implements PeriodRepositoryInterface
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
                $filters[] = 'period_name LIKE :search';
                $params['search'] = '%' . $search . '%';
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM periods' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare('SELECT id, period_name, timetable_id, status FROM periods' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): Period => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?Period
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, period_name, timetable_id, status FROM periods WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByName(string $name): ?Period
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, period_name, timetable_id, status FROM periods WHERE period_name = :name AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['name' => $name]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): Period
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO periods (period_name, timetable_id, status) VALUES (:period_name, :timetable_id, :status)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapEntity($attributes);
    }

    public function update(int $id, array $attributes): ?Period
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof Period ? PeriodResponse::fromEntity($current) : [], $attributes);
            $statement = $this->database->prepare('UPDATE periods SET period_name = :period_name, timetable_id = :timetable_id, status = :status WHERE id = :id AND deleted_at IS NULL');
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
            $statement = $this->database->prepare('UPDATE periods SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        return [
            'period_name' => $attributes['period_name'] ?? null,
            'timetable_id' => $attributes['timetable_id'] ?? null,
            'status' => $attributes['status'] ?? 'active',
        ];
    }

    private function mapEntity(array $row): Period
    {
        return new Period(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['period_name'] ?? null,
            isset($row['timetable_id']) ? (int) $row['timetable_id'] : null,
            $row['status'] ?? 'active'
        );
    }
}
