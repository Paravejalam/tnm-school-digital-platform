<?php

namespace App\Timetable;

use PDO;
use Throwable;

class TimetableRepository implements TimetableRepositoryInterface
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
                $filters[] = 'timetable_name LIKE :search';
                $params['search'] = '%' . $search . '%';
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM timetables' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare('SELECT id, timetable_name, academic_session_id, class_id, section_id, subject_id, teacher_id, status FROM timetables' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): Timetable => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?Timetable
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, timetable_name, academic_session_id, class_id, section_id, subject_id, teacher_id, status FROM timetables WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByName(string $name): ?Timetable
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, timetable_name, academic_session_id, class_id, section_id, subject_id, teacher_id, status FROM timetables WHERE timetable_name = :name AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['name' => $name]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): Timetable
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO timetables (timetable_name, academic_session_id, class_id, section_id, subject_id, teacher_id, status) VALUES (:timetable_name, :academic_session_id, :class_id, :section_id, :subject_id, :teacher_id, :status)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapEntity($attributes);
    }

    public function update(int $id, array $attributes): ?Timetable
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof Timetable ? TimetableResponse::fromEntity($current) : [], $attributes);
            $statement = $this->database->prepare('UPDATE timetables SET timetable_name = :timetable_name, academic_session_id = :academic_session_id, class_id = :class_id, section_id = :section_id, subject_id = :subject_id, teacher_id = :teacher_id, status = :status WHERE id = :id AND deleted_at IS NULL');
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
            $statement = $this->database->prepare('UPDATE timetables SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        return [
            'timetable_name' => $attributes['timetable_name'] ?? null,
            'academic_session_id' => $attributes['academic_session_id'] ?? null,
            'class_id' => $attributes['class_id'] ?? null,
            'section_id' => $attributes['section_id'] ?? null,
            'subject_id' => $attributes['subject_id'] ?? null,
            'teacher_id' => $attributes['teacher_id'] ?? null,
            'status' => $attributes['status'] ?? 'active',
        ];
    }

    private function mapEntity(array $row): Timetable
    {
        return new Timetable(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['timetable_name'] ?? null,
            isset($row['academic_session_id']) ? (int) $row['academic_session_id'] : null,
            isset($row['class_id']) ? (int) $row['class_id'] : null,
            isset($row['section_id']) ? (int) $row['section_id'] : null,
            isset($row['subject_id']) ? (int) $row['subject_id'] : null,
            isset($row['teacher_id']) ? (int) $row['teacher_id'] : null,
            $row['status'] ?? 'active'
        );
    }
}
