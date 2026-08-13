<?php

namespace App\AttendanceRecord;

use PDO;
use Throwable;

class AttendanceRecordRepository implements AttendanceRecordRepositoryInterface
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
                $filters[] = 'note LIKE :search';
                $params['search'] = '%' . $search . '%';
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM attendance_records' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare('SELECT id, attendance_id, status, note, recorded_by, recorded_at, created_at, updated_at FROM attendance_records' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): AttendanceRecord => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?AttendanceRecord
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, attendance_id, status, note, recorded_by, recorded_at, created_at, updated_at FROM attendance_records WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByName(string $name): ?AttendanceRecord
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, attendance_id, status, note, recorded_by, recorded_at, created_at, updated_at FROM attendance_records WHERE note = :name AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['name' => $name]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): AttendanceRecord
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO attendance_records (attendance_id, status, note, recorded_by) VALUES (:attendance_id, :status, :note, :recorded_by)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapEntity($attributes);
    }

    public function update(int $id, array $attributes): ?AttendanceRecord
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof AttendanceRecord ? [
                'attendance_id' => $current->attendanceId(),
                'status' => $current->status(),
                'note' => $current->note(),
                'recorded_by' => $current->recordedBy(),
            ] : [], $attributes);
            $statement = $this->database->prepare('UPDATE attendance_records SET attendance_id = :attendance_id, status = :status, note = :note, recorded_by = :recorded_by WHERE id = :id AND deleted_at IS NULL');
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
            $statement = $this->database->prepare('UPDATE attendance_records SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        return [
            'attendance_id' => $attributes['attendance_id'] ?? null,
            'status' => $attributes['status'] ?? 'present',
            'note' => $attributes['note'] ?? null,
            'recorded_by' => $attributes['recorded_by'] ?? null,
        ];
    }

    private function mapEntity(array $row): AttendanceRecord
    {
        return new AttendanceRecord(
            isset($row['id']) ? (int) $row['id'] : null,
            null,
            isset($row['attendance_id']) ? (int) $row['attendance_id'] : null,
            null,
            $row['status'] ?? 'present',
            $row['note'] ?? null,
            isset($row['recorded_by']) ? (int) $row['recorded_by'] : null,
            $row['recorded_at'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            null,
        );
    }
}
