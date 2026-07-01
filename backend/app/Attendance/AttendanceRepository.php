<?php

namespace App\Attendance;

use PDO;
use Throwable;

class AttendanceRepository implements AttendanceRepositoryInterface
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
                $filters[] = 'attendance_date LIKE :search';
                $params['search'] = '%' . $search . '%';
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM attendance' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare('SELECT id, attendance_date, academic_session_id, class_id, section_id, student_id, status FROM attendance' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): Attendance => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?Attendance
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, attendance_date, academic_session_id, class_id, section_id, student_id, status FROM attendance WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByName(string $name): ?Attendance
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, attendance_date, academic_session_id, class_id, section_id, student_id, status FROM attendance WHERE attendance_date = :name AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['name' => $name]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): Attendance
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO attendance (attendance_date, academic_session_id, class_id, section_id, student_id, status) VALUES (:attendance_date, :academic_session_id, :class_id, :section_id, :student_id, :status)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapEntity($attributes);
    }

    public function update(int $id, array $attributes): ?Attendance
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof Attendance ? AttendanceResponse::fromEntity($current) : [], $attributes);
            $statement = $this->database->prepare('UPDATE attendance SET attendance_date = :attendance_date, academic_session_id = :academic_session_id, class_id = :class_id, section_id = :section_id, student_id = :student_id, status = :status WHERE id = :id AND deleted_at IS NULL');
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
            $statement = $this->database->prepare('UPDATE attendance SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        return [
            'attendance_date' => $attributes['attendance_date'] ?? null,
            'academic_session_id' => $attributes['academic_session_id'] ?? null,
            'class_id' => $attributes['class_id'] ?? null,
            'section_id' => $attributes['section_id'] ?? null,
            'student_id' => $attributes['student_id'] ?? null,
            'status' => $attributes['status'] ?? 'active',
        ];
    }

    private function mapEntity(array $row): Attendance
    {
        return new Attendance(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['attendance_date'] ?? null,
            isset($row['academic_session_id']) ? (int) $row['academic_session_id'] : null,
            isset($row['class_id']) ? (int) $row['class_id'] : null,
            isset($row['section_id']) ? (int) $row['section_id'] : null,
            isset($row['student_id']) ? (int) $row['student_id'] : null,
            $row['status'] ?? 'active'
        );
    }
}
