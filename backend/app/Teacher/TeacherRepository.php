<?php

namespace App\Teacher;

use PDO;
use Throwable;

class TeacherRepository implements TeacherRepositoryInterface
{
    public function __construct(private ?PDO $database = null)
    {
    }

    public function paginate(int $page, int $perPage, ?string $name = null, ?string $employeeId = null): array
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

            if ($employeeId !== null) {
                $filters[] = 'employee_id = :employee_id';
                $params['employee_id'] = $employeeId;
            }

            $where = ' WHERE ' . implode(' AND ', $filters);
            $count = $this->database->prepare('SELECT COUNT(*) FROM teachers' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare(
                'SELECT id, user_id, employee_id, first_name, last_name, email, phone, department, designation, gender, date_joined, status, created_at, updated_at FROM teachers' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
            );

            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): Teacher => $this->mapTeacher($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?Teacher
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, user_id, employee_id, first_name, last_name, email, phone, department, designation, gender, date_joined, status, created_at, updated_at FROM teachers WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapTeacher($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByEmployeeId(string $employeeId): ?Teacher
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, user_id, employee_id, first_name, last_name, email, phone, department, designation, gender, date_joined, status, created_at, updated_at FROM teachers WHERE employee_id = :employee_id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['employee_id' => $employeeId]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapTeacher($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): Teacher
    {
        if (!$this->database instanceof PDO) {
            return $this->mapTeacher($attributes);
        }

        try {
            $statement = $this->database->prepare('INSERT INTO teachers (user_id, employee_id, first_name, last_name, email, phone, department, designation, gender, date_joined, status) VALUES (:user_id, :employee_id, :first_name, :last_name, :email, :phone, :department, :designation, :gender, :date_joined, :status)');
            $statement->execute($this->attributes($attributes));
            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapTeacher($attributes);
    }

    public function update(int $id, array $attributes): ?Teacher
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $merged = array_merge($current instanceof Teacher ? TeacherResponse::fromTeacher($current) : [], $attributes);
            $statement = $this->database->prepare('UPDATE teachers SET user_id = :user_id, employee_id = :employee_id, first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, department = :department, designation = :designation, gender = :gender, date_joined = :date_joined, status = :status WHERE id = :id AND deleted_at IS NULL');
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
            $statement = $this->database->prepare('UPDATE teachers SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
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
            'employee_id' => $attributes['employee_id'] ?? null,
            'first_name' => $attributes['first_name'] ?? null,
            'last_name' => $attributes['last_name'] ?? null,
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'department' => $attributes['department'] ?? null,
            'designation' => $attributes['designation'] ?? null,
            'gender' => $attributes['gender'] ?? null,
            'date_joined' => $attributes['date_joined'] ?? null,
            'status' => $attributes['status'] ?? 'active',
        ];
    }

    private function mapTeacher(array $row): Teacher
    {
        return new Teacher(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['employee_id'] ?? null,
            $row['first_name'] ?? null,
            $row['last_name'] ?? null,
            $row['email'] ?? null,
            $row['phone'] ?? null,
            $row['department'] ?? null,
            $row['designation'] ?? null,
            $row['status'] ?? 'active',
            isset($row['user_id']) ? (int) $row['user_id'] : null,
            $row['gender'] ?? null,
            $row['date_joined'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            null,
        );
    }
}
