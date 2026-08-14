<?php

namespace App\Student;

use PDO;
use Throwable;

class StudentRepository implements StudentRepositoryInterface
{
    public function __construct(private ?PDO $database = null)
    {
    }

    public function paginate(int $page, int $perPage, ?string $name = null, ?string $admissionNumber = null): array
    {
        if (!$this->database instanceof PDO) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        try {
            $filters = ['deleted_at IS NULL'];
            $params = [];

            if ($name !== null) {
                $filters[] = '(first_name LIKE :name OR last_name LIKE :name)';
                $params['name'] = '%' . $name . '%';
            }

            if ($admissionNumber !== null) {
                $filters[] = 'admission_number = :admission_number';
                $params['admission_number'] = $admissionNumber;
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM students' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare(
                'SELECT id, user_id, admission_number, roll_number, first_name, last_name, email, phone, date_of_birth, gender, academic_session_id, class_id, section_id, class_name, section, status, created_at, updated_at FROM students' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
            );

            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): Student => $this->mapStudent($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?Student
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, user_id, admission_number, roll_number, first_name, last_name, email, phone, date_of_birth, gender, academic_session_id, class_id, section_id, class_name, section, status, created_at, updated_at FROM students WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapStudent($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByAdmissionNumber(string $admissionNumber): ?Student
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, user_id, admission_number, roll_number, first_name, last_name, email, phone, date_of_birth, gender, academic_session_id, class_id, section_id, class_name, section, status, created_at, updated_at FROM students WHERE admission_number = :admission_number AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['admission_number' => $admissionNumber]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapStudent($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): Student
    {
        if (!$this->database instanceof PDO) {
            return $this->mapStudent($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO students (user_id, admission_number, roll_number, first_name, last_name, email, phone, date_of_birth, gender, academic_session_id, class_id, section_id, class_name, section, status) VALUES (:user_id, :admission_number, :roll_number, :first_name, :last_name, :email, :phone, :date_of_birth, :gender, :academic_session_id, :class_id, :section_id, :class_name, :section, :status)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapStudent($attributes);
    }

    public function update(int $id, array $attributes): ?Student
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof Student ? StudentResponse::fromStudent($current) : [], $attributes);
            $statement = $this->database->prepare('UPDATE students SET user_id = :user_id, admission_number = :admission_number, roll_number = :roll_number, first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, date_of_birth = :date_of_birth, gender = :gender, academic_session_id = :academic_session_id, class_id = :class_id, section_id = :section_id, class_name = :class_name, section = :section, status = :status WHERE id = :id AND deleted_at IS NULL');
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
            $statement = $this->database->prepare('UPDATE students SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function attributes(array $attributes): array
    {
        return [
            'user_id' => $attributes['user_id'] ?? null,
            'admission_number' => $attributes['admission_number'] ?? null,
            'roll_number' => $attributes['roll_number'] ?? null,
            'first_name' => $attributes['first_name'] ?? null,
            'last_name' => $attributes['last_name'] ?? null,
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'date_of_birth' => $attributes['date_of_birth'] ?? null,
            'gender' => $attributes['gender'] ?? null,
            'academic_session_id' => $attributes['academic_session_id'] ?? null,
            'class_id' => $attributes['class_id'] ?? null,
            'section_id' => $attributes['section_id'] ?? null,
            'class_name' => $attributes['class_name'] ?? null,
            'section' => $attributes['section'] ?? null,
            'status' => $attributes['status'] ?? 'active',
        ];
    }

    private function mapStudent(array $row): Student
    {
        return new Student(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['admission_number'] ?? null,
            $row['first_name'] ?? null,
            $row['last_name'] ?? null,
            $row['email'] ?? null,
            $row['phone'] ?? null,
            $row['class_name'] ?? null,
            $row['section'] ?? null,
            $row['status'] ?? 'active',
            isset($row['user_id']) ? (int) $row['user_id'] : null,
            $row['roll_number'] ?? null,
            $row['date_of_birth'] ?? null,
            $row['gender'] ?? null,
            isset($row['academic_session_id']) ? (int) $row['academic_session_id'] : null,
            isset($row['class_id']) ? (int) $row['class_id'] : null,
            isset($row['section_id']) ? (int) $row['section_id'] : null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            null,
        );
    }
}
