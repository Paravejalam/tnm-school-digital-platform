<?php

namespace App\HolidayCalendar;

use PDO;
use Throwable;

class HolidayCalendarRepository implements HolidayCalendarRepositoryInterface
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
                $filters[] = 'holiday_name LIKE :search';
                $params['search'] = '%' . $search . '%';
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM holiday_calendars' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare('SELECT id, academic_session_id, holiday_name, holiday_date, holiday_type, is_recurring, description, status, created_at, updated_at FROM holiday_calendars' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): HolidayCalendar => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?HolidayCalendar
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, academic_session_id, holiday_name, holiday_date, holiday_type, is_recurring, description, status, created_at, updated_at FROM holiday_calendars WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByName(string $name): ?HolidayCalendar
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, academic_session_id, holiday_name, holiday_date, holiday_type, is_recurring, description, status, created_at, updated_at FROM holiday_calendars WHERE holiday_name = :name AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['name' => $name]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): HolidayCalendar
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO holiday_calendars (academic_session_id, holiday_name, holiday_date, holiday_type, is_recurring, description, status) VALUES (:academic_session_id, :holiday_name, :holiday_date, :holiday_type, :is_recurring, :description, :status)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapEntity($attributes);
    }

    public function update(int $id, array $attributes): ?HolidayCalendar
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof HolidayCalendar ? HolidayCalendarResponse::fromEntity($current) : [], $attributes);
            $statement = $this->database->prepare('UPDATE holiday_calendars SET academic_session_id = :academic_session_id, holiday_name = :holiday_name, holiday_date = :holiday_date, holiday_type = :holiday_type, is_recurring = :is_recurring, description = :description, status = :status WHERE id = :id AND deleted_at IS NULL');
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
            $statement = $this->database->prepare('UPDATE holiday_calendars SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        return [
            'academic_session_id' => $attributes['academic_session_id'] ?? null,
            'holiday_name' => $attributes['holiday_name'] ?? null,
            'holiday_date' => $attributes['holiday_date'] ?? null,
            'holiday_type' => $attributes['holiday_type'] ?? null,
            'is_recurring' => $attributes['is_recurring'] ?? null,
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? 'active',
        ];
    }

    private function mapEntity(array $row): HolidayCalendar
    {
        return new HolidayCalendar(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['holiday_name'] ?? null,
            isset($row['academic_session_id']) ? (int) $row['academic_session_id'] : null,
            $row['status'] ?? 'active',
            $row['holiday_date'] ?? null,
            $row['holiday_type'] ?? null,
            isset($row['is_recurring']) ? (int) $row['is_recurring'] : null,
            $row['description'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            null,
        );
    }
}
